<?php

namespace Shipyard\Traits;

use Shipyard\Auth;

trait ChecksPermissions {
    /**
     * @param \Shipyard\Models\Permission|string $permission
     *
     * @return \Psr\Http\Message\ResponseInterface|true
     */
    private function can($permission) {
        if (Auth::user() === null  || !Auth::user()->can($permission)) {
            return Auth::abort(403, 'Unauthorized action.');
        }

        return true;
    }

    /**
     * @param int                                $id
     * @param \Shipyard\Models\Permission|string $permission
     *
     * @return \Psr\Http\Message\ResponseInterface|true
     */
    private function isOrCan($id, $permission) {
        if (Auth::user() === null  || !(Auth::user()->id == $id || Auth::user()->can($permission))) {
            return Auth::abort(403, 'Unauthorized action.');
        }

        return true;
    }

    /**
     * @param int $id
     *
     * @return \Psr\Http\Message\ResponseInterface|true
     */
    private function isUser($id) {
        if (Auth::user() === null  || Auth::user()->id != $id) {
            return Auth::abort(403, 'Unauthorized action.');
        }

        return true;
    }
}
