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

    // Tipos em que o próprio aluno preenche (os únicos que podem ser obrigatórios)
    const TIPOS_DO_ALUNO = ['pares', 'autoavaliacao'];

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
        $this->fecharVencidos($projetoId);
    }

    /**
     * Bloqueia os critérios com prazo vencido (de um projeto ou de todos) e, nos obrigatórios,
     * avisa o representante de quem não preencheu.
     */
    public function fecharVencidos(?int $projetoId = null): void
    {
        $filtro = $projetoId ? 'AND c.projeto_id = ?' : '';
        $stmt = $this->conn->prepare("
            SELECT c.*, p.turma_id FROM criterios c
            INNER JOIN projetos p ON p.id = c.projeto_id
            WHERE c.bloqueado = 0 AND c.prazo_avaliacao IS NOT NULL AND c.prazo_avaliacao < NOW() $filtro
        ");
        $stmt->execute($projetoId ? [$projetoId] : []);
        $vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$vencidos) return;

        $upd = $this->conn->prepare("UPDATE criterios SET bloqueado = 1 WHERE id = ? AND bloqueado = 0");
        foreach ($vencidos as $c) {
            $upd->execute([(int) $c['id']]);
            // rowCount = 0: outra requisição já fechou (e já avisou)
            if ($upd->rowCount() > 0 && !empty($c['obrigatorio'])) {
                $this->avisarPrazoFechado($c);
            }
        }
    }

    private function avisarPrazoFechado(array $c): void
    {
        require_once __DIR__ . '/Notificacao.php';
        $faltam = $this->quemFaltaPreencher($c, (int) $c['turma_id']);

        $st = $this->conn->prepare("
            SELECT usuario_id FROM turma_usuarios
            WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
        ");
        $st->execute([(int) $c['turma_id']]);
        $reps = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

        $notif = new Notificacao();
        $msg = empty($faltam)
            ? "O prazo do critério obrigatório '{$c['nome']}' fechou e todos preencheram."
            : "O prazo do critério obrigatório '{$c['nome']}' fechou. " . count($faltam)
              . " aluno(s) não preencheram. Você pode aplicar um desconto na nota deles.";
        foreach ($reps as $uid) {
            $notif->criar($uid, 'avaliacao',
                empty($faltam) ? 'Critério obrigatório concluído' : 'Alunos não preencheram um critério obrigatório',
                $msg, '/projetos/' . (int) $c['projeto_id'] . '/criterios', (int) $c['id']);
        }
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO criterios
                (projeto_id, etapa_id, nome, descricao, peso, tipo_avaliacao,
                 aplicavel_a, prazo_avaliacao, obrigatorio)
            VALUES
                (:projeto_id, :etapa_id, :nome, :descricao, :peso, :tipo,
                 :aplicavel_a, :prazo, :obrigatorio)
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
            ':obrigatorio' => !empty($d['obrigatorio']) ? 1 : 0,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE criterios
            SET etapa_id = ?, nome = ?, descricao = ?, peso = ?,
                tipo_avaliacao = ?, aplicavel_a = ?, prazo_avaliacao = ?, obrigatorio = ?,
                -- o bloqueio acompanha o prazo: prazo novo no futuro (ou sem prazo) reabre
                bloqueado = (prazo_avaliacao IS NOT NULL AND prazo_avaliacao < NOW())
            WHERE id = ?
        ")->execute([
            !empty($d['etapa_id'])        ? (int) $d['etapa_id'] : null,
            $d['nome'],
            $d['descricao'] ?? null,
            $d['peso'],
            $d['tipo_avaliacao'],
            $d['aplicavel_a'] ?? 'todos',
            !empty($d['prazo_avaliacao']) ? $d['prazo_avaliacao'] : null,
            !empty($d['obrigatorio']) ? 1 : 0,
            $id,
        ]);
    }

    /**
     * Quem ainda não terminou de preencher um critério de pares/autoavaliação.
     * Mesma regra das pendências do aluno: pares = algum colega de grupo ainda sem nota;
     * autoavaliação = ainda não se avaliou. Só conta quem está ativo na turma.
     * Retorna [usuario_id => nome].
     */
    public function quemFaltaPreencher(array $c, int $turmaId): array
    {
        $st = $this->conn->prepare("
            SELECT u.id, u.nome FROM turma_usuarios tu
            INNER JOIN usuarios u ON u.id = tu.usuario_id
            WHERE tu.turma_id = ? AND tu.ativo = 1
        ");
        $st->execute([$turmaId]);
        $ativos = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $ativos[(int) $r['id']] = $r['nome'];

        $faltam = [];
        if ($c['tipo_avaliacao'] === 'autoavaliacao') {
            $st = $this->conn->prepare("SELECT DISTINCT aluno_id FROM avaliacoes WHERE criterio_id = ? AND tipo = 'auto'");
            $st->execute([(int) $c['id']]);
            $feitos = array_flip(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));
            foreach ($ativos as $uid => $nome) if (!isset($feitos[$uid])) $faltam[$uid] = $nome;

        } elseif ($c['tipo_avaliacao'] === 'pares') {
            // Membros atuais dos grupos do projeto
            $st = $this->conn->prepare("
                SELECT ga.grupo_id, ga.usuario_id FROM grupo_alunos ga
                INNER JOIN grupos g ON g.id = ga.grupo_id
                WHERE g.projeto_id = ? AND ga.saiu_em IS NULL
            ");
            $st->execute([(int) $c['projeto_id']]);
            $grupos = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if (isset($ativos[(int) $r['usuario_id']])) $grupos[(int) $r['grupo_id']][] = (int) $r['usuario_id'];
            }
            $st = $this->conn->prepare("SELECT avaliador_id, aluno_id FROM avaliacoes WHERE criterio_id = ? AND tipo = 'par'");
            $st->execute([(int) $c['id']]);
            $dadas = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $dadas[(int) $r['avaliador_id']][(int) $r['aluno_id']] = true;

            foreach ($grupos as $membros) {
                foreach ($membros as $uid) {
                    foreach ($membros as $outro) {
                        if ($outro !== $uid && empty($dadas[$uid][$outro])) { $faltam[$uid] = $ativos[$uid]; break; }
                    }
                }
            }
        }
        asort($faltam);
        return $faltam;
    }

    public function totalAvaliacoes(int $id): int
    {
        $stmt = $this->conn->prepare("
            SELECT (SELECT COUNT(*) FROM avaliacoes WHERE criterio_id = ?)
                 + (SELECT COUNT(*) FROM avaliacoes_coletivas WHERE criterio_id = ?)
        ");
        $stmt->execute([$id, $id]);
        return (int) $stmt->fetchColumn();
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