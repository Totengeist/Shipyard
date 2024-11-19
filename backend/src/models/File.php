<?php

namespace Shipyard\Models;

/**
 * @property string $filename
 * @property string $media_type
 * @property string $extension
 * @property string $filepath
 * @property bool   $compressed
 */
class File extends Model {
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'filename', 'media_type', 'extension', 'filepath', 'compressed',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'id', 'filepath',
    ];

    /** @return string|false */
    public function file_contents() {
        return file_get_contents($this->getFilePath());
    }

    public function delete() {
        return unlink($this->getFilePath()) && parent::delete();
    }

    /**
     * The full filepath for the file.
     *
     * @return string
     */
    public function getFilePath() {
        return $_SERVER['STORAGE'] . $this->filepath;
    }
}
