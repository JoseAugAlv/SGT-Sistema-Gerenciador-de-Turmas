<?php
// app/Controllers/RelatoriosController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Helpers/ExcelHelper.php';
require_once __DIR__ . '/../Helpers/PdfHelper.php';

class RelatoriosController
{
    private $usuario;

    public function __construct()
    {
        $this->usuario = new Usuario();
    }

    /**
     * Página de relatórios (apenas Admin)
     */
    public function usuarios()
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] != 2) {
            header('Location: ' . App::getBasePath() . '/');
            exit;
        }

        $usuarios = $this->usuario->getAll();
        $tituloPagina = 'Relatório de Usuários - ' . App::getName();
        $cssPagina = 'home.css';
        
        require '../app/Views/relatorios/usuarios.php';
    }

    /**
     * Exportar para Excel
     */
    public function exportar()
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] != 2) {
            header('Location: ' . App::getBasePath() . '/');
            exit;
        }

        $usuarios = $this->usuario->getAll();
        $dados = ExcelHelper::prepararDados($usuarios);
        ExcelHelper::gerar($dados, 'usuarios_' . date('Y-m-d') . '.xls', 'Relatório de Usuários');
    }

    /**
     * Exportar para PDF
     */
    public function pdf()
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] != 2) {
            header('Location: ' . App::getBasePath() . '/');
            exit;
        }

        $usuarios = $this->usuario->getAll();
        
        $html = '<h1>Relatório de Usuários</h1>';
        $html .= '<p>Gerado em: ' . date('d/m/Y H:i:s') . '</p>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>ID</th><th>Nome</th><th>Email</th><th>Perfil</th><th>Status</th></tr>';
        
        foreach ($usuarios as $u) {
            $html .= '<tr>';
            $html .= '<td>' . $u['id_usuario'] . '</td>';
            $html .= '<td>' . htmlspecialchars($u['nome']) . '</td>';
            $html .= '<td>' . htmlspecialchars($u['email']) . '</td>';
            $html .= '<td>' . htmlspecialchars($u['nome_perfil'] ?? 'Usuario') . '</td>';
            $html .= '<td>' . ($u['ativo'] ? 'Ativo' : 'Inativo') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        PdfHelper::gerar($html, 'relatorio_usuarios_' . date('Y-m-d') . '.pdf');
    }
}