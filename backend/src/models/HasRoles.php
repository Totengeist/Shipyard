<?php

namespace Shipyard;

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
     * Assign the given role to the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function assignRole($role) {
        return $this->roles()->save(
            Role::whereSlug($role)->firstOrFail()
        );
    }

    /**
     * Remove the given role from the user.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function removeRole($role) {
        return $this->roles()->detach(
            Role::whereSlug($role)->firstOrFail()->id
        );
    }

    /**
     * Determine if the user has the given role.
     *
     * @param mixed $role
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
     * Alias for hasRole.
     *
     * @param mixed $role
     *
     * @return bool
     */
    public function can($permission) {
        return $this->hasPermission(Permission::where('slug', $permission)->first());
    }

    /**
     * Determine if the user has permission to perform the given task.
     *
     * @return bool
     */
    public function hasPermission(Permission $permission) {
        return $this->hasRole($permission->roles);
    }
}
