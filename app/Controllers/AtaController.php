<?php
// app/Controllers/AtaController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Ata.php';
require_once __DIR__ . '/../Models/AtividadeAta.php';
require_once __DIR__ . '/../Models/RelatorioAta.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Grupo.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Models/Notificacao.php';

class AtaController
{
    private Ata           $ata;
    private AtividadeAta  $ativ;
    private RelatorioAta  $rel;
    private Projeto       $projeto;
    private Grupo         $grupo;
    private GrupoAluno    $membro;
    private GrupoDiretor  $diretor;
    private TurmaUsuario  $tu;
    private Auditoria     $audit;

    public function __construct()
    {
        $this->ata     = new Ata();
        $this->ativ    = new AtividadeAta();
        $this->rel     = new RelatorioAta();
        $this->projeto = new Projeto();
        $this->grupo   = new Grupo();
        $this->membro  = new GrupoAluno();
        $this->diretor = new GrupoDiretor();
        $this->tu      = new TurmaUsuario();
        $this->audit   = new Auditoria();
    }

    // ============ LISTAGEM ============

public function index(int $projetoId)
{
    $projeto = $this->projeto->porId($projetoId);
    if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

    $u = $_SESSION['usuario'];
    $this->exigirAcesso($projeto);

    $souMaster = $u['tipo'] === 'master';
    $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

    if ($souMaster || $souRep) {
        $this->render('atas/index_rep', [
            'projeto' => $projeto,
            'modo'    => 'rep',
            'atas'    => [
                'todas'       => $this->ata->listarPorProjeto($projetoId),
                'preenchidas' => $this->ata->listarPreenchidasDoProjeto($projetoId),
            ],
        ]);
        return;
    }

    if ($this->temDiretorAtivoNoProjeto($projetoId, (int) $u['id'])) {
        $this->render('atas/index_diretor', [
            'projeto' => $projeto,
            'modo'    => 'diretor',
            'atas'    => [
                'todas' => $this->ata->listarDosGruposDoDiretor($projetoId, (int) $u['id']),
            ],
        ]);
        return;
    }

    $this->render('atas/index_aluno', [
        'projeto' => $projeto,
        'modo'    => 'aluno',
        'atas'    => [
            'minhas'   => $this->ata->listarDoParticipante($projetoId, (int) $u['id']),
            'abertas'  => $this->ata->listarAbertasParaMembro($projetoId, (int) $u['id']),
        ],
    ]);
}

    // ============ CRIAR (REP OU MASTER) ============

    public function criarForm(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditar($projeto);

        // Grupos do projeto com contagem de diretores ativos
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT g.id, g.nome,
                   (SELECT COUNT(*) FROM grupo_diretores gd WHERE gd.grupo_id = g.id AND gd.ativo = 1) AS total_diretores
            FROM grupos g
            WHERE g.projeto_id = ?
            ORDER BY g.nome ASC
        ");
        $stmt->execute([$projetoId]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('atas/criar', [
            'projeto' => $projeto,
            'grupos'  => $grupos,
        ]);
    }

        // ============ EDITAR (REP OU MASTER) ============

        public function editarForm(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        $this->exigirPodeEditar($projeto);

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/atas/' . $id);
        }

        if ($ata['status'] === 'revisada') {
            $this->flash('Atas revisadas não podem ser editadas.');
            $this->redirect('/atas/' . $id);
        }

        // Grupos do projeto com contagem de diretores ativos
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT g.id, g.nome,
                   (SELECT COUNT(*) FROM grupo_diretores gd WHERE gd.grupo_id = g.id AND gd.ativo = 1) AS total_diretores
            FROM grupos g
            WHERE g.projeto_id = ?
            ORDER BY g.nome ASC
        ");
        $stmt->execute([(int) $projeto['id']]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Aviso: se a ata já tem atividades/relatórios, trocar de grupo é uma mudança sensível
        $temConteudo = !empty($this->ativ->listarPorAta($id)) || !empty($this->rel->listarPorAta($id));

        $this->render('atas/editar', [
            'ata'         => $ata,
            'projeto'     => $projeto,
            'grupos'      => $grupos,
            'temConteudo' => $temConteudo,
        ]);
    }
    
        public function atualizar(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/atas/' . $id);
        }

        if ($ata['status'] === 'revisada') {
            $this->flash('Atas revisadas não podem ser editadas.');
            $this->redirect('/atas/' . $id);
        }

        $titulo         = trim($_POST['titulo'] ?? '');
        $descricao      = trim($_POST['descricao'] ?? '') ?: null;
        $dataAta        = trim($_POST['data_ata'] ?? '');
        $prazo          = trim($_POST['prazo_preenchimento'] ?? '') ?: null;
        $hi             = trim($_POST['horario_inicio'] ?? '') ?: null;
        $hf             = trim($_POST['horario_fim'] ?? '') ?: null;
        $gruposExtra    = array_map('intval', $_POST['grupos_extra'] ?? []);

        $erros = [];
        if (strlen($titulo) < 3)                                    $erros[] = 'Título muito curto.';
        if (!DateTime::createFromFormat('Y-m-d', $dataAta))         $erros[] = 'Data da ata inválida.';
        if ($prazo && !DateTime::createFromFormat('Y-m-d', $prazo)) $erros[] = 'Prazo inválido.';

        // Valida grupos extras
        foreach ($gruposExtra as $gid) {
            $g = $this->grupo->porId($gid);
            if (!$g || (int) $g['projeto_id'] !== (int) $projeto['id']) {
                $erros[] = 'Grupo inválido para replicação.';
                break;
            }
        }

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/atas/' . $id . '/editar');
        }

