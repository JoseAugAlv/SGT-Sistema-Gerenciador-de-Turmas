<?php
// app/Helpers/pdfHelper.php

require_once __DIR__ . '/../../vendor/autoload.php';

class PdfHelper
{
    /**
     * Gera um PDF a partir de HTML usando TCPDF
     */
    public static function gerar($html, $nomeArquivo = 'relatorio.pdf', $orientacao = 'P', $tamanho = 'A4')
    {
        // Limpar qualquer saída anterior
        if (ob_get_length()) {
            ob_end_clean();
        }
        ob_start();
        
        $pdf = new \TCPDF($orientacao, 'mm', $tamanho, true, 'UTF-8', false);
        
        // CORRIGIDO: Remover referências ao RecycleWays
        $appName = App::getName();
        $pdf->SetCreator($appName);
        $pdf->SetAuthor($appName);
        $pdf->SetTitle($nomeArquivo);
        $pdf->SetSubject('Relatório');
        $pdf->SetKeywords($appName . ', Sistema, Projeto');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Limpar buffer antes de enviar o PDF
        ob_end_clean();
        
        $pdf->Output($nomeArquivo, 'D');
        exit;
    }

    /**
     * Retorna o PDF como string (para salvar)
     */
    public static function gerarString($html)
    {
        if (ob_get_length()) {
            ob_end_clean();
        }
        ob_start();
        
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $appName = App::getName();
        $pdf->SetCreator($appName);
        $pdf->SetAuthor($appName);
        $pdf->SetTitle('Relatório');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        ob_end_clean();
        
        return $pdf->Output('', 'S');
    }
}