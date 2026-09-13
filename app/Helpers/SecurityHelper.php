<?php
// app/Helpers/SecurityHelper.php

require_once __DIR__ . '/../Core/App.php';

class SecurityHelper
{
    /**
     * Valida força de senha no backend
     */
    public static function validarForcaSenha($senha)
    {
        $erros = [];

        if (strlen($senha) < 8) {
            $erros[] = 'Mínimo 8 caracteres';
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            $erros[] = 'Pelo menos 1 letra maiúscula';
        }
        if (!preg_match('/[a-z]/', $senha)) {
            $erros[] = 'Pelo menos 1 letra minúscula';
        }
        if (!preg_match('/[0-9]/', $senha)) {
            $erros[] = 'Pelo menos 1 número';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $senha)) {
            $erros[] = 'Pelo menos 1 caractere especial';
        }

        return [
            'valida' => count($erros) === 0,
            'erros' => $erros
        ];
    }

    /**
     * Sanitiza caminho de arquivo para evitar path traversal
     */
    public static function sanitizarCaminhoArquivo($arquivo, $diretorioBase, $extensoesPermitidas = [])
    {
        $nomeArquivo = basename($arquivo);

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nomeArquivo)) {
            return null;
        }

        $caminhoCompleto = realpath($diretorioBase . DIRECTORY_SEPARATOR . $nomeArquivo);

        if ($caminhoCompleto === false || strpos($caminhoCompleto, realpath($diretorioBase)) !== 0) {
            return null;
        }

        if (!empty($extensoesPermitidas)) {
            $extensao = strtolower(pathinfo($caminhoCompleto, PATHINFO_EXTENSION));
            if (!in_array($extensao, $extensoesPermitidas)) {
                return null;
            }
        }

        return $caminhoCompleto;
    }

    /**
     * Valida tipo real de arquivo com finfo
     */
    public static function validarMimeArquivo($caminhoTemp, $mimesPermitidos = ['image/jpeg', 'image/png', 'application/pdf'])
    {
        if (!function_exists('finfo_file')) {
            if (in_array('image/jpeg', $mimesPermitidos) || in_array('image/png', $mimesPermitidos)) {
                $info = @getimagesize($caminhoTemp);
                return $info !== false;
            }
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $caminhoTemp);
        finfo_close($finfo);

        return in_array($mime, $mimesPermitidos);
    }

    /**
     * Log de ações sensíveis
     */
    public static function logAuditoria($acao, $usuario_id, $detalhes = '', $tipo = 'info')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $mensagem = "[{$timestamp}] [{$tipo}] Usuario:{$usuario_id} | IP:{$ip} | Acao:{$acao} | Detalhes:{$detalhes}\n";

        file_put_contents($logDir . '/auditoria.log', $mensagem, FILE_APPEND);
    }
}