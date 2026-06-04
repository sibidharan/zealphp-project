<?php
use ZealPHP\App;

App::render('_master', [
    'title'       => 'Playground',
    'page'        => 'playground',
    'active'      => 'playground',
    'description' => 'A live htmx playground — routing, the universal return contract, Store/Counter, HtmxResponse, and SSE, all served by this app.',
]);
