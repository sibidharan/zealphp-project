<?php

require 'vendor/autoload.php';

use ZealPHP\App;

$app = App::init('0.0.0.0', 8080);

# Define routes here

$app->route('/hello/{name}', function($name){
    echo "Hello, $name!";
});

# Additional routes can be added in `route` directory also

$app->run();