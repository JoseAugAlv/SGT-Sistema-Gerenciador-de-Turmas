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
        // Todo email sai no modelo profissional (quem já montou com Mail::modelo não é embrulhado de novo)
        if (!str_contains($html, '<!--modelo-prumo-->')) {
            $html = self::modelo(['titulo' => preg_replace('/\s+—\s+' . preg_quote(App::getName(), '/') . '$/u', '', $assunto), 'corpo' => $html]);
        }
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
            $mail->SMTPSecure = $this->port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            // MAIL_FROM: endereço que aparece como remetente (em serviços como o Brevo o login não é um email)
            $mail->setFrom((string) App::get('MAIL_FROM', $this->user) ?: $this->user, $this->fromName);
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
        $link = rtrim(App::getUrl(), '/') . '/auth/confirmar-email?token=' . urlencode($token);
        return $this->enviar($para, 'Confirme seu email — ' . App::getName(), self::modelo([
            'previa'  => 'Falta só um clique para ativar sua conta.',
            'selo'    => 'Boas-vindas',
            'titulo'  => 'Confirme seu email',
            'corpo'   => '<p>Olá, <strong>' . self::e($nome) . '</strong>! Sua conta no ' . self::e(App::getName()) . ' está quase pronta. Confirme que este email é seu para começar.</p>',
            'botao'   => ['Confirmar meu email', $link],
            'nota'    => 'O link vale por 24 horas. Se você não criou esta conta, ignore este email.',
        ]), $nome);
    }

    public function emailResetSenha(string $para, string $nome, string $token): bool
    {
        $link = rtrim(App::getUrl(), '/') . '/auth/redefinir?token=' . urlencode($token);
        return $this->enviar($para, 'Redefinição de senha — ' . App::getName(), self::modelo([
            'previa'  => 'Crie uma nova senha em poucos segundos.',
            'selo'    => 'Segurança',
            'titulo'  => 'Redefinir sua senha',
            'corpo'   => '<p>Olá, <strong>' . self::e($nome) . '</strong>. Recebemos um pedido para redefinir a senha da sua conta.</p>',
            'botao'   => ['Criar nova senha', $link],
            'nota'    => 'O link vale por 2 horas e só pode ser usado uma vez. Se não foi você, ignore: sua senha continua a mesma.',
        ]), $nome);
    }

    public function emailPrimeiroAcesso(string $para, string $nome, string $token, string $senhaTemp): bool
    {
        $link = rtrim(App::getUrl(), '/') . '/login';
        return $this->enviar($para, 'Sua conta foi criada — ' . App::getName(), self::modelo([
            'previa'  => 'Sua conta está pronta. Entre e defina sua senha.',
            'selo'    => 'Conta criada',
            'titulo'  => 'Bem-vindo ao ' . App::getName(),
            'corpo'   => '<p>Olá, <strong>' . self::e($nome) . '</strong>. Criamos sua conta. Use a senha temporária abaixo no primeiro acesso; depois você escolhe a sua.</p>',
            'info'    => ['Email' => $para, 'Senha temporária' => $senhaTemp],
            'botao'   => ['Entrar agora', $link],
            'nota'    => 'No primeiro acesso você troca a senha e aceita a Política de Privacidade (LGPD).',
        ]), $nome);
    }

    public function emailSenhaAlterada(string $para, string $nome): bool
    {
        return $this->enviar($para, 'Sua senha foi alterada — ' . App::getName(), self::modelo([
            'previa'  => 'A senha da sua conta acabou de ser alterada.',
            'selo'    => 'Segurança',
            'titulo'  => 'Senha alterada',
            'corpo'   => '<p>Olá, <strong>' . self::e($nome) . '</strong>. A senha da sua conta foi alterada em ' . date('d/m/Y \à\s H:i') . '.</p>',
            'nota'    => 'Se não foi você, redefina a senha agora pelo “Esqueci minha senha” na tela de login.',
        ]), $nome);
    }

    // ============ MODELO PROFISSIONAL DE EMAIL ============

    public static function e(?string $t): string
    {
        return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Monta o HTML do email (tabelas + estilos inline: funciona no Gmail, Outlook, Apple Mail e celular; tem modo escuro).
     * $o: titulo (obrigatório), corpo (HTML), previa (texto da caixa de entrada), selo (rótulo acima do título),
     *     botao [texto, url], info [rótulo => valor] (cartão de detalhes), destaque (texto grande, ex.: contagem),
     *     cor (cor do selo/botão, padrão azul), nota (texto pequeno abaixo), rodape (motivo do email).
     */
    public static function modelo(array $o): string
    {
        $app   = self::e(App::getName());
        $url   = rtrim(App::getUrl(), '/');
        $cor   = $o['cor'] ?? '#2563eb';
        $tit   = self::e($o['titulo'] ?? $app);
        $prev  = self::e($o['previa'] ?? strip_tags($o['titulo'] ?? ''));
        $selo  = !empty($o['selo']) ? '<div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:' . $cor . ';margin:0 0 10px">' . self::e($o['selo']) . '</div>' : '';
        $dest  = !empty($o['destaque']) ? '<div style="margin:18px 0 6px;padding:16px 18px;border-radius:12px;background:#f1f5f9;font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-.01em" class="ap-caixa">' . self::e($o['destaque']) . '</div>' : '';
        $info  = '';
        if (!empty($o['info'])) {
            $linhas = '';
            foreach ($o['info'] as $k => $v) {
                if ($v === null || $v === '') continue;
                $linhas .= '<tr><td style="padding:9px 0;color:#64748b;font-size:13px;width:38%;vertical-align:top" class="ap-mudo">' . self::e($k) . '</td>'
                         . '<td style="padding:9px 0;color:#0f172a;font-size:14px;font-weight:600;vertical-align:top" class="ap-texto">' . self::e((string) $v) . '</td></tr>';
            }
            $info = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0 4px;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0" class="ap-borda">' . $linhas . '</table>';
        }
        $botao = '';
        if (!empty($o['botao'])) {
            [$txt, $href] = $o['botao'];
            $href = self::e(str_starts_with($href, 'http') ? $href : $url . '/' . ltrim($href, '/'));
            $botao = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 8px"><tr><td style="border-radius:10px;background:' . $cor . ';background-image:linear-gradient(135deg,' . $cor . ',#7c3aed)">'
                   . '<a href="' . $href . '" style="display:inline-block;padding:14px 26px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px">' . self::e($txt) . ' &rarr;</a></td></tr></table>'
                   . '<p style="margin:12px 0 0;font-size:12px;line-height:1.5;color:#94a3b8" class="ap-mudo">Se o botão não abrir, copie e cole: <a href="' . $href . '" style="color:#64748b;word-break:break-all">' . $href . '</a></p>';
        }
        $nota   = !empty($o['nota']) ? '<p style="margin:22px 0 0;padding-top:18px;border-top:1px solid #e2e8f0;font-size:13px;line-height:1.6;color:#64748b" class="ap-mudo ap-borda">' . self::e($o['nota']) . '</p>' : '';
        $rodape = self::e($o['rodape'] ?? 'Você recebeu este email por causa da sua conta no ' . App::getName() . '.');
        $ano    = date('Y');
        $corpo  = $o['corpo'] ?? '';

        return <<<HTML
<!--modelo-prumo--><!DOCTYPE html>
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark">
<title>{$tit}</title>
<style>
  body{margin:0;padding:0;background:#eef2f7;-webkit-text-size-adjust:100%}
  .ap-corpo p{margin:0 0 14px;font-size:15px;line-height:1.65;color:#334155}
  .ap-corpo a{color:{$cor};font-weight:600}
  .ap-corpo strong{color:#0f172a}
  @media (max-width:620px){ .ap-card{padding:28px 22px !important} .ap-titulo{font-size:24px !important} }
  @media (prefers-color-scheme:dark){
    body,.ap-fundo{background:#0b1020 !important}
    .ap-card{background:#121a2e !important;border-color:#1f2a44 !important}
    .ap-titulo,.ap-texto,.ap-corpo strong{color:#f1f5f9 !important}
    .ap-corpo p{color:#cbd5e1 !important} .ap-mudo{color:#94a3b8 !important}
    .ap-borda{border-color:#1f2a44 !important} .ap-caixa{background:#1a2440 !important;color:#f1f5f9 !important}
  }
</style>
</head>
<body style="margin:0;padding:0;background:#eef2f7">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">{$prev}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="ap-fundo" style="background:#eef2f7;padding:32px 12px;font-family:'Inter','Segoe UI',Roboto,Helvetica,Arial,sans-serif">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px">
    <tr><td style="padding:0 4px 18px">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="font-size:20px;font-weight:800;letter-spacing:-.02em;color:#0f172a" class="ap-titulo">
          <span style="display:inline-block;width:30px;height:30px;line-height:30px;margin-right:8px;border-radius:9px;background:#2563eb;background-image:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;text-align:center;font-size:16px;vertical-align:middle">&#9650;</span><span style="vertical-align:middle">{$app}</span>
        </td>
        <td align="right" style="font-size:12px;color:#94a3b8" class="ap-mudo">Gestão de turmas e projetos</td>
      </tr></table>
    </td></tr>
    <tr><td style="height:4px;border-radius:4px 4px 0 0;background:#2563eb;background-image:linear-gradient(90deg,#2563eb,#7c3aed,#06b6d4)"></td></tr>
    <tr><td class="ap-card" style="background:#ffffff;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 18px 18px;padding:40px 40px 34px;box-shadow:0 12px 32px rgba(15,23,42,.06)">
      {$selo}
      <h1 class="ap-titulo" style="margin:0 0 18px;font-size:28px;line-height:1.2;font-weight:800;letter-spacing:-.02em;color:#0f172a">{$tit}</h1>
      <div class="ap-corpo">{$corpo}</div>
      {$dest}{$info}{$botao}{$nota}
    </td></tr>
    <tr><td style="padding:22px 8px 0;text-align:center;font-size:12px;line-height:1.7;color:#94a3b8" class="ap-mudo">
      {$rodape}<br>
      <a href="{$url}/notificacoes/preferencias" style="color:#64748b;text-decoration:underline">Preferências de email</a> &nbsp;·&nbsp; <a href="{$url}" style="color:#64748b;text-decoration:underline">Abrir o {$app}</a><br>
      &copy; {$ano} {$app}
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;
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