<?php

namespace Shipyard\Traits;

use Shipyard\Models\File;

/**
 * @property int  $file_id
 * @property File $file
 */
trait HasFile {
    /**
     * An item has a file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file() {
        return $this->belongsTo(File::class);
    }

    /**
     * Delete the item and it's associated file.
     *
     * @return bool|null
     */
    public function delete() {
        if ($this->file == null) {
            return parent::delete();
        }

        return $this->file->delete() && parent::delete();
    }
}
