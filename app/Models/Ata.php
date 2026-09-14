<?php
// app/Models/Ata.php
require_once __DIR__ . '/../Core/Model.php';

class Ata extends Model
{
    const STATUS = ['pendente', 'preenchida', 'revisada'];

    const TEMAS_RELATORIO = [
        'falta_compromisso'   => 'Falta de compromisso',
        'nao_ajudou'          => 'Não ajudou',
        'colaborou_outro_grupo' => 'Colaborou sendo de outro grupo',
        'lideranca'           => 'Liderança',
        'proatividade'        => 'Proatividade',
        'dificuldade_tecnica' => 'Dificuldade técnica',
        'conflito'            => 'Conflito',
        'entrega_fora_prazo'  => 'Entrega fora do prazo',
        'qualidade_trabalho'  => 'Qualidade do trabalho',
        'outro'               => 'Outro',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'atas';
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*,
                   g.nome AS grupo_nome, g.projeto_id,
                   p.nome AS projeto_nome, p.turma_id,
                   u.nome AS diretor_nome, u.email AS diretor_email,
                   r.nome AS representante_nome, r.email AS representante_email
            FROM atas a
            INNER JOIN grupos g ON g.id = a.grupo_id
            INNER JOIN projetos p ON p.id = g.projeto_id
            LEFT JOIN usuarios u ON u.id = a.diretor_id
            LEFT JOIN usuarios r ON r.id = a.representante_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarPorProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, g.nome AS grupo_nome,
                   u.nome AS diretor_nome
            FROM atas a
            INNER JOIN grupos g ON g.id = a.grupo_id
            LEFT JOIN usuarios u ON u.id = a.diretor_id
            WHERE g.projeto_id = ?
            ORDER BY a.data_ata DESC, a.id DESC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atas pendentes em que o usuário é o diretor designado.
     */
    public function listarPendentesDoDiretor(int $projetoId, int $diretorId): array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, g.nome AS grupo_nome
            FROM atas a
            INNER JOIN grupos g ON g.id = a.grupo_id
            WHERE g.projeto_id = ? AND a.diretor_id = ? AND a.status = 'pendente'
            ORDER BY a.prazo_preenchimento ASC, a.data_ata DESC
        ");
        $stmt->execute([$projetoId, $diretorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atas preenchidas aguardando validação do representante.
     */
    public function listarPreenchidasDoProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, g.nome AS grupo_nome, u.nome AS diretor_nome
            FROM atas a
            INNER JOIN grupos g ON g.id = a.grupo_id
            LEFT JOIN usuarios u ON u.id = a.diretor_id
            WHERE g.projeto_id = ? AND a.status = 'preenchida'
            ORDER BY a.updated_at DESC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atas em que o aluno participou (via ata_participantes).
     */
    public function listarDoParticipante(int $projetoId, int $alunoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, g.nome AS grupo_nome, ap.presente, ap.justificativa
            FROM atas a
            INNER JOIN grupos g ON g.id = a.grupo_id
            INNER JOIN ata_participantes ap ON ap.ata_id = a.id
            WHERE g.projeto_id = ? AND ap.aluno_id = ?
            ORDER BY a.data_ata DESC, a.id DESC
        ");
        $stmt->execute([$projetoId, $alunoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO atas
                (titulo, descricao, grupo_id, diretor_id, representante_id,
                 data_ata, prazo_preenchimento, horario_inicio, horario_fim)
            VALUES
                (:titulo, :descricao, :grupo_id, :diretor_id, :representante_id,
                 :data_ata, :prazo, :hi, :hf)
        ");
        $stmt->execute([
            ':titulo'           => $d['titulo'],
            ':descricao'        => $d['descricao'] ?? null,
            ':grupo_id'         => $d['grupo_id'],
            ':diretor_id'       => $d['diretor_id'],
            ':representante_id' => $d['representante_id'],
            ':data_ata'         => $d['data_ata'],
            ':prazo'            => $d['prazo_preenchimento'] ?? null,
            ':hi'               => $d['horario_inicio'] ?? null,
            ':hf'               => $d['horario_fim'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizarBasico(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE atas
            SET titulo = ?, descricao = ?, data_ata = ?,
                prazo_preenchimento = ?, horario_inicio = ?, horario_fim = ?
            WHERE id = ?
        ")->execute([
            $d['titulo'],
            $d['descricao'] ?? null,
            $d['data_ata'],
            $d['prazo_preenchimento'] ?? null,
            $d['horario_inicio'] ?? null,
            $d['horario_fim'] ?? null,
            $id,
        ]);
    }

    public function definirStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUS, true)) return false;
        return $this->conn->prepare("UPDATE atas SET status = ? WHERE id = ?")
                          ->execute([$status, $id]);
    }

    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM atas WHERE id = ?")->execute([$id]);
    }

        /**
     * Atualiza a ata incluindo troca de grupo.
     * Recalcula o diretor_id para o diretor ativo do novo grupo.
     */
    public function atualizarComGrupo(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE atas
            SET titulo = ?, descricao = ?, data_ata = ?,
                prazo_preenchimento = ?, horario_inicio = ?, horario_fim = ?,
                grupo_id = ?, diretor_id = ?
            WHERE id = ?
        ")->execute([
            $d['titulo'],
            $d['descricao'] ?? null,
            $d['data_ata'],
            $d['prazo_preenchimento'] ?? null,
            $d['horario_inicio'] ?? null,
            $d['horario_fim'] ?? null,
            (int) $d['grupo_id'],
            (int) $d['diretor_id'],
            $id,
        ]);
    }
}