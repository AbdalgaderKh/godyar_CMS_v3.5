<?php

use Godyar\Auth;

if (function_exists('g_is') === false) {
    function g_is(string $role): bool {
        if (class_exists(Auth::class) === false) {
            return false;
        }
        return Auth::hasRole($role);
    }
}

if (function_exists('g_can') === false) {
    function g_can(string $permission): bool {
        if (!class_exists(Auth::class)) {
            return false;
        }
        return Auth::hasPermission($permission);
    }
}
