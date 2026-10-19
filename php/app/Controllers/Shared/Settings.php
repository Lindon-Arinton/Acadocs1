<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;

/**
 * Display & accessibility settings. Nothing is stored server-side — the
 * choices are per-device and live in localStorage (see
 * partials/display_prefs_head.php), so this only renders the page.
 */
class Settings extends BaseController
{
    public function index()
    {
        return view('pages/shared/settings', [
            'pageTitle' => 'Display & Accessibility',
        ]);
    }
}
