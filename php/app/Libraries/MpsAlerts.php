<?php

namespace App\Libraries;

use App\Controllers\Teacher\PerformanceMps;
use App\Models\MpsTestScoreModel;
use App\Models\NotificationModel;
use App\Models\UserModel;

/**
 * Learner performance alerts: when a class's MPS (average of its entered
 * tests) lands below 75% — Developing or Emerging — the principal(s) and the
 * subject teacher get a notification. One notification per class and person:
 * it's refreshed (and marked unread again) only when the class drops into a
 * different descriptor, and removed once the class reaches 75% or more.
 */
class MpsAlerts
{
    public const REF_TYPE = 'mps_alert';
    private const MASTERY = 75;

    /**
     * Re-checks the given classes after scores were saved or imported.
     *
     * @param array<string,array{grade:string,subject:string,section:?string}> $cells the teacher's handled classes
     */
    public function checkClasses(string $year, int $term, array $cells, ?int $teacherUserId): void
    {
        if ($cells === []) {
            return;
        }

        // Excel imports save one blended value per grade + subject (no section):
        // check those too, alongside the per-section classes.
        foreach ($cells as $cell) {
            $cells[$cell['grade'] . '|' . $cell['subject'] . '|' . PerformanceMps::NO_SECTION] = ['grade' => $cell['grade'], 'subject' => $cell['subject'], 'section' => null];
        }

        $rows     = PerformanceMps::performanceRows($cells, (new MpsTestScoreModel())->forYearTerm($year, $term));
        $admins   = array_map('intval', array_column((new UserModel())->active()->where('role', 'admin')->findAll(), 'id'));
        $notifier = new NotificationModel();

        foreach ($rows as $row) {
            $refId = self::refId($year, $term, $row['grade'], $row['subject'], $row['section']);

            if ($row['avg'] === null || $row['avg'] >= self::MASTERY) {
                $notifier->deleteForRef(self::REF_TYPE, $refId); // recovered (or no scores): nothing to flag

                continue;
            }

            $descriptor = mpsDescriptor($row['avg'])['label'];
            $class      = $row['subject'] . ' · ' . $row['grade'] . ($row['section'] ? ' ' . $row['section'] : '');
            $title      = $class . ' is ' . $descriptor;
            $sub        = 'MPS ' . number_format($row['avg'], 2) . '% · SY ' . str_replace('-', '–', $year) . ', Term ' . $term;

            $recipients = array_fill_keys($admins, base_url('dashboard?year=' . urlencode($year)));
            if ($teacherUserId) {
                $recipients[$teacherUserId] = base_url('performance/mps?year=' . urlencode($year) . '&term=' . $term);
            }

            foreach ($recipients as $userId => $url) {
                $existing = $notifier->where('user_id', $userId)->where('ref_type', self::REF_TYPE)->where('ref_id', $refId)->first();
                if ($existing && $existing['title'] === $title) {
                    // Same descriptor as last time: keep the figure current, don't re-alert.
                    $notifier->update($existing['id'], ['sub' => $sub]);
                } else {
                    $notifier->upsertGrouped($userId, self::REF_TYPE, $refId, self::REF_TYPE, $title, $sub, $url);
                }
            }
        }
    }

    /** Stable id for one class in one term (notifications.ref_id is an unsigned INT). */
    public static function refId(string $year, int $term, string $grade, string $subject, ?string $section): int
    {
        return crc32($year . '|' . $term . '|' . $grade . '|' . $subject . '|' . ($section ?? ''));
    }
}
