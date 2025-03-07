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
        $filename = self::get_guid(32);
        $fullpath = self::getStorageDirectory($filename) . $filename;

        if (file_exists($fullpath)) {
            if ($attempts > 10) {
                Log::get()->channel('files')->error('We have attempted to save a file 10 times and run into naming conflicts each time');
                throw new \Exception('We have attempted to save a file 10 times and run into naming conflicts each time. Please report this to your administrator.');
            }

            usleep(30000);

            return self::moveUploadedFile($uploadedFile, $attempts++);
        }

        $uploadedFile->moveTo($fullpath);
        $is_compressed = false;
        $final_media_type = self::getMediaType($fullpath);
        if (in_array($final_media_type, ['text/plain', 'application/tls-ship+introversion', 'application/tls-save+introversion'])) {
            try {
                self::compressFile($fullpath, $fullpath . '.gz');
                $is_compressed = true;
                unlink($fullpath);
                rename($fullpath . '.gz', $fullpath);
            } catch (\Exception $e) {
                Log::get()->channel('files')->error('Failed to compress file: ' . $e->getMessage());
            }
        }

        /** @var File $file */
        $file = File::query()->create([
            'filename' => $original_filename,
            'media_type' => $final_media_type,
            'extension' => $extension,
            'filepath' => str_replace($_SERVER['STORAGE'], '', $fullpath),
            'compressed' => $is_compressed
        ]);

        Log::get()->channel('files')->notice('Saved file.', $file->makeVisible('filepath')->attributesToArray());

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
        $dir = $_SERVER['STORAGE'] . $hash[0] . '/' . $hash[1] . '/' . $hash . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 07700, true);
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
        if (($determined_ftype !== false) && (strlen($determined_ftype)>0)) {
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
            $iv_type = IVFile::checkFileType($file_contents);
            if ($iv_type !== false) {
                return (string) $iv_type;
            }
        }

        return $base_type;
    }

    /**
     * Compresses a file using GZip compression.
     *
     * @param string $inpath  the input filepath
     * @param string $outpath the output filepath
     *
     * @return void
     */
    public static function compressFile($inpath, $outpath) {
        // Open input file
        $inFile = fopen($inpath, 'rb');
        if ($inFile === false) {
            throw new \Exception("Unable to open input file: $inpath");
        }

        // Open output file
        $gzFile = gzopen($outpath, 'wb9');
        if ($gzFile === false) {
            fclose($inFile);
            throw new \Exception("Unable to open output file: $outpath");
        }

        // Stream copy
        $length = 512 * 1024; // 512 kB
        while (!feof($inFile)) {
            if (($str = fread($inFile, $length)) === false) {
                throw new \Exception("Unable to read file: $outpath");
            }
            gzwrite($gzFile, $str);
        }

        // Close files
        fclose($inFile);
        gzclose($gzFile);
    }

    /**
     * Decompresses a file using GZip compression.
     *
     * @param string $inpath  the input filepath
     * @param string $outpath the output filepath
     *
     * @return void
     */
    public static function decompressFile($inpath, $outpath) {
        // Open input file
        $gzFile = gzopen($inpath, 'rb');
        if ($gzFile === false) {
            throw new \Exception("Unable to open input file: $outpath");
        }

        // Open output file
        $outFile = fopen($outpath, 'wb');
        if ($outFile === false) {
            fclose($gzFile);
            throw new \Exception("Unable to open output file: $inpath");
        }

        // Stream copy
        $length = 512 * 1024; // 512 kB
        while (!gzeof($gzFile)) {
            if (($str = gzread($gzFile, $length)) === false) {
                throw new \Exception("Unable to read file: $gzFile");
            }
            fwrite($outFile, $str);
        }

        // Close files
        gzclose($gzFile);
        fclose($outFile);
    }

    /**
     * Check if one or more compression formats are supported by a given request.
     *
     * @param string|string[] $compression the allowed compression types
     * @param string|string[] $encoding    the encoding to check for
     *
     * @return string[]
     */
    public static function checkSupportedCompressions($compression, $encoding) {
        if (is_array($compression)) {
            $compression = implode(',', $compression);
        }
        if (trim($compression) == '') {
            return [];
        }
        if (!is_array($encoding)) {
            $encoding = [$encoding];
        }

        $accepted = explode(',', $compression);
        $callback = function (&$v) {
            $v = mb_strtolower(trim($v));
        };
        array_walk($accepted, $callback);

        return array_intersect($encoding, $accepted);
    }

    /**
     * Get the contents of a file.
     *
     * The returned string may contain compressed or uncompressed data depending on the allowed and supported compression formats.
     *
     * @param File            $file        the file to retrieve contents from
     * @param string|string[] $accepted    the allowed compression types
     * @param string          $compression the compression type in use
     *
     * @return string
     */
    public static function getFileContents($file, $accepted = '', &$compression = 'none') {
        $str = '';
        $gzip = count(self::checkSupportedCompressions($accepted, 'gzip'))>0;
        // If the file is compressed, but the requester does not accept the compression format, then decompress the data
        if (!$gzip && $file->compressed) {
            if (($handle = gzopen($file->getFilePath(), 'r')) === false || ($str = stream_get_contents($handle)) === false) {
                throw new \Exception('Unable to open file ' . $file->filename);
            }
        } else {
            if (($handle = fopen($file->getFilePath(), 'r')) === false || ($str = stream_get_contents($handle)) === false) {
                throw new \Exception('Unable to open file ' . $file->filename);
            }
            if ($gzip) {
                $compression = 'gzip';
            }
        }

        return $str;
    }
}
