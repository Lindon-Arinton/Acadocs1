<?php

if (! function_exists('currentUser')) {
    function currentUser(): ?array
    {
        return session('user');
    }
}

if (! function_exists('hasRole')) {
    /**
     * Whether the signed-in user has one of the roles. A super admin (an admin
     * account with users.is_super_admin) also passes every ADAS check, so they
     * get the principal's pages plus the ADAS's tools — never teacher-only ones.
     */
    function hasRole(string ...$roles): bool
    {
        $user = currentUser();
        if (! $user) {
            return false;
        }
        if (in_array($user['role'], $roles, true)) {
            return true;
        }

        return in_array('adas', $roles, true) && isSuperAdmin();
    }
}

if (! function_exists('isSuperAdmin')) {
    /** Read from the database once per request, so granting or removing it applies at once. */
    function isSuperAdmin(): bool
    {
        static $cache = [];
        $user = currentUser();
        if (! $user || $user['role'] !== 'admin') {
            return false;
        }
        $id = (int) $user['id'];
        if (! array_key_exists($id, $cache)) {
            try {
                $row        = db_connect()->query('SELECT is_super_admin FROM users WHERE id = ?', [$id])->getRowArray();
                $cache[$id] = ! empty($row['is_super_admin']);
            } catch (\Throwable $e) {
                $cache[$id] = false; // column not added yet
            }
        }

        return $cache[$id];
    }
}

if (! function_exists('roleLabel')) {
    /** "Super Admin" for a super admin, otherwise the role as written ("Admin", "Adas", …). */
    function roleLabel(array $user): string
    {
        return ($user['role'] ?? '') === 'admin' && ! empty($user['is_super_admin']) ? 'Super Admin' : ucfirst((string) ($user['role'] ?? ''));
    }
}

if (! function_exists('asset_url')) {
    /**
     * URL of a file under public/ with its last-modified time appended
     * (assets/css/app.css?v=1793...), so a new upload gets a new URL and
     * browsers / Hostinger's CDN can't keep serving the old cached copy.
     */
    function asset_url(string $path): string
    {
        $file = FCPATH . ltrim($path, '/');

        return base_url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
    }
}

if (! function_exists('storedFilePath')) {
    /**
     * Where an uploaded file really is. Paths are saved in full, so after the
     * app moves (another folder on the host, or a database copied from a PC
     * with C:\xampp\... paths) the same file is looked for under this
     * install's writable/uploads/. Returns the saved path unchanged when the
     * file can't be found either way, so callers' own is_file() checks still apply.
     */
    function storedFilePath(string $path): string
    {
        if ($path === '' || @is_file($path)) {
            return $path;
        }

        $normalized = str_replace('\\', '/', $path);
        $at         = strrpos($normalized, '/uploads/');
        if ($at !== false) {
            $moved = WRITEPATH . 'uploads/' . substr($normalized, $at + strlen('/uploads/'));
            if (is_file($moved)) {
                return $moved;
            }
        }

        return $path;
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
     * admin marks it Approved or Returned).
     */
    function submissionBadge(?string $status): string
    {
        return [
            'Approved' => 'badge-reviewed',
            'Returned' => 'badge-returned',
        ][$status] ?? 'badge-pending';
    }
}

if (! function_exists('submissionStatusPill')) {
    /**
     * Status pill (with icon) for the uploader's own view of a task:
     * Pending / Approved / Returned once uploaded, else Not Submitted / Missed.
     */
    function submissionStatusPill(?array $submission, string $taskStatus = 'Open'): string
    {
        if (! $submission) {
            return $taskStatus === 'Closed'
                ? '<span class="status-pill badge-returned"><i class="bi bi-x-circle me-1"></i>Missed</span>'
                : '<span class="status-pill badge-pending"><i class="bi bi-circle me-1"></i>Not Submitted</span>';
        }

        [$icon, $label] = [
            'Approved' => ['bi-check-circle-fill', 'Approved'],
            'Returned' => ['bi-arrow-return-left', 'Returned'],
        ][$submission['status']] ?? ['bi-hourglass-split', 'Pending'];

        return '<span class="status-pill ' . submissionBadge($submission['status']) . '"><i class="bi ' . $icon . ' me-1"></i>' . $label . '</span>';
    }
}

if (! function_exists('submissionStatusBanner')) {
    /**
     * One line telling the uploader what the principal decided and what to do next.
     */
    function submissionStatusBanner(array $submission): string
    {
        $by = '';
        if (! empty($submission['reviewer_name'])) {
            $by = ' by ' . e($submission['reviewer_name'])
                . (! empty($submission['reviewed_at']) ? ' on ' . date('M d, Y', strtotime($submission['reviewed_at'])) : '');
        }

        [$class, $icon, $text] = match ($submission['status']) {
            'Approved' => ['alert-success', 'bi-check-circle-fill', '<strong>Approved</strong>' . $by . '. No further action needed.'],
            'Returned' => ['alert-danger', 'bi-arrow-return-left', '<strong>Returned</strong>' . $by . '. Please revise your file and upload it again.'],
            default    => ['alert-warning', 'bi-hourglass-split', '<strong>Pending</strong>. Waiting for the principal to approve or return your upload.'],
        };

        return '<div class="alert ' . $class . ' d-flex align-items-center gap-2 py-2 px-3 mb-3 small"><i class="bi ' . $icon . '"></i><span>' . $text . '</span></div>';
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
