<?php
// app/Services/SnapshotService.php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/NotaService.php';
require_once __DIR__ . '/../Models/BoletimSnapshot.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';

class SnapshotService
{
    private PDO              $conn;
    private NotaService      $nota;
    private BoletimSnapshot  $snap;
    private TurmaUsuario     $tu;

    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->nota = new NotaService();
        $this->snap = new BoletimSnapshot();
        $this->tu   = new TurmaUsuario();
    }

    /**
     * Congela o boletim de todos os alunos da turma do projeto.
     * Retorna o número de snapshots gravados.
     */
    public function congelarBoletim(int $projetoId): int
    {
        $projeto = $this->buscarProjeto($projetoId);
        if (!$projeto) return 0;

        $alunos = $this->tu->listarAlunos((int) $projeto['turma_id']);
        $total  = 0;

        foreach ($alunos as $a) {
            $alunoId = (int) $a['id'];
            $boletim = $this->nota->boletimDoAluno($projetoId, $alunoId);

            // Serializa as linhas (criterio + nota + conceito)
            $dados = [];
            foreach ($boletim['linhas'] as $l) {
                $dados[] = [
                    'criterio_id'    => (int) $l['criterio']['id'],
                    'criterio_nome'  => $l['criterio']['nome'],
                    'tipo_avaliacao' => $l['criterio']['tipo_avaliacao'],
                    'peso'           => (float) $l['criterio']['peso'],
                    'nota'           => $l['nota'],
                    'conceito'       => $l['conceito'],
                ];
            }

            $this->snap->gravar(
                $projetoId,
                $alunoId,
                $dados,
                $boletim['media_ponderada'],
                $boletim['conceito_final']
            );
            $total++;
        }

        return $total;
    }

    /**
     * Retorna o boletim "efetivo" de um aluno:
     * - Se o projeto está encerrado E há snapshot → retorna o snapshot congelado
     * - Caso contrário → calcula em tempo real
     */
    public function boletimEfetivo(int $projetoId, int $alunoId): array
    {
        $projeto = $this->buscarProjeto($projetoId);
        if (!$projeto) return ['linhas' => [], 'media_ponderada' => null, 'conceito_final' => null, 'congelado' => false];

        if (!empty($projeto['encerrado'])) {
            $snap = $this->snap->porProjetoEAluno($projetoId, $alunoId);
            if ($snap) {
                // Reconstroi no formato do NotaService
                $linhas = [];
                foreach ($snap['dados'] as $d) {
                    $linhas[] = [
                        'criterio' => [
                            'id'             => $d['criterio_id'],
                            'nome'           => $d['criterio_nome'],
                            'tipo_avaliacao' => $d['tipo_avaliacao'],
                            'peso'           => $d['peso'],
                        ],
                        'nota'     => $d['nota'],
                        'conceito' => $d['conceito'],
                    ];
                }
                return [
                    'linhas'          => $linhas,
                    'media_ponderada' => $snap['media_geral'],
                    'conceito_final'  => $snap['conceito_geral'],
                    'congelado'       => true,
                    'congelado_em'    => $snap['created_at'],
                ];
            }
        }

        $live = $this->nota->boletimDoAluno($projetoId, $alunoId);
        $live['congelado'] = false;
        return $live;
    }

    private function buscarProjeto(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM projetos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}