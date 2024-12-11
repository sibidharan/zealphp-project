<?php

use ZealPHP\App;

$app = App::instance(); 

$app->route('/info', function(){
    phpinfo();
});