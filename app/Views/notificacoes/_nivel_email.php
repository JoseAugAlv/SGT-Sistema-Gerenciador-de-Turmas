<?php
// app/Views/notificacoes/_nivel_email.php
// Escolha de quanto email receber (campo nivel_email). Funciona nas telas de login e nas internas.
// Espera opcional: $nivelEmail (valor atual; padrão 'importantes').
require_once __DIR__ . '/../../Models/PreferenciasUsuario.php';
$nivelAtual = $nivelEmail ?? 'importantes';
$icones = ['todas' => 'fa-envelopes-bulk', 'importantes' => 'fa-star', 'mencoes' => 'fa-at', 'nenhuma' => 'fa-bell-slash'];
?>
<fieldset class="nivel-email">
    <legend>Notificações por email</legend>
    <div class="nivel-email-opcoes">
        <?php foreach (PreferenciasUsuario::NIVEIS as $valor => [$rotulo, $desc]): ?>
            <label class="nivel-email-op">
                <input type="radio" name="nivel_email" value="<?= $valor ?>"<?= $nivelAtual === $valor ? ' checked' : '' ?>>
                <span class="nivel-email-card">
                    <i class="fas <?= $icones[$valor] ?>" aria-hidden="true"></i>
                    <strong><?= h($rotulo) ?></strong>
                    <small><?= h($desc) ?></small>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
    <small class="nivel-email-nota">Dentro do sistema você vê todas as notificações. Dá para mudar depois em Notificações › Preferências.</small>
</fieldset>
<style>
.nivel-email { border: 0; padding: 0; margin: 16px 0; min-width: 0; }
.nivel-email legend { font-weight: 700; font-size: 14px; margin-bottom: 8px; padding: 0; }
.nivel-email-opcoes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
.nivel-email-op { position: relative; cursor: pointer; }
.nivel-email-op input { position: absolute; opacity: 0; pointer-events: none; }
.nivel-email-card { display: grid; grid-template-columns: auto 1fr; column-gap: 8px; row-gap: 2px; align-items: center; height: 100%;
    padding: 10px 12px; border: 1.5px solid var(--border, #d9dee7); border-radius: 10px; background: var(--surface, #fff); transition: border-color .15s, background .15s; }
.nivel-email-card i { color: var(--muted, #6b7280); }
.nivel-email-card small { grid-column: 1 / -1; font-size: 12px; line-height: 1.35; color: var(--muted, #6b7280); }
.nivel-email-op input:checked + .nivel-email-card { border-color: var(--primary, #1c6fd6); background: color-mix(in srgb, var(--primary, #1c6fd6) 8%, transparent); }
.nivel-email-op input:checked + .nivel-email-card i { color: var(--primary, #1c6fd6); }
.nivel-email-op input:focus-visible + .nivel-email-card { outline: 2px solid var(--primary, #1c6fd6); outline-offset: 2px; }
.nivel-email-nota { display: block; margin-top: 6px; font-size: 12px; color: var(--muted, #6b7280); }
@media (max-width: 520px) { .nivel-email-opcoes { grid-template-columns: 1fr; } }
</style>
