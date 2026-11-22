<?php
// app/Controllers/PlanoController.php
// Plano da turma: escolher plano, gerar o Pix (de teste) e pagar as parcelas.
// Modo de teste: nenhum gateway é chamado; o pagamento é simulado por um botão com confirmação.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Plano.php';
require_once __DIR__ . '/../Models/Assinatura.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Services/PrecoService.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class PlanoController
{
    private Turma        $turma;
    private TurmaUsuario $tu;
    private Plano        $plano;
    private Assinatura   $ass;
    private Auditoria    $audit;

    public function __construct()
    {
        $this->turma = new Turma();
        $this->tu    = new TurmaUsuario();
        $this->plano = new Plano();
        $this->ass   = new Assinatura();
        $this->audit = new Auditoria();
    }

    // ============ PLANO DA TURMA ============

    public function turma(int $turmaId)
    {
        $turma = $this->exigirRepOuMaster($turmaId);
        $atual = $this->ass->atualDaTurma($turmaId);

        // Cupom digitado (validado contra cada plano na tela)
        $codigoCupom = strtoupper(trim($_GET['cupom'] ?? ''));
        $planos  = $this->plano->listar('turma');
        $precos  = [];
        $cupom   = null;
        $erroCupom = null;
        foreach ($planos as $p) {
            [$c, $erro] = $this->plano->validarCupom($codigoCupom, $p);
            if ($c) $cupom = $c;
            if ($erro) $erroCupom = $erro;
            foreach (array_keys(PrecoService::opcoesParcelas($p)) as $n) {
                $precos[(int) $p['id']][$n] = PrecoService::calcular($p, $n, $c);
            }
        }

        $this->render('planos/turma', [
            'turma'        => $turma,
            'atual'        => $atual,
            'parcelas'     => $atual ? $this->ass->parcelas((int) $atual['id']) : [],
            'proxima'      => $atual ? $this->ass->proximaParcela((int) $atual['id']) : null,
            'projetosUsados' => $this->ass->projetosUsados($turmaId),
            'planos'       => $planos,
            'precos'       => $precos,
            'cupom'        => $cupom,
            'codigoCupom'  => $codigoCupom,
            'erroCupom'    => $cupom ? null : $erroCupom,
            'alunos'       => $this->contarAlunos($turmaId),
        ]);
    }

    public function assinar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirRepOuMaster($turmaId);
        $voltar = '/turmas/' . $turmaId . '/plano';

        if ($this->ass->atualDaTurma($turmaId)) {
            $this->flash('A turma já tem um plano em andamento. Pague ou cancele o atual antes de escolher outro.');
            $this->redirect($voltar);
        }

        $plano = $this->plano->porId((int) ($_POST['plano_id'] ?? 0));
        if (!$plano || !$plano['ativo'] || $plano['tipo'] !== 'turma') {
            $this->flash('Escolha um dos planos da lista.');
            $this->redirect($voltar);
        }

        $parcelas = (int) ($_POST['parcelas'] ?? 1);
        if (!isset(PrecoService::opcoesParcelas($plano)[$parcelas])) {
            $this->flash("O plano {$plano['nome']} não aceita {$parcelas} parcela(s).");
            $this->redirect($voltar);
        }

        [$cupom, $erro] = $this->plano->validarCupom($_POST['cupom'] ?? '', $plano);
        if ($erro) { $this->flash($erro); $this->redirect($voltar); }

        $preco = PrecoService::calcular($plano, $parcelas, $cupom);
        $id = $this->ass->criar([
            'turma_id'        => $turmaId,
            'plano_id'        => (int) $plano['id'],
            'projetos_limite' => (int) $plano['projetos'],
            'cupom_id'        => $cupom ? (int) $cupom['id'] : null,
            'comprado_por'    => (int) $_SESSION['usuario']['id'],
        ], $preco);

        $this->audit->registrar('assinatura_criada', 'assinaturas', $id, null, [
            'turma' => $turmaId, 'plano' => $plano['codigo'], 'parcelas' => $parcelas, 'total' => $preco['total'],
            'cupom' => $cupom['codigo'] ?? null,
        ]);

        $primeira = $this->ass->proximaParcela($id);
        $this->redirect('/pagamentos/' . (int) $primeira['id']);
    }

    // ============ PIX (TESTE) ============

    public function pix(int $pagamentoId)
    {
        [$pag, $ass, $turma] = $this->carregarPagamento($pagamentoId);

        $this->render('planos/pix', [
            'pagamento'  => $pag,
            'assinatura' => $ass,
            'turma'      => $turma,
            'pixCopia'   => $this->pixCopiaECola($pag),
        ]);
    }

    public function simular(int $pagamentoId)
    {
        CsrfMiddleware::validate();
        [$pag, $ass, $turma] = $this->carregarPagamento($pagamentoId);
        $voltar = '/turmas/' . (int) $turma['id'] . '/plano';

        if (PrecoService::config('modo_pagamento', 'teste') !== 'teste') {
            $this->flash('O pagamento simulado está desligado: o sistema está em modo de cobrança real.');
            $this->redirect($voltar);
        }
        if ($pag['status'] !== 'pendente') {
            $this->flash('Esta parcela já foi paga.', 'aviso');
            $this->redirect($voltar);
        }
        $proxima = $this->ass->proximaParcela((int) $ass['id']);
        if (!$proxima || (int) $proxima['id'] !== (int) $pag['id']) {
            $this->flash('Pague as parcelas na ordem: a ' . (int) $proxima['numero'] . 'ª vem antes.');
            $this->redirect($voltar);
        }

        $ativou = $this->ass->registrarPagamento($pag, $ass, (int) $_SESSION['usuario']['id']);
        if ($ativou && $ass['cupom_id']) $this->plano->usarCupom((int) $ass['cupom_id']);

        $this->audit->registrar('pagamento_simulado', 'pagamentos', (int) $pag['id'], null, [
            'assinatura' => (int) $ass['id'], 'parcela' => (int) $pag['numero'], 'valor' => (float) $pag['valor'],
        ]);

        $valor = 'R$ ' . number_format((float) $pag['valor'], 2, ',', '.');
        if ($ativou) {
            $fim = $ass['meses'] ? date('d/m/Y', strtotime('+' . (int) $ass['meses'] . ' months')) : null;
            $this->avisarRepresentantes((int) $turma['id'], 'Plano ativado',
                "O plano {$ass['plano_nome']} da turma {$turma['nome']} está ativo" . ($fim ? " até {$fim}." : ', sem vencimento.'));
            $this->flash("Pagamento de {$valor} confirmado (teste). O plano {$ass['plano_nome']} está ativo.", 'sucesso');
        } else {
            $this->flash("Parcela {$pag['numero']}/{$ass['parcelas']} de {$valor} confirmada (teste).", 'sucesso');
        }
        $this->redirect($voltar);
    }

    public function cancelar(int $assinaturaId)
    {
        CsrfMiddleware::validate();
        $ass = $this->ass->porId($assinaturaId);
        if (!$ass) { $this->flash('Assinatura não encontrada.'); $this->redirect('/turmas'); }
        $this->exigirRepOuMaster((int) $ass['turma_id']);

        if ($ass['status'] !== 'aguardando') {
            $this->flash('Só dá para cancelar um plano que ainda não teve pagamento. Para um plano ativo, fale com o suporte.');
            $this->redirect('/turmas/' . (int) $ass['turma_id'] . '/plano');
        }
        $this->ass->cancelar($assinaturaId);
        $this->audit->registrar('assinatura_cancelada', 'assinaturas', $assinaturaId, ['status' => 'aguardando'], ['status' => 'cancelada']);
        $this->flash('Pedido cancelado. Você pode escolher outro plano.', 'sucesso');
        $this->redirect('/turmas/' . (int) $ass['turma_id'] . '/plano');
    }

    // ============ HELPERS ============

    private function carregarPagamento(int $pagamentoId): array
    {
        $pag = $this->ass->pagamento($pagamentoId);
        if (!$pag) { $this->flash('Pagamento não encontrado.'); $this->redirect('/turmas'); }
        $ass = $this->ass->porId((int) $pag['assinatura_id']);
        $turma = $this->exigirRepOuMaster((int) $ass['turma_id']);
        if ($ass['status'] === 'cancelada') {
            $this->flash('Este pedido foi cancelado.');
            $this->redirect('/turmas/' . (int) $turma['id'] . '/plano');
        }
        return [$pag, $ass, $turma];
    }

    /** Texto no formato do "Pix copia e cola", só para ilustrar (não é cobrável). */
    private function pixCopiaECola(array $pag): string
    {
        $valor = number_format((float) $pag['valor'], 2, '.', '');
        return '00020126580014BR.GOV.BCB.PIX0136TESTE-PRUMO-SEM-COBRANCA5204000053039865406'
             . $valor . '5802BR5905PRUMO6009SAO PAULO62' . sprintf('%02d', strlen($pag['txid']) + 4) . '05' . $pag['txid'] . '6304TEST';
    }

    private function contarAlunos(int $turmaId): int
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM turma_usuarios WHERE turma_id = ? AND ativo = 1");
        $stmt->execute([$turmaId]);
        return (int) $stmt->fetchColumn();
    }

    private function avisarRepresentantes(int $turmaId, string $titulo, string $msg): void
    {
        $stmt = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1");
        $stmt->execute([$turmaId]);
        (new Notificacao())->criarParaVarios($stmt->fetchAll(PDO::FETCH_COLUMN), 'alerta', $titulo, $msg, '/turmas/' . $turmaId . '/plano');
    }

    private function exigirRepOuMaster(int $turmaId): array
    {
        $turma = $this->turma->porId($turmaId);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->ehRepresentante($turmaId, (int) $u['id'])) {
            http_response_code(403);
            exit('Só o representante da turma cuida do plano.');
        }
        return $turma;
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        require __DIR__ . '/../Views/' . $view . '.php';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . App::getBasePath() . $path);
        exit;
    }

    private function flash(string $msg, string $tipo = 'erro'): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $msg];
    }
}
