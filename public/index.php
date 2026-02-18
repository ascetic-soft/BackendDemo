<?php

declare(strict_types=1);

use App\Kernel;
use AsceticSoft\Psr7\ServerRequestCreator;

require_once __DIR__ . '/../vendor/autoload.php';

$kernel = new Kernel(projectDir: \dirname(__DIR__));
$container = $kernel->boot();

$router = $kernel->getRouter();

$request = ServerRequestCreator::fromGlobals();

// Handle the request
$response = $router->handle($request);

// Emit the response
\http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        \header(\sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody();
