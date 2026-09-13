<?php
// app/Core/Perfil.php

class Perfil
{
    public const MASTER = 1;
    public const ADMIN = 2;
    public const OPERADOR = 3;
    public const USUARIO = 4;
    
    public static function getNome(int $id): string
    {
        return [
            self::MASTER => 'Master',
            self::ADMIN => 'Admin',
            self::OPERADOR => 'Operador',
            self::USUARIO => 'Usuario'
        ][$id] ?? 'Desconhecido';
    }
    
    public static function getHierarquia(): array
    {
        return [
            self::MASTER => 4,
            self::ADMIN => 3,
            self::OPERADOR => 2,
            self::USUARIO => 1
        ];
    }
    
    public static function isHigherOrEqual(int $role, int $required): bool
    {
        $h = self::getHierarquia();
        return ($h[$role] ?? 0) >= ($h[$required] ?? 0);
    }
}