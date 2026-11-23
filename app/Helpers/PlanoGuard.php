<?php
// app/Helpers/PlanoGuard.php
// Faz o plano valer: diz a situação da turma e barra o acesso quando o plano falta, venceu ou está em atraso.
// Regras (precos.md): tolerância de N dias (config tolerancia_dias) e depois bloqueio. Master nunca é barrado.
require_once __DIR__ . '/../Models/Assinatura.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Services/PrecoService.php';
require_once __DIR__ . '/../Services/RenovacaoService.php';

class PlanoGuard
{
    private static array $cache = [];

    /**
     * Situação da turma: ['codigo', 'bloqueia' => bool, 'titulo', 'texto', 'assinatura' => array|null]
     * Códigos: isenta, ativa, vencendo, tolerancia, atraso (avisos) | sem_plano, aguardando, vencido, atraso_bloqueio (bloqueiam).
     */
    public static function situacao(array $turma): array
    {
        $tid = (int) $turma['id'];
        if (isset(self::$cache[$tid])) return self::$cache[$tid];
        $sit = self::calcular($turma);
        // perto do fim (ou na tolerância): cupom automático de renovação, uma vez por assinatura
        if (in_array($sit['codigo'], ['vencendo', 'tolerancia'], true)) {
            try { RenovacaoService::verificar($turma, $sit); } catch (\Throwable $e) { error_log('renovacao: ' . $e->getMessage()); }
        }
        return $sit;
    }

    private static function calcular(array $turma): array
    {
        $tid = (int) $turma['id'];

        $s = fn($codigo, $bloqueia, $titulo, $texto, $ass = null) =>
            ['codigo' => $codigo, 'bloqueia' => $bloqueia, 'titulo' => $titulo, 'texto' => $texto, 'assinatura' => $ass];

        if (PrecoService::config('exigir_plano', '1') !== '1' || !empty($turma['isenta_plano'])) {
            return self::$cache[$tid] = $s('isenta', false, '', '');
        }

        $tol   = (int) PrecoService::config('tolerancia_dias', '5');
        $aviso = (int) PrecoService::config('aviso_vencimento_dias', '7');
        $model = new Assinatura();
        $ass   = $model->atualDaTurma($tid);
        $hoje  = strtotime('today');

        if (!$ass) {
            return self::$cache[$tid] = $s('sem_plano', true, 'Turma sem plano',
                'Esta turma ainda não tem um plano ativo. O representante precisa escolher um plano para liberar o acesso.');
        }
        if ($ass['status'] === 'aguardando') {
            return self::$cache[$tid] = $s('aguardando', true, 'Aguardando o pagamento do plano',
                "O plano {$ass['plano_nome']} foi escolhido, mas a 1ª parcela ainda não foi paga.", $ass);
        }

        // Plano vencido
        if ($ass['fim'] && strtotime($ass['fim']) < time()) {
            $dias = (int) floor(($hoje - strtotime(date('Y-m-d', strtotime($ass['fim'])))) / 86400);
            if ($dias > $tol) {
                return self::$cache[$tid] = $s('vencido', true, 'Plano vencido',
                    "O plano {$ass['plano_nome']} venceu em " . date('d/m/Y', strtotime($ass['fim'])) . '. Renove para liberar a turma.', $ass);
            }
            return self::$cache[$tid] = $s('tolerancia', false, 'Plano vencido',
                'O plano venceu em ' . date('d/m/Y', strtotime($ass['fim'])) . '. A turma será bloqueada em ' . max(0, $tol - $dias) . ' dia(s) se não for renovado.', $ass);
        }

        // Parcela em atraso
        $prox = $model->proximaParcela((int) $ass['id']);
        if ($prox && strtotime($prox['vencimento']) < $hoje) {
            $dias = (int) floor(($hoje - strtotime($prox['vencimento'])) / 86400);
            $valor = 'R$ ' . number_format((float) $prox['valor'], 2, ',', '.');
            if ($dias > $tol) {
                return self::$cache[$tid] = $s('atraso_bloqueio', true, 'Parcela em atraso',
                    "A parcela {$prox['numero']} ({$valor}) venceu em " . date('d/m/Y', strtotime($prox['vencimento'])) . '. Pague para liberar a turma.', $ass);
            }
            return self::$cache[$tid] = $s('atraso', false, 'Parcela em atraso',
                "A parcela {$prox['numero']} ({$valor}) venceu em " . date('d/m/Y', strtotime($prox['vencimento'])) . '. A turma será bloqueada em ' . max(0, $tol - $dias) . ' dia(s).', $ass);
        }

        // Perto de vencer
        if ($ass['fim']) {
            $faltam = (int) floor((strtotime(date('Y-m-d', strtotime($ass['fim']))) - $hoje) / 86400);
            if ($faltam <= $aviso) {
                return self::$cache[$tid] = $s('vencendo', false, 'Plano perto de vencer',
                    "O plano {$ass['plano_nome']} vence em " . date('d/m/Y', strtotime($ass['fim'])) . " (faltam {$faltam} dia(s)).", $ass);
            }
        }
        return self::$cache[$tid] = $s('ativa', false, '', '', $ass);
    }

