<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use ZealPHP\App;
use ZealPHP\Middleware\CorsMiddleware;
use ZealPHP\Middleware\ETagMiddleware;
use ZealPHP\Middleware\RangeMiddleware;
use ZealPHP\Middleware\SessionStartMiddleware;

// Recommended default for new projects. Each request gets its own isolated
// state so many requests can run at the same time safely. Leave this as-is
// unless you're migrating a legacy PHP app — see https://php.zeal.ninja/learn/mental-model
App::superglobals(false);

// Bind to 0.0.0.0 for container / reverse-proxy deployments. For laptop dev,
// use '127.0.0.1' to keep the port off the network until you're ready.
//
// Document root — the folder every implicit route + static asset resolves
// against (public/about.php → /about). Defaults to 'public/'; uncomment to
// serve a different directory. Like all App config, set it BEFORE App::init().
// App::documentRoot('public');
$app = App::init('0.0.0.0', 8080);

// ─── Middleware stack ───────────────────────────────────────────────
//
// IMPORTANT — CorsMiddleware origin policy:
//   - The default `new CorsMiddleware()` falls back to '*' (with a one-time
//     warning) which is INSECURE for any API serving credentials or
//     user-scoped data. Lock it down for production.
//   - Pass explicit origins:
//         new CorsMiddleware(origins: ['https://yourapp.com'])
//   - Or override without code change:
//         ZEALPHP_CORS_ORIGINS="https://yourapp.com,https://admin.yourapp.com" php app.php

$app->addMiddleware(new CorsMiddleware(
    origins:     ['https://yourapp.com'],   // <-- replace with your real origin(s)
    methods:     ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    headers:     ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
    credentials: true,
));
$app->addMiddleware(new ETagMiddleware());
$app->addMiddleware(new RangeMiddleware());
$app->addMiddleware(new SessionStartMiddleware());

// ─── Routes ─────────────────────────────────────────────────────────
// The welcome page (public/index.php) and the htmx playground
// (public/playground.php + route/playground.php) ship as a starting point —
// open them to see how it all fits together, then replace with your own.
//
// You can register routes here, or drop files in `route/` (auto-loaded at
// startup). Handlers inject by name: `$req`/`$res` (or `$request`/`$response`),
// `$app`, and any `{param}` from the path.

$app->route('/hello/{name}', fn ($name, $req) =>
    ['hello' => $name, 'from' => 'ZealPHP', 'htmx' => $req->isHtmx()]);

$app->run();
