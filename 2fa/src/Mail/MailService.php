<?php

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    protected string $fromAddress = 'admin@example.com';
    protected string $fromName = '2FA System';

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

