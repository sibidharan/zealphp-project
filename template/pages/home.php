<?php
// Landing page. Computes its own runtime stamp so it's always accurate.
$php = PHP_VERSION;
$osw = defined('OPENSWOOLE_VERSION') ? OPENSWOOLE_VERSION : 'OpenSwoole';
?>
<section class="hero">
  <div class="bolt">⚡</div>
  <span class="status"><span class="dot"></span> running</span>
  <h1>Your ZealPHP app is <span>live</span>.</h1>
  <p class="lead">Coroutine PHP on OpenSwoole — one process, many concurrent requests,
     real streaming, WebSockets, and an htmx-native rendering model.</p>
  <div class="cta">
    <a class="btn btn-primary" href="/playground">Open the playground →</a>
    <a class="btn btn-outline" href="https://php.zeal.ninja" target="_blank" rel="noopener">Read the docs</a>
  </div>
  <p class="hero-stamp">PHP <?= htmlspecialchars($php, ENT_QUOTES) ?> · OpenSwoole <?= htmlspecialchars($osw, ENT_QUOTES) ?> · serving from one worker pool</p>
</section>

<section class="wrap">
  <div class="eyebrow">What you got</div>
  <h2 class="section-title">A batteries-included starting point</h2>
  <p class="section-desc">This page, the nav, and the playground are all served by the app you just created.
     Open <code class="inline">template/</code>, <code class="inline">route/</code>, and <code class="inline">public/</code> to see how — then make it yours.</p>

  <div class="feature-grid">
    <div class="card">
      <span class="card-icon">🧵</span>
      <h3>Coroutine concurrency</h3>
      <p>Each request runs in its own coroutine with isolated state. Blocking I/O (DB, HTTP, files) yields instead of stalling the worker.</p>
      <a class="card-link" href="https://php.zeal.ninja/coroutines" target="_blank" rel="noopener">Coroutines →</a>
    </div>
    <div class="card">
      <span class="card-icon">🔁</span>
      <h3>Universal return contract</h3>
      <p>Return an array → JSON. An int → HTTP status. A string → HTML. A generator → a live stream. One rule, every handler.</p>
      <a class="card-link" href="https://php.zeal.ninja/responses" target="_blank" rel="noopener">Responses →</a>
    </div>
    <div class="card">
      <span class="card-icon">⚡</span>
      <h3>htmx-native</h3>
      <p><code class="inline">$req-&gt;isHtmx()</code>, <code class="inline">HtmxResponse</code> (HX-Trigger / HX-Redirect / HX-Retarget), and <code class="inline">App::fragment()</code> partial rendering — hypermedia without a JS build step.</p>
      <a class="card-link" href="/playground">See it live →</a>
    </div>
    <div class="card">
      <span class="card-icon">🧩</span>
      <h3>Middleware &amp; routing</h3>
      <p>PSR-15 global, per-route, group, and path-scoped middleware. Flask-style <code class="inline">{param}</code> routes with by-name injection (<code class="inline">$req</code>, <code class="inline">$res</code>, <code class="inline">{params}</code>).</p>
      <a class="card-link" href="https://php.zeal.ninja/middleware" target="_blank" rel="noopener">Middleware →</a>
    </div>
    <div class="card">
      <span class="card-icon">🗃️</span>
      <h3>Store &amp; Counter</h3>
      <p>Cross-worker shared memory (<code class="inline">Store</code>) and lock-free atomics (<code class="inline">Counter</code>) — flip to Redis for cross-node with the same API.</p>
      <a class="card-link" href="https://php.zeal.ninja/store" target="_blank" rel="noopener">Store →</a>
    </div>
    <div class="card">
      <span class="card-icon">📡</span>
      <h3>Streaming &amp; WebSocket</h3>
      <p>SSE via <code class="inline">$res-&gt;sse()</code>, generator SSR, and <code class="inline">App::ws()</code> WebSocket endpoints on the same port as your HTTP routes.</p>
      <a class="card-link" href="https://php.zeal.ninja/streaming" target="_blank" rel="noopener">Streaming →</a>
    </div>
  </div>

  <div class="eyebrow mt-lg">Next steps</div>
  <h2 class="section-title">Make it yours</h2>
  <ul class="steps">
    <li><span class="n">1</span><span class="t">Edit <code>app.php</code> — middleware, lifecycle, and your first routes live here.</span></li>
    <li><span class="n">2</span><span class="t">Drop route files in <code>route/</code> and REST endpoints in <code>api/</code> — both auto-load at startup.</span></li>
    <li><span class="n">3</span><span class="t">Put pages + assets in <code>public/</code> and views in <code>template/</code> — this very page is <code>public/index.php</code>.</span></li>
    <li><span class="n">4</span><span class="t">Open the <a href="/playground">Playground</a> to see routing, the return contract, Store/Counter, htmx helpers, and SSE running live.</span></li>
  </ul>
</section>
