<?php

if (! function_exists('currentUser')) {
    function currentUser(): ?array
    {
        return session('user');
    }
}

if (! function_exists('hasRole')) {
    function hasRole(string ...$roles): bool
    {
        $user = currentUser();

        return $user && in_array($user['role'], $roles, true);
    }
}

if (! function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
