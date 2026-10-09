<?php

namespace App\Commands;

use App\Libraries\Automation;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Runs the ACADOCS automations (see App\Libraries\Automation).
 * Schedule it every 15 minutes or so with Windows Task Scheduler:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\Acadocs1\php\spark acadocs:automate
 */
class Automate extends BaseCommand
{
    protected $group       = 'ACADOCS';
    protected $name        = 'acadocs:automate';
    protected $description = 'Publishes/archives scheduled announcements, sends term reminders and report-pack notices, and runs the daily cleanup.';
    protected $usage       = 'acadocs:automate [--date YYYY-MM-DD]';
    protected $options     = ['--date' => 'Act as if today were this date (for testing term reminders).'];

    public function run(array $params)
    {
        $date = CLI::getOption('date');
        if ($date !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
            CLI::error('--date must be YYYY-MM-DD');

            return EXIT_USER_INPUT;
        }

        $done = (new Automation($date ?: null))->run();

        if ($done === []) {
            CLI::write('Nothing was due.', 'light_gray');
        }
        foreach ($done as $line) {
            CLI::write('• ' . $line, 'green');
        }

        return EXIT_SUCCESS;
    }
}
