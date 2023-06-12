<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasSlug;

/**
 * @property int    $id
 * @property string $slug
 * @property string $label
 */
class Tag extends Model {
    use HasSlug;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'slug', 'label',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'id',
    ];

    /**
     * Retrieve ships with this tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ships() {
        return $this->retrieve_type(Ship::class);
    }

    /**
     * Retrieve saves with this tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function saves() {
        return $this->retrieve_type(Save::class);
    }

    /**
     * Retrieve challenges with this tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function challenges() {
        return $this->retrieve_type(Challenge::class);
    }

    /**
     * Retrieve tagged items of a specific class.
     *
     * @param class-string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function retrieve_type($class) {
        return $this->belongsToMany($class, 'item_tags', 'tag_id', 'item_id')->wherePivot('type', '=', $class::$tag_label);
    }
}
