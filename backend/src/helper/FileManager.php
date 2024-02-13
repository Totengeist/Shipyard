<?php

namespace Shipyard;

use Shipyard\Models\File;
use Shipyard\Traits\CreatesUniqueIDs;
use Slim\Psr7\UploadedFile;

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

        return $file;
    }

    /**
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
     * @param string $filepath
     *
     * @return string
     */
    public static function getMediaType($filepath) {
        $default_ftype = 'application/octet-stream';
        $finfo = new \finfo(FILEINFO_MIME);
        $determined_ftype = $finfo->file($filepath);
        if (($determined_ftype !== false) && is_string($determined_ftype) && (strlen($determined_ftype)>0)) {
            return $determined_ftype;
        }

        return $default_ftype;
    }
}
