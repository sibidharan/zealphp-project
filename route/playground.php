<?php
// Playground demo endpoints — the live half of every panel on /playground.
// Thin handlers that return HTML fragments (htmx swaps them in). $req / $res
// are short aliases for $request / $response (ZealPHP v0.4.0+).
//
// Route files auto-load at startup with $app in scope. Keep them thin — real
// apps push logic into src/ service classes.

use ZealPHP\Counter;

// One atomic integer shared by every worker — constructed in the master before
// run() forks, so all workers increment the same count (no DB, no race).
$hits = new Counter(0);

// 1 ─ Routing, params & $req ------------------------------------------------
$app->route('/playground/greet', function ($req) {
    $name = trim((string) ($req->get['name'] ?? '')) ?: 'world';
    $name = htmlspecialchars($name, ENT_QUOTES);
    return "<strong>Hello, {$name}!</strong><br><small>served by worker " . getmypid() . "</small>";
});

// 2 ─ The universal return contract -----------------------------------------
$app->route('/playground/json',   fn () => ['framework' => 'ZealPHP', 'ok' => true, 'pid' => getmypid()]); // → JSON
$app->route('/playground/teapot', fn () => 418);                                                            // → HTTP 418
$app->route('/playground/stream', fn () => (function () {                                                   // → streamed
    foreach (['Streaming ', 'one ', 'chunk ', 'at ', 'a ', 'time…'] as $w) {
        yield $w;
        usleep(120000);
    }
})());

// 3 ─ Store & Counter — cross-worker state ----------------------------------
$app->route('/playground/counter', methods: ['POST'], handler: function () use ($hits) {
    $n = $hits->increment();
    return "<strong>{$n}</strong> total hits<br><small>this one served by worker " . getmypid() . "</small>";
});

// 4 ─ HtmxResponse — server-driven UI ---------------------------------------
$app->route('/playground/toast', methods: ['POST'], handler: function ($res) {
    $res->htmx()->trigger('{"toast":"⚡ Triggered from the server!"}');
    return "<strong>Sent HX-Trigger.</strong><br><small>the toast came from PHP, not JS</small>";
});

// 5 ─ Server-Sent Events — $res->sse() --------------------------------------
$app->route('/playground/sse', function ($res) {
    return $res->sse(function ($emit) {
        for ($i = 1; $i <= 5; $i++) {
            $emit("tick {$i} · " . date('H:i:s'), 'tick', (string) $i);
            usleep(700000);
        }
        $emit('done', 'close');
    });
});
