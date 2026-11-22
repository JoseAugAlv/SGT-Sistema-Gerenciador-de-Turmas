<?php
// app/Services/PrecoService.php
// Cálculo do preço de um plano (regras em precos.md):
// 1) desconto (cupom) no preço à vista, nunca abaixo do piso;  2) acréscimo das parcelas sobre o à vista.
require_once __DIR__ . '/../Config/database.php';

class PrecoService
{
    // Acréscimo (%) por número de parcelas. 'A': Semestral, Anual, Completo e grêmios. 'B': Estendido e Vitalício.
    const ACRESCIMOS = [
        'A' => [1 => 0, 2 => 5, 3 => 7, 4 => 10, 5 => 12, 6 => 14],
        'B' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 4, 8 => 6, 9 => 8, 10 => 10, 11 => 12, 12 => 14],
    ];

    /** Lê uma configuração comercial (editável pelo master). */
    public static function config(string $chave, ?string $padrao = null): ?string
    {
        $stmt = Database::getConnection()->prepare("SELECT valor FROM config_comercial WHERE chave = ?");
        $stmt->execute([$chave]);
        $v = $stmt->fetchColumn();
        return $v === false || $v === null || $v === '' ? $padrao : (string) $v;
    }

    /** Percentual de desconto que um cupom dá num plano (cupom sem percentual usa o padrão do tipo). */
    public static function percentualCupom(?array $cupom, array $plano): float
    {
        if (!$cupom) return 0.0;
        if ($cupom['percentual'] !== null) return (float) $cupom['percentual'];
        return (float) self::config($plano['tipo'] === 'gremio' ? 'cupom_gremio_pct' : 'cupom_turma_pct', '0');
    }

    /** Parcelas permitidas para o plano: [n => % de acréscimo]. */
    public static function opcoesParcelas(array $plano): array
    {
        $tabela = self::ACRESCIMOS[$plano['tabela_parcelas'] ?? ''] ?? [1 => 0];
        return array_filter($tabela, fn($n) => $n <= (int) $plano['max_parcelas'], ARRAY_FILTER_USE_KEY) ?: [1 => 0];
    }

    /**
     * Preço final. Retorna lista, desconto_pct, avista, acrescimo_pct, total, parcelas, parcela e ultima_parcela
     * (a última absorve o arredondamento, para a soma bater com o total).
     */
    public static function calcular(array $plano, int $parcelas = 1, ?array $cupom = null): array
    {
        $opcoes = self::opcoesParcelas($plano);
        if (!isset($opcoes[$parcelas])) $parcelas = 1;

        $lista  = (float) $plano['preco'];
        $pct    = self::percentualCupom($cupom, $plano);
        // O acréscimo incide sobre o valor com desconto ainda sem arredondar (igual à planilha de precos.md)
        $bruto = $lista;
        if ($pct > 0) {
            $bruto = max((float) $plano['piso'], $lista * (1 - $pct / 100));
        }
        $avista = round($bruto, 2);

        $acr     = (float) $opcoes[$parcelas];
        $total   = round($bruto * (1 + $acr / 100), 2);
        $parcela = round($total / $parcelas, 2);
        $ultima  = round($total - $parcela * ($parcelas - 1), 2);

        return [
            'lista'          => $lista,
            'desconto_pct'   => $pct,
            'avista'         => $avista,
            'acrescimo_pct'  => $acr,
            'total'          => $total,
            'parcelas'       => $parcelas,
            'parcela'        => $parcela,
            'ultima_parcela' => $ultima,
        ];
    }
}
