<?php
namespace App\Lib;

class Mailer
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    public function send(string $toEmail, string $subject, string $htmlBody, array $attachments = []): bool
    {
        $fromEmail = $this->config['from_email'] ?? ($this->config['username'] ?? 'no-reply@example.com');
        $fromName  = $this->config['from_name'] ?? 'Aulas de Innovación';

        // Load Composer autoload unconditionally (for PHPMailer)
        $vendorPath = realpath(__DIR__ . '/../../vendor/autoload.php');
        if ($vendorPath && file_exists($vendorPath)) {
            require_once $vendorPath;
        }

        // SMTP via PHPMailer
        if (($this->config['driver'] ?? 'smtp') === 'smtp') {
            if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                error_log('Mailer: PHPMailer not found. Ensure composer dependencies are installed.');
                return false; // No fallback if driver is smtp
            }
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->SMTPAuth   = true;
                $mail->Host       = $this->config['host'] ?? '';
                $mail->Username   = $this->config['username'] ?? '';
                $mail->Password   = $this->config['password'] ?? '';
                $mail->Port       = (int)($this->config['port'] ?? 587);
                $enc = $this->config['encryption'] ?? 'tls';
                // Map encryption to PHPMailer constants when possible
                if ($enc === 'tls') {
                    if (defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_STARTTLS')) {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    } else {
                        $mail->SMTPSecure = 'tls';
                    }
                } elseif ($enc === 'ssl') {
                    if (defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_SMTPS')) {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $mail->SMTPSecure = 'ssl';
                    }
                }

                // Common XAMPP local cert issues: allow self-signed (only for local env)
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];

                $mail->CharSet = 'UTF-8';
                $mail->Timeout = 20; // seconds

                // Optional debug via config
                if (!empty($this->config['debug'])) {
                    $mail->SMTPDebug = 2; // verbose
                    $mail->Debugoutput = function ($str, $level) {
                        error_log("SMTP($level): $str");
                    };
                } else {
                    $mail->SMTPDebug = 0;
                }

                // Set From and recipient
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($toEmail);
                $mail->addReplyTo($fromEmail, $fromName);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                // AltBody en texto plano para clientes que bloquean HTML o botones
                $alt = strip_tags($htmlBody);
                // Si hay un único enlace, intenta mantenerlo visible
                if (preg_match('/https?:\/\/\S+/i', $htmlBody, $m)) {
                    $alt .= "\n\nEnlace: " . $m[0];
                }
                $mail->AltBody = $alt;

                foreach ($attachments as $attach) {
                    if (is_array($attach) && isset($attach['data'], $attach['name'])) {
                        if (!empty($attach['isString'])) {
                            $mail->addStringAttachment($attach['data'], $attach['name']);
                        } else {
                            $mail->addAttachment($attach['data'], $attach['name']);
                        }
                    }
                }

                return $mail->send();
            } catch (\Throwable $e) {
                error_log('Mailer SMTP error: ' . $e->getMessage());
                return false; // Do NOT fallback to mail() when driver is smtp
            }
        }

        // Plain PHP mail() only if explicitly configured
        $headers = "MIME-Version: 1.0\r\n" .
                   "Content-type:text/html;charset=UTF-8\r\n" .
                   'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";
        return mail($toEmail, $subject, $htmlBody, $headers);
    }
}
