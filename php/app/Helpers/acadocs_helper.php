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

if (! function_exists('personLink')) {
    /**
     * A person's name that opens the shared person-card modal (see
     * layout/footer.php) when clicked. Falls back to plain escaped text when
     * the name isn't tied to a user account.
     */
    function personLink(?int $userId, string $name, string $class = ''): string
    {
        if (! $userId) {
            return e($name);
        }

        return '<a href="#" class="person-link' . ($class !== '' ? ' ' . e($class) : '') . '" data-person-id="' . (int) $userId . '">' . e($name) . '</a>';
    }
}

if (! function_exists('submissionTiming')) {
    /**
     * Whether a task submission came in before its deadline, and if not, by
     * how much ("2d 3h", "45m"). A resubmission counts from its latest
     * upload time, since that's what submitted_at holds.
     *
     * @return array{late:bool,lateBy:?string}
     */
    function submissionTiming(?string $submittedAt, ?string $deadline): array
    {
        if (! $submittedAt || ! $deadline) {
            return ['late' => false, 'lateBy' => null];
        }

        $over = strtotime($submittedAt) - strtotime($deadline);
        if ($over <= 0) {
            return ['late' => false, 'lateBy' => null];
        }

        $days    = intdiv($over, 86400);
        $hours   = intdiv($over % 86400, 3600);
        $minutes = max(1, intdiv($over % 3600, 60));
        $parts   = array_filter([
            $days ? $days . 'd' : null,
            $hours ? $hours . 'h' : null,
            ! $days ? $minutes . 'm' : null, // minutes only matter under a day
        ]);

        return ['late' => true, 'lateBy' => implode(' ', $parts)];
    }
}

if (! function_exists('submissionTimingBadge')) {
    /** "On time" / "Late · 2d 3h" pill for a submission (see submissionTiming()). */
    function submissionTimingBadge(?string $submittedAt, ?string $deadline): string
    {
        $t = submissionTiming($submittedAt, $deadline);

        return $t['late']
            ? '<span class="timing-badge timing-late" title="Submitted after the deadline"><i class="bi bi-alarm-fill me-1"></i>Late · ' . e($t['lateBy']) . '</span>'
            : '<span class="timing-badge timing-ontime" title="Submitted before the deadline"><i class="bi bi-check-circle-fill me-1"></i>On time</span>';
    }
}

if (! function_exists('mpsDescriptor')) {
    /**
     * DepEd grade descriptor for a score out of 100 (used for MPS):
     * 90–100 Advancing, 80–89 Benchmarking, 75–79 Connecting,
     * 65–74 Developing, below 65 Emerging. 75 is the mastery line.
     *
     * @return array{label:string,class:string,range:string}|null null when there's no score
     */
    function mpsDescriptor(?float $score): ?array
    {
        if ($score === null) {
            return null;
        }

        foreach (MPS_DESCRIPTORS as [$min, $label, $range]) {
            if ($score >= $min) {
                return ['label' => $label, 'class' => 'desc-' . strtolower($label), 'range' => $range];
            }
        }

        return null;
    }
}

if (! defined('MPS_DESCRIPTORS')) {
    /** [lowest score, descriptor, range label], highest band first — see mpsDescriptor(). */
    define('MPS_DESCRIPTORS', [
        [90, 'Advancing',    '90–100'],
        [80, 'Benchmarking', '80–89'],
        [75, 'Connecting',   '75–79'],
        [65, 'Developing',   '65–74'],
        [0,  'Emerging',     '0–64'],
    ]);
}
