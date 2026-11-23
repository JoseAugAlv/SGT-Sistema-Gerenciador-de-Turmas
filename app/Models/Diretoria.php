<?php
// app/Models/Diretoria.php
// Diretorias / coordenações do grêmio (criadas pelo presidente), com diretor, coordenadores e membros.
require_once __DIR__ . '/../Core/Model.php';

class Diretoria extends Model
{
    const CARGOS = ['diretor' => 'Diretor(a)', 'coordenador' => 'Coordenador(a)', 'membro' => 'Membro'];
    // Sugestões para começar rápido (o presidente pode criar qualquer outra)
    const SUGESTOES = ['Presidência', 'Secretaria', 'Finanças', 'Comunicação', 'Cultura', 'Esportes', 'Eventos', 'Social', 'Meio ambiente'];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'gremio_diretorias';
    }

    /** Diretorias do grêmio com os membros de cada uma (diretor primeiro). */
    public function listar(int $turmaId): array
    {
        $st = $this->conn->prepare("SELECT * FROM gremio_diretorias WHERE turma_id = ? ORDER BY nome");
        $st->execute([$turmaId]);
        $dirs = $st->fetchAll(PDO::FETCH_ASSOC);
        $m = $this->conn->prepare("
            SELECT m.*, u.nome FROM gremio_diretoria_membros m INNER JOIN usuarios u ON u.id = m.usuario_id
            WHERE m.diretoria_id = ? ORDER BY FIELD(m.cargo, 'diretor', 'coordenador', 'membro'), u.nome
        ");
        foreach ($dirs as &$d) { $m->execute([(int) $d['id']]); $d['membros'] = $m->fetchAll(PDO::FETCH_ASSOC); }
        return $dirs;
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM gremio_diretorias WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(int $turmaId, string $nome, ?string $descricao, int $porId): int
    {
        $this->conn->prepare("INSERT INTO gremio_diretorias (turma_id, nome, descricao, criado_por) VALUES (?, ?, ?, ?)")
                   ->execute([$turmaId, $nome, $descricao, $porId]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, string $nome, ?string $descricao): void
    {
        $this->conn->prepare("UPDATE gremio_diretorias SET nome = ?, descricao = ? WHERE id = ?")->execute([$nome, $descricao, $id]);
    }

    public function excluir(int $id): void
    {
        $this->conn->prepare("DELETE FROM gremio_diretorias WHERE id = ?")->execute([$id]);
    }

    /** Coloca (ou muda o cargo de) alguém na diretoria. Só um diretor por diretoria: o anterior vira coordenador. */
    public function definirMembro(int $diretoriaId, int $usuarioId, string $cargo): void
    {
        if (!isset(self::CARGOS[$cargo])) $cargo = 'membro';
        if ($cargo === 'diretor') {
            $this->conn->prepare("UPDATE gremio_diretoria_membros SET cargo = 'coordenador' WHERE diretoria_id = ? AND cargo = 'diretor' AND usuario_id <> ?")
                       ->execute([$diretoriaId, $usuarioId]);
        }
        $this->conn->prepare("
            INSERT INTO gremio_diretoria_membros (diretoria_id, usuario_id, cargo) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE cargo = VALUES(cargo)
        ")->execute([$diretoriaId, $usuarioId, $cargo]);
    }

    public function removerMembro(int $diretoriaId, int $usuarioId): void
    {
        $this->conn->prepare("DELETE FROM gremio_diretoria_membros WHERE diretoria_id = ? AND usuario_id = ?")->execute([$diretoriaId, $usuarioId]);
    }

    /** Cargo do usuário na diretoria (ou null). */
    public function cargoDe(int $diretoriaId, int $usuarioId): ?string
    {
        $st = $this->conn->prepare("SELECT cargo FROM gremio_diretoria_membros WHERE diretoria_id = ? AND usuario_id = ?");
        $st->execute([$diretoriaId, $usuarioId]);
        return $st->fetchColumn() ?: null;
    }

    /** Diretorias em que o usuário é diretor ou coordenador (pode marcar reuniões delas). */
    public function queLidero(int $turmaId, int $usuarioId): array
    {
        $st = $this->conn->prepare("
            SELECT d.id, d.nome FROM gremio_diretorias d INNER JOIN gremio_diretoria_membros m ON m.diretoria_id = d.id
            WHERE d.turma_id = ? AND m.usuario_id = ? AND m.cargo IN ('diretor', 'coordenador') ORDER BY d.nome
        ");
        $st->execute([$turmaId, $usuarioId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
