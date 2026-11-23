<?php
// app/Controllers/TarefaController.php
// Kanban de tarefas do grupo. Veem e mexem: integrantes e diretores do grupo, representante/vice e master.
// Excluir: quem criou, diretor do grupo ou representante. Responsável novo recebe notificação.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Grupo.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Tarefa.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class TarefaController
{
    private Tarefa $tar;

    public function __construct()
    {
        $this->tar = new Tarefa();
    }

    public function quadro(int $grupoId)
    {
        [$grupo, $projeto, $pessoas] = $this->acesso($grupoId);
        $this->render('grupos/tarefas', [
            'grupo'   => $grupo,
            'projeto' => $projeto,
            'quadro'  => $this->tar->quadro($grupoId),
            'pessoas' => $pessoas,
            'atas'    => $this->tar->atasDoGrupo($grupoId),
            'participacao' => $this->tar->participacao($grupoId),
            'gestor'  => $this->ehGestor($grupoId, $projeto),
        ]);
    }

    public function salvar(int $grupoId)
    {
        CsrfMiddleware::validate();
        [$grupo, $projeto, $pessoas] = $this->acesso($grupoId);
        $d = $this->ler($grupoId, $pessoas);
        if (is_string($d)) { $this->flash($d); $this->redirect('/grupos/' . $grupoId . '/tarefas'); }
        $id = $this->tar->criar($d + ['grupo_id' => $grupoId, 'criado_por' => (int) $_SESSION['usuario']['id']]);
        $this->avisarResponsavel($d['responsavel_id'], $d['titulo'], $grupo, $id);
        $this->flash('Tarefa criada.', 'sucesso');
        $this->redirect('/grupos/' . $grupoId . '/tarefas');
    }

    public function atualizar(int $id)
    {
        CsrfMiddleware::validate();
        $t = $this->carregar($id);
        [$grupo, , $pessoas] = $this->acesso((int) $t['grupo_id']);
        $d = $this->ler((int) $t['grupo_id'], $pessoas);
        if (is_string($d)) { $this->flash($d); $this->redirect('/grupos/' . (int) $t['grupo_id'] . '/tarefas'); }
        $this->tar->atualizar($id, $d);
        if ($d['responsavel_id'] && (int) $d['responsavel_id'] !== (int) $t['responsavel_id']) $this->avisarResponsavel($d['responsavel_id'], $d['titulo'], $grupo, $id);
        $this->flash('Tarefa atualizada.', 'sucesso');
        $this->redirect('/grupos/' . (int) $t['grupo_id'] . '/tarefas');
    }

    /** Arrastar/mover (JSON): coluna nova e a ordem dos ids nessa coluna. */
    public function mover(int $id)
    {
        CsrfMiddleware::validate();
        $t = $this->carregar($id);
        $this->acesso((int) $t['grupo_id']);
        $coluna = (string) ($_POST['coluna'] ?? '');
        header('Content-Type: application/json; charset=utf-8');
        if (!array_key_exists($coluna, Tarefa::COLUNAS)) { echo json_encode(['ok' => false]); exit; }
        $ordem = array_filter(array_map('intval', explode(',', (string) ($_POST['ordem'] ?? ''))));
        if (!in_array($id, $ordem, true)) $ordem[] = $id;
        $this->tar->mover($id, (int) $t['grupo_id'], $coluna, $ordem);
        echo json_encode(['ok' => true]);
        exit;
    }

    public function excluir(int $id)
    {
        CsrfMiddleware::validate();
        $t = $this->carregar($id);
        [, $projeto] = $this->acesso((int) $t['grupo_id']);
        if ((int) $t['criado_por'] !== (int) $_SESSION['usuario']['id'] && !$this->ehGestor((int) $t['grupo_id'], $projeto)) {
            $this->flash('Só quem criou, o diretor do grupo ou o representante exclui.');
        } else {
            $this->tar->excluir($id);
            $this->flash('Tarefa excluída.', 'sucesso');
        }
        $this->redirect('/grupos/' . (int) $t['grupo_id'] . '/tarefas');
    }

    // ============ AUXILIARES ============

    /** Valida o formulário. Retorna os dados ou a mensagem de erro. */
    private function ler(int $grupoId, array $pessoas)
    {
        $titulo = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 150);
        if (mb_strlen($titulo) < 2) return 'Dê um título à tarefa.';
        $resp = (int) ($_POST['responsavel_id'] ?? 0) ?: null;
        if ($resp && !isset($pessoas[$resp])) $resp = null;
        $prazo = trim((string) ($_POST['prazo'] ?? ''));
        $prazo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $prazo) ? $prazo : null;
        $ata = (int) ($_POST['ata_id'] ?? 0) ?: null;
        if ($ata && !in_array($ata, array_map(fn($a) => (int) $a['id'], $this->tar->atasDoGrupo($grupoId)), true)) $ata = null;
        return ['titulo' => $titulo, 'descricao' => trim((string) ($_POST['descricao'] ?? '')) ?: null, 'responsavel_id' => $resp, 'prazo' => $prazo, 'ata_id' => $ata];
    }

    private function avisarResponsavel(?int $uid, string $titulo, array $grupo, int $tarefaId): void
    {
        if (!$uid || $uid === (int) $_SESSION['usuario']['id']) return;
        (new Notificacao())->criar($uid, 'prazo', 'Nova tarefa para você: ' . $titulo,
            $_SESSION['usuario']['nome'] . ' colocou você como responsável no grupo ' . $grupo['nome'] . '.', '/grupos/' . (int) $grupo['id'] . '/tarefas', $tarefaId);
    }

    private function carregar(int $id): array
    {
        $t = $this->tar->porId($id);
        if (!$t) { $this->flash('Tarefa não encontrada.'); $this->redirect('/turmas'); }
        return $t;
    }

    /**
     * Confere o acesso e devolve [grupo, projeto, pessoas do grupo (id => nome)].
     * Acesso: integrante ou diretor do grupo, representante/vice da turma ou master.
     */
    private function acesso(int $grupoId): array
    {
        $grupo = (new Grupo())->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }
        $projeto = (new Projeto())->porId((int) $grupo['projeto_id']);
        $pessoas = [];
        foreach ((new GrupoAluno())->listarAtivos($grupoId) as $m) $pessoas[(int) $m['usuario_id']] = $m['nome'];
        foreach ((new GrupoDiretor())->listarAtivos($grupoId) as $m) $pessoas[(int) $m['usuario_id']] = $m['nome'] . ' (diretor)';
        $uid = (int) $_SESSION['usuario']['id'];
        if (!isset($pessoas[$uid]) && !$this->ehGestor($grupoId, $projeto)) {
            $this->flash('O quadro de tarefas é do grupo: só os integrantes, os diretores e o representante acessam.');
            $this->redirect('/grupos/' . $grupoId);
        }
        return [$grupo, $projeto, $pessoas];
    }

    /** Diretor do grupo, representante/vice da turma ou master. */
    private function ehGestor(int $grupoId, array $projeto): bool
    {
        $u = $_SESSION['usuario'];
        if ($u['tipo'] === 'master' || (new TurmaUsuario())->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) return true;
        foreach ((new GrupoDiretor())->listarAtivos($grupoId) as $d) if ((int) $d['usuario_id'] === (int) $u['id']) return true;
        return false;
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
