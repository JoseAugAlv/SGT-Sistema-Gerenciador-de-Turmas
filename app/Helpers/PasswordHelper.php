<?php
// app/Helpers/PasswordHelper.php

require_once __DIR__ . '/SecurityHelper.php';

class PasswordHelper
{
    const BCRYPT_COST = 12;

    public static function hash(string $senha): string
    {
        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
    }

    public static function verify(string $senha, string $hash): bool
    {
        return password_verify($senha, $hash);
    }

    public static function validate(string $senha): array
    {
        $r = SecurityHelper::validarForcaSenha($senha);
        return ['valid' => $r['valida'], 'errors' => $r['erros']];
    }

    public static function generateTemp(int $length = 10): string
    {
        $maius = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minus = 'abcdefghijkmnpqrstuvwxyz';
        $nums  = '23456789';
        $simb  = '!@#$%&*';

        $senha = $maius[random_int(0, strlen($maius) - 1)]
               . $minus[random_int(0, strlen($minus) - 1)]
               . $nums[random_int(0, strlen($nums) - 1)]
               . $simb[random_int(0, strlen($simb) - 1)];

        $todos = $maius . $minus . $nums . $simb;
        while (strlen($senha) < $length) {
            $senha .= $todos[random_int(0, strlen($todos) - 1)];
        }

        return str_shuffle($senha);
    }
}