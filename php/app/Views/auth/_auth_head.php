<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($authTitle) ?> — ACADOCS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<!-- The campus photo is the page's largest image: start it now, not after app.css arrives. -->
<link rel="preload" as="image" type="image/webp" href="<?= base_url('assets/img/login-bg-sm.webp') ?>" media="(max-aspect-ratio: 4/5)" fetchpriority="high">
<link rel="preload" as="image" type="image/webp" href="<?= base_url('assets/img/login-bg.webp') ?>" media="not all and (max-aspect-ratio: 4/5)" fetchpriority="high">
<!-- Fonts load without holding up the first paint (text shows in the fallback font, then swaps). -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"></noscript>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= asset_url('assets/css/app.css') ?>">
<link rel="icon" type="image/png" href="<?= asset_url('assets/img/logo-icon.png') ?>">
</head>
<body>
<div class="login-wrapper login-wrapper-photo">
  <div class="login-bg" aria-hidden="true">
    <div class="login-bg-photo"></div>
    <div class="login-bg-overlay"></div>
  </div>
  <div class="login-shell login-shell-compact login-shell-glass">
    <div class="login-form-panel">
      <div class="d-flex align-items-center gap-2 mb-4">
        <picture class="brand-pic"><source type="image/webp" srcset="<?= asset_url('assets/img/logo-icon-sm.webp') ?>"><img src="<?= asset_url('assets/img/logo-icon-sm.png') ?>" alt="ACADOCS" width="96" height="96" style="width:34px;height:34px;object-fit:contain;"></picture>
        <span class="fw-bold" style="font-size:1.05rem;color:var(--text);">ACADOCS</span>
      </div>
