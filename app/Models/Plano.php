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
    public function validarCupom(string $codigo, array $plano): array
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') return [null, null];

        $stmt = $this->conn->prepare("SELECT * FROM cupons WHERE codigo = ?");
        $stmt->execute([$codigo]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$c || !$c['ativo'])                                         return [null, "O cupom {$codigo} não existe ou foi desativado."];
        if ($c['valido_ate'] && $c['valido_ate'] < date('Y-m-d'))        return [null, "O cupom {$codigo} venceu em " . date('d/m/Y', strtotime($c['valido_ate'])) . '.'];
        if ($c['usos_max'] !== null && (int) $c['usos'] >= (int) $c['usos_max']) return [null, "O cupom {$codigo} já atingiu o limite de usos."];
        if ($c['tipo_plano'] !== 'todos' && $c['tipo_plano'] !== $plano['tipo']) {
            return [null, "O cupom {$codigo} não vale para planos de " . ($plano['tipo'] === 'gremio' ? 'grêmio' : 'turma') . '.'];
        }
        return [$c, null];
    }

    public function listarCupons(): array
    {
        return $this->conn->query("SELECT * FROM cupons ORDER BY ativo DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criarCupom(array $d): int
    {
        $this->conn->prepare("
            INSERT INTO cupons (codigo, tipo_plano, percentual, valido_ate, usos_max, criado_por)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$d['codigo'], $d['tipo_plano'], $d['percentual'], $d['valido_ate'], $d['usos_max'], $d['criado_por']]);
        return (int) $this->conn->lastInsertId();
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
