<?php
// app/Helpers/PapelHelper.php
// Nomes dos papéis conforme o tipo: turma (Representante / Vice) ou grêmio (Presidente / Vice-presidente).
class PapelHelper
{
    public static function ehGremio(?array $turma): bool
    {
        return ($turma['tipo'] ?? 'turma') === 'gremio';
    }

    /** ['entidade', 'rep', 'vice', 'reps', 'membro'] */
    public static function rotulos(?array $turma): array
    {
        return self::ehGremio($turma)
            ? ['entidade' => 'Grêmio', 'rep' => 'Presidente', 'vice' => 'Vice-presidente', 'reps' => 'Presidência', 'membro' => 'Membro']
            : ['entidade' => 'Turma',  'rep' => 'Representante', 'vice' => 'Vice-representante', 'reps' => 'Representantes', 'membro' => 'Aluno'];
    }
}
