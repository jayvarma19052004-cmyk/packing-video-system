<?php
/**
 * Email Helper
 */

class Mailer {
    private string $to;
    private string $subject;
    private string $body;
    private array $headers = [];

    public function __construct() {
        $this->headers['From'] = SMTP_FROM_NAME . ' <' . SMTP_FROM . '>';
        $this->headers['Reply-To'] = SMTP_FROM;
        $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
    }

    /**
     * Set recipient
     */
    public function to(string $email): self {
        $this->to = $email;
        return $this;
    }

    /**
     * Set subject
     */
    public function subject(string $subject): self {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set HTML body
     */
    public function html(string $html): self {
        $this->body = $html;
        return $this;
    }

    /**
     * Send email
     */
    public function send(): bool {
        if (empty($this->to) || empty($this->subject) || empty($this->body)) {
            Logger::error('Email validation failed: missing required fields');
            return false;
        }

        $headers = implode("\r\n", array_map(fn($k, $v) => "$k: $v", array_keys($this->headers), $this->headers));

        if (mail($this->to, $this->subject, $this->body, $headers)) {
            Logger::info('Email sent', ['to' => $this->to, 'subject' => $this->subject]);
            return true;
        }

        Logger::error('Failed to send email', ['to' => $this->to]);
        return false;
    }

    /**
     * Send verification email
     */
    public static function sendVerificationEmail(string $email, string $token): bool {
        $verificationLink = APP_URL . '/verify-email?token=' . $token;
        $html = <<<HTML
        <h2>Email Verification</h2>
        <p>Please click the link below to verify your email address:</p>
        <a href="{$verificationLink}">Verify Email</a>
        HTML;

        return (new self())
            ->to($email)
            ->subject('Email Verification')
            ->html($html)
            ->send();
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordResetEmail(string $email, string $token): bool {
        $resetLink = APP_URL . '/reset-password?token=' . $token;
        $html = <<<HTML
        <h2>Password Reset</h2>
        <p>Please click the link below to reset your password:</p>
        <a href="{$resetLink}">Reset Password</a>
        <p>This link will expire in 24 hours.</p>
        HTML;

        return (new self())
            ->to($email)
            ->subject('Password Reset')
            ->html($html)
            ->send();
    }
}
