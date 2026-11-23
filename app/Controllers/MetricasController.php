<?php
// app/Controllers/MetricasController.php
// Painel de métricas do master (/master/metricas): receita, turmas por situação do plano, planos vendidos,
// conversão, renovações, usuários ativos, uso dos recursos, cupons e escolas interessadas.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Services/MetricasService.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';

class MetricasController
{
    public function index()
    {
        if (($_SESSION['usuario']['tipo'] ?? '') !== 'master') { http_response_code(403); exit('Apenas o master.'); }
        $m = (new MetricasService())->montar();
        require __DIR__ . '/../Views/master/metricas.php';
    }
}
