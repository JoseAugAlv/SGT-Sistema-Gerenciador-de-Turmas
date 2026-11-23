<?php
// app/Services/RenovacaoService.php
// Aviso automático de renovação com cupom. Quando o plano entra na janela de aviso (config aviso_vencimento_dias)
// ou já venceu e está na tolerância, a turma ganha um cupom exclusivo (RENOVA-XXXXXX, config renovacao_cupom_pct),
// de uso único e válido até o fim da tolerância; os representantes recebem notificação (e email, conforme a
// preferência de cada um). Roda uma vez por assinatura: o cupom fica gravado em assinaturas.cupom_renovacao_id.
// Não precisa de agendador: PlanoGuard chama verificar() quando calcula a situação da turma.
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/PrecoService.php';
require_once __DIR__ . '/../Models/Notificacao.php';

class RenovacaoService
{
    /** Já verificadas nesta requisição (evita repetir a consulta). */
    private static array $vistas = [];

    /**
     * Gera o cupom e avisa, se for a hora. $sit é o retorno de PlanoGuard::situacao().
     * Retorna o cupom de renovação da assinatura atual (array) ou null.
     */
    public static function verificar(array $turma, array $sit): ?array
    {
        $ass = $sit['assinatura'] ?? null;
        if (!$ass || $ass['status'] !== 'ativa' || !in_array($sit['codigo'], ['vencendo', 'tolerancia'], true)) return null;
        $aid = (int) $ass['id'];
        if (array_key_exists($aid, self::$vistas)) return self::$vistas[$aid];

        $pdo = Database::getConnection();
        $st = $pdo->prepare("SELECT cupom_renovacao_id FROM assinaturas WHERE id = ?");
        $st->execute([$aid]);
        $cupomId = (int) $st->fetchColumn();
        if ($cupomId) return self::$vistas[$aid] = self::cupom($cupomId);

        $pct = (float) PrecoService::config('renovacao_cupom_pct', '10');
        if ($pct <= 0) return self::$vistas[$aid] = null;
        $tid = (int) $turma['id'];

        // já pediu a renovação: não precisa de cupom
        $pend = $pdo->prepare("SELECT 1 FROM assinaturas WHERE turma_id = ? AND status = 'aguardando' AND renovacao_de IS NOT NULL");
        $pend->execute([$tid]);
        if ($pend->fetchColumn()) return self::$vistas[$aid] = null;

        $tol    = (int) PrecoService::config('tolerancia_dias', '5');
        $valido = date('Y-m-d', strtotime(date('Y-m-d', strtotime($ass['fim'])) . " +{$tol} days"));
        $existe = $pdo->prepare("SELECT 1 FROM cupons WHERE codigo = ?");
        do {
            $codigo = 'RENOVA-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $existe->execute([$codigo]);
        } while ($existe->fetchColumn());

        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO cupons (codigo, origem, turma_dona_id, tipo_plano, percentual, valido_ate, usos_max, usos, ativo)
                           VALUES (?, 'renovacao', ?, ?, ?, ?, 1, 0, 1)")
                ->execute([$codigo, $tid, ($turma['tipo'] ?? 'turma') === 'gremio' ? 'gremio' : 'turma', $pct, $valido]);
            $cupomId = (int) $pdo->lastInsertId();
            // só grava se ninguém gravou antes (duas abas ao mesmo tempo)
            $up = $pdo->prepare("UPDATE assinaturas SET cupom_renovacao_id = ? WHERE id = ? AND cupom_renovacao_id IS NULL");
            $up->execute([$cupomId, $aid]);
            if ($up->rowCount() === 0) { $pdo->rollBack(); return self::$vistas[$aid] = null; }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('RenovacaoService: ' . $e->getMessage());
            return self::$vistas[$aid] = null;
        }

        // avisa os representantes (o vice não cuida de pagamento)
        $pctTxt = rtrim(rtrim(number_format($pct, 2, ',', ''), '0'), ',') . '%';
        $fimTxt = date('d/m/Y', strtotime($ass['fim']));
        $titulo = $sit['codigo'] === 'tolerancia' ? 'Plano vencido: renove com desconto' : 'Seu plano está acabando: renove com desconto';
        $msg = "O plano {$ass['plano_nome']} da turma {$turma['nome']} " . ($sit['codigo'] === 'tolerancia' ? "venceu em {$fimTxt}" : "vence em {$fimTxt}")
             . ". Use o cupom {$codigo} e ganhe {$pctTxt} de desconto na renovação (válido até " . date('d/m/Y', strtotime($valido)) . ').';
        $reps = $pdo->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1");
        $reps->execute([$tid]);
        try {
            (new Notificacao())->criarParaVarios($reps->fetchAll(PDO::FETCH_COLUMN), 'alerta', $titulo, $msg, '/turmas/' . $tid . '/plano?cupom=' . $codigo . '#renovar');
        } catch (\Throwable $e) {
            error_log('RenovacaoService aviso: ' . $e->getMessage());
        }
        return self::$vistas[$aid] = self::cupom($cupomId);
    }

    /** Cupom de renovação pelo id (null se não existir ou estiver desativado). */
    public static function cupom(int $id): ?array
    {
        $st = Database::getConnection()->prepare("SELECT * FROM cupons WHERE id = ? AND ativo = 1");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
