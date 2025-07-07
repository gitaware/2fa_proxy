<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\UserDb;
use App\Config\ConfigService;

use App\Providers\ProviderManager;
use App\Providers\TOTPProvider;
use App\Providers\EmailCodeProvider;

$userDb = new UserDb(__DIR__ . '/data/users.json');
$config = new ConfigService(__DIR__ . '/data/config.json');

$stripFolders = ['admin', 'api']; // Folders you want to strip from the end of the path
$pathParts = explode('/', trim(dirname($_SERVER['SCRIPT_NAME']), '/'));
if (in_array(end($pathParts), $stripFolders)) {
    array_pop($pathParts); // remove the last segment
}
$basePath = '/' . implode('/', $pathParts);

// Load Smarty
$smarty = new Smarty();
$smarty->setTemplateDir(__DIR__ . '/templates');
$smarty->setCompileDir(__DIR__ . '/templates_c');
$smarty->assign('basePath', $basePath);

$providerManager = new ProviderManager();
$providerManager->registerProvider(new TOTPProvider($userDb, $config));
$providerManager->registerProvider(new EmailCodeProvider($userDb, $config));

$selectedMethod = $_POST['method'] ?? null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle AJAX request to send code only
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Read raw JSON input if AJAX
    if ($isAjax) {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        if (($input['action'] ?? '') === 'send_email_code' && isset($input['email'], $input['method'])) {
            $email = trim($input['email']);
            $method = $input['method'];

            $provider = $providerManager->getProvider($method);

            header('Content-Type: application/json');

            if (!$provider || $method !== 'emailcode') {
                echo json_encode(['success' => false, 'error' => 'Invalid 2FA method.']);
                exit;
            }

            // Send code logic only
            [$success, $message] = $provider->verify(['email' => $email, 'code' => '']);
            // $success is false because code was sent, $message is notification

            echo json_encode(['success' => !$success, 'error' => $success ? null : $message]);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedMethod) {
    $provider = $providerManager->getProvider($selectedMethod);

    if (!$provider) {
        $error = "Invalid 2FA method.";
    } else {
        [$success, $error] = $provider->verify($_POST);

        if ($success) {
            $_SESSION['2fa_passed'] = true;
            $_SESSION['user_email'] = $_POST['email'] ?? '';
            $_SESSION['last_activity'] = time();
            header("Location: /application");
            exit;
        }
    }
}

$providers_json = [];

foreach ($providerManager->getProviders() as $key => $provider) {
    $providers_json[$key] = [
        'label' => $provider->getLabel(),
        'icon' => $provider->getIcon(),
        'form' => $provider->getFormDefinition()
    ];
}
$smarty->assign('providers_json', json_encode($providers_json));


$smarty->assign('error',           $error);
$smarty->assign('providers',       $providerManager->getProviders());
$smarty->assign('formDefinitions', $providerManager->getFormDefinitions());
$smarty->display('login.tpl');

