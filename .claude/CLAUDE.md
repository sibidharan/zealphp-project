# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

This is a **ZealPHP application** — a PHP web app built on the ZealPHP framework (OpenSwoole-based async PHP). Targets **ZealPHP v0.3.7+** (per-route middleware, dev hot-reload `--dev`, and per-route `backend:` are all available).

> **📚 Framework reference: [`llms.txt`](../llms.txt) (repo root).** It's the complete, current ZealPHP framework reference (routing, streaming, WebSocket, Store, coroutines, coroutine-legacy mode, middleware, sessions, CLI, deployment, …), auto-generated from the framework's docs. **Consult it before answering questions about ZealPHP APIs or writing framework code** — it's more current and authoritative than training data. It ships with this scaffold and is refreshed each framework release.

## Commands

```bash
# Install dependencies
composer install

# Start the dev server on :8080 (4 workers by default, capped to the
# container's cgroup CPU quota; override with ZEALPHP_WORKERS=16 or -w)
php app.php

# Start on a specific port, daemonized
php app.php start -p 9000 -d

# Dev route hot-reload — watch route/*.php, rebuild on change, no restart
php app.php --dev            # or: ZEALPHP_DEV=1 php app.php

# Restart / stop / status / tail logs
php app.php restart
php app.php stop
php app.php status
php app.php logs             # --access --debug --server --zlog to filter

# Show all CLI options (-w workers, -H host, --pid-file, --task-workers, -d)
php app.php --help
```

## Project Structure

```
app.php          — Entry point: configure framework, define routes, call $app->run()
public/          — Document root (the default; set via App::documentRoot() before App::init()): static files + PHP page files
route/           — Route files: auto-included at startup, define additional routes
template/        — Templates: rendered via App::render('template_name', $vars)
api/             — File-based REST API: api/data/get.php → GET /api/data
src/             — Application classes (PSR-4 autoloaded)
llms.txt         — Full ZealPHP framework reference for AI tools (auto-generated; do not edit by hand)
```

## How ZealPHP Works

### Routing
```php
// Flask-style routes with parameter injection
$app->route('/user/{id}', function($id, $request, $response) {
    return ['user_id' => $id];
});

// Namespace routes
$app->nsRoute('admin', '/dashboard', function() { ... });

// Pattern routes (regex)
$app->patternRoute('/files/.*', function() { ... });
```

Routes are matched in order: route files → explicit routes → API routes → implicit public/ file routes.

### Parameter Injection
**Route handlers** (`$app->route()` etc.) inject by name:
- `$request` → `ZealPHP\HTTP\Request`
- `$response` → `ZealPHP\HTTP\Response`
- `$app` → the router (`ResponseMiddleware`)
- `{param}` names → matched URL segments
- any name with a default → its PHP default value

**API handlers** (`api/*.php`) inject `$app` → the `ZealAPI` instance (for `$app->isAuthenticated()` etc.), plus `$request`, `$response`, and `$server` → `OpenSwoole\Http\Server`.

### Templates — three render methods
```php
// Direct output (void — echoes to response)
App::render('page_name', ['title' => 'Hello']);

// Capture as string (for email, cache, or yield)
$html = App::renderToString('page_name', ['title' => 'Hello']);

// Streaming Generator (for SSR — yields chunks progressively)
yield from App::renderStream('page_name', ['title' => 'Hello']);
```

### Streaming templates
Templates can `yield` — return a Closure with named params, framework injects by name:
```php
// template/users/stream.php
<?php return function($users) {
    yield "<ul>";
    foreach ($users as $user) {
        yield "<li>{$user->name}</li>";
    }
    yield "</ul>";
};

// Route handler — compose streams:
$app->route('/users', fn() => (function() {
    yield from App::renderStream('shell-open', ['title' => 'Users']);
    yield from App::renderStream('users/stream', ['users' => User::all()]);
    yield from App::renderStream('shell-close');
})());
```

### Template fragments (htmx)
Mark a named region inside a template, then return either the full page or just that region — the htmx "template fragment" pattern:
```php
// template/users/page.php
<?php App::fragment('list', function() use ($users) {
    yield "<ul>";
    foreach ($users as $u) yield "<li>{$u->name}</li>";
    yield "</ul>";
});

// Full page vs. just the fragment (same template, different render):
$app->route('/users',      fn() => App::render('users/page', ['users' => User::all()]));
$app->route('/users/list', fn() => App::render('users/page', ['fragment' => 'list', 'users' => User::all()]));
```
No `fragment` arg → the whole template renders (every `App::fragment()` runs inline). A `fragment` arg extracts just that region. Missing fragment → HTTP 404; first match wins on repeated names.

