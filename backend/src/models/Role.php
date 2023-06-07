<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasSlug;

class Role extends Model {
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
     * A role may be given various permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions() {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Grant the given permission to a role.
     *
     * @return mixed
     */
    public function givePermissionTo(Permission $permission) {
        return $this->permissions()->save($permission);
    }

    /**
     * Remove the given permission from a role.
     *
     * @return mixed
     */
    public function removePermissionTo(Permission $permission) {
        return $this->permissions()->detach($permission->id);
    }
}
