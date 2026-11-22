<?php
// app/Controllers/AvaliacaoController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Criterio.php';
require_once __DIR__ . '/../Models/Etapa.php';
require_once __DIR__ . '/../Models/Avaliacao.php';
require_once __DIR__ . '/../Models/AvaliacaoColetiva.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/ConfiguracaoConceito.php';
require_once __DIR__ . '/../Services/NotaService.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Usuario.php';

class AvaliacaoController
{
    private Projeto              $projeto;
    private Criterio             $criterio;
    private Avaliacao            $aval;
    private AvaliacaoColetiva    $coletiva;
    private TurmaUsuario         $tu;
    private GrupoAluno           $membro;
    private GrupoDiretor         $diretor;
    private ConfiguracaoConceito $cfg;
    private NotaService          $nota;
    private Auditoria            $audit;

    public function __construct()
    {
        $this->projeto  = new Projeto();
        $this->criterio = new Criterio();
        $this->aval     = new Avaliacao();
        $this->coletiva = new AvaliacaoColetiva();
        $this->tu       = new TurmaUsuario();
        $this->membro   = new GrupoAluno();
        $this->diretor  = new GrupoDiretor();
        $this->cfg      = new ConfiguracaoConceito();
        $this->nota     = new NotaService();
        $this->audit    = new Auditoria();
    }
    /**
     * Avisa cada aluno que recebeu avaliação nova ou alterada: uma notificação por salvamento,
     * sem mostrar a nota nem quem avaliou. $contagemPorAluno = [aluno_id => quantidade].
     */
    private function notificarAvaliados(int $projetoId, array $contagemPorAluno, string $origem): void
    {
        if (empty($contagemPorAluno)) return;

        $notif = new Notificacao();
        foreach ($contagemPorAluno as $alunoId => $qtd) {
            $qtd = (int) $qtd;
            $notif->criar(
                (int) $alunoId,
                'avaliacao',
                'Nova avaliação recebida',
                $qtd === 1
                    ? "Você recebeu uma nova avaliação ({$origem})."
                    : "Você recebeu {$qtd} novas avaliações ({$origem}).",
                '/projetos/' . $projetoId . '/avaliacoes/minhas'
            );
        }
    }

    /**
     * Redireciona para a visão correta conforme o papel do usuário.
     */
        public function index(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster  = $u['tipo'] === 'master';
        $souRep     = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        $souDiretor = $this->temDiretorAtivoNoProjeto((int) $projeto['id'], (int) $u['id']);

        if ($souMaster || $souRep) {
            $this->redirect('/projetos/' . $projetoId . '/avaliacoes/representante');
        }
        if ($souDiretor) {
            $this->redirect('/projetos/' . $projetoId . '/avaliacoes/diretor');
        }
        // Aluno comum: começa pelo que falta fazer (e vê o boletim parcial logo abaixo)
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/geral');
    }

    // ============ BULK REPRESENTANTE ============

