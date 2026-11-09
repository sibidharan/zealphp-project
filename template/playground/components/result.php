<?php
// One panel result: a bold value, optional text after it, and a small note.
// Rendered by the /playground/* routes: App::component('/playground/components/result', [...]).
use function ZealPHP\e;
?>
<strong><?= e($strong) ?></strong><?= e($text ?? '') ?><br><small><?= e($note) ?></small>
