<?php

use ZealPHP\App;

App::render('_master', [
    'title' => 'ZealPHP Framework',
    'description' => 'A dynamic PHP framework built with OpenSwoole',
    'content' => 'ZealPHP is a dynamic PHP web development framework built with OpenSwoole. It leverages the power of coroutines for high-performance, asynchronous task execution, making it ideal for building scalable and efficient web applications. With a focus on simplicity and flexibility, ZealPHP provides a robust foundation for modern PHP development.'
]);