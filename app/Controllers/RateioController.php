<?php
// app/Controllers/RateioController.php
// Rateio da mensalidade. O representante principal (ou master) divide a próxima parcela do plano entre os alunos;
// cada aluno paga a própria parte por Pix (no modo de teste, pagamento simulado). Com todas pagas — ou o restante
// coberto pelo representante — a parcela é quitada como se tivesse sido paga de uma vez (ativa o plano na 1ª).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Assinatura.php';
require_once __DIR__ . '/../Models/Plano.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Rateio.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Services/PrecoService.php';
require_once __DIR__ . '/../Services/IndicacaoService.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class RateioController
{
    private Rateio $rat;
    private Assinatura $ass;
    private TurmaUsuario $tu;

    public function __construct()
    {
        $this->rat = new Rateio();
        $this->ass = new Assinatura();
        $this->tu = new TurmaUsuario();
    }

    /** Formulário: escolher quem entra na divisão. */
    public function novo(int $pagamentoId)
    {
        [$pag, $assin, $turma] = $this->parcelaDivisivel($pagamentoId);
        $this->render('rateio/novo', ['pag' => $pag, 'assin' => $assin, 'turma' => $turma, 'pessoas' => $this->pessoas((int) $turma['id'])]);
    }

    public function criar(int $pagamentoId)
    {
        CsrfMiddleware::validate();
        [$pag, , $turma] = $this->parcelaDivisivel($pagamentoId);
        $validos = array_keys($this->pessoas((int) $turma['id']));
        $escolha = array_values(array_unique(array_intersect(array_map('intval', (array) ($_POST['usuarios'] ?? [])), $validos)));
        if (count($escolha) < 2) { $this->flash('Escolha pelo menos 2 pessoas para dividir.'); $this->redirect('/pagamentos/' . $pagamentoId . '/rateio'); }
        if ((float) $pag['valor'] / count($escolha) < 1) { $this->flash('Cada parte precisa ser de pelo menos R$ 1,00.'); $this->redirect('/pagamentos/' . $pagamentoId . '/rateio'); }

        $id = $this->rat->criar($pagamentoId, (int) $turma['id'], (int) $_SESSION['usuario']['id'], (float) $pag['valor'], $escolha);
        (new Auditoria())->registrar('rateio_criado', 'rateios', $id, null, ['pagamento' => $pagamentoId, 'pessoas' => count($escolha)]);
        $parte = 'R$ ' . number_format((float) $pag['valor'] / count($escolha), 2, ',', '.');
        (new Notificacao())->criarParaVarios(array_diff($escolha, [(int) $_SESSION['usuario']['id']]), 'plano', 'Sua parte da mensalidade: ' . $parte,
            'O representante dividiu a parcela do plano da turma ' . $turma['nome'] . ' entre ' . count($escolha) . ' pessoas. Pague a sua parte pelo Pix.', '/rateios/' . $id);
        $this->flash('Parcela dividida entre ' . count($escolha) . ' pessoas. Todos foram avisados.', 'sucesso');
        $this->redirect('/rateios/' . $id);
    }

    public function ver(int $id)
    {
        [$r, $turma, $gestor] = $this->carregar($id);
        $uid = (int) $_SESSION['usuario']['id'];
        $partes = $this->rat->partes($id);
        $minha = $this->rat->parteDe($id, $uid);
        if (!$gestor && !$minha) { $this->flash('Você não está nesta divisão.'); $this->redirect('/turmas/' . (int) $turma['id']); }
        $this->render('rateio/ver', [
            'rateio' => $r, 'turma' => $turma, 'gestor' => $gestor, 'partesRateio' => $partes, 'minha' => $minha,
            'pagoTotal' => array_sum(array_map(fn($p) => $p['status'] !== 'pendente' ? (float) $p['valor'] : 0, $partes)),
            'modoTeste' => PrecoService::config('modo_pagamento', 'teste') === 'teste',
        ]);
    }

    /** Aluno paga a própria parte (simulado no modo de teste). */
    public function pagar(int $id)
    {
        CsrfMiddleware::validate();
        [$r, $turma] = $this->carregar($id);
        $voltar = '/rateios/' . $id;
        if ($r['status'] !== 'aberto') { $this->flash('Esta divisão já foi encerrada.', 'aviso'); $this->redirect($voltar); }
        if (PrecoService::config('modo_pagamento', 'teste') !== 'teste') { $this->flash('O pagamento simulado está desligado (modo de cobrança real).'); $this->redirect($voltar); }
        $minha = $this->rat->parteDe($id, (int) $_SESSION['usuario']['id']);
        if (!$minha || $minha['status'] !== 'pendente') { $this->flash('Você não tem parte para pagar aqui.', 'aviso'); $this->redirect($voltar); }
        $this->rat->pagarParte((int) $minha['id']);
        (new Auditoria())->registrar('rateio_parte_paga', 'rateio_partes', (int) $minha['id'], null, ['valor' => (float) $minha['valor']]);
        $quitou = $this->talvezQuitar($r, $turma);
        $this->flash('Sua parte de R$ ' . number_format((float) $minha['valor'], 2, ',', '.') . ' foi paga (teste).' . ($quitou ? ' Com isso, a parcela inteira foi quitada!' : ''), 'sucesso');
        $this->redirect($voltar);
    }

    /** Representante: paga o que falta e quita a parcela. */
    public function cobrir(int $id)
    {
        CsrfMiddleware::validate();
        [$r, $turma, $gestor] = $this->carregar($id);
        $voltar = '/rateios/' . $id;
        if (!$gestor) { $this->flash('Só o representante cobre o restante.'); $this->redirect($voltar); }
        if ($r['status'] !== 'aberto') { $this->flash('Esta divisão já foi encerrada.', 'aviso'); $this->redirect($voltar); }
        if (PrecoService::config('modo_pagamento', 'teste') !== 'teste') { $this->flash('O pagamento simulado está desligado (modo de cobrança real).'); $this->redirect($voltar); }
        $v = $this->rat->cobrirRestante($id);
        (new Auditoria())->registrar('rateio_restante_coberto', 'rateios', $id, null, ['valor' => $v]);
        $this->talvezQuitar($r, $turma);
        $this->flash('Restante de R$ ' . number_format($v, 2, ',', '.') . ' pago (teste). Parcela quitada.', 'sucesso');
        $this->redirect($voltar);
    }

    public function lembrar(int $id)
    {
        CsrfMiddleware::validate();
        [$r, $turma, $gestor] = $this->carregar($id);
        if (!$gestor || $r['status'] !== 'aberto') $this->redirect('/rateios/' . $id);
        $pend = array_filter($this->rat->partes($id), fn($p) => $p['status'] === 'pendente');
        foreach ($pend as $p) {
            (new Notificacao())->criar((int) $p['usuario_id'], 'plano', 'Lembrete: sua parte da mensalidade',
                'Falta a sua parte de R$ ' . number_format((float) $p['valor'], 2, ',', '.') . ' do plano da turma ' . $turma['nome'] . '.', '/rateios/' . $id, $id, false);
        }
        $this->flash('Lembrete enviado para ' . count($pend) . ' pessoa(s).', 'sucesso');
        $this->redirect('/rateios/' . $id);
    }

    public function cancelar(int $id)
    {
        CsrfMiddleware::validate();
        [$r, $turma, $gestor] = $this->carregar($id);
        if (!$gestor) $this->redirect('/rateios/' . $id);
        if ($this->rat->algumPago($id)) { $this->flash('Alguém já pagou a parte: não dá para cancelar. Cubra o restante para quitar.'); $this->redirect('/rateios/' . $id); }
        $this->rat->cancelar($id);
        $this->flash('Divisão cancelada. A parcela volta a ser paga de uma vez.', 'sucesso');
        $this->redirect('/turmas/' . (int) $turma['id'] . '/plano');
    }

    // ============ AUXILIARES ============

    /** Todas as partes pagas/cobertas: quita a parcela como um pagamento normal. */
    private function talvezQuitar(array $r, array $turma): bool
    {
        if ($this->rat->faltam((int) $r['id']) > 0) return false;
        $pag = $this->ass->pagamento((int) $r['pagamento_id']);
        $assin = $this->ass->porId((int) $pag['assinatura_id']);
        if ($pag['status'] === 'pendente') {
            $ativou = $this->ass->registrarPagamento($pag, $assin, (int) $_SESSION['usuario']['id'], 'rateio');
            $assin = $this->ass->porId((int) $assin['id']) ?: $assin;
            if ($ativou) {
                (new IndicacaoService())->aoAtivar($assin);
                if ($assin['cupom_id']) (new Plano())->usarCupom((int) $assin['cupom_id']);
            }
            $st = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1");
            $st->execute([(int) $turma['id']]);
            (new Notificacao())->criarParaVarios($st->fetchAll(PDO::FETCH_COLUMN), 'plano', $ativou ? 'Plano ativado pelo rateio' : 'Parcela quitada pelo rateio',
                'Todas as partes da parcela ' . (int) $pag['numero'] . ' da turma ' . $turma['nome'] . ' foram pagas.', '/turmas/' . (int) $turma['id'] . '/plano');
        }
        $this->rat->quitar((int) $r['id']);
        return true;
    }

    /** A parcela pode ser dividida? Próxima parcela pendente (na ordem), sem rateio aberto. Só representante principal ou master. */
    private function parcelaDivisivel(int $pagamentoId): array
    {
        $pag = $this->ass->pagamento($pagamentoId);
        if (!$pag) { $this->flash('Pagamento não encontrado.'); $this->redirect('/turmas'); }
        $assin = $this->ass->porId((int) $pag['assinatura_id']);
        $turma = (new Turma())->porId((int) $assin['turma_id']);
        $voltar = '/turmas/' . (int) $turma['id'] . '/plano';
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->ehRepresentantePrincipal((int) $turma['id'], (int) $u['id'])) { $this->flash('Só o representante principal divide a mensalidade.'); $this->redirect($voltar); }
        $prox = $this->ass->proximaParcela((int) $assin['id']);
        if ($assin['status'] === 'cancelada' || $pag['status'] !== 'pendente' || ($pag['tipo'] ?? 'parcela') !== 'parcela' || !$prox || (int) $prox['id'] !== $pagamentoId) {
            $this->flash('Só a próxima parcela pendente pode ser dividida.'); $this->redirect($voltar);
        }
        if ($ab = $this->rat->abertoDoPagamento($pagamentoId)) $this->redirect('/rateios/' . (int) $ab['id']);
        return [$pag, $assin, $turma];
    }

    /** [rateio, turma, é gestor] — gestor: representante principal ou master; demais precisam ter parte. */
    private function carregar(int $id): array
    {
        $r = $this->rat->porId($id);
        if (!$r) { $this->flash('Divisão não encontrada.'); $this->redirect('/turmas'); }
        $turma = (new Turma())->porId((int) $r['turma_id']);
        $u = $_SESSION['usuario'];
        $gestor = $u['tipo'] === 'master' || $this->tu->ehRepresentantePrincipal((int) $turma['id'], (int) $u['id']);
        if (!$gestor && !$this->tu->estaAtivo((int) $turma['id'], (int) $u['id'])) { $this->flash('Você não está nesta turma.'); $this->redirect('/turmas'); }
        return [$r, $turma, $gestor];
    }

    private function pessoas(int $turmaId): array
    {
        $st = Database::getConnection()->prepare("SELECT u.id, u.nome, tu.papel FROM turma_usuarios tu INNER JOIN usuarios u ON u.id = tu.usuario_id WHERE tu.turma_id = ? AND tu.ativo = 1 ORDER BY tu.papel <> 'representante', u.nome");
        $st->execute([$turmaId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) $out[(int) $p['id']] = $p;
        return $out;
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
