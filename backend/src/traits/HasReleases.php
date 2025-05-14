<?php

namespace Shipyard\Traits;

use Shipyard\Models\Release;

/**
 * @property \Illuminate\Database\Eloquent\Collection $releases
 */
trait HasReleases {
    /**
     * An item may be compatible with multiple releases.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function releases() {
        return $this->belongsToMany(Release::class, 'item_releases', 'item_id', 'release_id')->wherePivot('type', self::tag_label());
    }

    /**
     * Assign the given release to an item.
     *
     * @param string|Release $release
     *
     * @return mixed
     */
    public function assignRelease($release) {
        if (is_string($release)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Release::query()->where('slug', '=', $release);
            /** @var Release $release */
            $release = $query->firstOrFail();
        }

        $return = $this->releases()->save($release, ['type' => self::tag_label()]);
        unset($this->releases);

        return $return;
    }

    /**
     * Remove the given release from an item.
     *
     * @param string|Release $release
     *
     * @return mixed
     */
    public function removeRelease($release) {
        if (is_string($release)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Release::query()->where('slug', '=', $release);
            /** @var Release $release */
            $release = $query->firstOrFail();
        }

        $return = $this->releases()->detach($release->id);
        unset($this->releases);

        return $return;
    }

    /**
     * Determine if the item is compatible with the given release.
     *
     * @param mixed $release
     *
     * @return bool
     */
    public function hasRelease($release) {
        if (is_string($release)) {
            return $this->releases->contains('slug', $release);
        }

        return (bool) $this->releases->intersect([$release])->count();
    }
}
