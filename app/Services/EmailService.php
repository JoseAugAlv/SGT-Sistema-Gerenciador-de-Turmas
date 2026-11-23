<?php
// app/Services/EmailService.php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Core/App.php';
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
            $nivel = $this->prefs->porUsuario($usuarioId)['nivel_email'] ?? 'importantes';
            // Alertas da turma são "importantes"; quem quer só menções ou nenhum email só recebe os urgentes
            if (in_array($nivel, ['mencoes', 'nenhuma'], true)) return false;
        }

        return $this->mail->enviar($user['email'], $assunto, $html, $user['nome']);
    }

    // Tipos de notificação considerados importantes (nível 'importantes')
    const TIPOS_IMPORTANTES = ['alerta', 'prazo', 'avaliacao', 'papel', 'plano', 'convite', 'lembrete'];

    /**
     * Manda por email uma notificação do sistema, conforme o nível escolhido pela pessoa:
     * todas = sempre; importantes = tipos importantes; mencoes = só as dirigidas a ela; nenhuma = nunca.
     * 'alerta' não passa por aqui (o AlertaController já manda o email do aviso).
     */
    public function notificacaoPorEmail(int $usuarioId, string $tipo, string $titulo, string $mensagem, ?string $link, bool $direta): bool
    {
        if ($tipo === 'alerta') return false;
        $nivel = $this->prefs->porUsuario($usuarioId)['nivel_email'] ?? 'importantes';
        $manda = match ($nivel) {
            'todas'       => true,
            'importantes' => in_array($tipo, self::TIPOS_IMPORTANTES, true),
            'mencoes'     => $direta,
            default       => false,
        };
        if (!$manda) return false;

        $url  = $link ? (str_starts_with($link, 'http') ? $link : App::getUrl() . (str_starts_with($link, App::getBasePath()) ? substr($link, strlen(App::getBasePath())) : $link)) : App::getUrl();
        $selos = ['lembrete' => ['Lembrete', '#e08a00'], 'prazo' => ['Prazo', '#d63548'], 'avaliacao' => ['Avaliação', '#2563eb'],
                  'papel' => ['Sua participação', '#7c3aed'], 'plano' => ['Plano', '#0e9f6e'], 'convite' => ['Convite', '#2563eb'],
                  'calendario' => ['Agenda', '#2563eb'], 'reuniao' => ['Reunião', '#7c3aed'], 'ata' => ['Ata', '#2563eb'], 'material' => ['Materiais', '#2563eb']];
        [$selo, $cor] = $selos[$tipo] ?? ['Notificação', '#2563eb'];
        $html = Mail::modelo([
            'previa' => $mensagem, 'selo' => $selo, 'cor' => $cor, 'titulo' => $titulo,
            'corpo'  => '<p>' . nl2br(Mail::e($mensagem)) . '</p>',
            'botao'  => ['Abrir no ' . App::getName(), $url],
            'rodape' => 'Você recebe este email pela sua preferência de notificações (' . (['todas' => 'todas', 'importantes' => 'só as importantes', 'mencoes' => 'só menções diretas'][$nivel] ?? $nivel) . ').',
        ]);
        return $this->notificar($usuarioId, $titulo, $html, 'geral', true);
    }
}