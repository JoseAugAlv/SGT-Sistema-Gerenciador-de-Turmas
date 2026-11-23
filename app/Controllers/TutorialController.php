<?php
// app/Controllers/TutorialController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';

class TutorialController
{
    public function index()
    {
        $tituloPagina = 'Tutorial — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();

        $usuario   = $_SESSION['usuario'] ?? null;
        $isMaster  = $usuario && $usuario['tipo'] === 'master';
        $isRep     = false;
        $isDiretor = false;

        if ($usuario && !$isMaster) {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare("
                SELECT 1 FROM turma_usuarios
                WHERE usuario_id = ? AND papel = 'representante' AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute([(int) $usuario['id']]);
            $isRep = (bool) $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT 1 FROM grupo_diretores
                WHERE usuario_id = ? AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute([(int) $usuario['id']]);
            $isDiretor = (bool) $stmt->fetchColumn();
        }

        $tours = $this->toursParaPagina();
        require __DIR__ . '/../Views/tutorial/index.php';
    }

    /** Tour feito/dispensado (JSON). tour '*' = não sugerir mais nenhum. */
    public function visto()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();
        header('Content-Type: application/json; charset=utf-8');
        $u = $_SESSION['usuario'] ?? null;
        $d = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $tour = (string) ($d['tour'] ?? '');
        if (!$u || !preg_match('/^([a-z_]{1,40}|\*)$/', $tour)) { http_response_code(422); echo '{"ok":false}'; exit; }
        $status = ($d['status'] ?? '') === 'feito' ? 'feito' : 'dispensado';
        Database::getConnection()->prepare("INSERT INTO tutorial_vistos (usuario_id, tour, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = IF(status = 'feito', 'feito', VALUES(status))")
            ->execute([(int) $u['id'], $tour, $status]);
        echo '{"ok":true}';
        exit;
    }

    /** Volta a sugerir os tours (botão na página /tutorial). */
    public function reiniciar()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();
        $this->exigirLogin();
        Database::getConnection()->prepare("DELETE FROM tutorial_vistos WHERE usuario_id = ?")->execute([(int) $_SESSION['usuario']['id']]);
        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Pronto: as sugestões de tour voltam a aparecer na primeira visita a cada tela.'];
        header('Location: ' . App::getBasePath() . '/tutorial#tours');
        exit;
    }

    /** Tours com o link para rodar na tela certa (ids da própria pessoa) e se já foram feitos. */
    private function toursParaPagina(): array
    {
        $cfg = require __DIR__ . '/../Config/tours.php';
        $u = $_SESSION['usuario'] ?? null;
        $ids = ['turma' => null, 'projeto' => null, 'grupo' => null, 'reuniao' => null];
        $vistos = [];
        if ($u) {
            $pdo = Database::getConnection();
            $uid = (int) $u['id'];
            $q = fn($sql, $p) => (function () use ($pdo, $sql, $p) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchColumn() ?: null; })();
            $ids['turma'] = $u['tipo'] === 'master' ? $q("SELECT id FROM turmas ORDER BY id LIMIT 1", []) : $q("SELECT turma_id FROM turma_usuarios WHERE usuario_id = ? AND ativo = 1 ORDER BY papel = 'representante' DESC, id LIMIT 1", [$uid]);
            if ($ids['turma']) {
                $ids['projeto'] = $q("SELECT id FROM projetos WHERE turma_id = ? ORDER BY encerrado, id DESC LIMIT 1", [$ids['turma']]);
                $ids['reuniao'] = $q("SELECT id FROM reunioes WHERE turma_id = ? ORDER BY data DESC LIMIT 1", [$ids['turma']]);
            }
            $ids['grupo'] = $q("SELECT g.id FROM grupo_alunos ga INNER JOIN grupos g ON g.id = ga.grupo_id WHERE ga.usuario_id = ? AND ga.saiu_em IS NULL ORDER BY ga.id DESC LIMIT 1", [$uid])
                ?? ($ids['projeto'] ? $q("SELECT id FROM grupos WHERE projeto_id = ? ORDER BY id LIMIT 1", [$ids['projeto']]) : null);
            $st = $pdo->prepare("SELECT tour, status FROM tutorial_vistos WHERE usuario_id = ?");
            $st->execute([$uid]);
            $vistos = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'status', 'tour');
        }
        $out = [];
        foreach ($cfg as $id => $t) {
            $url = $t['abrir'];
            $falta = null;
            foreach ($ids as $k => $v) if (str_contains($url, '{' . $k . '}')) { if ($v) $url = str_replace('{' . $k . '}', (string) $v, $url); else $falta = $k; }
            $out[$id] = $t + ['url' => $falta ? null : App::getBasePath() . $url . (str_contains($url, '?') ? '&' : '?') . 'tour=' . $id, 'falta' => $falta, 'feito' => ($vistos[$id] ?? '') === 'feito'];
        }
        return $out;
    }

    public function aluno()
    {
        $this->exigirLogin();
        $tituloPagina = 'Tutorial do Aluno — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/aluno.php';
    }

    public function diretor()
    {
        $this->exigirLogin();
        $tituloPagina = 'Tutorial do Diretor — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/diretor.php';
    }

    public function representante()
    {
        $this->exigirLogin();
        $tituloPagina = 'Tutorial do Representante — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/representante.php';
    }

    // ============ GALERIA DE EXEMPLOS ============

    public function exemplos()
    {
        $tituloPagina = 'Exemplos de telas — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/exemplos.php';
    }

    /**
     * Página de demo renderizada DENTRO do iframe.
     * Sem header/nav/footer do sistema — layout próprio.
     */
    public function demo(string $slug)
    {
        $permitidos = [
            'dashboard-aluno',
            'dashboard-diretor',
            'dashboard-representante',
            'turma-detalhe',
            'projeto-detalhe',
            'grupo-detalhe',
            'criterios',
            'avaliacoes-rep',
            'avaliacoes-diretor',
            'avaliacoes-pares',
            'avaliacoes-auto',
            'minhas-notas',
            'atas-rep',
            'atas-diretor',
            'materiais',
            'relatorio-geral',
            'notificacoes',
            'conceitos',
        ];

        if (!in_array($slug, $permitidos, true)) {
            http_response_code(404);
            exit('Exemplo não disponível.');
        }

        $arquivo = __DIR__ . '/../Views/tutorial/demos/' . $slug . '.php';
        if (!file_exists($arquivo)) {
            http_response_code(404);
            exit('Demo não encontrada.');
        }

        // Layout base da demo
        $basePath = App::getBasePath();
        $appName  = App::getName();
        $ativo    = $slug;
        $conteudo = $arquivo;

        require __DIR__ . '/../Views/tutorial/demos/_layout.php';
    }

    public function master()
    {
        $this->exigirLogin();
        if (empty($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'master') {
            http_response_code(403);
            exit('Acesso restrito ao master.');
        }
        $tituloPagina = 'Tutorial do Master — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/master.php';
    }

    private function exigirLogin(): void
    {
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }
    }
}