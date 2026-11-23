<?php
// app/Controllers/OrcamentoController.php
// Orçamento do projeto e prestação de contas da turma/grêmio.
// Veem: todos da turma. Mexem: representante/presidente, vice, master e (no grêmio) o diretor/coordenador da
// diretoria responsável pelo projeto. Gasto/arrecadação do projeto vira lançamento no caixa (com comprovante).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Orcamento.php';
require_once __DIR__ . '/../Models/Caixa.php';
require_once __DIR__ . '/../Models/Diretoria.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/UploadHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class OrcamentoController
{
    private Orcamento $orc;
    private TurmaUsuario $tu;

    public function __construct()
    {
        $this->orc = new Orcamento();
        $this->tu = new TurmaUsuario();
    }

    public function projeto(int $projetoId)
    {
        $projeto = $this->carregarProjeto($projetoId);
        $turma = (new Turma())->porId((int) $projeto['turma_id']);
        $this->render('orcamento/projeto', [
            'projeto'    => $projeto,
            'turma'      => $turma,
            'itens'      => $this->orc->itens($projetoId),
            'lancamentos'=> $this->orc->lancamentos($projetoId),
            'resumo'     => $this->orc->resumo($projetoId),
            'pode'       => $this->podeMexer($projeto),
            'podeDiretoria' => $this->ehGestorTurma((int) $projeto['turma_id']),
            'diretorias' => ($turma['tipo'] ?? 'turma') === 'gremio' ? (new Diretoria())->listar((int) $projeto['turma_id']) : [],
        ]);
    }

    public function item(int $projetoId)
    {
        CsrfMiddleware::validate();
        $projeto = $this->exigirMexer($projetoId);
        $voltar = '/projetos/' . $projetoId . '/orcamento';
        if (($_POST['acao'] ?? '') === 'remover') {
            $this->orc->removerItem((int) ($_POST['item_id'] ?? 0), $projetoId);
            $this->flash('Item removido do orçamento.', 'sucesso');
            $this->redirect($voltar);
        }
        $desc = mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 150);
        $valor = self::valor($_POST['valor'] ?? '');
        if (mb_strlen($desc) < 2 || $valor <= 0) { $this->flash('Informe o item e um valor maior que zero.'); $this->redirect($voltar); }
        $this->orc->adicionarItem($projetoId, $desc, $valor);
        $this->flash('Item adicionado ao orçamento.', 'sucesso');
        $this->redirect($voltar);
    }

    /** Lança gasto/arrecadação do projeto no caixa (com comprovante). */
    public function lancar(int $projetoId)
    {
        CsrfMiddleware::validate();
        $projeto = $this->exigirMexer($projetoId);
        $voltar = '/projetos/' . $projetoId . '/orcamento';
        $tipo = ($_POST['tipo'] ?? '') === 'entrada' ? 'entrada' : 'saida';
        $valor = self::valor($_POST['valor'] ?? '');
        $desc = mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 200);
        $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data'] ?? '') && $_POST['data'] <= date('Y-m-d') ? $_POST['data'] : date('Y-m-d');
        if ($valor <= 0 || mb_strlen($desc) < 2) { $this->flash('Informe a descrição e um valor maior que zero.'); $this->redirect($voltar); }
        $comp = null; $compNome = null;
        if (!empty($_FILES['comprovante']) && ($_FILES['comprovante']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $r = UploadHelper::upload($_FILES['comprovante'], 'uploads/caixa/' . (int) $projeto['turma_id'], ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 5 * 1048576);
            if (!$r['success']) { $this->flash('Comprovante: ' . $r['message'] . ' Use foto ou PDF de até 5 MB.'); $this->redirect($voltar); }
            $comp = $r['arquivo']; $compNome = mb_substr(basename((string) $_FILES['comprovante']['name']), 0, 200);
        }
        $cx = new Caixa();
        $id = $cx->criar(['turma_id' => (int) $projeto['turma_id'], 'tipo' => $tipo, 'valor' => $valor, 'descricao' => $desc,
            'categoria' => $tipo === 'saida' ? 'Material' : 'Evento', 'data' => $data, 'pessoa_id' => null,
            'diretoria_id' => $projeto['diretoria_id'] ? (int) $projeto['diretoria_id'] : null,
            'comprovante' => $comp, 'comprovante_nome' => $compNome, 'registrado_por' => (int) $_SESSION['usuario']['id']]);
        Database::getConnection()->prepare("UPDATE caixa_lancamentos SET projeto_id = ? WHERE id = ?")->execute([$projetoId, $id]);
        (new Auditoria())->registrar('orcamento_lancamento', 'caixa_lancamentos', $id, null, ['projeto' => $projetoId, 'tipo' => $tipo, 'valor' => $valor]);
        $this->flash(($tipo === 'saida' ? 'Gasto' : 'Arrecadação') . ' lançado no caixa do projeto.', 'sucesso');
        $this->redirect($voltar);
    }

    /** Diretoria responsável (grêmio). Só representante/presidente, vice ou master. */
    public function diretoria(int $projetoId)
    {
        CsrfMiddleware::validate();
        $projeto = $this->carregarProjeto($projetoId);
        $voltar = '/projetos/' . $projetoId . '/orcamento';
        if (!$this->ehGestorTurma((int) $projeto['turma_id'])) { $this->flash('Só o presidente (ou o vice) define a diretoria.'); $this->redirect($voltar); }
        $dir = (int) ($_POST['diretoria_id'] ?? 0) ?: null;
        if ($dir) { $d = (new Diretoria())->porId($dir); if (!$d || (int) $d['turma_id'] !== (int) $projeto['turma_id']) $dir = null; }
        $this->orc->definirDiretoria($projetoId, $dir);
        $this->flash('Diretoria responsável atualizada.', 'sucesso');
        $this->redirect($voltar);
    }

    /** Prestação de contas da turma/grêmio (por diretoria e por projeto), pronta para imprimir. */
    public function prestacao(int $turmaId)
    {
        $turma = (new Turma())->porId($turmaId);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->estaAtivo($turmaId, (int) $u['id'])) { $this->flash('Você não está nesta turma.'); $this->redirect('/turmas'); }
        $this->render('orcamento/prestacao', ['turma' => $turma, 'dados' => $this->orc->prestacao($turmaId), 'caixa' => (new Caixa())->totais($turmaId)]);
    }

    // ============ AUXILIARES ============

    private static function valor($bruto): float
    {
        $b = trim((string) $bruto);
        $v = round((float) (str_contains($b, ',') ? str_replace(['.', ','], ['', '.'], $b) : $b), 2);
        return $v > 0 && $v <= 1000000 ? $v : 0;
    }

    private function carregarProjeto(int $id): array
    {
        $p = (new Projeto())->porId($id);
        if (!$p) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->estaAtivo((int) $p['turma_id'], (int) $u['id'])) { $this->flash('Você não está nesta turma.'); $this->redirect('/turmas'); }
        if (!array_key_exists('diretoria_id', $p)) {
            $st = Database::getConnection()->prepare("SELECT diretoria_id FROM projetos WHERE id = ?");
            $st->execute([$id]);
            $p['diretoria_id'] = $st->fetchColumn() ?: null;
        }
        return $p;
    }

    private function exigirMexer(int $projetoId): array
    {
        $p = $this->carregarProjeto($projetoId);
        if (!$this->podeMexer($p)) { $this->flash('Só o representante/presidente, o vice ou a diretoria responsável mexem no orçamento.'); $this->redirect('/projetos/' . $projetoId . '/orcamento'); }
        if (!empty($p['encerrado'])) { $this->flash('O projeto foi encerrado: o orçamento fica só para consulta.', 'aviso'); $this->redirect('/projetos/' . $projetoId . '/orcamento'); }
        return $p;
    }

    private function ehGestorTurma(int $turmaId): bool
    {
        $u = $_SESSION['usuario'];
        return $u['tipo'] === 'master' || $this->tu->ehRepresentante($turmaId, (int) $u['id']);
    }

    private function podeMexer(array $p): bool
    {
        if ($this->ehGestorTurma((int) $p['turma_id'])) return true;
        if (empty($p['diretoria_id'])) return false;
        return in_array((new Diretoria())->cargoDe((int) $p['diretoria_id'], (int) $_SESSION['usuario']['id']), ['diretor', 'coordenador'], true);
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
