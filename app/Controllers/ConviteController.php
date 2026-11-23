<?php
// app/Controllers/ConviteController.php
// Convidar alunos só pelo email: a pessoa já entra na turma e, pelo link do email,
// completa o cadastro (nome, telefone e senha) ou entra com o Google.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Core/Mail.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Convite.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/PreferenciasUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/PasswordHelper.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Services/GoogleAuthService.php';
require_once __DIR__ . '/AuthController.php';

class ConviteController
{
    const MAX_POR_ENVIO = 100;

    private Usuario      $usuario;
    private TurmaUsuario $tu;
    private Convite      $convite;

    public function __construct()
    {
        $this->usuario = new Usuario();
        $this->tu      = new TurmaUsuario();
        $this->convite = new Convite();
    }

    // ============ REPRESENTANTE: CONVIDAR ============

    public function form()
    {
        $turmas = $this->turmasQuePosso();
        if (!$turmas) { $this->flash('Você não representa nenhuma turma.'); $this->redirect('/alunos'); }

        $this->render('alunos/convidar', [
            'turmas'     => $turmas,
            'turmaSel'   => (int) ($_GET['turma_id'] ?? $turmas[0]['id']),
            'pendentes'  => $this->convite->pendentes(array_column($turmas, 'id')),
            'isMaster'   => $_SESSION['usuario']['tipo'] === 'master',
        ]);
    }

    public function enviar()
    {
        CsrfMiddleware::validate();
        $u = $_SESSION['usuario'];
        $turmaId = (int) ($_POST['turma_id'] ?? 0);
        $turma = $this->turmaPermitida($turmaId);

        $emails = self::extrairEmails((string) ($_POST['emails'] ?? ''));
        if (!$emails['validos'] && !$emails['invalidos']) {
            $this->flash('Informe pelo menos um email.'); $this->redirect('/alunos/convidar?turma_id=' . $turmaId);
        }
        if (count($emails['validos']) > self::MAX_POR_ENVIO) {
            $this->flash('Envie no máximo ' . self::MAX_POR_ENVIO . ' emails por vez.'); $this->redirect('/alunos/convidar?turma_id=' . $turmaId);
        }

        $r = ['convidados' => [], 'adicionados' => [], 'ja_estavam' => [], 'invalidos' => $emails['invalidos']];
        foreach ($emails['validos'] as $email) {
            $user = $this->usuario->findByEmail($email);

            if ($user && $this->tu->estaAtivo($turmaId, (int) $user['id'])) { $r['ja_estavam'][] = $email; continue; }

            if (!$user) {
                // Conta "reservada": completa o cadastro pelo link e só então entra na turma
                $id = $this->usuario->criar([
                    'nome'              => strstr($email, '@', true),
                    'email'             => $email,
                    'senha'             => PasswordHelper::hash(bin2hex(random_bytes(24))),
                    'tipo'              => 'aluno',
                    'email_confirmado'  => 0,
                    'primeiro_login'    => 0,
                    'cadastro_pendente' => 1,
                    'ativo'             => 1,
                    'lgpd_aceito'       => 0,
                ]);
                (new PreferenciasUsuario())->criarPadrao($id);
                $user = $this->usuario->findById($id);
            }

            // Ninguém entra na turma agora: só quando aceitar o convite pelo link
            $token = $this->convite->criar($turmaId, (int) $user['id'], $email, (int) $u['id']);
            if ($user['cadastro_pendente']) {
                $this->enviarEmailConvite($email, $turma, $token, $u['nome']);
            } else {
                // Já tem conta: recebe a notificação (no app e por email) com o link para aceitar
                (new Notificacao())->criar((int) $user['id'], 'convite', 'Convite para ' . (self::rotulo($turma) === 'grêmio' ? 'um grêmio' : 'uma turma'),
                    "{$u['nome']} convidou você para o " . self::rotulo($turma) . " {$turma['nome']}. Toque para aceitar.", '/convite/' . $token);
            }
            $r['convidados'][] = $email;
        }

        (new Auditoria())->registrar('alunos_convidados', 'turmas', $turmaId, null, [
            'convidados' => count($r['convidados']), 'adicionados' => count($r['adicionados']), 'por' => $u['id'],
        ]);

        $partes = [];
        if ($r['convidados'])  $partes[] = count($r['convidados']) . ' convite(s) enviado(s)';
        if ($r['ja_estavam'])  $partes[] = count($r['ja_estavam']) . ' já estava(m) na turma';
        if ($r['invalidos'])   $partes[] = 'ignorado(s) por estar(em) inválido(s): ' . implode(', ', array_slice($r['invalidos'], 0, 5));
        $this->flash(implode(' · ', $partes) . '.', $r['convidados'] ? 'sucesso' : 'aviso');
        $this->redirect('/alunos/convidar?turma_id=' . $turmaId);
    }