        // 1) Atualiza a ata original
        $antes = $ata;
        $this->ata->definirPreenchimento($id, ($_POST['preenchimento'] ?? $ata['preenchimento'] ?? 'diretor'));
        $this->ata->atualizarBasico($id, [
            'titulo'              => $titulo,
            'descricao'           => $descricao,
            'data_ata'            => $dataAta,
            'prazo_preenchimento' => $prazo,
            'horario_inicio'      => $hi,
            'horario_fim'         => $hf,
        ]);

        $this->audit->registrar('ata_atualizada', 'atas', $id, $antes, [
            'titulo' => $titulo, 'data_ata' => $dataAta,
        ]);

        // 2) Replica para os grupos extras marcados
        $replicadas    = 0;
        $semDiretor    = [];

        foreach ($gruposExtra as $gid) {
            if ($gid === (int) $ata['grupo_id']) continue; // não replica para o mesmo grupo

            // Se já existe uma ata com mesmo título nesse grupo, pula
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT 1 FROM atas
                WHERE grupo_id = ? AND titulo = ? AND data_ata = ?
                LIMIT 1
            ");
            $stmt->execute([$gid, $titulo, $dataAta]);
            if ($stmt->fetchColumn()) continue;

            // Precisa ter diretor ativo
            $diretoresAtivos = $this->diretor->listarAtivos($gid);
            if (empty($diretoresAtivos)) {
                $g = $this->grupo->porId($gid);
                $semDiretor[] = $g['nome'] ?? "#{$gid}";
                continue;
            }

            $novoId = $this->ata->criar([
                'titulo'              => $titulo,
                'descricao'           => $descricao,
                'grupo_id'            => $gid,
                'diretor_id'          => (int) $diretoresAtivos[0]['usuario_id'],
                'representante_id'    => (int) $_SESSION['usuario']['id'],
                'data_ata'            => $dataAta,
                'prazo_preenchimento' => $prazo,
                'horario_inicio'      => $hi,
                'horario_fim'         => $hf,
            ]);

            $this->audit->registrar('ata_replicada', 'atas', $novoId, null, [
                'origem_id' => $id,
                'grupo_id'  => $gid,
                'titulo'    => $titulo,
            ]);

            $replicadas++;
        }

        // Mensagem final
        $msg = 'Ata atualizada.';
        if ($replicadas > 0) {
            $msg .= " Replicada em {$replicadas} grupo(s).";
        }
        if (!empty($semDiretor)) {
            $msg .= ' Ignorados (sem diretor ativo): ' . implode(', ', $semDiretor) . '.';
        }
        $this->flash($msg, 'sucesso');

