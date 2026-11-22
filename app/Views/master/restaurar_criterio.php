<?php
// app/Views/master/restaurar_criterio.php
// View não utilizada atualmente.
// A restauração é feita via POST direto em /master/criterios-arquivados/{id}/restaurar,
// que redireciona de volta para a listagem. Mantida apenas para compatibilidade.

http_response_code(404);
exit('Não encontrado.');