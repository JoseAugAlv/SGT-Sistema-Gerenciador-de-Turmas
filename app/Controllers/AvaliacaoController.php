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
     * Notifica cada aluno que recebeu uma avaliação.
     * $porAluno = [aluno_id => ['conceito' => 'B', 'valor' => 75], ...]
     */
    private function notificarAvaliados(int $projetoId, array $porAluno, string $criterioNome): void
    {
        if (empty($porAluno)) return;

        $notif = new Notificacao();

        foreach ($porAluno as $alunoId => $dados) {
            $conceito = $dados['conceito'] ?? '';
            $valor    = isset($dados['valor']) ? number_format((float) $dados['valor'], 2, ',', '.') : '';

            $notif->criar(
                (int) $alunoId,
                'avaliacao',
                'Nova avaliação recebida',
                "Você recebeu uma avaliação no critério '{$criterioNome}': conceito {$conceito} ({$valor}).",
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
        // Aluno comum: vê suas notas
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/minhas');
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

        $this->render('avaliacoes/index_rep', [
            'projeto'          => $projeto,
            'cfg'              => $cfg,
            'critTodos'        => $critTodos,
            'critDiretores'    => $critDiretores,
            'critRepresent'    => $critRepresent,
            'alunos'           => $alunos,
            'minhasAvaliacoes' => $minhasAvaliacoes,
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

        if ($limpar) {
            $this->aval->deletarDoAvaliadorNoProjeto($projetoId, (int) $u['id']);
        }

        $this->criterio->atualizarBloqueios($projetoId);
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
            $aplica = in_array($c['tipo_avaliacao'], ['representante', 'pares', 'autoavaliacao', 'misto'], true)
                   || ($c['tipo_avaliacao'] === 'diretor' && $souMaster);
            if (!$aplica) continue;
            if (!$tipoAval) continue;

            // Pega lista de valores do POST: aval[cid][aluno_id]
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $alunoId => $dados) {
                $alunoId = (int) $alunoId;
                $conceito = $dados['conceito'] ?? '';
                $valor    = (float) str_replace(',', '.', $dados['valor'] ?? '');

                if ($conceito === '' && $valor == 0.0) {
                    // vazio: se estava salvo, remove
                    $this->aval->deletar($cid, $alunoId, (int) $u['id'], $tipoAval);
                    continue;
                }

                if (!in_array($conceito, ['I','R','B','MB'], true)) {
                    $conceito = NotaService::conceitoPorValor($valor, $cfg);
                }
                if ($valor <= 0) {
                    $valor = NotaService::valorPadraoPorConceito($conceito, $cfg);
                }

                $this->aval->upsert(
                    $cid, $alunoId, (int) $u['id'], $tipoAval,
                    $conceito, $valor, null, null
                );
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_rep_salva', 'avaliacoes', $projetoId, null,
            ['avaliador_id' => $u['id'], 'limpar' => $limpar, 'total' => $salvos]);

                // Notifica alunos que receberam avaliação
        $notificados = [];
        foreach ($criterios as $c) {
            $cid = (int) $c['id'];
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $alunoId => $dados) {
                if (($dados['conceito'] ?? '') !== '' || ((float) ($dados['valor'] ?? 0)) > 0) {
                    $notificados[(int) $alunoId] = [
                        'conceito' => $dados['conceito'] ?? '',
                        'valor'    => $dados['valor'] ?? 0,
                    ];
                }
            }
        }
        if (!empty($notificados)) {
            $this->notificarAvaliados($projetoId, $notificados, 'Representante');
        }

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

                $conceito = $dados['conceito'] ?? '';
                $valor    = (float) str_replace(',', '.', $dados['valor'] ?? '');

                if ($conceito === '' && $valor == 0.0) {
                    $this->aval->deletar($cid, $alunoId, (int) $u['id'], $tipoAval);
                    continue;
                }

                if (!in_array($conceito, ['I','R','B','MB'], true)) {
                    $conceito = NotaService::conceitoPorValor($valor, $cfg);
                }
                if ($valor <= 0) {
                    $valor = NotaService::valorPadraoPorConceito($conceito, $cfg);
                }

                $this->aval->upsert($cid, $alunoId, (int) $u['id'], $tipoAval, $conceito, $valor);
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_diretor_salva', 'avaliacoes', $grupoId, null,
            ['avaliador_id' => $u['id'], 'total' => $salvos]);

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

        $pdo = Database::getConnection();
        $turmaId = (int) $projeto['turma_id'];

        // Todos os alunos ativos na turma, exceto o próprio avaliador
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.email
            FROM turma_usuarios tu
            INNER JOIN usuarios u ON u.id = tu.usuario_id
            WHERE tu.turma_id = ? AND tu.ativo = 1 AND u.id != ?
            ORDER BY u.nome ASC
        ");
        $stmt->execute([$turmaId, (int) $u['id']]);
        $alunosDaTurma = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Diretores ativos no projeto (qualquer grupo do projeto)
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

            // Filtra alunos conforme o critério
            $colegas = [];
            foreach ($alunosDaTurma as $a) {
                $aid = (int) $a['id'];

                if ($aplicavel === 'apenas_diretores'       && !in_array($aid, $diretoresIds, true)) continue;
                if ($aplicavel === 'apenas_representantes'  && !in_array($aid, $repsIds, true))     continue;

                $a['is_diretor']       = in_array($aid, $diretoresIds, true);
                $a['is_representante'] = in_array($aid, $repsIds, true);

                $colegas[$aid] = $a;
            }

            // Minhas avaliações anteriores para este critério
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

        $this->criterio->atualizarBloqueios($projetoId);
        $cfg = $this->cfg->garantir($projetoId);

        $criterios = array_filter(
            $this->criterio->listarPorProjeto($projetoId),
            fn($c) => $c['tipo_avaliacao'] === 'pares'
        );

        $pdo     = Database::getConnection();
        $turmaId = (int) $projeto['turma_id'];

        // Lista de alunos ativos da turma (com cache em memória)
        $stmt = $pdo->prepare("
            SELECT usuario_id FROM turma_usuarios
            WHERE turma_id = ? AND ativo = 1
        ");
        $stmt->execute([$turmaId]);
        $alunosDaTurma = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $salvos = 0;
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;

            $cid   = (int) $c['id'];
            $linha = $_POST['aval'][$cid] ?? [];

            foreach ($linha as $alunoId => $dados) {
                $alunoId = (int) $alunoId;

                // Não pode se autoavaliar
                if ($alunoId === (int) $u['id']) continue;

                // Aluno precisa estar ativo na turma
                if (!in_array($alunoId, $alunosDaTurma, true)) continue;

                $conceito = $dados['conceito'] ?? '';
                $valor    = (float) str_replace(',', '.', $dados['valor'] ?? '');
                $just     = trim($dados['justificativa'] ?? '');

                // Célula vazia — apaga se existia
                if ($conceito === '' && $valor == 0.0) {
                    $this->aval->deletar($cid, $alunoId, (int) $u['id'], 'par');
                    continue;
                }

                // Justificativa obrigatória em pares — ignora silenciosamente se faltar
                if ($just === '') continue;

                // Se o usuário só digitou valor sem conceito, calcula
                if (!in_array($conceito, ['I', 'R', 'B', 'MB'], true)) {
                    $conceito = NotaService::conceitoPorValor($valor, $cfg);
                }
                // Se só escolheu conceito, aplica valor padrão da faixa
                if ($valor <= 0) {
                    $valor = NotaService::valorPadraoPorConceito($conceito, $cfg);
                }

                $this->aval->upsert(
                    $cid,
                    $alunoId,
                    (int) $u['id'],
                    'par',
                    $conceito,
                    $valor,
                    $just
                );
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_pares_salva', 'avaliacoes', $projetoId, null, [
            'avaliador_id' => $u['id'],
            'total'        => $salvos,
        ]);

                // Notifica os colegas avaliados
        $notificados = [];
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            $cid = (int) $c['id'];
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $alunoId => $dados) {
                if ((int) $alunoId === (int) $u['id']) continue;
                if (($dados['conceito'] ?? '') !== '' || ((float) ($dados['valor'] ?? 0)) > 0) {
                    $notificados[(int) $alunoId] = [
                        'conceito' => $dados['conceito'] ?? '',
                        'valor'    => $dados['valor'] ?? 0,
                    ];
                }
            }
        }
        if (!empty($notificados)) {
            $this->notificarAvaliados($projetoId, $notificados, 'Avaliação por pares');
        }

        $this->flash("Avaliações salvas ({$salvos}).", 'sucesso');
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
            $conceito = $dados['conceito'] ?? '';
            $valor    = (float) str_replace(',', '.', $dados['valor'] ?? '');

            if ($conceito === '' && $valor == 0.0) {
                $this->aval->deletar($cid, (int) $u['id'], (int) $u['id'], 'auto');
                continue;
            }

            if (!in_array($conceito, ['I','R','B','MB'], true)) {
                $conceito = NotaService::conceitoPorValor($valor, $cfg);
            }
            if ($valor <= 0) {
                $valor = NotaService::valorPadraoPorConceito($conceito, $cfg);
            }

            $this->aval->upsert($cid, (int) $u['id'], (int) $u['id'], 'auto', $conceito, $valor);
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

        $salvos = 0;
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            $cid = (int) $c['id'];
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $grupoId => $dados) {
                $grupoId = (int) $grupoId;
                $conceito = $dados['conceito'] ?? '';
                $valor    = (float) str_replace(',', '.', $dados['valor'] ?? '');
                $just     = trim($dados['justificativa'] ?? '') ?: null;

                if ($conceito === '' && $valor == 0.0) {
                    // remove? Deixa para simplificar — só não salva
                    continue;
                }

                if (!in_array($conceito, ['I','R','B','MB'], true)) {
                    $conceito = NotaService::conceitoPorValor($valor, $cfg);
                }
                if ($valor <= 0) {
                    $valor = NotaService::valorPadraoPorConceito($conceito, $cfg);
                }

                $this->coletiva->upsert($cid, $grupoId, (int) $u['id'], $conceito, $valor, $just);
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_coletiva_salva', 'avaliacoes_coletivas', $projetoId, null,
            ['avaliador_id' => $u['id'], 'total' => $salvos]);

                // Notifica todos os membros dos grupos avaliados
        $pdo = Database::getConnection();
        $alunosNotificar = [];
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            $cid = (int) $c['id'];
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $grupoId => $dados) {
                if (($dados['conceito'] ?? '') === '' && ((float) ($dados['valor'] ?? 0)) <= 0) continue;

                $stmt = $pdo->prepare("
                    SELECT usuario_id FROM grupo_alunos
                    WHERE grupo_id = ? AND saiu_em IS NULL
                ");
                $stmt->execute([(int) $grupoId]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                    $alunosNotificar[(int) $uid] = [
                        'conceito' => $dados['conceito'] ?? '',
                        'valor'    => $dados['valor'] ?? 0,
                    ];
                }
            }
        }
        if (!empty($alunosNotificar)) {
            $this->notificarAvaliados($projetoId, $alunosNotificar, 'Nota coletiva');
        }

        $this->flash("Avaliações coletivas salvas ({$salvos}).", 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/coletiva');
    }

    // ============ HELPERS ============

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

    private function flash(string $msg, string $tipo = 'erro'): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $msg];
    }
}