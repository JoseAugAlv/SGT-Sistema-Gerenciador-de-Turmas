<?php
// app/Models/Criterio.php
require_once __DIR__ . '/../Core/Model.php';

class Criterio extends Model
{
    const TIPOS = [
        'diretor'       => 'Diretor',
        'representante' => 'Representante',
        'pares'         => 'Pares',
        'autoavaliacao' => 'Autoavaliação',
        'coletiva'      => 'Coletiva',
        'misto'         => 'Misto (diretor + representante)',
    ];

    const APLICAVEIS = [
        'todos'                  => 'Todos os alunos',
        'apenas_diretores'       => 'Apenas diretores',
        'apenas_representantes'  => 'Apenas representantes',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'criterios';
    }

    public function listarPorProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT c.*,
                   e.nome AS etapa_nome,
                   (SELECT COUNT(*) FROM avaliacoes a WHERE a.criterio_id = c.id) AS total_avaliacoes,
                   (SELECT COUNT(*) FROM avaliacoes_coletivas ac WHERE ac.criterio_id = c.id) AS total_coletivas
            FROM criterios c
            LEFT JOIN projeto_etapas e ON e.id = c.etapa_id
            WHERE c.projeto_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, p.nome AS projeto_nome, p.turma_id, p.encerrado AS projeto_encerrado
            FROM criterios c
            INNER JOIN projetos p ON p.id = c.projeto_id
            WHERE c.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function somaPesos(int $projetoId): float
    {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(peso), 0) FROM criterios WHERE projeto_id = ?
        ");
        $stmt->execute([$projetoId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Marca como bloqueado todo critério cujo prazo já passou.
     * Chamar em qualquer leitura de listagem/detalhe.
     */
    public function atualizarBloqueios(int $projetoId): void
    {
        $this->conn->prepare("
            UPDATE criterios
            SET bloqueado = 1
            WHERE projeto_id = ?
              AND bloqueado = 0
              AND prazo_avaliacao IS NOT NULL
              AND prazo_avaliacao < NOW()
        ")->execute([$projetoId]);
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO criterios
                (projeto_id, etapa_id, nome, descricao, peso, tipo_avaliacao,
                 aplicavel_a, prazo_avaliacao)
            VALUES
                (:projeto_id, :etapa_id, :nome, :descricao, :peso, :tipo,
                 :aplicavel_a, :prazo)
        ");
        $stmt->execute([
            ':projeto_id'  => $d['projeto_id'],
            ':etapa_id'    => !empty($d['etapa_id'])        ? (int) $d['etapa_id'] : null,
            ':nome'        => $d['nome'],
            ':descricao'   => $d['descricao'] ?? null,
            ':peso'        => $d['peso'],
            ':tipo'        => $d['tipo_avaliacao'],
            ':aplicavel_a' => $d['aplicavel_a'] ?? 'todos',
            ':prazo'       => !empty($d['prazo_avaliacao']) ? $d['prazo_avaliacao'] : null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE criterios
            SET etapa_id = ?, nome = ?, descricao = ?, peso = ?,
                tipo_avaliacao = ?, aplicavel_a = ?, prazo_avaliacao = ?
            WHERE id = ?
        ")->execute([
            !empty($d['etapa_id'])        ? (int) $d['etapa_id'] : null,
            $d['nome'],
            $d['descricao'] ?? null,
            $d['peso'],
            $d['tipo_avaliacao'],
            $d['aplicavel_a'] ?? 'todos',
            !empty($d['prazo_avaliacao']) ? $d['prazo_avaliacao'] : null,
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM criterios WHERE id = ?")->execute([$id]);
    }

    public function coletarAvaliacoes(int $criterioId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM avaliacoes WHERE criterio_id = ?");
        $stmt->execute([$criterioId]);
        $individuais = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->conn->prepare("SELECT * FROM avaliacoes_coletivas WHERE criterio_id = ?");
        $stmt->execute([$criterioId]);
        $coletivas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'criterio'    => $this->porId($criterioId),
            'individuais' => $individuais,
            'coletivas'   => $coletivas,
        ];
    }

    public function reabrir(int $id, int $porUsuarioId, string $novoPrazo): bool
    {
        return $this->conn->prepare("
            UPDATE criterios
            SET bloqueado = 0,
                prazo_avaliacao = ?,
                reaberto_por = ?,
                reaberto_em = NOW()
            WHERE id = ?
        ")->execute([$novoPrazo, $porUsuarioId, $id]);
    }
}