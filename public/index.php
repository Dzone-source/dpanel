<?php

/**
 * DPanel Public Entrance File
 *
 * @license MIT
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/.config.php';
require_once __DIR__ . '/../config/appprofile.php';
require_once __DIR__ . '/../app/predefine.php';

use App\Middleware\ErrorHandler;
use App\Services\Boot;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Slim\Factory\AppFactory;
use Slim\Http\Factory\DecoratedResponseFactory;

Boot::setTime();
Boot::normalizeClientIp();
Boot::bootSentry();
Boot::bootDb();

$guzzle_factory = new HttpFactory();
$response_factory = new DecoratedResponseFactory($guzzle_factory, $guzzle_factory);
$app = AppFactory::create($response_factory);

$app->add(new ErrorHandler());

$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

$request = ServerRequest::fromGlobals();
$request = new Slim\Http\ServerRequest($request);

$app->run($request);
