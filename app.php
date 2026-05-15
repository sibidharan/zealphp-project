<?php

require 'vendor/autoload.php';

use ZealPHP\App;

// Coroutine mode — per-request state isolated via Coroutine::getContext().
// Recommended default for new projects (thousands of concurrent requests per worker).
// Flip to App::superglobals(true) only for migration scenarios where unmodified
// legacy code needs access to $_GET / $_POST / $_SESSION as PHP-FPM expects.
App::superglobals(false);

$app = App::init('0.0.0.0', 8080);

# Define routes here

$app->route('/hello/{name}', function($name){
    App::render('/hello', ['name' => $name]);
});

$app->route('/hello', function(){
    App::render('check');
});

# Additional routes can be added in `route` directory also

$app->run();