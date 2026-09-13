<?php
// app/Helpers/ViewHelper.php

class ViewHelper
{
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