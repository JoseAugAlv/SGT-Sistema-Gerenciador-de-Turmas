<?php
// app/Helpers/ViewHelper.php

class ViewHelper
{
    /**
     * URL de um arquivo de public/ com versão pela data de modificação,
     * para o navegador baixar de novo sempre que o arquivo mudar.
     * Ex.: ViewHelper::asset('css/base.css')
     */
    public static function asset(string $caminho): string
    {
        $arquivo = __DIR__ . '/../../public/' . $caminho;
        $versao  = is_file($arquivo) ? filemtime($arquivo) : '';
        return App::getBasePath() . '/public/' . $caminho . ($versao ? '?v=' . $versao : '');
    }

    /**
     * Cor de texto legível sobre um fundo (#rrggbb): branco ou quase preto,
     * escolhida pelo contraste WCAG.
     */
    public static function corTexto(string $fundo): string
    {
        if (!preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $fundo, $m)) return '#ffffff';
        $lin = function ($c) { $c = hexdec($c) / 255; return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4; };
        $l = 0.2126 * $lin($m[1]) + 0.7152 * $lin($m[2]) + 0.0722 * $lin($m[3]);
        $contrasteBranco = 1.05 / ($l + 0.05);
        $contrastePreto  = ($l + 0.05) / (0.0062 + 0.05); // luminância de #0b1220
        return $contrasteBranco >= $contrastePreto ? '#ffffff' : '#0b1220';
    }

    /**
     * Data no formato brasileiro: "12/04/2026" (ou "12/04/2026 14:30" com $hora).
     * Vazio ou inválido vira "—".
     */
    public static function data(?string $valor, bool $hora = false): string
    {
        if (!$valor || !($ts = strtotime($valor))) return '—';
        return date($hora ? 'd/m/Y H:i' : 'd/m/Y', $ts);
    }

    /**
     * Símbolo do Prumo (redesenho em SVG de public/img/logo-prumo.png), na cor do texto.
     */
    public static function marcaPrumo(): string
    {
        return '<svg class="marca-prumo" viewBox="0 0 512 512" fill="none" stroke="currentColor" stroke-width="44" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M408 32H172C95 32 32 95 32 172v168c0 77 63 140 140 140h150c77 0 140-63 140-140v-46"/><path d="M322 160l52 52L478 96"/><path d="M140 228h150M140 316h108"/></svg>';
    }

    /**
     * Sigla grande da faixa da turma: "231-3ANO-INFO-M" vira "3º";
     * sem ano no nome, as 3 primeiras letras ou números.
     */
    public static function siglaTurma(string $nome): string
    {
        if (preg_match('/(\d)\s*º?\s*ano/iu', $nome, $m)) return $m[1] . 'º';
        return mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N}]/u', '', $nome), 0, 3));
    }

    /**
     * Variáveis CSS da faixa da turma quando ela usa cor personalizada.
     * Retorna '' para manter as cores do tema.
     */
    public static function estiloTurma(array $turma): string
    {
        if (empty($turma['cor_personalizada']) || !preg_match('/^#[0-9a-f]{6}$/i', $turma['cor_primaria'] ?? '')) return '';
        $fundo = strtolower($turma['cor_primaria']);
        $texto = self::corTexto($fundo);
        $suave = $texto === '#ffffff' ? 'rgba(255,255,255,.78)' : 'rgba(11,18,32,.72)';
        return "--shell-bg:{$fundo};--shell-text:{$texto};--shell-muted:{$suave};--accent-on-dark:{$texto};";
    }

    /**
     * Retorna o campo CSRF para formulários
     */
    public static function csrfField()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        return CsrfMiddleware::field();
    }

    /**
     * Escapa HTML para saída segura
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Formata data
     */
    public static function formatDate($date, $format = 'd/m/Y H:i')
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        return date($format, strtotime($date));
    }

    /**
     * Formata valor monetário
     */
    public static function formatMoney($value)
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    /**
     * Gera badge de status
     */
    public static function statusBadge($status)
    {
        $classes = [
            'aprovado' => 'badge-success',
            'aprovada' => 'badge-success',
            'ativo' => 'badge-success',
            'pendente' => 'badge-warning',
            'rejeitado' => 'badge-danger',
            'rejeitada' => 'badge-danger',
            'inativo' => 'badge-danger',
            'cancelado' => 'badge-secondary',
            'planejamento' => 'badge-info',
            'finalizado' => 'badge-secondary'
        ];

        $class = $classes[strtolower($status)] ?? 'badge-secondary';
        return "<span class='badge {$class}'>" . ucfirst($status) . "</span>";
    }
}