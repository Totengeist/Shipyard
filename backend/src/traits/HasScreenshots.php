<?php

namespace Shipyard\Traits;

use Shipyard\Models\Screenshot;

trait HasScreenshots {
    /**
     * A user may have multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function screenshots() {
        return $this->belongsToMany(Screenshot::class, 'item_screenshots', 'item_id', 'screenshot_id')->wherePivot('type', self::$tag_label);
    }

    /**
     * Assign the given role to the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function assignScreenshot($screenshot) {
        if (is_string($screenshot)) {
            $screenshot = Screenshot::whereRef($screenshot)->firstOrFail();
        }

        $return = $this->screenshots()->save($screenshot, ['type' => self::$tag_label]);
        unset($this->screenshots);

        return $return;
    }

    /**
     * Remove the given role from the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function removeScreenshot($screenshots) {
        if (is_string($screenshots)) {
            $screenshots = Screenshot::whereRef($screenshots)->firstOrFail();
        }

        $return = $this->screenshots()->detach($screenshots->id);
        unset($this->screenshots);

        return $return;
    }

    /**
     * Determine if the user has the given role.
     *
     * @param mixed $role
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
