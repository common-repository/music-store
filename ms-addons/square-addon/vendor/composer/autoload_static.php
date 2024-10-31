<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdacd8b26930a9420d0b60002218b2efb
{
    public static $prefixLengthsPsr4 = array (
        'a' => 
        array (
            'apimatic\\jsonmapper\\' => 20,
        ),
        'U' => 
        array (
            'Unirest\\' => 8,
        ),
        'S' => 
        array (
            'Square\\' => 7,
        ),
        'C' => 
        array (
            'Core\\' => 5,
            'CoreInterfaces\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'apimatic\\jsonmapper\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/jsonmapper/src',
        ),
        'Unirest\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/unirest-php/src',
        ),
        'Square\\' => 
        array (
            0 => __DIR__ . '/..' . '/square/square/src',
        ),
        'Core\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/core/src',
        ),
        'CoreInterfaces\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/core-interfaces/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Rs\\Json' => 
            array (
                0 => __DIR__ . '/..' . '/php-jsonpointer/php-jsonpointer/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitdacd8b26930a9420d0b60002218b2efb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitdacd8b26930a9420d0b60002218b2efb::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitdacd8b26930a9420d0b60002218b2efb::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitdacd8b26930a9420d0b60002218b2efb::$classMap;

        }, null, ClassLoader::class);
    }
}
