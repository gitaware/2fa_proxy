<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use App\Database\UserDb;
use App\Config\ConfigService;
use Slim\Psr7\Response;

class AdminAuthMiddleware implements MiddlewareInterface
{
    private UserDb $userdb;
    private ConfigDb $configdb;

    public function __construct(UserDb $userdb, ConfigService $config) {
        $this->userdb = $userdb;
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['authenticated']) || empty($_SESSION['email'])) {
            return $this->unauthorizedRedirect();
        }

        $user = $this->userdb->getUserByEmail($_SESSION['email']);
        if (!$user || empty($user['isadmin']) || $user['isadmin'] !== true) {
            return $this->unauthorizedRedirect();
        }

        return $handler->handle($request);
    }

    private function unauthorizedRedirect(): ResponseInterface {
        $response = new Response();
        return $response
            ->withStatus(302)
            ->withHeader('Location', '/2fa/admin/login');
    }
}
