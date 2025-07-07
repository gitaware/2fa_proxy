<?php
namespace App\Providers;

#use function sendEmail;
use App\Mail\MailService;

class EmailCodeProvider implements ProviderInterface {
    private $db;

    public function __construct($db, $config) {
        $this->db          = $db;
        $this->config      = $config;
        $this->mailservice = new MailService();
        $this->mfa_bytes   = 5; //number of bytes for 2FA code per email
    }

    public function getId(): string {
        return 'emailcode';
    }

    public function getLabel(): string {
        return 'Email One-Time Code';
    }

    public function getIcon(): string {
        return 'bi-envelope-fill';
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
                        'type' => 'button',
                        'name' => 'send_code',
                        'label' => 'Send Code to Email Address',
                        'buttonText' => 'Send Code',
                        'jsEventListener' => <<<JS
        // This function is attached to the send_code button after rendering
        async function sendCodeHandler(event) {
            const btn = event.target;
            const form = btn.closest('form');
            const emailInput = form.querySelector('input[name="email"]');
            const codeInputLbl = document.getElementById('code-lbl');
            const codeInput = form.querySelector('input[name="code"]');
            const verifyBtn = form.querySelector('button[id="verify-btn"]');

            if (!emailInput.value) {
                alert('Please enter your email address first.');
                emailInput.focus();
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        action: 'send_email_code',
                        method: 'emailcode',
                        email: emailInput.value
                    }),
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Code sent to your email.');
                    codeInput.disabled = false;
                    codeInputLbl.classList.remove('disabled-label');
                    verifyBtn.disabled = false;
                } else {
                    alert(result.error || 'Failed to send code.');
                }
            } catch (e) {
                alert('An error occurred while sending the code.');
            }

            btn.disabled = false;
            btn.textContent = 'Send Code';
        }
        JS
                    ],
                    [
                        'type' => 'text',
                        'name' => 'code',
                        'label' => 'Email Code',
                        'required' => false,
                        'disabled' => true,
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
                        'type' => 'button',
                        'name' => 'send_code',
                        'label' => 'Send Code to Email Address',
                        'buttonText' => 'Send Code',
                        'jsEventListener' => <<<JS
        // This function is attached to the send_code button after rendering
        async function sendCodeHandler(event) {
            const btn = event.target;
            const form = btn.closest('form');
            const emailInput = form.querySelector('input[name="email"]');
            const csrfInput = form.querySelector('input[name="_csrf_token"]');
            const codeInputLbl = document.getElementById('code-lbl');
            const codeInput = form.querySelector('input[name="code"]');
            const verifyBtn = form.querySelector('button[id="verify-btn"]');

            if (!emailInput.value) {
                alert('Please enter your email address first.');
                emailInput.focus();
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const response = await fetch('send_email_code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        _csrf_token: csrfInput.value,
                        method: 'emailcode',
                        email: emailInput.value
                    }),
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Code sent to your email.');
                    codeInput.disabled = false;
                    codeInputLbl.classList.remove('disabled-label');
                    verifyBtn.disabled = false;
                } else {
                    alert(result.error || 'Failed to send code.');
                }
            } catch (e) {
                alert('An error occurred while sending the code.');
            }

            btn.disabled = false;
            btn.textContent = 'Send Code';
        }
        JS
                    ],
                    [
                        'type' => 'text',
                        'name' => 'code',
                        'label' => 'Email Code',
                        'required' => false,
                        'disabled' => true,
                        'autocomplete' => 'off'
                    ],
                ],
            ];
        }
    }

    public function sendEmailCode(array $data): array {
        $email = trim($data['email'] ?? '');

        if (!$email) return [false, 'Email is required.'];

        $code = strtoupper(bin2hex(random_bytes($this->mfa_bytes)));
        $_SESSION['email_otp'][$email] = $code;
        $this->mailservice->sendEmail($email, 'Your login code', "Your one-time code is: $code");
        return [true, 'Code sent to your email. Please enter it below.'];
    }

    public function verify(array $data): array {
        $email     = trim($data['email'] ?? '');
        $inputCode = trim($data['code'] ?? '');

        if (!$email) return [false, 'Email is required.'];

        $user = $this->db->getUserByEmail($email);
        $user['isactive'] = $user['isactive'] ?? true;
        if ( !$user or !$user['isactive'] ) {
          return [false, 'Inactive user'];
        }

        if (empty($inputCode)) {
            $code = strtoupper(bin2hex(random_bytes($this->mfa_bytes)));
            $_SESSION['email_otp'][$email] = $code;
            $this->mailservice->sendEmail($email, 'Your login code', "Your one-time code is: $code");
            return [false, 'Code sent to your email. Please enter it below.'];
        }

        if (isset($_SESSION['email_otp'][$email]) && $_SESSION['email_otp'][$email] === $inputCode) {
            unset($_SESSION['email_otp'][$email]); // One-time use
            return [true, null];
        }

        return [false, 'Invalid or expired code.'];
    }
}

