# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

This is a **ZealPHP application** — a PHP web app built on the ZealPHP framework (OpenSwoole-based async PHP).

## Commands

```bash
# Install dependencies
composer install

# Start the dev server on :8080
php app.php

# Start on a specific port, daemonized
php app.php start -p 9000 -d

# Stop the server
php app.php stop

# Check if running
php app.php status

# Show all CLI options
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
Handler arguments are injected by name:
- `$request` → `ZealPHP\HTTP\Request`
- `$response` → `ZealPHP\HTTP\Response`
- `{param}` names → matched URL segments
- Any name with a default → PHP default value

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

### Return value conventions
```php
return 404;                    // int → HTTP status code
return ['id' => 42];           // array → JSON
return '<h1>Hello</h1>';       // string → HTML body
return (function() { yield..; })(); // Generator → SSR streaming
echo "Hello";                  // void+echo → output buffering
```

### Implicit Routes
Files in `public/` are served automatically:
- `public/index.php` → `/`
- `public/about.php` → `/about`
- `public/admin/index.php` → `/admin/`
- Static files (CSS, JS, images) served by OpenSwoole directly

### File-based API
Files in `api/` become REST endpoints:
- `api/users/get.php` → `GET /api/users` (must define `$get = function(...)`)
- `api/users/post.php` → `POST /api/users`

### Middleware
```php
$app->addMiddleware(new CorsMiddleware(['*']));
$app->addMiddleware(new ETagMiddleware());
// Last-added runs first (outermost)
```

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
Store::make('cache', 1024, [
    ['key',   Store::TYPE_STRING, 64],
    ['value', Store::TYPE_STRING, 256],
]);
Store::set('cache', 'item1', ['key' => 'greeting', 'value' => 'hello']);
$row = Store::get('cache', 'item1');

// Atomic counter (lock-free)
$hits = new Counter('hits');
$hits->increment();
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

> **Known limitation (PHP 8.4 / 8.5):** coroutine-legacy *code* isolation
> (silent function/class redeclare + `require_once` re-execution) has a
> pre-existing heap-corruption race under heavy *concurrent class autoloading*
> on PHP 8.4/8.5 (tracked). It is **not** a state leak — *state* isolation is
> solid on 8.3, 8.4 and 8.5. For affected apps on 8.4/8.5, use
> `App::mode('legacy-cgi')` or run on PHP 8.3.

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

## Key Classes

| Class | Purpose |
|-------|---------|
| `ZealPHP\App` | Framework core: routing, server lifecycle, `render()`, `include()`, `mode()` |
| `ZealPHP\G` | Per-request global state (`G::instance()`) |
| `ZealPHP\HTTP\Request` | Request wrapper |
| `ZealPHP\HTTP\Response` | Response wrapper: `stream()`, `sse()`, `redirect()`, `flush()` |
| `ZealPHP\Store` | Cross-worker shared memory (OpenSwoole\Table) |
| `ZealPHP\Counter` | Lock-free atomic counter |

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
