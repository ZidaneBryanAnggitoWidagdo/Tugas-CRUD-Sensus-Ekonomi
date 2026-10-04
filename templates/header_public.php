<?php
/**
 * Header halaman publik (landing, login).
 * Variabel: $page_title (string)
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="index, follow">
<title><?= e($page_title ?? APP_NAME_LONG) ?> — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= e(u('assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(u('assets/css/style.css')) ?>">
</head>
<body class="pub">

<header class="pub-header">
  <div class="pub-header-inner">
    <a class="brand" href="<?= e(u('index.php')) ?>">
      <img class="brand-mark" src="<?= e(u('assets/img/logo.svg')) ?>" alt="Logo <?= e(APP_NAME) ?>" width="34" height="34">
      <span class="brand-text">
        <strong><?= e(APP_NAME) ?> <?= e(APP_YEAR) ?></strong>
        <span><?= e(APP_NAME_LONG) ?></span>
      </span>
    </a>
    <div class="pub-header-actions">
      <?php if (is_logged_in()): ?>
        <a class="btn btn-outline-light" href="<?= e(u('dashboard.php')) ?>">Ke Dasbor</a>
      <?php else: ?>
        <a class="btn btn-outline-light" href="<?= e(u('login.php')) ?>">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</header>
