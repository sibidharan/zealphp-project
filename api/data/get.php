<?php

use ZealPHP\App;

$get = function() {
    $$data = [
        'username' => 'John Doe',
        'email' => 'john@doe.com',
        'phone' => '1234567890'
    ];
    
    $this->response($this->json($data));
};