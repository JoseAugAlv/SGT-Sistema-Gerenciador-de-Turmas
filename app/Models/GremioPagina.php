<?php
// app/Models/GremioPagina.php
// Página pública do grêmio (/g/{slug}): configuração e os dados que ela mostra (sem nada sensível:
// nomes abreviados, sem emails; eventos só os da turma de tipo evento/reunião).
require_once __DIR__ . '/../Core/Model.php';

class GremioPagina extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'gremio_paginas';
    }

    public function daTurma(int $turmaId): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM gremio_paginas WHERE turma_id = ?");
        $st->execute([$turmaId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Página ativa pelo endereço, com os dados do grêmio. */
    public function porSlug(string $slug): ?array
    {
        $st = $this->conn->prepare("
            SELECT g.*, t.nome, t.logo, t.cor_primaria, t.cor_secundaria
            FROM gremio_paginas g INNER JOIN turmas t ON t.id = g.turma_id
            WHERE g.slug = ? AND g.ativa = 1 AND t.tipo = 'gremio' AND t.bloqueada = 0
        ");
        $st->execute([$slug]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function slugLivre(string $slug, int $turmaId): bool
    {
        $st = $this->conn->prepare("SELECT 1 FROM gremio_paginas WHERE slug = ? AND turma_id <> ?");
        $st->execute([$slug, $turmaId]);
        return !$st->fetchColumn();
    }

    public function salvar(int $turmaId, array $d): void
    {
        $this->conn->prepare("
            INSERT INTO gremio_paginas (turma_id, slug, ativa, apresentacao, instagram, mostrar_diretorias, mostrar_eventos, mostrar_conquistas, mostrar_contas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE slug = VALUES(slug), ativa = VALUES(ativa), apresentacao = VALUES(apresentacao), instagram = VALUES(instagram),
                mostrar_diretorias = VALUES(mostrar_diretorias), mostrar_eventos = VALUES(mostrar_eventos),
                mostrar_conquistas = VALUES(mostrar_conquistas), mostrar_contas = VALUES(mostrar_contas)
        ")->execute([$turmaId, $d['slug'], $d['ativa'], $d['apresentacao'], $d['instagram'], $d['mostrar_diretorias'], $d['mostrar_eventos'], $d['mostrar_conquistas'], $d['mostrar_contas']]);
    }

    /** "Davi Souza Lima" -> "Davi L." */
    public static function abreviar(string $nome): string
    {
        $p = preg_split('/\s+/', trim($nome));
        return count($p) > 1 ? $p[0] . ' ' . mb_strtoupper(mb_substr(end($p), 0, 1)) . '.' : ($p[0] ?? '');
    }

    /** Diretorias com os membros (nomes abreviados, cargo). */
    public function diretorias(int $turmaId): array
    {
        $st = $this->conn->prepare("SELECT id, nome, descricao FROM gremio_diretorias WHERE turma_id = ? ORDER BY nome");
        $st->execute([$turmaId]);
        $dirs = $st->fetchAll(PDO::FETCH_ASSOC);
        $m = $this->conn->prepare("SELECT u.nome, m.cargo FROM gremio_diretoria_membros m INNER JOIN usuarios u ON u.id = m.usuario_id
                                   WHERE m.diretoria_id = ? ORDER BY FIELD(m.cargo, 'diretor', 'coordenador', 'membro'), u.nome");
        foreach ($dirs as &$d) {
            $m->execute([$d['id']]);
            $d['membros'] = array_map(fn($r) => ['nome' => self::abreviar($r['nome']), 'cargo' => $r['cargo']], $m->fetchAll(PDO::FETCH_ASSOC));
        }
        return $dirs;
    }

    /** Presidente(s): representantes do grêmio, nomes abreviados. */
    public function presidencia(int $turmaId): array
    {
        $st = $this->conn->prepare("SELECT u.nome FROM turma_usuarios tu INNER JOIN usuarios u ON u.id = tu.usuario_id
                                    WHERE tu.turma_id = ? AND tu.papel = 'representante' AND tu.ativo = 1 ORDER BY tu.id");
        $st->execute([$turmaId]);
        return array_map(fn($n) => self::abreviar($n), $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Próximos eventos e reuniões gerais (sem diretoria) do grêmio. */
    public function eventos(int $turmaId): array
    {
        $st = $this->conn->prepare("
            (SELECT titulo, data, hora_inicio AS hora, local, tipo FROM eventos_calendario
              WHERE turma_id = ? AND visibilidade = 'turma' AND tipo IN ('evento','reuniao') AND data >= CURDATE())
            UNION ALL
            (SELECT titulo, DATE(data), TIME(data), local, 'reuniao' FROM reunioes
              WHERE turma_id = ? AND diretoria_id IS NULL AND status = 'agendada' AND data >= NOW())
            ORDER BY data, hora LIMIT 8
        ");
        $st->execute([$turmaId, $turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Conquistas aprovadas do mural (as fotos de pessoas ficam de fora). */
    public function conquistas(int $turmaId): array
    {
        $st = $this->conn->prepare("SELECT titulo, texto, icone, created_at FROM mural_posts WHERE turma_id = ? AND aprovado = 1 AND tipo = 'conquista' ORDER BY fixado DESC, created_at DESC LIMIT 9");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
