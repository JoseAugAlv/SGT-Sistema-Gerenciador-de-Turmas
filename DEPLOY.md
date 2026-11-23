# Publicar o Prumo na Hostinger

Guia do que fazer no servidor. O código já está preparado para cada passo.

---

## 1. Atualizar o site pelo Git (em vez de enviar arquivo por arquivo)

**hPanel → Sites → seu site → Avançado → Git**

1. **Repositório:** `https://github.com/JoseAugAlv/SGT-Sistema-Gerenciador-de-Turmas.git` · **Branch:** `main`
2. **Diretório:** `public_html/projetos/Prumo` (a pasta precisa estar **vazia** na primeira vez; faça backup do `.env` e da pasta `public/uploads` antes e devolva depois)
3. Clique em **Criar** e depois em **Implantar**.
4. Para atualizar automaticamente a cada `git push`: na mesma tela, **Implantação automática** → copie a URL do webhook → no GitHub: *Settings → Webhooks → Add webhook* → cole a URL → *Just the push event*.

> Se o repositório for **privado**, a Hostinger mostra uma chave SSH: adicione em GitHub → *Settings → Deploy keys*.

**O que o Git não leva** (estão no `.gitignore` e ficam só no servidor):

| O quê | Por quê | Como fica no servidor |
|---|---|---|
| `.env` | senhas e chaves | crie uma vez (veja o passo 2) |
| `vendor/` | bibliotecas (PHPMailer, TCPDF), 160 MB | envie **uma vez** compactado: no seu PC, zipe a pasta `vendor`, envie pelo Gerenciador de Arquivos para `projetos/Prumo/` e extraia. Só precisa repetir se mudar o `composer.json` |
| `public/uploads/` | arquivos enviados pelos usuários | ficam no servidor, o Git não mexe |
| `logs/`, `backups/` | gerados pelo sistema | criados sozinhos |

## 2. `.env` do servidor

```ini
APP_NAME=Prumo
APP_ENV=production
APP_URL=https://josealvesdev.com/projetos/Prumo
APP_BASE_PATH=/projetos/Prumo
DB_HOST=localhost
DB_NAME=...            # hPanel → Bancos de dados
DB_USER=...
DB_PASS=...
MAIL_HOST=...          # veja o passo 5
MAIL_PORT=...
MAIL_USER=...
MAIL_PASS=...
MAIL_FROM=...
MAIL_FROM_NAME=Prumo
APP_KEY=...            # php -r "echo bin2hex(random_bytes(32));"
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

Confira depois: `https://josealvesdev.com/projetos/Prumo/.env` precisa dar **não encontrado**.

## 3. Banco

- **Zerar:** phpMyAdmin → banco → aba **SQL** → cole `SGT_apagar_tabelas.sql` → Executar.
- **Criar tudo vazio:** aba **Importar** → `SGT_producao.sql`.
- **Primeiro acesso:** entre com `jose@adm.com` / `Teste@1234`. O sistema **obriga a trocar a senha** na hora.
- **Migrações novas** (quando o banco já tem dados): importe só os arquivos novos da pasta `database/` (ex.: `2026-11-24_lembrar_login.sql`).

## 4. Tarefas agendadas (Cron)

**hPanel → Avançado → Cron Jobs → Personalizado.** Troque `USUARIO` e o caminho pelo que aparece no Gerenciador de Arquivos.

| Tarefa | Quando | Comando |
|---|---|---|
| Lembretes da agenda, aviso de plano vencendo, limpeza | todo dia às 7h (`0 7 * * *`) | `php /home/USUARIO/domains/josealvesdev.com/public_html/projetos/Prumo/scripts/tarefas_diarias.php` |
| Resumo semanal por email | segunda às 7h (`0 7 * * 1`) | `php /home/USUARIO/domains/josealvesdev.com/public_html/projetos/Prumo/scripts/resumo_semanal.php` |
| Backup do banco (guarda os 8 últimos) | domingo às 3h (`0 3 * * 0`) | `php /home/USUARIO/domains/josealvesdev.com/public_html/projetos/Prumo/scripts/backup_banco.php` |

Os backups ficam em `projetos/Prumo/backups/` (fora do alcance do navegador). Para restaurar: phpMyAdmin → Importar → o `.sql.gz`.
Vale ligar também o **backup automático da Hostinger** (hPanel → Arquivos → Backups), se o seu plano tiver.

## 5. Email que chega (e não cai no spam)

O Gmail limita a ~500 emails por dia e costuma cair no spam quando sai de um servidor que não é do Google. Opções (troque só no `.env`; detalhes no `.env.example`):

- **Email da Hostinger** (`contato@josealvesdev.com`): hPanel → Emails → crie a caixa. Use `smtp.hostinger.com`, porta `465`. Melhor entrega, porque o domínio é seu.
- **Brevo** (grátis, 300/dia) ou **Resend** (grátis, 3.000/mês): bons para volume. Verifique o domínio no painel deles (eles mostram os registros DNS para colar no hPanel → DNS).

Teste depois: crie uma conta nova e veja se o email de confirmação chega na caixa de entrada.

## 6. Google e Mercado Pago

- **Google Cloud → Credenciais → cliente OAuth → URIs de redirecionamento:** `https://josealvesdev.com/projetos/Prumo/auth/google/callback` (com **P maiúsculo**).
- **Mercado Pago → Webhooks:** `https://josealvesdev.com/projetos/Prumo/webhooks/mercadopago`.

## 7. Depois de publicar

- **Erros:** entre como master → menu **Erros do sistema**. O usuário só vê uma página amigável; o detalhe aparece ali.
- **Google Search Console:** <https://search.google.com/search-console> → adicionar propriedade com o prefixo `https://josealvesdev.com/projetos/Prumo/` → confirmar → **Inspeção de URL** → *Solicitar indexação*.
- **Prévia no WhatsApp:** cole o link numa conversa; deve aparecer a imagem do Prumo. Se aparecer a antiga, use <https://developers.facebook.com/tools/debug/> → *Coletar novamente*.
