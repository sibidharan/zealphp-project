<?php
// Top navigation. $active = current page key ('home' | 'playground').
/** @var string $active */
$a = $active ?? '';
?>
<nav class="topnav">
  <a href="/" class="logo"><span class="bolt">⚡</span>zeal<span>·</span>PHP</a>
  <div class="nav-links">
    <a href="/"           class="<?= $a === 'home' ? 'active' : '' ?>">Home</a>
    <a href="/playground" class="<?= $a === 'playground' ? 'active' : '' ?>">Playground</a>
    <span class="spacer"></span>
    <a href="https://php.zeal.ninja" class="ext" target="_blank" rel="noopener">Docs</a>
    <a href="https://github.com/sibidharan/zealphp" class="ext" target="_blank" rel="noopener">GitHub</a>
  </div>
</nav>
