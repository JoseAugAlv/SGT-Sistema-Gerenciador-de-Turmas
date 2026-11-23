<?php
// app/Models/PreferenciasUsuario.php
require_once __DIR__ . '/../Core/Model.php';

class PreferenciasUsuario extends Model
{
    // Quanto email a pessoa quer receber (as notificações dentro do sistema chegam sempre)
    const NIVEIS = [
        'todas'       => ['Todas', 'Um email para cada notificação.'],
        'importantes' => ['Só as importantes', 'Avisos da turma, prazos, avaliações e mudanças no seu papel.'],
        'mencoes'     => ['Só menções diretas', 'Apenas o que é sobre você: sua nota, ser nomeado diretor, um pedido seu.'],
        'nenhuma'     => ['Nenhum email', 'Só dentro do sistema (avisos urgentes ainda chegam).'],
    ];

    /** Liga/desliga o resumo semanal por email (cria a linha de preferências se faltar). */
    public function definirResumo(int $usuarioId, bool $ligado): void
    {
        $this->conn->prepare("
            INSERT INTO preferencias_usuario (usuario_id, resumo_semanal) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE resumo_semanal = VALUES(resumo_semanal)
        ")->execute([$usuarioId, $ligado ? 1 : 0]);
    }

    /** Define o nível de email (cria a linha de preferências se faltar). */
    public function definirNivel(int $usuarioId, string $nivel): void
    {
        if (!isset(self::NIVEIS[$nivel])) $nivel = 'importantes';
        $this->conn->prepare("
            INSERT INTO preferencias_usuario (usuario_id, nivel_email) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE nivel_email = VALUES(nivel_email)
        ")->execute([$usuarioId, $nivel]);
    }

    public function __construct()
    {
        parent::__construct();
        $this->table = 'preferencias_usuario';
    }

    public function criarPadrao(int $usuarioId): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO preferencias_usuario (usuario_id) VALUES (?)
        ");
        $stmt->execute([$usuarioId]);
        return (int) $this->conn->lastInsertId();
    }

        public function atualizar(int $usuarioId, array $d): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE preferencias_usuario
            SET receber_email = ?, receber_email_prazos = ?,
                receber_email_alertas = ?, receber_email_avaliacoes = ?
            WHERE usuario_id = ?
        ");
        return $stmt->execute([
            (int) !empty($d['receber_email']),
            (int) !empty($d['receber_email_prazos']),
            (int) !empty($d['receber_email_alertas']),
            (int) !empty($d['receber_email_avaliacoes']),
            $usuarioId,
        ]);
    }
    

    public function porUsuario(int $usuarioId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM preferencias_usuario WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}