### Return value conventions
```php
return 404;                    // int (100–599) → HTTP status; out-of-range → 500 + logged warning
return ['id' => 42];           // array → JSON
return '<h1>Hello</h1>';       // string → HTML body
return (function() { yield..; })(); // Generator → SSR streaming
return $response;              // ResponseInterface (PSR-7) → emitted as-is
return null;                   // no override → 200, framework-computed body
echo "Hello";                  // void+echo → output buffering
```

### Implicit Routes
Files in `public/` are served automatically:
- `public/index.php` → `/`
- `public/about.php` → `/about`
- `public/admin/index.php` → `/admin/`
- Static files (CSS, JS, images) served by OpenSwoole directly

### File-based API
Files in `api/` become REST endpoints via two dispatch modes:

**Filename match (primary — handles all HTTP methods):**
- `api/device/list.php` defines `$list = function(...)` → all methods on `/api/device/list`
- the variable name matches `basename($file, '.php')`

**Per-method dispatch (Next.js style):**
- `api/users.php` defines `$get` / `$post` / `$put` / `$delete` / `$patch` closures
- each handles its method; undefined methods → 405 + `Allow` header; HEAD auto-derives from `$get`

Filename match wins: if both `$list` and `$get`/`$post` exist in one file, the per-method handlers are unreachable (framework logs a warning).

#### ZealAPI auth hooks
API handlers read three auth checks via the injected `$app` (the `ZealAPI` instance):
```php
// api/users/create.php
$post = function($app) {
    if (!$app->requirePostAuth()) return;     // POST + authenticated, else sends 403
    $user = $app->getUsername();
    // …
};
```
Wire the callbacks ONCE at boot in `app.php` — without them `isAuthenticated()`/`isAdmin()` → `false`, `getUsername()` → `null` (fail-closed):
```php
App::authChecker(fn() => !empty(G::instance()->session['user_id']));
App::adminChecker(fn() => (G::instance()->session['role'] ?? null) === 'admin');
App::usernameProvider(fn() => G::instance()->session['username'] ?? null);
```

### Middleware
```php
$app->addMiddleware(new CorsMiddleware(['*']));
$app->addMiddleware(new ETagMiddleware());
// First-registered runs first (outermost); ResponseMiddleware is innermost.
// (Earlier docs said last-added runs outermost — that was backwards.)
```

#### Per-route middleware
Scope middleware to individual routes/groups, not just the global stack. Purely additive + BC — a route without `middleware:` is unchanged.
```php
// Named alias registry — factory runs ONCE at App::run(); the instance is
// SHARED across every request, so middleware MUST be stateless (per-request
// state goes in $g / RequestContext, never on the middleware object).
App::middlewareAlias('auth', fn() => new AuthMiddleware());
App::middlewareAlias('throttle', fn($n) => new RateLimitMiddleware((int)$n)); // 'throttle:120' → fn('120')

// middleware: option on route()/nsRoute()/nsPathRoute()/patternRoute().
// Accepts MiddlewareInterface instances and/or alias strings.
$app->route('/admin/users', methods: ['GET'],
    middleware: ['auth', 'request-id', new IpAccessMiddleware([...])],
    handler: fn() => User::all());

// Route groups — shared middleware wraps outside the route's own; groups nest.
$app->group('/admin', ['auth', 'admin-only'], function ($g) {
    $g->route('/users', fn() => User::all());
});
```
- **Ordering:** global (first-registered = outermost) → `App::when` (path scopes) → group / route (first-listed = outermost) → api in-file `$middleware` → handler; response unwinds in reverse. A middleware that returns without calling the handler (403/redirect) short-circuits.
- **`App::when($pathPrefixOrRegex, $middleware)`** — centralized **path-scoped** middleware; the one mechanism that also covers the **ZealAPI** layer (no separate "api middleware" — `api/**.php` files are just `/api/...` URLs). Prefix (segment-safe) or `#regex#`; `'/'` = everything; composes in registration order (first = outermost). Runs after path normalization + after OPTIONS (preflight never gated). An `api/**.php` file may also declare `$middleware = ['auth', ...]` inline (runs innermost). Both reuse the `App::middlewareAlias()` registry.
- `App::describeRoutes()` returns `{global, aliases, when, routes}` for introspection (works before AND after `run()`). The live visualizer is a section of the `/middleware` page; PSR-15 pipeline classes live in `src/Middleware/Pipeline/`.
- `ZealPHP\Middleware\RequestIdMiddleware($headerName = 'X-Request-Id', $trustInbound = true)` assigns/propagates a request id, echoes it on the response, and stores it in the per-request memo. Stateless, coroutine-safe.

