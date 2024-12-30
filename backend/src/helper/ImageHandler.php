<?php

namespace Shipyard;

use Shipyard\Models\File;
use Shipyard\Models\Screenshot;
use Shipyard\Models\Thumbnail;

/**
 * Performs standard image management functions.
 *
 * Process images, create thumbnails, and manage metadata.
 * Thumbnail process inspired by https://pqina.nl/blog/creating-thumbnails-with-php/
 *
 * @todo Support WebP
 */
class ImageHandler {
    /**
     * The functions and settings used to handle image file types.
     *
     * @var array<string,array{load: string, save: string, quality: int}>
     */
    protected static $handlers = [
        'image/jpeg' => [
            'load' => 'imagecreatefromjpeg',
            'save' => 'imagejpeg',
            'quality' => 85
        ],
        'image/png' => [
            'load' => 'imagecreatefrompng',
            'save' => 'imagepng',
            'quality' => 9
        ],
        'image/gif' => [
            'load' => 'imagecreatefromgif',
            'save' => 'imagegif',
            'quality' => 0
        ]
    ];

    /**
     * The functions and settings used to handle image file types.
     *
     * @var int[][]
     */
    protected static $thumb_sizes = [
        [318, 200], // List thumbnail
        [800, 520], // Main thumbnail
    ];

    /**
     * Generates thumbnails of various sizes from a File object.
     *
     * @param Screenshot $screenshot
     *
     * @return bool
     */
    public static function generateThumbnails($screenshot) {
        /** @var File $file */
        $file = $screenshot->file;
        // Verify the file is an image and is supported.
        $type = explode(';', $file->media_type, 2)[0];
        if (strpos($type, 'image/') !== 0 || !isset(self::$handlers[$type])) {
            return false;
        }

        // Load the image and verify it loaded.
        $image = call_user_func(self::$handlers[$type]['load'], $file->getFilePath()); // @phpstan-ignore argument.type
        if (!$image) {
            return false;
        }

        $thumbnails = [];
        foreach (self::$thumb_sizes as $size) {
            $thumbnail = self::createThumbnail($image, $type, $size[0], $size[1]);
            $thumb_filepath = $file->getFilePath() . '-' . $size[0];
            $thumb_filename = rtrim($file->filename, '.' . $file->extension) . '-' . $size[0];
            if ($thumbnail !== false) {
                call_user_func(
                    self::$handlers[$type]['save'], // @phpstan-ignore argument.type
                    $thumbnail,
                    $thumb_filepath,
                    self::$handlers[$type]['quality']
                );
            }
            /** @var File $thumb_file */
            $thumb_file = File::query()->create([
                'filename' => $thumb_filename,
                'media_type' => $file->media_type,
                'extension' => $file->extension,
                'filepath' => str_replace($_SERVER['STORAGE'], '', $thumb_filepath),
                'compressed' => false
            ]);
            $thumbnails[] = ['file_id' => $thumb_file->id];
        }
        $screenshot->thumbnails()->createMany($thumbnails);

        return true;
    }

    /**
     * Generates a thumbnail of a specific size from an image file.
     *
     * @param resource $image
     * @param string   $type
     * @param int      $width
     * @param int|null $height
     *
     * @return resource|false
     */
    public static function createThumbnail($image, $type, $width, $height = null) {
        $_width = imagesx($image);
        $_height = imagesy($image);

        if ($height == 0) {
            $height = null;
        }
        if ($width == 0) {
            return false;
        }

        // maintain aspect ratio when no height set
        if ($height === null) {
            // get width to height ratio
            $ratio = $_width / $_height;

            // if is portrait
            // use ratio to scale height to fit in square
            if ($_width > $_height) {
                $height = (int) floor($width / $ratio);
            }
            // if is landscape
            // use ratio to scale width to fit in square
            else {
                $height = (int) $width;
                $width = (int) floor($width * $ratio);
            }
        }

        // create duplicate image based on calculated target size
        /** @var int<1, max> $width */
        /** @var int<1, max> $height */
        $thumbnail = imagecreatetruecolor($width, $height);

        // set transparency options for GIFs and PNGs
        if ($type == 'image/gif' || $type == 'image/png') {
            // make image transparent
            imagecolortransparent(
                $thumbnail,
                (int) imagecolorallocate($thumbnail, 0, 0, 0)
            );

            // additional settings for PNGs
            if ($type == 'image/png') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }
        }

        // copy entire source image to duplicate image and resize
        imagecopyresampled(
            $thumbnail,
            $image,
            0, 0, 0, 0,
            $width, $height,
            $_width, $_height
        );

        // 3. Save the $thumbnail to disk
        // - call the correct save method
        // - set the correct quality level

        // save the duplicate version of the image to disk
        return $thumbnail;
    }
}
