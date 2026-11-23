<?php
// app/Controllers/CaixaController.php
// Controle de caixa da turma/grêmio. Todos da turma veem saldo, lançamentos e comprovantes (transparência);
// representante/vice/master lançam e removem. Comprovante: foto ou PDF de até 5 MB. Exporta CSV.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Caixa.php';
require_once __DIR__ . '/../Models/Diretoria.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Helpers/UploadHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class CaixaController
{
    private Caixa $cx;
    private TurmaUsuario $tu;

    public function __construct()
    {
        $this->cx = new Caixa();
        $this->tu = new TurmaUsuario();
    }

    public function index(int $turmaId)
    {
        $turma = $this->exigirMembro($turmaId);
        $mes = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : null;
        $ehGremio = ($turma['tipo'] ?? 'turma') === 'gremio';
        $this->render('caixa/index', [
            'turma'        => $turma,
            'gestor'       => $this->ehGestor($turmaId),
            'mes'          => $mes,
            'lancamentos'  => $this->cx->listar($turmaId, $mes),
            'geral'        => $this->cx->totais($turmaId),
            'doMes'        => $this->cx->totais($turmaId, $mes ?? date('Y-m')),
            'grafico'      => $this->cx->porMes($turmaId, 6),
            'contribuicoes'=> $this->cx->contribuicoes($turmaId),
            'pessoas'      => $this->pessoas($turmaId),
            'diretorias'   => $ehGremio ? (new Diretoria())->listar($turmaId) : [],
        ]);
    }

    public function salvar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $this->exigirMembro($turmaId);
        $voltar = '/turmas/' . $turmaId . '/caixa';
        if (!$this->ehGestor($turmaId)) { $this->flash('Só o representante (ou o vice) lança no caixa.'); $this->redirect($voltar); }

        $tipo  = ($_POST['tipo'] ?? '') === 'saida' ? 'saida' : 'entrada';
        $bruto = trim((string) ($_POST['valor'] ?? '0'));
        // "1.234,56" e "12,50" (formato brasileiro) ou "12.50"
        $valor = round((float) (str_contains($bruto, ',') ? str_replace(['.', ','], ['', '.'], $bruto) : $bruto), 2);
        $desc  = mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 200);
        $cat   = in_array($_POST['categoria'] ?? '', Caixa::CATEGORIAS[$tipo], true) ? $_POST['categoria'] : 'Outros';
        $data  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data'] ?? '') ? $_POST['data'] : date('Y-m-d');
        $pessoa = (int) ($_POST['pessoa_id'] ?? 0) ?: null;
        if ($pessoa && !isset($this->pessoas($turmaId)[$pessoa])) $pessoa = null;
        $dir = (int) ($_POST['diretoria_id'] ?? 0) ?: null;
        if ($dir) { $d = (new Diretoria())->porId($dir); if (!$d || (int) $d['turma_id'] !== $turmaId) $dir = null; }

        if ($valor <= 0 || $valor > 1000000) { $this->flash('Informe um valor maior que zero.'); $this->redirect($voltar); }
        if (mb_strlen($desc) < 2)            { $this->flash('Descreva o lançamento.'); $this->redirect($voltar); }
        if ($data > date('Y-m-d'))           { $this->flash('A data não pode ser no futuro.'); $this->redirect($voltar); }

        $comp = null; $compNome = null;
        if (!empty($_FILES['comprovante']) && ($_FILES['comprovante']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $r = UploadHelper::upload($_FILES['comprovante'], 'uploads/caixa/' . $turmaId, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 5 * 1048576);
            if (!$r['success']) { $this->flash('Comprovante: ' . $r['message'] . ' Use foto ou PDF de até 5 MB.'); $this->redirect($voltar); }
            $comp = $r['arquivo']; $compNome = mb_substr(basename((string) $_FILES['comprovante']['name']), 0, 200);
        }
        $id = $this->cx->criar(['turma_id' => $turmaId, 'tipo' => $tipo, 'valor' => $valor, 'descricao' => $desc, 'categoria' => $cat, 'data' => $data,
            'pessoa_id' => $tipo === 'entrada' ? $pessoa : null, 'diretoria_id' => $dir, 'comprovante' => $comp, 'comprovante_nome' => $compNome,
            'registrado_por' => (int) $_SESSION['usuario']['id']]);
        (new Auditoria())->registrar('caixa_lancamento', 'caixa_lancamentos', $id, null, ['tipo' => $tipo, 'valor' => $valor, 'descricao' => $desc]);
        $this->flash(($tipo === 'entrada' ? 'Entrada' : 'Saída') . ' de R$ ' . number_format($valor, 2, ',', '.') . ' lançada.', 'sucesso');
        $this->redirect($voltar);
    }

    public function excluir(int $id)
    {
        CsrfMiddleware::validate();
        $l = $this->carregar($id);
        $voltar = '/turmas/' . (int) $l['turma_id'] . '/caixa';
        if (!$this->ehGestor((int) $l['turma_id'])) { $this->flash('Só o representante (ou o vice) remove lançamentos.'); $this->redirect($voltar); }
        $this->cx->excluir($id);
        if ($l['comprovante'] && str_starts_with($l['comprovante'], 'uploads/caixa/')) { $f = __DIR__ . '/../../public/' . $l['comprovante']; if (is_file($f)) @unlink($f); }
        (new Auditoria())->registrar('caixa_lancamento_excluido', 'caixa_lancamentos', $id, $l, null);
        $this->flash('Lançamento removido.', 'sucesso');
        $this->redirect($voltar);
    }

    /** Abre o comprovante (só membros da turma). */
    public function comprovante(int $id)
    {
        $l = $this->carregar($id);
        $f = $l['comprovante'] ? realpath(__DIR__ . '/../../public/' . $l['comprovante']) : false;
        $raiz = realpath(__DIR__ . '/../../public/uploads/caixa');
        if (!$f || !$raiz || !str_starts_with($f, $raiz) || !is_file($f)) { http_response_code(404); exit('Comprovante não encontrado.'); }
        header('Content-Type: ' . (mime_content_type($f) ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: inline; filename="' . preg_replace('/[^\w.\- ]+/u', '_', $l['comprovante_nome'] ?: basename($f)) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($f);
        exit;
    }

    /** Exporta os lançamentos em CSV (abre no Excel). */
    public function csv(int $turmaId)
    {
        $turma = $this->exigirMembro($turmaId);
        $nome = 'caixa-' . preg_replace('/[^a-z0-9]+/i', '-', $turma['nome']) . '-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Data', 'Tipo', 'Categoria', 'Descrição', 'Valor', 'Quem pagou', 'Diretoria', 'Comprovante', 'Registrado por'], ';');
        foreach (array_reverse($this->cx->listar($turmaId)) as $l) {
            fputcsv($out, [date('d/m/Y', strtotime($l['data'])), $l['tipo'] === 'entrada' ? 'Entrada' : 'Saída', $l['categoria'], $l['descricao'],
                ($l['tipo'] === 'saida' ? '-' : '') . number_format((float) $l['valor'], 2, ',', ''), $l['pessoa'] ?? '', $l['diretoria'] ?? '',
                $l['comprovante'] ? 'sim' : 'não', $l['registrado'] ?? ''], ';');
        }
        fclose($out);
        exit;
    }

    // ============ AUXILIARES ============

    private function pessoas(int $turmaId): array
    {
        $st = Database::getConnection()->prepare("SELECT u.id, u.nome FROM turma_usuarios tu INNER JOIN usuarios u ON u.id = tu.usuario_id WHERE tu.turma_id = ? AND tu.ativo = 1 ORDER BY u.nome");
        $st->execute([$turmaId]);
        return array_column($st->fetchAll(PDO::FETCH_ASSOC), 'nome', 'id');
    }

    private function carregar(int $id): array
    {
        $l = $this->cx->porId($id);
        if (!$l) { $this->flash('Lançamento não encontrado.'); $this->redirect('/turmas'); }
        $this->exigirMembro((int) $l['turma_id']);
        return $l;
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
