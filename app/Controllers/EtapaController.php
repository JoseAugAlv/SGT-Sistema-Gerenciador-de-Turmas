<?php
// app/Controllers/EtapaController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Etapa.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class EtapaController
{
    private Projeto      $projeto;
    private Etapa        $etapa;
    private TurmaUsuario $tu;
    private Auditoria    $audit;

    public function __construct()
    {
        $this->projeto = new Projeto();
        $this->etapa   = new Etapa();
        $this->tu      = new TurmaUsuario();
        $this->audit   = new Auditoria();
    }

    // ============ CRIAR (REP OU MASTER) ============

    public function criar(int $projetoId)
    {
        $this->exigirPodeEditar($projetoId);
        CsrfMiddleware::validate();

        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto || $projeto['encerrado']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto não pode receber etapas.'];
            $this->redirect('/projetos/' . $projetoId);
        }

        $nome = trim($_POST['nome'] ?? '');
        $desc = trim($_POST['descricao'] ?? '') ?: null;
        $ini  = trim($_POST['data_inicio'] ?? '') ?: null;
        $fim  = trim($_POST['data_fim'] ?? '') ?: null;

        $erros = [];
        if (strlen($nome) < 2) $erros[] = 'Nome muito curto.';
        if ($ini && !DateTime::createFromFormat('Y-m-d', $ini)) $erros[] = 'Data início inválida.';
        if ($fim && !DateTime::createFromFormat('Y-m-d', $fim)) $erros[] = 'Data fim inválida.';
        if ($ini && $fim && strtotime($fim) < strtotime($ini))  $erros[] = 'Data fim anterior à início.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/projetos/' . $projetoId);
        }

        $ordem = $this->etapa->proximaOrdem($projetoId);
        $id = $this->etapa->criar([
            'projeto_id'  => $projetoId,
            'nome'        => $nome,
            'descricao'   => $desc,
            'data_inicio' => $ini,
            'data_fim'    => $fim,
            'ordem'       => $ordem,
        ]);

        $this->audit->registrar('etapa_criada', 'projeto_etapas', $id, null,
            ['projeto_id' => $projetoId, 'nome' => $nome]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Etapa criada.'];
        $this->redirect('/projetos/' . $projetoId);
    }

    // ============ EDITAR ============

    public function editarForm(int $id)
    {
        $etapa = $this->etapa->porId($id);
        if (!$etapa) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Etapa não encontrada.'];
            $this->redirect('/turmas');
        }

        $this->exigirPodeEditar((int) $etapa['projeto_id']);

        $projeto = $this->projeto->porId((int) $etapa['projeto_id']);

        $this->render('etapas/editar', [
            'etapa'   => $etapa,
            'projeto' => $projeto,
        ]);
    }

    public function atualizar(int $id)
    {
        $etapa = $this->etapa->porId($id);
        if (!$etapa) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Etapa não encontrada.'];
            $this->redirect('/turmas');
        }

        $this->exigirPodeEditar((int) $etapa['projeto_id']);
        CsrfMiddleware::validate();

        $projeto = $this->projeto->porId((int) $etapa['projeto_id']);
        if ($projeto['encerrado']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto encerrado. Não é possível editar.'];
            $this->redirect('/projetos/' . $projeto['id']);
        }

        $nome = trim($_POST['nome'] ?? '');
        $desc = trim($_POST['descricao'] ?? '') ?: null;
        $ini  = trim($_POST['data_inicio'] ?? '') ?: null;
        $fim  = trim($_POST['data_fim'] ?? '') ?: null;

        $erros = [];
        if (strlen($nome) < 2) $erros[] = 'Nome muito curto.';
        if ($ini && !DateTime::createFromFormat('Y-m-d', $ini)) $erros[] = 'Data início inválida.';
        if ($fim && !DateTime::createFromFormat('Y-m-d', $fim)) $erros[] = 'Data fim inválida.';
        if ($ini && $fim && strtotime($fim) < strtotime($ini))  $erros[] = 'Data fim anterior à início.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/etapas/' . $id . '/editar');
        }

        $this->etapa->atualizar($id, [
            'nome' => $nome, 'descricao' => $desc, 'data_inicio' => $ini, 'data_fim' => $fim,
        ]);

        $this->audit->registrar('etapa_atualizada', 'projeto_etapas', $id, $etapa,
            ['nome' => $nome]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Etapa atualizada.'];
        $this->redirect('/projetos/' . $etapa['projeto_id']);
    }

    // ============ EXCLUIR ============

    public function excluir(int $id)
    {
        $etapa = $this->etapa->porId($id);
        if (!$etapa) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Etapa não encontrada.'];
            $this->redirect('/turmas');
        }

        $this->exigirPodeEditar((int) $etapa['projeto_id']);
        CsrfMiddleware::validate();

        $projeto = $this->projeto->porId((int) $etapa['projeto_id']);
        if ($projeto['encerrado']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto encerrado. Não é possível excluir.'];
            $this->redirect('/projetos/' . $projeto['id']);
        }

        $this->etapa->excluir($id);
        $this->audit->registrar('etapa_excluida', 'projeto_etapas', $id, $etapa, null);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Etapa excluída.'];
        $this->redirect('/projetos/' . $etapa['projeto_id']);
    }

    // ============ MOVER (subir/descer) ============

    public function mover(int $id)
    {
        $etapa = $this->etapa->porId($id);
        if (!$etapa) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Etapa não encontrada.'];
            $this->redirect('/turmas');
        }

        $this->exigirPodeEditar((int) $etapa['projeto_id']);
        CsrfMiddleware::validate();

        $direcao = $_POST['direcao'] ?? 'subir';
        if (!in_array($direcao, ['subir', 'descer'], true)) {
            $this->redirect('/projetos/' . $etapa['projeto_id']);
        }

        $this->etapa->mover($id, $direcao);
        $this->redirect('/projetos/' . $etapa['projeto_id']);
    }

    // ============ HELPERS ============

    private function exigirPodeEditar(int $projetoId): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }

        $souMaster = $u['tipo'] === 'master';
        if ($souMaster) return;

        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { http_response_code(404); exit('Projeto inexistente.'); }

        $souRep = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        if (!$souRep) { http_response_code(403); exit('Apenas representante ou master.'); }
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
}