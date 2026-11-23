<?php
// app/Controllers/MuralController.php
// Mural de fotos e conquistas. Representante/vice/master publicam direto; aluno envia e o representante aprova.
// Todos da turma curtem. Quem publicou (ou o representante) exclui; o representante fixa no topo.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Mural.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Helpers/UploadHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class MuralController
{
    private Mural $mural;
    private TurmaUsuario $tu;

    public function __construct()
    {
        $this->mural = new Mural();
        $this->tu = new TurmaUsuario();
    }

    public function index(int $turmaId)
    {
        $turma = $this->exigirMembro($turmaId);
        $uid = (int) $_SESSION['usuario']['id'];
        $gestor = $this->ehGestor($turmaId);
        $this->render('mural/index', [
            'turma'     => $turma,
            'gestor'    => $gestor,
            'posts'     => $this->mural->aprovados($turmaId, $uid),
            'pendentes' => $this->mural->pendentes($turmaId, $gestor ? null : $uid),
        ]);
    }

    public function salvar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirMembro($turmaId);
        $voltar = '/turmas/' . $turmaId . '/mural';
        $uid = (int) $_SESSION['usuario']['id'];
        $gestor = $this->ehGestor($turmaId);
        $tipo = ($_POST['tipo'] ?? 'foto') === 'conquista' ? 'conquista' : 'foto';
        $titulo = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 120);
        $texto  = mb_substr(trim((string) ($_POST['texto'] ?? '')), 0, 600) ?: null;
        if (mb_strlen($titulo) < 2) { $this->flash('Dê um título para a publicação.'); $this->redirect($voltar); }

        $arquivo = null;
        $temArquivo = !empty($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($tipo === 'foto' && !$temArquivo) { $this->flash('Escolha a foto.'); $this->redirect($voltar); }
        if ($temArquivo) {
            $r = UploadHelper::upload($_FILES['foto'], 'uploads/mural/' . $turmaId, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1048576);
            if (!$r['success']) { $this->flash('Foto: ' . $r['message'] . ' Use JPG, PNG ou WEBP de até 5 MB.'); $this->redirect($voltar); }
            $arquivo = $r['arquivo'];
        }

        $this->mural->criar([
            'turma_id' => $turmaId, 'autor_id' => $uid, 'tipo' => $tipo,
            'icone'    => $tipo === 'conquista' && array_key_exists($_POST['icone'] ?? '', Mural::ICONES) ? $_POST['icone'] : ($tipo === 'conquista' ? 'trofeu' : null),
            'titulo'   => $titulo, 'texto' => $texto, 'arquivo' => $arquivo, 'aprovado' => $gestor ? 1 : 0,
        ]);

        if (!$gestor) {
            $st = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1");
            $st->execute([$turmaId]);
            (new Notificacao())->criarParaVarios($st->fetchAll(PDO::FETCH_COLUMN), 'mural', 'Publicação esperando aprovação no mural',
                $_SESSION['usuario']['nome'] . ' enviou "' . $titulo . '" para o mural da turma ' . $turma['nome'] . '.', $voltar . '#pendentes');
        }
        $this->flash($gestor ? 'Publicado no mural.' : 'Enviado! Aparece no mural quando o representante aprovar.', 'sucesso');
        $this->redirect($voltar);
    }

    public function aprovar(int $id)
    {
        CsrfMiddleware::validate();
        $p = $this->carregar($id, true);
        $this->mural->aprovar($id);
        if ($p['autor_id'] && (int) $p['autor_id'] !== (int) $_SESSION['usuario']['id']) {
            (new Notificacao())->criar((int) $p['autor_id'], 'mural', 'Sua publicação está no mural', '"' . $p['titulo'] . '" foi aprovada.', '/turmas/' . (int) $p['turma_id'] . '/mural', $id);
        }
        $this->flash('Aprovada e publicada no mural.', 'sucesso');
        $this->redirect('/turmas/' . (int) $p['turma_id'] . '/mural');
    }

    public function fixar(int $id)
    {
        CsrfMiddleware::validate();
        $p = $this->carregar($id, true);
        $this->mural->fixar($id);
        $this->redirect('/turmas/' . (int) $p['turma_id'] . '/mural');
    }

    public function excluir(int $id)
    {
        CsrfMiddleware::validate();
        $p = $this->carregar($id);
        if ((int) $p['autor_id'] !== (int) $_SESSION['usuario']['id'] && !$this->ehGestor((int) $p['turma_id'])) {
            $this->flash('Só quem publicou (ou o representante) exclui.'); $this->redirect('/turmas/' . (int) $p['turma_id'] . '/mural');
        }
        $this->mural->excluir($id);
        if ($p['arquivo'] && str_starts_with($p['arquivo'], 'uploads/mural/')) {
            $f = __DIR__ . '/../../public/' . $p['arquivo'];
            if (is_file($f)) @unlink($f);
        }
        $this->flash('Publicação excluída.', 'sucesso');
        $this->redirect('/turmas/' . (int) $p['turma_id'] . '/mural');
    }

    /** Curtir/descurtir (JSON). */
    public function curtir(int $id)
    {
        CsrfMiddleware::validate();
        $p = $this->carregar($id);
        header('Content-Type: application/json; charset=utf-8');
        if (!$p['aprovado']) { echo json_encode(['ok' => false]); exit; }
        [$curtiu, $total] = $this->mural->curtir($id, (int) $_SESSION['usuario']['id']);
        echo json_encode(['ok' => true, 'curtiu' => $curtiu, 'total' => $total]);
        exit;
    }

    // ============ AUXILIARES ============

    private function carregar(int $id, bool $gestor = false): array
    {
        $p = $this->mural->porId($id);
        if (!$p) { $this->flash('Publicação não encontrada.'); $this->redirect('/turmas'); }
        $this->exigirMembro((int) $p['turma_id']);
        if ($gestor && !$this->ehGestor((int) $p['turma_id'])) { $this->flash('Só o representante (ou o vice) faz isso.'); $this->redirect('/turmas/' . (int) $p['turma_id'] . '/mural'); }
        return $p;
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
