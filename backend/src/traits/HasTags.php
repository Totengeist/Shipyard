<?php

namespace Shipyard\Traits;

use Shipyard\Models\Tag;

trait HasTags {
    /**
     * A user may have multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags() {
        return $this->belongsToMany(Tag::class, 'item_tags', 'item_id', 'tag_id')->wherePivot('type', self::$tag_label);
    }

    /**
     * Assign the given role to the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function assignTag($tag) {
        if (is_string($tag)) {
            $tag = Tag::whereSlug($tag)->firstOrFail();
        }

        $return = $this->tags()->save($tag, ['type' => self::$tag_label]);
        unset($this->tags);

        return $return;
    }

    /**
     * Remove the given role from the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function removeTag($tag) {
        if (is_string($tag)) {
            $tag = Tag::whereSlug($tag)->firstOrFail();
        }

        $return = $this->tags()->detach($tag->id);
        unset($this->tags);

        return $return;
    }

    /**
     * Determine if the user has the given role.
     *
     * @param mixed $role
     *
     * @return bool
     */
    public function hasTag($tag) {
        if (is_string($tag)) {
            return $this->tags->contains('slug', $tag);
        }

        return (bool) $this->tags->intersect([$tag])->count();
    }
}
