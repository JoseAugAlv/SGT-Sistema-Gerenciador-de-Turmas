<?php
// routes/web.php — Rotas do Prumo
// Organizado por seção. Cole e execute. Não há rotas duplicadas.

require_once __DIR__ . '/../app/Helpers/menuHelper.php';

// Carrega configurações de módulos (opcional)
$modulesConfig = __DIR__ . '/../app/Config/modules.php';
if (file_exists($modulesConfig)) {
    $modules = require $modulesConfig;
    $modulos = $modules['modules'] ?? [];
} else {
    $modulos = [];
}

// ═══════════════════════════════════════════════════════════════
// PÚBLICAS
// ═══════════════════════════════════════════════════════════════
$router->get('/',         'HomeController@index');
$router->get('/sobre',    'SobreController@index');
$router->get('/termos',   'TermosController@index');
$router->get('/lgpd',     'LgpdController@index');

// ═══════════════════════════════════════════════════════════════
// AUTH
// ═══════════════════════════════════════════════════════════════
$router->get ('/login',           'AuthController@loginForm');
$router->post('/login',           'AuthController@login');
$router->get ('/logout',          'AuthController@logout');

$router->get ('/login/cadastrar', 'AuthController@cadastroForm');
$router->post('/login/salvar',    'AuthController@salvarCadastro');

$router->get ('/auth/confirmar-email',          'AuthController@confirmarEmail');
$router->post('/auth/reenviar-confirmacao',     'AuthController@reenviarConfirmacao');
$router->get ('/auth/confirmar-email-pendente', 'AuthController@confirmarEmailPendente');

$router->get ('/auth/esqueci-senha',    'AuthController@esqueciSenhaForm');
$router->post('/auth/enviar-token',     'AuthController@enviarToken');
$router->get ('/auth/redefinir',        'AuthController@redefinirForm');
$router->post('/auth/redefinir-senha',  'AuthController@redefinirSenha');

$router->get ('/primeiro-acesso', 'AuthController@primeiroAcessoForm');
$router->post('/primeiro-acesso', 'AuthController@completarPrimeiroAcesso');