        $this->redirect('/atas/' . $id);
    }

        /**
     * Retorna alunos do grupo + alunos da turma, separados.
     * Alunos do grupo aparecem primeiro.
     */
    private function alunosParaSelecao(int $ataId): array
    {
        $ata = $this->ata->porId($ataId);
        if (!$ata) return ['grupo' => [], 'turma' => []];

        $pdo = Database::getConnection();

        // Alunos ativos no grupo
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.email
            FROM grupo_alunos ga
            INNER JOIN usuarios u ON u.id = ga.usuario_id
            WHERE ga.grupo_id = ? AND ga.saiu_em IS NULL
            ORDER BY u.nome ASC
        ");
        $stmt->execute([(int) $ata['grupo_id']]);
        $doGrupo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Alunos da turma que NÃO estão no grupo
        $idsGrupo = array_map(fn($a) => (int) $a['id'], $doGrupo);
        $sql = "
            SELECT u.id, u.nome, u.email
            FROM turma_usuarios tu
            INNER JOIN usuarios u ON u.id = tu.usuario_id
            WHERE tu.turma_id = ? AND tu.ativo = 1
        ";
        $params = [(int) $ata['turma_id']];
        if (!empty($idsGrupo)) {
            $ph = implode(',', array_fill(0, count($idsGrupo), '?'));
            $sql .= " AND u.id NOT IN ({$ph})";
            $params = array_merge($params, $idsGrupo);
        }
        $sql .= " ORDER BY u.nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $daTurma = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['grupo' => $doGrupo, 'turma' => $daTurma];
    }

        public function salvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        $titulo     = trim($_POST['titulo'] ?? '');
        $descricao  = trim($_POST['descricao'] ?? '') ?: null;
        $gruposIds  = array_map('intval', $_POST['grupos'] ?? []);
        $dataAta    = trim($_POST['data_ata'] ?? '');
        $prazo      = trim($_POST['prazo_preenchimento'] ?? '') ?: null;
        $hi         = trim($_POST['horario_inicio'] ?? '') ?: null;
        $hf         = trim($_POST['horario_fim'] ?? '') ?: null;
        // Quem preenche: só o diretor ou todos os membros do grupo
        $preenchimento = ($_POST['preenchimento'] ?? 'diretor') === 'grupo' ? 'grupo' : 'diretor';

        $erros = [];
        if (strlen($titulo) < 3)                                    $erros[] = 'Título muito curto.';
        if (empty($gruposIds))                                      $erros[] = 'Selecione pelo menos 1 grupo.';
        if (!DateTime::createFromFormat('Y-m-d', $dataAta))         $erros[] = 'Data da ata inválida.';
        if ($prazo && !DateTime::createFromFormat('Y-m-d', $prazo)) $erros[] = 'Prazo inválido.';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/projetos/' . $projetoId . '/atas/criar');
        }

        $criadas    = 0;
        $semDiretor = [];
        $idsCriados = [];

        foreach ($gruposIds as $grupoId) {
            $grupo = $this->grupo->porId($grupoId);
            if (!$grupo || (int) $grupo['projeto_id'] !== $projetoId) continue;

            $diretoresAtivos = $this->diretor->listarAtivos($grupoId);
            if (empty($diretoresAtivos)) {
                $semDiretor[] = $grupo['nome'];
                continue;
            }
            $diretorId = (int) $diretoresAtivos[0]['usuario_id'];

            $id = $this->ata->criar([
                'titulo'              => $titulo,
                'descricao'           => $descricao,
                'grupo_id'            => $grupoId,
                'diretor_id'          => $diretorId,
                'representante_id'    => (int) $_SESSION['usuario']['id'],
                'data_ata'            => $dataAta,
                'prazo_preenchimento' => $prazo,
                'horario_inicio'      => $hi,
                'horario_fim'         => $hf,
                'preenchimento'       => $preenchimento,
            ]);

            $this->audit->registrar('ata_criada', 'atas', $id, null, [
                'grupo_id' => $grupoId, 'diretor_id' => $diretorId, 'titulo' => $titulo,
                
            ]);

            // Auto-cadastra todos os membros do grupo como participantes (presente = 'sim')
            $membros = $this->membro->listarAtivos($grupoId);
            $pdo = Database::getConnection();
            foreach ($membros as $m) {
                $pdo->prepare("
                    INSERT IGNORE INTO ata_participantes (ata_id, aluno_id, presente)
                    VALUES (?, ?, 'sim')
                ")->execute([$id, (int) $m['usuario_id']]);
            }

            // Avisa quem preenche: o diretor; no modo "grupo", todos os membros
            $prazoTxt = $prazo ? ' Prazo: ' . date('d/m/Y', strtotime($prazo)) . '.' : '';
            (new Notificacao())->criar(
                $diretorId,
                'ata',
                'Nova ata para preencher',
                "Você é diretor do grupo '{$grupo['nome']}': preencha a ata '{$titulo}'.{$prazoTxt}",
                '/atas/' . $id . '/preencher'
            );
            if ($preenchimento === 'grupo') {
                $outros = array_values(array_filter(
                    array_map(fn($m) => (int) $m['usuario_id'], $membros),
                    fn($uid) => $uid !== $diretorId
                ));
                (new Notificacao())->criarParaVarios(
                    $outros,
                    'ata',
                    'Ata aberta para o grupo',
                    "Registre o que você fez na ata '{$titulo}' do grupo '{$grupo['nome']}'.{$prazoTxt}",
                    '/atas/' . $id . '/preencher'
                );
            }

            $idsCriados[] = $id;
            $criadas++;
        }

        if ($criadas === 0) {
            $msg = 'Nenhuma ata criada.';
            if (!empty($semDiretor)) {
                $msg .= ' Grupos sem diretor ativo: ' . implode(', ', $semDiretor) . '.';
            }
            $this->flash($msg);
            $this->redirect('/projetos/' . $projetoId . '/atas/criar');
        }

        $msg = "Ata(s) criada(s): {$criadas} grupo(s).";
        if (!empty($semDiretor)) {
            $msg .= " Ignorados (sem diretor ativo): " . implode(', ', $semDiretor) . '.';
        }
        $this->flash($msg, 'sucesso');

        if (count($idsCriados) === 1) {
            $this->redirect('/atas/' . $idsCriados[0]);
        }
        $this->redirect('/projetos/' . $projetoId . '/atas');
    }
        public function salvarParticipantes(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $this->exigirPreenchimento($ata);
        CsrfMiddleware::validate();

        $presencas      = $_POST['presenca'] ?? [];
        $justificativas = $_POST['justificativa'] ?? [];

        $pdo = Database::getConnection();

        // Todos os membros do grupo precisam ter linha em ata_participantes
        $membros = $this->membro->listarAtivos((int) $ata['grupo_id']);
        $idsValidos = array_map(fn($m) => (int) $m['usuario_id'], $membros);

        // "Falta justificada" precisa do motivo
        $semMotivo = [];
        foreach ($membros as $m) {
            $aid = (int) $m['usuario_id'];
            if (($presencas[$aid] ?? 'sim') === 'justificado' && trim($justificativas[$aid] ?? '') === '') {
                $semMotivo[] = $m['nome'];
            }
        }
        if ($semMotivo) {
            $this->flash('Informe o motivo da falta justificada de: ' . implode(', ', $semMotivo) . '.');
            $this->voltarAta($id);
        }

        foreach ($idsValidos as $alunoId) {
            $presente = $presencas[$alunoId] ?? 'sim';
            if (!in_array($presente, ['sim', 'nao', 'justificado'], true)) {
                $presente = 'sim';
            }
            $just = trim($justificativas[$alunoId] ?? '') ?: null;

            // Justificativa só faz sentido quando NÃO está presente
            if ($presente === 'sim') {
                $just = null;
            }

            $pdo->prepare("
                INSERT INTO ata_participantes (ata_id, aluno_id, presente, justificativa)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    presente = VALUES(presente),
                    justificativa = VALUES(justificativa)
            ")->execute([$id, $alunoId, $presente, $just]);
        }

        $this->audit->registrar('ata_participantes_atualizados', 'ata_participantes', $id, null,
            ['total' => count($idsValidos)]);

        $this->flash('Presenças atualizadas.', 'sucesso');
        $this->voltarAta($id);
    }
    
    // ============ TELA FOCADA DE PREENCHIMENTO ============

public function preencher(int $id)
{
    $ata = $this->ata->porId($id);
    if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

    $this->exigirContribuicao($ata);

    $projeto = $this->projeto->porId((int) $ata['projeto_id']);

    $this->render('atas/preencher', [
        'ata'           => $ata,
        'projeto'       => $projeto,
        'atividades'    => $this->ativ->listarPorAta($id),
        'relatorios'    => $this->rel->listarPorAta($id),
        'participantes' => $this->participantes($id),
        'membros'       => $this->membro->listarAtivos((int) $ata['grupo_id']),
        'souDiretorAta' => $_SESSION['usuario']['tipo'] === 'master' || $this->ehDiretorDoGrupo($ata, (int) $_SESSION['usuario']['id']),
        'meuId'         => (int) $_SESSION['usuario']['id'],
        'alunos'        => $this->alunosParaSelecao($id),
    ]);
}

// ============ TELA FOCADA DE VALIDAÇÃO ============

public function validarForm(int $id)
{
    $ata = $this->ata->porId($id);
    if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

    $projeto = $this->projeto->porId((int) $ata['projeto_id']);
    $this->exigirPodeEditar($projeto);

    if ($ata['status'] !== 'preenchida') {
        $this->flash('Apenas atas preenchidas podem ser validadas.');
        $this->redirect('/atas/' . $id);
    }

    $this->render('atas/validar', [
        'ata'           => $ata,
        'projeto'       => $projeto,
        'atividades'    => $this->ativ->listarPorAta($id),
        'relatorios'    => $this->rel->listarPorAta($id),
        'participantes' => $this->participantes($id),
    ]);
}
    // ============ DETALHE / VISUALIZAÇÃO ============

        public function detalhe(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        $u = $_SESSION['usuario'];

        $souMaster       = $u['tipo'] === 'master';
        $souRep          = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        $souDiretor      = $this->diretor->ehDiretorAtivo((int) $ata['grupo_id'], (int) $u['id']);
        $souParticipante = $this->ehParticipante($id, (int) $u['id']) || $this->ehMembroDoGrupo($ata, (int) $u['id']);

        if (!$souMaster && !$souRep && !$souDiretor && !$souParticipante) {
            http_response_code(403); exit('Sem acesso a esta ata.');
        }

        $atividades    = $this->ativ->listarPorAta($id);
        $relatorios    = $this->rel->listarPorAta($id);
        $participantes = $this->participantes($id);

        // Alunos para os selects (grupo + turma)
        $alunos = $this->alunosParaSelecao($id);

        // Permissões
        // Qualquer diretor ativo do grupo pode preencher (diretor_id é só referência)
        $podePreencher = ($souDiretor || (($ata['preenchimento'] ?? 'diretor') === 'grupo' && $this->ehMembroDoGrupo($ata, (int) $u['id'])))
                      && $ata['status'] === 'pendente'
                      && !$projeto['encerrado'];

        $podeValidar = ($souRep || $souMaster) && !$projeto['encerrado'];
        $podeExcluir = ($souRep || $souMaster) && !$projeto['encerrado'];

        $this->render('atas/detalhe', [
            'ata'                  => $ata,
            'projeto'              => $projeto,
            'atividades'           => $atividades,
            'relatorios'           => $relatorios,
            'participantes'        => $participantes,
            'alunosDoGrupo'        => $alunos['grupo'],
            'alunosOutros'         => $alunos['turma'],
            'podePreencher'        => $podePreencher,
            'podeValidar'          => $podeValidar,
            'podeExcluir'          => $podeExcluir,
            'souDiretorDesignado'  => $souDiretor,
        ]);
    }

    // ============ PREENCHER (DIRETOR) ============

    public function adicionarAtividade(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $this->exigirContribuicao($ata);
        CsrfMiddleware::validate();

        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '') ?: null;
        $participantesIds = $this->filtrarDaTurma($ata, (array) ($_POST['participantes'] ?? []));

        if (strlen($nome) < 2) {
            $this->flash('Nome da atividade muito curto.');
            $this->voltarAta($id);
        }

        $ativId = $this->ativ->criar($id, $nome, $descricao, (int) $_SESSION['usuario']['id']);

        foreach ($participantesIds as $uid) {
            if ($uid > 0) {
                $this->ativ->adicionarParticipante($ativId, $uid, 'colaborador');
            }
        }

        $mencionados = array_values(array_diff($participantesIds, [(int) $_SESSION['usuario']['id']]));
        if ($mencionados) {
            (new Notificacao())->criarParaVarios(
                $mencionados,
                'ata',
                'Você foi mencionado em uma ata',
                "{$_SESSION['usuario']['nome']} registrou você na atividade '{$nome}' da ata '{$ata['titulo']}'.",
                '/minhas-atividades'
            );
        }

        $this->audit->registrar('atividade_adicionada', 'atividades_ata', $ativId, null,
            ['ata_id' => $id, 'nome' => $nome]);

        $this->flash('Atividade adicionada.', 'sucesso');
        $this->voltarAta($id);
    }

    public function excluirAtividade(int $ativId)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM atividades_ata WHERE id = ? LIMIT 1");
        $stmt->execute([$ativId]);
        $atv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$atv) { $this->flash('Atividade não encontrada.'); $this->redirect('/turmas'); }

        $ata = $this->ata->porId((int) $atv['ata_id']);
        $this->exigirContribuicao($ata);
        $this->exigirAutoriaOuDiretor($ata, (int) $atv['autor_id']);
        CsrfMiddleware::validate();

        $this->ativ->excluir($ativId);
        $this->audit->registrar('atividade_removida', 'atividades_ata', $ativId, $atv, null);
        $this->flash('Atividade removida.', 'sucesso');
        $this->voltarAta((int) $atv['ata_id']);
    }

    public function adicionarRelatorio(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $this->exigirContribuicao($ata);
        CsrfMiddleware::validate();

        $titulo         = trim($_POST['titulo'] ?? '') ?: null;
        $conteudo       = trim($_POST['conteudo'] ?? '');
        $tipoRelatorio  = $_POST['tipo_relatorio'] ?? 'ocorrencia';
        $tema           = $_POST['tema'] ?? null;
        $participantesIds = $this->filtrarDaTurma($ata, (array) ($_POST['participantes'] ?? []));

        $erros = [];
        if (strlen($conteudo) < 3) $erros[] = 'Conteúdo do relatório muito curto.';
        if (!in_array($tipoRelatorio, ['ocorrencia', 'decisao', 'encaminhamento', 'observacao'], true)) {
            $erros[] = 'Tipo de relatório inválido.';
        }

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->voltarAta($id);
        }

        $usuario = $_SESSION['usuario'];

        // Descobre o papel real do autor neste contexto
        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        $souRep  = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $usuario['id']);

        // Master cai em 'diretor' por padrão (é quem revisa), a menos que seja rep da turma
        $tipoUsuario = $souRep ? 'representante' : ($this->ehDiretorDoGrupo($ata, (int) $usuario['id']) ? 'diretor' : 'aluno');

        $relId = $this->rel->criar($id, (int) $usuario['id'], $tipoUsuario, $titulo, $conteudo, $tipoRelatorio, $tema);

        foreach ($participantesIds as $uid) {
            if ($uid > 0) $this->rel->adicionarParticipante($relId, $uid);
        }

        $this->audit->registrar('relatorio_adicionado', 'relatorios_ata', $relId, null,
            ['ata_id' => $id, 'tema' => $tema]);

        $this->flash('Relatório adicionado.', 'sucesso');
        $this->voltarAta($id);
    }

    public function excluirRelatorio(int $relId)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM relatorios_ata WHERE id = ? LIMIT 1");
        $stmt->execute([$relId]);
        $rel = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rel) { $this->flash('Relatório não encontrado.'); $this->redirect('/turmas'); }

        $ata = $this->ata->porId((int) $rel['ata_id']);
        $this->exigirContribuicao($ata);
        $this->exigirAutoriaOuDiretor($ata, (int) $rel['usuario_id']);
        CsrfMiddleware::validate();

        $this->rel->excluir($relId);
        $this->audit->registrar('relatorio_removido', 'relatorios_ata', $relId, $rel, null);
        $this->flash('Relatório removido.', 'sucesso');
        $this->voltarAta((int) $rel['ata_id']);
    }

        public function finalizar(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $this->exigirPreenchimento($ata);
        CsrfMiddleware::validate();

        $atividades = $this->ativ->listarPorAta($id);
        if (empty($atividades)) {
            $this->flash('Adicione pelo menos 1 atividade antes de finalizar.');
            $this->redirect('/atas/' . $id);
        }

        $this->ata->definirStatus($id, 'preenchida');
        $this->audit->registrar('ata_preenchida', 'atas', $id,
            ['status' => 'pendente'], ['status' => 'preenchida']);

        // Notifica todos os representantes da turma
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT usuario_id FROM turma_usuarios
            WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
        ");
        $stmt->execute([(int) $ata['turma_id']]);
        $reps = $stmt->fetchAll(PDO::FETCH_COLUMN);

        (new Notificacao())->criarParaVarios(
            $reps,
            'ata',
            'Ata aguardando validação',
            "A ata '{$ata['titulo']}' do grupo '{$ata['grupo_nome']}' foi preenchida e aguarda revisão.",
            '/atas/' . $id
        );

        $this->flash('Ata finalizada. Aguardando validação do representante.', 'sucesso');
        $this->redirect('/atas/' . $id);
    }

    // ============ VALIDAR (REP / MASTER) ============

        public function validar(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        if ($ata['status'] !== 'preenchida') {
            $this->flash('Apenas atas preenchidas podem ser validadas.');
            $this->redirect('/atas/' . $id);
        }

        $this->ata->definirStatus($id, 'revisada');
        $this->audit->registrar('ata_validada', 'atas', $id,
            ['status' => 'preenchida'], ['status' => 'revisada']);

        // Notifica o diretor que preencheu
        if (!empty($ata['diretor_id'])) {
            (new Notificacao())->criar(
                (int) $ata['diretor_id'],
                'ata',
                'Ata validada',
                "Sua ata '{$ata['titulo']}' foi revisada e aprovada pelo representante.",
                '/atas/' . $id
            );
        }

        $this->flash('Ata validada.', 'sucesso');
        $this->redirect('/atas/' . $id);
    }

    /**
     * Representante devolve uma ata preenchida (ou já validada) para o diretor corrigir.
     * Volta para 'pendente'; o motivo é obrigatório e vai no aviso aos diretores do grupo.
     */
    public function reabrir(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        if (!in_array($ata['status'], ['preenchida', 'revisada'], true)) {
            $this->flash('Esta ata ainda está com o diretor: não há o que reabrir.');
            $this->redirect('/atas/' . $id);
        }
        if (!empty($projeto['encerrado'])) {
            $this->flash('Projeto encerrado: as atas não podem mais ser reabertas.');
            $this->redirect('/atas/' . $id);
        }

        $motivo = trim($_POST['motivo'] ?? '');
        if (mb_strlen($motivo) < 3) {
            $this->flash('Diga o que precisa ser corrigido: o diretor vai ler esse motivo.');
            $this->redirect('/atas/' . $id . ($ata['status'] === 'preenchida' ? '/validar-form' : ''));
        }

        $this->ata->definirStatus($id, 'pendente');
        $this->audit->registrar('ata_reaberta', 'atas', $id,
            ['status' => $ata['status']], ['status' => 'pendente', 'motivo' => $motivo]);

        $diretores = array_map(fn($d) => (int) $d['usuario_id'], $this->diretor->listarAtivos((int) $ata['grupo_id']));
        (new Notificacao())->criarParaVarios(
            $diretores,
            'ata',
            'Ata devolvida para correção',
            "A ata '{$ata['titulo']}' voltou para você corrigir. Motivo: {$motivo}",
            '/atas/' . $id . '/preencher'
        );

        $this->flash('Ata devolvida ao diretor. Ele foi avisado com o motivo.', 'sucesso');
        $this->redirect('/atas/' . $id);
    }

    public function excluir(int $id)
    {
        $ata = $this->ata->porId($id);
        if (!$ata) { $this->flash('Ata não encontrada.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        $pid = (int) $ata['projeto_id'];
        $this->ata->excluir($id);

        $this->audit->registrar('ata_excluida', 'atas', $id, $ata, null);
        $this->flash('Ata excluída.', 'sucesso');
        $this->redirect('/projetos/' . $pid . '/atas');
    }

    // ============ HELPERS ============

    // ============ MINHAS ATIVIDADES (área do aluno) ============

    public function minhasAtividades()
    {
        $u = $_SESSION['usuario'];
        $this->render('atas/minhas_atividades', [
            'atividades' => $this->ata->atividadesDoUsuario((int) $u['id']),
            'meuId'      => (int) $u['id'],
        ]);
    }

    private function ehParticipante(int $ataId, int $usuarioId): bool
    {
        $stmt = Database::getConnection()->prepare("
            SELECT 1 FROM ata_participantes WHERE ata_id = ? AND aluno_id = ? LIMIT 1
        ");
        $stmt->execute([$ataId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    private function participantes(int $ataId): array
    {
        $stmt = Database::getConnection()->prepare("
            SELECT ap.*, u.nome AS usuario_nome
            FROM ata_participantes ap
            INNER JOIN usuarios u ON u.id = ap.aluno_id
            WHERE ap.ata_id = ?
        ");
        $stmt->execute([$ataId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function temDiretorAtivoNoProjeto(int $projetoId, int $usuarioId): bool
    {
        $stmt = Database::getConnection()->prepare("
            SELECT 1 FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([$projetoId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    private function exigirAcesso(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Sem acesso.');
    }

    private function exigirPodeEditar(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Apenas representante ou master.');
    }

    /**
     * Mantém só os IDs de pessoas ativas na turma da ata (impede marcar usuários de fora).
     */
    private function filtrarDaTurma(array $ata, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
        if (!$ids) return [];
        $stmt = Database::getConnection()->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND ativo = 1");
        $stmt->execute([(int) $ata['turma_id']]);
        return array_values(array_intersect($ids, array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }

    /**
     * Depois de uma ação de preenchimento, volta para a tela de onde a pessoa veio
     * (a tela focada "preencher" ou o detalhe da ata).
     */
    private function voltarAta(int $ataId): void
    {
        $origem = $_SERVER['HTTP_REFERER'] ?? '';
        $this->redirect('/atas/' . $ataId . (str_contains($origem, '/atas/' . $ataId . '/preencher') ? '/preencher' : ''));
    }

    private function ehDiretorDoGrupo(array $ata, int $usuarioId): bool
    {
        $st = Database::getConnection()->prepare("SELECT 1 FROM grupo_diretores WHERE grupo_id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1");
        $st->execute([(int) $ata['grupo_id'], $usuarioId]);
        return (int) $ata['diretor_id'] === $usuarioId || (bool) $st->fetchColumn();
    }

    private function ehMembroDoGrupo(array $ata, int $usuarioId): bool
    {
        return $this->membro->estaAtivo((int) $ata['grupo_id'], $usuarioId);
    }

    /**
     * Quem pode adicionar atividades e relatórios: o diretor do grupo e, quando a ata
     * está aberta ao grupo, qualquer membro ativo. Presenças e envio continuam com o diretor.
     */
    private function exigirContribuicao(array $ata): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;

        $uid = (int) $u['id'];
        $pode = $this->ehDiretorDoGrupo($ata, $uid)
             || (($ata['preenchimento'] ?? 'diretor') === 'grupo' && $this->ehMembroDoGrupo($ata, $uid));
        if (!$pode) { http_response_code(403); exit('Você não pode preencher esta ata.'); }
        if ($ata['status'] !== 'pendente') { http_response_code(403); exit('Esta ata já foi enviada.'); }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        if ($projeto['encerrado']) { http_response_code(403); exit('Projeto encerrado.'); }
    }

    /** Membro comum só remove o que ele mesmo registrou; diretor e master removem qualquer item. */
    private function exigirAutoriaOuDiretor(array $ata, int $autorId): void
    {
        $u = $_SESSION['usuario'];
        if ($u['tipo'] === 'master' || $this->ehDiretorDoGrupo($ata, (int) $u['id']) || (int) $u['id'] === $autorId) return;
        http_response_code(403); exit('Você só pode remover o que você registrou.');
    }

    private function exigirPreenchimento(array $ata): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;

        // Qualquer diretor ativo do grupo pode preencher: diretor_id é só uma referência (descricao.md 8.1)
        $stDir = Database::getConnection()->prepare(
            "SELECT 1 FROM grupo_diretores WHERE grupo_id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1"
        );
        $stDir->execute([(int) $ata['grupo_id'], (int) $u['id']]);
        if ((int) $ata['diretor_id'] !== (int) $u['id'] && !$stDir->fetchColumn()) {
            http_response_code(403); exit('Apenas os diretores do grupo podem preencher esta ata.');
        }
        if ($ata['status'] !== 'pendente') {
            http_response_code(403); exit('Esta ata já foi finalizada.');
        }

        $projeto = $this->projeto->porId((int) $ata['projeto_id']);
        if ($projeto['encerrado']) {
            http_response_code(403); exit('Projeto encerrado.');
        }
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