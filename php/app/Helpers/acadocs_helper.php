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

if (! function_exists('submissionBadge')) {
    /**
     * Status-pill class for a task submission status (Pending until an
     * admin marks it Reviewed or Returned).
     */
    function submissionBadge(?string $status): string
    {
        return [
            'Reviewed' => 'badge-reviewed',
            'Returned' => 'badge-returned',
        ][$status] ?? 'badge-pending';
    }
}

if (! function_exists('fileTypeStyle')) {
    /**
     * Icon + colors for a file extension, shared by Templates and Document
     * Management file lists.
     *
     * @return array{0:string,1:string,2:string} [bootstrap icon, text color, background]
     */
    function fileTypeStyle(?string $ext): array
    {
        return [
            'pdf'  => ['bi-file-earmark-pdf-fill',   '#991b1b', '#fee2e2'],
            'doc'  => ['bi-file-earmark-word-fill',  '#1e40af', '#dbeafe'],
            'docx' => ['bi-file-earmark-word-fill',  '#1e40af', '#dbeafe'],
            'xls'  => ['bi-file-earmark-excel-fill', '#065f46', '#d1fae5'],
            'xlsx' => ['bi-file-earmark-excel-fill', '#065f46', '#d1fae5'],
            'csv'  => ['bi-filetype-csv',            '#065f46', '#d1fae5'],
            'ppt'  => ['bi-file-earmark-ppt-fill',   '#c2410c', '#ffedd5'],
            'pptx' => ['bi-file-earmark-ppt-fill',   '#c2410c', '#ffedd5'],
            'txt'  => ['bi-file-earmark-text-fill',  '#374151', '#f3f4f6'],
            'zip'  => ['bi-file-earmark-zip-fill',   '#713f12', '#fef9c3'],
            'rar'  => ['bi-file-earmark-zip-fill',   '#713f12', '#fef9c3'],
            'jpg'  => ['bi-file-earmark-image-fill', '#3730a3', '#eff6ff'],
            'jpeg' => ['bi-file-earmark-image-fill', '#3730a3', '#eff6ff'],
            'png'  => ['bi-file-earmark-image-fill', '#3730a3', '#eff6ff'],
        ][strtolower((string) $ext)] ?? ['bi-file-earmark-fill', '#374151', '#f3f4f6'];
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
        $html = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $html); // bold + italic (Ctrl+B then Ctrl+I)
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<!\*)\*([^*]+?)\*(?!\*)/s', '<em>$1</em>', $html);

        return nl2br($html);
    }
}
