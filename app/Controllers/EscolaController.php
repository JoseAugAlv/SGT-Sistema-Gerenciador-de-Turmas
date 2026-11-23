<?php
// app/Controllers/EscolaController.php
// Página pública "Para escolas" (/escolas): o que o pacote oferece, simulador de preço por volume
// (descontos configuráveis pelo master: escola_desc_5/10/20) e formulário de pedido de proposta
// (salvo em escola_contatos; os masters recebem notificação). Master muda o status em Comercial.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Plano.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Services/PrecoService.php';
require_once __DIR__ . '/../Helpers/RateLimiter.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class EscolaController
{
    const STATUS = ['novo' => 'Novo', 'em_contato' => 'Em contato', 'fechado' => 'Fechado', 'descartado' => 'Descartado'];

    /** Faixas de desconto por número de turmas: [mínimo => %]. */
    public static function faixas(): array
    {
        return [
            5  => (float) PrecoService::config('escola_desc_5', '10'),
            10 => (float) PrecoService::config('escola_desc_10', '15'),
            20 => (float) PrecoService::config('escola_desc_20', '20'),
        ];
    }

    public static function descontoPara(int $turmas): float
    {
        $d = 0;
        foreach (self::faixas() as $min => $pct) if ($turmas >= $min) $d = $pct;
        return $d;
    }

    public function index()
    {
        $planos = array_values(array_filter((new Plano())->listar('turma'), fn($p) => !empty($p['meses'])));
        $basePath = App::getBasePath();
        $faixas = self::faixas();
        $enviado = !empty($_SESSION['escola_enviado']);
        unset($_SESSION['escola_enviado']);
        $antigo = $_SESSION['escola_form'] ?? [];
        unset($_SESSION['escola_form']);
        $erro = $_SESSION['escola_erro'] ?? null;
        unset($_SESSION['escola_erro']);
        require __DIR__ . '/../Views/escolas/index.php';
    }

    public function contato()
    {
        CsrfMiddleware::validate();
        $d = [
            'nome'     => mb_substr(trim((string) ($_POST['nome'] ?? '')), 0, 120),
            'escola'   => mb_substr(trim((string) ($_POST['escola'] ?? '')), 0, 150),
            'cidade'   => mb_substr(trim((string) ($_POST['cidade'] ?? '')), 0, 100) ?: null,
            'email'    => mb_substr(trim((string) ($_POST['email'] ?? '')), 0, 150),
            'telefone' => mb_substr(preg_replace('/[^\d()+\- ]/', '', (string) ($_POST['telefone'] ?? '')), 0, 30) ?: null,
            'turmas'   => max(1, min(500, (int) ($_POST['turmas'] ?? 1))),
            'plano_id' => (int) ($_POST['plano_id'] ?? 0) ?: null,
            'mensagem' => mb_substr(trim((string) ($_POST['mensagem'] ?? '')), 0, 2000) ?: null,
        ];
        $voltar = App::getBasePath() . '/escolas#proposta';
        // campo escondido contra robôs: gente não preenche
        if (!empty($_POST['site'])) { header('Location: ' . $voltar); exit; }
        $erro = null;
        if (mb_strlen($d['nome']) < 3)                        $erro = 'Informe o seu nome.';
        elseif (mb_strlen($d['escola']) < 3)                  $erro = 'Informe o nome da escola.';
        elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $erro = 'Informe um email válido.';
        elseif (!RateLimiter::check('escola_' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600)) $erro = 'Muitos pedidos seguidos. Tente de novo mais tarde.';
        if ($erro) { $_SESSION['escola_erro'] = $erro; $_SESSION['escola_form'] = $d; header('Location: ' . $voltar); exit; }

        $pdo = Database::getConnection();
        $pdo->prepare("INSERT INTO escola_contatos (nome, escola, cidade, email, telefone, turmas, plano_id, mensagem, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$d['nome'], $d['escola'], $d['cidade'], $d['email'], $d['telefone'], $d['turmas'], $d['plano_id'], $d['mensagem'], $_SERVER['REMOTE_ADDR'] ?? null]);
        RateLimiter::increment('escola_' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $masters = $pdo->query("SELECT id FROM usuarios WHERE tipo = 'master' AND ativo = 1")->fetchAll(PDO::FETCH_COLUMN);
        (new Notificacao())->criarParaVarios($masters, 'plano', 'Nova escola interessada: ' . $d['escola'],
            $d['nome'] . ' pediu proposta para ' . $d['turmas'] . ' turma(s)' . ($d['cidade'] ? ' em ' . $d['cidade'] : '') . '.', '/master/comercial#escolas');
        $_SESSION['escola_enviado'] = true;
        header('Location: ' . $voltar);
        exit;
    }

    /** Master: muda o status do pedido. */
    public function status(int $id)
    {
        CsrfMiddleware::validate();
        if (($_SESSION['usuario']['tipo'] ?? '') !== 'master') { http_response_code(403); exit('Apenas o master.'); }
        $st = array_key_exists($_POST['status'] ?? '', self::STATUS) ? $_POST['status'] : 'novo';
        Database::getConnection()->prepare("UPDATE escola_contatos SET status = ? WHERE id = ?")->execute([$st, $id]);
        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Pedido da escola atualizado.'];
        header('Location: ' . App::getBasePath() . '/master/comercial#escolas');
        exit;
    }
}
