<?php
// app/Controllers/DiretorController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Grupo.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class DiretorController
{
    private Projeto      $projeto;
    private Grupo        $grupo;
    private GrupoAluno   $membro;
    private GrupoDiretor $diretor;
    private TurmaUsuario $tu;
    private Auditoria    $audit;

    public function __construct()
    {
        $this->projeto = new Projeto();
        $this->grupo   = new Grupo();
        $this->membro  = new GrupoAluno();
        $this->diretor = new GrupoDiretor();
        $this->tu      = new TurmaUsuario();
        $this->audit   = new Auditoria();
    }

    public function gerenciar(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        $this->render('diretores/gerenciar', [
            'grupo'      => $grupo,
            'projeto'    => $projeto,
            'diretores'  => $this->diretor->listarAtivos($grupoId),
            'membros'    => $this->membro->listarAtivos($grupoId),
            'podeEditar' => !$projeto['encerrado'],
        ]);
    }

    public function historico(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirAcessoProjeto($projeto);

        $this->render('diretores/historico', [
            'grupo'     => $grupo,
            'projeto'   => $projeto,
            'historico' => $this->diretor->listarHistorico($grupoId),
        ]);
    }

    public function nomear(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) {
            $this->flash('Selecione um membro do grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        if (!$this->membro->estaAtivo($grupoId, $usuarioId)) {
            $this->flash('O usuário precisa ser membro ativo do grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        if ($this->diretor->ehDiretorAtivo($grupoId, $usuarioId)) {
            $this->flash('Usuário já é diretor ativo deste grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $meuId = (int) $_SESSION['usuario']['id'];
        $this->diretor->nomear($grupoId, $usuarioId, $meuId);
        $this->audit->registrar('diretor_nomeado', 'grupo_diretores', $grupoId, null,
            ['usuario_id' => $usuarioId, 'por' => $meuId]);

        $this->flash('Diretor nomeado.', 'sucesso');
        $this->redirect('/grupos/' . $grupoId . '/diretores');
    }

    public function remover(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) {
            $this->flash('Usuário não informado.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        if (!$this->diretor->ehDiretorAtivo($grupoId, $usuarioId)) {
            $this->flash('Usuário não é diretor ativo deste grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $meuId = (int) $_SESSION['usuario']['id'];
        $this->diretor->remover($grupoId, $usuarioId, $meuId);

        $totalReatribuidas = $this->diretor->reatribuirAtasPendentes($grupoId, $usuarioId);

        $this->audit->registrar('diretor_removido', 'grupo_diretores', $grupoId,
            ['usuario_id' => $usuarioId, 'por' => $meuId],
            ['atas_reatribuidas' => $totalReatribuidas]);

        $msg = 'Diretor removido.';
        if ($totalReatribuidas > 0) {
            $msg .= " {$totalReatribuidas} ata(s) pendente(s) foram reatribuídas.";
        }
        $this->flash($msg, 'sucesso');
        $this->redirect('/grupos/' . $grupoId . '/diretores');
    }

    // ============ HELPERS ============

    private function exigirAcessoProjeto(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Sem acesso.');
    }

    private function exigirPodeEditarProjeto(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Apenas representante da turma ou master.');
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