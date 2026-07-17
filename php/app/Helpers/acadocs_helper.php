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

if (! function_exists('richText')) {
    /**
     * Escapes user text, then renders **bold** and *italic* markers as HTML.
     * Escaping happens first, so only the literal markers we recognize can
     * ever turn into tags — no user-supplied HTML can pass through.
     */
    function richText(string $value): string
    {
        $html = e($value);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<!\*)\*([^*]+?)\*(?!\*)/s', '<em>$1</em>', $html);

        return nl2br($html);
    }
}
