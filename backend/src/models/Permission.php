<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasSlug;

/**
 * @property \Illuminate\Database\Eloquent\Collection $roles
 */
class Permission extends Model {
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
        'id', 'pivot', 'created_at', 'updated_at',
    ];

    /**
     * A permission can be applied to roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}
