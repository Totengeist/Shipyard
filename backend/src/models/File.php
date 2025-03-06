<?php

namespace Shipyard\Models;

/**
 * @property string            $filename
 * @property string            $media_type
 * @property string            $extension
 * @property string            $filepath
 * @property bool              $compressed
 * @property Ship|null         $ship
 * @property Save|null         $save_item
 * @property Modification|null $modification
 * @property Screenshot|null   $screenshot
 * @property Thumbnail|null    $thumbnail
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

    /**
     * A file may be a ship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ship() {
        return $this->hasOne(Ship::class);
    }

    /**
     * A file may be a save.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function save_item() {
        return $this->hasOne(Save::class);
    }

    /**
     * A file may be a mod.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function modification() {
        return $this->hasOne(Modification::class);
    }

    /**
     * A file may be a screenshot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function screenshot() {
        return $this->hasOne(Screenshot::class);
    }

    /**
     * A file may be a thumbnail.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function thumbnail() {
        return $this->hasOne(Thumbnail::class);
    }

    /**
     * A file may have an item.
     *
     * @return Ship|Save|Modification|Screenshot|Thumbnail|null
     */
    public function item() {
        if ($this->ship !== null) {
            return $this->ship;
        }
        if ($this->save_item !== null) {
            return $this->save_item;
        }
        if ($this->modification !== null) {
            return $this->modification;
        }
        if ($this->screenshot !== null) {
            return $this->screenshot;
        }
        if ($this->thumbnail !== null) {
            return $this->thumbnail;
        }

        return null;
    }

    /**
     * Delete the file and it's database record.
     *
     * @return bool
     */
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
