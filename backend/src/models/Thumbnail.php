<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasFile;

/**
 * @property int        $screenshot_id
 * @property int        $size
 * @property Screenshot $screenshot
 */
class Thumbnail extends Model {
    use HasFile;

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
     * Delete the thumbnail and it's database record.
     *
     * @return bool
     */
    public function delete() {
        if ($this->file->delete()) {
            Thumbnail::query()->where([['file_id', $this->file_id], ['screenshot_id', $this->screenshot_id]])->delete();

            return true;
        }

        return false;
    }
}
