<?php
// app/Core/Mail.php

require_once __DIR__ . '/App.php';
require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromName;

    public function __construct()
    {
        $this->host = Config::get('MAIL_HOST');
        $this->port = Config::get('MAIL_PORT');
        $this->username = Config::get('MAIL_USER');
        $this->password = Config::get('MAIL_PASS');
        $this->fromName = Config::get('MAIL_FROM_NAME', App::getName());
    }

    /**
     * Envia um e-mail usando SMTP
     */
    public function send($para, $assunto, $mensagem, $paraNome = '')
    {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;

            $mail->setFrom($this->username, $this->fromName);
            $mail->addAddress($para, $paraNome);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $mensagem;

            return $mail->send();
            
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Envia e-mail de redefinição de senha
     */
    public function sendResetPassword($email, $nome, $token)
    {
        $appName = App::getName();
        $appUrl = App::getUrl();
        $link = $appUrl . '/auth/redefinir?token=' . $token;
        
        $assunto = "Redefinição de Senha - " . $appName;
        
        $mensagem = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background: #10b981; color: #fff; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; }
                .btn { display: inline-block; background: #10b981; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
                .btn:hover { background: #059669; }
                .footer { text-align: center; padding: 15px; font-size: 12px; color: #888; border-top: 1px solid #ddd; margin-top: 20px; }
                .token { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; word-break: break-all; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>" . $appName . "</h2>
                </div>
                <div class='content'>
                    <p>Olá <strong>" . htmlspecialchars($nome) . "</strong>,</p>
                    <p>Recebemos uma solicitação para redefinir sua senha no sistema " . $appName . ".</p>
                    <p>Clique no botão abaixo para redefinir sua senha:</p>
                    <p style='text-align: center;'>
                        <a href='" . $link . "' class='btn' style='color:#ffffff;'>Redefinir Senha</a>
                    </p>
                    <p>Ou copie e cole o link no navegador:</p>
                    <p class='token'>" . $link . "</p>
                    <p><strong>Este link é válido por 1 hora.</strong></p>
                    <p>Se você não solicitou a redefinição de senha, ignore este e-mail.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . $appName . " - Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($email, $assunto, $mensagem, $nome);
    }

    /**
     * Método enviar() - alias para send() para compatibilidade
     * Usado pelo AuthController
     */
    public function enviar($email, $nome, $assunto, $mensagem)
    {
        return $this->send($email, $assunto, $mensagem, $nome);
    }
}