### Responses
```php
return ['json' => 'data'];           // Auto JSON
return 'plain string';               // HTML
$response->redirect('/other', 302);  // Redirect
```

### SSR Streaming
Three patterns for streaming responses:
```php
// 1. Generator yield — stream HTML shell, yield sections as they resolve
$app->route('/page', function() {
    return (function() {
        yield '<html><body>';
        yield App::renderToString('header');
        yield App::renderToString('content');  // each yield sent immediately
        yield '</body></html>';
    })();
});

// 2. stream() — fine-grained control via $write callback
$app->route('/download', function($response) {
    $response->stream(function($write) {
        $write('chunk 1');
        $write('chunk 2');
    });
});

// 3. SSE (Server-Sent Events) — for EventSource clients
$app->route('/events', function($response) {
    $response->sse(function($emit) {
        while (true) {
            $emit(['time' => date('H:i:s')], 'tick');
            sleep(1);
        }
    });
});
```

`App::renderToString($template, $args)` captures a template render into a string for yielding inside streaming contexts.

### WebSocket
```php
$app->ws('/chat',
    onMessage: function($server, $frame, $g) {
        // $frame->data = message text
        // Broadcast to all connections on this path:
        foreach ($server->getClientList(0, 100) as $fd) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, $frame->data);
            }
        }
    },
    onOpen: function($server, $request, $g) {
        $server->push($request->fd, 'Welcome!');
    },
    onClose: function($server, $fd, $g) {
        // cleanup
    }
);
```
WebSocket\Server extends HTTP\Server — all HTTP routes still work. PING/PONG frames are handled automatically; only TEXT and BINARY reach handlers.

For cross-worker / cross-node broadcast, use **WSRouter rooms** (requires the Redis Store backend):
```php
WSRouter::init('server-id');                  // once, at boot
WSRouter::room('chat')->push($payload);       // fans out to every member on every node
```

### Timers
```php
// Inside onWorkerStart or a request handler:
App::tick(5000, function() {
    // runs every 5 seconds per worker
});

App::after(1000, function() {
    // runs once after 1 second
});
```
Must be called inside a coroutine context (onWorkerStart or request handler).

### Shared Memory
```php
// Create BEFORE $app->run() (shared across all workers via fork)
// Columns are an ASSOCIATIVE map: 'name' => [Store::TYPE_*, size]
Store::make('cache', 1024, [
    'key'   => [Store::TYPE_STRING, 64],
    'value' => [Store::TYPE_STRING, 256],
]);
Store::set('cache', 'item1', ['key' => 'greeting', 'value' => 'hello']);
$row = Store::get('cache', 'item1');

// Atomic counter (lock-free)
$hits = new Counter('hits');
$hits->increment();
```

Store and Counter are **backend-agnostic** (default = `Table`/`Atomic`, single-node in-memory). Flip to Redis/Tiered for cross-node state — set BEFORE `$app->run()`, every existing call works unchanged:
```php
Store::defaultBackend(Store::BACKEND_REDIS, 'redis://cache:6379/0');
Store::defaultBackend(Store::BACKEND_TIERED, ['url' => 'redis://cache:6379/0', 'l1_ttl' => 5]);
Counter::defaultBackend(Counter::BACKEND_REDIS);
```

For a read-through cache (compute-on-miss, stampede-gated, null-safe):
```php
Cache::init();                                              // before $app->run()
$user = Cache::getOrCompute('user:42', fn() => User::find(42), ttl: 300);
```

## Lifecycle modes — `App::mode()` (v0.3.x)

Pick a lifecycle preset with `App::mode()` **before `App::init()`**:

| Mode | Use for |
|------|---------|
| `App::mode('coroutine')` | **Default for new apps.** Per-coroutine `RequestContext` isolation + HOOK_ALL non-blocking I/O. Recommended. |
| `App::mode('coroutine-legacy')` | Traditional request-style PHP (the PHP-FPM "fresh state per request" model) run **concurrently** under coroutines. Every request-state primitive — the 7 superglobals, `$GLOBALS`/`global $x`, class & function `static`, `define()`, `ini_set`, `putenv` — is isolated per coroutine. **Requires ext-zealphp.** |
| `App::mode('legacy-cgi')` | Unmodified WordPress / Drupal — one CGI subprocess per request (mod_php-style global-scope isolation). |
| `App::mode('mixed')` | Symfony / Laravel — real `$_SESSION`, no per-include CGI fork cost, sequential per worker. |

