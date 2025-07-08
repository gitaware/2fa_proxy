<?php

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    protected string $fromAddress;
    protected string $fromName;

    public function __construct($config) {
        $this->config      = $config;

        $default_fromname    = '2FA System';
        $default_fromaddress = 'admin@example.com';
        $mailer = $this->config->get('mailer');
        $this->fromName = $mailer && $mailer->get('fromname', $default_fromname) !== null
            ? $mailer->get('fromname', $default_fromname) : $default_fromname;
        $this->fromAddress = $mailer && $mailer->get('fromaddress', $default_fromaddress) !== null
            ? $mailer->get('fromaddress', $default_fromaddress) : $default_fromaddress;
    }

    public function sendEmail(string $to, string $subject, string $body, array $attachments = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->setFrom($this->fromAddress, $this->fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false); // Plain text

            foreach ($attachments as $attachment) {
                $mail->addStringAttachment(
                    $attachment['data'],
                    $attachment['filename'],
                    $attachment['encoding'] ?? 'base64',
                    $attachment['type'] ?? 'application/octet-stream'
                );
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function sendQrCodeEmail(string $to, string $subject, string $body, string $qrImageData): bool
    {
        return $this->sendEmail($to, $subject, $body, [
            [
                'data' => $qrImageData,
                'filename' => 'qrcode.png',
                'type' => 'image/png',
                'encoding' => 'base64'
            ]
        ]);
    }
}

