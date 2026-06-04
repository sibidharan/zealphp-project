<?php
// HTMX playground. Each panel shows the real server handler (left) and a live,
// htmx-driven result (right). The handlers live in route/playground.php and run
// in THIS app — no mock data, no separate backend.
use ZealPHP\App;
?>
<section class="hero">
  <div class="bolt">⚡</div>
  <h1>htmx <span>playground</span></h1>
  <p class="lead">Hypermedia, server-driven. Every panel below is a real ZealPHP route
     responding to an <code>hx-get</code>/<code>hx-post</code> — no JSON glue, no JS build step.</p>
</section>

<section class="wrap">
  <div class="callout">
    <strong>How this works:</strong> htmx is loaded once in <code>template/_head.php</code> and
    <code>hx-boost</code> is on the <code>&lt;body&gt;</code>. A click/submit fires an AJAX request to a
    ZealPHP handler; the handler returns an HTML <em>fragment</em>, and htmx swaps it into the page.
    The source on the left is exactly what runs in <code>route/playground.php</code>.
  </div>

<?php
// 1 ─ Routing + params + $req
App::render('components/_demo', [
  'title' => '1 · Routing, params & $req',
  'hint'  => '<span class="badge badge-get">GET</span> /playground/greet',
  'code'  => <<<'PHP'
// route/playground.php — $req / $res are short
// aliases for $request / $response (v0.4.0).
$app->route('/playground/greet', function ($req) {
    $name = trim($req->get['name'] ?? '') ?: 'world';
    $name = htmlspecialchars($name);
    return "<strong>Hello, {$name}!</strong>
            <br><small>served by worker "
          . getmypid() . "</small>";
});
PHP,
  'live'  => <<<'HTML'
<div class="demo-controls">
  <input type="text" name="name" placeholder="your name…"
         hx-get="/playground/greet" hx-target="#greet-out" hx-trigger="keyup changed delay:250ms, search">
  <button class="btn btn-primary btn-sm"
          hx-get="/playground/greet" hx-include="[name='name']" hx-target="#greet-out">Greet</button>
</div>
<div id="greet-out" class="demo-out"></div>
HTML,
]);

// 2 ─ Universal return contract
App::render('components/_demo', [
  'title' => '2 · The universal return contract',
  'hint'  => 'array→JSON · int→status · generator→stream',
  'code'  => <<<'PHP'
// One rule, three handlers — the return TYPE
// decides the response.
$app->route('/playground/json', fn () =>
    ['framework' => 'ZealPHP', 'ok' => true, 'pid' => getmypid()]);   // → JSON

$app->route('/playground/teapot', fn () => 418);                      // → HTTP 418

$app->route('/playground/stream', fn () => (function () {             // → streamed
    foreach (['Streaming ', 'one ', 'chunk ', 'at ', 'a ', 'time…'] as $w) {
        yield $w; usleep(120000);
    }
})());
PHP,
  'live'  => <<<'HTML'
<div class="demo-controls">
  <button class="btn btn-outline btn-sm" hx-get="/playground/json"   hx-target="#ret-out">array → JSON</button>
  <button class="btn btn-outline btn-sm" hx-get="/playground/teapot" hx-target="#ret-out" hx-swap="innerHTML">int → 418</button>
  <button class="btn btn-outline btn-sm" hx-get="/playground/stream" hx-target="#ret-out">generator → stream</button>
</div>
<div id="ret-out" class="demo-out"></div>
HTML,
]);

// 3 ─ Store + Counter (cross-worker)
App::render('components/_demo', [
  'title' => '3 · Store &amp; Counter — cross-worker state',
  'hint'  => '<span class="badge badge-post">POST</span> /playground/counter',
  'code'  => <<<'PHP'
// $hits is a Counter created at the top of
// route/playground.php (before run() forks) —
// one atomic integer shared by every worker.
$app->route('/playground/counter', methods: ['POST'],
  handler: function () use ($hits) {
    $n = $hits->increment();
    return "<strong>{$n}</strong> total hits
            <br><small>this one served by worker "
          . getmypid() . "</small>";
});
PHP,
  'live'  => <<<'HTML'
<div class="demo-controls">
  <button class="btn btn-primary btn-sm" hx-post="/playground/counter" hx-target="#counter-out">Hit the counter</button>
</div>
<div id="counter-out" class="demo-out">click — every worker shares the same count</div>
HTML,
]);

// 4 ─ HTMX response helpers (HtmxResponse → HX-Trigger)
App::render('components/_demo', [
  'title' => '4 · HtmxResponse — server-driven UI',
  'hint'  => 'HX-Trigger fires a client event',
  'code'  => <<<'PHP'
// HtmxResponse sets HX-* response headers. Here
// the server tells the browser to fire a "toast"
// event AND swaps a confirmation fragment.
$app->route('/playground/toast', methods: ['POST'],
  handler: function ($res) {
    $res->htmx()->trigger(
        '{"toast":"⚡ Triggered from the server!"}'
    );
    return "<strong>Sent HX-Trigger.</strong>
            <br><small>the toast came from PHP, "
          . "not JS</small>";
});
PHP,
  'live'  => <<<'HTML'
<div class="demo-controls">
  <button class="btn btn-primary btn-sm" hx-post="/playground/toast" hx-target="#toast-out">Trigger a toast</button>
</div>
<div id="toast-out" class="demo-out"></div>
HTML,
]);

// 5 ─ SSE live stream
App::render('components/_demo', [
  'title' => '5 · Server-Sent Events — $res->sse()',
  'hint'  => '<span class="badge badge-sse">SSE</span> /playground/sse',
  'code'  => <<<'PHP'
// $res->sse($fn) flushes headers, then $emit()
// streams events as coroutines resolve. The
// browser reads them with EventSource.
$app->route('/playground/sse', function ($res) {
    return $res->sse(function ($emit) {
        for ($i = 1; $i <= 5; $i++) {
            $emit("tick {$i} · " . date('H:i:s'), 'tick', (string)$i);
            usleep(700000);
        }
        $emit('done', 'close');
    });
});
PHP,
  'live'  => <<<'HTML'
<div class="demo-controls">
  <button class="btn btn-primary btn-sm" data-sse-start data-sse-url="/playground/sse" data-sse-target="#sse-out">Start stream</button>
</div>
<div id="sse-out" class="demo-out"></div>
HTML,
]);
?>
</section>
