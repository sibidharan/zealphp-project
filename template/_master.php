<?php
// Universal layout. Caller: App::render('_master', ['title'=>..., 'page'=>'home', 'active'=>'home']).
// 'page' names the page's template, looked up in the page's own folder first
// (public/index.php -> template/index/home.php).
// $page names the page's template (template/<public file>/<page>.php). hx-boost makes every <a>/<form> an AJAX
// swap; head-support keeps <title> + assets in sync across boosted navigations.
/** @var string $title */
/** @var string $page */
/** @var string $active */
/** @var string $description */
use ZealPHP\App;
$page = $page ?? 'home';
?><!doctype html>
<html lang="en">
<?php App::render('_head', ['title' => $title ?? '', 'description' => $description ?? '']); ?>
<body hx-boost="true" hx-ext="head-support">
<?php App::render('_nav', ['active' => $active ?? $page]); ?>
<main id="main">
<?php App::render($page); /* the page's own template: template/<public file>/<page>.php */ ?>
</main>
<?php App::render('_footer'); ?>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
</body>
</html>
