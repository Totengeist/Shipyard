<?php

namespace Shipyard;

trait HasTags {
    /**
     * A user may have multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags() {
        return $this->belongsToMany(Tag::class, 'item_tags', 'item_id', 'tag_id')->wherePivot('type', $this->tag_label);
    }

    /**
     * Assign the given role to the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function assignTag($tag) {
        return $this->tags()->save(
            Tag::whereSlug($tag)->firstOrFail(), ['type' => $this->tag_label]
        );
    }

    /**
     * Remove the given role from the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function removeTag($tag) {
        return $this->tags()->detach(
            Tag::whereSlug($tag)->firstOrFail()->id, ['type' => $this->tag_label]
        );
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

        return (bool) $role->intersect($this->tags)->count();
    }
}
