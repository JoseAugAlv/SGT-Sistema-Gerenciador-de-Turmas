<?php
// app/Controllers/GremioPublicoController.php
// Página pública do grêmio. /turmas/{id}/pagina: o presidente (representante/vice) ou o master configura.
// /g/{slug}: a página aberta, sem login (só se estiver ativa e o grêmio não estiver bloqueado).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/GremioPagina.php';
require_once __DIR__ . '/../Models/Caixa.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class GremioPublicoController
{
    private GremioPagina $pag;

    public function __construct()
    {
        $this->pag = new GremioPagina();
    }

    public function config(int $turmaId)
    {
        $turma = $this->exigirPresidente($turmaId);
        $p = $this->pag->daTurma($turmaId) ?? ['slug' => self::slugify($turma['nome']), 'ativa' => 0, 'apresentacao' => '', 'instagram' => '',
            'mostrar_diretorias' => 1, 'mostrar_eventos' => 1, 'mostrar_conquistas' => 1, 'mostrar_contas' => 0];
        $this->render('gremio/pagina_config', ['turma' => $turma, 'p' => $p]);
    }

    public function salvar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $this->exigirPresidente($turmaId);
        $voltar = '/turmas/' . $turmaId . '/pagina';
        $slug = self::slugify((string) ($_POST['slug'] ?? ''));
        if (strlen($slug) < 3) { $this->flash('O endereço precisa de pelo menos 3 letras ou números.'); $this->redirect($voltar); }
        if (in_array($slug, ['admin', 'login', 'prumo', 'api', 'master'], true) || !$this->pag->slugLivre($slug, $turmaId)) {
            $this->flash('Esse endereço já está em uso. Escolha outro.'); $this->redirect($voltar);
        }
        $insta = ltrim(trim((string) ($_POST['instagram'] ?? '')), '@');
        $insta = preg_match('/^[A-Za-z0-9._]{1,30}$/', $insta) ? $insta : null;
        $d = [
            'slug' => $slug, 'ativa' => !empty($_POST['ativa']) ? 1 : 0,
            'apresentacao' => mb_substr(trim((string) ($_POST['apresentacao'] ?? '')), 0, 1500) ?: null, 'instagram' => $insta,
            'mostrar_diretorias' => !empty($_POST['mostrar_diretorias']) ? 1 : 0, 'mostrar_eventos' => !empty($_POST['mostrar_eventos']) ? 1 : 0,
            'mostrar_conquistas' => !empty($_POST['mostrar_conquistas']) ? 1 : 0, 'mostrar_contas' => !empty($_POST['mostrar_contas']) ? 1 : 0,
        ];
        $this->pag->salvar($turmaId, $d);
        (new Auditoria())->registrar('gremio_pagina', 'gremio_paginas', $turmaId, null, ['slug' => $slug, 'ativa' => $d['ativa']]);
        $this->flash($d['ativa'] ? 'Página pública salva e no ar.' : 'Configuração salva. A página está desligada.', 'sucesso');
        $this->redirect($voltar);
    }

    /** Página aberta (sem login). */
    public function publico(string $slug)
    {
        $p = $this->pag->porSlug(strtolower($slug));
        if (!$p) {
            http_response_code(404);
            $basePath = App::getBasePath();
            require __DIR__ . '/../Views/gremio/pagina_404.php';
            return;
        }
        $tid = (int) $p['turma_id'];
        $this->render('gremio/pagina_publica', [
            'p'           => $p,
            'presidencia' => $this->pag->presidencia($tid),
            'diretorias'  => $p['mostrar_diretorias'] ? $this->pag->diretorias($tid) : [],
            'eventos'     => $p['mostrar_eventos'] ? $this->pag->eventos($tid) : [],
            'conquistas'  => $p['mostrar_conquistas'] ? $this->pag->conquistas($tid) : [],
            'contas'      => $p['mostrar_contas'] ? (new Caixa())->totais($tid) : null,
        ]);
    }

    // ============ AUXILIARES ============

    public static function slugify(string $t): string
    {
        $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t) ?: $t;
        $t = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $t));
        return substr(trim($t, '-'), 0, 60);
    }

    private function exigirPresidente(int $turmaId): array
    {
        $turma = (new Turma())->porId($turmaId);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }
        if (($turma['tipo'] ?? 'turma') !== 'gremio') { $this->flash('A página pública é só para grêmios.'); $this->redirect('/turmas/' . $turmaId); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !(new TurmaUsuario())->ehRepresentante($turmaId, (int) $u['id'])) {
            $this->flash('Só o presidente (ou o vice) configura a página do grêmio.'); $this->redirect('/turmas/' . $turmaId);
        }
        return $turma;
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        $basePath = App::getBasePath();
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
