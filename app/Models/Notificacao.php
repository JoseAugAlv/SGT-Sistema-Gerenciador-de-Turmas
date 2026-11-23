<?php
// app/Models/Notificacao.php
require_once __DIR__ . '/../Core/Model.php';

class Notificacao extends Model
{
    const TIPOS = [
        'alerta'    => 'Alerta',
        'prazo'     => 'Prazo',
        'avaliacao' => 'Avaliação',
        'papel'     => 'Papel',
        'ata'       => 'Ata',
        'material'  => 'Material',
        'lgpd'      => 'LGPD',
        'enquete'   => 'Enquete',
        'caixinha'  => 'Caixinha',
        'mural'     => 'Mural',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'notificacoes';
    }

    public function criar(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensagem,
        ?string $link = null,
        ?int $referenciaId = null,
        bool $direta = true // false quando a mesma notificação vai para vários (não é "menção direta")
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO notificacoes
                (usuario_id, tipo, referencia_id, titulo, mensagem, link)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuarioId, $tipo, $referenciaId, $titulo, $mensagem, $link]);
        $id = (int) $this->conn->lastInsertId();

        // Email conforme o nível escolhido pela pessoa (todas / importantes / menções / nenhuma).
        // Vai para uma fila enviada DEPOIS da resposta ao navegador: avisar a turma inteira não deixa o clique lento.
        self::$filaEmail[] = [$id, $usuarioId, $tipo, $titulo, $mensagem, $link, $direta];
        if (!self::$filaRegistrada) {
            self::$filaRegistrada = true;
            register_shutdown_function([self::class, 'despacharFila']);
        }
        return $id;
    }

    /** Emails das notificações esperando o fim da requisição. */
    private static array $filaEmail = [];
    private static bool $filaRegistrada = false;

    /**
     * Fim da requisição: entrega a resposta ao navegador (fecha a conexão) e só então envia os emails da fila.
     * Também pode ser chamada direto (ex.: scripts de linha de comando).
     */
    public static function despacharFila(): void
    {
        if (!self::$filaEmail) return;
        ignore_user_abort(true);
        @set_time_limit(300);
        if (PHP_SAPI !== 'cli') {
            if (!headers_sent()) {
                $corpo = '';
                while (ob_get_level() > 0) $corpo .= ob_get_clean();
                header('Connection: close');
                header('Content-Length: ' . strlen($corpo));
                echo $corpo;
            }
            if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
            while (ob_get_level() > 0) @ob_end_flush();
            flush();
            if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        }
        $fila = self::$filaEmail;
        self::$filaEmail = [];
        try {
            require_once __DIR__ . '/../Services/EmailService.php';
            $email = new EmailService();
            $conn = Database::getConnection();
            $marca = $conn->prepare("UPDATE notificacoes SET enviada_email = 1 WHERE id = ?");
            foreach ($fila as [$id, $uid, $tipo, $titulo, $mensagem, $link, $direta]) {
                try {
                    if ($email->notificacaoPorEmail($uid, $tipo, $titulo, $mensagem, $link, $direta)) $marca->execute([$id]);
                } catch (\Throwable $e) {
                    error_log('Email da notificação: ' . $e->getMessage()); // email nunca derruba a ação
                }
            }
            // notificação no celular/computador de quem ativou (app instalável)
            require_once __DIR__ . '/../Services/PushService.php';
            $push = new PushService();
            $comAparelho = [];
            foreach ($fila as [$id, $uid, $tipo, $titulo, $mensagem, $link]) {
                if (!isset($comAparelho[$uid])) $comAparelho[$uid] = $push->temInscricao($uid);
                if (!$comAparelho[$uid]) continue;
                try { $push->enviar($uid, $titulo, $mensagem, $link); } catch (\Throwable $e) { error_log('Push da notificação: ' . $e->getMessage()); }
            }
        } catch (\Throwable $e) {
            error_log('Fila de emails: ' . $e->getMessage());
        }
    }

    /**
     * Cria a mesma notificação para vários usuários.
     */
    public function criarParaVarios(array $usuarioIds, string $tipo, string $titulo, string $mensagem, ?string $link = null): int
    {
        $total = 0;
        foreach ($usuarioIds as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $this->criar($uid, $tipo, $titulo, $mensagem, $link, null, false);
                $total++;
            }
        }
        return $total;
    }

    public function listarPorUsuario(int $usuarioId, ?string $filtro = null, int $limit = 50): array
    {
        $sql = "SELECT * FROM notificacoes WHERE usuario_id = ?";
        $params = [$usuarioId];

        if ($filtro === 'nao_lidas') {
            $sql .= " AND lida = 0";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function ultimas(int $usuarioId, int $limit = 10): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM notificacoes
            WHERE usuario_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarLida(int $id, int $usuarioId): bool
    {
        return $this->conn->prepare("
            UPDATE notificacoes SET lida = 1, lida_em = NOW()
            WHERE id = ? AND usuario_id = ?
        ")->execute([$id, $usuarioId]);
    }

    public function marcarTodasLidas(int $usuarioId): int
    {
        $stmt = $this->conn->prepare("
            UPDATE notificacoes SET lida = 1, lida_em = NOW()
            WHERE usuario_id = ? AND lida = 0
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->rowCount();
    }
        /**
     * Cria notificações para critérios que vencem em até $dias dias.
     * Idempotente: não recria se já existir notificação do tipo 'prazo'
     * com mesmo referencia_id para o mesmo usuário.
     */
    public function verificarPrazosProximos(int $usuarioId, int $dias = 3): int
    {
        $pdo = Database::getConnection();

        // Projetos do usuário
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id
            FROM projetos p
            INNER JOIN turma_usuarios tu ON tu.turma_id = p.turma_id
            WHERE tu.usuario_id = ? AND tu.ativo = 1 AND p.encerrado = 0
        ");
        $stmt->execute([$usuarioId]);
        $projetos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($projetos)) return 0;

        $ph = implode(',', array_fill(0, count($projetos), '?'));

        $stmt = $pdo->prepare("
            SELECT c.id, c.nome, c.prazo_avaliacao, c.projeto_id, p.nome AS projeto_nome, c.obrigatorio
            FROM criterios c
            INNER JOIN projetos p ON p.id = c.projeto_id
            WHERE c.projeto_id IN ({$ph})
              AND c.bloqueado = 0
              AND c.prazo_avaliacao IS NOT NULL
              AND c.prazo_avaliacao >= NOW()
              AND c.prazo_avaliacao <= DATE_ADD(NOW(), INTERVAL ? DAY)
        ");
        $params = array_merge($projetos, [$dias]);
        $stmt->execute($params);
        $crits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($crits)) return 0;

        $total = 0;
        foreach ($crits as $c) {
            // Já existe notificação deste critério para o usuário?
            $stmt = $pdo->prepare("
                SELECT 1 FROM notificacoes
                WHERE usuario_id = ? AND tipo = 'prazo' AND referencia_id = ?
                LIMIT 1
            ");
            $stmt->execute([$usuarioId, (int) $c['id']]);
            if ($stmt->fetchColumn()) continue;

            $this->criar(
                $usuarioId,
                'prazo',
                !empty($c['obrigatorio']) ? 'Avaliação obrigatória vencendo' : 'Prazo próximo',
                "O critério '{$c['nome']}' do projeto '{$c['projeto_nome']}' vence em " . date('d/m H:i', strtotime($c['prazo_avaliacao'])) . '.'
                    . (!empty($c['obrigatorio']) ? ' O preenchimento é obrigatório.' : ''),
                '/projetos/' . (int) $c['projeto_id'] . '/avaliacoes',
                (int) $c['id']
            );
            $total++;
        }

        return $total;
    }    
}