<?php

namespace Shipyard;

use Illuminate\Database\Eloquent\Model;

class Release extends Model {
    use HasSlug;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug', 'label',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * Retrieve ships compatible with this release.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ships() {
        return $this->retrieve_type(Ship::class);
    }

    /**
     * Retrieve saves compatible with this release.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function saves() {
        return $this->retrieve_type(Save::class);
    }

    /**
     * Retrieve challenges compatible with this release.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function challenges() {
        return $this->retrieve_type(Challenge::class);
    }

    /**
     * A base function to handle picking the correct type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function retrieve_type($class) {
        return $this->belongsToMany($class, 'item_releases', 'release_id', 'item_id')->wherePivot('type', '=', $class::$tag_label);
    }
}
