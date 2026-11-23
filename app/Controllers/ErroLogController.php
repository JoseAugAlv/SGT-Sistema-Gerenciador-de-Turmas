<?php
// app/Controllers/ErroLogController.php
// Master: últimos erros do sistema (logs/php_errors.log), agrupados por mensagem, e botão para limpar o arquivo.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ErroLogController
{
    const ARQUIVO = __DIR__ . '/../../logs/php_errors.log';
    const LER_BYTES = 400000;   // só o fim do arquivo (os erros mais recentes)

    public function index()
    {
        $this->soMaster();
        $erros = self::ler();
        require __DIR__ . '/../Views/master/erros.php';
    }

    public function limpar()
    {
        $this->soMaster();
        CsrfMiddleware::validate();
        if (is_file(self::ARQUIVO)) file_put_contents(self::ARQUIVO, '');
        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Registro de erros limpo.'];
        header('Location: ' . App::getBasePath() . '/master/erros');
        exit;
    }

    /** Entradas do log (mais recentes primeiro), agrupando as repetidas. */
    public static function ler(): array
    {
        if (!is_file(self::ARQUIVO) || !filesize(self::ARQUIVO)) return [];
        $tam = filesize(self::ARQUIVO);
        $fp = fopen(self::ARQUIVO, 'r');
        if ($tam > self::LER_BYTES) fseek($fp, -self::LER_BYTES, SEEK_END);
        $txt = stream_get_contents($fp);
        fclose($fp);

        // cada entrada começa com "[23-Nov-2026 14:05:01 America/Sao_Paulo] "; o resto (pilha) vai junto
        $partes = preg_split('/^(?=\[\d{2}-\w{3}-\d{4} [\d:]+(?: [\w\/+-]+)?\] )/m', $txt, -1, PREG_SPLIT_NO_EMPTY);
        $grupos = [];
        foreach ($partes as $p) {
            if (!preg_match('/^\[([^\]]+)\] (.*)$/s', trim($p), $m)) continue;
            $linhas = explode("\n", $m[2]);
            $msg = trim($linhas[0]);
            $chave = md5(preg_replace('/\d+/', '#', $msg));   // números (ids, linhas) não separam o grupo
            if (!isset($grupos[$chave])) $grupos[$chave] = ['msg' => $msg, 'detalhe' => trim(implode("\n", array_slice($linhas, 1))), 'vezes' => 0, 'ultima' => $m[1]];
            $grupos[$chave]['vezes']++;
            $grupos[$chave]['ultima'] = $m[1];
        }
        $lista = array_values($grupos);
        usort($lista, fn($a, $b) => strtotime(preg_replace('/ \S+$/', '', $b['ultima'])) <=> strtotime(preg_replace('/ \S+$/', '', $a['ultima'])));
        return array_slice($lista, 0, 100);
    }

    private function soMaster(): void
    {
        if (($_SESSION['usuario']['tipo'] ?? '') !== 'master') { http_response_code(403); exit('Apenas o master.'); }
    }
}
