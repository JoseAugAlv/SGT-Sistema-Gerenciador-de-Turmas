<?php
// app/Controllers/MasterController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class MasterController
{
    private Auditoria $audit;
    private Usuario   $usuario;

    public function __construct()
    {
        $this->audit   = new Auditoria();
        $this->usuario = new Usuario();
    }

    // ============ PAINEL ============

    public function index()
    {
        $this->exigirMaster();
        $pdo = Database::getConnection();

        $stats = [
            'turmas'          => (int) $pdo->query("SELECT COUNT(*) FROM turmas")->fetchColumn(),
            'turmas_ativas'   => (int) $pdo->query("SELECT COUNT(*) FROM turmas WHERE bloqueada = 0")->fetchColumn(),
            'usuarios'        => (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
            'alunos_ativos'   => (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno' AND ativo = 1")->fetchColumn(),
            'projetos'        => (int) $pdo->query("SELECT COUNT(*) FROM projetos")->fetchColumn(),
            'projetos_abertos'=> (int) $pdo->query("SELECT COUNT(*) FROM projetos WHERE encerrado = 0")->fetchColumn(),
            'grupos'          => (int) $pdo->query("SELECT COUNT(*) FROM grupos")->fetchColumn(),
            'crit_arquivados' => (int) $pdo->query("SELECT COUNT(*) FROM avaliacoes_arquivadas WHERE restaurado = 0")->fetchColumn(),
            'lgpd_pendentes'  => (int) $pdo->query("SELECT COUNT(*) FROM lgpd_solicitacoes WHERE status = 'pendente'")->fetchColumn(),
        ];

        $tituloPagina = 'Painel Master — ' . App::getName();
        require __DIR__ . '/../Views/master/index.php';
    }

    // ============ AUDITORIA ============

    public function auditoria()
    {
        $this->exigirMaster();
        $pdo = Database::getConnection();

        $filtros = [
            'usuario_id' => (int) ($_GET['usuario_id'] ?? 0),
            'acao'       => trim($_GET['acao'] ?? ''),
            'tabela'     => trim($_GET['tabela'] ?? ''),
            'de'         => trim($_GET['de'] ?? ''),
            'ate'        => trim($_GET['ate'] ?? ''),
        ];

        $sql    = "
            SELECT a.*, u.nome AS usuario_nome
            FROM auditoria_log a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE 1=1
        ";
        $params = [];

        if ($filtros['usuario_id'] > 0) {
            $sql .= " AND a.usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        if ($filtros['acao'] !== '') {
            $sql .= " AND a.acao LIKE ?";
            $params[] = '%' . $filtros['acao'] . '%';
        }
        if ($filtros['tabela'] !== '') {
            $sql .= " AND a.tabela_afetada = ?";
            $params[] = $filtros['tabela'];
        }
        if ($filtros['de'] !== '') {
            $sql .= " AND a.created_at >= ?";
            $params[] = $filtros['de'] . ' 00:00:00';
        }
        if ($filtros['ate'] !== '') {
            $sql .= " AND a.created_at <= ?";
            $params[] = $filtros['ate'] . ' 23:59:59';
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Listas para os filtros
        $usuarios  = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $tabelas   = $pdo->query("SELECT DISTINCT tabela_afetada FROM auditoria_log WHERE tabela_afetada IS NOT NULL ORDER BY tabela_afetada ASC")->fetchAll(PDO::FETCH_COLUMN);

        $tituloPagina = 'Auditoria — ' . App::getName();
        require __DIR__ . '/../Views/master/auditoria.php';
    }

    public function auditoriaDetalhe(int $id)
    {
        $this->exigirMaster();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT a.*, u.nome AS usuario_nome, u.email AS usuario_email
            FROM auditoria_log a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE a.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) { $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Registro não encontrado.']; $this->redirect('/master/auditoria'); }

        $tituloPagina = 'Detalhe da Auditoria #' . $id . ' — ' . App::getName();
        require __DIR__ . '/../Views/master/auditoria_detalhe.php';
    }

    // ============ CRITÉRIOS ARQUIVADOS ============

    public function criteriosArquivados()
    {
        $this->exigirMaster();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT aa.*,
                   u.nome AS arquivado_por_nome,
                   p.nome AS projeto_nome,
                   t.nome AS turma_nome
            FROM avaliacoes_arquivadas aa
            LEFT JOIN usuarios u ON u.id = aa.arquivado_por
            INNER JOIN projetos p ON p.id = aa.projeto_id
            INNER JOIN turmas t ON t.id = p.turma_id
            WHERE aa.restaurado = 0
            ORDER BY aa.arquivado_em DESC
        ");
        $arquivados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tituloPagina = 'Critérios Arquivados — ' . App::getName();
        require __DIR__ . '/../Views/master/criterios_arquivados.php';
    }

    public function restaurarCriterio(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM avaliacoes_arquivadas WHERE id = ? AND restaurado = 0 LIMIT 1");
        $stmt->execute([$id]);
        $arq = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$arq) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Arquivamento não encontrado ou já restaurado.'];
            $this->redirect('/master/criterios-arquivados');
        }

        if (!empty($arq['expira_em']) && strtotime($arq['expira_em']) < time()) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Janela de restauração expirada.'];
            $this->redirect('/master/criterios-arquivados');
        }

        $dados = json_decode($arq['dados_json'], true) ?: [];
        $crit  = $dados['criterio'] ?? null;
        if (!$crit) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'JSON inválido.'];
            $this->redirect('/master/criterios-arquivados');
        }

        try {
            $pdo->beginTransaction();

            // Reinsere o critério
            $stmt = $pdo->prepare("
                INSERT INTO criterios
                    (projeto_id, etapa_id, nome, descricao, peso, tipo_avaliacao,
                     aplicavel_a, prazo_avaliacao, bloqueado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                (int) $crit['projeto_id'],
                !empty($crit['etapa_id']) ? (int) $crit['etapa_id'] : null,
                $crit['nome'],
                $crit['descricao'] ?? null,
                (float) $crit['peso'],
                $crit['tipo_avaliacao'],
                $crit['aplicavel_a'] ?? 'todos',
                $crit['prazo_avaliacao'] ?? null,
            ]);
            $novoCriterioId = (int) $pdo->lastInsertId();

            // Reinsere avaliações individuais
            foreach (($dados['individuais'] ?? []) as $a) {
                $stmt = $pdo->prepare("
                    INSERT INTO avaliacoes
                        (criterio_id, aluno_id, avaliador_id, tipo, conceito, valor_numerico, justificativa)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $novoCriterioId,
                    (int) $a['aluno_id'],
                    !empty($a['avaliador_id']) ? (int) $a['avaliador_id'] : null,
                    $a['tipo'],
                    $a['conceito'],
                    (float) $a['valor_numerico'],
                    $a['justificativa'] ?? null,
                ]);
            }

            // Reinsere coletivas
            foreach (($dados['coletivas'] ?? []) as $a) {
                $stmt = $pdo->prepare("
                    INSERT INTO avaliacoes_coletivas
                        (criterio_id, grupo_id, avaliador_id, conceito, valor_numerico, justificativa)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $novoCriterioId,
                    (int) $a['grupo_id'],
                    (int) $a['avaliador_id'],
                    $a['conceito'],
                    (float) $a['valor_numerico'],
                    $a['justificativa'] ?? null,
                ]);
            }

            // Marca como restaurado
            $pdo->prepare("
                UPDATE avaliacoes_arquivadas
                SET restaurado = 1, restaurado_em = NOW(), restaurado_por = ?
                WHERE id = ?
            ")->execute([(int) $_SESSION['usuario']['id'], $id]);

            $pdo->commit();

            $this->audit->registrar('criterio_restaurado', 'avaliacoes_arquivadas', $id,
                null, ['novo_criterio_id' => $novoCriterioId]);

            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Critério restaurado com sucesso.'];

        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Falha ao restaurar: ' . $e->getMessage()];
        }

        $this->redirect('/master/criterios-arquivados');
    }

    // ============ LGPD — SOLICITAÇÕES ============

    public function lgpdSolicitacoes()
    {
        $this->exigirMaster();
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT s.*,
                   u.nome AS usuario_nome, u.email AS usuario_email,
                   p.nome AS processado_por_nome
            FROM lgpd_solicitacoes s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN usuarios p ON p.id = s.processado_por
            ORDER BY s.status = 'pendente' DESC, s.created_at DESC
            LIMIT 200
        ");
        $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tituloPagina = 'Solicitações LGPD — ' . App::getName();
        require __DIR__ . '/../Views/master/lgpd_solicitacoes.php';
    }

    public function aprovarLgpd(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM lgpd_solicitacoes WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $sol = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sol || $sol['status'] !== 'pendente') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Solicitação não encontrada ou já processada.'];
            $this->redirect('/master/lgpd');
        }

        if ($sol['tipo'] === 'exclusao') {
            $this->executarExclusao((int) $sol['usuario_id'], $id);
        } else {
            $pdo->prepare("
                UPDATE lgpd_solicitacoes
                SET status = 'concluida', processado_por = ?, processado_em = NOW()
                WHERE id = ?
            ")->execute([(int) $_SESSION['usuario']['id'], $id]);
        }

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Solicitação processada.'];
        $this->redirect('/master/lgpd');
    }

    public function negarLgpd(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $motivo = trim($_POST['motivo'] ?? '') ?: 'Não especificado';

        $pdo = Database::getConnection();
        $pdo->prepare("
            UPDATE lgpd_solicitacoes
            SET status = 'negada', motivo_negacao = ?, processado_por = ?, processado_em = NOW()
            WHERE id = ? AND status = 'pendente'
        ")->execute([$motivo, (int) $_SESSION['usuario']['id'], $id]);

        $this->audit->registrar('lgpd_negada', 'lgpd_solicitacoes', $id, null, ['motivo' => $motivo]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Solicitação negada.'];
        $this->redirect('/master/lgpd');
    }

    /**
     * Exclusão LGPD: anonimiza o usuário, mantém logs.
     */
    private function executarExclusao(int $usuarioId, int $solicitacaoId): void
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $original = $this->usuario->findById($usuarioId);

            // Anonimiza dados pessoais
            $pdo->prepare("
                UPDATE usuarios
                SET nome = 'Usuário Excluído (LGPD)',
                    email = CONCAT('excluido_', id, '@removido.lgpd'),
                    telefone = NULL,
                    data_nascimento = NULL,
                    ativo = 0
                WHERE id = ?
            ")->execute([$usuarioId]);

            // Desativa vínculos
            $pdo->prepare("UPDATE turma_usuarios SET ativo = 0 WHERE usuario_id = ?")->execute([$usuarioId]);
            $pdo->prepare("UPDATE grupo_diretores SET ativo = 0, removido_em = NOW() WHERE usuario_id = ?")->execute([$usuarioId]);

            // Registra auditoria antes de fechar
            $this->audit->registrar('lgpd_exclusao_executada', 'usuarios', $usuarioId,
                ['nome' => $original['nome'] ?? null, 'email' => $original['email'] ?? null],
                ['anonimizado' => true]);

            $pdo->prepare("
                UPDATE lgpd_solicitacoes
                SET status = 'concluida', processado_por = ?, processado_em = NOW()
                WHERE id = ?
            ")->execute([(int) $_SESSION['usuario']['id'], $solicitacaoId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ============ BACKUP ============

    public function backup()
    {
        $this->exigirMaster();

        $dir = __DIR__ . '/../../backups';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);

        $arquivos = glob($dir . '/*.sql') ?: [];
        usort($arquivos, fn($a, $b) => filemtime($b) - filemtime($a));

        $backups = [];
        foreach ($arquivos as $f) {
            $backups[] = [
                'nome'    => basename($f),
                'tamanho' => filesize($f),
                'data'    => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }

        $tituloPagina = 'Backup — ' . App::getName();
        require __DIR__ . '/../Views/master/backup.php';
    }

    public function gerarBackup()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $dir = __DIR__ . '/../../backups';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);

        $nome    = 'backup_' . date('Ymd_His') . '.sql';
        $arquivo = $dir . '/' . $nome;

        $host = App::get('DB_HOST', '127.0.0.1');
        $port = App::get('DB_PORT', '3306');
        $user = App::get('DB_USER', 'root');
        $pass = App::get('DB_PASS', '');
        $db   = App::get('DB_NAME', 'sgt');

        // Caminho do mysqldump (XAMPP padrão)
        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (!file_exists($mysqldump)) {
            $mysqldump = 'mysqldump'; // fallback para o PATH
        }

        $cmd = sprintf(
            '%s --host=%s --port=%s --user=%s %s --routines --triggers %s > %s 2>&1',
            escapeshellarg($mysqldump),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $pass !== '' ? '--password=' . escapeshellarg($pass) : '',
            escapeshellarg($db),
            escapeshellarg($arquivo)
        );

        exec($cmd, $out, $code);

        if ($code !== 0 || !file_exists($arquivo) || filesize($arquivo) < 100) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Falha ao gerar backup. Verifique o caminho do mysqldump no controller.'];
        } else {
            $this->audit->registrar('backup_gerado', 'sistema', null, null, ['arquivo' => $nome]);
            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => "Backup gerado: {$nome}"];
        }

        $this->redirect('/master/backup');
    }

    public function baixarBackup(string $nome)
    {
        $this->exigirMaster();

        // Sanitiza o nome
        $nome = basename($nome);
        if (!preg_match('/^backup_\d+_\d+\.sql$/', $nome)) {
            http_response_code(400); exit('Nome inválido.');
        }

        $arquivo = __DIR__ . '/../../backups/' . $nome;
        if (!file_exists($arquivo)) { http_response_code(404); exit('Não encontrado.'); }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('Content-Length: ' . filesize($arquivo));
        readfile($arquivo);
        exit;
    }

    public function excluirBackup(string $nome)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $nome = basename($nome);
        if (!preg_match('/^backup_\d+_\d+\.sql$/', $nome)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Nome inválido.'];
            $this->redirect('/master/backup');
        }

        $arquivo = __DIR__ . '/../../backups/' . $nome;
        if (file_exists($arquivo)) {
            unlink($arquivo);
            $this->audit->registrar('backup_excluido', 'sistema', null, null, ['arquivo' => $nome]);
        }

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Backup excluído.'];
        $this->redirect('/master/backup');
    }

    // ============ CONFIGURAÇÕES ============

    public function configuracoes()
    {
        $this->exigirMaster();

        $info = [
            'php'       => PHP_VERSION,
            'mysql'     => Database::getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION),
            'app_env'   => App::get('APP_ENV', 'production'),
            'app_url'   => App::getUrl(),
            'base_path' => App::getBasePath(),
        ];

        $tituloPagina = 'Configurações — ' . App::getName();
        require __DIR__ . '/../Views/master/configuracoes.php';
    }

    // ============ HELPER ============

    private function exigirMaster(): void
    {
        if (empty($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'master') {
            http_response_code(403);
            exit('Acesso restrito ao master.');
        }
    }

    private function redirect(string $path): void
    {
        header('Location: ' . App::getBasePath() . $path);
        exit;
    }
}