<?php
// app/Models/Enquete.php
// Enquetes e votações da turma/grêmio: pergunta, opções e votos (um por opção por pessoa).
require_once __DIR__ . '/../Core/Model.php';

class Enquete extends Model
{
    const RESULTADO = [
        'sempre'        => 'Sempre visível',
        'apos_votar'    => 'Depois de votar',
        'apos_encerrar' => 'Só quando encerrar',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'enquetes';
    }

    /** Enquetes da turma, mais novas primeiro, com total de votantes e se o usuário já votou. */
    public function daTurma(int $turmaId, int $usuarioId): array
    {
        $st = $this->conn->prepare("
            SELECT e.*, u.nome AS autor,
                   (SELECT COUNT(DISTINCT v.usuario_id) FROM enquete_votos v WHERE v.enquete_id = e.id) AS votantes,
                   EXISTS(SELECT 1 FROM enquete_votos v WHERE v.enquete_id = e.id AND v.usuario_id = ?) AS votei
            FROM enquetes e
            INNER JOIN usuarios u ON u.id = e.criado_por
            WHERE e.turma_id = ?
            ORDER BY (e.encerrada_em IS NULL AND (e.encerra_em IS NULL OR e.encerra_em > NOW())) DESC, e.created_at DESC
        ");
        $st->execute([$usuarioId, $turmaId]);
        return array_map(fn($e) => $e + ['aberta' => self::aberta($e)], $st->fetchAll(PDO::FETCH_ASSOC));
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT e.*, u.nome AS autor FROM enquetes e INNER JOIN usuarios u ON u.id = e.criado_por WHERE e.id = ?");
        $st->execute([$id]);
        $e = $st->fetch(PDO::FETCH_ASSOC);
        return $e ? $e + ['aberta' => self::aberta($e)] : null;
    }

    /** Aberta: não foi encerrada à mão e o prazo (se houver) não passou. */
    public static function aberta(array $e): bool
    {
        return empty($e['encerrada_em']) && (empty($e['encerra_em']) || strtotime($e['encerra_em']) > time());
    }

    /** Opções com o número de votos (e, se não for anônima, quem votou). */
    public function opcoes(int $enqueteId, bool $comNomes = false): array
    {
        $st = $this->conn->prepare("
            SELECT o.id, o.texto, COUNT(v.id) AS votos
                   " . ($comNomes ? ", GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS nomes" : '') . "
            FROM enquete_opcoes o
            LEFT JOIN enquete_votos v ON v.opcao_id = o.id
            " . ($comNomes ? 'LEFT JOIN usuarios u ON u.id = v.usuario_id' : '') . "
            WHERE o.enquete_id = ?
            GROUP BY o.id, o.texto, o.ordem
            ORDER BY o.ordem, o.id
        ");
        $st->execute([$enqueteId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ids das opções em que o usuário votou. */
    public function meusVotos(int $enqueteId, int $usuarioId): array
    {
        $st = $this->conn->prepare("SELECT opcao_id FROM enquete_votos WHERE enquete_id = ? AND usuario_id = ?");
        $st->execute([$enqueteId, $usuarioId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    public function votantes(int $enqueteId): int
    {
        $st = $this->conn->prepare("SELECT COUNT(DISTINCT usuario_id) FROM enquete_votos WHERE enquete_id = ?");
        $st->execute([$enqueteId]);
        return (int) $st->fetchColumn();
    }

    /** Cria a enquete com as opções. Retorna o id. */
    public function criar(array $d, array $opcoes): int
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("
                INSERT INTO enquetes (turma_id, criado_por, pergunta, descricao, multipla, anonima, resultado, encerra_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$d['turma_id'], $d['criado_por'], $d['pergunta'], $d['descricao'], $d['multipla'], $d['anonima'], $d['resultado'], $d['encerra_em']]);
            $id = (int) $this->conn->lastInsertId();
            $ins = $this->conn->prepare("INSERT INTO enquete_opcoes (enquete_id, texto, ordem) VALUES (?, ?, ?)");
            foreach (array_values($opcoes) as $i => $t) $ins->execute([$id, $t, $i]);
            $this->conn->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /** Substitui os votos do usuário pelas opções escolhidas (pode mudar o voto enquanto está aberta). */
    public function votar(int $enqueteId, int $usuarioId, array $opcaoIds): void
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("DELETE FROM enquete_votos WHERE enquete_id = ? AND usuario_id = ?")->execute([$enqueteId, $usuarioId]);
            $ins = $this->conn->prepare("INSERT INTO enquete_votos (enquete_id, opcao_id, usuario_id) VALUES (?, ?, ?)");
            foreach ($opcaoIds as $o) $ins->execute([$enqueteId, $o, $usuarioId]);
            $this->conn->commit();
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function encerrar(int $id): void
    {
        $this->conn->prepare("UPDATE enquetes SET encerrada_em = NOW() WHERE id = ? AND encerrada_em IS NULL")->execute([$id]);
    }

    public function excluir(int $id): void
    {
        $this->conn->prepare("DELETE FROM enquetes WHERE id = ?")->execute([$id]);
    }
}
