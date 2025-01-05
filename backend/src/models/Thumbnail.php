<?php

namespace Shipyard\Models;

/**
 * @property int        $file_id
 * @property int        $screenshot_id
 * @property int        $size
 * @property File       $file
 * @property Screenshot $screenshot
 */
class Thumbnail extends Model {
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'file_id', 'screenshot_id', 'size'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'file_id',
    ];

    /**
     * Retrieve screenshots from items of a specific class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function screenshot() {
        return $this->belongsTo(Screenshot::class);
    }

    /**
     * A ship has a file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file() {
        return $this->belongsTo(File::class);
    }

    public function delete() {
        return $this->file->delete() && parent::delete();
    }
}
