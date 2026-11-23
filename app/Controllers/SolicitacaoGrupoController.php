<?php
// app/Controllers/SolicitacaoGrupoController.php
// Aluno pede para entrar num grupo ou para ser diretor (se o projeto permitir); o representante aprova ou recusa.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Grupo.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/SolicitacaoGrupo.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class SolicitacaoGrupoController
{
    private Projeto          $projeto;
    private Grupo            $grupo;
    private GrupoAluno       $membro;
    private GrupoDiretor     $diretor;
    private TurmaUsuario     $tu;
    private SolicitacaoGrupo $solic;

    public function __construct()
    {
        $this->projeto = new Projeto();
        $this->grupo   = new Grupo();
        $this->membro  = new GrupoAluno();
        $this->diretor = new GrupoDiretor();
        $this->tu      = new TurmaUsuario();
        $this->solic   = new SolicitacaoGrupo();
    }

    /** POST /grupos/{id}/solicitar  (tipo = entrar | diretor) */
    public function solicitar(int $grupoId)
    {
        CsrfMiddleware::validate();
        $u = $_SESSION['usuario'];
        $uid = (int) $u['id'];
        [$grupo, $projeto] = $this->carregar($grupoId);
        $volta = '/projetos/' . (int) $projeto['id'] . '/grupos';
        $tipo = $_POST['tipo'] ?? '';

        if (!$this->tu->estaAtivo((int) $projeto['turma_id'], $uid)) { $this->flash('Você não está nesta turma.'); $this->redirect('/turmas'); }
        if ($projeto['encerrado']) { $this->flash('Projeto encerrado.'); $this->redirect($volta); }

        if ($tipo === 'entrar') {
            if (!$projeto['alunos_pedem_grupo']) { $this->flash('Neste projeto quem monta os grupos é o representante.'); $this->redirect($volta); }
            if ($this->gruposDoAluno((int) $projeto['id'], $uid)) { $this->flash('Você já está num grupo deste projeto.'); $this->redirect($volta); }
            if ($this->solic->pendenteNoProjeto((int) $projeto['id'], $uid, 'entrar')) { $this->flash('Você já tem um pedido aguardando. Cancele antes de pedir outro grupo.'); $this->redirect($volta); }
        } elseif ($tipo === 'diretor') {
            if (!$projeto['alunos_pedem_diretor']) { $this->flash('Neste projeto quem escolhe os diretores é o representante.'); $this->redirect($volta); }
            if (!$this->membro->estaAtivo($grupoId, $uid)) { $this->flash('Você precisa ser membro do grupo.'); $this->redirect($volta); }
            if ($this->diretor->ehDiretorAtivo($grupoId, $uid)) { $this->flash('Você já é diretor deste grupo.'); $this->redirect($volta); }
            if (count($this->diretor->listarAtivos($grupoId)) >= (int) $projeto['diretores_por_grupo']) { $this->flash('Este grupo já tem o número máximo de diretores.'); $this->redirect($volta); }
            if ($this->solic->minhasNoProjeto((int) $projeto['id'], $uid)[$grupoId]['diretor'] ?? null) { $this->flash('Seu pedido já está aguardando.'); $this->redirect($volta); }
        } else {
            $this->flash('Pedido inválido.'); $this->redirect($volta);
        }

        $id = $this->solic->criar($grupoId, $uid, $tipo);
        (new Auditoria())->registrar('solicitacao_grupo', 'solicitacoes_grupo', $id, null, ['grupo' => $grupoId, 'tipo' => $tipo]);
        $texto = $tipo === 'entrar' ? "{$u['nome']} pediu para entrar no grupo {$grupo['nome']}." : "{$u['nome']} pediu para ser diretor do grupo {$grupo['nome']}.";
        (new Notificacao())->criarParaVarios($this->representantes((int) $projeto['turma_id']), 'papel', 'Novo pedido no projeto ' . $projeto['nome'], $texto, $volta);

        $this->flash('Pedido enviado ao representante.', 'sucesso');
        $this->redirect($volta);
    }

    /** POST /solicitacoes-grupo/{id}/responder (acao = aprovar | recusar) */
    public function responder(int $id)
    {
        CsrfMiddleware::validate();
        $s = $this->solic->porId($id);
        if (!$s || $s['status'] !== 'pendente') { $this->flash('Pedido não encontrado ou já respondido.'); $this->redirect('/turmas'); }
        [$grupo, $projeto] = $this->carregar((int) $s['grupo_id']);
        $volta = '/projetos/' . (int) $projeto['id'] . '/grupos';
        $meuId = (int) $_SESSION['usuario']['id'];
        if (!$this->podeGerir($projeto)) { http_response_code(403); exit('Só o representante responde os pedidos.'); }

        $aprovar = ($_POST['acao'] ?? '') === 'aprovar';
        $uid = (int) $s['usuario_id'];
        if ($aprovar && $s['tipo'] === 'entrar') {
            if ($this->gruposDoAluno((int) $projeto['id'], $uid)) { $this->solic->responder($id, 'recusada', $meuId); $this->flash('O aluno já entrou em outro grupo; pedido encerrado.'); $this->redirect($volta); }
            $this->membro->adicionar((int) $grupo['id'], $uid);
        } elseif ($aprovar && $s['tipo'] === 'diretor') {
            if (count($this->diretor->listarAtivos((int) $grupo['id'])) >= (int) $projeto['diretores_por_grupo']) {
                $this->flash("O grupo já tem {$projeto['diretores_por_grupo']} diretor(es). Remova um ou aumente o limite nas configurações do projeto.");
                $this->redirect($volta);
            }
            if (!$this->membro->estaAtivo((int) $grupo['id'], $uid)) { $this->solic->responder($id, 'recusada', $meuId); $this->flash('O aluno não é mais membro do grupo; pedido encerrado.'); $this->redirect($volta); }
            $this->diretor->nomear((int) $grupo['id'], $uid, $meuId);
        }
        $this->solic->responder($id, $aprovar ? 'aprovada' : 'recusada', $meuId);
        (new Auditoria())->registrar('solicitacao_grupo_' . ($aprovar ? 'aprovada' : 'recusada'), 'solicitacoes_grupo', $id, null, ['grupo' => (int) $grupo['id'], 'tipo' => $s['tipo']]);

        $oque = $s['tipo'] === 'entrar' ? "entrar no grupo {$grupo['nome']}" : "ser diretor do grupo {$grupo['nome']}";
        (new Notificacao())->criar($uid, 'papel', $aprovar ? 'Pedido aprovado' : 'Pedido recusado',
            'Seu pedido para ' . $oque . ($aprovar ? ' foi aprovado.' : ' foi recusado.'), '/grupos/' . (int) $grupo['id']);

        $this->flash($aprovar ? "Aprovado: {$s['usuario_nome']} — {$oque}." : 'Pedido recusado.', 'sucesso');
        $this->redirect($volta);
    }

    /** POST /solicitacoes-grupo/{id}/cancelar (o próprio aluno) */
    public function cancelar(int $id)
    {
        CsrfMiddleware::validate();
        $s = $this->solic->porId($id);
        if (!$s || $s['status'] !== 'pendente' || (int) $s['usuario_id'] !== (int) $_SESSION['usuario']['id']) {
            $this->flash('Pedido não encontrado.'); $this->redirect('/turmas');
        }
        $this->solic->responder($id, 'cancelada', null);
        $this->flash('Pedido cancelado.', 'sucesso');
        $this->redirect('/projetos/' . (int) $s['projeto_id'] . '/grupos');
    }

    // ============ HELPERS ============

    private function carregar(int $grupoId): array
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }
        return [$grupo, $this->projeto->porId((int) $grupo['projeto_id'])];
    }

    private function podeGerir(array $projeto): bool
    {
        $u = $_SESSION['usuario'];
        return $u['tipo'] === 'master' || $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
    }

    private function gruposDoAluno(int $projetoId, int $uid): int
    {
        $st = Database::getConnection()->prepare("
            SELECT COUNT(*) FROM grupo_alunos ga INNER JOIN grupos g ON g.id = ga.grupo_id
            WHERE g.projeto_id = ? AND ga.usuario_id = ? AND ga.saiu_em IS NULL
        ");
        $st->execute([$projetoId, $uid]);
        return (int) $st->fetchColumn();
    }

    private function representantes(int $turmaId): array
    {
        $st = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
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
