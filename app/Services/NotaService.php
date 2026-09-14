<?php
// app/Services/NotaService.php

require_once __DIR__ . '/../Config/database.php';

class NotaService
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    // ============ CONVERSÃO CONCEITO <-> VALOR ============

    public static function valorPadraoPorConceito(string $conceito, array $cfg): float
    {
        switch ($conceito) {
            case 'I':  return (float) $cfg['limite_i_max'];
            case 'R':  return ((float) $cfg['limite_r_min'] + (float) $cfg['limite_r_max']) / 2;
            case 'B':  return ((float) $cfg['limite_b_min'] + (float) $cfg['limite_b_max']) / 2;
            case 'MB': return (float) $cfg['limite_mb_min'];
            default:   return 0.0;
        }
    }

    public static function conceitoPorValor(float $valor, array $cfg): string
    {
        if ($valor <= (float) $cfg['limite_i_max']) return 'I';
        if ($valor <= (float) $cfg['limite_r_max']) return 'R';
        if ($valor <= (float) $cfg['limite_b_max']) return 'B';
        return 'MB';
    }

    // ============ NOTA DO CRITÉRIO ============

    /**
     * Retorna a nota final do critério para o aluno (ou null se ainda não há avaliações).
     */
    public function notaDoCriterio(int $criterioId, int $alunoId): ?float
    {
        $criterio = $this->buscarCriterio($criterioId);
        if (!$criterio) return null;

        switch ($criterio['tipo_avaliacao']) {
            case 'coletiva':      return $this->notaColetiva($criterio, $alunoId);
            case 'diretor':       return $this->notaDiretor($criterio, $alunoId);
            case 'representante': return $this->notaRepresentante($criterio, $alunoId);
            case 'pares':         return $this->notaPares($criterioId, $alunoId);
            case 'autoavaliacao': return $this->notaAuto($criterioId, $alunoId);
            case 'misto':         return $this->notaMisto($criterio, $alunoId);
        }
        return null;
    }

    private function notaColetiva(array $criterio, int $alunoId): ?float
    {
        // Pega grupos do aluno no projeto do critério
        $stmt = $this->conn->prepare("
            SELECT ga.grupo_id
            FROM grupo_alunos ga
            INNER JOIN grupos g ON g.id = ga.grupo_id
            WHERE ga.usuario_id = ? AND g.projeto_id = ? AND ga.saiu_em IS NULL
        ");
        $stmt->execute([$alunoId, (int) $criterio['projeto_id']]);
        $grupos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($grupos)) return null;

        $valores = [];
        foreach ($grupos as $gid) {
            $stmt = $this->conn->prepare("
                SELECT valor_numerico FROM avaliacoes_coletivas
                WHERE criterio_id = ? AND grupo_id = ?
            ");
            $stmt->execute([(int) $criterio['id'], $gid]);
            $vals = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($vals as $v) $valores[] = (float) $v;
        }

        return empty($valores) ? null : array_sum($valores) / count($valores);
    }

    private function notaDiretor(array $criterio, int $alunoId): ?float
    {
        // Diretores ativos de TODOS os grupos do aluno no projeto
        $stmt = $this->conn->prepare("
            SELECT DISTINCT gd.usuario_id
            FROM grupo_alunos ga
            INNER JOIN grupos g ON g.id = ga.grupo_id
            INNER JOIN grupo_diretores gd ON gd.grupo_id = g.id AND gd.ativo = 1
            WHERE ga.usuario_id = ? AND g.projeto_id = ? AND ga.saiu_em IS NULL
        ");
        $stmt->execute([$alunoId, (int) $criterio['projeto_id']]);
        $diretores = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Se o aluno é o único diretor, delegar para representante
        $stmt = $this->conn->prepare("
            SELECT DISTINCT gd.grupo_id
            FROM grupo_alunos ga
            INNER JOIN grupos g ON g.id = ga.grupo_id
            INNER JOIN grupo_diretores gd ON gd.grupo_id = g.id AND gd.ativo = 1
            WHERE ga.usuario_id = ? AND g.projeto_id = ?
              AND (SELECT COUNT(*) FROM grupo_diretores WHERE grupo_id = gd.grupo_id AND ativo = 1) = 1
              AND gd.usuario_id = ?
        ");
        $stmt->execute([$alunoId, (int) $criterio['projeto_id'], $alunoId]);
        $gruposDelegar = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Remove a auto-avaliação dos diretores
        $diretores = array_filter($diretores, fn($d) => (int) $d !== $alunoId);

        if (empty($diretores) && empty($gruposDelegar)) return null;

        $valores = [];
        foreach ($diretores as $avaliadorId) {
            $stmt = $this->conn->prepare("
                SELECT valor_numerico FROM avaliacoes
                WHERE criterio_id = ? AND aluno_id = ? AND avaliador_id = ? AND tipo = 'diretor'
            ");
            $stmt->execute([(int) $criterio['id'], $alunoId, $avaliadorId]);
            $v = $stmt->fetchColumn();
            if ($v !== false) $valores[] = (float) $v;
        }

        // Delegação para representante (aluno é único diretor)
        if (!empty($gruposDelegar)) {
            $stmt = $this->conn->prepare("
                SELECT valor_numerico FROM avaliacoes
                WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'representante'
            ");
            $stmt->execute([(int) $criterio['id'], $alunoId]);
            $v = $stmt->fetchColumn();
            if ($v !== false) $valores[] = (float) $v;
        }

        return empty($valores) ? null : array_sum($valores) / count($valores);
    }

    private function notaRepresentante(array $criterio, int $alunoId): ?float
    {
        $stmt = $this->conn->prepare("
            SELECT valor_numerico FROM avaliacoes
            WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'representante'
        ");
        $stmt->execute([(int) $criterio['id'], $alunoId]);
        $valores = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return empty($valores) ? null : array_sum($valores) / count($valores);
    }

    private function notaPares(int $criterioId, int $alunoId): ?float
    {
        $stmt = $this->conn->prepare("
            SELECT valor_numerico FROM avaliacoes
            WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'par'
        ");
        $stmt->execute([$criterioId, $alunoId]);
        $valores = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return empty($valores) ? null : array_sum($valores) / count($valores);
    }

    private function notaAuto(int $criterioId, int $alunoId): ?float
    {
        $stmt = $this->conn->prepare("
            SELECT valor_numerico FROM avaliacoes
            WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'auto'
            LIMIT 1
        ");
        $stmt->execute([$criterioId, $alunoId]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (float) $v;
    }

    private function notaMisto(array $criterio, int $alunoId): ?float
    {
        $stmt = $this->conn->prepare("
            SELECT valor_numerico FROM avaliacoes
            WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'diretor'
        ");
        $stmt->execute([(int) $criterio['id'], $alunoId]);
        $dir = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->conn->prepare("
            SELECT valor_numerico FROM avaliacoes
            WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'representante'
        ");
        $stmt->execute([(int) $criterio['id'], $alunoId]);
        $rep = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($dir) && empty($rep)) return null;
        $mDir = empty($dir) ? 0 : array_sum($dir) / count($dir);
        $mRep = empty($rep) ? 0 : array_sum($rep) / count($rep);

        if (empty($dir)) return $mRep;
        if (empty($rep)) return $mDir;
        return $mDir * 0.5 + $mRep * 0.5;
    }

    // ============ NOTA FINAL DO PROJETO ============

    /**
     * Retorna ['nota_criterios'=>[['criterio'=>..., 'nota'=>float|null], ...],
     *           'media_ponderada'=>float|null, 'conceito_final'=>string|null]
     */
    public function boletimDoAluno(int $projetoId, int $alunoId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM criterios WHERE projeto_id = ? ORDER BY created_at ASC");
        $stmt->execute([$projetoId]);
        $criterios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cfg = $this->carregarCfg($projetoId);

        $linhas = [];
        $somaPeso = 0.0;
        $somaNotaPeso = 0.0;

        foreach ($criterios as $c) {
            $nota = $this->notaDoCriterio((int) $c['id'], $alunoId);
            $peso = (float) $c['peso'];

            $linhas[] = [
                'criterio' => $c,
                'nota'     => $nota,
                'conceito' => $nota === null ? null : self::conceitoPorValor($nota, $cfg),
            ];

            if ($nota !== null) {
                $somaNotaPeso += $nota * $peso;
                $somaPeso     += $peso;
            }
        }

        $media = $somaPeso > 0 ? $somaNotaPeso / $somaPeso : null;

        return [
            'linhas'          => $linhas,
            'media_ponderada' => $media,
            'conceito_final'  => $media === null ? null : self::conceitoPorValor($media, $cfg),
        ];
    }

    public function carregarCfg(int $projetoId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM configuracoes_conceito WHERE projeto_id = ? LIMIT 1");
        $stmt->execute([$projetoId]);
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cfg) {
            // defaults do schema
            $cfg = [
                'limite_i_max' => 49, 'limite_r_min' => 50, 'limite_r_max' => 69,
                'limite_b_min' => 70, 'limite_b_max' => 84, 'limite_mb_min' => 85,
                'visibilidade_diretor' => 'grupo', 'visibilidade_aluno' => 'proprio',
            ];
        }
        return $cfg;
    }

    private function buscarCriterio(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM criterios WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}