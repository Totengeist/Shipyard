<?php

namespace Shipyard;

use Shipyard\Models\File;
use Shipyard\Traits\CreatesUniqueIDs;
use Slim\Psr7\UploadedFile;
use Totengeist\IVParser\IVFile;

/**
 * Performs standard file management functions.
 *
 * Processing uploaded files, moving files, and determining file types.
 *
 * @todo Implement virus scanning with clamav https://stackoverflow.com/q/7648623/9882907
 * @todo Use IVParsers to implement custom internal media types
 */
class FileManager {
    use CreatesUniqueIDs;

    /**
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     *
     * @param UploadedFile $uploadedFile file uploaded file to move
     * @param int          $attempts     the number of attempts (in the event of collision)
     *
     * @return File the moved file
     */
    public static function moveUploadedFile(UploadedFile $uploadedFile, $attempts = 0) {
        $extension = pathinfo((string) $uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $media_type = $uploadedFile->getClientMediaType();
        $original_filename = pathinfo((string) $uploadedFile->getClientFilename(), PATHINFO_FILENAME);
        $basename = self::get_guid(32);
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $fullpath = self::getStorageDirectory($basename) . $filename;

        if (file_exists($fullpath)) {
            if ($attempts > 10) {
                Log::get()->channel('files')->error('We have attempted to save a file 10 times and run into naming conflicts each time');
                throw new \Exception('We have attempted to save a file 10 times and run into naming conflicts each time. Please report this to your administrator.');
            }

            usleep(30000);

            return self::moveUploadedFile($uploadedFile, $attempts++);
        }

        $uploadedFile->moveTo($fullpath);

        /** @var File $file */
        $file = File::query()->create([
            'filename' => $original_filename,
            'media_type' => self::getMediaType($fullpath),
            'extension' => $extension,
            'filepath' => $fullpath,
            'compressed' => false
        ]);

        Log::get()->channel('files')->info('Saved file.', $file->makeVisible('filepath')->attributesToArray());

        return $file;
    }

    /**
     * Get the storage directory for the file based on its hash.
     *
     * This creates a segmented directory structure for storage to reduce issues with file indexing
     * on large directories. In theory, this will reduce the number of files per directory to n/256.
     *
     * Since the storage directory is stored, this should be arbitrarily alterable to support
     * further segmenting in the future.
     *
     * @param string $hash
     *
     * @return string
     */
    public static function getStorageDirectory($hash) {
        $dir = dirname(__DIR__) . '/public/storage/' . $hash[0] . '/' . $hash[1] . '/' . $hash . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /**
     * Determine the standard media type of the file or fall back to application/octet-stream.
     *
     * UploadedFile seems to default to 'application/octet-stream' for most/all uploads, so this
     * is a 'safe' backup media type.
     *
     * @param string $filepath
     *
     * @return string
     */
    public static function getBaseMediaType($filepath) {
        $default_ftype = 'application/octet-stream';
        $finfo = new \finfo(FILEINFO_MIME);
        $determined_ftype = $finfo->file($filepath);
        if (($determined_ftype !== false) && is_string($determined_ftype) && (strlen($determined_ftype)>0)) {
            return $determined_ftype;
        }

        return $default_ftype;
    }

    /**
     * Determine the media type of the file.
     *
     * Check the standard media type first. If it is a text file, check if it is a specific
     * Introversion file.
     *
     * @param string $filepath
     *
     * @return string
     */
    public static function getMediaType($filepath) {
        $base_type = self::getBaseMediaType($filepath);
        if (explode(';', $base_type)[0] == 'text/plain') {
            /** @var string $file_contents */
            $file_contents = file_get_contents($filepath);
            $iv_type = IVFile::check_file_type($file_contents);
            if ($iv_type !== false) {
                return (string) IVFile::check_file_type($file_contents);
            }
        }

        return $base_type;
    }
}
