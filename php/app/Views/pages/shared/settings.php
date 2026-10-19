<?php
/**
 * Display & accessibility settings. Every control applies instantly through
 * window.AcadocsPrefs (partials/display_prefs_head.php) — there's no Save
 * button and nothing is posted; the choices live in this browser only.
 */
$accessibilityToggles = [
    ['reduceMotion',   'bi-pause-circle',   'Reduce motion',            'Turns off page transitions, animated counters, and loading animations.'],
    ['highContrast',   'bi-circle-half',    'High contrast',            'Darkens secondary text and strengthens borders so content stands out from the background.'],
    ['underlineLinks', 'bi-type-underline', 'Underline links',          'Shows links with an underline so they don\'t rely on color alone.'],
    ['focusRing',      'bi-bullseye',       'Prominent focus outline',  'Draws a thick outline around the button, link, or field you reach with the Tab key.'],
    ['textSpacing',    'bi-text-paragraph', 'Increase text spacing',    'Adds extra space between lines, words, and letters.'],
];

include APPPATH . 'Views/layout/header.php';
?>

<div class="page-header">
  <h4><i class="bi bi-universal-access me-2"></i>Display &amp; Accessibility</h4>
  <p>Adjust how ACADOCS looks and behaves on this device</p>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <!-- Display -->
    <div class="card mb-4">
      <div class="card-header bg-white py-3">
        <span class="fw-semibold"><i class="bi bi-display me-2 text-muted"></i>Display</span>
      </div>
      <div class="card-body">
        <fieldset class="mb-4">
          <legend class="form-label fs-6 mb-2">Theme</legend>
          <div class="pref-choice-group" role="radiogroup">
            <?php foreach ([['light', 'bi-sun', 'Light'], ['dark', 'bi-moon-stars', 'Dark'], ['system', 'bi-circle-half', 'Match device']] as [$value, $icon, $label]): ?>
            <input type="radio" class="btn-check" name="pref-theme" id="pref-theme-<?= $value ?>" value="<?= $value ?>" data-pref="theme" autocomplete="off">
            <label class="pref-choice" for="pref-theme-<?= $value ?>"><i class="bi <?= $icon ?>"></i><?= $label ?></label>
            <?php endforeach; ?>
          </div>
          <div class="form-text">"Match device" follows your computer or phone's light/dark setting.</div>
        </fieldset>

        <fieldset>
          <legend class="form-label fs-6 mb-2">Text size</legend>
          <div class="pref-choice-group" role="radiogroup">
            <?php foreach ([['90', 'Small', '.85rem'], ['100', 'Default', '1rem'], ['112.5', 'Large', '1.15rem'], ['125', 'Extra large', '1.3rem']] as [$value, $label, $size]): ?>
            <input type="radio" class="btn-check" name="pref-textSize" id="pref-text-<?= str_replace('.', '_', $value) ?>" value="<?= $value ?>" data-pref="textSize" autocomplete="off">
            <label class="pref-choice" for="pref-text-<?= str_replace('.', '_', $value) ?>">
              <span class="pref-choice-glyph" style="font-size:<?= $size ?>;" aria-hidden="true">Aa</span><?= $label ?>
            </label>
            <?php endforeach; ?>
          </div>
          <div class="form-text">Scales text across every page. Your browser's own zoom (Ctrl + / Ctrl −) still works on top of this.</div>
        </fieldset>
      </div>
    </div>

    <!-- Accessibility -->
    <div class="card">
      <div class="card-header bg-white py-3">
        <span class="fw-semibold"><i class="bi bi-universal-access-circle me-2 text-muted"></i>Accessibility</span>
      </div>
      <div class="card-body p-0">
        <?php foreach ($accessibilityToggles as [$key, $icon, $label, $help]): ?>
        <label class="pref-toggle" for="pref-<?= $key ?>">
          <i class="bi <?= $icon ?> pref-toggle-icon" aria-hidden="true"></i>
          <span class="pref-toggle-text">
            <span class="pref-toggle-label"><?= e($label) ?></span>
            <span class="pref-toggle-help" id="pref-<?= $key ?>-help"><?= e($help) ?>
              <?php if ($key === 'reduceMotion'): ?>
              <span class="d-none" id="prefMotionOsNote"><br><i class="bi bi-info-circle me-1"></i>Your device already asks for reduced motion, so it stays on.</span>
              <?php endif; ?>
            </span>
          </span>
          <span class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" role="switch" id="pref-<?= $key ?>" data-pref="<?= $key ?>" aria-describedby="pref-<?= $key ?>-help">
          </span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <!-- Live preview -->
    <div class="card mb-4 pref-preview-card">
      <div class="card-header bg-white py-3">
        <span class="fw-semibold"><i class="bi bi-eye me-2 text-muted"></i>Preview</span>
      </div>
      <div class="card-body">
        <h6 class="fw-bold mb-1">Quarterly Accomplishment Report</h6>
        <p class="text-muted small mb-2">Due Friday · Submitted by 18 of 24 teachers</p>
        <p class="mb-3">Changes apply instantly across every page. Here's how a <a href="#" onclick="return false;">link</a>, muted text and a button look with your current settings.</p>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-primary btn-sm">Primary action</button>
          <button type="button" class="btn btn-outline-secondary btn-sm">Secondary</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <p class="small text-muted mb-3">
          <i class="bi bi-laptop me-1"></i>These settings are saved in this browser only. Set them again on each computer or phone you use.
        </p>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetDisplayPrefs(this)">
          <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to defaults
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = <<<'HTML'
<script>
function syncSettingsControls() {
    const prefs = AcadocsPrefs.get();
    document.querySelectorAll('[data-pref]').forEach(el => {
        const value = prefs[el.dataset.pref];
        if (el.type === 'radio') el.checked = String(value) === el.value;
        else el.checked = !!value;
    });

    const osMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const motionToggle = document.getElementById('pref-reduceMotion');
    const motionNote = document.getElementById('prefMotionOsNote');
    if (motionToggle && osMotion) { motionToggle.checked = true; motionToggle.disabled = true; }
    if (motionNote) motionNote.classList.toggle('d-none', !osMotion);
}

document.querySelectorAll('[data-pref]').forEach(el => {
    el.addEventListener('change', () => {
        const key = el.dataset.pref;
        let value = el.type === 'checkbox' ? el.checked : el.value;
        if (key === 'textSize') value = Number(value);
        AcadocsPrefs.set(key, value, { origin: themeTransitionOrigin(el.labels && el.labels[0]) });
    });
});

function resetDisplayPrefs(btn) {
    AcadocsPrefs.reset({ origin: themeTransitionOrigin(btn) });
    showToast('Display settings reset to defaults.');
}

// Keeps the controls in step with the topbar theme toggle or another tab.
// Registered once — this script re-runs on every AJAX visit to the page.
if (!window.settingsPrefsListenerAdded) {
    window.settingsPrefsListenerAdded = true;
    document.addEventListener('acadocs:prefschange', () => {
        if (document.querySelector('[data-pref]')) syncSettingsControls();
    });
}

syncSettingsControls();
</script>
HTML;

include APPPATH . 'Views/layout/footer.php';
?>
