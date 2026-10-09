<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Settings for App\Libraries\Automation (scheduled announcements, term
 * reminders, report-pack notices, cleanup). Run it on a schedule with
 * `php spark acadocs:automate` (Windows Task Scheduler / cron); as a fallback
 * it also runs during normal page loads, at most every $webIntervalSeconds.
 */
class Automation extends BaseConfig
{
    /** Also run due jobs during normal page loads (so nothing depends on a scheduler being set up). */
    public bool $runOnWebRequests = true;

    /** Minimum seconds between runs triggered by page loads. */
    public int $webIntervalSeconds = 300;

    /** Term reminders / pack notices are only sent within this many days of the date they're due. */
    public int $termNoticeWindowDays = 14;

    /** Read notifications older than this many days are deleted. */
    public int $readNotificationDays = 90;

    /** Any notification older than this many days is deleted. */
    public int $anyNotificationDays = 365;

    /** Uploaded import files (DTR, MPS, enrollment, KPI) older than this many days are deleted. */
    public int $importFileDays = 30;

    /** Orphaned uploads are moved to writable/orphaned_uploads/ first, and deleted after this many days there. */
    public int $quarantineDays = 30;
}
