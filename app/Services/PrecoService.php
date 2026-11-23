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

    /**
     * Crédito do plano atual numa troca: a parte do valor pago (valor_base, à vista e já com desconto)
     * proporcional ao tempo que ainda falta. Plano sem vencimento: o valor todo.
     */
    public static function creditoRestante(array $assinatura): float
    {
        $base = (float) ($assinatura['valor_base'] ?? 0);
        if ($base <= 0 || empty($assinatura['inicio'])) return 0.0;
        if (empty($assinatura['fim'])) return round($base, 2);

        $total = strtotime($assinatura['fim']) - strtotime($assinatura['inicio']);
        $falta = strtotime($assinatura['fim']) - time();
        if ($total <= 0 || $falta <= 0) return 0.0;
        return round($base * min(1, $falta / $total), 2);
    }

    /**
     * Troca de plano (só para um mais caro): o novo plano começa do zero, com a duração cheia,
     * e o tempo que falta do atual vira crédito. Paga: preço do novo − crédito, com as parcelas do novo.
     * Cupom não vale na troca. Retorna null se o novo não for mais caro.
     */
    public static function calcularTroca(array $planoAtual, array $planoNovo, float $credito, int $parcelas = 1): ?array
    {
        if ((float) $planoNovo['preco'] <= (float) $planoAtual['preco']) return null;
        $dif = round((float) $planoNovo['preco'] - $credito, 2);
        if ($dif <= 0) return null;
        $base = ['preco' => $dif, 'piso' => $dif] + $planoNovo;
        return self::calcular($base, $parcelas) + ['diferenca' => $dif, 'credito' => $credito];
    }

    /** Lê uma configuração comercial (editável pelo master). */
    public static function config(string $chave, ?string $padrao = null): ?string
    {
        $stmt = Database::getConnection()->prepare("SELECT valor FROM config_comercial WHERE chave = ?");
        $stmt->execute([$chave]);
        $v = $stmt->fetchColumn();
        return $v === false || $v === null || $v === '' ? $padrao : (string) $v;
    }

    /**
     * Desconto que o cupom dá neste plano: ['tipo' => 'pct'|'valor', 'valor' => float] ou null (não vale para o plano).
     * Ordem: crédito de indicação (virtual) > regras por plano (cupom_planos) > cupom de indicação > regra antiga.
     */
    public static function descontoDoCupom(?array $cupom, array $plano): ?array
    {
        if (!$cupom) return null;
        if (in_array($cupom['origem'] ?? '', ['credito', 'cruzado'], true)) return ['tipo' => 'pct', 'valor' => (float) $cupom['percentual']];
        if (!empty($cupom['regras'])) {
            $r = $cupom['regras'][(int) $plano['id']] ?? null;
            return $r ? ['tipo' => $r['tipo_desconto'], 'valor' => (float) $r['valor']] : null;
        }
        if (($cupom['origem'] ?? '') === 'indicacao') return ['tipo' => 'pct', 'valor' => (float) self::config('indicacao_pct', '10')];
        $pct = self::percentualCupom($cupom, $plano);
        return $pct > 0 ? ['tipo' => 'pct', 'valor' => $pct] : null;
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
    /** Preço do adicional Dia a dia neste plano (0 se o plano já inclui ou não vende o adicional). */
    public static function precoDiaADia(array $plano): float
    {
        return empty($plano['inclui_dia_a_dia']) && !empty($plano['preco_dia_a_dia']) ? (float) $plano['preco_dia_a_dia'] : 0.0;
    }

    /**
     * $comDiaADia: soma o adicional Dia a dia (planos que não incluem). O cupom vale só sobre o plano;
     * o adicional entra depois do desconto e antes do acréscimo das parcelas.
     */
    public static function calcular(array $plano, int $parcelas = 1, ?array $cupom = null, bool $comDiaADia = false): array
    {
        $opcoes = self::opcoesParcelas($plano);
        if (!isset($opcoes[$parcelas])) $parcelas = 1;

        $lista  = (float) $plano['preco'];
        $desc   = self::descontoDoCupom($cupom, $plano);
        // O acréscimo incide sobre o valor com desconto ainda sem arredondar (igual à planilha de precos.md)
        $bruto = $lista;
        if ($desc && $desc['valor'] > 0) {
            $bruto = $desc['tipo'] === 'valor' ? $lista - $desc['valor'] : $lista * (1 - $desc['valor'] / 100);
            // Cupom de pacote (escola já pagou) pode zerar; os demais param no piso do plano
            $bruto = ($cupom['origem'] ?? '') === 'pacote' ? max(0.0, $bruto) : max((float) $plano['piso'], $bruto);
            $bruto = min($lista, $bruto);
        }
        $avista = round($bruto, 2);
        $pct    = $lista > 0 ? round(($lista - $bruto) / $lista * 100, 2) : 0.0;
        $adicional = $comDiaADia ? self::precoDiaADia($plano) : 0.0;
        if ($adicional > 0) { $bruto += $adicional; $avista = round($bruto, 2); }
        if ($avista <= 0) { $avista = 0.0; $bruto = 0.0; $parcelas = 1; } // gratuito: sem parcelas

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
            'dia_a_dia'      => $adicional,
        ];
    }
}
