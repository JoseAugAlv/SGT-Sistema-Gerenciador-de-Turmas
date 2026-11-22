<?php
// app/Helpers/SecurityHelper.php
require_once __DIR__ . '/../Core/App.php';

class SecurityHelper
{
    public static function validarForcaSenha(string $senha): array
    {
        $erros = [];
        if (strlen($senha) < 8)            $erros[] = 'Mínimo 8 caracteres';
        if (!preg_match('/[A-Z]/', $senha)) $erros[] = 'Pelo menos 1 letra maiúscula';
        if (!preg_match('/[a-z]/', $senha)) $erros[] = 'Pelo menos 1 letra minúscula';
        if (!preg_match('/[0-9]/', $senha)) $erros[] = 'Pelo menos 1 número';
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $senha))
            $erros[] = 'Pelo menos 1 caractere especial';

        return ['valida' => empty($erros), 'erros' => $erros];
    }

    /**
     * Confere o tipo real do arquivo pelo conteúdo (não pela extensão).
     */
    public static function validarMimeArquivo(string $caminho, array $mimesPermitidos): bool
    {
        if (!is_file($caminho)) return false;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($caminho);
        return $mime !== false && in_array($mime, $mimesPermitidos, true);
    }

    public static function gerarToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function logAuditoria(string $acao, $usuarioId, string $detalhes = '', string $tipo = 'info'): void
    {
        $dir = __DIR__ . '/../../logs';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);

        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $linha = sprintf(
            "[%s] [%s] user=%s ip=%s acao=%s | %s\n",
            date('Y-m-d H:i:s'), $tipo, $usuarioId ?? '-', $ip, $acao, $detalhes
        );
        @file_put_contents($dir . '/auditoria.log', $linha, FILE_APPEND);
    }
}