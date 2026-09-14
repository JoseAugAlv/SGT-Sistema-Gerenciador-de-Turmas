<?php
// routes/web.php

require_once __DIR__ . '/../app/Helpers/menuHelper.php';

// CARREGA CONFIGURAÇÕES DE MÓDULOS
$modulesConfig = __DIR__ . '/../app/Config/modules.php';
if (file_exists($modulesConfig)) {
    $modules  = require $modulesConfig;
    $modulos  = $modules['modules'] ?? [];
} else {
    $modulos = [];
}

// ============ PÚBLICAS ============
$router->get('/',         'HomeController@index');
$router->get('/tutorial', 'TutorialController@index');

// ============ AUTH ============
$router->get ('/login',          'AuthController@loginForm');
$router->post('/login',          'AuthController@login');
$router->get ('/logout',         'AuthController@logout');

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

// ============ TURMAS ============
$router->get ('/turmas',                        'TurmaController@index',              ['master', 'aluno']);
$router->get ('/turmas/criar',                  'TurmaController@criarForm',          ['master']);
$router->post('/turmas/salvar',                 'TurmaController@salvar',             ['master']);

$router->get ('/turmas/entrar',                 'TurmaController@entrarForm',         ['master', 'aluno']);
$router->post('/turmas/entrar',                 'TurmaController@entrar',             ['master', 'aluno']);

$router->get ('/turmas/{id}',                   'TurmaController@detalhe',            ['master', 'aluno']);
$router->post('/turmas/{id}/bloquear',          'TurmaController@bloquear',           ['master']);
$router->post('/turmas/{id}/desbloquear',       'TurmaController@desbloquear',        ['master']);
$router->post('/turmas/{id}/regenerar-codigo',  'TurmaController@regenerarCodigo',    ['master', 'aluno']);

$router->get ('/turmas/{id}/representantes',           'TurmaController@representantesForm',   ['master']);
$router->post('/turmas/{id}/representantes/nomear',    'TurmaController@nomearRepresentante',  ['master']);
$router->post('/turmas/{id}/representantes/remover',   'TurmaController@removerRepresentante', ['master']);

$router->get ('/turmas/{id}/editar',    'TurmaController@editarForm', ['master']);
$router->post('/turmas/{id}/atualizar', 'TurmaController@atualizar',  ['master']);
$router->get ('/turmas/{id}/excluir',   'TurmaController@excluirForm',['master']);
$router->post('/turmas/{id}/excluir',   'TurmaController@excluir',    ['master']);

// ============ CONFIGURAÇÕES DE TURMA (MASTER) ============
$router->get ('/configuracoes/turmas',                      'ConfiguracaoTurmaController@index',         ['master']);
$router->post('/configuracoes/turmas/curso/salvar',         'ConfiguracaoTurmaController@salvarCurso',   ['master']);
$router->post('/configuracoes/turmas/curso/toggle/{id}',    'ConfiguracaoTurmaController@toggleCurso',   ['master']);
$router->post('/configuracoes/turmas/periodo/salvar',       'ConfiguracaoTurmaController@salvarPeriodo', ['master']);
$router->post('/configuracoes/turmas/periodo/toggle/{id}',  'ConfiguracaoTurmaController@togglePeriodo', ['master']);

// ============ PROJETOS ============
$router->get ('/projetos',                 'ProjetoController@index',      ['master', 'aluno']);
$router->get ('/projetos/criar',           'ProjetoController@criarForm',  ['master']);
$router->post('/projetos/salvar',          'ProjetoController@salvar',     ['master']);

$router->get ('/projetos/{id}',            'ProjetoController@detalhe',    ['master', 'aluno']);
$router->get ('/projetos/{id}/editar',     'ProjetoController@editarForm', ['master']);
$router->post('/projetos/{id}/atualizar',  'ProjetoController@atualizar',  ['master']);

$router->get ('/projetos/{id}/encerrar',   'ProjetoController@encerrarForm', ['master', 'aluno']);
$router->post('/projetos/{id}/encerrar',   'ProjetoController@encerrar',     ['master', 'aluno']);

