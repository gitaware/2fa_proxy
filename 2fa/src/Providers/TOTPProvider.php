<?php
namespace App\Providers;

use App\TOTP;

class TOTPProvider implements ProviderInterface {
    private $db;

    public function __construct($db, $config) {
        $this->db     = $db;
        $this->config = $config;
    }

    public function getId(): string {
        return 'totp';
    }

    public function getLabel(): string {
        return 'Authenticator App (TOTP)';
    }

    public function getIcon(): string {
        return 'bi-shield-lock-fill';
    }

    public function getFormDefinition($adminform=false): array {
        if (!$adminform) {
            return [
                'fields' => [
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'required' => true,
                        'autocomplete' => 'email'
                    ],
                    [
                        'type' => 'text',
                        'name' => 'code',
                        'label' => 'Authenticator Code',
                        'required' => true,
                        'autocomplete' => 'off'
                    ],
                ],
            ];
        } else {
            return [
                'fields' => [
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'required' => true,
                        'autocomplete' => 'email'
                    ],
                    [
                        'type' => 'password',
                        'name' => 'password',
                        'label' => 'Password',
                        'required' => true,
                        'autocomplete' => 'off'
                    ],
                    [
                        'type' => 'text',
                        'name' => 'code',
                        'label' => 'Authenticator Code',
                        'required' => true,
                        'autocomplete' => 'off'
                    ],
                ],
            ];
        }
    }

    public function verify(array $data): array {
        $email = trim($data['email'] ?? '');
        $code = trim($data['code'] ?? '');

        $user = $this->db->getUserByEmail($email);
        $user['isactive'] = $user['isactive'] ?? true;

        if ( !$user or !$user['isactive'] ) {
          return [false, 'User not found.'];
        }

        $totp = \OTPHP\TOTP::create($user['secret']);
        $totp->setLabel($user['email']);
        $totp->setIssuer($this->config->get('totp')->get('issuer', 'CloudAware'));

        return [$totp->verify($code), $totp->verify($code) ? null : 'Invalid code'];
    }

    public function getQrCodeForUser(string $email): ?string
    {
        $user = $this->db->getUserByEmail($email);
        if (!$user) {
            return null;
        }

        $totp = \OTPHP\TOTP::create($user['secret']);
        $totp->setLabel($user['email']);
        $totp->setIssuer($this->config->get('totp')->get('issuer', 'CloudAware'));

        $qr = \Endroid\QrCode\Builder\Builder::create()
            ->data($totp->getProvisioningUri())
            ->build();

        return base64_encode($qr->getString());
    }
}

