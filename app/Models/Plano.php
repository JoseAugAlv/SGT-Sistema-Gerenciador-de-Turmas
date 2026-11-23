<?php
// app/Models/Plano.php
// Planos de turma e de grêmio (preços de precos.md) e cupons de desconto.
require_once __DIR__ . '/../Core/Model.php';

class Plano extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'planos';
    }

    public function listar(?string $tipo = null, bool $soAtivos = true): array
    {
        $sql = "SELECT * FROM planos WHERE 1=1"
             . ($tipo ? " AND tipo = ?" : "")
             . ($soAtivos ? " AND ativo = 1" : "")
             . " ORDER BY ordem";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($tipo ? [$tipo] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM planos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ============ CUPONS ============

    /**
     * Cupom pelo código, se puder ser usado neste plano. Retorna [cupom|null, mensagem de erro|null].
     */
    /**
     * Cupom pelo código, se puder ser usado neste plano (e por esta turma, quando informada).
     * Retorna [cupom com 'regras' por plano | null, mensagem de erro | null].
     */
    public function validarCupom(string $codigo, array $plano, ?int $turmaId = null): array
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') return [null, null];

        $c = $this->cupomPorCodigo($codigo);

        if (!$c || !$c['ativo'])                                         return [null, "O cupom {$codigo} não existe ou foi desativado."];
        if ($c['valido_ate'] && $c['valido_ate'] < date('Y-m-d'))        return [null, "O cupom {$codigo} venceu em " . date('d/m/Y', strtotime($c['valido_ate'])) . '.'];
        if ($c['usos_max'] !== null && (int) $c['usos'] >= (int) $c['usos_max']) return [null, "O cupom {$codigo} já foi usado."];
        if ($c['tipo_plano'] !== 'todos' && $c['tipo_plano'] !== $plano['tipo']) {
            return [null, "O cupom {$codigo} não vale para planos de " . ($plano['tipo'] === 'gremio' ? 'grêmio' : 'turma') . '.'];
        }
        if ($c['regras'] && !isset($c['regras'][(int) $plano['id']])) {
            return [null, "O cupom {$codigo} não vale para o plano {$plano['nome']}."];
        }
        if ($c['origem'] === 'renovacao' && (!$turmaId || (int) $c['turma_dona_id'] !== $turmaId)) {
            return [null, "O cupom {$codigo} é exclusivo da renovação de outra turma."];
        }
        if ($c['origem'] === 'indicacao') {
            if ($turmaId && (int) $c['turma_dona_id'] === $turmaId) return [null, 'Esse é o código de indicação da sua própria turma: passe para outra turma usar.'];
            if ($turmaId && $this->turmaJaComprou($turmaId))        return [null, 'O código de indicação vale só na primeira compra da turma.'];
        }
        return [$c, null];
    }

    public function cupomPorCodigo(string $codigo): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM cupons WHERE codigo = ?");
        $stmt->execute([strtoupper(trim($codigo))]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$c) return null;
        $c['regras'] = $this->regrasDoCupom((int) $c['id']);
        return $c;
    }

    /** Desconto do cupom em cada plano: [plano_id => ['tipo_desconto', 'valor']]. Vazio = regra antiga. */
    public function regrasDoCupom(int $cupomId): array
    {
        $stmt = $this->conn->prepare("SELECT plano_id, tipo_desconto, valor FROM cupom_planos WHERE cupom_id = ?");
        $stmt->execute([$cupomId]);
        $r = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $l) $r[(int) $l['plano_id']] = $l;
        return $r;
    }

    /** Troca as regras por plano do cupom. $regras: [plano_id => ['tipo_desconto' => pct|valor, 'valor' => x]] */
    public function salvarRegras(int $cupomId, array $regras): void
    {
        $this->conn->prepare("DELETE FROM cupom_planos WHERE cupom_id = ?")->execute([$cupomId]);
        $ins = $this->conn->prepare("INSERT INTO cupom_planos (cupom_id, plano_id, tipo_desconto, valor) VALUES (?, ?, ?, ?)");
        foreach ($regras as $pid => $r) $ins->execute([$cupomId, (int) $pid, $r['tipo_desconto'], $r['valor']]);
    }

    /** A turma já teve algum plano pago (ativa, trocada ou encerrada depois de ativar)? */
    public function turmaJaComprou(int $turmaId): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM assinaturas WHERE turma_id = ? AND inicio IS NOT NULL LIMIT 1");
        $stmt->execute([$turmaId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Cupons criados à mão pelo master (os de pacote e de indicação têm telas próprias), com as regras por plano. */
    public function listarCupons(): array
    {
        $lista = $this->conn->query("SELECT * FROM cupons WHERE origem = 'manual' ORDER BY ativo DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lista as &$c) $c['regras'] = $this->regrasDoCupom((int) $c['id']);
        return $lista;
    }

    public function criarCupom(array $d): int
    {
        $this->conn->prepare("
            INSERT INTO cupons (codigo, origem, pacote_id, turma_dona_id, tipo_plano, percentual, valido_ate, usos_max, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$d['codigo'], $d['origem'] ?? 'manual', $d['pacote_id'] ?? null, $d['turma_dona_id'] ?? null,
                     $d['tipo_plano'], $d['percentual'], $d['valido_ate'], $d['usos_max'], $d['criado_por']]);
        return (int) $this->conn->lastInsertId();
    }

    public function codigoCupomExiste(string $codigo): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM cupons WHERE codigo = ?");
        $stmt->execute([$codigo]);
        return (bool) $stmt->fetchColumn();
    }

    // ============ PACOTES (ESCOLAS) ============

    public function criarPacote(array $d): int
    {
        $this->conn->prepare("INSERT INTO pacotes (nome, plano_id, quantidade, valido_ate, criado_por) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$d['nome'], $d['plano_id'], $d['quantidade'], $d['valido_ate'], $d['criado_por']]);
        return (int) $this->conn->lastInsertId();
    }

    /** Pacotes com plano e quantos códigos já foram usados. */
    public function listarPacotes(): array
    {
        return $this->conn->query("
            SELECT pk.*, p.nome AS plano_nome,
                   (SELECT COUNT(*) FROM cupons c WHERE c.pacote_id = pk.id AND c.usos > 0) AS usados
            FROM pacotes pk INNER JOIN planos p ON p.id = pk.plano_id
            ORDER BY pk.ativo DESC, pk.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Códigos de um pacote e qual turma usou cada um. */
    public function cuponsDoPacote(int $pacoteId): array
    {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.codigo, c.usos, c.ativo,
                   (SELECT t.nome FROM assinaturas a INNER JOIN turmas t ON t.id = a.turma_id
                    WHERE a.cupom_id = c.id AND a.status <> 'cancelada' ORDER BY a.id DESC LIMIT 1) AS turma_nome
            FROM cupons c WHERE c.pacote_id = ? ORDER BY c.codigo
        ");
        $stmt->execute([$pacoteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Liga/desliga o pacote e todos os códigos ainda não usados. */
    public function alternarPacote(int $id): void
    {
        $this->conn->prepare("UPDATE pacotes SET ativo = 1 - ativo WHERE id = ?")->execute([$id]);
        $this->conn->prepare("UPDATE cupons c INNER JOIN pacotes p ON p.id = c.pacote_id SET c.ativo = p.ativo WHERE p.id = ? AND c.usos = 0")->execute([$id]);
    }

    public function cupomPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM cupons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function atualizarCupom(int $id, array $d): void
    {
        $this->conn->prepare("UPDATE cupons SET tipo_plano = ?, percentual = ?, valido_ate = ?, usos_max = ? WHERE id = ?")
                   ->execute([$d['tipo_plano'], $d['percentual'], $d['valido_ate'], $d['usos_max'], $id]);
    }

    /** Exclui o cupom só se nunca foi usado em nenhuma compra (senão, desative). */
    public function excluirCupom(int $id): bool
    {
        $uso = $this->conn->prepare("SELECT COUNT(*) FROM assinaturas WHERE cupom_id = ?");
        $uso->execute([$id]);
        if ((int) $uso->fetchColumn() > 0) return false;
        $this->conn->prepare("DELETE FROM cupons WHERE id = ?")->execute([$id]);
        return true;
    }

    // ============ PLANOS (MASTER) ============

    public function codigoExiste(string $codigo): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM planos WHERE codigo = ?");
        $stmt->execute([$codigo]);
        return (bool) $stmt->fetchColumn();
    }

    public function criarPlano(array $d): int
    {
        $this->conn->prepare("
            INSERT INTO planos (codigo, tipo, nome, meses, projetos, pessoas_max, preco, piso, max_parcelas, tabela_parcelas, inclui_dia_a_dia, preco_dia_a_dia, ordem, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$d['codigo'], $d['tipo'], $d['nome'], $d['meses'], $d['projetos'], $d['pessoas_max'],
                     $d['preco'], $d['piso'], $d['max_parcelas'], $d['tabela_parcelas'], $d['inclui_dia_a_dia'] ?? 1, $d['preco_dia_a_dia'] ?? null, $d['ordem'], $d['ativo']]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizarPlano(int $id, array $d): void
    {
        $this->conn->prepare("
            UPDATE planos SET nome = ?, meses = ?, preco = ?, piso = ?, max_parcelas = ?, projetos = ?, pessoas_max = ?, ordem = ?, ativo = ?,
                   inclui_dia_a_dia = ?, preco_dia_a_dia = ?
            WHERE id = ?
        ")->execute([$d['nome'], $d['meses'], $d['preco'], $d['piso'], $d['max_parcelas'], $d['projetos'],
                     $d['pessoas_max'], $d['ordem'], $d['ativo'], $d['inclui_dia_a_dia'] ?? 1, $d['preco_dia_a_dia'] ?? null, $id]);
    }

    public function alternarCupom(int $id): void
    {
        $this->conn->prepare("UPDATE cupons SET ativo = 1 - ativo WHERE id = ?")->execute([$id]);
    }

    public function usarCupom(int $id): void
    {
        $this->conn->prepare("UPDATE cupons SET usos = usos + 1 WHERE id = ?")->execute([$id]);
    }
}
