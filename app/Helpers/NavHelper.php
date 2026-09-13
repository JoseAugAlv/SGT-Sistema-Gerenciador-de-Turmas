<?php
// app/Helpers/NavHelper.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';

class NavHelper
{
    /**
     * Busca o perfil do usuário diretamente na tabela usuario
     */
    public static function getPerfil($idUsuario)
    {
        try {
            $pdo = Database::getConnection();
            // CORRIGIDO: Busca direto na tabela usuario
            $sql = "SELECT id_perfil FROM usuario WHERE id_usuario = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idUsuario]);
            $idPerfil = $stmt->fetchColumn();
            return $idPerfil ? (int) $idPerfil : 4;
        } catch (Exception $e) {
            return 4;
        }
    }

    /**
     * Busca o nome do perfil
     */
    public static function getNomePerfil($idUsuario)
    {
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT p.perfil 
                    FROM usuario u
                    INNER JOIN perfil p ON u.id_perfil = p.id_perfil
                    WHERE u.id_usuario = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idUsuario]);
            $nome = $stmt->fetchColumn();
            return $nome ?: 'Usuario';
        } catch (Exception $e) {
            return 'Usuario';
        }
    }

    public static function getContadorNotificacoes($idUsuario)
    {
        if (!$idUsuario) {
            return 0;
        }
        try {
            require_once __DIR__ . '/../Models/Notificacao.php';
            $notificacao = new Notificacao();
            return $notificacao->contarNaoLidas($idUsuario);
        } catch (Exception $e) {
            return 0;
        }
    }

    public static function getBasePath()
    {
        return App::getBasePath();
    }

    public static function getAppName()
    {
        return App::getName();
    }
}