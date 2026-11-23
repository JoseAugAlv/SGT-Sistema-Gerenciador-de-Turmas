<?php
// app/Controllers/PlanoController.php
// Plano da turma: escolher plano, gerar o Pix (de teste) e pagar as parcelas.
// Modo de teste: nenhum gateway é chamado; o pagamento é simulado por um botão com confirmação.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Plano.php';
require_once __DIR__ . '/../Models/Assinatura.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/Curso.php';
require_once __DIR__ . '/../Models/Periodo.php';
require_once __DIR__ . '/../Services/PrecoService.php';
require_once __DIR__ . '/../Services/CobrancaService.php';
require_once __DIR__ . '/../Models/DadosCobranca.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Services/RenovacaoService.php';
require_once __DIR__ . '/../Services/MercadoPagoService.php';
require_once __DIR__ . '/../Services/IndicacaoService.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class PlanoController
{
    private Turma        $turma;
    private TurmaUsuario $tu;
    private Plano        $plano;
    private Assinatura   $ass;
    private Auditoria    $audit;

    public function __construct()
    {
        $this->turma = new Turma();
        $this->tu    = new TurmaUsuario();
        $this->plano = new Plano();
        $this->ass   = new Assinatura();
        $this->audit = new Auditoria();
    }

    // ============ PLANO DA TURMA ============

    public function turma(int $turmaId)
    {
        $turma = $this->exigirRepOuMaster($turmaId);
        PlanoGuard::situacao($turma);              // gera o cupom de renovação antes de ler o plano, se for a hora
        $atual = $this->ass->atualDaTurma($turmaId);
        $renov = $this->dadosRenovacao($turma, $atual);

        // na janela de renovação, o cupom automático já vem aplicado
        $codigo  = $_GET['cupom'] ?? ($renov['cupom']['codigo'] ?? '');
        $escolha = $this->dadosEscolha($codigo, $turmaId, $turma['tipo'] ?? 'turma');
        $troca   = $this->ass->trocaPendente($turmaId);
        $ind     = new IndicacaoService();

        $this->render('planos/turma', $escolha + $this->dadosTroca($atual) + $this->dadosCobrancaView() + [
            'turma'        => $turma,
            'atual'        => $atual,
            'parcelas'     => $atual ? $this->ass->parcelas((int) $atual['id']) : [],
            'proxima'      => $atual ? $this->ass->proximaParcela((int) $atual['id']) : null,
            'troca'        => $troca,
            'trocaProxima' => $troca ? $this->ass->proximaParcela((int) $troca['id']) : null,
            'projetosUsados' => $this->ass->projetosUsados($turmaId),
            'alunos'       => $this->contarAlunos($turmaId),
            // Indicação: código da turma (só com plano ativo) e créditos ganhos
            'codigoIndicacao' => $atual && $atual['status'] === 'ativa' ? $ind->codigoDaTurma($turmaId) : null,
            'creditos'        => $ind->historico($turmaId),
            'pctIndicada'     => (float) PrecoService::config('indicacao_pct', '10'),
            'pctRecompensa'   => (float) PrecoService::config('indicacao_recompensa_pct', '10'),
            // Dia a dia: o plano inclui? Se não, preço do adicional e pedido pendente
            'diaPreco'        => $atual ? PrecoService::precoDiaADia($this->plano->porId((int) $atual['plano_id'])) : 0,
            'diaPendente'     => $atual ? $this->ass->adicionalPendente((int) $atual['id']) : null,
            'precoAnual'      => (float) ($this->plano->listar($turma['tipo'] === 'gremio' ? 'gremio' : 'turma')[2]['preco'] ?? 0),
            'renov'           => $renov,
            // veio de "escolher plano" (preços / nova turma): já marca o plano escolhido
            'planoInicial'    => preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($_GET['plano'] ?? ''))) ?: 'anual',
        ]);
    }

    // ============ RENOVAR (PRÓXIMO PERÍODO, ANTES OU LOGO DEPOIS DO FIM) ============

    /**
     * Situação da renovação: ['pode' => bool, 'pendente' => assinatura|null, 'proxima' => parcela|null,
     * 'cupom' => cupom automático|null, 'inicio' => quando o novo período começa (texto)].
     */
    private function dadosRenovacao(array $turma, ?array $atual): array
    {
        $vazio = ['pode' => false, 'pendente' => null, 'proxima' => null, 'cupom' => null, 'inicio' => ''];
        if (!$atual || $atual['status'] !== 'ativa' || !$atual['fim']) return $vazio;
        $pend = $this->ass->renovacaoPendente((int) $turma['id']);
        $aviso = (int) PrecoService::config('aviso_vencimento_dias', '7');
        $faltam = (int) floor((strtotime(date('Y-m-d', strtotime($atual['fim']))) - strtotime('today')) / 86400);
        $cupom = null;
        if (!empty($atual['cupom_renovacao_id'])) {
            $c = RenovacaoService::cupom((int) $atual['cupom_renovacao_id']);
            if ($c && (int) $c['usos'] < (int) $c['usos_max'] && (!$c['valido_ate'] || $c['valido_ate'] >= date('Y-m-d'))) $cupom = $c;
        }
        return [
            'pode'     => !$pend && $faltam <= $aviso,
            'pendente' => $pend,
            'proxima'  => $pend ? $this->ass->proximaParcela((int) $pend['id']) : null,
            'cupom'    => $cupom,
            'inicio'   => strtotime($atual['fim']) > time() ? date('d/m/Y', strtotime($atual['fim'])) : 'o dia do pagamento',
        ];
    }

    public function renovar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma  = $this->exigirRepOuMaster($turmaId);
        $voltar = '/turmas/' . $turmaId . '/plano#renovar';
        $atual  = $this->ass->atualDaTurma($turmaId);
        $renov  = $this->dadosRenovacao($turma, $atual);
        if (!$renov['pode']) {
            $this->flash($renov['pendente'] ? 'A renovação já foi pedida: pague a 1ª parcela ou cancele o pedido.' : 'A renovação abre perto do fim do plano.', 'aviso');
            $this->redirect($voltar);
        }

        $plano = $this->plano->porId((int) ($_POST['plano_id'] ?? 0));
        if (!$plano || !$plano['ativo'] || $plano['tipo'] !== ($turma['tipo'] ?? 'turma')) { $this->flash('Escolha um dos planos da lista.'); $this->redirect($voltar); }
        $parcelas = (int) ($_POST['parcelas'] ?? 1);
        if (!isset(PrecoService::opcoesParcelas($plano)[$parcelas])) { $this->flash("O plano {$plano['nome']} não aceita {$parcelas} parcela(s)."); $this->redirect($voltar); }

        [$cupom, $erro] = $this->plano->validarCupom($_POST['cupom'] ?? '', $plano, $turmaId);
        if ($erro) { $this->flash($erro); $this->redirect($voltar); }
        $credito = (new IndicacaoService())->creditoComoCupom($turmaId);
        $comDia  = !empty($_POST['dia_a_dia']) && PrecoService::precoDiaADia($plano) > 0;
        [$preco, $usado] = self::melhorPreco($plano, $parcelas, [$cupom, $credito, $this->descontoCruzado($turma['tipo'] ?? 'turma')], $comDia);
        if ($preco['total'] > 0) $this->exigirCobranca($voltar);

        $id = $this->ass->criar([
            'turma_id'        => $turmaId,
            'plano_id'        => (int) $plano['id'],
            'projetos_limite' => (int) $plano['projetos'],
            'cupom_id'        => $usado && !in_array($usado['origem'], ['credito', 'cruzado'], true) ? (int) $usado['id'] : null,
            'dia_a_dia'       => $comDia ? 1 : 0,
            'renovacao_de'    => (int) $atual['id'],
            'comprado_por'    => (int) $_SESSION['usuario']['id'],
        ], $preco);
        if ($usado && $usado['origem'] === 'credito') (new IndicacaoService())->marcarUsado((int) $usado['credito_id'], $id);
        $this->audit->registrar('assinatura_renovacao', 'assinaturas', $id, null, [
            'turma' => $turmaId, 'de' => (int) $atual['id'], 'plano' => $plano['codigo'], 'total' => $preco['total'], 'cupom' => $usado['codigo'] ?? null,
        ]);
        if ($preco['total'] <= 0) {
            $this->ativarSemCusto($id, "Plano {$plano['nome']} renovado, sem custo.");
        }
        $primeira = $this->ass->proximaParcela($id);
        $this->redirect('/pagamentos/' . (int) $primeira['id']);
    }

    // ============ TROCAR DE PLANO (SÓ PARA UM MAIS CARO) ============

    public function trocar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $this->exigirRepOuMaster($turmaId);
        $voltar = '/turmas/' . $turmaId . '/plano';

        $atual = $this->ass->atualDaTurma($turmaId);
        $dados = $this->dadosTroca($atual);
        if ($dados['trocaBloqueio']) { $this->flash($dados['trocaBloqueio']); $this->redirect($voltar); }

        $novo = $this->plano->porId((int) ($_POST['plano_id'] ?? 0));
        if (!$novo || !isset($dados['trocaPrecos'][(int) $novo['id']])) {
            $this->flash('Escolha um plano mais caro que o atual. Não é possível trocar para um plano mais barato.');
            $this->redirect($voltar);
        }
        $parcelas = (int) ($_POST['parcelas'] ?? 1);
        if (!isset($dados['trocaPrecos'][(int) $novo['id']][$parcelas])) {
            $this->flash("O plano {$novo['nome']} não aceita {$parcelas} parcela(s).");
            $this->redirect($voltar);
        }
        $this->exigirCobranca($voltar . '#trocar');

        $preco = $dados['trocaPrecos'][(int) $novo['id']][$parcelas];
        $id = $this->ass->criar([
            'turma_id' => $turmaId, 'plano_id' => (int) $novo['id'], 'projetos_limite' => (int) $novo['projetos'],
            'cupom_id' => null, 'troca_de' => (int) $atual['id'], 'comprado_por' => (int) $_SESSION['usuario']['id'],
            'valor_base' => (float) $novo['preco'], // crédito + diferença = preço cheio do plano novo
        ], $preco);

        $this->audit->registrar('troca_plano_pedida', 'assinaturas', $id, ['plano' => $atual['plano_codigo']], [
            'plano' => $novo['codigo'], 'credito' => $preco['credito'], 'diferenca' => $preco['diferenca'],
            'parcelas' => $parcelas, 'total' => $preco['total'],
        ]);

        $primeira = $this->ass->proximaParcela($id);
        $this->flash("Troca para o plano {$novo['nome']} pedida. O plano atual continua valendo até a 1ª parcela da diferença ser paga.", 'sucesso');
        $this->redirect('/pagamentos/' . (int) $primeira['id']);
    }

    // ============ PREÇOS (PÚBLICO) ============

    public function precos()
    {
        $planos = $this->plano->listar('turma');
        $precos = [];
        foreach ($planos as $p) {
            foreach (array_keys(PrecoService::opcoesParcelas($p)) as $n) $precos[(int) $p['id']][$n] = PrecoService::calcular($p, $n);
        }
        $gremios = $this->plano->listar('gremio');
        $precosGremio = [];
        foreach ($gremios as $p) {
            foreach (array_keys(PrecoService::opcoesParcelas($p)) as $n) $precosGremio[(int) $p['id']][$n] = PrecoService::calcular($p, $n);
        }
        $tituloPagina = 'Planos e preços — ' . App::getName();
        $this->render('planos/precos', [
            'planos'       => $planos,
            'precos'       => $precos,
            'gremios'      => $gremios,
            'precosGremio' => $precosGremio,
            'tituloPagina' => $tituloPagina,
        ]);
    }

    /** "Escolher" na página de preços: guarda o plano e leva a criar a turma (ou ao cadastro, se não estiver logado). */
    public function escolher(string $codigo)
    {
        $_SESSION['plano_desejado'] = preg_replace('/[^a-z0-9_]/', '', strtolower($codigo));
        $destino = str_starts_with($_SESSION['plano_desejado'], 'g_') ? '/gremios/novo' : '/turmas/nova';
        $this->redirect(empty($_SESSION['usuario']) ? '/login/cadastrar' : $destino);
    }

    // ============ CRIAR MINHA TURMA (COMPRA SEM TER TURMA) ============

    public function novaForm(string $tipo = 'turma')
    {
        $u = $_SESSION['usuario'];
        if ($u['tipo'] === 'master') { $this->redirect('/turmas/criar'); }

        $escolha = $this->dadosEscolha($_GET['cupom'] ?? '', null, $tipo);
        $plano   = preg_replace('/[^a-z0-9_]/', '', $_GET['plano'] ?? ($_SESSION['plano_desejado'] ?? ($tipo === 'gremio' ? 'g_anual' : 'anual')));

        $this->render('planos/nova', $escolha + $this->dadosCobrancaView() + [
            'tipo'         => $tipo,
            'turmasSemPlano' => $this->turmasSemPlano((int) $u['id'], $tipo),
            'cursos'       => (new Curso())->listarAtivos(),
            'periodos'     => (new Periodo())->listarAtivos(),
            'anosModulo'   => self::ANOS_MODULO,
            'planoInicial' => $plano ?: 'anual',
            'antigo'       => $_SESSION['nova_turma_form'] ?? [],
        ]);
        unset($_SESSION['nova_turma_form']);
    }

    /**
     * Turmas (do tipo pedido) em que a pessoa é representante principal e que precisam de plano:
     * sem plano, plano vencido ou pedido aguardando pagamento. Assim ela contrata para a turma que já tem.
     */
    private function turmasSemPlano(int $uid, string $tipo): array
    {
        $st = Database::getConnection()->prepare("
            SELECT t.* FROM turma_usuarios tu INNER JOIN turmas t ON t.id = tu.turma_id
            WHERE tu.usuario_id = ? AND tu.papel = 'representante' AND tu.ativo = 1 AND t.tipo = ? AND t.isenta_plano = 0
            ORDER BY t.nome
        ");
        $st->execute([$uid, $tipo === 'gremio' ? 'gremio' : 'turma']);
        $tu = new TurmaUsuario();
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $t) {
            if (!$tu->ehRepresentantePrincipal((int) $t['id'], $uid)) continue;
            $sit = PlanoGuard::situacao($t);
            if (in_array($sit['codigo'], ['sem_plano', 'vencido', 'aguardando'], true)) $out[] = $t + ['situacao' => $sit];
        }
        return $out;
    }

    // ============ CRIAR GRÊMIO (MESMO FLUXO, COM PLANOS DE GRÊMIO) ============

    public function novoGremioForm()
    {
        $this->novaForm('gremio');
    }

    public function novoGremioSalvar()
    {
        $_POST['tipo'] = 'gremio';
        $this->novaSalvar();
    }

    public function novaSalvar()
    {
        CsrfMiddleware::validate();
        $u = $_SESSION['usuario'];
        if ($u['tipo'] === 'master') { $this->redirect('/turmas/criar'); }

        // Grêmio: nome livre e planos de grêmio; quem cria vira o presidente
        $tipo     = ($_POST['tipo'] ?? 'turma') === 'gremio' ? 'gremio' : 'turma';
        $ehGremio = $tipo === 'gremio';
        $urlForm  = $ehGremio ? '/gremios/novo' : '/turmas/nova';
        $escola  = trim($_POST['codigo_escola'] ?? '');
        $ano     = trim($_POST['ano_modulo'] ?? '');
        $curso   = (new Curso())->porId((int) ($_POST['curso_id'] ?? 0));
        $periodo = (new Periodo())->porId((int) ($_POST['periodo_id'] ?? 0));
        $plano   = $this->plano->porId((int) ($_POST['plano_id'] ?? 0));
        $parcelas = (int) ($_POST['parcelas'] ?? 1);

        $erros = [];
        if ($ehGremio) {
            $nomeGremio = preg_replace('/\s+/', ' ', trim($_POST['nome_gremio'] ?? ''));
            if (mb_strlen($nomeGremio) < 5 || mb_strlen($nomeGremio) > 150) $erros[] = 'Dê o nome do grêmio com a escola (ex.: Grêmio Estudantil ETEC Morato).';
        } else {
            if (!ctype_digit($escola))                              $erros[] = 'O código da escola deve ter só números.';
            if (!array_key_exists($ano, self::ANOS_MODULO))         $erros[] = 'Escolha o ano/módulo.';
            if (!$curso || !$curso['ativo'])                        $erros[] = 'Escolha o curso.';
            if (!$periodo || !$periodo['ativo'])                    $erros[] = 'Escolha o período.';
        }
        if (!$plano || !$plano['ativo'] || $plano['tipo'] !== $tipo) $erros[] = 'Escolha um dos planos.';
        elseif (!isset(PrecoService::opcoesParcelas($plano)[$parcelas])) $erros[] = "O plano {$plano['nome']} não aceita {$parcelas} parcela(s).";
        $cupom = null;
        if ($plano) {
            [$cupom, $erroCupom] = $this->plano->validarCupom($_POST['cupom'] ?? '', $plano);
            if ($erroCupom) $erros[] = $erroCupom;
        }
        // Melhor desconto: cupom digitado ou desconto cruzado (já tem plano de turma/grêmio). Grátis não pede CPF.
        $comDia = $plano && !empty($_POST['dia_a_dia']) && PrecoService::precoDiaADia($plano) > 0;
        [$precoEscolhido, $usado] = $plano && !$erros ? self::melhorPreco($plano, $parcelas, [$cupom, $this->descontoCruzado($tipo)], $comDia) : [null, null];
        $gratis = $precoEscolhido && $precoEscolhido['total'] <= 0;
        [$cobranca, $errosCobranca] = CobrancaService::lerDoPost($_POST);
        if (!$gratis) $erros = array_merge($erros, $errosCobranca);
        $nome = $ehGremio ? ($nomeGremio ?? '') : (($curso && $periodo) ? Turma::montarNome($escola, $ano, $curso['sigla'], $periodo['sigla']) : '');
        if ($nome && $this->turmaComNomeExiste($nome)) {
            $erros[] = $ehGremio ? "Já existe o grêmio {$nome}. Se ele é o seu, peça o código de acesso ao presidente."
                                 : "Já existe a turma {$nome}. Se ela é a sua, peça o código de acesso ao representante e entre por “Entrar com código”.";
        }
        if ($erros) {
            $_SESSION['nova_turma_form'] = $_POST;
            $_SESSION['cobranca_form']   = $cobranca;
            $this->flash(implode(' ', $erros));
            $this->redirect($urlForm . ($plano ? '?plano=' . $plano['codigo'] : ''));
        }
        if (!$gratis || !$errosCobranca) (new DadosCobranca())->salvar((int) $u['id'], $cobranca);

        // Turma criada já com quem comprou como representante; fica bloqueada até a 1ª parcela ser paga
        do { $codigo = strtoupper(bin2hex(random_bytes(4))); } while ($this->turma->codigoJaExiste($codigo));
        $turmaId = $this->turma->criar([
            'nome' => $nome, 'codigo_acesso' => $codigo, 'codigo_interno' => $nome,
        ]);
        $this->tu->adicionar($turmaId, (int) $u['id'], 'representante');
        if ($ehGremio) Database::getConnection()->prepare("UPDATE turmas SET tipo = 'gremio' WHERE id = ?")->execute([$turmaId]);
        $papel = $ehGremio ? (mb_stripos($nome, 'grêmio') === 0 ? $nome : "Grêmio {$nome}") . " criado com você como presidente." : "Turma {$nome} criada com você como representante.";

        $preco = $precoEscolhido;
        $assId = $this->ass->criar([
            'turma_id' => $turmaId, 'plano_id' => (int) $plano['id'], 'projetos_limite' => (int) $plano['projetos'],
            'cupom_id' => $usado && !in_array($usado['origem'], ['credito', 'cruzado'], true) ? (int) $usado['id'] : null, 'comprado_por' => (int) $u['id'],
            'dia_a_dia' => $comDia ? 1 : 0,
        ], $preco);
        if ($preco['total'] <= 0) {
            $this->ativarSemCusto($assId, "{$papel} O plano {$plano['nome']} já está ativo (cupom {$usado['codigo']}).");
        }

        $this->audit->registrar('turma_criada_por_compra', 'turmas', $turmaId, null, [
            'nome' => $nome, 'plano' => $plano['codigo'], 'parcelas' => $parcelas, 'total' => $preco['total'],
        ]);
        unset($_SESSION['plano_desejado']);

        $primeira = $this->ass->proximaParcela($assId);
        $this->flash("{$papel} Pague a 1ª parcela para liberar o acesso.", 'sucesso');
        $this->redirect('/pagamentos/' . (int) $primeira['id']);
    }

    public function assinar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirRepOuMaster($turmaId);
        $voltar = '/turmas/' . $turmaId . '/plano';

        if ($this->ass->atualDaTurma($turmaId)) {
            $this->flash('A turma já tem um plano em andamento. Pague ou cancele o atual antes de escolher outro.');
            $this->redirect($voltar);
        }

        $plano = $this->plano->porId((int) ($_POST['plano_id'] ?? 0));
        if (!$plano || !$plano['ativo'] || $plano['tipo'] !== ($turma['tipo'] ?? 'turma')) {
            $this->flash('Escolha um dos planos da lista.');
            $this->redirect($voltar);
        }

        $parcelas = (int) ($_POST['parcelas'] ?? 1);
        if (!isset(PrecoService::opcoesParcelas($plano)[$parcelas])) {
            $this->flash("O plano {$plano['nome']} não aceita {$parcelas} parcela(s).");
            $this->redirect($voltar);
        }

        [$cupom, $erro] = $this->plano->validarCupom($_POST['cupom'] ?? '', $plano, $turmaId);
        if ($erro) { $this->flash($erro); $this->redirect($voltar); }

        // Vale o melhor desconto: o cupom digitado ou o crédito de indicação da turma (não somam)
        $credito = (new IndicacaoService())->creditoComoCupom($turmaId);
        $comDia = !empty($_POST['dia_a_dia']) && PrecoService::precoDiaADia($plano) > 0;
        [$preco, $usado] = self::melhorPreco($plano, $parcelas, [$cupom, $credito, $this->descontoCruzado($turma['tipo'] ?? 'turma')], $comDia);
        if ($preco['total'] > 0) $this->exigirCobranca($voltar); // plano gratuito (pacote) não pede CPF

        $id = $this->ass->criar([
            'turma_id'        => $turmaId,
            'plano_id'        => (int) $plano['id'],
            'projetos_limite' => (int) $plano['projetos'],
            'cupom_id'        => $usado && !in_array($usado['origem'], ['credito', 'cruzado'], true) ? (int) $usado['id'] : null,
            'dia_a_dia'       => $comDia ? 1 : 0,
            'comprado_por'    => (int) $_SESSION['usuario']['id'],
        ], $preco);
        if ($usado && $usado['origem'] === 'credito') (new IndicacaoService())->marcarUsado((int) $usado['credito_id'], $id);
        if ($preco['total'] <= 0) {
            $this->ativarSemCusto($id, "O plano {$plano['nome']} está ativo, sem custo (cupom {$usado['codigo']}).");
        }

        $this->audit->registrar('assinatura_criada', 'assinaturas', $id, null, [
            'turma' => $turmaId, 'plano' => $plano['codigo'], 'parcelas' => $parcelas, 'total' => $preco['total'],
            'cupom' => $cupom['codigo'] ?? null,
        ]);

        $primeira = $this->ass->proximaParcela($id);
        $this->redirect('/pagamentos/' . (int) $primeira['id']);
    }

    // ============ MEUS PLANOS (TODAS AS TURMAS DO USUÁRIO) ============

    public function meus()
    {
        $u = $_SESSION['usuario'];
        if ($u['tipo'] === 'master') $this->redirect('/master/comercial');

        $comoRep = []; $comoAluno = [];
        foreach ($this->turma->listarDoUsuario((int) $u['id']) as $t) {
            if (($t['meu_papel'] ?? '') === 'representante' && empty($t['sou_vice'])) { // o vice não cuida do plano
                $sit = PlanoGuard::situacao($t);
                $ass = $sit['assinatura'] ?? $this->ass->atualDaTurma((int) $t['id']);
                $comoRep[] = $t + [
                    'situacao'  => $sit,
                    'atual'     => $ass,
                    'proxima'   => $ass ? $this->ass->proximaParcela((int) $ass['id']) : null,
                    'troca'     => $this->ass->trocaPendente((int) $t['id']),
                    'projetos_usados' => $this->ass->projetosUsados((int) $t['id']),
                ];
            } else {
                $comoAluno[] = $t + ['situacao' => PlanoGuard::situacao($t)];
            }
        }

        $this->render('planos/meus', $this->dadosCobrancaView() + [
            'comoRep'   => $comoRep,
            'comoAluno' => $comoAluno,
        ]);
    }

    /** Salva os dados de cobrança fora de uma compra (tela Meus planos). */
    public function salvarCobranca()
    {
        CsrfMiddleware::validate();
        [$d, $erros] = CobrancaService::lerDoPost($_POST);
        if ($erros) {
            $_SESSION['cobranca_form'] = $d;
            $this->flash(implode(' ', $erros));
            $this->redirect('/planos#cobranca');
        }
        (new DadosCobranca())->salvar((int) $_SESSION['usuario']['id'], $d);
        $this->flash('Dados de cobrança salvos.', 'sucesso');
        $this->redirect('/planos#cobranca');
    }

    /** Consulta de CEP para o preenchimento automático do endereço (JSON). */
    public function cep(string $cep)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(CobrancaService::consultarCep($cep), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============ ADICIONAL DIA A DIA (PLANOS QUE NÃO INCLUEM) ============

    /** Compra o Dia a dia para o plano ativo: gera um pagamento à parte (não conta como parcela atrasada). */
    public function comprarDiaADia(int $turmaId)
    {
        CsrfMiddleware::validate();
        $this->exigirRepOuMaster($turmaId);
        $voltar = '/turmas/' . $turmaId . '/plano#dia-a-dia';
        $atual = $this->ass->atualDaTurma($turmaId);
        if (!$atual || $atual['status'] !== 'ativa') { $this->flash('A turma precisa de um plano ativo.'); $this->redirect($voltar); }
        $plano = $this->plano->porId((int) $atual['plano_id']);
        $valor = PrecoService::precoDiaADia($plano);
        if ($valor <= 0 || !empty($atual['dia_a_dia'])) { $this->flash('O plano já inclui o Dia a dia.', 'aviso'); $this->redirect($voltar); }
        $pend = $this->ass->adicionalPendente((int) $atual['id']);
        $pagId = $pend ? (int) $pend['id'] : $this->ass->criarAdicional((int) $atual['id'], $valor);
        $this->audit->registrar('dia_a_dia_pedido', 'assinaturas', (int) $atual['id'], null, ['valor' => $valor]);
        $this->redirect('/pagamentos/' . $pagId);
    }

    /** Começa o teste grátis do Dia a dia (uma vez por turma; dias em config teste_dia_dias). */
    public function testarDiaADia(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirRepOuMaster($turmaId);
        $teste = PlanoGuard::testeDia($turma);
        if (!$teste['pode']) {
            $this->flash($teste['usado'] ? 'Esta turma já usou o teste grátis do Dia a dia.' : 'O teste grátis não está disponível para esta turma.', 'aviso');
            $this->redirect('/turmas/' . $turmaId . '/plano#dia-a-dia');
        }
        $ate = date('Y-m-d H:i:s', strtotime('+' . $teste['dias'] . ' days'));
        Database::getConnection()->prepare("UPDATE turmas SET teste_dia_ate = ? WHERE id = ?")->execute([$ate, $turmaId]);
        $this->audit->registrar('dia_a_dia_teste', 'turmas', $turmaId, null, ['ate' => $ate]);
        $this->flash("Teste grátis do Dia a dia liberado até " . date('d/m/Y', strtotime($ate)) . ". Aproveite a agenda, o horário e o aviso do dia.", 'sucesso');
        $this->redirect('/turmas/' . $turmaId . '/calendario');
    }

    public function cancelarAdicional(int $pagamentoId)
    {
        CsrfMiddleware::validate();
        $pag = $this->ass->pagamento($pagamentoId);
        if (!$pag || $pag['tipo'] !== 'adicional' || $pag['status'] !== 'pendente') { $this->flash('Pedido não encontrado.'); $this->redirect('/turmas'); }
        $ass = $this->ass->porId((int) $pag['assinatura_id']);
        $this->exigirRepOuMaster((int) $ass['turma_id']);
        $this->ass->cancelarPagamento($pagamentoId);
        $this->flash('Pedido do Dia a dia cancelado.', 'sucesso');
        $this->redirect('/turmas/' . (int) $ass['turma_id'] . '/plano#dia-a-dia');
    }

    // ============ PIX (TESTE) ============

    public function pix(int $pagamentoId)
    {
        [$pag, $ass, $turma] = $this->carregarPagamento($pagamentoId);

        // Pix real (Mercado Pago): gera o QR e o copia e cola de verdade
        $real = false; $erroPix = null;
        if (MercadoPagoService::ativo() && $pag['status'] === 'pendente') {
            $real = true;
            $comprador = (int) ($ass['comprado_por'] ?: $_SESSION['usuario']['id']);
            $cob = (new DadosCobranca())->porUsuario($comprador) ?? (new DadosCobranca())->porUsuario((int) $_SESSION['usuario']['id']) ?? [];
            $st = Database::getConnection()->prepare("SELECT email, nome FROM usuarios WHERE id = ?");
            $st->execute([$comprador]);
            $u = $st->fetch(PDO::FETCH_ASSOC) ?: ['email' => $_SESSION['usuario']['email'], 'nome' => $_SESSION['usuario']['nome']];
            try {
                $pag = (new MercadoPagoService())->pixDaParcela($pag, $ass, $turma, ['email' => $u['email'], 'nome' => $cob['nome_completo'] ?? $u['nome'], 'cpf' => $cob['cpf'] ?? '']);
                $pag = $this->ass->pagamento($pagamentoId) ?: $pag;   // pode ter sido aprovado na hora
            } catch (\Throwable $e) {
                $erroPix = $e->getMessage();
            }
        }

        $this->render('planos/pix', [
            'pagamento'  => $pag,
            'assinatura' => $ass,
            'turma'      => $turma,
            'pixCopia'   => $real ? (string) ($pag['pix_copia'] ?? '') : $this->pixCopiaECola($pag),
            'pixReal'    => $real,
            'erroPix'    => $erroPix,
        ]);
    }

    /** Situação da parcela (JSON) para a tela do Pix: consulta o Mercado Pago se ainda estiver pendente. */
    public function status(int $pagamentoId)
    {
        [$pag] = $this->carregarPagamento($pagamentoId);
        if ($pag['status'] === 'pendente' && !empty($pag['mp_id']) && MercadoPagoService::ativo()) {
            (new MercadoPagoService())->sincronizar((string) $pag['mp_id']);
            $pag = $this->ass->pagamento($pagamentoId) ?: $pag;
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['pago' => $pag['status'] === 'pago']);
        exit;
    }

    public function simular(int $pagamentoId)
    {
        CsrfMiddleware::validate();
        [$pag, $ass, $turma] = $this->carregarPagamento($pagamentoId);
        $voltar = '/turmas/' . (int) $turma['id'] . '/plano';

        if (PrecoService::config('modo_pagamento', 'teste') !== 'teste') {
            $this->flash('O pagamento simulado está desligado: o sistema está em modo de cobrança real.');
            $this->redirect($voltar);
        }
        if ($pag['status'] !== 'pendente') {
            $this->flash('Esta parcela já foi paga.', 'aviso');
            $this->redirect($voltar);
        }
        // parcela dividida entre os alunos: paga pelo rateio (evita pagar em dobro)
        require_once __DIR__ . '/../Models/Rateio.php';
        if ($rat = (new Rateio())->abertoDoPagamento((int) $pag['id'])) {
            $this->flash('Esta parcela está dividida entre os alunos. Acompanhe ou pague o restante na divisão.', 'aviso');
            $this->redirect('/rateios/' . (int) $rat['id']);
        }
        if (!$this->ass->trocaAindaValida($ass)) {
            $this->ass->cancelar((int) $ass['id']);
            $this->flash('O plano atual venceu ou foi encerrado depois do pedido de troca, então o crédito mudou. A troca foi cancelada: peça de novo.');
            $this->redirect($voltar);
        }
        $proxima = $pag['tipo'] === 'adicional' ? $pag : $this->ass->proximaParcela((int) $ass['id']); // adicional não segue a ordem das parcelas
        if (!$proxima || (int) $proxima['id'] !== (int) $pag['id']) {
            $this->flash('Pague as parcelas na ordem: a ' . (int) $proxima['numero'] . 'ª vem antes.');
            $this->redirect($voltar);
        }

        $ativou = $this->ass->registrarPagamento($pag, $ass, (int) $_SESSION['usuario']['id']);
        $ass    = $this->ass->porId((int) $ass['id']) ?: $ass; // fim já recalculado
        if ($ativou) (new IndicacaoService())->aoAtivar($ass); // crédito para a turma que indicou
        if ($ativou && $ass['cupom_id']) $this->plano->usarCupom((int) $ass['cupom_id']);

        $this->audit->registrar('pagamento_simulado', 'pagamentos', (int) $pag['id'], null, [
            'assinatura' => (int) $ass['id'], 'parcela' => (int) $pag['numero'], 'valor' => (float) $pag['valor'],
        ]);

        $valor = 'R$ ' . number_format((float) $pag['valor'], 2, ',', '.');
        if ($ativou) {
            $fim = $ass['fim'] ? date('d/m/Y', strtotime($ass['fim'])) : null;
            $titulo = $ass['troca_de'] ? 'Plano trocado' : 'Plano ativado';
            $this->avisarRepresentantes((int) $turma['id'], $titulo,
                "O plano {$ass['plano_nome']} da turma {$turma['nome']} está ativo" . ($fim ? " até {$fim}." : ', sem vencimento.'));
            $this->flash("Pagamento de {$valor} confirmado (teste). "
                . ($ass['troca_de'] ? "A turma passou para o plano {$ass['plano_nome']}." : "O plano {$ass['plano_nome']} está ativo."), 'sucesso');
        } elseif ($pag['tipo'] === 'adicional') {
            $this->avisarRepresentantes((int) $turma['id'], 'Dia a dia liberado', "Agenda, horário, aviso do dia e contagem regressiva estão liberados para a turma {$turma['nome']}.");
            $this->flash("Pagamento de {$valor} confirmado (teste). O Dia a dia está liberado: agenda, horário, aviso do dia e contagem.", 'sucesso');
        } else {
            $this->flash("Parcela {$pag['numero']}/{$ass['parcelas']} de {$valor} confirmada (teste).", 'sucesso');
        }
        $this->redirect($voltar);
    }

    public function cancelar(int $assinaturaId)
    {
        CsrfMiddleware::validate();
        $ass = $this->ass->porId($assinaturaId);
        if (!$ass) { $this->flash('Assinatura não encontrada.'); $this->redirect('/turmas'); }
        $this->exigirRepOuMaster((int) $ass['turma_id']);

        if ($ass['status'] !== 'aguardando') {
            $this->flash('Só dá para cancelar um plano que ainda não teve pagamento. Para um plano ativo, fale com o suporte.');
            $this->redirect('/turmas/' . (int) $ass['turma_id'] . '/plano');
        }
        $this->ass->cancelar($assinaturaId);
        (new IndicacaoService())->devolverDoPedido($assinaturaId); // crédito de indicação volta a valer
        $this->audit->registrar('assinatura_cancelada', 'assinaturas', $assinaturaId, ['status' => 'aguardando'], ['status' => 'cancelada']);
        $this->flash($ass['troca_de'] ? 'Troca cancelada. O plano atual continua valendo.' : 'Pedido cancelado. Você pode escolher outro plano.', 'sucesso');
        $this->redirect('/turmas/' . (int) $ass['turma_id'] . '/plano');
    }

    // ============ HELPERS ============

    const ANOS_MODULO = ['1MOD' => '1º Módulo', '2MOD' => '2º Módulo', '3MOD' => '3º Módulo'];

    /**
     * Planos de turma com os preços de cada parcela, já com o melhor desconto: o cupom digitado (se valer
     * para o plano) ou o crédito de indicação da turma. Descontos não somam.
     */
    private function dadosEscolha(string $codigoCupom, ?int $turmaId = null, string $tipo = 'turma'): array
    {
        $codigoCupom = strtoupper(trim($codigoCupom));
        $planos  = $this->plano->listar($tipo === 'gremio' ? 'gremio' : 'turma');
        $credito = $turmaId ? (new IndicacaoService())->creditoComoCupom($turmaId) : null;
        $cruzado = $this->descontoCruzado($tipo);
        $precos = [];
        $cupom  = null;
        $erroCupom = null;
        foreach ($planos as $p) {
            [$c, $erro] = $this->plano->validarCupom($codigoCupom, $p, $turmaId);
            if ($c) $cupom = $c;
            if ($erro) $erroCupom = $erro;
            foreach (array_keys(PrecoService::opcoesParcelas($p)) as $n) {
                $precos[(int) $p['id']][$n] = self::melhorPreco($p, $n, [$c, $credito, $cruzado])[0];
                // Mesmo plano com o adicional Dia a dia (só nos planos que não incluem)
                if (PrecoService::precoDiaADia($p) > 0) $precosDia[(int) $p['id']][$n] = self::melhorPreco($p, $n, [$c, $credito, $cruzado], true)[0];
            }
        }
        return [
            'planos' => $planos, 'precos' => $precos, 'precosDia' => $precosDia ?? [], 'cupom' => $cupom, 'credito' => $credito, 'cruzado' => $cruzado,
            'codigoCupom' => $codigoCupom, 'erroCupom' => $cupom ? null : $erroCupom,
        ];
    }

    /** Preço com o desconto que sair mais barato (cupom, crédito de indicação ou desconto cruzado). Retorna [preço, desconto usado | null]. */
    private static function melhorPreco(array $plano, int $parcelas, array $candidatos, bool $comDiaADia = false): array
    {
        $melhor = [PrecoService::calcular($plano, $parcelas, null, $comDiaADia), null];
        foreach ($candidatos as $d) {
            if (!$d || !PrecoService::descontoDoCupom($d, $plano)) continue;
            $p = PrecoService::calcular($plano, $parcelas, $d, $comDiaADia);
            if ($p['avista'] < $melhor[0]['avista']) $melhor = [$p, $d];
        }
        return $melhor;
    }

    /**
     * Desconto cruzado: quem já é representante/presidente com plano ativo numa turma ganha desconto no plano
     * do grêmio, e vice-versa (config desconto_cruzado_pct). Retorna um "cupom virtual" ou null.
     */
    private function descontoCruzado(string $tipoAlvo): ?array
    {
        $pct = (float) PrecoService::config('desconto_cruzado_pct', '10');
        $u = $_SESSION['usuario'] ?? null;
        if ($pct <= 0 || !$u || $u['tipo'] === 'master') return null;
        $outro = $tipoAlvo === 'gremio' ? 'turma' : 'gremio';
        $st = Database::getConnection()->prepare("
            SELECT 1 FROM turma_usuarios tu
            INNER JOIN turmas t ON t.id = tu.turma_id
            INNER JOIN assinaturas a ON a.turma_id = t.id AND a.status = 'ativa' AND (a.fim IS NULL OR a.fim > NOW())
            WHERE tu.usuario_id = ? AND tu.papel = 'representante' AND tu.vice = 0 AND tu.ativo = 1 AND t.tipo = ? LIMIT 1
        ");
        $st->execute([(int) $u['id'], $outro]);
        if (!$st->fetchColumn()) return null;
        return ['id' => null, 'origem' => 'cruzado', 'percentual' => $pct, 'regras' => [],
                'codigo' => $outro === 'turma' ? 'desconto de quem tem plano de turma' : 'desconto de quem tem plano de grêmio'];
    }

    /** Plano gratuito (cupom de pacote): ativa na hora, sem Pix. */
    private function ativarSemCusto(int $assId, string $mensagem): void
    {
        $ass = $this->ass->porId($assId);
        $pag = $this->ass->proximaParcela($assId);
        $this->ass->registrarPagamento($pag, $ass, (int) $_SESSION['usuario']['id'], 'cortesia');
        if ($ass['cupom_id']) $this->plano->usarCupom((int) $ass['cupom_id']);
        $ass = $this->ass->porId($assId);
        (new IndicacaoService())->aoAtivar($ass);
        $this->audit->registrar('plano_ativado_sem_custo', 'assinaturas', $assId, null, ['cupom_id' => $ass['cupom_id']]);
        $this->avisarRepresentantes((int) $ass['turma_id'], 'Plano ativado', $mensagem);
        $this->flash($mensagem, 'sucesso');
        $this->redirect('/turmas/' . (int) $ass['turma_id'] . '/plano');
    }

    /** Dados de cobrança para o formulário: o que foi digitado (se deu erro) ou o que está salvo. */
    private function dadosCobrancaView(): array
    {
        $u = $_SESSION['usuario'];
        $cobranca = $_SESSION['cobranca_form'] ?? (new DadosCobranca())->porUsuario((int) $u['id']);
        unset($_SESSION['cobranca_form']);
        return ['pedirCobranca' => $u['tipo'] !== 'master', 'cobranca' => $cobranca];
    }

    /**
     * Exige CPF e endereço válidos de quem compra (o master, agindo pela turma, não precisa).
     * Válidos: salva para as próximas compras. Inválidos: volta com a mensagem e o que foi digitado.
     */
    private function exigirCobranca(string $voltar): void
    {
        $u = $_SESSION['usuario'];
        if ($u['tipo'] === 'master') return;
        [$d, $erros] = CobrancaService::lerDoPost($_POST);
        if ($erros) {
            $_SESSION['cobranca_form'] = $d;
            $this->flash('Dados de cobrança: ' . implode(' ', $erros));
            $this->redirect($voltar);
        }
        (new DadosCobranca())->salvar((int) $u['id'], $d);
    }

    /**
     * Planos para os quais a turma pode trocar (só os mais caros), com o preço da diferença por parcela.
     * trocaBloqueio: motivo de não poder trocar agora (ou null).
     */
    private function dadosTroca(?array $atual): array
    {
        $vazio = ['trocaPlanos' => [], 'trocaPrecos' => [], 'trocaBloqueio' => 'A turma precisa de um plano ativo para trocar.', 'trocaCredito' => 0.0];
        if (!$atual || $atual['status'] !== 'ativa') return $vazio;

        $planoAtual = $this->plano->porId((int) $atual['plano_id']);
        $credito = PrecoService::creditoRestante($atual);
        $planos = []; $precos = [];
        foreach ($this->plano->listar($planoAtual['tipo']) as $p) {
            if ((int) $p['id'] === (int) $planoAtual['id']) continue;
            if (!PrecoService::calcularTroca($planoAtual, $p, $credito)) continue; // mais barato ou igual: não pode
            $planos[] = $p;
            foreach (array_keys(PrecoService::opcoesParcelas($p)) as $n) {
                $precos[(int) $p['id']][$n] = PrecoService::calcularTroca($planoAtual, $p, $credito, $n);
            }
        }

        $bloqueio = null;
        if ($atual['fim'] && strtotime($atual['fim']) < time()) $bloqueio = 'O plano atual venceu. Renove antes de trocar.';
        elseif ($this->ass->proximaParcela((int) $atual['id']))  $bloqueio = 'Pague as parcelas do plano atual antes de trocar de plano.';
        elseif ($this->ass->trocaPendente((int) $atual['turma_id'])) $bloqueio = 'Já existe uma troca de plano aguardando pagamento. Pague ou cancele antes de pedir outra.';
        elseif (!$planos) $bloqueio = 'A turma já está no plano mais completo.';

        return ['trocaPlanos' => $planos, 'trocaPrecos' => $precos, 'trocaBloqueio' => $bloqueio, 'trocaCredito' => $credito];
    }

    private function turmaComNomeExiste(string $nome): bool
    {
        $stmt = Database::getConnection()->prepare("SELECT 1 FROM turmas WHERE nome = ? LIMIT 1");
        $stmt->execute([$nome]);
        return (bool) $stmt->fetchColumn();
    }

    private function carregarPagamento(int $pagamentoId): array
    {
        $pag = $this->ass->pagamento($pagamentoId);
        if (!$pag) { $this->flash('Pagamento não encontrado.'); $this->redirect('/turmas'); }
        $ass = $this->ass->porId((int) $pag['assinatura_id']);
        $turma = $this->exigirRepOuMaster((int) $ass['turma_id']);
        if ($ass['status'] === 'cancelada') {
            $this->flash('Este pedido foi cancelado.');
            $this->redirect('/turmas/' . (int) $turma['id'] . '/plano');
        }
        return [$pag, $ass, $turma];
    }

    /** Texto no formato do "Pix copia e cola", só para ilustrar (não é cobrável). */
    private function pixCopiaECola(array $pag): string
    {
        $valor = number_format((float) $pag['valor'], 2, '.', '');
        return '00020126580014BR.GOV.BCB.PIX0136TESTE-PRUMO-SEM-COBRANCA5204000053039865406'
             . $valor . '5802BR5905PRUMO6009SAO PAULO62' . sprintf('%02d', strlen($pag['txid']) + 4) . '05' . $pag['txid'] . '6304TEST';
    }

    private function contarAlunos(int $turmaId): int
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM turma_usuarios WHERE turma_id = ? AND ativo = 1");
        $stmt->execute([$turmaId]);
        return (int) $stmt->fetchColumn();
    }

    private function avisarRepresentantes(int $turmaId, string $titulo, string $msg): void
    {
        $stmt = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND ativo = 1");
        $stmt->execute([$turmaId]);
        (new Notificacao())->criarParaVarios($stmt->fetchAll(PDO::FETCH_COLUMN), 'alerta', $titulo, $msg, '/turmas/' . $turmaId . '/plano');
    }

    private function exigirRepOuMaster(int $turmaId): array
    {
        $turma = $this->turma->porId($turmaId);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->ehRepresentantePrincipal($turmaId, (int) $u['id'])) {
            http_response_code(403);
            exit('Só o representante principal da turma cuida do plano e dos pagamentos.');
        }
        return $turma;
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
