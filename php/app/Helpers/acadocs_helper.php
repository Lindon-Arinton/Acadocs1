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
