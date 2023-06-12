<?php

namespace Shipyard\Traits;

use Shipyard\Models\Screenshot;

/**
 * @property \Illuminate\Database\Eloquent\Collection $screenshots
 */
trait HasScreenshots {
    /**
     * An item may have multiple screenshots.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function screenshots() {
        return $this->belongsToMany(Screenshot::class, 'item_screenshots', 'item_id', 'screenshot_id')->wherePivot('type', self::$tag_label);
    }

    /**
     * Assign the given screenshot to the item.
     *
     * @param string $screenshot
     *
     * @return mixed
     */
    public function assignScreenshot($screenshot) {
        if (is_string($screenshot)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Screenshot::query()->where('ref', '=', $screenshot);
            /** @var \Shipyard\Models\Screenshot $screenshot */
            $screenshot = $query->firstOrFail();
        }

        $return = $this->screenshots()->save($screenshot, ['type' => self::$tag_label]);
        unset($this->screenshots);

        return $return;
    }

    /**
     * Remove the given screenshot from the item.
     *
     * @param string $screenshot
     *
     * @return mixed
     */
    public function removeScreenshot($screenshot) {
        if (is_string($screenshot)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Screenshot::query()->where('ref', '=', $screenshot);
            /** @var \Shipyard\Models\Screenshot $screenshot */
            $screenshot = $query->firstOrFail();
        }

        $return = $this->screenshots()->detach($screenshot->id);
        unset($this->screenshots);

        return $return;
    }

    /**
     * Determine if the item has the given screenshot.
     *
     * @param mixed $screenshots
     *
     * @return bool
     */
    public function hasScreenshot($screenshots) {
        if (is_string($screenshots)) {
            return $this->screenshots->contains('ref', $screenshots);
        }

        return (bool) $this->screenshots->intersect([$screenshots])->count();
    }
}
