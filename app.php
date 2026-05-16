<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use ZealPHP\App;
use ZealPHP\Middleware\CorsMiddleware;
use ZealPHP\Middleware\ETagMiddleware;
use ZealPHP\Middleware\RangeMiddleware;
use ZealPHP\Middleware\SessionStartMiddleware;

// Coroutine mode — per-request state isolated via Coroutine::getContext().
// Recommended default for new projects (thousands of concurrent requests per worker).
// Flip to App::superglobals(true) only for migration scenarios where unmodified
// legacy code needs access to $_GET / $_POST / $_SESSION as PHP-FPM expects.
App::superglobals(false);

// Bind to 0.0.0.0 for container / reverse-proxy deployments. For laptop dev,
// use '127.0.0.1' to keep the port off the network until you're ready.
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
// Add more in the `route/` directory (auto-loaded at startup) or directly here.

$app->route('/hello/{name}', function ($name) {
    App::render('/hello', ['name' => $name]);
});

$app->route('/hello', function () {
    App::render('check');
});

$app->run();
