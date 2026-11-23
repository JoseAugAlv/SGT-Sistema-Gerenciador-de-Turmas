<?php
// app/Services/PagamentoService.php
// Confirma um pagamento recebido (Pix real do Mercado Pago): marca a parcela como paga e faz o mesmo que o
// pagamento simulado faz — ativa o plano na 1ª parcela (ou libera o Dia a dia no adicional), usa o cupom,
// credita a indicação, registra na auditoria e avisa os representantes. Idempotente: parcela já paga não muda.
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Assinatura.php';
require_once __DIR__ . '/../Models/Plano.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/IndicacaoService.php';

class PagamentoService
{
    /** Retorna true se confirmou agora (false se já estava pago ou não existe). */
    public static function confirmar(int $pagamentoId, string $metodo = 'pix', ?int $porUsuario = null): bool
    {
        $ass = new Assinatura();
        $pag = $ass->pagamento($pagamentoId);
        if (!$pag || $pag['status'] !== 'pendente') return false;
        $assin = $ass->porId((int) $pag['assinatura_id']);
        if (!$assin || $assin['status'] === 'cancelada') return false;
        $turma = (new Turma())->porId((int) $assin['turma_id']);

        $ativou = $ass->registrarPagamento($pag, $assin, (int) ($porUsuario ?? $assin['comprado_por'] ?? 0), $metodo);
        $assin = $ass->porId((int) $assin['id']) ?: $assin;
        if ($ativou) {
            (new IndicacaoService())->aoAtivar($assin);
            if ($assin['cupom_id']) (new Plano())->usarCupom((int) $assin['cupom_id']);
        }
        (new Auditoria())->registrar('pagamento_confirmado', 'pagamentos', $pagamentoId, null,
            ['metodo' => $metodo, 'valor' => (float) $pag['valor'], 'parcela' => (int) $pag['numero'], 'mp_id' => $pag['mp_id'] ?? null]);

        $valor = 'R$ ' . number_format((float) $pag['valor'], 2, ',', '.');
        if ($ativou) {
            $fim = $assin['fim'] ? date('d/m/Y', strtotime($assin['fim'])) : null;
            [$titulo, $msg] = ['Plano ativado', "Pix de {$valor} recebido. O plano {$assin['plano_nome']} da turma {$turma['nome']} está ativo" . ($fim ? " até {$fim}." : '.')];
        } elseif (($pag['tipo'] ?? 'parcela') === 'adicional') {
            [$titulo, $msg] = ['Dia a dia liberado', "Pix de {$valor} recebido. Agenda, horário, aviso do dia e contagem estão liberados para a turma {$turma['nome']}."];
        } else {
            [$titulo, $msg] = ['Pagamento recebido', "Pix de {$valor} da parcela {$pag['numero']} do plano da turma {$turma['nome']} recebido."];
        }
        $st = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1");
        $st->execute([(int) $turma['id']]);
        (new Notificacao())->criarParaVarios($st->fetchAll(PDO::FETCH_COLUMN), 'plano', $titulo, $msg, '/turmas/' . (int) $turma['id'] . '/plano');
        return true;
    }
}
