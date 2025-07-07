<?php
// admin/index.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Middleware\BodyParsingMiddleware;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use App\Admin\Admin;
use App\Mail\MailService;
use App\Database\UserDb;
use App\Database\ConfigDb;
use App\Config\ConfigService;
use App\Middleware\AdminAuthMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

$stripFolders = ['admin', 'api']; // Folders you want to strip from the end of the path
$pathParts = explode('/', trim(dirname($_SERVER['SCRIPT_NAME']), '/'));
if (in_array(end($pathParts), $stripFolders)) {
    array_pop($pathParts); // remove the last segment
}
$basePath = '/' . implode('/', $pathParts);

// Setup DI objects

$userDb      = new UserDb(__DIR__ . '/../data/users.json');
$config      = new ConfigService(__DIR__ . '/../data/config.json');
#$db          = new JsonDb(__DIR__ . '/../users.json');

$csrfManager = new CsrfTokenManager();
$mailer      = new MailService();
$adminApp    = new Admin($userDb, $config, $csrfManager, $mailer, $basePath);
$adminAuthMiddleware = new AdminAuthMiddleware($userDb, $config);

// Create App
AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);
$app = AppFactory::create();
$app->setBasePath($basePath);
$app->addBodyParsingMiddleware();  //this fixes getParsedBody() when Content-Type: application/json

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add error middleware (optional, for debugging)
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// JSON Response helper (no external dependencies)
function jsonResponse(Response $response, $data, int $status = 200): Response
{
    $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

// CSRF protection middleware for POST, PUT, DELETE
$app->add(function (Request $request, $handler) use ($adminApp) {
    $method = $request->getMethod();

    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $contentType = $request->getHeaderLine('Content-Type');

        // https://github.com/slimphp/Slim/issues/2829
        if (strstr($contentType, 'application/json')) {
          $contents = json_decode(file_get_contents('php://input'), true);
          if (json_last_error() === JSON_ERROR_NONE) {
            $request = $request->withParsedBody($contents);
          }
        }

        $parsedBody = $request->getParsedBody() ?? [];
        #error_log("=== REQUEST DEBUG ===");
        #error_log("Method: " . $request->getMethod());
        #error_log("URI: " . (string) $request->getUri());
        #error_log("Headers: " . json_encode($request->getHeaders()));
        #error_log("Raw body: " . (string) $request->getBody());
        #error_log("Parsed body: " . json_encode($request->getParsedBody()));
        #error_log("Query params: " . json_encode($request->getQueryParams()));
        $token = $parsedBody['_csrf_token'] ?? '';

        if (!$adminApp->validateCsrfToken($token)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write('Invalid CSRF token.');
            return $response->withStatus(400);
        }
    }

    return $handler->handle($request);
});

// Routes
// basepath is defined with $app->setBasePath()

$app->get('/admin/login[/]', function ($request, $response) use ($adminApp) {
    return $adminApp->showLoginForm($request, $response);
});

$app->post('/admin/login[/]', function ($request, $response) use ($adminApp) {
    return $adminApp->processLogin($request, $response);
});

$app->get('/admin/logout[/]', function (Request $request, Response $response) use ($app) {
    return $adminApp->logout($request, $response);
});

$app->post('/admin/send_email_code[/]', function ($request, $response) use ($adminApp) {
    return $adminApp->sendEmailCode($request, $response);
});

$app->group('/admin', function (RouteCollectorProxy $group) use ($adminApp)  {
  $group->get('/install[/]', function ($request, $response) use ($adminApp) {
      return $adminApp->install($request, $response);
  });
  $group->get('[/]', function ($request, $response) use ($adminApp) {
      return $adminApp->showList($request, $response);
  });
  $group->get('/qr[/]', function ($request, $response) use ($adminApp) {
      return $adminApp->showQrCode($request, $response);
  });

  // get json for datatables
  $group->get('/users[/]', function ($request, $response) use ($adminApp) {
      return $adminApp->getDatatable($request, $response);
  });

  $group->post('/user/delete[/]', function (Request $request, Response $response) use ($adminApp) {
      return $adminApp->deleteUser($request, $response);
  });
  $group->post('/user/reset[/]', function (Request $request, Response $response) use ($adminApp) {
      return $adminApp->resetUser($request, $response);
  });

  $group->get('/user/add[/]', function ($request, $response) use ($adminApp) {
      return $adminApp->addUserForm($request, $response);
  });
  $group->post('/user/add[/]', function (Request $request, Response $response) use ($adminApp) {
      return $adminApp->addUser($request, $response);
  });
  $group->get('/user/edit[/]', function ($request, $response) use ($adminApp) {
      return $adminApp->editUserForm($request, $response);
  });
  $group->post('/user/edit[/]', function (Request $request, Response $response) use ($adminApp) {
      return $adminApp->editUser($request, $response);
  });
  $group->post('/user/toggle-admin', function (Request $request, Response $response) use ($adminApp) {
      return $adminApp->toggleField($request, $response, 'isadmin', false);
  });
  $group->post('/user/toggle-active', function (Request $request, Response $response) use ($adminApp) {
      return $adminApp->toggleField($request, $response, 'isactive', true);
  });
})->add($adminAuthMiddleware);




// Run app
$app->run();