`App::isolation()` exposes the same coupling directly; the standalone setters
(`App::coroutineGlobalsIsolation()`, `App::coroutineStaticsIsolation()`,
`App::silentRedeclare()`, `App::includeIsolation()`, `App::defineIsolation()`,
`App::keepGlobals()`) give per-knob control.

### ext-zealphp — the per-coroutine isolation engine

`coroutine-legacy` needs **ext-zealphp**, ZealPHP's purpose-built C extension
(replaces the uopz dependency as of v0.3.0). It dlsym's OpenSwoole's
`on_yield`/`on_resume`/`on_close` scheduler callbacks and snapshots/restores
per-coroutine state across each yield. Install:

```bash
pie install sibidharan/ext-zealphp
php -m | grep zealphp          # verify it's loaded (NTS-only)
```

When ext-zealphp is absent, `App::mode('coroutine-legacy')` refuses to boot
(the superglobals-under-coroutines combo would race across requests).

> **PHP 8.4 / 8.5 — which legacy apps run *concurrently* in coroutine-legacy:**
> the `require_once`'d inherited-class heap-corruption crash is **FIXED in
> ext-zealphp 0.3.24+** and per-request state resets (0.3.25+) close the
> remaining legacy 500s, so **Composer/autoloader apps run concurrently** in
> coroutine-legacy — verified on a 12-app sweep (Adminer, FreshRSS, YOURLS,
> Grav, phpBB, MyBB, Piwigo, Drupal). The one gotcha for these is benign:
> **cold-concurrent-autoload** — a class first compiled while several coroutines
> overlap can transiently raise "class not found" on the very first burst (a PHP
> early-binding race, ASAN/Valgrind clean — not a memory bug). **Fix:** warm
> hot-path classes at boot with `App::preloadClassmap()` /
> `App::preloadClasses()` / `App::preloadDir()` (below). State isolation is
> solid on 8.3, 8.4 and 8.5.
>
> **Classic unmodified WordPress is the exception — use `legacy-cgi`.** It's
> pure `require_once` with no autoloader, so it can't be preloaded. It *works*
> in coroutine-legacy only run **sequentially** (a correctness benchmark — public
> site + login + comment writes, ASAN-clean); **true concurrent** WordPress still
> crashes (a cold-boot `mysqlnd`/`libtasn1` teardown layer) and full wp-admin
> still wants `legacy-cgi`. **For real unmodified WordPress, `legacy-cgi` (Mode 1)
> is the recommended mode** — process-isolated, fully concurrent-safe.

### Preloading hot-path classes (coroutine-legacy)

Warm your app's classes at boot so a first concurrent burst can't race their
compilation. Call **before `App::init()`** — these run in the master
(single-coroutine), then COW-fork into every worker:

```php
App::preloadClassmap();                       // whole Composer classmap (needs `composer dump-autoload --optimize`)
App::preloadDir(__DIR__ . '/src');            // a PSR-4 source dir
App::preloadClasses(App\Home::class, App\Auth::class);  // specific hot controllers/services
```

Only needed in `coroutine-legacy` (the concurrent-compile mode). A pure
`require_once` app with no autoloader (classic unmodified WordPress) can't be
preloaded this way — run it in `legacy-cgi` (process-isolated, no race).

### Legacy app catch-all (pretty permalinks)

```php
App::mode('legacy-cgi');   // or 'coroutine-legacy' for concurrent legacy

$app->setFallback(function() {
    $g = G::instance();
    $g->server['PHP_SELF'] = '/index.php';
    $g->server['SCRIPT_NAME'] = '/index.php';
    $g->server['SCRIPT_FILENAME'] = App::$cwd . '/public/index.php';
    return App::include('/index.php');   // App::includeFile() is a deprecated alias
});
```

`App::include($publicPath)` runs PHP files rooted at `public/`. In `legacy-cgi`
mode it dispatches to a CGI subprocess for true global-scope isolation; in
coroutine modes it runs in-process. The file's return value flows through the
universal return contract (`return 404;` → status, `return [...]` → JSON, a
Generator → SSR stream).

### CGI dispatch modes — `App::cgiMode()`

When `App::include()` dispatches to a subprocess, `App::cgiMode()` (set before
`App::run()`) picks the strategy:
- **`'pool'` (default)** — warm pre-spawned worker pool, interpreter resident (~1–3 ms/req).
- **`'proc'`** — fresh `proc_open` subprocess per request (~30–50 ms cold start).
- **`'fork'`** — Apache-prefork: fresh child at true global scope (~1 ms; EXPERIMENTAL, needs `pcntl`+`posix`). Unmodified-WordPress correctness (no `Cannot redeclare class`) without the `proc` cost.
- **`'fcgi'`** — forward to an external FastCGI upstream (php-fpm, RoadRunner) via `App::fcgiAddress()`.