    public function bulkRepresentante(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep) {
            http_response_code(403); exit('Apenas representante ou master.');
        }

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.', 'aviso');
            $this->redirect('/projetos/' . $projetoId . '/criterios');
        }

        $this->criterio->atualizarBloqueios($projetoId);

        $cfg         = $this->cfg->garantir($projetoId);
        $criterios   = $this->criterio->listarPorProjeto($projetoId);
        $alunos      = $this->tu->listarAlunos((int) $projeto['turma_id']);

        // Separa critérios: aplicáveis a todos vs apenas para diretores vs apenas representantes
        $critTodos        = [];
        $critDiretores    = [];
        $critRepresent    = [];
        foreach ($criterios as $c) {
            if ($c['tipo_avaliacao'] === 'coletiva') continue; // coletiva tem tela própria
            switch ($c['aplicavel_a']) {
                case 'apenas_diretores':      $critDiretores[] = $c; break;
                case 'apenas_representantes': $critRepresent[] = $c; break;
                default:                      $critTodos[]     = $c;
            }
        }

        // Busca avaliações já feitas por este representante
        $minhasAvaliacoes = [];
        foreach ($criterios as $c) {
            $minhasAvaliacoes[(int) $c['id']] = $this->aval->listarDoAvaliador((int) $c['id'], (int) $u['id']);
        }

        // Quais critérios este usuário edita aqui; os demais aparecem só para leitura,
        // com a nota calculada (média dos colegas, do próprio aluno ou dos diretores).
        $editaveis    = [];
        $notasLeitura = [];
        foreach ($criterios as $c) {
            if ($c['tipo_avaliacao'] === 'coletiva') continue;
            $cid = (int) $c['id'];
            $editaveis[$cid] = in_array($c['tipo_avaliacao'], ['representante', 'misto'], true)
                            || ($c['tipo_avaliacao'] === 'diretor' && $souMaster);
            if (!$editaveis[$cid]) {
                foreach ($alunos as $a) {
                    $notasLeitura[$cid][(int) $a['id']] = $this->nota->notaDoCriterio($cid, (int) $a['id']);
                }
            }
        }

        $this->render('avaliacoes/index_rep', [
            'projeto'          => $projeto,
            'cfg'              => $cfg,
            'critTodos'        => $critTodos,
            'critDiretores'    => $critDiretores,
            'critRepresent'    => $critRepresent,
            'alunos'           => $alunos,
            'minhasAvaliacoes' => $minhasAvaliacoes,
            'editaveis'        => $editaveis,
            'notasLeitura'     => $notasLeitura,
        ]);
    }

    public function bulkRepresentanteSalvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        if (!$souMaster && !$souRep) { http_response_code(403); exit('Sem permissão.'); }

        CsrfMiddleware::validate();

        if ($projeto['encerrado']) { $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId); }

        $limpar = !empty($_POST['limpar_antes']);

        // Atualiza bloqueios ANTES de limpar: critérios já bloqueados ficam intactos
        $this->criterio->atualizarBloqueios($projetoId);

        if ($limpar) {
            $this->aval->deletarDoAvaliadorEmCriteriosAbertos($projetoId, (int) $u['id']);
        }

        // Só alunos ativos desta turma podem receber nota
        $idsTurma = array_map(fn($a) => (int) ($a['id'] ?? $a['usuario_id'] ?? 0), $this->tu->listarAlunos((int) $projeto['turma_id']));
        $mudancas = [];
        $cfg       = $this->cfg->garantir($projetoId);
        $criterios = $this->criterio->listarPorProjeto($projetoId);

        $salvos = 0;
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            if ($c['tipo_avaliacao'] === 'coletiva') continue;

            $cid = (int) $c['id'];
            $tipoAval = Avaliacao::MAPA_TIPO[$c['tipo_avaliacao']] ?? null;

            // Decide se este representante aplica este critério
            // Critérios 'diretor' são aplicados pelo representante apenas se o aluno for
            // único diretor do grupo — deixamos para a F6 parte 2 (não forçamos aqui)
            // Pares e autoavaliação são dos alunos: o representante só visualiza essas colunas
            $aplica = in_array($c['tipo_avaliacao'], ['representante', 'misto'], true)
                   || ($c['tipo_avaliacao'] === 'diretor' && $souMaster);
            if (!$aplica) continue;
            if (!$tipoAval) continue;

            // Pega lista de valores do POST: aval[cid][aluno_id]
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $alunoId => $dados) {
                $alunoId = (int) $alunoId;
                if (!in_array($alunoId, $idsTurma, true)) continue;

                $nota = $this->lerNota((array) $dados, $cfg);
                if ($nota === null) {
                    // vazio: se estava salvo, remove
                    $this->aval->deletar($cid, $alunoId, (int) $u['id'], $tipoAval);
                    continue;
                }

                if ($this->aval->upsert($cid, $alunoId, (int) $u['id'], $tipoAval, $nota['conceito'], $nota['valor'])) {
                    $mudancas[$alunoId] = ($mudancas[$alunoId] ?? 0) + 1;
                }
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_rep_salva', 'avaliacoes', $projetoId, null,
            ['avaliador_id' => $u['id'], 'limpar' => $limpar, 'total' => $salvos]);

        $this->notificarAvaliados($projetoId, $mudancas, 'representante');

        $this->flash("Avaliações salvas ({$salvos}).", 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/representante');
    }

    // ============ MINHAS NOTAS (ALUNO) ============

    public function minhasNotas(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $boletim = $this->nota->boletimDoAluno($projetoId, (int) $u['id']);
        $cfg     = $this->cfg->garantir($projetoId);

        $this->render('avaliacoes/minhas_notas', [
            'projeto' => $projeto,
            'boletim' => $boletim,
            'cfg'     => $cfg,
        ]);
    }

        // ============ AVALIAÇÃO POR DIRETOR ============

    public function diretor(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        // Grupos que este usuário dirige
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT g.id, g.nome
            FROM grupos g
            INNER JOIN grupo_diretores gd ON gd.grupo_id = g.id
            WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
            ORDER BY g.nome ASC
        ");
        $stmt->execute([$projetoId, (int) $u['id']]);
        $meusGrupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($meusGrupos)) {
            http_response_code(403);
            exit('Você não é diretor ativo de nenhum grupo neste projeto.');
        }

        // Grupo selecionado (via ?grupo_id=N ou o primeiro)
        $grupoId = (int) ($_GET['grupo_id'] ?? $meusGrupos[0]['id']);

        // Verifica se o grupo pertence ao projeto E o usuário dirige ele
        $grupoValido = false;
        foreach ($meusGrupos as $g) {
            if ((int) $g['id'] === $grupoId) { $grupoValido = true; break; }
        }
        if (!$grupoValido) {
            $this->flash('Grupo inválido.');
            $this->redirect('/projetos/' . $projetoId . '/avaliacoes/diretor');
        }

        // Membros do grupo
        $membros = $this->membro->listarAtivos($grupoId);

        // Critérios tipo 'diretor' + 'misto' (diretor faz parte do misto)
        $this->criterio->atualizarBloqueios($projetoId);
        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => in_array($c['tipo_avaliacao'], ['diretor', 'misto'], true) && !$c['bloqueado']
        );

        $cfg = $this->cfg->garantir($projetoId);

        // Avaliações já feitas por MIM em cada critério
        $minhasAvaliacoes = [];
        foreach ($criterios as $c) {
            $minhasAvaliacoes[(int) $c['id']] = $this->aval->listarDoAvaliador((int) $c['id'], (int) $u['id']);
        }

        // Avaliações feitas pelos OUTROS diretores do grupo (transparência)
        $avaliacoesOutros = [];
        foreach ($meusGrupos as $g) {
            // pega outros diretores ativos no grupo
            $stmt = $pdo->prepare("
                SELECT usuario_id FROM grupo_diretores
                WHERE grupo_id = ? AND ativo = 1 AND usuario_id != ?
            ");
            $stmt->execute([(int) $g['id'], (int) $u['id']]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $outroId) {
                foreach ($criterios as $c) {
                    $avaliacoesOutros[(int) $c['id']][(int) $outroId] =
                        $this->aval->listarDoAvaliador((int) $c['id'], (int) $outroId);
                }
            }
        }

        $this->render('avaliacoes/index_diretor', [
            'projeto'          => $projeto,
            'cfg'              => $cfg,
            'meusGrupos'       => $meusGrupos,
            'grupoAtual'       => $grupoId,
            'membros'          => $membros,
            'criterios'        => array_values($criterios),
            'minhasAvaliacoes' => $minhasAvaliacoes,
            'avaliacoesOutros' => $avaliacoesOutros,
            'meuId'            => (int) $u['id'],
        ]);
    }

    public function diretorSalvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) { $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId); }

        $grupoId = (int) ($_POST['grupo_id'] ?? 0);

        // Verifica que o usuário é diretor ativo deste grupo
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT gd.grupo_id FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE gd.grupo_id = ? AND gd.usuario_id = ? AND gd.ativo = 1 AND g.projeto_id = ?
        ");
        $stmt->execute([$grupoId, (int) $u['id'], $projetoId]);
        if (!$stmt->fetchColumn()) { http_response_code(403); exit('Você não dirige este grupo.'); }

        $this->criterio->atualizarBloqueios($projetoId);
        $cfg = $this->cfg->garantir($projetoId);

        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => in_array($c['tipo_avaliacao'], ['diretor', 'misto'], true)
        );

        $salvos = 0;
        $bloqueados = 0;
        $mudancas = [];

        foreach ($criterios as $c) {
            if ($c['bloqueado']) { $bloqueados++; continue; }

            $cid = (int) $c['id'];
            $tipoAval = 'diretor'; // diretor sempre grava tipo 'diretor'

            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $alunoId => $dados) {
                $alunoId = (int) $alunoId;

                // Não pode se auto-avaliar
                if ($alunoId === (int) $u['id']) continue;

                // Aluno precisa ser membro ativo do grupo
                if (!$this->membro->estaAtivo($grupoId, $alunoId)) continue;

                $nota = $this->lerNota((array) $dados, $cfg);
                if ($nota === null) {
                    $this->aval->deletar($cid, $alunoId, (int) $u['id'], $tipoAval);
                    continue;
                }

                if ($this->aval->upsert($cid, $alunoId, (int) $u['id'], $tipoAval, $nota['conceito'], $nota['valor'])) {
                    $mudancas[$alunoId] = ($mudancas[$alunoId] ?? 0) + 1;
                }
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_diretor_salva', 'avaliacoes', $grupoId, null,
            ['avaliador_id' => $u['id'], 'total' => $salvos]);

        $this->notificarAvaliados($projetoId, $mudancas, 'diretor do grupo');

        $msg = "Avaliações salvas ({$salvos}).";
        if ($bloqueados > 0) $msg .= " {$bloqueados} critério(s) ignorado(s) por estar bloqueado(s).";
        $this->flash($msg, 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/diretor?grupo_id=' . $grupoId);
    }

    // ============ AVALIAÇÃO POR PARES ============

        public function pares(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $this->criterio->atualizarBloqueios($projetoId);
        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => $c['tipo_avaliacao'] === 'pares' && !$c['bloqueado']
        );

        $pdo     = Database::getConnection();
        $turmaId = (int) $projeto['turma_id'];

        // Colegas ativos na turma com grupos do projeto
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.email,
                   (
                     SELECT GROUP_CONCAT(DISTINCT g.nome ORDER BY g.nome SEPARATOR ', ')
                     FROM grupo_alunos ga
                     INNER JOIN grupos g ON g.id = ga.grupo_id
                     WHERE ga.usuario_id = u.id
                       AND ga.saiu_em IS NULL
                       AND g.projeto_id = ?
                   ) AS grupos
            FROM turma_usuarios tu
            INNER JOIN usuarios u ON u.id = tu.usuario_id
            WHERE tu.turma_id = ? AND tu.ativo = 1 AND u.id != ?
            ORDER BY u.nome ASC
        ");
        $stmt->execute([$projetoId, $turmaId, (int) $u['id']]);
        $colegasIds    = $this->colegasDeGrupo($projetoId, (int) $u['id']);
        $alunosDaTurma = array_values(array_filter(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            fn($a) => in_array((int) $a['id'], $colegasIds, true)
        ));

        // Diretores ativos no projeto
        $stmt = $pdo->prepare("
            SELECT DISTINCT gd.usuario_id
            FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE g.projeto_id = ? AND gd.ativo = 1
        ");
        $stmt->execute([$projetoId]);
        $diretoresIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        // Representantes ativos da turma
        $stmt = $pdo->prepare("
            SELECT usuario_id FROM turma_usuarios
            WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
        ");
        $stmt->execute([$turmaId]);
        $repsIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $dados = [];
        foreach ($criterios as $c) {
            $cid       = (int) $c['id'];
            $aplicavel = $c['aplicavel_a'];

            $colegas = [];
            foreach ($alunosDaTurma as $a) {
                $aid = (int) $a['id'];

                if ($aplicavel === 'apenas_diretores'       && !in_array($aid, $diretoresIds, true)) continue;
                if ($aplicavel === 'apenas_representantes'  && !in_array($aid, $repsIds, true))     continue;

                $a['is_diretor']       = in_array($aid, $diretoresIds, true);
                $a['is_representante'] = in_array($aid, $repsIds, true);

                $colegas[$aid] = $a;
            }

            $minhas = [];
            foreach ($this->aval->listarDoAvaliador($cid, (int) $u['id']) as $m) {
                $minhas[(int) $m['aluno_id']] = $m;
            }

            $dados[$cid] = [
                'criterio' => $c,
                'colegas'  => array_values($colegas),
                'minhas'   => $minhas,
            ];
        }

        $cfg = $this->cfg->garantir($projetoId);

        $this->render('avaliacoes/pares', [
            'projeto' => $projeto,
            'cfg'     => $cfg,
            'dados'   => $dados,
        ]);
    }

    public function paresSalvar(int $projetoId)
{
    $projeto = $this->projeto->porId($projetoId);
    if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

    $u = $_SESSION['usuario'];
    $this->exigirAcesso($projeto);
    CsrfMiddleware::validate();

    if ($projeto['encerrado']) {
        $this->flash('Projeto encerrado.');
        $this->redirect('/projetos/' . $projetoId);
    }

    // ---------- Requisição AJAX? ----------
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    // ---------- Critério específico (opcional) ----------
    $criterioIdUnico = (int) ($_POST['criterio_id'] ?? 0);

    $this->criterio->atualizarBloqueios($projetoId);
    $cfg = $this->cfg->garantir($projetoId);

    $todosCriterios = array_filter(
        $this->criterio->listarPorProjeto($projetoId),
        fn($c) => $c['tipo_avaliacao'] === 'pares'
    );

    // Se veio critério único, filtra só ele
    if ($criterioIdUnico > 0) {
        $todosCriterios = array_filter(
            $todosCriterios,
            fn($c) => (int) $c['id'] === $criterioIdUnico
        );
    }

    $pdo     = Database::getConnection();
    $turmaId = (int) $projeto['turma_id'];

    // Só colegas que dividem grupo com quem avalia (e que seguem ativos na turma)
    $stmt = $pdo->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND ativo = 1");
    $stmt->execute([$turmaId]);
    $alunosDaTurma = array_values(array_intersect(
        array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)),
        $this->colegasDeGrupo($projetoId, (int) $u['id'])
    ));

    $salvos   = 0;
    $pulados  = 0;
    $mudancas = [];

    foreach ($todosCriterios as $c) {
        if ($c['bloqueado']) continue;

        $cid   = (int) $c['id'];
        $linha = $_POST['aval'][$cid] ?? [];

        foreach ($linha as $alunoId => $dados) {
            $alunoId = (int) $alunoId;

            // Não pode se autoavaliar
            if ($alunoId === (int) $u['id']) continue;
            // Precisa ser colega de grupo, ativo na turma
            if (!in_array($alunoId, $alunosDaTurma, true)) continue;

            $just = trim($dados['justificativa'] ?? '');
            $nota = $this->lerNota((array) $dados, $cfg);

            // Célula vazia — apaga se existia
            if ($nota === null) {
                $this->aval->deletar($cid, $alunoId, (int) $u['id'], 'par');
                continue;
            }

            // Justificativa obrigatória APENAS para R e I (conceito já conferido pelo valor)
            if (in_array($nota['conceito'], ['R', 'I'], true) && $just === '') {
                $pulados++;
                continue;
            }

            if ($this->aval->upsert($cid, $alunoId, (int) $u['id'], 'par', $nota['conceito'], $nota['valor'], $just)) {
                $mudancas[$alunoId] = ($mudancas[$alunoId] ?? 0) + 1;
            }
            $salvos++;
        }
    }

    // ---------- Auditoria ----------
    $this->audit->registrar('avaliacao_pares_salva', 'avaliacoes', $projetoId, null, [
        'avaliador_id' => $u['id'],
        'criterio_id'  => $criterioIdUnico ?: null,
        'total'        => $salvos,
        'pulados'      => $pulados,
    ]);

    // ---------- Notificações (sem nota e sem autor: o aluno não vê quem avaliou) ----------
    $this->notificarAvaliados($projetoId, $mudancas, 'avaliação por pares');

    // ---------- Resposta ----------
    $msg = "Avaliações salvas ({$salvos}).";
    if ($pulados > 0) {
        $msg .= " {$pulados} linha(s) ignorada(s) por falta de justificativa em R ou I.";
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok'       => true,
            'salvos'   => $salvos,
            'pulados'  => $pulados,
            'mensagem' => $msg,
        ]);
        exit;
    }

    $this->flash($msg, 'sucesso');
    $this->redirect('/projetos/' . $projetoId . '/avaliacoes/pares');
}

    // ============ AUTOAVALIAÇÃO ============

    public function auto(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $this->criterio->atualizarBloqueios($projetoId);
        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => $c['tipo_avaliacao'] === 'autoavaliacao' && !$c['bloqueado']
        );

        $minhas = [];
        foreach ($criterios as $c) {
            $stmt = Database::getConnection()->prepare("
                SELECT * FROM avaliacoes
                WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'auto'
                LIMIT 1
            ");
            $stmt->execute([(int) $c['id'], (int) $u['id']]);
            $minhas[(int) $c['id']] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $cfg = $this->cfg->garantir($projetoId);

        $this->render('avaliacoes/auto', [
            'projeto'   => $projeto,
            'cfg'       => $cfg,
            'criterios' => array_values($criterios),
            'minhas'    => $minhas,
        ]);
    }

    public function autoSalvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) { $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId); }

        $this->criterio->atualizarBloqueios($projetoId);
        $cfg = $this->cfg->garantir($projetoId);

        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => $c['tipo_avaliacao'] === 'autoavaliacao'
        );

        $salvos = 0;
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            $cid = (int) $c['id'];
            $dados = $_POST['aval'][$cid] ?? [];
            $nota = $this->lerNota((array) $dados, $cfg);

            if ($nota === null) {
                $this->aval->deletar($cid, (int) $u['id'], (int) $u['id'], 'auto');
                continue;
            }

            $this->aval->upsert($cid, (int) $u['id'], (int) $u['id'], 'auto', $nota['conceito'], $nota['valor']);
            $salvos++;
        }

        $this->audit->registrar('avaliacao_auto_salva', 'avaliacoes', $projetoId, null,
            ['aluno_id' => $u['id'], 'total' => $salvos]);

        $this->flash("Autoavaliações salvas ({$salvos}).", 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/auto');
    }

    // ============ AVALIAÇÃO COLETIVA ============

    public function coletiva(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        if (!$souMaster && !$souRep) { http_response_code(403); exit('Apenas representante ou master.'); }

        $this->criterio->atualizarBloqueios($projetoId);
        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => $c['tipo_avaliacao'] === 'coletiva' && !$c['bloqueado']
        );

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, nome FROM grupos WHERE projeto_id = ? ORDER BY nome ASC");
        $stmt->execute([$projetoId]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Avaliações existentes por critério/grupo
        $existentes = [];
        foreach ($criterios as $c) {
            foreach ($grupos as $g) {
                $existentes[(int) $c['id']][(int) $g['id']] =
                    $this->coletiva->porCriterioEGrupo((int) $c['id'], (int) $g['id']);
            }
        }

        $cfg = $this->cfg->garantir($projetoId);

        $this->render('avaliacoes/coletiva', [
            'projeto'    => $projeto,
            'cfg'        => $cfg,
            'criterios'  => array_values($criterios),
            'grupos'     => $grupos,
            'existentes' => $existentes,
        ]);
    }

    public function coletivaSalvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);
        CsrfMiddleware::validate();

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        if (!$souMaster && !$souRep) { http_response_code(403); exit('Sem permissão.'); }

        if ($projeto['encerrado']) { $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId); }

        $this->criterio->atualizarBloqueios($projetoId);
        $cfg = $this->cfg->garantir($projetoId);

        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => $c['tipo_avaliacao'] === 'coletiva'
        );

        // Só grupos deste projeto podem receber nota coletiva
        $stG = Database::getConnection()->prepare("SELECT id FROM grupos WHERE projeto_id = ?");
        $stG->execute([$projetoId]);
        $gruposDoProjeto = array_map('intval', $stG->fetchAll(PDO::FETCH_COLUMN));

        $salvos = 0;
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            $cid = (int) $c['id'];
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $grupoId => $dados) {
                $grupoId = (int) $grupoId;
                if (!in_array($grupoId, $gruposDoProjeto, true)) continue;
                $just = trim($dados['justificativa'] ?? '') ?: null;

                $nota = $this->lerNota((array) $dados, $cfg);
                if ($nota === null) continue;

                $this->coletiva->upsert($cid, $grupoId, (int) $u['id'], $nota['conceito'], $nota['valor'], $just);
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_coletiva_salva', 'avaliacoes_coletivas', $projetoId, null,
            ['avaliador_id' => $u['id'], 'total' => $salvos]);

        // Notifica os membros dos grupos que receberam nota
        $pdo = Database::getConnection();
        $alunosNotificar = [];
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            foreach (($_POST['aval'][(int) $c['id']] ?? []) as $grupoId => $dados) {
                if (!in_array((int) $grupoId, $gruposDoProjeto, true) || $this->lerNota((array) $dados, $cfg) === null) continue;
                $stmt = $pdo->prepare("SELECT usuario_id FROM grupo_alunos WHERE grupo_id = ? AND saiu_em IS NULL");
                $stmt->execute([(int) $grupoId]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                    $alunosNotificar[(int) $uid] = ($alunosNotificar[(int) $uid] ?? 0) + 1;
                }
            }
        }
        $this->notificarAvaliados($projetoId, $alunosNotificar, 'nota coletiva do grupo');

        $this->flash("Avaliações coletivas salvas ({$salvos}).", 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/coletiva');
    }

        // ============ PAINEL DE AVALIAÇÕES DO ALUNO ============

    public function indexAluno(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $this->criterio->atualizarBloqueios($projetoId);

        // Critérios abertos que o aluno pode responder
        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => !$c['bloqueado']
        );

        $pdo = Database::getConnection();

        // Colegas de grupo que seguem ativos na turma (quem o aluno deve avaliar)
        $stA = $pdo->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND ativo = 1");
        $stA->execute([(int) $projeto['turma_id']]);
        $colegasGrupo = array_values(array_intersect(
            $this->colegasDeGrupo((int) $projeto['id'], (int) $u['id']),
            array_map('intval', $stA->fetchAll(PDO::FETCH_COLUMN))
        ));

        // Para cada critério, decidir se o aluno tem pendência
        $pendencias = [];
        foreach ($criterios as $c) {
            $tipo = $c['tipo_avaliacao'];
            $cid  = (int) $c['id'];
            $pend = null;

            if ($tipo === 'pares') {
                // Conta os colegas DE GRUPO (ativos na turma) que ainda não avaliou
                $stmt = $pdo->prepare("
                    SELECT a.aluno_id FROM avaliacoes a
                    WHERE a.criterio_id = ? AND a.avaliador_id = ? AND a.tipo = 'par'
                ");
                $stmt->execute([$cid, (int) $u['id']]);
                $jaAvaliados = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                $faltaPares  = count(array_diff($colegasGrupo, $jaAvaliados));
                $pend = $faltaPares > 0 ? "{$faltaPares} colega(s) faltando" : null;

            } elseif ($tipo === 'autoavaliacao') {
                $stmt = $pdo->prepare("
                    SELECT 1 FROM avaliacoes
                    WHERE criterio_id = ? AND aluno_id = ? AND tipo = 'auto'
                    LIMIT 1
                ");
                $stmt->execute([$cid, (int) $u['id']]);
                if (!$stmt->fetchColumn()) $pend = 'Você ainda não se avaliou';
            }

            $pendencias[$cid] = $pend;
        }

        // Boletim parcial
        $boletim = $this->nota->boletimDoAluno($projetoId, (int) $u['id']);

        $this->render('avaliacoes/index_aluno', [
            'projeto'    => $projeto,
            'criterios'  => array_values($criterios),
            'pendencias' => $pendencias,
            'boletim'    => $boletim,
        ]);
    }

    // ============ BOLETIM PARCIAL ============

    public function parcial(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $alunoId = (int) ($_GET['aluno_id'] ?? $u['id']);

        if (!$this->podeVerBoletimParcial($projetoId, $alunoId)) {
            http_response_code(403);
            exit('Sem permissão para ver este boletim.');
        }

        $aluno   = (new Usuario())->findById($alunoId);
        if (!$aluno) { $this->flash('Aluno não encontrado.'); $this->redirect('/projetos/' . $projetoId); }

        // Sempre em tempo real (mesmo se encerrado — o oficial é o snapshot)
        $boletim = $this->nota->boletimDoAluno($projetoId, $alunoId);
        $cfg     = $this->cfg->garantir($projetoId);

        // Conta critérios ainda em aberto
        $this->criterio->atualizarBloqueios($projetoId);
        $criterios = $this->criterio->listarPorProjeto($projetoId);
        $abertos   = array_filter($criterios, fn($c) => !$c['bloqueado']);
        $bloqueados = array_filter($criterios, fn($c) => $c['bloqueado']);

        $this->render('avaliacoes/parcial', [
            'projeto'    => $projeto,
            'aluno'      => $aluno,
            'boletim'    => $boletim,
            'cfg'        => $cfg,
            'abertos'    => count($abertos),
            'bloqueados' => count($bloqueados),
            'souEu'      => $alunoId === (int) $u['id'],
        ]);
    }

    private function podeVerBoletimParcial(int $projetoId, int $alvoId): bool
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) return false;
        if ((int) $u['id'] === $alvoId) return true;
        if ($u['tipo'] === 'master')    return true;

        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) return false;

        if ($this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) return true;

        // Diretor segue a regra de visibilidade do projeto
        $cfg = $this->cfg->garantir($projetoId);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 1 FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([$projetoId, (int) $u['id']]);
        $souDiretor = (bool) $stmt->fetchColumn();

        $regra = $souDiretor ? $cfg['visibilidade_diretor'] : $cfg['visibilidade_aluno'];

        if ($regra === 'turma') {
            $stmt = $pdo->prepare("
                SELECT 1 FROM turma_usuarios
                WHERE turma_id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1
            ");
            $stmt->execute([(int) $projeto['turma_id'], $alvoId]);
            return (bool) $stmt->fetchColumn();
        }

        if ($regra === 'grupo') {
            $stmt = $pdo->prepare("
                SELECT 1
                FROM grupo_alunos ga1
                INNER JOIN grupo_alunos ga2 ON ga2.grupo_id = ga1.grupo_id
                INNER JOIN grupos g ON g.id = ga1.grupo_id
                WHERE g.projeto_id = ? AND ga1.usuario_id = ? AND ga2.usuario_id = ?
                  AND ga1.saiu_em IS NULL AND ga2.saiu_em IS NULL
                LIMIT 1
            ");
            $stmt->execute([$projetoId, (int) $u['id'], $alvoId]);
            return (bool) $stmt->fetchColumn();
        }

        return false;
    }

    // ============ HELPERS ============

    /**
     * IDs dos colegas que dividem pelo menos um grupo ativo com o usuário neste projeto.
     * Regra: na avaliação por pares, cada aluno avalia só quem trabalhou com ele.
     */
    private function colegasDeGrupo(int $projetoId, int $usuarioId): array
    {
        $stmt = Database::getConnection()->prepare("
            SELECT DISTINCT outro.usuario_id
            FROM grupo_alunos eu
            INNER JOIN grupos g          ON g.id = eu.grupo_id AND g.projeto_id = ?
            INNER JOIN grupo_alunos outro ON outro.grupo_id = eu.grupo_id AND outro.saiu_em IS NULL
            WHERE eu.usuario_id = ? AND eu.saiu_em IS NULL AND outro.usuario_id != ?
        ");
        $stmt->execute([$projetoId, $usuarioId, $usuarioId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function temDiretorAtivoNoProjeto(int $projetoId, int $usuarioId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 1
            FROM grupo_diretores gd
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

    /**
     * Lê uma célula de nota do formulário. Retorna null se estiver vazia;
     * senão ['conceito', 'valor'] validados. O valor digitado manda (0 a 100,
     * inclusive zero); sem valor, usa o valor padrão do conceito escolhido.
     */
    private function lerNota(array $dados, array $cfg): ?array
    {
        $conceito = strtoupper(trim((string) ($dados['conceito'] ?? '')));
        $bruto    = trim(str_replace(',', '.', (string) ($dados['valor'] ?? '')));
        $temValor = $bruto !== '' && is_numeric($bruto);
        $temConc  = in_array($conceito, ['I', 'R', 'B', 'MB'], true);

        if (!$temValor && !$temConc) return null;

        if ($temValor) {
            $valor = max(0.0, min(100.0, (float) $bruto));
            return ['conceito' => NotaService::conceitoPorValor($valor, $cfg), 'valor' => $valor];
        }
        return ['conceito' => $conceito, 'valor' => NotaService::valorPadraoPorConceito($conceito, $cfg)];
    }

    private function flash(string $msg, string $tipo = 'erro'): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $msg];
    }
}