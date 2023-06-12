<?php

namespace Shipyard;

use Shipyard\Traits\CreatesUniqueIDs;
use Slim\Psr7\UploadedFile;

class FileManager {
    use CreatesUniqueIDs;

    /**
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     *
     * @param UploadedFile $uploadedFile file uploaded file to move
     *
     * @return string filename of moved file
     */
    public static function moveUploadedFile(UploadedFile $uploadedFile) {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = self::get_guid();
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $fullpath = self::getStorageDirectory($basename) . $filename;

        $uploadedFile->moveTo($fullpath);

        return $fullpath;
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
}
