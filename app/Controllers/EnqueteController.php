<?php
// app/Controllers/EnqueteController.php
// Enquetes e votações da turma/grêmio. Cria: representante, vice (o presidente no grêmio) ou master.
// Vota: qualquer membro ativo da turma, enquanto a enquete está aberta (pode mudar o voto).
// Resultado: sempre, depois de votar ou só quando encerrar (quem criou e o master sempre veem).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Enquete.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class EnqueteController
{
    private Enquete $enq;
    private TurmaUsuario $tu;

    public function __construct()
    {
        $this->enq = new Enquete();
        $this->tu  = new TurmaUsuario();
    }

    // ============ LISTA E CRIAÇÃO ============

    public function index(int $turmaId)
    {
        $turma = $this->exigirMembro($turmaId);
        $uid = (int) $_SESSION['usuario']['id'];
        $this->render('enquetes/index', [
            'turma'    => $turma,
            'enquetes' => $this->enq->daTurma($turmaId, $uid),
            'podeCriar'=> $this->podeCriar($turmaId),
            'membros'  => $this->contarMembros($turmaId),
        ]);
    }

    public function salvar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirMembro($turmaId);
        $voltar = '/turmas/' . $turmaId . '/enquetes';
        if (!$this->podeCriar($turmaId)) { $this->flash('Só o representante (ou o vice) cria enquetes.'); $this->redirect($voltar); }

        $pergunta = mb_substr(trim((string) ($_POST['pergunta'] ?? '')), 0, 200);
        $opcoes = [];
        foreach ((array) ($_POST['opcoes'] ?? []) as $o) {
            $o = mb_substr(trim((string) $o), 0, 150);
            if ($o !== '' && !in_array(mb_strtolower($o), array_map('mb_strtolower', $opcoes), true)) $opcoes[] = $o;
        }
        $encerra = trim((string) ($_POST['encerra_em'] ?? ''));
        $encerraTs = $encerra !== '' ? strtotime($encerra) : null;

        $erro = null;
        if (mb_strlen($pergunta) < 3)                 $erro = 'Escreva a pergunta.';
        elseif (count($opcoes) < 2)                   $erro = 'Coloque pelo menos 2 opções diferentes.';
        elseif (count($opcoes) > 12)                  $erro = 'No máximo 12 opções.';
        elseif ($encerra !== '' && (!$encerraTs || $encerraTs <= time())) $erro = 'O encerramento precisa ser no futuro.';
        if ($erro) { $this->flash($erro); $this->redirect($voltar); }

        $id = $this->enq->criar([
            'turma_id'   => $turmaId,
            'criado_por' => (int) $_SESSION['usuario']['id'],
            'pergunta'   => $pergunta,
            'descricao'  => trim((string) ($_POST['descricao'] ?? '')) ?: null,
            'multipla'   => !empty($_POST['multipla']) ? 1 : 0,
            'anonima'    => !empty($_POST['anonima']) ? 1 : 0,
            'resultado'  => array_key_exists($_POST['resultado'] ?? '', Enquete::RESULTADO) ? $_POST['resultado'] : 'apos_votar',
            'encerra_em' => $encerraTs ? date('Y-m-d H:i:s', $encerraTs) : null,
        ], $opcoes);
        (new Auditoria())->registrar('enquete_criada', 'enquetes', $id, null, ['turma' => $turmaId, 'pergunta' => $pergunta]);

        // avisa a turma (menos quem criou)
        $st = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND ativo = 1 AND usuario_id <> ?");
        $st->execute([$turmaId, (int) $_SESSION['usuario']['id']]);
        $prazo = $encerraTs ? ' Vota até ' . date('d/m H:i', $encerraTs) . '.' : '';
        (new Notificacao())->criarParaVarios($st->fetchAll(PDO::FETCH_COLUMN), 'enquete', 'Nova enquete: ' . $pergunta, 'Dê o seu voto na enquete da turma ' . $turma['nome'] . '.' . $prazo, '/enquetes/' . $id);

        $this->flash('Enquete criada e enviada para a turma.', 'sucesso');
        $this->redirect('/enquetes/' . $id);
    }

    // ============ ENQUETE: VOTO E RESULTADO ============

    public function ver(int $id)
    {
        $e = $this->carregar($id);
        $uid = (int) $_SESSION['usuario']['id'];
        $meus = $this->enq->meusVotos($id, $uid);
        $gestor = $this->ehGestor($e);
        $veResultado = $gestor || $e['resultado'] === 'sempre' || ($e['resultado'] === 'apos_votar' && ($meus || !$e['aberta'])) || !$e['aberta'];
        $this->render('enquetes/detalhe', [
            'turma'       => (new Turma())->porId((int) $e['turma_id']),
            'enquete'     => $e,
            'opcoes'      => $this->enq->opcoes($id, !$e['anonima'] && $veResultado),
            'meus'        => $meus,
            'votantes'    => $this->enq->votantes($id),
            'membros'     => $this->contarMembros((int) $e['turma_id']),
            'veResultado' => $veResultado,
            'gestor'      => $gestor,
        ]);
    }

    public function votar(int $id)
    {
        CsrfMiddleware::validate();
        $e = $this->carregar($id);
        $voltar = '/enquetes/' . $id;
        if (!$e['aberta']) { $this->flash('Esta enquete já foi encerrada.', 'aviso'); $this->redirect($voltar); }

        $validas = array_map(fn($o) => (int) $o['id'], $this->enq->opcoes($id));
        $escolha = array_values(array_unique(array_intersect(array_map('intval', (array) ($_POST['opcao'] ?? [])), $validas)));
        if (!$escolha)                          { $this->flash('Escolha uma opção.'); $this->redirect($voltar); }
        if (!$e['multipla'] && count($escolha) > 1) $escolha = [$escolha[0]];

        $this->enq->votar($id, (int) $_SESSION['usuario']['id'], $escolha);
        $this->flash('Voto registrado. Você pode mudar enquanto a enquete estiver aberta.', 'sucesso');
        $this->redirect($voltar);
    }

    public function encerrar(int $id)
    {
        CsrfMiddleware::validate();
        $e = $this->carregar($id);
        if (!$this->ehGestor($e)) { $this->flash('Só quem criou (ou o representante) encerra a enquete.'); $this->redirect('/enquetes/' . $id); }
        $this->enq->encerrar($id);
        (new Auditoria())->registrar('enquete_encerrada', 'enquetes', $id, null, []);
        $this->flash('Enquete encerrada. O resultado agora aparece para todos.', 'sucesso');
        $this->redirect('/enquetes/' . $id);
    }

    public function excluir(int $id)
    {
        CsrfMiddleware::validate();
        $e = $this->carregar($id);
        if (!$this->ehGestor($e)) { $this->flash('Só quem criou (ou o representante) exclui a enquete.'); $this->redirect('/enquetes/' . $id); }
        $this->enq->excluir($id);
        (new Auditoria())->registrar('enquete_excluida', 'enquetes', $id, ['pergunta' => $e['pergunta']], null);
        $this->flash('Enquete excluída.', 'sucesso');
        $this->redirect('/turmas/' . (int) $e['turma_id'] . '/enquetes');
    }

    // ============ AUXILIARES ============

    private function carregar(int $id): array
    {
        $e = $this->enq->porId($id);
        if (!$e) { $this->flash('Enquete não encontrada.'); $this->redirect('/turmas'); }
        $this->exigirMembro((int) $e['turma_id']);
        return $e;
    }

    private function exigirMembro(int $turmaId): array
    {
        $turma = (new Turma())->porId($turmaId);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->estaAtivo($turmaId, (int) $u['id'])) { $this->flash('Você não está nesta turma.'); $this->redirect('/turmas'); }
        PlanoGuard::exigir($turma);
        return $turma;
    }

    /** Representante, vice (papel representante) ou master. */
    private function podeCriar(int $turmaId): bool
    {
        $u = $_SESSION['usuario'];
        return $u['tipo'] === 'master' || $this->tu->ehRepresentante($turmaId, (int) $u['id']);
    }

    /** Quem criou, o representante/vice da turma ou o master. */
    private function ehGestor(array $e): bool
    {
        return (int) $e['criado_por'] === (int) $_SESSION['usuario']['id'] || $this->podeCriar((int) $e['turma_id']);
    }

    private function contarMembros(int $turmaId): int
    {
        $st = Database::getConnection()->prepare("SELECT COUNT(*) FROM turma_usuarios WHERE turma_id = ? AND ativo = 1");
        $st->execute([$turmaId]);
        return (int) $st->fetchColumn();
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        $basePath = App::getBasePath();
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
