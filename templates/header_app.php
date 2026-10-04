<?php
/**
 * Header aplikasi (halaman internal yang butuh login).
 * Variabel: $page_title, $active (kunci nav), $breadcrumb (array label => url|null)
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

$user = current_user();
$canEdit = can_edit_data();
$isAdmin = ($user['role'] ?? '') === 'admin';

$nav = [
    'dashboard'  => ['label' => 'Dasbor',            'url' => 'dashboard.php',      'show' => true],
    'responden'  => ['label' => 'Responden',         'url' => 'responden/index.php','show' => true],
    'usaha'      => ['label' => 'Usaha',             'url' => 'usaha/index.php',    'show' => true],
    'laporan'    => ['label' => 'Laporan',           'url' => 'laporan.php',        'show' => true],
    'pengguna'   => ['label' => 'Pengguna',          'url' => 'pengguna/index.php', 'show' => $isAdmin],
    'audit'      => ['label' => 'Audit Log',         'url' => 'audit_log.php',      'show' => $isAdmin],
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($page_title ?? 'Dasbor') ?> — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= e(u('assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(u('assets/css/style.css')) ?>">
</head>
<body class="app">

<a class="skip-link" href="#main-content">Lewati ke konten utama</a>

<header class="app-topbar">
  <div class="app-topbar-inner">
    <div class="app-topbar-left">
      <button class="nav-toggle" type="button" data-nav-toggle aria-label="Buka menu navigasi" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <a class="brand brand-on-dark" href="<?= e(u('dashboard.php')) ?>">
        <img class="brand-mark" src="<?= e(u('assets/img/logo.svg')) ?>" alt="Logo <?= e(APP_NAME) ?>" width="30" height="30">
        <span class="brand-text">
          <strong><?= e(APP_NAME) ?> <?= e(APP_YEAR) ?></strong>
          <span><?= e(APP_NAME_LONG) ?></span>
        </span>
      </a>
    </div>
    <div class="app-topbar-right">
      <div class="user-chip">
        <span class="user-chip-name"><?= e($user['full_name'] ?? '-') ?></span>
        <span class="user-chip-role"><?= e(strtoupper($user['role'] ?? '-')) ?></span>
      </div>
      <form method="post" action="<?= e(u('logout.php')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <button class="btn btn-outline-light btn-sm" type="submit">Keluar</button>
      </form>
    </div>
  </div>
</header>

<div class="app-shell">
  <nav class="app-sidenav" id="app-sidenav" aria-label="Navigasi utama">
    <p class="sidenav-caption">Pendataan</p>
    <?php foreach ($nav as $key => $item): if (!$item['show']) continue; ?>
      <a class="sidenav-link<?= ($active ?? '') === $key ? ' is-active' : '' ?>" href="<?= e(u($item['url'])) ?>"><?= e($item['label']) ?></a>
    <?php endforeach; ?>
    <p class="sidenav-caption">Akun</p>
    <a class="sidenav-link<?= ($active ?? '') === 'profil' ? ' is-active' : '' ?>" href="<?= e(u('profil.php')) ?>">Profil Saya</a>
    <div class="sidenav-foot">
      <p class="sidenav-foot-title"><?= e(APP_NAME) ?></p>
      <p class="sidenav-foot-text">Data yang terdapat dalam sistem ini bersifat rahasia dan hanya untuk keperluan pendataan resmi.</p>
    </div>
  </nav>

  <main class="app-content" id="main-content">
    <?php if (!empty($breadcrumb)): ?>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <?php foreach ($breadcrumb as $label => $url): ?>
          <?php if ($url): ?>
            <a href="<?= e(u($url)) ?>"><?= e($label) ?></a><span aria-hidden="true">/</span>
          <?php else: ?>
            <span class="breadcrumb-here" aria-current="page"><?= e($label) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <div class="page-head">
      <h1 class="page-title"><?= e($page_title ?? '') ?></h1>
    </div>

    <?php $flash = flash_get(); if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>" role="status">
        <p><?= e($flash['msg']) ?></p>
      </div>
    <?php endif; ?>
