<?php
// app/Models/Caixinha.php
// Caixinha anônima da turma. Nenhuma consulta para o representante devolve autor_id:
// o autor só é usado para avisá-lo da resposta e para o limite diário de envios.
require_once __DIR__ . '/../Core/Model.php';

class Caixinha extends Model
{
    const TIPOS = [
        'duvida'     => ['Dúvida', 'fa-circle-question'],
        'sugestao'   => ['Sugestão', 'fa-lightbulb'],
        'reclamacao' => ['Reclamação', 'fa-triangle-exclamation'],
        'elogio'     => ['Elogio', 'fa-heart'],
    ];
    const LIMITE_DIA = 5;

    /** Colunas seguras (sem autor) para quem lê a caixinha. */
    private const COLUNAS = 'id, turma_id, tipo, texto, status, resposta, respondida_em, publica, created_at';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'caixinha_mensagens';
    }

    public function enviar(int $turmaId, int $autorId, string $tipo, string $texto): int
    {
        $this->conn->prepare("INSERT INTO caixinha_mensagens (turma_id, autor_id, tipo, texto) VALUES (?, ?, ?, ?)")
                   ->execute([$turmaId, $autorId, $tipo, $texto]);
        return (int) $this->conn->lastInsertId();
    }

    public function enviadasHoje(int $turmaId, int $autorId): int
    {
        $st = $this->conn->prepare("SELECT COUNT(*) FROM caixinha_mensagens WHERE turma_id = ? AND autor_id = ? AND created_at >= CURDATE()");
        $st->execute([$turmaId, $autorId]);
        return (int) $st->fetchColumn();
    }

    /** Caixa de entrada do representante (sem autor). $filtro: abertas | respondidas | arquivadas */
    public function entrada(int $turmaId, string $filtro = 'abertas'): array
    {
        $cond = ['abertas' => "status IN ('nova','lida')", 'respondidas' => "status = 'respondida'", 'arquivadas' => "status = 'arquivada'"][$filtro] ?? "status IN ('nova','lida')";
        $st = $this->conn->prepare("SELECT " . self::COLUNAS . " FROM caixinha_mensagens WHERE turma_id = ? AND {$cond} ORDER BY created_at DESC");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contagem(int $turmaId): array
    {
        $st = $this->conn->prepare("SELECT status, COUNT(*) n FROM caixinha_mensagens WHERE turma_id = ? GROUP BY status");
        $st->execute([$turmaId]);
        $c = ['nova' => 0, 'lida' => 0, 'respondida' => 0, 'arquivada' => 0];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $c[$r['status']] = (int) $r['n'];
        return $c;
    }

    /** Mensagens do próprio autor (para ele acompanhar as respostas). */
    public function minhas(int $turmaId, int $autorId): array
    {
        $st = $this->conn->prepare("SELECT " . self::COLUNAS . " FROM caixinha_mensagens WHERE turma_id = ? AND autor_id = ? ORDER BY created_at DESC LIMIT 30");
        $st->execute([$turmaId, $autorId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Perguntas e respostas publicadas para a turma. */
    public function publicas(int $turmaId): array
    {
        $st = $this->conn->prepare("SELECT " . self::COLUNAS . " FROM caixinha_mensagens WHERE turma_id = ? AND publica = 1 AND status = 'respondida' ORDER BY respondida_em DESC LIMIT 50");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT " . self::COLUNAS . " FROM caixinha_mensagens WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Só para avisar o autor da resposta (nunca vai para a tela). */
    public function autorDe(int $id): ?int
    {
        $st = $this->conn->prepare("SELECT autor_id FROM caixinha_mensagens WHERE id = ?");
        $st->execute([$id]);
        $a = $st->fetchColumn();
        return $a ? (int) $a : null;
    }

    public function marcarLidas(int $turmaId): void
    {
        $this->conn->prepare("UPDATE caixinha_mensagens SET status = 'lida' WHERE turma_id = ? AND status = 'nova'")->execute([$turmaId]);
    }

    public function responder(int $id, string $resposta, bool $publica, int $porUsuario): void
    {
        $this->conn->prepare("UPDATE caixinha_mensagens SET resposta = ?, publica = ?, status = 'respondida', respondida_por = ?, respondida_em = NOW() WHERE id = ?")
                   ->execute([$resposta, $publica ? 1 : 0, $porUsuario, $id]);
    }

    public function arquivar(int $id, bool $arquivar = true): void
    {
        $this->conn->prepare("UPDATE caixinha_mensagens SET status = IF(?, 'arquivada', IF(resposta IS NULL, 'lida', 'respondida')) WHERE id = ?")
                   ->execute([$arquivar ? 1 : 0, $id]);
    }
}
