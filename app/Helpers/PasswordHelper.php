<?php
// app/Helpers/PasswordHelper.php
class PasswordHelper
{
    public static function validate(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Mínimo 8 caracteres';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Pelo menos 1 letra maiúscula';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Pelo menos 1 letra minúscula';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Pelo menos 1 número';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Pelo menos 1 caractere especial';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function generateTemp(int $length = 10): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
}