// ============ ABAS DO PROJETO ============
$router->get('/projetos/{id}/grupos',     'GrupoController@index',     ['master', 'aluno']);
$router->get('/projetos/{id}/criterios',  'CriterioController@index',  ['master', 'aluno']);
$router->get('/projetos/{id}/conceitos',  'ConceitoController@index',  ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios', 'ProjetoController@relatorios', ['master', 'aluno']);

// ============ ETAPAS ============
$router->post('/projetos/{id}/etapas/criar',  'EtapaController@criar',      ['master', 'aluno']);
$router->get ('/etapas/{id}/editar',          'EtapaController@editarForm', ['master', 'aluno']);
$router->post('/etapas/{id}/atualizar',       'EtapaController@atualizar',  ['master', 'aluno']);
$router->post('/etapas/{id}/excluir',         'EtapaController@excluir',    ['master', 'aluno']);
$router->post('/etapas/{id}/mover',           'EtapaController@mover',      ['master', 'aluno']);

// ============ CONCEITOS ============
$router->post('/projetos/{id}/conceitos/salvar', 'ConceitoController@salvar', ['master', 'aluno']);

// ============ GRUPOS ============
$router->get ('/projetos/{id}/grupos/criar',   'GrupoController@criarForm',      ['master', 'aluno']);
$router->post('/projetos/{id}/grupos/salvar',  'GrupoController@salvar',         ['master', 'aluno']);

$router->get ('/grupos/{id}',                  'GrupoController@detalhe',        ['master', 'aluno']);
$router->get ('/grupos/{id}/editar',           'GrupoController@editarForm',     ['master', 'aluno']);
$router->post('/grupos/{id}/atualizar',        'GrupoController@atualizar',      ['master', 'aluno']);
$router->post('/grupos/{id}/excluir',          'GrupoController@excluir',        ['master', 'aluno']);
$router->post('/grupos/{id}/membros/adicionar','GrupoController@adicionarMembro',['master', 'aluno']);
$router->post('/grupos/{id}/membros/remover',  'GrupoController@removerMembro',  ['master', 'aluno']);

// ============ DIRETORES ============
$router->get ('/grupos/{id}/diretores',           'DiretorController@gerenciar', ['master', 'aluno']);
$router->get ('/grupos/{id}/diretores/historico', 'DiretorController@historico', ['master', 'aluno']);
$router->post('/grupos/{id}/diretores/nomear',    'DiretorController@nomear',    ['master', 'aluno']);
$router->post('/grupos/{id}/diretores/remover',   'DiretorController@remover',   ['master', 'aluno']);

// ============ CRITÉRIOS ============
$router->get ('/projetos/{id}/criterios/criar',  'CriterioController@criarForm', ['master', 'aluno']);
$router->post('/projetos/{id}/criterios/salvar', 'CriterioController@salvar',    ['master', 'aluno']);

$router->get ('/criterios/{id}/editar',   'CriterioController@editarForm',  ['master', 'aluno']);
$router->post('/criterios/{id}/atualizar','CriterioController@atualizar',   ['master', 'aluno']);
$router->post('/criterios/{id}/excluir',  'CriterioController@excluir',     ['master', 'aluno']);
$router->get ('/criterios/{id}/reabrir',  'CriterioController@reabrirForm', ['master', 'aluno']);
$router->post('/criterios/{id}/reabrir',  'CriterioController@reabrir',     ['master', 'aluno']);

// ============ AVALIAÇÕES ============
$router->get ('/projetos/{id}/avaliacoes',        'AvaliacaoController@index',             ['master', 'aluno']);
$router->get ('/projetos/{id}/avaliacoes/representante',        'AvaliacaoController@bulkRepresentante',       ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/representante/salvar', 'AvaliacaoController@bulkRepresentanteSalvar', ['master', 'aluno']);
$router->get ('/projetos/{id}/avaliacoes/minhas', 'AvaliacaoController@minhasNotas',       ['master', 'aluno']);
// ============ AVALIAÇÕES — DIRETOR ============
$router->get ('/projetos/{id}/avaliacoes/diretor',        'AvaliacaoController@diretor',      ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/diretor/salvar', 'AvaliacaoController@diretorSalvar',['master', 'aluno']);

// ============ AVALIAÇÕES — PARES ============
$router->get ('/projetos/{id}/avaliacoes/pares',        'AvaliacaoController@pares',      ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/pares/salvar', 'AvaliacaoController@paresSalvar',['master', 'aluno']);

// ============ AVALIAÇÕES — AUTO ============
$router->get ('/projetos/{id}/avaliacoes/auto',        'AvaliacaoController@auto',      ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/auto/salvar', 'AvaliacaoController@autoSalvar',['master', 'aluno']);

// ============ AVALIAÇÕES — COLETIVA ============
$router->get ('/projetos/{id}/avaliacoes/coletiva',        'AvaliacaoController@coletiva',      ['master', 'aluno']);
$router->post('/projetos/{id}/avaliacoes/coletiva/salvar', 'AvaliacaoController@coletivaSalvar',['master', 'aluno']);

// ============ RELATÓRIOS ============
$router->get('/projetos/{id}/relatorios/boletim',       'RelatorioController@boletimAluno',   ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios/boletim/pdf',   'RelatorioController@boletimAlunoPdf',['master', 'aluno']);

$router->get('/projetos/{id}/relatorios/geral',         'RelatorioController@geralProjeto',    ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios/geral/pdf',     'RelatorioController@geralProjetoPdf', ['master', 'aluno']);
$router->get('/projetos/{id}/relatorios/geral/excel',   'RelatorioController@geralProjetoExcel',['master', 'aluno']);