<?php
// app/Services/EmailService.php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Core/Mail.php';
require_once __DIR__ . '/../Models/PreferenciasUsuario.php';
require_once __DIR__ . '/../Models/Usuario.php';

class EmailService
{
    private Mail                 $mail;
    private PreferenciasUsuario  $prefs;
    private Usuario              $usuario;

    public function __construct()
    {
        $this->mail    = new Mail();
        $this->prefs   = new PreferenciasUsuario();
        $this->usuario = new Usuario();
    }

    /**
     * Envia email respeitando preferências.
     * Categorias: 'geral', 'prazos', 'alertas', 'avaliacoes'
     * Alertas urgentes ignoram preferências (chamado com $forcar=true).
     */
    public function notificar(
        int $usuarioId,
        string $assunto,
        string $html,
        string $categoria = 'geral',
        bool $forcar = false
    ): bool {
        $user = $this->usuario->findById($usuarioId);
        if (!$user || !$user['ativo']) return false;

        if (!$forcar) {
            $prefs = $this->prefs->porUsuario($usuarioId);

            // Se não existe linha de preferências, assume tudo ligado
            if ($prefs) {
                if (empty($prefs['receber_email'])) return false;

                if ($categoria === 'prazos'     && empty($prefs['receber_email_prazos']))     return false;
                if ($categoria === 'alertas'    && empty($prefs['receber_email_alertas']))    return false;
                if ($categoria === 'avaliacoes' && empty($prefs['receber_email_avaliacoes'])) return false;
            }
        }

        return $this->mail->enviar($user['email'], $assunto, $html, $user['nome']);
    }
}