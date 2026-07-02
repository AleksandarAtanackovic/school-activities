<?php
// Expects $u (current user) and optional $page_title to be set before include.
$u = $u ?? current_user();
$pt = $page_title ?? APP_NAME;
?><!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pt) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(asset_ver('assets/style.css')) ?>">
</head>
<body>
<header class="topbar">
  <a class="brand" href="dashboard.php"><?= e(APP_NAME) ?></a>
  <?php if ($u): ?>
  <input type="checkbox" id="navtoggle" class="navtoggle">
  <label for="navtoggle" class="navburger" aria-label="Мени"><span></span><span></span><span></span></label>
  <div class="navmenu">
    <nav class="mainnav">
      <a href="dashboard.php">Почетна</a>
      <a href="activities.php">Секције</a>
      <?php if (is_student($u)): ?>
        <a href="my_activities.php">Моје секције</a>
        <a href="notifications.php">Обавештења</a>
      <?php endif; ?>
      <?php if (is_admin($u) || is_teacher($u)): ?>
        <a href="applications.php">Пријаве</a>
        <a href="attendance.php">Присуство</a>
      <?php endif; ?>
      <?php if (is_admin($u)): ?>
        <a href="users.php">Корисници</a>
      <?php endif; ?>
    </nav>
    <div class="userbox">
      <span class="uname"><?= e($u['name']) ?> <span class="role"><?= e(role_label($u['role'])) ?></span></span>
      <a class="btn btn-ghost" href="change_password.php">Лозинка</a>
      <a class="btn btn-ghost" href="logout.php">Одјава</a>
    </div>
  </div>
  <?php endif; ?>
</header>
<main class="container">
<?php if ($f = flash()): ?>
  <div class="flash"><?= e($f) ?></div>
<?php endif; ?>
