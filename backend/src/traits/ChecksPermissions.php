<?php

namespace Shipyard\Traits;

use Shipyard\Auth;

trait ChecksPermissions {
    private function can($permission) {
        if (!Auth::user()->can($permission)) {
            return Auth::abort(403, 'Unauthorized action.');
        }

        return null;
    }

    private function isOrCan($id, $permission) {
        if (!(Auth::user()->id == $id || Auth::user()->can($permission))) {
            return Auth::abort(403, 'Unauthorized action.');
        }

        return null;
    }

    private function isUser($id) {
        if (Auth::user()->id != $id) {
            return Auth::abort(403, 'Unauthorized action.');
        }

        return null;
    }
}
