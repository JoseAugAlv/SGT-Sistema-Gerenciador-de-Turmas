<?php
// app/Views/auth/_google.php
// Botão "Continuar com o Google". Opcional: $urlGoogle (padrão /auth/google), $textoGoogle.
require_once __DIR__ . '/../../Services/GoogleAuthService.php';
if (!GoogleAuthService::configurado()) return;
$urlGoogle   = $urlGoogle ?? ($basePath . '/auth/google');
$textoGoogle = $textoGoogle ?? 'Continuar com o Google';
?>
<a class="google-btn" href="<?= h($urlGoogle) ?>">
    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
    <span><?= h($textoGoogle) ?></span>
</a>
<div class="google-ou"><span>ou</span></div>
<style>
.google-btn { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px 16px; margin-bottom: 4px;
    border: 1.5px solid var(--border, #d9dee7); border-radius: 10px; background: #fff; color: #1f2937; font-weight: 600; text-decoration: none; transition: background .15s, border-color .15s; }
.google-btn:hover { background: #f7f8fa; border-color: #b9c2d0; }
.google-btn:focus-visible { outline: 2px solid var(--primary, #1c6fd6); outline-offset: 2px; }
.google-ou { display: flex; align-items: center; gap: 10px; margin: 14px 0; color: var(--muted, #6b7280); font-size: 13px; }
.google-ou::before, .google-ou::after { content: ''; flex: 1; height: 1px; background: var(--border, #d9dee7); }
</style>
