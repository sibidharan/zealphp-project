<?php
// Playground demo endpoints — the live half of every panel on /playground.
// Route files are wiring: read the input, do the work (in a src/ class in a
// real app), return data, a status, or a component. The HTML fragments htmx
// swaps in are components (template/playground/components/), never strings
// built here. $req / $res are short aliases for $request / $response.

use ZealPHP\App;
use ZealPHP\Counter;

// One atomic integer shared by every worker — constructed in the master before
// run() forks, so all workers increment the same count (no DB, no race).
$hits = new Counter(0);

// 1 ─ Routing, params & $req ------------------------------------------------
$app->route('/playground/greet', function ($req) {
    $name = trim((string) ($req->get['name'] ?? '')) ?: 'world';
    return App::component('/playground/components/result', [
        'strong' => "Hello, {$name}!",
        'note'   => 'served by worker ' . getmypid(),
    ]);
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
    return App::component('/playground/components/result', [
        'strong' => (string) $hits->increment(),
        'text'   => ' total hits',
        'note'   => 'this one served by worker ' . getmypid(),
    ]);
});

// 4 ─ HtmxResponse — server-driven UI ---------------------------------------
$app->route('/playground/toast', methods: ['POST'], handler: function ($res) {
    $res->htmx()->trigger('{"toast":"Triggered from the server!"}');
    return App::component('/playground/components/result', [
        'strong' => 'Sent HX-Trigger.',
        'note'   => 'the toast came from PHP, not JS',
    ]);
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
