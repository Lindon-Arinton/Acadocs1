<?php

namespace App\Commands;

use App\Libraries\BiometricSync;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Pulls attendance from the biometric terminal now (see App\Libraries\BiometricSync).
 *   php spark acadocs:biometric --test      check the connection only
 *   php spark acadocs:biometric --days 60   backfill the last 60 days
 *   php spark acadocs:biometric --all       store the device's whole history
 */
class BiometricPull extends BaseCommand
{
    protected $group       = 'ACADOCS';
    protected $name        = 'acadocs:biometric';
    protected $description = 'Pulls attendance from the ZKTeco biometric terminal into Time Records.';
    protected $usage       = 'acadocs:biometric [--test] [--days N] [--all]';
    protected $options     = [
        '--test'    => 'Only connect and show what is on the device; import nothing.',
        '--verbose' => 'With --test, print every packet exchanged with the device.',
        '--days'    => 'Also rebuild the last N days of time records from the stored punches (a backfill).',
        '--all'     => 'Store the device\'s whole attendance history and fill in every day that has no records (keeps existing ones).',
    ];

    public function run(array $params)
    {
        $config = config('Biometric');

        if (CLI::getOption('all') !== null) {
            CLI::write('Copying the whole attendance history off the device, in short sessions...');
            $result = (new BiometricSync())->archiveAll(static fn (string $line) => CLI::write($line, 'light_gray'));
            $result['ok'] ? CLI::write($result['message'], 'green') : CLI::error($result['message']);

            return $result['ok'] ? EXIT_SUCCESS : EXIT_ERROR;
        }

        if (CLI::getOption('test') !== null) {
            $before = BiometricSync::lastStatus();
            CLI::write('Looking for the device (' . ($config->host === '' || strtolower($config->host) === 'auto' ? 'biometric.host = auto' : "biometric.host = {$config->host}") . ')...');
            try {
                $trace  = CLI::getOption('verbose') !== null ? static fn (string $line) => CLI::write($line, 'light_gray') : null;
                $device = (new BiometricSync())->locate($trace);
                $sizes  = $device->sizes();
                $device->disconnect();
            } catch (\Throwable $e) {
                CLI::error($e->getMessage());

                return EXIT_ERROR;
            }

            $last = BiometricSync::lastStatus();
            if (($before['host'] ?? null) !== $device->host()) {
                CLI::write('Found it at a new address' . (empty($before['host']) ? '' : " (was {$before['host']})") . '; syncs will use it from now on.', 'yellow');
            }
            CLI::write("Connected to {$device->host()} over " . $device->transport() . (empty($last['serial']) ? '' : ", serial {$last['serial']}") . '.', 'green');
            CLI::write("{$sizes['users']} user(s), {$sizes['records']} attendance record(s) on the device.");
            if (isset($last['cursor'])) {
                CLI::write("{$last['cursor']} already synced; the next sync reads " . max(0, $sizes['records'] - $last['cursor']) . ' new record(s).');
            } else {
                CLI::write('Never synced: the first sync reads the whole log (about 2 minutes per 50,000 records).');
            }

            return EXIT_SUCCESS;
        }

        $days = CLI::getOption('days');
        if ($days !== null && ! ctype_digit((string) $days)) {
            CLI::error('--days must be a whole number');

            return EXIT_USER_INPUT;
        }

        $result = (new BiometricSync())->pull($days !== null ? (int) $days : null);
        $result['ok'] ? CLI::write($result['message'], 'green') : CLI::error($result['message']);

        return $result['ok'] ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
