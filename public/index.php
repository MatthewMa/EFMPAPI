<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Middleware\HttpBasicAuthentication\PdoAuthenticator;
use \Tuupola\Middleware\Cors;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
require __DIR__ . '/../vendor/autoload.php';
session_start();
// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
// add env file
$dotenv = Dotenv\Dotenv::create(__DIR__ . '/..');
$dotenv->load();
$app = new \Slim\App($settings);

$container = $app->getContainer();
// PDO database library
$container['db'] = function ($c) {
    try {
        $settings = $c->get('settings')['db'];
        $pdo = new PDO("pgsql:host=" . $settings['host'] . ";dbname=" . $settings['dbname'],
            $settings['user'], $settings['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }
    catch(PDOException $e)
    {
        echo $e->getMessage();
    }
};

// Add CORS
$options = [
    "origin" => "*",
    "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    "headers.allow" => ["Content-type","Authorization"],
    "headers.expose" => [],
    "credentials" => false,
    "cache" => 0,
];
$app->add(new Tuupola\Middleware\Cors($options));
// Add user account
$app->add(new Slim\Middleware\HttpBasicAuthentication([
    "path" => ["/v1/auth/login"],
    "realm" => "Protected",
    "secure" => false,
    "users" => [
        "mma" => getenv('MMA_PASSWORD'),
    ],
    "environment" => "REDIRECT_HTTP_AUTHORIZATION",
    "authenticator" => new PdoAuthenticator([
        "pdo" => $container['db'],
        "table" => "accounts",
        "user" => "accountUsername",
        "hash" => "accountPassword"
    ]),
    "error" => function ($request, $response, $arguments) {
        $data = [];
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    }
]));

// Add JWT authorization
$app->add(new \Slim\Middleware\JwtAuthentication([
    "path" => [
        "/v1/user",
        "v1/site",
        "/v1/users",
        "/v1/sites"],
    "secret" => getenv("JWT_SECRET"),
    "secure" => false,
    "attribute" => "decoded_token_data",
    "algorithm" => ["HS256"],
    "error" => function ($request, $response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

// Add authorization function
/*$app->add(function($request,$response,$next){
    $authorization_header = $request->getHeader("Authorization");

    if(empty($authorization_header) || ($authorization_header[0]!="mma")){ //you can check the header for a certain string or you can check if what is in the Authorization header exists in your database

        return $response->withStatus(400);
    }

    $response = $next($request,$response);

    return $response;

});*/

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';
// Register middleware
require __DIR__ . '/../src/middleware.php';
// Register routes
require __DIR__ . '/../src/routes.php';
// Run app
$app->run();