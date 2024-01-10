<?php

namespace Shipyard\Traits;

use Shipyard\Models\Tag;

/**
 * @property \Illuminate\Database\Eloquent\Collection $tags
 */
trait HasTags {
    /**
     * An item may have multiple tags.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags() {
        return $this->belongsToMany(Tag::class, 'item_tags', 'item_id', 'tag_id')->wherePivot('type', self::$tag_label);
    }

    /**
     * Assign the given tag to the item.
     *
     * @param Tag|string $tag
     *
     * @return mixed
     */
    public function assignTag($tag) {
        if (is_string($tag)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Tag::query()->where('slug', '=', $tag);
            /** @var Tag $tag */
            $tag = $query->firstOrFail();
        }

        $return = $this->tags()->save($tag, ['type' => self::$tag_label]);
        unset($this->tags);

        return $return;
    }

    /**
     * Remove the given tag from the item.
     *
     * @param Tag|string $tag
     *
     * @return mixed
     */
    public function removeTag($tag) {
        if (is_string($tag)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Tag::query()->where('slug', '=', $tag);
            /** @var Tag $tag */
            $tag = $query->firstOrFail();
        }

        $return = $this->tags()->detach($tag->id);
        unset($this->tags);

        return $return;
    }

    /**
     * Determine if the item has the given tag.
     *
     * @param Tag|string $tag
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
