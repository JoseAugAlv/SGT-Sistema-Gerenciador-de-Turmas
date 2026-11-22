<?php
// app/Controllers/ComercialController.php
// Área comercial do master: percentuais de desconto, cupons, planos (leitura) e vendas.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Plano.php';
require_once __DIR__ . '/../Models/Assinatura.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Services/PrecoService.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ComercialController
{
    // Configurações que o master pode editar: chave => [rótulo, mínimo, máximo]
    const EDITAVEIS = [
        'cupom_turma_pct'  => ['Cupom em planos de turma (%)', 0, 90],
        'cupom_gremio_pct' => ['Cupom em planos de grêmio (%)', 0, 90],
        'indicacao_pct'    => ['Indicação: desconto da turma indicada (%)', 0, 90],
    ];

    private Plano      $plano;
    private Assinatura $ass;
    private Auditoria  $audit;

    public function __construct()
    {
        if (($_SESSION['usuario']['tipo'] ?? '') !== 'master') { http_response_code(403); exit('Apenas o master.'); }
        $this->plano = new Plano();
        $this->ass   = new Assinatura();
        $this->audit = new Auditoria();
    }

    public function index()
    {
        $config = [];
        foreach (array_keys(self::EDITAVEIS) as $k) $config[$k] = PrecoService::config($k, '0');

        $vendas = $this->ass->listarVendas();
        $this->render('master/comercial', [
            'editaveis' => self::EDITAVEIS,
            'config'    => $config,
            'modo'      => PrecoService::config('modo_pagamento', 'teste'),
            'cupons'    => $this->plano->listarCupons(),
            'planos'    => $this->plano->listar(null, false),
            'vendas'    => $vendas,
            'recebido'  => array_sum(array_map(fn($v) => (float) $v['pago'], $vendas)),
            'ativas'    => count(array_filter($vendas, fn($v) => $v['status'] === 'ativa')),
        ]);
    }

    public function salvarConfig()
    {
        CsrfMiddleware::validate();
        $antes = []; $depois = [];
        $stmt = Database::getConnection()->prepare("UPDATE config_comercial SET valor = ? WHERE chave = ?");
        foreach (self::EDITAVEIS as $k => [$rotulo, $min, $max]) {
            if (!isset($_POST[$k])) continue;
            $v = (float) str_replace(',', '.', $_POST[$k]);
            if ($v < $min || $v > $max) { $this->flash("{$rotulo}: use um valor entre {$min} e {$max}."); $this->redirect('/master/comercial'); }
            $antes[$k] = PrecoService::config($k);
            $depois[$k] = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
            $stmt->execute([$depois[$k], $k]);
        }
        $this->audit->registrar('config_comercial_alterada', 'config_comercial', null, $antes, $depois);
        $this->flash('Percentuais salvos. Valem para as próximas compras; as já feitas não mudam.', 'sucesso');
        $this->redirect('/master/comercial');
    }

    public function criarCupom()
    {
        CsrfMiddleware::validate();
        $codigo = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['codigo'] ?? ''));
        $tipo   = $_POST['tipo_plano'] ?? 'todos';
        $pct    = trim($_POST['percentual'] ?? '');
        $pct    = $pct === '' ? null : (float) str_replace(',', '.', $pct);
        $ate    = trim($_POST['valido_ate'] ?? '') ?: null;
        $usos   = trim($_POST['usos_max'] ?? '');
        $usos   = $usos === '' ? null : (int) $usos;

        $erros = [];
        if (strlen($codigo) < 3)                                 $erros[] = 'O código precisa de pelo menos 3 letras ou números.';
        if (!in_array($tipo, ['turma', 'gremio', 'todos'], true)) $erros[] = 'Tipo de plano inválido.';
        if ($pct !== null && ($pct <= 0 || $pct > 90))           $erros[] = 'O percentual deve ficar entre 0,01 e 90.';
        if ($ate && $ate < date('Y-m-d'))                        $erros[] = 'A validade já passou.';
        if ($usos !== null && $usos < 1)                         $erros[] = 'O limite de usos deve ser pelo menos 1.';
        $existe = Database::getConnection()->prepare("SELECT 1 FROM cupons WHERE codigo = ?");
        $existe->execute([$codigo]);
        if ($existe->fetchColumn())                              $erros[] = "Já existe o cupom {$codigo}.";
        if ($erros) { $this->flash(implode(' ', $erros)); $this->redirect('/master/comercial'); }

        $id = $this->plano->criarCupom([
            'codigo' => $codigo, 'tipo_plano' => $tipo, 'percentual' => $pct,
            'valido_ate' => $ate, 'usos_max' => $usos, 'criado_por' => (int) $_SESSION['usuario']['id'],
        ]);
        $this->audit->registrar('cupom_criado', 'cupons', $id, null, ['codigo' => $codigo, 'percentual' => $pct]);
        $this->flash("Cupom {$codigo} criado.", 'sucesso');
        $this->redirect('/master/comercial');
    }

    public function alternarCupom(int $id)
    {
        CsrfMiddleware::validate();
        $this->plano->alternarCupom($id);
        $this->audit->registrar('cupom_alternado', 'cupons', $id, null, null);
        $this->flash('Cupom atualizado.', 'sucesso');
        $this->redirect('/master/comercial');
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
