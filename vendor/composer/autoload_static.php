<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9994867abbda5e5dcb5367a4499de518
{
    public static $files = array (
        '0f5c2e42cd57cee173a4e5751046ee6b' => __DIR__ . '/..' . '/openswoole/core/src/Coroutine/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'Z' => 
        array (
            'ZealPHP\\' => 8,
        ),
        'S' => 
        array (
            'Sibidharan\\ZealphpProject\\' => 26,
        ),
        'P' => 
        array (
            'Psr\\Http\\Server\\' => 16,
            'Psr\\Http\\Message\\' => 17,
        ),
        'O' => 
        array (
            'OpenSwoole\\Core\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ZealPHP\\' => 
        array (
            0 => __DIR__ . '/..' . '/sibidharan/zealphp/src',
        ),
        'Sibidharan\\ZealphpProject\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Psr\\Http\\Server\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-server-handler/src',
            1 => __DIR__ . '/..' . '/psr/http-server-middleware/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'OpenSwoole\\Core\\' => 
        array (
            0 => __DIR__ . '/..' . '/openswoole/core/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9994867abbda5e5dcb5367a4499de518::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9994867abbda5e5dcb5367a4499de518::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9994867abbda5e5dcb5367a4499de518::$classMap;

        }, null, ClassLoader::class);
    }
}