    public function reenviar(int $id)
    {
        CsrfMiddleware::validate();
        $c = $this->convite->porId($id);
        if (!$c || $c['status'] !== 'pendente') { $this->flash('Convite não encontrado.'); $this->redirect('/alunos/convidar'); }
        $turma = $this->turmaPermitida((int) $c['turma_id']);
        $token = $this->convite->criar((int) $c['turma_id'], (int) $c['usuario_id'], $c['email'], (int) $_SESSION['usuario']['id']);
        $dono = $this->usuario->findById((int) $c['usuario_id']);
        if ($dono && !$dono['cadastro_pendente']) {
            (new Notificacao())->criar((int) $dono['id'], 'convite', 'Convite para ' . (self::rotulo($turma) === 'grêmio' ? 'um grêmio' : 'uma turma'),
                "{$_SESSION['usuario']['nome']} convidou você para o " . self::rotulo($turma) . " {$turma['nome']}. Toque para aceitar.", '/convite/' . $token);
        } else {
            $this->enviarEmailConvite($c['email'], $turma, $token, $_SESSION['usuario']['nome']);
        }
        $this->flash("Convite reenviado para {$c['email']}.", 'sucesso');
        $this->redirect('/alunos/convidar?turma_id=' . (int) $c['turma_id']);
    }

    /** Cancela: tira da turma quem ainda não completou o cadastro. */
    public function cancelar(int $id)
    {
        CsrfMiddleware::validate();
        $c = $this->convite->porId($id);
        if (!$c || $c['status'] !== 'pendente') { $this->flash('Convite não encontrado.'); $this->redirect('/alunos/convidar'); }
        $this->turmaPermitida((int) $c['turma_id']);
        $this->convite->cancelar($id);
        $user = $this->usuario->findById((int) $c['usuario_id']);
        if ($user && $user['cadastro_pendente']) {
            Database::getConnection()->prepare("UPDATE turma_usuarios SET ativo = 0, saiu_em = NOW() WHERE turma_id = ? AND usuario_id = ?")
                ->execute([(int) $c['turma_id'], (int) $c['usuario_id']]);
        }
        $this->flash("Convite de {$c['email']} cancelado.", 'sucesso');
        $this->redirect('/alunos/convidar?turma_id=' . (int) $c['turma_id']);
    }

    // ============ ALUNO: ABRIR O LINK DO CONVITE ============

    public function aceitarForm(string $token)
    {
        $c = $this->convite->validoPorToken($token);
        if (!$c) { $this->flash('Convite inválido ou expirado. Peça um novo ao representante.'); $this->redirect('/login'); }
        if (!$c['cadastro_pendente']) {
            // Já tem conta: confirma com um botão (abrir o link sozinho não aceita; leitores de email abrem links)
            $this->render('auth/convite_entrar', ['convite' => $c, 'token' => $token]);
            return;
        }
        $this->render('auth/convite', [
            'convite' => $c, 'token' => $token,
            'antigo'  => $_SESSION['convite_form'] ?? [],
            'googleOk' => GoogleAuthService::configurado(),
        ]);
        unset($_SESSION['convite_form']);
    }

    public function aceitar(string $token)
    {
        CsrfMiddleware::validate();
        $c = $this->convite->validoPorToken($token);
        if (!$c) { $this->flash('Convite inválido ou expirado.'); $this->redirect('/login'); }
        if (!$c['cadastro_pendente']) {
            // Já tem conta: confirmou no botão "Entrar na turma"
            $this->convite->aceitar((int) $c['id']);
            SecurityHelper::logAuditoria('convite_aceito', (int) $c['usuario_id'], "Turma {$c['turma_nome']}", 'info');
            $logadoCerto = (int) ($_SESSION['usuario']['id'] ?? 0) === (int) $c['usuario_id'];
            $this->flash("Pronto! Você entrou n" . (self::rotulo($c) === 'grêmio' ? 'o grêmio' : 'a turma') . " {$c['turma_nome']}." . ($logadoCerto ? '' : ' Entre com sua conta para ver.'), 'sucesso');
            $this->redirect($logadoCerto ? '/turmas/' . (int) $c['turma_id'] : '/login');
        }

        [$nome, $telefone, $erros] = AuthController::lerNomeTelefone($_POST);
        $senha = (string) ($_POST['senha'] ?? '');
        if ($senha !== (string) ($_POST['senha_confirmacao'] ?? '')) $erros[] = 'As senhas não conferem.';
        $forca = SecurityHelper::validarForcaSenha($senha);
        if (!$forca['valida']) $erros = array_merge($erros, $forca['erros']);
        if (empty($_POST['lgpd'])) $erros[] = 'Você precisa aceitar a Política de Privacidade (LGPD).';
        if ($erros) {
            $_SESSION['convite_form'] = array_diff_key($_POST, ['senha' => 1, 'senha_confirmacao' => 1]);
            $this->flash(implode(' | ', $erros));
            $this->redirect('/convite/' . $token);
        }

        $uid = (int) $c['usuario_id'];
        $this->usuario->completarCadastro($uid, $nome, $telefone, PasswordHelper::hash($senha)); // o link do email confirma o email
        (new PreferenciasUsuario())->definirNivel($uid, (string) ($_POST['nivel_email'] ?? 'importantes'));
        $this->convite->aceitar((int) $c['id']);
        SecurityHelper::logAuditoria('convite_aceito', $uid, "Turma {$c['turma_nome']}", 'info');

        session_regenerate_id(true);
        (new AuthController())->montarSessao($this->usuario->findById($uid));
        $this->flash("Cadastro concluído! Bem-vindo " . (self::rotulo($c) === 'grêmio' ? 'ao grêmio' : 'à turma') . " {$c['turma_nome']}.", 'sucesso');
        $this->redirect('/turmas/' . (int) $c['turma_id']);
    }

