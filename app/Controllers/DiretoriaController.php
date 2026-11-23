<?php
// app/Controllers/DiretoriaController.php
// Diretorias / coordenações do grêmio. O presidente cria, renomeia e exclui; presidente e vice distribuem as pessoas.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Diretoria.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class DiretoriaController
{
    private TurmaUsuario $tu;
    private Diretoria    $dir;

    public function __construct()
    {
        $this->tu  = new TurmaUsuario();
        $this->dir = new Diretoria();
    }

    public function index(int $turmaId)
    {
        $g = $this->exigirGremio($turmaId);
        $u = $_SESSION['usuario'];
        $this->render('gremio/diretorias', [
            'turma'       => $g,
            'diretorias'  => $this->dir->listar($turmaId),
            'membros'     => $this->tu->listarAlunos($turmaId),
            'souPresidente' => $u['tipo'] === 'master' || $this->tu->ehRepresentantePrincipal($turmaId, (int) $u['id']),
            'podeDistribuir' => $u['tipo'] === 'master' || $this->tu->ehRepresentante($turmaId, (int) $u['id']),
        ]);
    }

    public function criar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $this->exigirPresidente($turmaId);
        $nomes = array_filter(array_map(fn($n) => mb_substr(trim($n), 0, 100), preg_split('/[\r\n,]+/', (string) ($_POST['nome'] ?? ''))), fn($n) => mb_strlen($n) >= 2);
        if (!$nomes) { $this->flash('Dê um nome à diretoria.'); $this->redirect('/turmas/' . $turmaId . '/diretorias'); }
        $existentes = array_map(fn($d) => mb_strtolower($d['nome']), $this->dir->listar($turmaId));
        $criadas = 0;
        foreach (array_unique($nomes) as $n) {
            if (in_array(mb_strtolower($n), $existentes, true)) continue;
            $this->dir->criar($turmaId, $n, trim($_POST['descricao'] ?? '') ?: null, (int) $_SESSION['usuario']['id']);
            $criadas++;
        }
        (new Auditoria())->registrar('diretorias_criadas', 'gremio_diretorias', $turmaId, null, ['nomes' => array_values($nomes)]);
        $this->flash($criadas ? "{$criadas} diretoria(s) criada(s)." : 'Essas diretorias já existem.', $criadas ? 'sucesso' : 'aviso');
        $this->redirect('/turmas/' . $turmaId . '/diretorias');
    }

    public function atualizar(int $id)
    {
        CsrfMiddleware::validate();
        $d = $this->carregar($id);
        $this->exigirPresidente((int) $d['turma_id']);
        $nome = mb_substr(trim($_POST['nome'] ?? ''), 0, 100);
        if (mb_strlen($nome) < 2) { $this->flash('Nome muito curto.'); $this->redirect('/turmas/' . (int) $d['turma_id'] . '/diretorias'); }
        $this->dir->atualizar($id, $nome, mb_substr(trim($_POST['descricao'] ?? ''), 0, 500) ?: null);
        $this->flash('Diretoria salva.', 'sucesso');
        $this->redirect('/turmas/' . (int) $d['turma_id'] . '/diretorias#d' . $id);
    }

    public function excluir(int $id)
    {
        CsrfMiddleware::validate();
        $d = $this->carregar($id);
        $this->exigirPresidente((int) $d['turma_id']);
        $this->dir->excluir($id);
        (new Auditoria())->registrar('diretoria_excluida', 'gremio_diretorias', $id, $d, null);
        $this->flash("Diretoria {$d['nome']} excluída.", 'sucesso');
        $this->redirect('/turmas/' . (int) $d['turma_id'] . '/diretorias');
    }

    /** Coloca alguém na diretoria ou muda o cargo (diretor / coordenador / membro). */
    public function definirMembro(int $id)
    {
        CsrfMiddleware::validate();
        $d = $this->carregar($id);
        $tid = (int) $d['turma_id'];
        $this->exigirDistribuidor($tid);
        $uid = (int) ($_POST['usuario_id'] ?? 0);
        $cargo = array_key_exists($_POST['cargo'] ?? '', Diretoria::CARGOS) ? $_POST['cargo'] : 'membro';
        if (!$uid || !$this->tu->estaAtivo($tid, $uid)) { $this->flash('Escolha alguém do grêmio.'); $this->redirect('/turmas/' . $tid . '/diretorias#d' . $id); }
        $antes = $this->dir->cargoDe($id, $uid);
        $this->dir->definirMembro($id, $uid, $cargo);
        if ($antes !== $cargo) {
            (new Notificacao())->criar($uid, 'papel', 'Diretoria ' . $d['nome'],
                'Você agora é ' . mb_strtolower(Diretoria::CARGOS[$cargo]) . ' da diretoria ' . $d['nome'] . '.', '/turmas/' . $tid . '/diretorias#d' . $id);
        }
        $this->flash('Diretoria atualizada.', 'sucesso');
        $this->redirect('/turmas/' . $tid . '/diretorias#d' . $id);
    }

    public function removerMembro(int $id)
    {
        CsrfMiddleware::validate();
        $d = $this->carregar($id);
        $this->exigirDistribuidor((int) $d['turma_id']);
        $this->dir->removerMembro($id, (int) ($_POST['usuario_id'] ?? 0));
        $this->flash('Removido da diretoria.', 'sucesso');
        $this->redirect('/turmas/' . (int) $d['turma_id'] . '/diretorias#d' . $id);
    }

    // ============ HELPERS ============

    private function carregar(int $id): array
    {
        $d = $this->dir->porId($id);
        if (!$d) { $this->flash('Diretoria não encontrada.'); $this->redirect('/turmas'); }
        $this->exigirGremio((int) $d['turma_id']);
        return $d;
    }

    private function exigirGremio(int $turmaId): array
    {
        $t = (new Turma())->porId($turmaId);
        if (!$t || $t['tipo'] !== 'gremio') { $this->flash('Diretorias existem só em grêmios.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->estaAtivo($turmaId, (int) $u['id'])) { $this->flash('Você não está neste grêmio.'); $this->redirect('/turmas'); }
        return $t;
    }

    private function exigirPresidente(int $turmaId): void
    {
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->ehRepresentantePrincipal($turmaId, (int) $u['id'])) {
            http_response_code(403); exit('Só o presidente do grêmio cria e exclui diretorias.');
        }
    }

    private function exigirDistribuidor(int $turmaId): void
    {
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->ehRepresentante($turmaId, (int) $u['id'])) {
            http_response_code(403); exit('Só o presidente e o vice distribuem as diretorias.');
        }
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
