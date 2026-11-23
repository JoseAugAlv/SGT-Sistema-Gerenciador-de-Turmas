<?php
// app/Models/Reuniao.php
// Reuniões da turma ou do grêmio (geral ou de uma diretoria): pauta, ata e lista de presença.
require_once __DIR__ . '/../Core/Model.php';

class Reuniao extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'reunioes';
    }

    public function listar(int $turmaId): array
    {
        $st = $this->conn->prepare("
            SELECT r.*, d.nome AS diretoria_nome,
                   (SELECT COUNT(*) FROM reuniao_presencas p WHERE p.reuniao_id = r.id AND p.presente = 1) AS presentes
            FROM reunioes r LEFT JOIN gremio_diretorias d ON d.id = r.diretoria_id
            WHERE r.turma_id = ? ORDER BY r.data DESC
        ");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("
            SELECT r.*, d.nome AS diretoria_nome, t.nome AS turma_nome, t.tipo AS turma_tipo, u.nome AS criado_por_nome
            FROM reunioes r INNER JOIN turmas t ON t.id = r.turma_id
            LEFT JOIN gremio_diretorias d ON d.id = r.diretoria_id LEFT JOIN usuarios u ON u.id = r.criado_por
            WHERE r.id = ?
        ");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $this->conn->prepare("INSERT INTO reunioes (turma_id, diretoria_id, titulo, data, local, pauta, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?)")
                   ->execute([$d['turma_id'], $d['diretoria_id'], $d['titulo'], $d['data'], $d['local'], $d['pauta'], $d['criado_por']]);
        return (int) $this->conn->lastInsertId();
    }

    /** Registra a ata e a presença; a reunião passa a "realizada". $presentes = ids de quem veio. */
    public function registrar(int $id, string $ata, array $convocados, array $presentes): void
    {
        $this->conn->prepare("UPDATE reunioes SET ata = ?, status = 'realizada', qr_token = NULL, qr_ate = NULL WHERE id = ?")->execute([$ata, $id]);
        // quem não é mais convocado sai da lista; os demais são atualizados (quem marcou pelo QR e continua presente mantém "qr")
        $ids = array_map('intval', $convocados) ?: [0];
        $this->conn->prepare("DELETE FROM reuniao_presencas WHERE reuniao_id = ? AND usuario_id NOT IN (" . implode(',', $ids) . ")")->execute([$id]);
        $ins = $this->conn->prepare("
            INSERT INTO reuniao_presencas (reuniao_id, usuario_id, presente, via, marcado_em) VALUES (?, ?, ?, 'manual', NOW())
            ON DUPLICATE KEY UPDATE via = IF(VALUES(presente) = 1 AND presente = 1, via, 'manual'), presente = VALUES(presente)
        ");
        foreach ($convocados as $uid) $ins->execute([$id, (int) $uid, in_array((int) $uid, $presentes, true) ? 1 : 0]);
    }

    // ============ PRESENÇA POR QR CODE ============

    /** Abre (ou renova) o QR de presença por $minutos. Retorna o token. */
    public function abrirQr(int $id, int $minutos = 20): string
    {
        $token = bin2hex(random_bytes(12));
        $this->conn->prepare("UPDATE reunioes SET qr_token = ?, qr_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?")->execute([$token, $minutos, $id]);
        return $token;
    }

    public function fecharQr(int $id): void
    {
        $this->conn->prepare("UPDATE reunioes SET qr_token = NULL, qr_ate = NULL WHERE id = ?")->execute([$id]);
    }

    /** Marca a presença de quem escaneou (não desmarca nada). */
    public function marcarPresencaQr(int $id, int $usuarioId): bool
    {
        $st = $this->conn->prepare("SELECT presente FROM reuniao_presencas WHERE reuniao_id = ? AND usuario_id = ?");
        $st->execute([$id, $usuarioId]);
        if ((int) $st->fetchColumn() === 1) return false;   // já estava presente
        $this->conn->prepare("
            INSERT INTO reuniao_presencas (reuniao_id, usuario_id, presente, via, marcado_em) VALUES (?, ?, 1, 'qr', NOW())
            ON DUPLICATE KEY UPDATE presente = 1, via = 'qr', marcado_em = NOW()
        ")->execute([$id, $usuarioId]);
        return true;
    }

    /** Presentes pelo QR, mais recentes primeiro: [['nome', 'marcado_em'], ...] */
    public function presentesQr(int $id): array
    {
        $st = $this->conn->prepare("
            SELECT u.nome, p.marcado_em FROM reuniao_presencas p INNER JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.reuniao_id = ? AND p.presente = 1 AND p.via = 'qr' ORDER BY p.marcado_em DESC
        ");
        $st->execute([$id]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** [usuario_id => via] de quem está presente */
    public function viasPresenca(int $id): array
    {
        $st = $this->conn->prepare("SELECT usuario_id, via FROM reuniao_presencas WHERE reuniao_id = ? AND presente = 1");
        $st->execute([$id]);
        return $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function cancelar(int $id): void
    {
        $this->conn->prepare("UPDATE reunioes SET status = 'cancelada' WHERE id = ?")->execute([$id]);
    }

    /** [usuario_id => presente] */
    public function presencas(int $id): array
    {
        $st = $this->conn->prepare("SELECT usuario_id, presente FROM reuniao_presencas WHERE reuniao_id = ?");
        $st->execute([$id]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    /** Quem é convocado: os membros da diretoria (reunião de diretoria) ou todos da turma/grêmio. */
    public function convocados(array $reuniao): array
    {
        if ($reuniao['diretoria_id']) {
            $st = $this->conn->prepare("SELECT u.id, u.nome FROM gremio_diretoria_membros m INNER JOIN usuarios u ON u.id = m.usuario_id WHERE m.diretoria_id = ? ORDER BY u.nome");
            $st->execute([(int) $reuniao['diretoria_id']]);
        } else {
            $st = $this->conn->prepare("SELECT u.id, u.nome FROM turma_usuarios tu INNER JOIN usuarios u ON u.id = tu.usuario_id WHERE tu.turma_id = ? AND tu.ativo = 1 ORDER BY u.nome");
            $st->execute([(int) $reuniao['turma_id']]);
        }
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
