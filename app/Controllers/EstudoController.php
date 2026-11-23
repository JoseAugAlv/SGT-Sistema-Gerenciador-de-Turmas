<?php
// app/Controllers/EstudoController.php
// Banco de materiais de estudo da turma, por disciplina: arquivo (PDF, imagem ou ZIP), link ou resumo escrito,
// ligado (opcional) a uma prova/entrega da agenda. Qualquer membro compartilha; quem enviou ou o representante remove.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Estudo.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Helpers/UploadHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class EstudoController
{
    private Estudo $est;
    private TurmaUsuario $tu;

    public function __construct()
    {
        $this->est = new Estudo();
        $this->tu = new TurmaUsuario();
    }

    public function index(int $turmaId)
    {
        $turma = $this->exigirMembro($turmaId);
        $disc  = trim((string) ($_GET['disciplina'] ?? ''));
        $evId  = (int) ($_GET['evento'] ?? 0) ?: null;
        $busca = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
        $this->render('estudos/index', [
            'turma'       => $turma,
            'materiais'   => $this->est->listar($turmaId, $disc ?: null, $evId, $busca),
            'disciplinas' => $this->est->disciplinas($turmaId),
            'eventos'     => $this->est->proximosEventos($turmaId),
            'eventoAtual' => $evId ? $this->est->evento($evId, $turmaId) : null,
            'disciplina'  => $disc,
            'busca'       => $busca,
            'gestor'      => $this->ehGestor($turmaId),
        ]);
    }

    public function salvar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirMembro($turmaId);
        $voltar = '/turmas/' . $turmaId . '/estudos';
        $tipo = array_key_exists($_POST['tipo'] ?? '', Estudo::TIPOS) ? $_POST['tipo'] : 'arquivo';
        $disc = mb_substr(trim(preg_replace('/\s+/', ' ', (string) ($_POST['disciplina'] ?? ''))), 0, 80);
        $titulo = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 150);
        $evId = (int) ($_POST['evento_id'] ?? 0) ?: null;
        if ($evId && !$this->est->evento($evId, $turmaId)) $evId = null;

        if (mb_strlen($disc) < 2)   { $this->flash('Informe a disciplina.'); $this->redirect($voltar); }
        if (mb_strlen($titulo) < 2) { $this->flash('Dê um título ao material.'); $this->redirect($voltar); }

        $d = ['turma_id' => $turmaId, 'autor_id' => (int) $_SESSION['usuario']['id'], 'disciplina' => $disc, 'titulo' => $titulo,
              'tipo' => $tipo, 'arquivo' => null, 'nome_original' => null, 'url' => null, 'texto' => null, 'evento_id' => $evId];

        if ($tipo === 'arquivo') {
            if (empty($_FILES['arquivo']) || ($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { $this->flash('Escolha o arquivo.'); $this->redirect($voltar); }
            $r = UploadHelper::upload($_FILES['arquivo'], 'uploads/estudos/' . $turmaId, ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'zip'], 15 * 1048576);
            if (!$r['success']) { $this->flash('Arquivo: ' . $r['message'] . ' Aceita PDF, imagem ou ZIP de até 15 MB.'); $this->redirect($voltar); }
            $d['arquivo'] = $r['arquivo'];
            $d['nome_original'] = mb_substr(basename((string) $_FILES['arquivo']['name']), 0, 200);
        } elseif ($tipo === 'link') {
            $url = trim((string) ($_POST['url'] ?? ''));
            if (!preg_match('#^https?://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) { $this->flash('Cole um link válido (começando com http:// ou https://).'); $this->redirect($voltar); }
            $d['url'] = mb_substr($url, 0, 500);
        } else {
            $txt = trim((string) ($_POST['texto'] ?? ''));
            if (mb_strlen($txt) < 10) { $this->flash('Escreva o resumo (pelo menos 10 letras).'); $this->redirect($voltar); }
            $d['texto'] = mb_substr($txt, 0, 20000);
        }
        $id = $this->est->criar($d);

        // material ligado a uma prova/entrega: avisa a turma
        if ($evId) {
            $ev = $this->est->evento($evId, $turmaId);
            $st = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND ativo = 1 AND usuario_id <> ?");
            $st->execute([$turmaId, (int) $_SESSION['usuario']['id']]);
            (new Notificacao())->criarParaVarios($st->fetchAll(PDO::FETCH_COLUMN), 'material', 'Material de estudo para ' . $ev['titulo'],
                $_SESSION['usuario']['nome'] . ' compartilhou "' . $titulo . '" (' . $disc . ').', $voltar . '?evento=' . $evId);
        }
        $this->flash('Material compartilhado com a turma.', 'sucesso');
        $this->redirect($voltar . '?disciplina=' . rawurlencode($disc) . '#m' . $id);
    }

    /** Baixa o arquivo com o nome original. */
    public function baixar(int $id)
    {
        $m = $this->carregar($id);
        $f = realpath(__DIR__ . '/../../public/' . $m['arquivo']);
        $raiz = realpath(__DIR__ . '/../../public/uploads/estudos');
        if (!$m['arquivo'] || !$f || !$raiz || !str_starts_with($f, $raiz) || !is_file($f)) { http_response_code(404); exit('Arquivo não encontrado.'); }
        $nome = preg_replace('/[^\w.\- ]+/u', '_', $m['nome_original'] ?: basename($f));
        header('Content-Type: ' . (mime_content_type($f) ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: ' . (str_ends_with(strtolower($f), '.pdf') ? 'inline' : 'attachment') . '; filename="' . $nome . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($f);
        exit;
    }

    public function excluir(int $id)
    {
        CsrfMiddleware::validate();
        $m = $this->carregar($id);
        $voltar = '/turmas/' . (int) $m['turma_id'] . '/estudos';
        if ((int) $m['autor_id'] !== (int) $_SESSION['usuario']['id'] && !$this->ehGestor((int) $m['turma_id'])) {
            $this->flash('Só quem compartilhou (ou o representante) remove.'); $this->redirect($voltar);
        }
        $this->est->excluir($id);
        if ($m['arquivo'] && str_starts_with($m['arquivo'], 'uploads/estudos/')) {
            $f = __DIR__ . '/../../public/' . $m['arquivo'];
            if (is_file($f)) @unlink($f);
        }
        $this->flash('Material removido.', 'sucesso');
        $this->redirect($voltar);
    }

    // ============ AUXILIARES ============

    private function carregar(int $id): array
    {
        $m = $this->est->porId($id);
        if (!$m) { $this->flash('Material não encontrado.'); $this->redirect('/turmas'); }
        $this->exigirMembro((int) $m['turma_id']);
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
