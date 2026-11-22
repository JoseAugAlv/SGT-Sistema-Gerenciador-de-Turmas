<?php
// app/Views/relatorios/exportar_excel.php
//
// View de fallback para exportação Excel.
// O fluxo normal usa ExcelHelper::gerar() direto do controller.
// Esta view é útil se você quiser renderizar a planilha manualmente
// (ex: para testes, ou para salvar como HTML no servidor).

if (!isset($titulo))  $titulo  = 'Relatório';
if (!isset($linhas))  $linhas  = [];
if (!isset($appName)) $appName = App::getName();
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<!--[if gte mso 9]>
<xml>
    <x:ExcelWorkbook>
        <x:ExcelWorksheets>
            <x:ExcelWorksheet>
                <x:Name><?= h($titulo) ?></x:Name>
                <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
            </x:ExcelWorksheet>
        </x:ExcelWorksheets>
    </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
    table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 10px; }
    th, td { border: 1px solid #000; padding: 3px 5px; text-align: left; vertical-align: top; }
    th { background: #e0e0e0; font-weight: bold; }
</style>
</head>
<body>

<h3><?= h($titulo) ?></h3>
<p>Gerado em: <?= date('d/m/Y H:i:s') ?></p>

<?php if (empty($linhas)): ?>
    <p>Sem dados para exportar.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <?php foreach (array_keys($linhas[0]) as $coluna): ?>
                    <th><?= h($coluna) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($linhas as $linha): ?>
                <tr>
                    <?php foreach ($linha as $valor): ?>
                        <td><?= h(is_array($valor) ? json_encode($valor) : (string) $valor) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>