<?php

namespace App\Admin;

#use App\TOTP;
use Endroid\QrCode\Builder\Builder;
use Smarty;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface      as Response;

use App\Providers\ProviderManager;
use App\Providers\TOTPProvider;
use App\Providers\EmailCodeProvider;

class Admin
{
    private $userdb;
    private $config;
    private $csrfManager;
    private $mailer;
    
    public function __construct($userdb, $config, $csrfManager, $mailer, $basePath)
    {
        $this->userdb      = $userdb;
        $this->config      = $config;
        $this->csrfManager = $csrfManager;
        $this->mailer      = $mailer;
        $this->basePath    = $basePath;

        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir(__DIR__ . '/../templates');
        $this->smarty->setCompileDir(__DIR__ . '/../templates_c');
        $this->smarty->assign('basePath', $basePath);

        $this->providerManager = new ProviderManager();
        $this->providerManager->registerProvider(new TOTPProvider($this->userdb, $this->config));
        $this->providerManager->registerProvider(new EmailCodeProvider($this->userdb, $this->config));
    }

    // Generate new CSRF token string for response or forms
    public function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $this->csrfManager->getToken(
                    $this->config->get('csrf')->get('token_id', 'admin_action')
               )->getValue();
    }

    public function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $this->csrfManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken(
                  $this->config->get('csrf')->get('token_id', 'admin_action'), $token));
    }

    public function getUsers(): array
    {
        return $this->userdb->getAllUsers();
    }

    public function editUser(Request $request, Response $response): Response
    {
        $data      = $request->getParsedBody() ?? [];

        $name      = trim($data['name'] ?? '');
        $email     = trim($data['email'] ?? '');
        $isadmin   = trim($data['is_admin'] ?? 0);
        $csrfToken = $data['_csrf_token'] ?? '';

        if (!$this->validateCsrfToken($csrfToken)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid CSRF token.'
            ]));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }

        $user = $this->userdb->getUserByEmail($email);
        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'User not found.'
            ]));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }

        $errors = [];

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }

        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($errors) {
            $response->getBody()->write(json_encode(['errors' => $errors]));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }

        $updateData = [
            'name'  => $data['name'],
            'isadmin' => $isadmin
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $this->userdb->updateUser($user['email'], $updateData);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User updated successfully.'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function editUserForm($request, $response)
    {
        $email     = trim($_GET['email'] ?? '');
        // Generate CSRF token using your existing method
        $csrfTokenValue = $this->generateCsrfToken();
        if (!$this->validateCsrfToken($csrfTokenValue)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid CSRF token.'
            ]));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }

        $user = $this->userdb->getUserByEmail($email);
        $user['isactive'] = $user['isactive'] ?? true;
        $user['isadmin'] = !empty($user['isadmin']);

        $this->smarty->assign('csrf_token', $csrfTokenValue);
        $this->smarty->assign('name',  $user['name']);
        $this->smarty->assign('email', $user['email']);
        $this->smarty->assign('isadmin', $user['isadmin']);
        $this->smarty->assign('isactive', $user['isactive']);

        $html = $this->smarty->fetch('edituserform.tpl');
        $response->getBody()->write($html);

        return $response;
    }

    public function addUser($request, $response): Response
    {
        $data      = $request->getParsedBody() ?? [];

        $errors    = [];
        $name      = trim($data['name'] ?? '');
        $email     = trim($data['email'] ?? '');
        $csrfToken = $data['_csrf_token'] ?? '';

        if (!$this->validateCsrfToken($csrfToken)) {
            $errors[] = 'Invalid CSRF token.';
            return ['errors' => $errors];
        }

        if (!$name || !$email) {
            $errors[] = 'Name and Email are required.';
            return ['errors' => $errors];
        }

        if ($this->userdb->getUserByEmail($email)) {
            $errors[] = 'Email already exists.';
            return ['errors' => $errors];
        }

        $totp = \OTPHP\TOTP::create();
        $totp->setIssuer($this->config->get('totp')->get('issuer', 'CloudAware'));
        $totp->setLabel($email);
        $secret = $totp->getSecret();

        $this->userdb->addUser($name, $email, $secret);

        // Send QR code email
        $qr = Builder::create()->data($totp->getProvisioningUri())->build();
        $this->mailer->sendQrCodeEmail($email, "Your 2FA QR Code", "Scan this code to configure your 2FA.", $qr->getString());
        $response->getBody()->write(json_encode(['success' => true]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function addUserForm($request, $response)
    {
        // Generate CSRF token using your existing method
        $csrfTokenValue = $this->generateCsrfToken();
        $this->smarty->assign('csrf_token', $csrfTokenValue);
        $html = $this->smarty->fetch('form.tpl');
        $response->getBody()->write($html);

        return $response;
    }

    public function showList($request, $response)
    {

        // Generate CSRF token using your existing method
        $csrfTokenValue = $this->generateCsrfToken();
        $this->smarty->assign('csrf_token', $csrfTokenValue);

        $html = $this->smarty->fetch('list.tpl');
        $response->getBody()->write($html);

        return $response;
    }

    public function getDatatable(Request $request, Response $response): Response
    {
        $params = (array)$request->getParsedBody() + $request->getQueryParams();

        $draw   = intval($params['draw'] ?? 0);
        $start  = intval($params['start'] ?? 0);
        $length = intval($params['length'] ?? 10);
        $searchValue = $params['search']['value'] ?? '';

        $orderColumnIndex = intval($params['order'][0]['column'] ?? 0);
        $orderDir = $params['order'][0]['dir'] ?? 'asc';

        $columns = ['name', 'email', 'isadmin', 'isactive', 'actions'];
        $sortColumn = $columns[$orderColumnIndex] ?? 'name';

        $allUsers = $this->userdb->getAllUsers();

        // Filter
        $filtered = array_filter($allUsers, function ($user) use ($searchValue) {
            if ($searchValue === '') return true;
            return stripos($user['name'], $searchValue) !== false ||
                   stripos($user['email'], $searchValue) !== false;
        });

        // Sort
        usort($filtered, function ($a, $b) use ($sortColumn, $orderDir) {
            $aVal = $a[$sortColumn] ?? '';
            $bVal = $b[$sortColumn] ?? '';
            return $orderDir === 'asc'
                ? strcasecmp($aVal, $bVal)
                : strcasecmp($bVal, $aVal);
        });

        // Pagination slice
        $paginated = array_slice($filtered, $start, $length);

        $csrfTokenValue = $this->csrfManager->getToken(
                $this->config->get('csrf')->get('token_id', 'admin_action')
        )->getValue();

        // Format for DataTables
        $data = array_map(function ($user) use ($csrfTokenValue) {
            $emailEsc = htmlspecialchars($user['email']);
            $isAdmin = !empty($user['isadmin']); // Default to false if not set
            $isactive = $user['isactive'] ?? true;  // default to true if not set
            //$adminIcon = $isAdmin
            //    ? '<a href="#" class="toggler toggle-admin"><i class="bi bi-check-circle-fill text-success"></i></a>'
            //    : '<a href="#" class="toggler toggle-admin"><i class="bi bi-x-circle-fill text-danger"></i></a>';
            //$activeIcon = $isactive
            //    ? '<a href="#" class="toggler toggle-active"><i class="bi bi-check-circle-fill text-success"></i></a>'
            //    : '<a href="#" class="toggler toggle-active"><i class="bi bi-x-circle-fill text-danger"></i></a>';

            return [
                'name'     => htmlspecialchars($user['name']),
                'email'    => $emailEsc,
                'isadmin'  => $isAdmin,
                'isactive' => $isactive,
                'actions'  => <<<HTML
<form method="post" action="user/reset" style="display:inline">
    <input type="hidden" name="_csrf_token" value="{$csrfTokenValue}" />
    <input type="hidden" name="email" value="{$emailEsc}">
    <button class="btn btn-warning btn-sm">Reset 2FA</button>
</form>
<button class="btn btn-info btn-sm" 
    onclick="openModal('2FA QR Code', 'qr?email=' + encodeURIComponent('{$emailEsc}') + '&_csrf_token=' + encodeURIComponent('{$csrfTokenValue}'))">
  View QR
</button>
<button class="btn btn-info btn-sm edit-user">Edit</button>
<form method="post" action="user/delete" style="display:inline" onsubmit="return confirm('Delete this user?');">
    <input type="hidden" name="_csrf_token" value="{$csrfTokenValue}" />
    <input type="hidden" name="email" value="{$emailEsc}">
    <button class="btn btn-danger btn-sm">Delete</button>
</form>
HTML,
                'DT_RowAttr' => [
                    'data-email' => $emailEsc
                    //can add extra attributes here
                ]
            ];
        }, $paginated);

        $result = [
            'draw' => $draw,
            'recordsTotal' => count($allUsers),
            'recordsFiltered' => count($filtered),
            'data' => array_values($data)
        ];

        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function showQrCode($request, $response)
    {
        $params = $request->getQueryParams();
        $email = trim($params['email'] ?? '');

        if (!$email) {
            return $response->withStatus(400)->write('Email parameter is required.');
        }

        /** @var \App\Providers\TOTPProvider $totpProvider */
        $totpProvider = $this->providerManager->getProvider('totp');

        if (!$totpProvider) {
            return $response->withStatus(500)->write('TOTP provider not available.');
        }

        $base64 = $totpProvider->getQrCodeForUser($email);

        if (!$base64) {
            return $response->withStatus(404)->write('User not found or no QR available.');
        }

        $html = '<img src="data:image/png;base64,' . $base64 . '" class="img-fluid" alt="QR Code" />';
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function deleteUser(Request $request, Response $response): Response
    {
        $params = (array)$request->getParsedBody();
        $email = trim($params['email'] ?? '');

        $errors = [];

        if (!$email) {
            $errors[] = 'Email is required.';
        } else {
            $user = $this->userdb->getUserByEmail($email);
            if (!$user) {
                $errors[] = 'User not found.';
            } else {
                $this->userdb->deleteUserByEmail($email);
            }
        }

        if (!empty($errors)) {
            $payload = ['errors' => $errors];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Redirect to the user list page after deletion
        return $response->withHeader('Location', $this->basePath.'/admin')->withStatus(302);
    }

    public function resetUser(Request $request, Response $response): Response
    {
        $params = (array)$request->getParsedBody();
        $email = trim($params['email'] ?? '');

        $errors = [];

        if (!$email) {
            $errors[] = 'Email is required.';
        } else {
            $user = $this->userdb->getUserByEmail($email);
            if (!$user) {
                $errors[] = 'User not found.';
            } else {
                $totp = \OTPHP\TOTP::create();
                $totp->setIssuer($this->config->get('totp')->get('issuer', 'CloudAware'));
                $totp->setLabel($user['email']);
                $secret = $totp->getSecret();

                $this->userdb->updateSecret($user['email'], $secret);

                $qr = \Endroid\QrCode\Builder\Builder::create()->data($totp->getProvisioningUri())->build();
                $this->mailer->sendQrCodeEmail($user['email'], "2FA Reset", "Here is your new QR code", $qr->getString());
            }
        }

        if (!empty($errors)) {
            $payload = ['errors' => $errors];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Redirect to user list after reset
        return $response->withHeader('Location', $this->basePath.'/admin')->withStatus(302);
    }

    // Show login form
    public function sendEmailCode($request, $response)
    {
        $data = (array)$request->getParsedBody();
        $selectedProvider = $data['method'] ?? '';
        $csrfToken        = $data['_csrf_token'] ?? '';

        $provider = $this->providerManager->getProvider($selectedProvider);
        if (!$provider) {
            $error = "Invalid 2FA provider.";
        }
        [$success, $error] = $provider->sendEmailCode($data);

        $response->getBody()->write(json_encode([
        'success' => $success,
        'error'   => $error,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Show login form
    public function showLoginForm($request, $response)
    {
        $providers_json = [];

        foreach ($this->providerManager->getProviders() as $key => $provider) {
            $providers_json[$key] = [
                'label' => $provider->getLabel(),
                'icon' => $provider->getIcon(),
                'form' => $provider->getFormDefinition(true)
            ];
        }
        $this->smarty->assign('providers_json',   json_encode($providers_json));
        $this->smarty->assign('providers',        $this->providerManager->getProviders());
        $this->smarty->assign('formDefinitions',  $this->providerManager->getFormDefinitions());

        $csrfTokenValue = $this->generateCsrfToken();
        $this->smarty->assign('csrf_token', $csrfTokenValue);
        $html = $this->smarty->fetch('adminlogin.tpl');
        $response->getBody()->write($html);

        return $response;
    }

    // Process login form POST
    public function processLogin($request, $response)
    {
        $data = (array)$request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $selectedProvider = $data['method'] ?? '';
        $error            = null;

        $user = $this->userdb->getUserByEmail($email);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if (!$user || empty($user['password']) || !password_verify($hashed_password, $user['password'])) {
            // invalid login
            $error = 'Invalid email or password';
        }

        if (empty($user['isadmin']) || $user['isadmin'] !== true) {
            // Not admin user
            $error = 'Invalid email or password';
        }

        $provider = $this->providerManager->getProvider($selectedProvider);
        if (!$provider) {
            $error = "Invalid 2FA provider.";
        }
        [$success, $error] = $provider->verify($data);
        if (!$success) {
            $error = 'Invalid email or password';
        }

        if ($error) {
            $this->smarty->assign('error', $error);
            $html = $this->smarty->fetch('adminlogin.tpl');
            $response->getBody()->write($html);

            return $response;
        }

        // Login success: Set session authenticated
        session_start();
        $_SESSION['authenticated'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['last_activity']   = time();

        // Redirect to admin home
        return $response
            ->withStatus(302)
            ->withHeader('Location', $this->basePath.'/admin');
    }

    //public function toggleAdmin(Request $request, Response $response): Response
    //{
    //    $params = (array)$request->getParsedBody();
    //    $email = $params['email'] ?? '';
    //    $token = $params['_csrf_token'] ?? '';

    //    // CSRF token validation
    //    $csrfId = $this->config->get('csrf')->get('token_id', 'admin_action');
    //    if (!$this->csrfManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($csrfId, $token))) {
    //        return $response
    //            ->withStatus(400)
    //            ->withHeader('Content-Type', 'application/json')
    //            ->write(json_encode(['success' => false, 'error' => 'Invalid CSRF token.']));
    //    }

    //    // Prevent self-modification
    //    $currentUserEmail = $_SESSION['email'] ?? '';
    //    if (strcasecmp($email, $currentUserEmail) === 0) {
    //        $response->getBody()->write(json_encode(['success' => false, 'error' => 'You cannot change your own admin status.']));
    //        return $response
    //            ->withStatus(403)
    //            ->withHeader('Content-Type', 'application/json');
    //    }

    //    if (!$email || !$this->userdb->getUserByEmail($email)) {
    //        return $response
    //            ->withStatus(404)
    //            ->withHeader('Content-Type', 'application/json')
    //            ->write(json_encode(['success' => false, 'error' => 'User not found.']));
    //    }

    //    $result = $this->userdb->toggleAdminStatus($email);

    //    $response->getBody()->write(json_encode(['success' => $result]));
    //    return $response->withHeader('Content-Type', 'application/json');
    //}

    public function toggleField(Request $request, Response $response, $field, $default): Response
    {
        $params = (array)$request->getParsedBody();
        $email = $params['email'] ?? '';
        $token = $params['_csrf_token'] ?? '';

        // CSRF token validation
        $csrfId = $this->config->get('csrf')->get('token_id', 'admin_action');
        if (!$this->csrfManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($csrfId, $token))) {
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['success' => false, 'error' => 'Invalid CSRF token.']));
        }

        if (!$email || !$this->userdb->getUserByEmail($email)) {
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['success' => false, 'error' => 'User not found.']));
        }

        // Prevent self-modification
        $currentUserEmail = $_SESSION['email'] ?? '';
        if (strcasecmp($email, $currentUserEmail) === 0) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'You cannot change your own status.']));
            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        }

        $result = $this->userdb->toggleBool($email, $field, $default);

        $response->getBody()->write(json_encode(['success' => $result]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function logout(Request $request, Response $response): Response
    {
        // Clear session and destroy
        session_unset();
        session_destroy();

        // Redirect to login page
        return $response->withHeader('Location',  $this->basePath. '/admin/login')->withStatus(302);
    }

    public function install(Request $request, Response $response): Response
    {
        $results = [
            'data_writable'        => is_writable(__DIR__ .'/../data'),
            'vendor_exists'        => is_dir(__DIR__ . '/../vendor'),
            'templates_c_writable' => is_writable(__DIR__ . '/../templates_c'),
            'public_exists'        => is_dir(__DIR__ . '/../public'),
            'sessions_writable'    => is_writable(__DIR__ . '/../sessions'),
        ];

        $results['all_passed'] = !in_array(false, $results, true);

        $this->smarty->assign('results', $results);
        $html = $this->smarty->fetch('install.tpl');
        $response->getBody()->write($html);

        return $response;
    }
}