    /** "Continuar com o Google" a partir do convite. */
    public function google(string $token)
    {
        if (!$this->convite->validoPorToken($token)) { $this->flash('Convite inválido ou expirado.'); $this->redirect('/login'); }
        $_SESSION['convite_token'] = $token;
        $this->redirect('/auth/google');
    }

    // ============ HELPERS ============

    /** Separa os emails colados (linha, vírgula, ponto e vírgula ou espaço). */
    public static function extrairEmails(string $texto): array
    {
        $validos = []; $invalidos = [];
        foreach (preg_split('/[\s,;]+/', strtolower($texto), -1, PREG_SPLIT_NO_EMPTY) as $e) {
            $e = trim($e, " <>\"'");
            if ($e === '') continue;
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) $validos[$e] = $e; else $invalidos[$e] = $e;
        }
        return ['validos' => array_values($validos), 'invalidos' => array_values($invalidos)];
    }

    /** "turma" ou "grêmio", para os textos. */
    private static function rotulo(array $turma): string
    {
        return ($turma['tipo'] ?? $turma['turma_tipo'] ?? 'turma') === 'gremio' ? 'grêmio' : 'turma';
    }

    private function enviarEmailConvite(string $email, array $turma, string $token, string $quem): void
    {
        $app  = App::getName();
        $turmaNome = $turma['nome'];
        $rot  = self::rotulo($turma);
        $art  = $rot === 'grêmio' ? 'o' : 'a';
        $link = rtrim(App::getUrl(), '/') . '/convite/' . $token;
        $html = Mail::modelo([
            'previa' => "{$quem} convidou você para {$art} {$rot} {$turmaNome}. Crie sua conta para entrar.",
            'selo'   => 'Convite',
            'titulo' => "Convite para {$art} {$rot} {$turmaNome}",
            'corpo'  => '<p><strong>' . Mail::e($quem) . '</strong> convidou você para o ' . Mail::e($app) . '. Para entrar n' . $art . ' ' . $rot . ', crie sua conta: com uma senha ou com sua conta Google.</p>',
            'info'   => [ucfirst($rot) => $turmaNome, 'Convidado por' => $quem, 'Link válido até' => date('d/m/Y', strtotime('+' . Convite::DIAS_VALIDADE . ' days'))],
            'botao'  => ["Criar conta e entrar n{$art} {$rot}", $link],
            'nota'   => 'Se você não esperava este convite, pode ignorar este email.',
        ]);
        (new Mail())->enviar($email, "Convite para {$art} {$rot} {$turmaNome} — {$app}", $html);
    }

    /** Turmas em que posso convidar: todas (master) ou as que represento. */
    private function turmasQuePosso(): array
    {
        $u = $_SESSION['usuario'];
        $pdo = Database::getConnection();
        if ($u['tipo'] === 'master') return $pdo->query("SELECT id, nome, tipo FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
        $st = $pdo->prepare("
            SELECT t.id, t.nome, t.tipo FROM turmas t INNER JOIN turma_usuarios tu ON tu.turma_id = t.id
            WHERE tu.usuario_id = ? AND tu.papel = 'representante' AND tu.ativo = 1 ORDER BY t.nome
        ");
        $st->execute([(int) $u['id']]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function turmaPermitida(int $turmaId): array
    {
        foreach ($this->turmasQuePosso() as $t) if ((int) $t['id'] === $turmaId) return $t;
        http_response_code(403);
        exit('Você só pode convidar alunos para as turmas que representa.');
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
