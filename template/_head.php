<?php
// Page <head>. Cache-busts CSS/JS by file mtime (no build step). htmx is loaded
// from the CDN with the head-support extension so hx-boost navigation keeps the
// <title> + per-page assets in sync across swaps.
/** @var string $title */
/** @var string $description */
$root = dirname(__DIR__);
$cssV = @filemtime($root . '/public/css/scaffold.css') ?: 1;
$jsV  = @filemtime($root . '/public/js/scaffold.js') ?: 1;
$pageTitle = isset($title) && $title !== '' ? $title . ' · ZealPHP' : 'ZealPHP';
?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
  <meta name="description" content="<?= htmlspecialchars($description ?? 'A ZealPHP app — coroutine PHP on OpenSwoole.', ENT_QUOTES) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap">
  <link rel="stylesheet" href="/css/scaffold.css?v=<?= $cssV ?>">
  <script src="https://unpkg.com/htmx.org@2.0.10" defer></script>
  <script src="https://unpkg.com/htmx-ext-head-support@2.0.4/head-support.js" defer></script>
  <script src="/js/scaffold.js?v=<?= $jsV ?>" defer></script>
</head>
