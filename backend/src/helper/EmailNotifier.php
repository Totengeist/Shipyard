<?php

namespace Shipyard;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * @todo Generalize to send notifications through one or more channels (email, log file, Gotify, Pushover, etc.)
 */
class EmailNotifier extends Notifier {
    /**
     * The mailer.
     *
     * @var PHPMailer
     */
    private $mailer;
    /**
     * Whether the notification should send an HTML body.
     *
     * @var bool
     */
    private $isHTML = false;

    /**
     * Instanciate the mailer.
     *
     * @param PHPMailer|null $mailer
     */
    public function __construct($mailer = null) {
        if ($mailer == null) {
            $mailer = new PHPMailer(true);
            $mailer->isHTML($this->isHTML);
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->getEnvironmentSettings($mailer);
        }

        $this->mailer = $mailer;
    }

    /**
     * @param bool $html whether to enable HTML in the body
     *
     * @return void
     */
    public function isHTML($html = true) {
        $this->mailer->isHTML($html);
        $this->isHTML = $html;
    }

    /**
     * Retrieve the basic configuration settings from the environment.
     *
     * @param PHPMailer $mailer
     *
     * @return void
     */
    public function getEnvironmentSettings($mailer) {
        if (!isset($_SERVER['SMTP_HOST'], $_SERVER['SMTP_USER'], $_SERVER['SMTP_PASSWORD'], $_SERVER['SMTP_PASSWORD'], $_SERVER['SMTP_FROM'])
            || $_SERVER['SMTP_HOST'] == '' || $_SERVER['SMTP_USER'] == '' || $_SERVER['SMTP_PASSWORD'] == '' || $_SERVER['SMTP_PASSWORD'] == '' || $_SERVER['SMTP_FROM'] == ''
        ) {
            $this->disable();

            return;
        }

        $mailer->isSMTP();
        if (isset($_SERVER['SMTP_AUTH']) && strtolower(trim($_SERVER['SMTP_AUTH'])) == 'true') {
            $mailer->SMTPAuth = boolval($_SERVER['SMTP_AUTH']);
        } else {
            $mailer->SMTPAuth = false;
        }
        $mailer->SMTPAuth = true;
        $mailer->Host       = $_SERVER['SMTP_HOST'];                     // Set the SMTP server to send through
        $mailer->Username   = $_SERVER['SMTP_USER'];                     // SMTP username
        $mailer->Password   = $_SERVER['SMTP_PASSWORD'];                               // SMTP password
        $mailer->SMTPSecure = 'tls';            // Enable implicit TLS encryption
        $mailer->Port       = isset($_SERVER['SMTP_PORT']) ? intval($_SERVER['SMTP_PORT']) : 465;
        $mailer->setFrom($_SERVER['SMTP_FROM']);
    }

    /**
     * @param string $to the email address to send the notification to
     *
     * @return Notifier
     */
    public function addAddress($to) {
        $this->mailer->addAddress($to);

        return $this;
    }

    /**
     * @return bool
     */
    public function send($message, $subject) {
        if (!$this->enabled || count($this->mailer->getToAddresses()) <= 0) {
            return false;
        }
        $this->mailer->Subject = $subject;
        $this->mailer->Body    = $message;

        Log::get()->channel('notifications')->debug('Sending email to ' . implode(',', $this->mailer->getToAddresses()[0]) . ': ' . $message);

        return $this->mailer->send();
    }
}
