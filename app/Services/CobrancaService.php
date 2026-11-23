<?php
// app/Services/CobrancaService.php
// Dados de cobrança: valida o CPF (dígitos verificadores) e consulta o CEP (ViaCEP, com a BrasilAPI de reserva).
// Obs.: não existe API pública gratuita que diga se um CPF está ativo na Receita; isso exige serviço pago (ex.: Serpro).
require_once __DIR__ . '/../Models/DadosCobranca.php';

class CobrancaService
{
    const UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

    public static function soDigitos(?string $v): string
    {
        return preg_replace('/\D/', '', (string) $v);
    }

    /** CPF válido pelos dígitos verificadores (recusa sequências como 111.111.111-11). */
    public static function cpfValido(string $cpf): bool
    {
        $cpf = self::soDigitos($cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) $soma += (int) $cpf[$i] * (($t + 1) - $i);
            $dv = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] !== $dv) return false;
        }
        return true;
    }

    public static function formatarCpf(string $cpf): string
    {
        $c = self::soDigitos($cpf);
        return strlen($c) === 11 ? substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9) : $cpf;
    }

    public static function formatarCep(string $cep): string
    {
        $c = self::soDigitos($cep);
        return strlen($c) === 8 ? substr($c, 0, 5) . '-' . substr($c, 5) : $cep;
    }

    /**
     * Consulta o CEP. Retorna ['ok' => true, 'logradouro', 'bairro', 'cidade', 'uf'],
     * ['ok' => false, 'erro' => 'nao_encontrado'] ou ['ok' => false, 'erro' => 'indisponivel'] (APIs fora do ar).
     */
    public static function consultarCep(string $cep): array
    {
        $cep = self::soDigitos($cep);
        if (strlen($cep) !== 8) return ['ok' => false, 'erro' => 'nao_encontrado'];

        // 1) ViaCEP
        [$status, $j] = self::getJson("https://viacep.com.br/ws/{$cep}/json/");
        if ($status === 200 && is_array($j)) {
            if (!empty($j['erro'])) return ['ok' => false, 'erro' => 'nao_encontrado'];
            return ['ok' => true, 'logradouro' => $j['logradouro'] ?? '', 'bairro' => $j['bairro'] ?? '',
                    'cidade' => $j['localidade'] ?? '', 'uf' => $j['uf'] ?? ''];
        }
        // 2) BrasilAPI (reserva)
        [$status, $j] = self::getJson("https://brasilapi.com.br/api/cep/v1/{$cep}");
        if ($status === 200 && is_array($j)) {
            return ['ok' => true, 'logradouro' => $j['street'] ?? '', 'bairro' => $j['neighborhood'] ?? '',
                    'cidade' => $j['city'] ?? '', 'uf' => $j['state'] ?? ''];
        }
        if ($status === 404) return ['ok' => false, 'erro' => 'nao_encontrado'];
        return ['ok' => false, 'erro' => 'indisponivel'];
    }

    /**
     * Lê e valida os dados de cobrança do POST. Retorna [dados, erros].
     * Se o CEP existir, cidade e UF vêm da consulta (não do que foi digitado).
     */
    public static function lerDoPost(array $post): array
    {
        $d = [];
        foreach (DadosCobranca::CAMPOS as $c) $d[$c] = trim((string) ($post['cob_' . $c] ?? ''));
        $d['cpf'] = self::soDigitos($d['cpf']);
        $d['cep'] = self::soDigitos($d['cep']);
        $d['uf']  = strtoupper($d['uf']);
        $d['complemento'] = $d['complemento'] !== '' ? $d['complemento'] : null;

        $erros = [];
        if (mb_strlen($d['nome_completo']) < 5 || !str_contains($d['nome_completo'], ' ')) $erros[] = 'Informe o nome completo (nome e sobrenome).';
        if (!self::cpfValido($d['cpf'])) $erros[] = 'CPF inválido. Confira os números.';

        if (strlen($d['cep']) !== 8) {
            $erros[] = 'CEP inválido: use 8 números.';
        } else {
            $r = self::consultarCep($d['cep']);
            if (!$r['ok'] && $r['erro'] === 'nao_encontrado') {
                $erros[] = 'CEP não encontrado. Confira os números.';
            } elseif ($r['ok']) {
                $d['cidade'] = $r['cidade'] ?: $d['cidade'];
                $d['uf']     = $r['uf'] ?: $d['uf'];
                if ($d['logradouro'] === '') $d['logradouro'] = $r['logradouro'];
                if ($d['bairro'] === '')     $d['bairro']     = $r['bairro'];
            }
            // CEP indisponível (APIs fora do ar): aceita o que foi digitado, para não travar a venda
        }
        if ($d['logradouro'] === '') $erros[] = 'Informe a rua (logradouro).';
        if ($d['numero'] === '')     $erros[] = 'Informe o número (use "S/N" se não houver).';
        if ($d['bairro'] === '')     $erros[] = 'Informe o bairro.';
        if ($d['cidade'] === '')     $erros[] = 'Informe a cidade.';
        if (!in_array($d['uf'], self::UFS, true)) $erros[] = 'UF inválida.';

        foreach (['nome_completo' => 120, 'logradouro' => 150, 'numero' => 20, 'complemento' => 80, 'bairro' => 80, 'cidade' => 80] as $c => $max) {
            if ($d[$c] !== null && mb_strlen($d[$c]) > $max) $d[$c] = mb_substr($d[$c], 0, $max);
        }
        return [$d, $erros];
    }

    /** [status HTTP, json decodificado|null]. Status 0 = falha de rede. */
    private static function getJson(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT      => 'Prumo/1.0',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body   = curl_exec($ch);
        $status = $body === false ? 0 : (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$status, $body ? json_decode($body, true) : null];
    }
}
