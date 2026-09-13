<?php
// app/Views/home/dashboard.php

$tituloPagina = $tituloPagina ?? 'Dashboard - ' . App::getName();
$cssPagina = 'home.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';

$basePath = App::getBasePath();
$appName = App::getName();
?>

<section class="section-block animate-in">
    <div class="container">
        <h1><i class="fas fa-chart-pie"></i> Dashboard</h1>
        <p style="color: var(--color-text);">Visão geral do sistema <?= $appName ?></p>

        <div class="stats-grid" style="margin-top: 2rem;">
            <div class="stat-box">
                <strong><?= $totalUsuarios ?? 0 ?></strong>
                <p>Total de Usuários</p>
            </div>
            <div class="stat-box">
                <strong><?= $totalAtivos ?? 0 ?></strong>
                <p>Usuários Ativos</p>
            </div>
            <div class="stat-box">
                <strong><?= $primeiroAcessoPendentes ?? 0 ?></strong>
                <p>Primeiro Acesso Pendente</p>
            </div>
            <div class="stat-box">
                <strong><?= count($perfis ?? []) ?></strong>
                <p>Perfis</p>
            </div>
        </div>

        <!-- Gráfico de perfis -->
        <?php if (!empty($perfis)): ?>
        <div style="margin-top: 2rem; background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
            <h3>Distribuição por Perfil</h3>
            <canvas id="perfilChart" height="200"></canvas>
        </div>
        <?php endif; ?>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="<?= $basePath ?>/usuarios" class="btn btn-primary">
                <i class="fas fa-users-cog"></i> Gerenciar Usuários
            </a>
            <a href="<?= $basePath ?>/logs" class="btn">
                <i class="fas fa-history"></i> Ver Logs
            </a>
            <a href="<?= $basePath ?>/" class="btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</section>

<script>
<?php if (!empty($perfis)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('perfilChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($perfis, 'perfil')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($perfis, 'total')) ?>,
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>