<?php
// app/Core/Mail.php
require_once __DIR__ . '/App.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $fromName;

    public function __construct()
    {
        $this->host     = (string) App::get('MAIL_HOST', '');
        $this->port     = (int)    App::get('MAIL_PORT', 587);
        $this->user     = (string) App::get('MAIL_USER', '');
        $this->pass     = (string) App::get('MAIL_PASS', '');
        $this->fromName = (string) App::get('MAIL_FROM_NAME', App::getName());
    }

    /**
     * Envia (ou tenta enviar) email. Sempre registra em logs/mail.log.
     */
    public function enviar(string $para, string $assunto, string $html, string $paraNome = ''): bool
    {
        $this->logar($para, $assunto, $html);

        if (empty($this->user)) {
            return true; // modo dev — só log
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->user;
            $mail->Password   = $this->pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->user, $this->fromName);
            $mail->addAddress($para, $paraNome);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $html;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer: ' . $e->getMessage());
            return false;
        }
    }

    // Aliases legados
    public function send($para, $assunto, $msg, $nome = '') { return $this->enviar($para, $assunto, $msg, $nome); }

    public function emailConfirmacao(string $para, string $nome, string $token): bool
    {
        $link = App::getUrl() . '/auth/confirmar-email?token=' . urlencode($token);
        $assunto = 'Confirme seu email — ' . App::getName();
        $html = "
            <p>Olá, <strong>{$nome}</strong>.</p>
            <p>Confirme seu email clicando no link abaixo (válido por 24h):</p>
            <p><a href=\"{$link}\">{$link}</a></p>
        ";
        return $this->enviar($para, $assunto, $html, $nome);
    }

    public function emailResetSenha(string $para, string $nome, string $token): bool
    {
        $link = App::getUrl() . '/auth/redefinir?token=' . urlencode($token);
        $assunto = 'Redefinição de senha — ' . App::getName();
        $html = "
            <p>Olá, <strong>{$nome}</strong>.</p>
            <p>Para redefinir sua senha, clique no link abaixo (válido por 2h):</p>
            <p><a href=\"{$link}\">{$link}</a></p>
            <p>Se você não solicitou, ignore este email.</p>
        ";
        return $this->enviar($para, $assunto, $html, $nome);
    }

    public function emailPrimeiroAcesso(string $para, string $nome, string $token, string $senhaTemp): bool
    {
        $link = App::getUrl() . '/login';
        $assunto = 'Sua conta foi criada — ' . App::getName();
        $html = "
            <p>Olá, <strong>{$nome}</strong>.</p>
            <p>Sua conta foi criada com a senha temporária: <strong>{$senhaTemp}</strong></p>
            <p>Acesse o sistema: <a href=\"{$link}\">{$link}</a></p>
            <p>No primeiro acesso você precisará trocar a senha e aceitar os termos LGPD.</p>
        ";
        return $this->enviar($para, $assunto, $html, $nome);
    }

    public function emailSenhaAlterada(string $para, string $nome): bool
    {
        $assunto = 'Sua senha foi alterada — ' . App::getName();
        $html = "<p>Olá, <strong>{$nome}</strong>.</p><p>Sua senha foi alterada com sucesso.</p>";
        return $this->enviar($para, $assunto, $html, $nome);
    }

    private function logar(string $para, string $assunto, string $html): void
    {
        $dir = __DIR__ . '/../../logs';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $linha  = str_repeat('=', 70) . "\n";
        $linha .= "[" . date('Y-m-d H:i:s') . "] To: {$para}\n";
        $linha .= "Assunto: {$assunto}\n";
        $linha .= $html . "\n";
        @file_put_contents($dir . '/mail.log', $linha, FILE_APPEND);
    }
}