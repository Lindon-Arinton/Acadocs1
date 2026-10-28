<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * The ZKTeco biometric terminal (K30) that App\Libraries\BiometricSync pulls
 * attendance from over the LAN. Values can be overridden in .env, e.g.
 *   biometric.host = 192.168.100.143
 *
 * By default ($host = 'auto') the device is found on whatever network this
 * computer is on, and found again if its IP changes, so nothing needs setting
 * up after deploying. A fixed address (DHCP off in Menu → Comm., or the IP
 * reserved in the router) still makes each sync quicker and more reliable.
 * Searching a whole network for the device is fastest with PHP's sockets
 * extension (extension=sockets in php.ini); without it only the addresses
 * this computer has recently seen on the network are checked by port.
 */
class Biometric extends BaseConfig
{
    /** Pull from the device automatically (via the automations). */
    public bool $enabled = true;

    /**
     * 'auto' to find the device on the network, or its fixed IP
     * (Menu → Comm. → IP Address on the terminal) to always start there.
     */
    public string $host = 'auto';

    /** SDK port; 4370 unless someone changed it on the device. */
    public int $port = 4370;

    /** Menu → Comm. → Password on the terminal (0 = none). */
    public int $password = 0;

    /** 'auto' tries TCP then UDP; set 'tcp' or 'udp' to skip the probe. */
    public string $protocol = 'auto';

    /** Seconds to wait for the device before giving up. */
    public int $timeout = 4;

    /**
     * Minutes between automatic syncs. Each one only reads the punches added
     * since the last (~5 seconds), so this can be short. An open Time Records
     * page also triggers them, and refreshes itself when new punches arrive.
     */
    public int $intervalMinutes = 1;

    /**
     * Each sync rebuilds this many recent days of time_records from the
     * stored punches (so gaps get marked Absent). Older days are left alone,
     * so manual corrections to them are never overwritten.
     */
    public int $daysBack = 3;

    /**
     * Also sync during normal page loads (any page). Off: a sync still takes
     * a few seconds — the Time Records page and the scheduled task cover it.
     */
    public bool $pullOnWebRequests = false;
}
