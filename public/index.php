<?php

define('SYNTAXCORE_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. If vendor autoload has not been generated yet,
| a fallback PSR-4 autoloader is provided to get started instantly.
|
*/

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) {
        $prefixes = [
            'App\\' => __DIR__ . '/../app/',
            'Core\\' => __DIR__ . '/../core/',
        ];

        foreach ($prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

/** @var \Core\Application\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(\Core\Application\Kernel::class);
$request = \Core\Http\Request::capture();

$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);
