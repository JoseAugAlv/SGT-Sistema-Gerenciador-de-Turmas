<?php
// app/Controllers/LgpdController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class LgpdController
{
    private Usuario  $usuario;
    private Auditoria $audit;

    public function __construct()
    {
        $this->usuario = new Usuario();
        $this->audit   = new Auditoria();
    }

    // ============ PÁGINA PÚBLICA ============

    public function index()
    {
        $tituloPagina = 'Política de Privacidade (LGPD) — ' . App::getName();
        require __DIR__ . '/../Views/lgpd/index.php';
    }

    // ============ ÁREA DO USUÁRIO ============

    public function meusDireitos()
    {
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario']['id'];
        $pdo       = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT * FROM lgpd_solicitacoes
            WHERE usuario_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$usuarioId]);
        $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tituloPagina = 'Meus Direitos (LGPD) — ' . App::getName();
        require __DIR__ . '/../Views/lgpd/meus_direitos.php';
    }

    public function exportar()
    {
        CsrfMiddleware::validate();
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario']['id'];
        $pdo       = Database::getConnection();

        // Coleta tudo relacionado ao usuário
        $dados = [
            'gerado_em'    => date('Y-m-d H:i:s'),
            'perfil'       => $this->usuario->findById($usuarioId),
        ];
        unset($dados['perfil']['senha']); // nunca exporta hash

        $consultas = [
            'turmas' => "
                SELECT t.*, tu.papel, tu.ativo AS vinculo_ativo, tu.entrou_em
                FROM turma_usuarios tu
                INNER JOIN turmas t ON t.id = tu.turma_id
                WHERE tu.usuario_id = ?
            ",
            'grupos' => "
                SELECT g.*, ga.entrou_em, ga.saiu_em
                FROM grupo_alunos ga
                INNER JOIN grupos g ON g.id = ga.grupo_id
                WHERE ga.usuario_id = ?
            ",
            'diretorias' => "
                SELECT gd.*, g.nome AS grupo_nome, p.nome AS projeto_nome
                FROM grupo_diretores gd
                INNER JOIN grupos g ON g.id = gd.grupo_id
                INNER JOIN projetos p ON p.id = g.projeto_id
                WHERE gd.usuario_id = ?
            ",
            'avaliacoes_recebidas' => "
                SELECT a.*, c.nome AS criterio_nome
                FROM avaliacoes a
                INNER JOIN criterios c ON c.id = a.criterio_id
                WHERE a.aluno_id = ?
            ",
            'avaliacoes_feitas' => "
                SELECT a.*, c.nome AS criterio_nome
                FROM avaliacoes a
                INNER JOIN criterios c ON c.id = a.criterio_id
                WHERE a.avaliador_id = ?
            ",
            'atas_participou' => "
                SELECT a.titulo, a.data_ata, ap.presente, ap.justificativa
                FROM ata_participantes ap
                INNER JOIN atas a ON a.id = ap.ata_id
                WHERE ap.aluno_id = ?
            ",
            'atividades' => "
                SELECT aa.nome, aa.descricao, pa.tipo_participacao
                FROM participantes_atividade pa
                INNER JOIN atividades_ata aa ON aa.id = pa.atividade_id
                WHERE pa.usuario_id = ?
            ",
            'movimentacoes_materiais' => "
                SELECT mv.*, m.nome AS material_nome
                FROM movimentacoes_materiais mv
                INNER JOIN materiais m ON m.id = mv.material_id
                WHERE mv.usuario_id = ?
            ",
            'notificacoes' => "
                SELECT tipo, titulo, mensagem, lida, created_at
                FROM notificacoes
                WHERE usuario_id = ?
                ORDER BY created_at DESC
            ",
            'alertas_recebidos' => "
                SELECT al.titulo, al.mensagem, al.created_at
                FROM alertas al
                WHERE al.turma_id IN (
                    SELECT turma_id FROM turma_usuarios WHERE usuario_id = ?
                )
                ORDER BY al.created_at DESC
            ",
        ];

        foreach ($consultas as $chave => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuarioId]);
            $dados[$chave] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Registra solicitação
        $stmt = $pdo->prepare("
            INSERT INTO lgpd_solicitacoes (usuario_id, tipo, status, processado_em)
            VALUES (?, 'exportacao', 'concluida', NOW())
        ");
        $stmt->execute([$usuarioId]);
        $solicitacaoId = (int) $pdo->lastInsertId();

        // Gera arquivo JSON
        $dir = __DIR__ . '/../../public/uploads/lgpd';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);

        $nomeArquivo = 'dados_' . $usuarioId . '_' . date('Ymd_His') . '.json';
        $caminho     = $dir . '/' . $nomeArquivo;

        file_put_contents(
            $caminho,
            json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Atualiza arquivo_gerado
        $pdo->prepare("UPDATE lgpd_solicitacoes SET arquivo_gerado = ? WHERE id = ?")
            ->execute([$nomeArquivo, $solicitacaoId]);

        $this->audit->registrar('lgpd_exportacao', 'lgpd_solicitacoes', $solicitacaoId, null,
            ['arquivo' => $nomeArquivo]);

        $_SESSION['flash'] = [
            'tipo' => 'sucesso',
            'mensagem' => 'Dados exportados. Use o link abaixo para baixar.',
        ];
        header('Location: ' . App::getBasePath() . '/lgpd/meus-direitos');
        exit;
    }

    public function baixarExportacao(int $id)
    {
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario']['id'];
        $pdo       = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT * FROM lgpd_solicitacoes
            WHERE id = ? AND usuario_id = ? AND tipo = 'exportacao'
            LIMIT 1
        ");
        $stmt->execute([$id, $usuarioId]);
        $sol = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sol || empty($sol['arquivo_gerado'])) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $caminho = __DIR__ . '/../../public/uploads/lgpd/' . $sol['arquivo_gerado'];
        if (!file_exists($caminho)) {
            http_response_code(404);
            exit('Arquivo não encontrado no disco.');
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $sol['arquivo_gerado'] . '"');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }

    public function solicitarExclusao()
    {
        CsrfMiddleware::validate();
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $usuarioId = (int) $_SESSION['usuario']['id'];
        $pdo       = Database::getConnection();

        // Já existe pedido pendente?
        $stmt = $pdo->prepare("
            SELECT 1 FROM lgpd_solicitacoes
            WHERE usuario_id = ? AND tipo = 'exclusao' AND status IN ('pendente', 'processando')
            LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        if ($stmt->fetchColumn()) {
            $_SESSION['flash'] = ['tipo' => 'aviso', 'mensagem' => 'Você já possui uma solicitação de exclusão em análise.'];
            header('Location: ' . App::getBasePath() . '/lgpd/meus-direitos');
            exit;
        }

        $motivo = trim($_POST['motivo'] ?? '') ?: null;

        $stmt = $pdo->prepare("
            INSERT INTO lgpd_solicitacoes (usuario_id, tipo, status, motivo_negacao)
            VALUES (?, 'exclusao', 'pendente', ?)
        ");
        $stmt->execute([$usuarioId, $motivo]);

        $this->audit->registrar('lgpd_solicitacao_exclusao', 'lgpd_solicitacoes',
            (int) $pdo->lastInsertId(), null, ['motivo' => $motivo]);

        $_SESSION['flash'] = [
            'tipo' => 'sucesso',
            'mensagem' => 'Solicitação de exclusão registrada. O administrador irá analisar.',
        ];
        header('Location: ' . App::getBasePath() . '/lgpd/meus-direitos');
        exit;
    }
}