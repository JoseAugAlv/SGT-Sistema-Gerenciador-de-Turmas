<?php
// app/Services/PortfolioService.php
// Dados do relatório final do projeto em formato de portfólio: capa com números, etapas, critérios,
// grupos (integrantes, diretores, atas, tarefas), desempenho da turma (distribuição, sem notas individuais)
// e orçamento. Usado por PortfolioController para gerar o PDF.
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/SnapshotService.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';

class PortfolioService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function montar(array $projeto): array
    {
        $pid = (int) $projeto['id'];
        $q = function (string $sql, array $p = []) { $st = $this->pdo->prepare($sql); $st->execute($p); return $st->fetchAll(PDO::FETCH_ASSOC); };
        $um = function (string $sql, array $p = []) { $st = $this->pdo->prepare($sql); $st->execute($p); return $st->fetchColumn(); };

        $turma = $q("SELECT id, nome, tipo, cor_primaria FROM turmas WHERE id = ?", [(int) $projeto['turma_id']])[0] ?? [];
        $etapas = $q("SELECT nome, descricao, data_inicio, data_fim FROM projeto_etapas WHERE projeto_id = ? ORDER BY ordem, data_inicio, id", [$pid]);
        $criterios = $q("SELECT nome, peso, tipo_avaliacao FROM criterios WHERE projeto_id = ? ORDER BY peso DESC, nome", [$pid]);

        $grupos = $q("SELECT id, nome FROM grupos WHERE projeto_id = ? ORDER BY nome", [$pid]);
        foreach ($grupos as &$g) {
            $gid = (int) $g['id'];
            $g['membros'] = array_column($q("SELECT u.nome FROM grupo_alunos ga INNER JOIN usuarios u ON u.id = ga.usuario_id WHERE ga.grupo_id = ? AND ga.saiu_em IS NULL ORDER BY u.nome", [$gid]), 'nome');
            $g['diretores'] = array_column($q("SELECT u.nome FROM grupo_diretores gd INNER JOIN usuarios u ON u.id = gd.usuario_id WHERE gd.grupo_id = ? AND gd.ativo = 1 ORDER BY u.nome", [$gid]), 'nome');
            $g['atas'] = $q("SELECT a.titulo, a.data_ata, (SELECT COUNT(*) FROM atividades_ata x WHERE x.ata_id = a.id) atividades FROM atas a WHERE a.grupo_id = ? ORDER BY a.data_ata, a.id", [$gid]);
            $g['tarefas_feitas'] = (int) $um("SELECT COUNT(*) FROM grupo_tarefas WHERE grupo_id = ? AND coluna = 'feito'", [$gid]);
            $g['tarefas_total'] = (int) $um("SELECT COUNT(*) FROM grupo_tarefas WHERE grupo_id = ?", [$gid]);
        }
        unset($g);

        // desempenho: distribuição dos conceitos finais e média geral (sem nomes)
        $snap = new SnapshotService();
        $alunos = (new TurmaUsuario())->listarAlunos((int) $projeto['turma_id']);
        $dist = []; $soma = 0; $n = 0;
        foreach ($alunos as $a) {
            $b = $snap->boletimEfetivo($pid, (int) $a['id']);
            if ($b['conceito_final'] !== null && $b['conceito_final'] !== '') $dist[$b['conceito_final']] = ($dist[$b['conceito_final']] ?? 0) + 1;
            if ($b['media_ponderada'] !== null) { $soma += (float) $b['media_ponderada']; $n++; }
        }
        $ordem = ['MB' => 1, 'B' => 2, 'R' => 3, 'I' => 4];
        uksort($dist, fn($x, $y) => ($ordem[$x] ?? 9) <=> ($ordem[$y] ?? 9) ?: strcmp((string) $x, (string) $y));

        $orc = $q("SELECT (SELECT COALESCE(SUM(valor_previsto), 0) FROM projeto_orcamento_itens WHERE projeto_id = ?) previsto,
                          COALESCE(SUM(IF(tipo = 'entrada', valor, 0)), 0) arrecadado, COALESCE(SUM(IF(tipo = 'saida', valor, 0)), 0) gasto
                   FROM caixa_lancamentos WHERE projeto_id = ?", [$pid, $pid])[0];

        return [
            'turma'      => $turma,
            'etapas'     => $etapas,
            'criterios'  => $criterios,
            'grupos'     => $grupos,
            'distribuicao' => $dist,
            'media'      => $n ? round($soma / $n, 2) : null,
            'avaliados'  => $n,
            'alunos'     => count($alunos),
            'orcamento'  => array_map('floatval', $orc),
            'numeros'    => [
                'grupos'   => count($grupos),
                'alunos'   => count($alunos),
                'atas'     => array_sum(array_map(fn($g) => count($g['atas']), $grupos)),
                'tarefas'  => array_sum(array_column($grupos, 'tarefas_feitas')),
                'criterios'=> count($criterios),
            ],
        ];
    }
}
