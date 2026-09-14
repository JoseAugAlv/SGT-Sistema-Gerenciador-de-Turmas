<?php
// app/Models/RelatorioAta.php
require_once __DIR__ . '/../Core/Model.php';

class RelatorioAta extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'relatorios_ata';
    }

    public function listarPorAta(int $ataId): array
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.nome AS autor_nome
            FROM relatorios_ata r
            INNER JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.ata_id = ?
            ORDER BY r.id ASC
        ");
        $stmt->execute([$ataId]);
        $relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($relatorios as &$r) {
            $r['participantes'] = $this->participantes((int) $r['id']);
        }
        return $relatorios;
    }

    public function participantes(int $relatorioId): array
    {
        $stmt = $this->conn->prepare("
            SELECT pr.*, u.nome AS usuario_nome
            FROM participantes_relatorio pr
            INNER JOIN usuarios u ON u.id = pr.usuario_id
            WHERE pr.relatorio_id = ?
        ");
        $stmt->execute([$relatorioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(
        int $ataId,
        int $usuarioId,
        string $tipoUsuario,
        ?string $titulo,
        string $conteudo,
        string $tipoRelatorio,
        ?string $tema
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO relatorios_ata
                (ata_id, usuario_id, tipo_usuario, titulo, conteudo, tipo_relatorio, tema)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ataId, $usuarioId, $tipoUsuario, $titulo, $conteudo, $tipoRelatorio, $tema]);
        return (int) $this->conn->lastInsertId();
    }

    public function adicionarParticipante(int $relatorioId, int $usuarioId): void
    {
        $this->conn->prepare("
            INSERT IGNORE INTO participantes_relatorio (relatorio_id, usuario_id)
            VALUES (?, ?)
        ")->execute([$relatorioId, $usuarioId]);
    }

    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM relatorios_ata WHERE id = ?")->execute([$id]);
    }
}