`App::mode('legacy-cgi')` defaults to `cgiMode('pool')` with `cgiPoolMaxRequests = 1` (a fresh subprocess per request).

### Per-route CGI backend — `backend:`

A single route can override the app-wide CGI mode for the file its handler `App::include()`s:
```php
App::cgiBackendAlias('wp-fork', 'fork');                          // register an alias once at boot
$app->route('/wordpress', backend: 'wp-fork', handler: fn() => App::include('/index.php'));
$app->route('/legacy',    backend: ['mode' => 'fcgi', 'address' => 'unix:/run/php-fpm.sock'],
    handler: fn() => App::include('/legacy/index.php'));
```
Accepted by all four registrars and `$app->group()`, as the `backend:` named arg or the `['backend' => …]` option key. **Boundary:** `backend:` is the CGI-isolation family only (`pool`/`proc`/`fork`/`fcgi`) — the process-wide lifecycle modes (`coroutine`/`coroutine-legacy`/`legacy-cgi`/`mixed`) are frozen at boot and **cannot** vary per route (passing one throws). To mix lifecycle modes, run separate processes per port behind a proxy.

## Key Classes

| Class | Purpose |
|-------|---------|
| `ZealPHP\App` | Framework core: routing, server lifecycle, `render()`, `include()`, `mode()` |
| `ZealPHP\App::mode(string)` | Lifecycle preset (set before `init()`): `'coroutine'`/`'mixed'`/`'legacy-cgi'`/`'coroutine-legacy'` |
| `ZealPHP\App::parallel()` / `parallelLimit()` | Fork-join concurrency: run tasks in parallel (or capped), wait for all |
| `ZealPHP\App::onSignal()` / `addProcess()` | Signal handlers + sidecar/background-worker processes (register before `run()`) |
| `ZealPHP\HTTP` | Outbound HTTP: `HTTP::get/post/put/delete()` → typed `HTTPResponse`; `HTTP::all()` fans out concurrently |
| `ZealPHP\G` | Per-request global state (`G::instance()`) |
| `ZealPHP\HTTP\Request` | Request wrapper |
| `ZealPHP\HTTP\Response` | Response wrapper: `stream()`, `sse()`, `redirect()`, `flush()` |
| `ZealPHP\Store` | Cross-worker shared memory (Table default; flip to Redis/Tiered via `Store::defaultBackend()`) |
| `ZealPHP\Counter` | Lock-free atomic counter (Atomic default; flip to Redis via `Counter::defaultBackend()`) |

## Coding Standards

### PHP Style
- Follow **PSR-2** (https://www.php-fig.org/psr/psr-2/) for all PHP code.
- Use `declare(strict_types=1)` in `src/` classes. Short array syntax, meaningful docblocks.

### Separation of Concerns

| Rule | Details |
|------|---------|
| No inline `<script>` in templates | All JS goes in `public/js/`. Templates are HTML-only. |
| No inline `style=` or `<style>` in templates | All CSS goes in `public/css/`. Use CSS classes. |
| No function definitions in templates | Extract helpers to `src/` classes (PSR-4 autoloaded). |
| No function definitions in API files | API files define one closure (`$get`, `$post`, etc.). Business logic goes in `src/` service classes. |
| No top-level `function` in `route/*.php` | Breaks dev hot-reload (`--dev`) — re-including the file fatals on redeclaration in coroutine mode. Extract helpers to `src/` classes. |
| `function_exists()` = wrong place | The function belongs in a class, autoloaded via Composer. |

### Architecture Rules
- **Business logic in `src/`** — proper OOP classes with constructors, autoloaded via Composer PSR-4.
- **API endpoints in `api/`** (ZealAPI) — file-based REST routing. Use `route/` only for path-param routes, WebSocket, or Store tables.
- **Thin route handlers** — 1–5 lines that call a `src/` service class. If a handler exceeds ~10 lines, extract to a service.
- **`app.php` stays thin** — bootstrap only: middleware registration, `$app->run()`.

### htmx Convention
Set `hx-boost="true"` on `<body>` for automatic AJAX navigation with progressive enhancement. Prefer `hx-get`/`hx-post`/`hx-target`/`hx-swap` over custom `fetch()`. Use WebSocket (`App::ws()`) or SSE (`$response->sse()`) for server-push.

## Documentation

Full docs with live demos: https://php.zeal.ninja
API reference: https://deepwiki.com/sibidharan/zealphp
