<?php

namespace Shipyard\Traits;

use Shipyard\Models\Permission;
use Shipyard\Models\Role;

/**
 * @property \Illuminate\Database\Eloquent\Collection $roles
 *
 * @todo check for attempting to assign non-existent permission
 */
trait HasRoles {
    /**
     * A user may have multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles() {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Assign the given role to an item.
     *
     * @param \Shipyard\Models\Role|string $role
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function assignRole($role) {
        if (is_string($role)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Role::query()->where('slug', '=', $role);
            /** @var \Shipyard\Models\Role $role */
            $role = $query->firstOrFail();
        }

        $return = $this->roles()->save($role);
        unset($this->roles);

        return $return;
    }

    /**
     * Remove the given role from an item.
     *
     * @param \Shipyard\Models\Role|string $role
     *
     * @return int
     */
    public function removeRole($role) {
        if (is_string($role)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Role::query()->where('slug', '=', $role);
            /** @var \Shipyard\Models\Role $role */
            $role = $query->firstOrFail();
        }

        $return = $this->roles()->detach($role->id);
        unset($this->roles);

        return $return;
    }

    /**
     * Determine if the user has the given role.
     *
     * @param \Illuminate\Database\Eloquent\Collection|string $role
     *
     * @return bool
     */
    public function hasRole($role) {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }

        return (bool) $role->intersect($this->roles)->count();
    }

    /**
     * Alias for hasPermission.
     *
     * @param \Shipyard\Models\Permission|string $permission
     *
     * @return bool
     */
    public function can($permission) {
        return $this->hasPermission($permission);
    }

    /**
     * Determine if the user has permission to perform the given task.
     *
     * @param \Shipyard\Models\Permission|string $permission
     *
     * @return bool
     */
    public function hasPermission($permission) {
        if (is_string($permission)) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = Permission::query()->where('slug', $permission);
            /** @var \Shipyard\Models\Permission $permission */
            $permission = $query->first();
        }

        return $this->hasRole($permission->roles);
    }
}