    /**
     * Barra o acesso a uma turma bloqueada pelo plano.
     * Representante vai para a tela do plano (para regularizar); os demais veem a página de bloqueio.
     */
    public static function exigir(array $turma, bool $paginaJaComecou = false): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u || $u['tipo'] === 'master') return;

        $sit = self::situacao($turma);
        if (!$sit['bloqueia']) return;

        // No meio de uma página já começada não dá para redirecionar: mostra o bloqueio ali mesmo
        if (!$paginaJaComecou && (new TurmaUsuario())->ehRepresentantePrincipal((int) $turma['id'], (int) $u['id'])) {
            $_SESSION['flash'] = ['tipo' => 'aviso', 'mensagem' => $sit['titulo'] . '. ' . $sit['texto']];
            header('Location: ' . App::getBasePath() . '/turmas/' . (int) $turma['id'] . '/plano');
            exit;
        }
        $situacaoPlano = $sit;
        $basePath = App::getBasePath();
        require __DIR__ . '/../Views/turmas/bloqueada_plano.php';
        exit;
    }

    /** Aviso (sem bloqueio) para o representante/master: vencendo, tolerância ou atraso. HTML pronto ou ''. */
    public static function aviso(array $turma): string
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) return '';
        $souGestor = $u['tipo'] === 'master' || (new TurmaUsuario())->ehRepresentantePrincipal((int) $turma['id'], (int) $u['id']);
        if (!$souGestor) return '';

        $sit = self::situacao($turma);
        $url = App::getBasePath() . '/turmas/' . (int) $turma['id'] . '/plano';
        if (!in_array($sit['codigo'], ['vencendo', 'tolerancia', 'atraso'], true)) {
            // teste grátis do Dia a dia acabando (3 dias ou menos)
            $t = self::testeDia($turma);
            if ($t['ativo'] && $t['faltam'] <= 3 && !self::temDiaPago($sit)) {
                return '<div class="notice notice-warning aviso-plano"><span><i class="fas fa-hourglass-end"></i></span><div><strong>Teste do Dia a dia acaba em '
                     . $t['faltam'] . ' dia' . ($t['faltam'] > 1 ? 's' : '') . '</strong><p>Depois disso a agenda, o horário e o aviso do dia ficam bloqueados. '
                     . '<a href="' . $url . '#dia-a-dia">Continuar com o Dia a dia</a></p></div></div>';
            }
            return '';
        }

        $classe = $sit['codigo'] === 'vencendo' ? 'notice-warning' : 'notice-danger';
        return '<div class="notice ' . $classe . ' aviso-plano"><span><i class="fas fa-receipt"></i></span><div><strong>'
             . htmlspecialchars($sit['titulo']) . '</strong><p>' . htmlspecialchars($sit['texto'])
             . ' <a href="' . $url . '">Ver plano</a></p></div></div>';
    }

    /**
     * A turma tem o "Dia a dia" (agenda, horário, aviso do dia e contagem)?
     * Sim quando: cobrança desligada, turma isenta, plano do Anual para cima, ou comprou o adicional.
     */
    public static function temDiaADia(array $turma): bool
    {
        $sit = self::situacao($turma);
        if ($sit['codigo'] === 'isenta') return true;
        if ($sit['bloqueia'] || empty($sit['assinatura'])) return false;
        $a = $sit['assinatura'];
        return !empty($a['inclui_dia_a_dia']) || !empty($a['dia_a_dia']) || self::testeDia($turma)['ativo'];
    }

    /**
     * Teste grátis do Dia a dia (uma vez por turma, config teste_dia_dias).
     * ['ativo' => bool, 'usado' => bool, 'ate' => 'Y-m-d H:i:s'|null, 'faltam' => dias, 'pode' => bool (pode começar agora), 'dias' => int]
     */
    public static function testeDia(array $turma): array
    {
        $dias = (int) PrecoService::config('teste_dia_dias', '14');
        $ate  = $turma['teste_dia_ate'] ?? null;
        if ($ate === null && !array_key_exists('teste_dia_ate', $turma)) {   // turma lida sem a coluna
            $st = Database::getConnection()->prepare("SELECT teste_dia_ate FROM turmas WHERE id = ?");
            $st->execute([(int) $turma['id']]);
            $ate = $st->fetchColumn() ?: null;
        }
        $ativo  = $ate && strtotime($ate) > time();
        $faltam = $ativo ? (int) ceil((strtotime($ate) - time()) / 86400) : 0;
        $sit = self::situacao($turma);
        $a   = $sit['assinatura'] ?? null;
        $jaTem = $sit['codigo'] === 'isenta' || ($a && (!empty($a['inclui_dia_a_dia']) || !empty($a['dia_a_dia'])));
        $pode = $dias > 0 && !$ate && !$jaTem && !$sit['bloqueia'] && $a && $a['status'] === 'ativa';
        return ['ativo' => (bool) $ativo, 'usado' => (bool) $ate, 'ate' => $ate, 'faltam' => $faltam, 'pode' => $pode, 'dias' => $dias];
    }

    /** O plano já inclui (ou comprou) o Dia a dia? */
    private static function temDiaPago(array $sit): bool
    {
        $a = $sit['assinatura'] ?? null;
        return $a && (!empty($a['inclui_dia_a_dia']) || !empty($a['dia_a_dia']));
    }

    /** O plano permite criar mais um projeto? Retorna null se pode, ou a mensagem de recusa. */
    public static function podeCriarProjeto(array $turma): ?string
    {
        $sit = self::situacao($turma);
        if ($sit['codigo'] === 'isenta') return null;
        if ($sit['bloqueia'] || !$sit['assinatura']) return 'A turma precisa de um plano ativo para criar projetos.';

        $limite = (int) $sit['assinatura']['projetos_limite'];
        $usados = (new Assinatura())->projetosUsados((int) $turma['id']);
        if ($usados >= $limite) {
            return "O plano {$sit['assinatura']['plano_nome']} permite {$limite} projeto(s), e a turma já tem {$usados}. Para mais projetos, troque de plano.";
        }
        return null;
    }
}
