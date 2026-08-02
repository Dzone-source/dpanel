<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\Config;
use Exception;
use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class Smtp extends Base
{
    private PHPMailer $mail;

    /**
     * @throws Exception
     * @throws PhpMailerException
     */
    public function __construct()
    {
        $configs = Config::getClass('email');

        // Enable exceptions so callers learn about SMTP failures instead of silent false returns.
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) $configs['smtp_host'];
        $mail->Port = (int) $configs['smtp_port'];
        $mail->SMTPAuth = ! ($configs['smtp_username'] === '' && $configs['smtp_password'] === '');
        $mail->CharSet = 'UTF-8';
        $mail->Username = (string) $configs['smtp_username'];
        $mail->Password = (string) $configs['smtp_password'];
        $mail->setFrom((string) $configs['smtp_sender'], (string) $configs['smtp_name']);

        if ($configs['smtp_ssl']) {
            $mail->SMTPSecure = ((string) $configs['smtp_port'] === '587' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS);
        }

        if ($configs['smtp_bbc'] !== '') {
            $mail->addBCC((string) $configs['smtp_bbc']);
        }

        $this->mail = $mail;
    }

    /**
     * @throws PhpMailerException
     */
    public function send($to, $subject, $body): void
    {
        $mail = $this->mail;
        $mail->clearAddresses();
        $mail->clearAttachments();
        $mail->addAddress($to);
        $mail->isHTML();
        $mail->Subject = $subject;
        $mail->Body = $body;

        if (! $mail->send()) {
            throw new PhpMailerException($mail->ErrorInfo !== '' ? $mail->ErrorInfo : 'SMTP send failed');
        }
    }
}
