<?php

namespace App\Filters;

use App\Libraries\Automation;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Fallback scheduler: runs the automations during a normal page load, at most
 * every Config\Automation::$webIntervalSeconds, so they still happen when no
 * Task Scheduler / cron job is set up. Never allowed to break the page.
 */
class AutomationTick implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $config = config('Automation');
        if (! $config->runOnWebRequests || cache('automation_tick') !== null) {
            return;
        }
        cache()->save('automation_tick', time(), $config->webIntervalSeconds);

        try {
            (new Automation())->run();
        } catch (\Throwable $e) {
            log_message('error', 'Automation (page-load run) failed: ' . $e->getMessage());
        }
    }
}
