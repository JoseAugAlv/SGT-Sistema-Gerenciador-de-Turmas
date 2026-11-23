<?php
// app/Helpers/EscalaNota.php
// Esquema de notas do projeto: menção (I, R, B, MB), 0 a 10 ou 0 a 100.
// Internamente tudo continua de 0 a 100 (critérios, pesos, médias); o esquema muda só como se digita e como se lê.
require_once __DIR__ . '/../Services/NotaService.php';

class EscalaNota
{
    const ESQUEMAS = [
        'mencao' => ['Menção', 'I, R, B e MB', 'fa-award'],
        '0a10'   => ['De 0 a 10', 'Ex.: 7,5', 'fa-list-ol'],
        '0a100'  => ['De 0 a 100', 'Ex.: 75', 'fa-percent'],
    ];

    // Faixas prontas das menções (limite máximo de I, R e B; MB vai do fim de B até 100)
    const PRESETS = [
        'padrao'    => ['Padrão',    'I até 49 · R 50–69 · B 70–84 · MB 85+', 49, 69, 84],
        'exigente'  => ['Exigente',  'I até 59 · R 60–74 · B 75–89 · MB 90+', 59, 74, 89],
        'tranquilo' => ['Tranquilo', 'I até 39 · R 40–59 · B 60–79 · MB 80+', 39, 59, 79],
        'quartos'   => ['Em quartos','I até 24 · R 25–49 · B 50–74 · MB 75+', 24, 49, 74],
    ];

    public static function esquema(?array $cfg): string
    {
        $e = $cfg['esquema_nota'] ?? 'mencao';
        return isset(self::ESQUEMAS[$e]) ? $e : 'mencao';
    }

    public static function ehNumerico(?array $cfg): bool
    {
        return self::esquema($cfg) !== 'mencao';
    }

    /** Maior valor que se digita no esquema. */
    public static function maximo(?array $cfg): int
    {
        return self::esquema($cfg) === '0a10' ? 10 : 100;
    }

    /** Valor interno (0–100) → valor do esquema (0–10 ou 0–100). */
    public static function paraEscala(float $v100, ?array $cfg): float
    {
        return self::esquema($cfg) === '0a10' ? $v100 / 10 : $v100;
    }

    /** Valor digitado no esquema → interno (0–100). */
    public static function paraInterno(float $valor, ?array $cfg): float
    {
        $v = self::esquema($cfg) === '0a10' ? $valor * 10 : $valor;
        return max(0.0, min(100.0, $v));
    }

    /** Formatação do campo/valor já no esquema (substitui o $fmt das telas de avaliação). */
    public static function fmt(?array $cfg): callable
    {
        return function ($v) use ($cfg) {
            if ($v === null || $v === '') return '';
            $x = self::paraEscala((float) $v, $cfg);
            return rtrim(rtrim(number_format($x, 2, '.', ''), '0'), '.');
        };
    }

    /** Texto para leitura: "MB" na menção, "7,5" no 0 a 10, "75" no 0 a 100. */
    public static function exibir(?float $v100, ?array $cfg): string
    {
        if ($v100 === null) return '—';
        if (!self::ehNumerico($cfg)) return NotaService::conceitoPorValor($v100, $cfg);
        $x = self::paraEscala($v100, $cfg);
        return rtrim(rtrim(number_format($x, self::esquema($cfg) === '0a10' ? 1 : 0, ',', ''), '0'), ',') ?: '0';
    }

    /**
     * Lê o esquema e as faixas do formulário (_esquema_nota.php). Retorna [dados, erros].
     * Faixas: preset escolhido ou personalizado (máximos de I, R e B); os mínimos são deduzidos.
     */
    public static function lerDoPost(array $post): array
    {
        $esq = $post['esquema_nota'] ?? 'mencao';
        $erros = [];
        if (!isset(self::ESQUEMAS[$esq])) { $erros[] = 'Esquema de notas inválido.'; $esq = 'mencao'; }

        $preset = $post['faixa_preset'] ?? 'padrao';
        $f = self::faixasDoPreset($preset);
        if (!$f) {
            $i = (int) ($post['faixa_i'] ?? 49); $r = (int) ($post['faixa_r'] ?? 69); $b = (int) ($post['faixa_b'] ?? 84);
            if (!($i >= 0 && $i < $r && $r < $b && $b < 100)) $erros[] = 'Faixas inválidas: precisa ser I < R < B < 100.';
            $f = ['limite_i_max' => $i, 'limite_r_min' => $i + 1, 'limite_r_max' => $r,
                  'limite_b_min' => $r + 1, 'limite_b_max' => $b, 'limite_mb_min' => $b + 1];
        }
        return [['esquema_nota' => $esq] + $f, $erros];
    }

    /** Faixas a partir de um preset (para salvar em configuracoes_conceito). */
    public static function faixasDoPreset(string $preset): ?array
    {
        if (!isset(self::PRESETS[$preset])) return null;
        [, , $i, $r, $b] = self::PRESETS[$preset];
        return ['limite_i_max' => $i, 'limite_r_min' => $i + 1, 'limite_r_max' => $r,
                'limite_b_min' => $r + 1, 'limite_b_max' => $b, 'limite_mb_min' => $b + 1];
    }

    /** Qual preset bate com as faixas atuais (ou null = personalizado). */
    public static function presetAtual(array $cfg): ?string
    {
        foreach (self::PRESETS as $k => [, , $i, $r, $b]) {
            if ((int) $cfg['limite_i_max'] === $i && (int) $cfg['limite_r_max'] === $r && (int) $cfg['limite_b_max'] === $b) return $k;
        }
        return null;
    }
}
