<?php

namespace Shipyard\Traits;

use Shipyard\Models\Screenshot;

/**
 * @property \Illuminate\Database\Eloquent\Collection $screenshots
 * @property \Illuminate\Database\Eloquent\Collection $primary_screenshot
 */
trait HasScreenshots {
    /**
     * An item may have multiple screenshots.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function screenshots() {
        return $this->belongsToMany(Screenshot::class, 'item_screenshots', 'item_id', 'screenshot_id')->wherePivot('type', self::$tag_label)->withPivot('primary');
    }

    /**
     * An item with screenshots should have a primary screenshot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primary_screenshot() {
        return $this->belongsToMany(Screenshot::class, 'item_screenshots', 'item_id', 'screenshot_id')->wherePivot('type', '=', self::$tag_label)->wherePivot('primary', '=', true);
    }

    /**
     * Assign the given screenshot to the item.
     *
     * @param string|Screenshot $screenshot
     * @param bool              $primary
     *
     * @return mixed
     */
    public function assignScreenshot($screenshot, $primary = false) {
        if (!$primary) {
            $primary = !$this->hasScreenshot();
        }
        if (is_string($screenshot)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Screenshot::query()->where('ref', '=', $screenshot);
            /** @var Screenshot $screenshot */
            $screenshot = $query->firstOrFail();
        }

        if ($primary) {
            foreach ($this->primary_screenshot as $old_primary) {
                $this->screenshots()->updateExistingPivot($old_primary->id, ['primary' => false]);
            }
        }
        $return = $this->screenshots()->save($screenshot, ['type' => self::$tag_label, 'primary' => $primary]);
        unset($this->screenshots);
        unset($this->primary_screenshot);

        return $return;
    }

    /**
     * Remove the given screenshot from the item.
     *
     * @param string|Screenshot $screenshot
     *
     * @return mixed
     */
    public function removeScreenshot($screenshot) {
        if (is_string($screenshot)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Screenshot::query()->where('ref', '=', $screenshot);
            /** @var Screenshot $screenshot */
            $screenshot = $query->firstOrFail();
        }

        $return = $this->screenshots()->detach($screenshot->id);
        unset($this->screenshots);
        unset($this->primary_screenshot);

        return $return;
    }

    /**
     * Determine if the item has the given screenshot.
     *
     * @param string|Screenshot|null $screenshots
     *
     * @return bool
     */
    public function hasScreenshot($screenshots = null) {
        if (is_null($screenshots)) {
            return (bool) $this->screenshots->count();
        } elseif (is_string($screenshots)) {
            return $this->screenshots->contains('ref', $screenshots);
        }

        return (bool) $this->screenshots->intersect([$screenshots])->count();
    }
}