// ═══════════════════════════════════════════════════════════════
// ÁREA DO USUÁRIO (própria conta)
// ═══════════════════════════════════════════════════════════════
$router->get ('/user',           'UserController@index',     ['master', 'aluno']);
$router->get ('/user/editar',    'UserController@editar',    ['master', 'aluno']);
$router->post('/user/atualizar', 'UserController@atualizar', ['master', 'aluno']);
$router->get ('/user/senha',     'UserController@senha',     ['master', 'aluno']);
$router->post('/user/senha',     'UserController@salvarSenha', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// LGPD — ÁREA DO TITULAR
// ═══════════════════════════════════════════════════════════════
$router->get ('/lgpd/meus-direitos',          'LgpdController@meusDireitos',      ['master', 'aluno']);
$router->post('/lgpd/exportar',               'LgpdController@exportar',          ['master', 'aluno']);
$router->get ('/lgpd/exportacao/{id}/baixar', 'LgpdController@baixarExportacao',  ['master', 'aluno']);
$router->post('/lgpd/solicitar-exclusao',     'LgpdController@solicitarExclusao', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// NOTIFICAÇÕES
// ═══════════════════════════════════════════════════════════════
$router->get ('/notificacoes',                     'NotificacaoController@index',             ['master', 'aluno']);
$router->post('/notificacoes/marcar-lida',         'NotificacaoController@marcarLida',        ['master', 'aluno']);
$router->post('/notificacoes/marcar-todas-lidas',  'NotificacaoController@marcarTodasLidas',  ['master', 'aluno']);
$router->get ('/notificacoes/contador',            'NotificacaoController@contador',          ['master', 'aluno']);
$router->get ('/notificacoes/dropdown',            'NotificacaoController@dropdown',          ['master', 'aluno']);
$router->get ('/notificacoes/preferencias',        'NotificacaoController@preferencias',      ['master', 'aluno']);
$router->post('/notificacoes/preferencias/salvar', 'NotificacaoController@salvarPreferencias', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// TURMAS
// ═══════════════════════════════════════════════════════════════
$router->get ('/turmas',                        'TurmaController@index',              ['master', 'aluno']);
$router->get ('/turmas/criar',                  'TurmaController@criarForm',          ['master']);
$router->post('/turmas/salvar',                 'TurmaController@salvar',             ['master']);
$router->get ('/turmas/entrar',                 'TurmaController@entrarForm',         ['master', 'aluno']);
$router->post('/turmas/entrar',                 'TurmaController@entrar',             ['master', 'aluno']);
$router->get ('/turmas/{id}',                   'TurmaController@detalhe',            ['master', 'aluno']);
$router->get ('/turmas/{id}/editar',            'TurmaController@editarForm',         ['master']);
$router->post('/turmas/{id}/atualizar',         'TurmaController@atualizar',          ['master']);
$router->get ('/turmas/{id}/excluir',           'TurmaController@excluirForm',        ['master']);
$router->post('/turmas/{id}/excluir',           'TurmaController@excluir',            ['master']);
$router->post('/turmas/{id}/bloquear',          'TurmaController@bloquear',           ['master']);
$router->post('/turmas/{id}/desbloquear',       'TurmaController@desbloquear',        ['master']);
$router->post('/turmas/{id}/regenerar-codigo',  'TurmaController@regenerarCodigo',    ['master', 'aluno']);
$router->get ('/turmas/{id}/identidade',          'TurmaController@identidadeForm',     ['master', 'aluno']);
$router->post('/turmas/{id}/identidade/salvar',   'TurmaController@salvarIdentidade',   ['master', 'aluno']);

// Representantes da turma
$router->get ('/turmas/{id}/representantes',         'TurmaController@representantesForm',   ['master']);
$router->post('/turmas/{id}/representantes/nomear',  'TurmaController@nomearRepresentante',  ['master']);
$router->post('/turmas/{id}/representantes/remover', 'TurmaController@removerRepresentante', ['master']);

// Alertas da turma
$router->get ('/turmas/{id}/alertas',        'AlertaController@index',     ['master', 'aluno']);
$router->get ('/turmas/{id}/alertas/criar',  'AlertaController@criarForm', ['master', 'aluno']);
$router->post('/turmas/{id}/alertas/salvar', 'AlertaController@salvar',    ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// CONFIGURAÇÕES DE TURMA (cursos e períodos)
// ═══════════════════════════════════════════════════════════════
$router->get ('/configuracoes/turmas',                     'ConfiguracaoTurmaController@index',         ['master']);
$router->post('/configuracoes/turmas/curso/salvar',        'ConfiguracaoTurmaController@salvarCurso',   ['master']);
$router->post('/configuracoes/turmas/curso/toggle/{id}',   'ConfiguracaoTurmaController@toggleCurso',   ['master']);
$router->post('/configuracoes/turmas/periodo/salvar',      'ConfiguracaoTurmaController@salvarPeriodo', ['master']);
$router->post('/configuracoes/turmas/periodo/toggle/{id}', 'ConfiguracaoTurmaController@togglePeriodo', ['master']);

// ═══════════════════════════════════════════════════════════════
// ALUNOS
// ═══════════════════════════════════════════════════════════════
$router->get ('/alunos',                     'AlunoController@index',          ['master', 'aluno']);
$router->get ('/alunos/criar',               'AlunoController@criar',          ['master', 'aluno']);
$router->post('/alunos/salvar',              'AlunoController@salvar',         ['master', 'aluno']);
$router->get ('/alunos/massa',               'AlunoController@massa',          ['master', 'aluno']);
$router->post('/alunos/massa/salvar',        'AlunoController@massaSalvar',    ['master', 'aluno']);
$router->get ('/alunos/massa/resultado',     'AlunoController@massaResultado', ['master', 'aluno']);
$router->post('/alunos/{id}/alternar-ativo', 'AlunoController@alternarAtivo',  ['master', 'aluno']);
$router->get ('/alunos/{id}/editar',         'AlunoController@editarForm',     ['master', 'aluno']);
$router->post('/alunos/{id}/atualizar',      'AlunoController@atualizar',      ['master', 'aluno']);
$router->get ('/alunos/{id}/grupos',         'AlunoController@grupos',         ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// PROJETOS
// ═══════════════════════════════════════════════════════════════
$router->get ('/projetos',                'ProjetoController@index',       ['master', 'aluno']);
$router->get ('/projetos/criar',          'ProjetoController@criarForm',   ['master']);
$router->post('/projetos/salvar',         'ProjetoController@salvar',      ['master']);
$router->get ('/projetos/{id}',           'ProjetoController@detalhe',     ['master', 'aluno']);
$router->get ('/projetos/{id}/editar',    'ProjetoController@editarForm',  ['master']);
$router->post('/projetos/{id}/atualizar', 'ProjetoController@atualizar',   ['master']);
$router->get ('/projetos/{id}/encerrar',  'ProjetoController@encerrarForm', ['master', 'aluno']);
$router->post('/projetos/{id}/encerrar',  'ProjetoController@encerrar',     ['master', 'aluno']);

// Abas do projeto
$router->get('/projetos/{id}/grupos',     'GrupoController@index',      ['master', 'aluno']);
$router->get('/projetos/{id}/criterios',  'CriterioController@index',   ['master', 'aluno']);
$router->get('/projetos/{id}/conceitos',  'ConceitoController@index',   ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios', 'ProjetoController@relatorios', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// ETAPAS DO PROJETO
// ═══════════════════════════════════════════════════════════════
$router->post('/projetos/{id}/etapas/criar', 'EtapaController@criar',      ['master', 'aluno']);
$router->get ('/etapas/{id}/editar',         'EtapaController@editarForm', ['master', 'aluno']);
$router->post('/etapas/{id}/atualizar',      'EtapaController@atualizar',  ['master', 'aluno']);
$router->post('/etapas/{id}/excluir',        'EtapaController@excluir',    ['master', 'aluno']);
$router->post('/etapas/{id}/mover',          'EtapaController@mover',      ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// CONCEITOS
// ═══════════════════════════════════════════════════════════════
$router->post('/projetos/{id}/conceitos/salvar', 'ConceitoController@salvar', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// GRUPOS
// ═══════════════════════════════════════════════════════════════
$router->get ('/projetos/{id}/grupos/criar',    'GrupoController@criarForm',      ['master', 'aluno']);
$router->post('/projetos/{id}/grupos/salvar',   'GrupoController@salvar',         ['master', 'aluno']);
$router->get ('/grupos/{id}',                   'GrupoController@detalhe',        ['master', 'aluno']);
$router->get ('/grupos/{id}/editar',            'GrupoController@editarForm',     ['master', 'aluno']);
$router->post('/grupos/{id}/atualizar',         'GrupoController@atualizar',      ['master', 'aluno']);
$router->post('/grupos/{id}/excluir',           'GrupoController@excluir',        ['master', 'aluno']);
$router->post('/grupos/{id}/membros/adicionar', 'GrupoController@adicionarMembro',['master', 'aluno']);
$router->post('/grupos/{id}/membros/remover',   'GrupoController@removerMembro',  ['master', 'aluno']);

// Configuração de avaliação do grupo
$router->get ('/grupos/{id}/config-avaliacao', 'GrupoController@configAvaliacaoForm',   ['master', 'aluno']);
$router->post('/grupos/{id}/config-avaliacao', 'GrupoController@configAvaliacaoSalvar', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// DIRETORES (grupo)
// ═══════════════════════════════════════════════════════════════
$router->get ('/diretor/grupos',                  'DiretorController@index',       ['master', 'aluno']);
$router->get ('/grupos/{id}/diretores',           'DiretorController@gerenciar',   ['master', 'aluno']);
$router->get ('/grupos/{id}/diretores/historico', 'DiretorController@historico',   ['master', 'aluno']);
$router->post('/grupos/{id}/diretores/nomear',    'DiretorController@nomear',      ['master', 'aluno']);
$router->post('/grupos/{id}/diretores/remover',   'DiretorController@remover',     ['master', 'aluno']);
$router->get ('/grupos/{id}/diretores/promover',  'DiretorController@promover',    ['master', 'aluno']);
$router->get ('/grupos/{id}/diretores/trocar',    'DiretorController@trocarForm',  ['master', 'aluno']);
$router->post('/grupos/{id}/diretores/trocar',    'DiretorController@trocar',      ['master', 'aluno']);
$router->get ('/grupos/{id}/diretores/alocar',    'DiretorController@alocarForm',  ['master', 'aluno']);
$router->post('/grupos/{id}/diretores/alocar',    'DiretorController@alocar',      ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// CRITÉRIOS
// ═══════════════════════════════════════════════════════════════
$router->get ('/projetos/{id}/criterios/criar',  'CriterioController@criarForm', ['master', 'aluno']);
$router->post('/projetos/{id}/criterios/salvar', 'CriterioController@salvar',    ['master', 'aluno']);
$router->get ('/criterios/{id}/editar',          'CriterioController@editarForm', ['master', 'aluno']);
$router->post('/criterios/{id}/atualizar',       'CriterioController@atualizar',  ['master', 'aluno']);
$router->post('/criterios/{id}/excluir',         'CriterioController@excluir',    ['master', 'aluno']);
$router->get ('/criterios/{id}/reabrir',         'CriterioController@reabrirForm',['master', 'aluno']);
$router->post('/criterios/{id}/reabrir',         'CriterioController@reabrir',    ['master', 'aluno']);
$router->post('/criterios/{id}/descontar',       'CriterioController@descontar',  ['master', 'aluno']);
$router->post('/criterios/{id}/remover-desconto','CriterioController@removerDesconto', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// AVALIAÇÕES
// ═══════════════════════════════════════════════════════════════
$router->get ('/projetos/{id}/avaliacoes', 'AvaliacaoController@index', ['master', 'aluno']);

// Representante (em massa)
$router->get ('/projetos/{id}/avaliacoes/representante',        'AvaliacaoController@bulkRepresentante',       ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/representante/salvar', 'AvaliacaoController@bulkRepresentanteSalvar', ['master', 'aluno']);

// Diretor
$router->get ('/projetos/{id}/avaliacoes/diretor',        'AvaliacaoController@diretor',       ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/diretor/salvar', 'AvaliacaoController@diretorSalvar', ['master', 'aluno']);

// Pares
$router->get ('/projetos/{id}/avaliacoes/pares',        'AvaliacaoController@pares',       ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/pares/salvar', 'AvaliacaoController@paresSalvar', ['master', 'aluno']);

// Autoavaliação
$router->get ('/projetos/{id}/avaliacoes/auto',        'AvaliacaoController@auto',       ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/auto/salvar', 'AvaliacaoController@autoSalvar', ['master', 'aluno']);

// Coletiva
$router->get ('/projetos/{id}/avaliacoes/coletiva',        'AvaliacaoController@coletiva',       ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/coletiva/salvar', 'AvaliacaoController@coletivaSalvar', ['master', 'aluno']);

// Extras
$router->get('/projetos/{id}/avaliacoes/geral',   'AvaliacaoController@indexAluno',   ['master', 'aluno']);
$router->get('/projetos/{id}/avaliacoes/parcial', 'AvaliacaoController@parcial',      ['master', 'aluno']);
$router->get('/projetos/{id}/avaliacoes/minhas',  'AvaliacaoController@minhasNotas',  ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// RELATÓRIOS
// ═══════════════════════════════════════════════════════════════
$router->get('/projetos/{id}/relatorios/boletim',     'RelatorioController@boletimAluno',    ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios/boletim/pdf', 'RelatorioController@boletimAlunoPdf', ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios/geral',       'RelatorioController@geralProjeto',    ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios/geral/pdf',   'RelatorioController@geralProjetoPdf', ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios/geral/excel', 'RelatorioController@geralProjetoExcel', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// ATAS
// ═══════════════════════════════════════════════════════════════
$router->get ('/projetos/{id}/atas',        'AtaController@index',      ['master', 'aluno']);
$router->get ('/projetos/{id}/atas/criar',  'AtaController@criarForm',  ['master', 'aluno']);
$router->post('/projetos/{id}/atas/salvar', 'AtaController@salvar',     ['master', 'aluno']);

$router->get ('/minhas-atividades',      'AtaController@minhasAtividades', ['master', 'aluno']);
$router->get ('/atas/{id}',              'AtaController@detalhe',  ['master', 'aluno']);
$router->get ('/atas/{id}/editar',       'AtaController@editarForm', ['master', 'aluno']);
$router->post('/atas/{id}/atualizar',    'AtaController@atualizar',  ['master', 'aluno']);
$router->post('/atas/{id}/excluir',      'AtaController@excluir',    ['master', 'aluno']);
$router->post('/atas/{id}/validar',      'AtaController@validar',    ['master', 'aluno']);
$router->post('/atas/{id}/reabrir',      'AtaController@reabrir',    ['master', 'aluno']);
$router->post('/atas/{id}/finalizar',    'AtaController@finalizar',  ['master', 'aluno']);

// Views focadas
$router->get ('/atas/{id}/preencher',    'AtaController@preencher',    ['master', 'aluno']);
$router->get ('/atas/{id}/validar-form', 'AtaController@validarForm',  ['master', 'aluno']);

// Atividades
$router->post('/atas/{id}/atividade',     'AtaController@adicionarAtividade', ['master', 'aluno']);
$router->post('/atividades/{id}/excluir', 'AtaController@excluirAtividade',   ['master', 'aluno']);

// Relatórios internos
$router->post('/atas/{id}/relatorio',     'AtaController@adicionarRelatorio', ['master', 'aluno']);
$router->post('/relatorios/{id}/excluir', 'AtaController@excluirRelatorio',   ['master', 'aluno']);

// Participantes
$router->post('/atas/{id}/participantes', 'AtaController@salvarParticipantes', ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// MATERIAIS
// ═══════════════════════════════════════════════════════════════
$router->get ('/projetos/{id}/materiais',               'MaterialController@index',          ['master', 'aluno']);
$router->get ('/projetos/{id}/materiais/cadastrar',     'MaterialController@cadastrarForm',  ['master', 'aluno']);
$router->post('/projetos/{id}/materiais/salvar',        'MaterialController@salvarCadastro', ['master', 'aluno']);
$router->get ('/projetos/{id}/materiais/movimentacoes', 'MaterialController@movimentacoes',  ['master', 'aluno']);

$router->get ('/materiais/{id}/usar',     'MaterialController@usarForm',   ['master', 'aluno']);
$router->post('/materiais/{id}/usar',     'MaterialController@usar',       ['master', 'aluno']);
$router->get ('/materiais/{id}/comprar',  'MaterialController@comprarForm',['master', 'aluno']);
$router->post('/materiais/{id}/comprar',  'MaterialController@comprar',    ['master', 'aluno']);
$router->post('/materiais/{id}/excluir',  'MaterialController@excluir',    ['master', 'aluno']);

// ═══════════════════════════════════════════════════════════════
// SOLICITAÇÕES DE MATERIAL
// ═══════════════════════════════════════════════════════════════
$router->get ('/projetos/{id}/materiais/solicitar',    'MaterialController@solicitarForm',    ['master', 'aluno']);
$router->post('/projetos/{id}/materiais/solicitar',    'MaterialController@solicitar',        ['master', 'aluno']);
$router->get ('/projetos/{id}/materiais/solicitacoes', 'MaterialController@solicitacoes',     ['master', 'aluno']);

$router->post('/solicitacoes/{id}/aprovar',  'MaterialController@aprovarSolicitacao',    ['master', 'aluno']);
$router->post('/solicitacoes/{id}/recusar',  'MaterialController@recusarSolicitacao',    ['master', 'aluno']);
$router->get ('/solicitacoes/{id}/comprar',  'MaterialController@comprarSolicitacaoForm',['master', 'aluno']);
$router->post('/solicitacoes/{id}/comprar',  'MaterialController@comprarSolicitacao',    ['master', 'aluno']);
$router->post('/solicitacoes/{id}/cancelar', 'MaterialController@cancelarSolicitacao',   ['master', 'aluno']);
$router->post('/solicitacoes/{id}/excluir',  'MaterialController@excluirSolicitacao',    ['master']);

// ═══════════════════════════════════════════════════════════════
// MASTER
// ═══════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════
// PLANOS E PAGAMENTOS (modo de teste: pagamento simulado)
// ═══════════════════════════════════════════════════════════════
$router->get ('/turmas/{id}/plano',             'PlanoController@turma',    ['master', 'aluno']);
$router->post('/turmas/{id}/plano/assinar',     'PlanoController@assinar',  ['master', 'aluno']);
$router->get ('/pagamentos/{id}',               'PlanoController@pix',      ['master', 'aluno']);
$router->post('/pagamentos/{id}/simular',       'PlanoController@simular',  ['master', 'aluno']);
$router->post('/assinaturas/{id}/cancelar',     'PlanoController@cancelar', ['master', 'aluno']);

$router->get ('/master/comercial',                        'ComercialController@index',         ['master']);
$router->post('/master/comercial/config',                 'ComercialController@salvarConfig',  ['master']);
$router->post('/master/comercial/cupons',                 'ComercialController@criarCupom',    ['master']);
$router->post('/master/comercial/cupons/{id}/alternar',   'ComercialController@alternarCupom', ['master']);

$router->get ('/master',                       'MasterController@index',               ['master']);
$router->get ('/master/auditoria',             'MasterController@auditoria',           ['master']);
$router->get ('/master/auditoria/{id}',        'MasterController@auditoriaDetalhe',    ['master']);
$router->get ('/master/criterios-arquivados',  'MasterController@criteriosArquivados', ['master']);
$router->post('/master/criterios-arquivados/{id}/restaurar', 'MasterController@restaurarCriterio', ['master']);
$router->get ('/master/lgpd',                  'MasterController@lgpdSolicitacoes',    ['master']);
$router->post('/master/lgpd/{id}/aprovar',     'MasterController@aprovarLgpd',         ['master']);
$router->post('/master/lgpd/{id}/negar',       'MasterController@negarLgpd',           ['master']);
$router->get ('/master/backup',                'MasterController@backup',              ['master']);
$router->post('/master/backup/gerar',          'MasterController@gerarBackup',         ['master']);
$router->get ('/master/backup/{nome}/baixar',  'MasterController@baixarBackup',        ['master']);
$router->post('/master/backup/{nome}/excluir', 'MasterController@excluirBackup',       ['master']);
$router->get ('/master/configuracoes',         'MasterController@configuracoes',       ['master']);
$router->get ('/master/usuarios',              'MasterController@usuarios',            ['master']);

// ═══════════════════════════════════════════════════════════════
// USUÁRIOS (gestão pelo master)
// ═══════════════════════════════════════════════════════════════
$router->get ('/usuarios',                       'UsuarioController@index',               ['master']);
$router->get ('/usuarios/criar',                 'UsuarioController@criar',               ['master']);
$router->post('/usuarios/salvar',                'UsuarioController@salvar',              ['master']);
$router->get ('/usuarios/editar',                'UsuarioController@editar',              ['master']);
$router->post('/usuarios/atualizar',             'UsuarioController@atualizar',           ['master']);
$router->get ('/usuarios/excluir',               'UsuarioController@excluir',             ['master']);
$router->post('/usuarios/atribuir-perfil',       'UsuarioController@atribuirPerfilMaster', ['master']);
$router->post('/usuarios/atribuir-perfil-admin', 'UsuarioController@atribuirPerfilAdmin',  ['master']);

// Representante (novas rotas)
$router->post('/usuarios/tornar-representante',  'UsuarioController@tornarRepresentante',  ['master']);
$router->post('/usuarios/remover-representante', 'UsuarioController@removerRepresentante', ['master']);

// ═══════════════════════════════════════════════════════════════
// TUTORIAL
// ═══════════════════════════════════════════════════════════════
$router->get('/tutorial',               'TutorialController@index');
$router->get('/tutorial/aluno',         'TutorialController@aluno');
$router->get('/tutorial/diretor',       'TutorialController@diretor');
$router->get('/tutorial/representante', 'TutorialController@representante');
$router->get('/tutorial/master',        'TutorialController@master');
$router->get('/tutorial/exemplos',      'TutorialController@exemplos');
$router->get('/tutorial/demo/{slug}',   'TutorialController@demo');