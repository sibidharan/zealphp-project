<?php
use ZealPHP\App;

App::render('_master', [
    'title'       => 'Home',
    'page'        => 'home',
    'active'      => 'home',
    'description' => 'Your ZealPHP app — coroutine PHP on OpenSwoole, with an htmx-native rendering model.',
]);
