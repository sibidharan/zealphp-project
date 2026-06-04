<?php
// Reusable playground panel: server code on the left, a live (htmx-wired)
// output on the right. Args:
//   $title  string  — panel heading
//   $hint   string  — small monospace hint (raw HTML, optional)
//   $code   string  — the handler source shown on the left (escaped)
//   $live   string  — the live demo markup shown on the right (raw HTML — trusted)
/** @var string $title */
/** @var string $hint */
/** @var string $code */
/** @var string $live */
?>
<div class="demo">
  <div class="demo-head">
    <h3><?= htmlspecialchars($title ?? '', ENT_QUOTES) ?></h3>
    <?php if (!empty($hint)): ?><span class="hint"><?= $hint ?></span><?php endif; ?>
  </div>
  <div class="demo-body">
    <div class="demo-code"><pre><code><?= htmlspecialchars($code ?? '', ENT_QUOTES) ?></code></pre></div>
    <div class="demo-live">
      <span class="live-label">live output</span>
      <?= $live ?? '' ?>
    </div>
  </div>
</div>
