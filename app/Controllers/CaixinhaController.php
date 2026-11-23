<?php
// app/Controllers/CaixinhaController.php
// Caixinha anônima: qualquer membro envia dúvida/sugestão/reclamação/elogio; o representante (ou vice/master)
// lê sem saber quem enviou, responde e pode publicar a pergunta com a resposta para a turma toda.
// O autor recebe uma notificação quando a mensagem dele é respondida.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Caixinha.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class CaixinhaController
{
    private Caixinha $cx;
    private TurmaUsuario $tu;

    public function __construct()
    {
        $this->cx = new Caixinha();
        $this->tu = new TurmaUsuario();
    }

    public function index(int $turmaId)
    {
        $turma = $this->exigirMembro($turmaId);
        $uid = (int) $_SESSION['usuario']['id'];
        $gestor = $this->ehGestor($turmaId);
        $filtro = in_array($_GET['ver'] ?? '', ['abertas', 'respondidas', 'arquivadas'], true) ? $_GET['ver'] : 'abertas';
        $dados = [
            'turma'    => $turma,
            'gestor'   => $gestor,
            'filtro'   => $filtro,
            'minhas'   => $this->cx->minhas($turmaId, $uid),
            'publicas' => $this->cx->publicas($turmaId),
            'restam'   => max(0, Caixinha::LIMITE_DIA - $this->cx->enviadasHoje($turmaId, $uid)),
        ];
        if ($gestor) {
            $dados['contagem'] = $this->cx->contagem($turmaId);
            $dados['entrada']  = $this->cx->entrada($turmaId, $filtro);
            $this->cx->marcarLidas($turmaId);   // abriu a caixinha: as novas viram lidas
        }
        $this->render('caixinha/index', $dados);
    }

    public function enviar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirMembro($turmaId);
        $uid = (int) $_SESSION['usuario']['id'];
        $voltar = '/turmas/' . $turmaId . '/caixinha';
        $tipo  = array_key_exists($_POST['tipo'] ?? '', Caixinha::TIPOS) ? $_POST['tipo'] : 'duvida';
        $texto = trim((string) ($_POST['texto'] ?? ''));

        if (mb_strlen($texto) < 5)  { $this->flash('Escreva a sua mensagem (pelo menos 5 letras).'); $this->redirect($voltar); }
        if (mb_strlen($texto) > 2000) { $this->flash('A mensagem pode ter até 2000 caracteres.'); $this->redirect($voltar); }
        if ($this->cx->enviadasHoje($turmaId, $uid) >= Caixinha::LIMITE_DIA) {
            $this->flash('Você já enviou ' . Caixinha::LIMITE_DIA . ' mensagens hoje. Tente amanhã.', 'aviso'); $this->redirect($voltar);
        }
        $this->cx->enviar($turmaId, $uid, $tipo, $texto);

        // avisa os representantes, sem dizer quem foi
        $st = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1 AND usuario_id <> ?");
        $st->execute([$turmaId, $uid]);
        (new Notificacao())->criarParaVarios($st->fetchAll(PDO::FETCH_COLUMN), 'caixinha',
            'Nova mensagem na caixinha: ' . Caixinha::TIPOS[$tipo][0], 'Alguém da turma ' . $turma['nome'] . ' deixou uma mensagem anônima.', $voltar);

        $this->flash('Mensagem enviada de forma anônima. Você recebe um aviso quando ela for respondida.', 'sucesso');
        $this->redirect($voltar);
    }

    public function responder(int $id)
    {
        CsrfMiddleware::validate();
        $m = $this->carregarComoGestor($id);
        $voltar = '/turmas/' . (int) $m['turma_id'] . '/caixinha';
        $resp = trim((string) ($_POST['resposta'] ?? ''));
        if (mb_strlen($resp) < 2) { $this->flash('Escreva a resposta.'); $this->redirect($voltar); }
        $publica = !empty($_POST['publica']);
        $this->cx->responder($id, mb_substr($resp, 0, 3000), $publica, (int) $_SESSION['usuario']['id']);

        $autor = $this->cx->autorDe($id);
        if ($autor && $autor !== (int) $_SESSION['usuario']['id']) {
            (new Notificacao())->criar($autor, 'caixinha', 'Sua mensagem na caixinha foi respondida',
                mb_strimwidth($resp, 0, 160, '…'), $voltar . '#minhas', $id);
        }
        $this->flash($publica ? 'Resposta enviada e publicada para a turma.' : 'Resposta enviada para quem perguntou.', 'sucesso');
        $this->redirect($voltar);
    }

    public function arquivar(int $id)
    {
        CsrfMiddleware::validate();
        $m = $this->carregarComoGestor($id);
        $arquivar = ($_POST['acao'] ?? 'arquivar') === 'arquivar';
        $this->cx->arquivar($id, $arquivar);
        $this->flash($arquivar ? 'Mensagem arquivada.' : 'Mensagem de volta para a caixa.', 'sucesso');
        $this->redirect('/turmas/' . (int) $m['turma_id'] . '/caixinha' . ($arquivar ? '' : '?ver=arquivadas'));
    }

    // ============ AUXILIARES ============

    private function carregarComoGestor(int $id): array
    {
        $m = $this->cx->porId($id);
        if (!$m) { $this->flash('Mensagem não encontrada.'); $this->redirect('/turmas'); }
        $this->exigirMembro((int) $m['turma_id']);
        if (!$this->ehGestor((int) $m['turma_id'])) { $this->flash('Só o representante (ou o vice) responde a caixinha.'); $this->redirect('/turmas/' . (int) $m['turma_id'] . '/caixinha'); }
        return $m;
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

    private function ehGestor(int $turmaId): bool
    {
        $u = $_SESSION['usuario'];
        return $u['tipo'] === 'master' || $this->tu->ehRepresentante($turmaId, (int) $u['id']);
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
