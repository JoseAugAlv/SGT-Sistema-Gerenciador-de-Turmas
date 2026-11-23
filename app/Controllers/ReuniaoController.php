<?php
// app/Controllers/ReuniaoController.php
// Reuniões da turma ou do grêmio: marcar (com pauta), registrar ata e presença, cancelar.
// Marcam: representante/presidente e vice; no grêmio, também o diretor/coordenador para a própria diretoria.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Reuniao.php';
require_once __DIR__ . '/../Models/Diretoria.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Helpers/RespostaHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ReuniaoController
{
    private TurmaUsuario $tu;
    private Reuniao      $reuniao;
    private Diretoria    $dir;

    public function __construct()
    {
        $this->tu      = new TurmaUsuario();
        $this->reuniao = new Reuniao();
        $this->dir     = new Diretoria();
    }

    public function index(int $turmaId)
    {
        $turma = $this->exigirMembro($turmaId);
        $uid = (int) $_SESSION['usuario']['id'];
        $geral = $this->podeGeral($turmaId);
        $lidero = $turma['tipo'] === 'gremio' ? $this->dir->queLidero($turmaId, $uid) : [];
        $this->render('reunioes/index', [
            'turma'       => $turma,
            'reunioes'    => $this->reuniao->listar($turmaId),
            'podeMarcar'  => $geral || $lidero,
            'podeGeral'   => $geral,
            'diretorias'  => $turma['tipo'] === 'gremio' ? ($geral ? $this->dir->listar($turmaId) : $lidero) : [],
        ]);
    }

    public function salvar(int $turmaId)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirMembro($turmaId);
        $uid = (int) $_SESSION['usuario']['id'];
        $dirId = (int) ($_POST['diretoria_id'] ?? 0) ?: null;
        if (!$this->podeMarcar($turma, $dirId)) { http_response_code(403); exit('Você não pode marcar esta reunião.'); }

        $data = str_replace('T', ' ', (string) ($_POST['data'] ?? ''));
        $d = [
            'turma_id' => $turmaId, 'diretoria_id' => $dirId,
            'titulo'   => mb_substr(trim($_POST['titulo'] ?? ''), 0, 150),
            'data'     => strlen($data) === 16 ? $data . ':00' : $data,
            'local'    => mb_substr(trim($_POST['local'] ?? ''), 0, 120) ?: null,
            'pauta'    => trim($_POST['pauta'] ?? '') ?: null,
            'criado_por' => $uid,
        ];
        if (mb_strlen($d['titulo']) < 3 || !DateTime::createFromFormat('Y-m-d H:i:s', $d['data'])) {
            $this->flash('Informe o título e a data/hora da reunião.'); $this->redirect('/turmas/' . $turmaId . '/reunioes');
        }
        $id = $this->reuniao->criar($d);
        (new Auditoria())->registrar('reuniao_marcada', 'reunioes', $id, null, ['data' => $d['data'], 'diretoria' => $dirId]);
        $this->flash('Reunião marcada. Ela já aparece no calendário.', 'sucesso');

        // Convoca depois de responder (o email pode demorar)
        RespostaHelper::redirecionarEContinuar('/reunioes/' . $id);
        $r = $this->reuniao->porId($id);
        $ids = array_values(array_filter(array_map(fn($c) => (int) $c['id'], $this->reuniao->convocados($r)), fn($x) => $x !== $uid));
        (new Notificacao())->criarParaVarios($ids, 'reuniao', 'Reunião: ' . $d['titulo'],
            'Marcada para ' . date('d/m/Y \à\s H:i', strtotime($d['data'])) . ($d['local'] ? " · {$d['local']}" : '') . '.', '/reunioes/' . $id);
        exit;
    }

    public function detalhe(int $id)
    {
        $r = $this->carregar($id);
        $this->render('reunioes/detalhe', [
            'r'          => $r,
            'convocados' => $this->reuniao->convocados($r),
            'presencas'  => $this->reuniao->presencas($id),
            'vias'       => $this->reuniao->viasPresenca($id),
            'qrAberto'   => $this->qrAberto($r),
            'podeGerir'  => $this->podeMarcar(['id' => $r['turma_id'], 'tipo' => $r['turma_tipo']], $r['diretoria_id'] ? (int) $r['diretoria_id'] : null),
        ]);
    }

    /** Ata + presença: a reunião vira "realizada". */
    public function registrar(int $id)
    {
        CsrfMiddleware::validate();
        $r = $this->carregar($id, true);
        $convocados = array_map(fn($c) => (int) $c['id'], $this->reuniao->convocados($r));
        $presentes  = array_map('intval', (array) ($_POST['presentes'] ?? []));
        $this->reuniao->registrar($id, trim($_POST['ata'] ?? ''), $convocados, $presentes);
        (new Auditoria())->registrar('reuniao_ata', 'reunioes', $id, null, ['presentes' => count($presentes)]);
        $this->flash('Ata e presença salvas.', 'sucesso');
        $this->redirect('/reunioes/' . $id);
    }

    public function cancelar(int $id)
    {
        CsrfMiddleware::validate();
        $r = $this->carregar($id, true);
        $this->reuniao->cancelar($id);
        $this->flash('Reunião cancelada.', 'sucesso');
        $this->redirect('/turmas/' . (int) $r['turma_id'] . '/reunioes');
    }

    // ============ PRESENÇA POR QR CODE ============
    // O QR leva a /presenca/{reuniao}/{token}/{codigo}. O código muda a cada 30 s (HMAC com APP_KEY);
    // vale o atual e o anterior, então uma foto do QR mandada no grupo para de funcionar em até 1 minuto.

    const QR_JANELA = 30;

    /** Abre o QR (organizador) e vai para a tela de projeção. */
    public function qrAbrir(int $id)
    {
        CsrfMiddleware::validate();
        $r = $this->carregar($id, true);
        if ($r['status'] === 'cancelada') { $this->flash('A reunião foi cancelada.'); $this->redirect('/reunioes/' . $id); }
        $min = max(5, min(120, (int) ($_POST['minutos'] ?? 20)));
        $this->reuniao->abrirQr($id, $min);
        (new Auditoria())->registrar('reuniao_qr_aberto', 'reunioes', $id, null, ['minutos' => $min]);
        $this->redirect('/reunioes/' . $id . '/qr');
    }

    public function qrFechar(int $id)
    {
        CsrfMiddleware::validate();
        $this->carregar($id, true);
        $this->reuniao->fecharQr($id);
        $this->flash('QR de presença fechado. Confira a lista e salve a ata.', 'sucesso');
        $this->redirect('/reunioes/' . $id . '#presenca');
    }

    /** Tela de projeção: QR grande, contagem e quem já marcou (atualiza sozinha). */
    public function qrTela(int $id)
    {
        $r = $this->carregar($id, true);
        if (!$this->qrAberto($r)) { $this->flash('O QR de presença está fechado.', 'aviso'); $this->redirect('/reunioes/' . $id . '#presenca'); }
        $this->render('reunioes/qr', ['r' => $r, 'convocados' => count($this->reuniao->convocados($r))] + $this->qrDados($r));
    }

    /** JSON para a tela de projeção: QR atual (SVG), presentes e tempo restante. */
    public function qrStatus(int $id)
    {
        $r = $this->carregar($id, true);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if (!$this->qrAberto($r)) { echo json_encode(['aberto' => false]); exit; }
        echo json_encode(['aberto' => true] + $this->qrDados($r), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Quem escaneou o QR: marca a própria presença. */
    public function presenca(int $id, string $token, string $codigo)
    {
        $r = $this->reuniao->porId($id);
        $ok = $r && $this->qrAberto($r) && hash_equals((string) $r['qr_token'], $token) && $this->codigoValido($id, $token, $codigo);
        $uid = (int) $_SESSION['usuario']['id'];
        $situacao = 'invalido';
        if ($ok) {
            $convocado = in_array($uid, array_map(fn($c) => (int) $c['id'], $this->reuniao->convocados($r)), true);
            if (!$convocado) $situacao = 'nao_convocado';
            else $situacao = $this->reuniao->marcarPresencaQr($id, $uid) ? 'marcada' : 'ja_marcada';
        } elseif ($r && $r['qr_token'] && !$this->qrAberto($r)) {
            $situacao = 'fechado';
        }
        $this->render('reunioes/presenca', ['r' => $r, 'situacao' => $situacao]);
    }

    private function qrAberto(array $r): bool
    {
        return !empty($r['qr_token']) && !empty($r['qr_ate']) && strtotime($r['qr_ate']) > time() && $r['status'] !== 'cancelada';
    }

    private static function chave(): string
    {
        $k = (string) App::get('APP_KEY', '');
        return $k !== '' ? $k : hash('sha256', __DIR__ . App::get('DB_NAME', '') . App::get('DB_PASS', ''));
    }

    private function codigo(int $id, string $token, int $janela): string
    {
        return substr(hash_hmac('sha256', $id . '|' . $token . '|' . $janela, self::chave()), 0, 12);
    }

    private function codigoValido(int $id, string $token, string $codigo): bool
    {
        $j = intdiv(time(), self::QR_JANELA);
        return hash_equals($this->codigo($id, $token, $j), $codigo) || hash_equals($this->codigo($id, $token, $j - 1), $codigo);
    }

    /** QR atual em SVG, link, presentes pelo QR e segundos até fechar / até o próximo código. */
    private function qrDados(array $r): array
    {
        require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
        $j = intdiv(time(), self::QR_JANELA);
        $caminho = '/presenca/' . (int) $r['id'] . '/' . $r['qr_token'] . '/' . $this->codigo((int) $r['id'], $r['qr_token'], $j);
        // o celular de quem escaneia precisa alcançar o servidor: usa o endereço da própria requisição
        // (quem projeta abriu a página pelo IP/domínio certo); APP_URL (já com o caminho base) é o plano B
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $url = $host !== ''
            ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $host . App::getBasePath() . $caminho
            : rtrim((string) App::get('APP_URL', 'http://localhost'), '/') . $caminho;
        $svg = (new TCPDF2DBarcode($url, 'QRCODE,M'))->getBarcodeSVGcode(8, 8, '#0f1222');
        $svg = preg_replace('/^<\?xml[^>]*>\s*(<!DOCTYPE[^>]*>\s*)?/i', '', $svg);
        // escala com a caixa: troca a largura/altura fixas por viewBox
        if (preg_match('/<svg[^>]*\swidth="([\d.]+)"[^>]*\sheight="([\d.]+)"/', $svg, $m)) {
            $svg = preg_replace('/(<svg[^>]*?)\swidth="[\d.]+"([^>]*?)\sheight="[\d.]+"/', '$1 viewBox="0 0 ' . $m[1] . ' ' . $m[2] . '" preserveAspectRatio="xMidYMid meet"$2', $svg, 1);
        }
        $presentes = $this->reuniao->presentesQr((int) $r['id']);
        return [
            'svg'        => $svg,
            'url'        => $url,
            'presentes'  => array_map(fn($p) => ['nome' => $p['nome'], 'hora' => date('H:i', strtotime($p['marcado_em']))], array_slice($presentes, 0, 12)),
            'total'      => count($presentes),
            'restam'     => max(0, strtotime($r['qr_ate']) - time()),
            'proximo'    => self::QR_JANELA - (time() % self::QR_JANELA),
        ];
    }

    // ============ HELPERS ============

    private function carregar(int $id, bool $gerir = false): array
    {
        $r = $this->reuniao->porId($id);
        if (!$r) { $this->flash('Reunião não encontrada.'); $this->redirect('/turmas'); }
        $this->exigirMembro((int) $r['turma_id']);
        if ($gerir && !$this->podeMarcar(['id' => $r['turma_id'], 'tipo' => $r['turma_tipo']], $r['diretoria_id'] ? (int) $r['diretoria_id'] : null)) {
            http_response_code(403); exit('Só quem organiza esta reunião pode alterá-la.');
        }
        return $r;
    }

    private function exigirMembro(int $turmaId): array
    {
        $turma = (new Turma())->porId($turmaId);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->estaAtivo($turmaId, (int) $u['id'])) { $this->flash('Você não está nesta turma.'); $this->redirect('/turmas'); }
        return $turma;
    }

    private function podeGeral(int $turmaId): bool
    {
        $u = $_SESSION['usuario'];
        return $u['tipo'] === 'master' || $this->tu->ehRepresentante($turmaId, (int) $u['id']);
    }

    /** Geral: representante/presidente e vice. Diretoria: eles ou o diretor/coordenador dela. */
    private function podeMarcar(array $turma, ?int $dirId): bool
    {
        if ($this->podeGeral((int) $turma['id'])) return true;
        if (!$dirId || ($turma['tipo'] ?? 'turma') !== 'gremio') return false;
        $d = $this->dir->porId($dirId);
        if (!$d || (int) $d['turma_id'] !== (int) $turma['id']) return false;
        return in_array($this->dir->cargoDe($dirId, (int) $_SESSION['usuario']['id']), ['diretor', 'coordenador'], true);
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
