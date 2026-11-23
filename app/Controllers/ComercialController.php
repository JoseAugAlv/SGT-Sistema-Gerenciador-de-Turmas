<?php
// app/Controllers/ComercialController.php
// Área comercial do master: percentuais de desconto, cupons, planos, vendas e ajustes em cada assinatura.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Plano.php';
require_once __DIR__ . '/../Models/Assinatura.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Services/PrecoService.php';
require_once __DIR__ . '/../Services/CobrancaService.php';
require_once __DIR__ . '/../Services/IndicacaoService.php';
require_once __DIR__ . '/../Models/DadosCobranca.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ComercialController
{
    // Configurações que o master pode editar: chave => [rótulo, mínimo, máximo]
    const EDITAVEIS = [
        'cupom_turma_pct'  => ['Cupom em planos de turma (%)', 0, 90],
        'cupom_gremio_pct' => ['Cupom em planos de grêmio (%)', 0, 90],
        'indicacao_pct'    => ['Indicação: desconto da turma indicada, na 1ª compra (%)', 0, 90],
        'desconto_cruzado_pct' => ['Desconto de quem já tem plano de turma no plano do grêmio, e vice-versa (%)', 0, 90],
        'indicacao_recompensa_pct' => ['Indicação: desconto de quem indicou, nas próximas parcelas ou na próxima compra (%)', 0, 90],
        'tolerancia_dias'  => ['Tolerância de atraso antes do bloqueio (dias)', 0, 60],
        'aviso_vencimento_dias' => ['Avisar o representante antes do fim do plano (dias)', 0, 60],
        'teste_dia_dias'   => ['Teste grátis do Dia a dia (dias; 0 = sem teste)', 0, 60],
        'renovacao_cupom_pct' => ['Cupom automático de renovação, perto do fim do plano (%; 0 = sem cupom)', 0, 90],
        'escola_desc_5'    => ['Para escolas: desconto a partir de 5 turmas (%)', 0, 90],
        'escola_desc_10'   => ['Para escolas: desconto a partir de 10 turmas (%)', 0, 90],
        'escola_desc_20'   => ['Para escolas: desconto a partir de 20 turmas (%)', 0, 90],
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
            'exigirPlano' => PrecoService::config('exigir_plano', '1') === '1',
            'turmas'    => Database::getConnection()->query("SELECT id, nome, isenta_plano FROM turmas ORDER BY isenta_plano, nome")->fetchAll(PDO::FETCH_ASSOC),
            'escolas'   => Database::getConnection()->query("SELECT c.*, p.nome AS plano_nome FROM escola_contatos c LEFT JOIN planos p ON p.id = c.plano_id ORDER BY c.status = 'novo' DESC, c.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC),
            'cupons'    => $this->plano->listarCupons(),
            'pacotes'   => array_map(fn($pk) => $pk + ['codigos' => $this->plano->cuponsDoPacote((int) $pk['id'])], $this->plano->listarPacotes()),
            'indicacoes' => (new IndicacaoService())->todas(50),
            'planos'    => $this->plano->listar(null, false),
            'vendas'    => $vendas,
            'recebido'  => array_sum(array_map(fn($v) => (float) $v['pago'], $vendas)),
            'ativas'    => count(array_filter($vendas, fn($v) => $v['status'] === 'ativa')),
        ]);
    }

    /** Liga/desliga a cobrança real pelo Mercado Pago (precisa do MP_ACCESS_TOKEN no .env). */
    public function modo()
    {
        CsrfMiddleware::validate();
        $modo = ($_POST['modo'] ?? '') === 'mercadopago' ? 'mercadopago' : 'teste';
        if ($modo === 'mercadopago' && (string) App::get('MP_ACCESS_TOKEN', '') === '') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Coloque o MP_ACCESS_TOKEN no .env antes de ligar a cobrança real.'];
        } else {
            Database::getConnection()->prepare("UPDATE config_comercial SET valor = ? WHERE chave = 'modo_pagamento'")->execute([$modo]);
            $this->audit->registrar('modo_pagamento', 'config_comercial', null, null, ['modo' => $modo]);
            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => $modo === 'mercadopago' ? 'Cobrança real ligada: os próximos Pix serão pelo Mercado Pago.' : 'Voltamos ao modo de teste (pagamentos simulados).'];
        }
        header('Location: ' . App::getBasePath() . '/master/comercial');
        exit;
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
        $antes['exigir_plano']  = PrecoService::config('exigir_plano');
        $depois['exigir_plano'] = !empty($_POST['exigir_plano']) ? '1' : '0';
        $stmt->execute([$depois['exigir_plano'], 'exigir_plano']);
        $this->audit->registrar('config_comercial_alterada', 'config_comercial', null, $antes, $depois);
        $this->flash('Percentuais salvos. Valem para as próximas compras; as já feitas não mudam.', 'sucesso');
        $this->redirect('/master/comercial');
    }

    /** Edita nome, duração, preço, piso, parcelas, projetos, pessoas, ordem e se o plano está à venda. */
    public function salvarPlano(int $id)
    {
        CsrfMiddleware::validate();
        $plano = $this->plano->porId($id);
        if (!$plano) { $this->flash('Plano não encontrado.'); $this->redirect('/master/comercial'); }

        [$d, $erros] = $this->lerPlano($plano['tabela_parcelas']);
        if ($erros) { $this->flash("{$plano['nome']}: " . implode(' ', $erros)); $this->redirect('/master/comercial#planos'); }

        $this->plano->atualizarPlano($id, $d);
        $this->audit->registrar('plano_alterado', 'planos', $id, array_intersect_key($plano, $d), $d);
        $this->flash("Plano {$d['nome']} salvo. Vale para as próximas compras; as já feitas não mudam.", 'sucesso');
        $this->redirect('/master/comercial#planos');
    }

    public function criarPlano()
    {
        CsrfMiddleware::validate();
        $codigo = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $_POST['codigo'] ?? ''));
        $tipo   = $_POST['tipo'] ?? 'turma';
        $tabela = $_POST['tabela_parcelas'] ?? '';
        $tabela = in_array($tabela, ['A', 'B'], true) ? $tabela : null;

        [$d, $erros] = $this->lerPlano($tabela);
        if (strlen($codigo) < 3)                         $erros[] = 'O código precisa de pelo menos 3 letras ou números.';
        elseif ($this->plano->codigoExiste($codigo))     $erros[] = "Já existe um plano com o código {$codigo}.";
        if (!in_array($tipo, ['turma', 'gremio'], true)) $erros[] = 'Tipo inválido.';
        if ($erros) { $this->flash('Novo plano: ' . implode(' ', $erros)); $this->redirect('/master/comercial#planos'); }

        $id = $this->plano->criarPlano($d + ['codigo' => $codigo, 'tipo' => $tipo, 'tabela_parcelas' => $tabela]);
        $this->audit->registrar('plano_criado', 'planos', $id, null, $d + ['codigo' => $codigo, 'tipo' => $tipo]);
        $this->flash("Plano {$d['nome']} criado.", 'sucesso');
        $this->redirect('/master/comercial#planos');
    }

    /** Isenta (ou volta a exigir) o plano de uma turma. */
    public function alternarIsencao(int $turmaId)
    {
        CsrfMiddleware::validate();
        $pdo = Database::getConnection();
        $st = $pdo->prepare("SELECT nome, isenta_plano FROM turmas WHERE id = ?");
        $st->execute([$turmaId]);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t) { $this->flash('Turma não encontrada.'); $this->redirect('/master/comercial'); }

        $novo = $t['isenta_plano'] ? 0 : 1;
        $pdo->prepare("UPDATE turmas SET isenta_plano = ? WHERE id = ?")->execute([$novo, $turmaId]);
        $this->audit->registrar('turma_isencao_plano', 'turmas', $turmaId, ['isenta_plano' => (int) $t['isenta_plano']], ['isenta_plano' => $novo]);
        $this->flash($novo ? "{$t['nome']} não precisa mais de plano." : "{$t['nome']} agora precisa de um plano ativo.", 'sucesso');

        $volta = $_POST['voltar'] ?? '';
        $this->redirect($volta === 'turma' ? '/turmas/' . $turmaId : '/master/comercial#turmas');
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

        [$regras, $errosRegras] = $this->lerRegrasCupom();
        if ($errosRegras) { $this->flash(implode(' ', $errosRegras)); $this->redirect('/master/comercial#cupons'); }

        $id = $this->plano->criarCupom([
            'codigo' => $codigo, 'tipo_plano' => $tipo, 'percentual' => $pct,
            'valido_ate' => $ate, 'usos_max' => $usos, 'criado_por' => (int) $_SESSION['usuario']['id'],
        ]);
        $this->plano->salvarRegras($id, $regras);
        $this->audit->registrar('cupom_criado', 'cupons', $id, null, ['codigo' => $codigo, 'percentual' => $pct, 'regras' => $regras]);
        $this->flash("Cupom {$codigo} criado.", 'sucesso');
        $this->redirect('/master/comercial');
    }

    public function editarCupom(int $id)
    {
        CsrfMiddleware::validate();
        $c = $this->plano->cupomPorId($id);
        if (!$c) { $this->flash('Cupom não encontrado.'); $this->redirect('/master/comercial#cupons'); }

        $tipo = $_POST['tipo_plano'] ?? 'todos';
        $pct  = trim($_POST['percentual'] ?? '');
        $pct  = $pct === '' ? null : (float) str_replace(',', '.', $pct);
        $ate  = trim($_POST['valido_ate'] ?? '') ?: null;
        $usos = trim($_POST['usos_max'] ?? '');
        $usos = $usos === '' ? null : (int) $usos;

        $erros = [];
        if (!in_array($tipo, ['turma', 'gremio', 'todos'], true)) $erros[] = 'Tipo de plano inválido.';
        if ($pct !== null && ($pct <= 0 || $pct > 90))           $erros[] = 'O percentual deve ficar entre 0,01 e 90.';
        if ($usos !== null && $usos < (int) $c['usos'])          $erros[] = "O limite de usos não pode ser menor que os {$c['usos']} já feitos.";
        if ($erros) { $this->flash("{$c['codigo']}: " . implode(' ', $erros)); $this->redirect('/master/comercial#cupons'); }

        [$regras, $errosRegras] = $this->lerRegrasCupom();
        if ($errosRegras) { $this->flash("{$c['codigo']}: " . implode(' ', $errosRegras)); $this->redirect('/master/comercial#cupons'); }

        $d = ['tipo_plano' => $tipo, 'percentual' => $pct, 'valido_ate' => $ate, 'usos_max' => $usos];
        $this->plano->atualizarCupom($id, $d);
        $this->plano->salvarRegras($id, $regras);
        $d['regras'] = $regras;
        $this->audit->registrar('cupom_alterado', 'cupons', $id, array_intersect_key($c, $d), $d);
        $this->flash("Cupom {$c['codigo']} salvo.", 'sucesso');
        $this->redirect('/master/comercial#cupons');
    }

    public function excluirCupom(int $id)
    {
        CsrfMiddleware::validate();
        $c = $this->plano->cupomPorId($id);
        if (!$c) { $this->flash('Cupom não encontrado.'); $this->redirect('/master/comercial#cupons'); }
        if (!$this->plano->excluirCupom($id)) {
            $this->flash("O cupom {$c['codigo']} já foi usado em compras e não pode ser excluído. Desative-o.");
            $this->redirect('/master/comercial#cupons');
        }
        $this->audit->registrar('cupom_excluido', 'cupons', $id, $c, null);
        $this->flash("Cupom {$c['codigo']} excluído.", 'sucesso');
        $this->redirect('/master/comercial#cupons');
    }

    // ============ ASSINATURA (AJUSTES DO MASTER) ============

    public function assinatura(int $id)
    {
        $ass = $this->carregarAssinatura($id);
        $this->render('master/assinatura', [
            'ass'      => $ass,
            'parcelas' => $this->ass->parcelas($id),
            'proxima'  => $this->ass->proximaParcela($id),
            'planos'   => $this->plano->listar($ass['plano_tipo'], false),
            'projetosUsados' => $this->ass->projetosUsados((int) $ass['turma_id']),
            'cobranca' => $ass['comprado_por'] ? (new DadosCobranca())->porUsuario((int) $ass['comprado_por']) : null,
        ]);
    }

    /** Baixa manual: o master confirma que recebeu a próxima parcela (fora do Pix do sistema). */
    public function assinaturaPagar(int $id)
    {
        CsrfMiddleware::validate();
        $ass  = $this->carregarAssinatura($id);
        $prox = $this->ass->proximaParcela($id);
        if ($ass['status'] === 'cancelada' || $ass['status'] === 'trocada' || !$prox) {
            $this->flash('Não há parcela a pagar nesta assinatura.');
            $this->redirect('/master/comercial/assinaturas/' . $id);
        }
        if (!$this->ass->trocaAindaValida($ass)) {
            $this->ass->cancelar($id);
            $this->flash('O plano antigo venceu ou foi encerrado depois do pedido de troca (o crédito mudou). A troca foi cancelada.');
            $this->redirect('/master/comercial/assinaturas/' . $id);
        }
        $ativou = $this->ass->registrarPagamento($prox, $ass, (int) $_SESSION['usuario']['id'], 'manual');
        if ($ativou && $ass['cupom_id']) $this->plano->usarCupom((int) $ass['cupom_id']);
        if ($ativou) (new IndicacaoService())->aoAtivar($this->ass->porId($id)); // crédito para quem indicou
        $this->audit->registrar('pagamento_manual', 'pagamentos', (int) $prox['id'], null, [
            'assinatura' => $id, 'parcela' => (int) $prox['numero'], 'valor' => (float) $prox['valor'],
        ]);
        $this->flash("Parcela {$prox['numero']} marcada como paga (baixa manual)." . ($ativou ? ' O plano foi ativado.' : ''), 'sucesso');
        $this->redirect('/master/comercial/assinaturas/' . $id);
    }

    public function assinaturaCancelar(int $id)
    {
        CsrfMiddleware::validate();
        $ass = $this->carregarAssinatura($id);
        if (in_array($ass['status'], ['cancelada', 'trocada'], true)) {
            $this->flash('Esta assinatura já está encerrada.');
            $this->redirect('/master/comercial/assinaturas/' . $id);
        }
        $this->ass->cancelar($id);
        $this->audit->registrar('assinatura_cancelada_master', 'assinaturas', $id, ['status' => $ass['status']], ['status' => 'cancelada']);
        $this->flash('Assinatura cancelada. Parcelas pendentes foram canceladas.', 'sucesso');
        $this->redirect('/master/comercial/assinaturas/' . $id);
    }

    /** Soma ou tira meses do fim do plano (cortesia, mês extra, correção). */
    public function assinaturaPrazo(int $id)
    {
        CsrfMiddleware::validate();
        $ass   = $this->carregarAssinatura($id);
        $meses = (int) ($_POST['meses'] ?? 0);
        if ($ass['status'] !== 'ativa' || !$ass['fim']) {
            $this->flash('Só dá para mudar o prazo de um plano ativo com vencimento.');
        } elseif ($meses === 0 || abs($meses) > 36) {
            $this->flash('Informe de -36 a 36 meses (diferente de zero).');
        } else {
            $this->ass->prorrogar($id, $meses);
            $this->audit->registrar('assinatura_prazo', 'assinaturas', $id, ['fim' => $ass['fim']], ['meses' => $meses]);
            $this->flash(($meses > 0 ? "{$meses} mês(es) somado(s)" : abs($meses) . ' mês(es) retirado(s)') . ' ao prazo.', 'sucesso');
        }
        $this->redirect('/master/comercial/assinaturas/' . $id);
    }

    public function assinaturaLimite(int $id)
    {
        CsrfMiddleware::validate();
        $ass    = $this->carregarAssinatura($id);
        $limite = (int) ($_POST['projetos_limite'] ?? 0);
        if ($limite < 1 || $limite > 999) {
            $this->flash('O limite de projetos deve ficar entre 1 e 999.');
        } else {
            $this->ass->alterarLimiteProjetos($id, $limite);
            $this->audit->registrar('assinatura_limite', 'assinaturas', $id, ['projetos_limite' => (int) $ass['projetos_limite']], ['projetos_limite' => $limite]);
            $this->flash("Limite de projetos alterado para {$limite}.", 'sucesso');
        }
        $this->redirect('/master/comercial/assinaturas/' . $id);
    }

    /** O master troca o plano sem cobrar (inclusive para um mais barato): muda limite e recalcula o fim. */
    public function assinaturaPlano(int $id)
    {
        CsrfMiddleware::validate();
        $ass   = $this->carregarAssinatura($id);
        $plano = $this->plano->porId((int) ($_POST['plano_id'] ?? 0));
        if (in_array($ass['status'], ['cancelada', 'trocada'], true)) {
            $this->flash('Esta assinatura já está encerrada.');
        } elseif (!$plano || $plano['tipo'] !== $ass['plano_tipo']) {
            $this->flash('Escolha um plano do mesmo tipo.');
        } elseif ((int) $plano['id'] === (int) $ass['plano_id']) {
            $this->flash('A assinatura já está nesse plano.', 'aviso');
        } else {
            $this->ass->trocarPlanoSemCobranca($ass, $plano);
            $this->audit->registrar('assinatura_plano_master', 'assinaturas', $id, ['plano_id' => (int) $ass['plano_id']], ['plano_id' => (int) $plano['id']]);
            $this->flash("Plano trocado para {$plano['nome']} sem cobrança. Limite e prazo recalculados.", 'sucesso');
        }
        $this->redirect('/master/comercial/assinaturas/' . $id);
    }

    /** Muda valor (desconto ou acerto) e vencimento de uma parcela ainda não paga. */
    public function parcelaEditar(int $pagamentoId)
    {
        CsrfMiddleware::validate();
        $pag = $this->ass->pagamento($pagamentoId);
        if (!$pag) { $this->flash('Parcela não encontrada.'); $this->redirect('/master/comercial'); }
        $volta = '/master/comercial/assinaturas/' . (int) $pag['assinatura_id'];

        $valor = round((float) str_replace(',', '.', $_POST['valor'] ?? '0'), 2);
        $venc  = $_POST['vencimento'] ?? '';
        if ($pag['status'] !== 'pendente')                      $this->flash('Só parcelas a pagar podem ser alteradas.');
        elseif ($valor <= 0)                                    $this->flash('O valor precisa ser maior que zero.');
        elseif (!DateTime::createFromFormat('Y-m-d', $venc))    $this->flash('Vencimento inválido.');
        else {
            $this->ass->editarParcela($pag, $valor, $venc);
            $this->audit->registrar('parcela_alterada', 'pagamentos', $pagamentoId,
                ['valor' => (float) $pag['valor'], 'vencimento' => $pag['vencimento']], ['valor' => $valor, 'vencimento' => $venc]);
            $this->flash("Parcela {$pag['numero']} alterada. O total da assinatura foi refeito.", 'sucesso');
        }
        $this->redirect($volta);
    }

    public function alternarCupom(int $id)
    {
        CsrfMiddleware::validate();
        $this->plano->alternarCupom($id);
        $this->audit->registrar('cupom_alternado', 'cupons', $id, null, null);
        $this->flash('Cupom atualizado.', 'sucesso');
        $this->redirect('/master/comercial');
    }

    /**
     * Regras do cupom por plano vindas da tabela do formulário: regra[plano_id][on|tipo|valor].
     * Nenhum plano marcado = o cupom segue o "Vale para" + percentual geral. Retorna [regras, erros].
     */
    private function lerRegrasCupom(): array
    {
        $regras = []; $erros = [];
        foreach ((array) ($_POST['regra'] ?? []) as $pid => $r) {
            if (empty($r['on'])) continue;
            $plano = $this->plano->porId((int) $pid);
            if (!$plano) continue;
            $tipo  = ($r['tipo'] ?? 'pct') === 'valor' ? 'valor' : 'pct';
            $valor = round((float) str_replace(',', '.', $r['valor'] ?? '0'), 2);
            if ($tipo === 'pct' && ($valor <= 0 || $valor > 100))      $erros[] = "{$plano['nome']}: o desconto deve ficar entre 0,01% e 100%.";
            elseif ($tipo === 'valor' && ($valor <= 0 || $valor >= (float) $plano['preco'])) $erros[] = "{$plano['nome']}: o desconto em reais deve ser maior que zero e menor que o preço (R$ " . number_format((float) $plano['preco'], 2, ',', '.') . ').';
            else $regras[(int) $pid] = ['tipo_desconto' => $tipo, 'valor' => $valor];
        }
        return [$regras, $erros];
    }

    // ============ PACOTES (ESCOLAS) ============

    /** Cria o pacote e um código por turma, de uso único, que deixa o plano do pacote sem custo. */
    public function criarPacote()
    {
        CsrfMiddleware::validate();
        $nome  = trim($_POST['nome'] ?? '');
        $plano = $this->plano->porId((int) ($_POST['plano_id'] ?? 0));
        $qtd   = (int) ($_POST['quantidade'] ?? 0);
        $ate   = trim($_POST['valido_ate'] ?? '') ?: null;

        $erros = [];
        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) $erros[] = 'Dê um nome ao pacote (ex.: ETEC Morato).';
        if (!$plano)                                         $erros[] = 'Escolha o plano do pacote.';
        if ($qtd < 1 || $qtd > 300)                          $erros[] = 'Quantidade de turmas: de 1 a 300.';
        if ($ate && $ate < date('Y-m-d'))                    $erros[] = 'A validade já passou.';
        if ($erros) { $this->flash('Pacote: ' . implode(' ', $erros)); $this->redirect('/master/comercial#pacotes'); }

        $uid = (int) $_SESSION['usuario']['id'];
        $pacoteId = $this->plano->criarPacote(['nome' => $nome, 'plano_id' => (int) $plano['id'], 'quantidade' => $qtd, 'valido_ate' => $ate, 'criado_por' => $uid]);

        // Prefixo a partir do nome (ETEC Morato → ETECMORA) + 4 caracteres aleatórios por turma
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome) ?: $nome;
        $prefixo = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $semAcento)), 0, 8) ?: 'PACOTE';
        for ($i = 0; $i < $qtd; $i++) {
            do { $codigo = $prefixo . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4)); } while ($this->plano->codigoCupomExiste($codigo));
            $cid = $this->plano->criarCupom([
                'codigo' => $codigo, 'origem' => 'pacote', 'pacote_id' => $pacoteId, 'tipo_plano' => 'turma', 'percentual' => null,
                'valido_ate' => $ate, 'usos_max' => 1, 'criado_por' => $uid,
            ]);
            $this->plano->salvarRegras($cid, [(int) $plano['id'] => ['tipo_desconto' => 'pct', 'valor' => 100]]);
        }
        $this->audit->registrar('pacote_criado', 'pacotes', $pacoteId, null, ['nome' => $nome, 'plano' => $plano['codigo'], 'quantidade' => $qtd]);
        $this->flash("Pacote {$nome} criado com {$qtd} código(s) do plano {$plano['nome']}. Envie um código para cada turma.", 'sucesso');
        $this->redirect('/master/comercial#pacotes');
    }

    public function alternarPacote(int $id)
    {
        CsrfMiddleware::validate();
        $this->plano->alternarPacote($id);
        $this->audit->registrar('pacote_alternado', 'pacotes', $id, null, null);
        $this->flash('Pacote atualizado. Códigos já usados continuam valendo para quem usou.', 'sucesso');
        $this->redirect('/master/comercial#pacotes');
    }

    /** Lê e valida os campos comuns de plano do POST. Retorna [dados, erros]. */
    private function lerPlano(?string $tabela): array
    {
        $num   = fn($k) => (float) str_replace(',', '.', $_POST[$k] ?? '0');
        $meses = trim($_POST['meses'] ?? '');
        $d = [
            'nome'         => trim($_POST['nome'] ?? ''),
            'meses'        => $meses === '' ? null : (int) $meses, // vazio = sem vencimento
            'preco'        => round($num('preco'), 2),
            'piso'         => round($num('piso'), 2),
            'max_parcelas' => (int) ($_POST['max_parcelas'] ?? 1),
            'projetos'     => (int) ($_POST['projetos'] ?? 0),
            'pessoas_max'  => (int) ($_POST['pessoas_max'] ?? 0),
            'ordem'        => (int) ($_POST['ordem'] ?? 0),
            'inclui_dia_a_dia' => !empty($_POST['inclui_dia_a_dia']) ? 1 : 0,
            'preco_dia_a_dia'  => trim((string) ($_POST['preco_dia_a_dia'] ?? '')) === '' ? null : round($num('preco_dia_a_dia'), 2),
            'ativo'        => !empty($_POST['ativo']) ? 1 : 0,
        ];
        $maxTabela = max(array_keys(PrecoService::ACRESCIMOS[$tabela ?? ''] ?? [1 => 0]));

        $erros = [];
        if (mb_strlen($d['nome']) < 2 || mb_strlen($d['nome']) > 60) $erros[] = 'O nome deve ter de 2 a 60 letras.';
        if ($d['meses'] !== null && ($d['meses'] < 1 || $d['meses'] > 120)) $erros[] = 'Duração: de 1 a 120 meses (vazio = sem vencimento).';
        if ($d['preco'] <= 0)                                   $erros[] = 'O preço precisa ser maior que zero.';
        if ($d['piso'] <= 0 || $d['piso'] > $d['preco'])        $erros[] = 'O piso deve ser maior que zero e no máximo o preço.';
        if ($d['max_parcelas'] < 1 || $d['max_parcelas'] > $maxTabela) $erros[] = "Parcelas: de 1 a {$maxTabela} neste plano.";
        if ($d['projetos'] < 1)                                 $erros[] = 'O plano precisa de pelo menos 1 projeto.';
        if ($d['pessoas_max'] < 1)                              $erros[] = 'O limite de pessoas precisa ser pelo menos 1.';
        if (!$d['inclui_dia_a_dia'] && $d['preco_dia_a_dia'] !== null && $d['preco_dia_a_dia'] <= 0) $erros[] = 'O adicional Dia a dia precisa de um preço maior que zero.';
        return [$d, $erros];
    }

    private function carregarAssinatura(int $id): array
    {
        $ass = $this->ass->porId($id);
        if (!$ass) { $this->flash('Assinatura não encontrada.'); $this->redirect('/master/comercial'); }
        return $ass;
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
