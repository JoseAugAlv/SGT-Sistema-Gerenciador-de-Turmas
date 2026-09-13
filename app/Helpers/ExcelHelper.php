<?php

class ExcelHelper
{
    /**
     * Gera um arquivo Excel (.xls) com dados puros, sem formatação
     */
    public static function gerar($dados, $nomeArquivo = 'relatorio.xls', $titulo = 'Relatório')
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" 
                    xmlns:x="urn:schemas-microsoft-com:office:excel" 
                    xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<!--[if gte mso 9]>
                <xml>
                    <x:ExcelWorkbook>
                        <x:ExcelWorksheets>
                            <x:ExcelWorksheet>
                                <x:Name>' . htmlspecialchars($titulo) . '</x:Name>
                                <x:WorksheetOptions>
                                    <x:DisplayGridlines/>
                                </x:WorksheetOptions>
                            </x:ExcelWorksheet>
                        </x:ExcelWorksheets>
                    </x:ExcelWorkbook>
                </xml>
                <![endif]-->';
        echo '<style>
                table { 
                    border-collapse: collapse; 
                    font-family: Arial, sans-serif; 
                    font-size: 10px;
                }
                th, td { 
                    border: 1px solid #000000; 
                    padding: 3px 5px; 
                    text-align: left;
                    vertical-align: top;
                }
                th {
                    background-color: #E0E0E0;
                    font-weight: bold;
                }
              </style>';
        echo '</head>';
        echo '<body>';

        // ==========================================
        // TÍTULO (apenas texto)
        // ==========================================
        echo '<h3>' . htmlspecialchars($titulo) . '</h3>';
        echo '<p>Gerado em: ' . date('d/m/Y H:i:s') . '</p>';

        // ==========================================
        // TABELA COM DADOS PUROS
        // ==========================================
        if (!empty($dados)) {
            echo '<table>';
            
            // CABEÇALHO
            echo '<thead>';
            echo '<tr>';
            $headers = array_keys($dados[0]);
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            echo '</thead>';
            
            // CORPO - DADOS PUROS, SEM FORMATAÇÃO
            echo '<tbody>';
            foreach ($dados as $row) {
                echo '<tr>';
                foreach ($row as $value) {
                    // Remove qualquer formatação, exibe o valor bruto
                    $valorBruto = self::limparValor($value);
                    echo '<td>' . htmlspecialchars($valorBruto) . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody>';
            
            echo '</table>';
        }

        echo '</body>';
        echo '</html>';
        exit;
    }

    /**
     * Limpa o valor, removendo formatação
     */
    private static function limparValor($value)
    {
        // Se for null ou vazio
        if ($value === null || $value === '') {
            return '';
        }
        
        // Se for array, converte para JSON
        if (is_array($value)) {
            return json_encode($value);
        }
        
        // Se for booleano
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        // Se for número, mantém como está (sem formatação de moeda)
        if (is_numeric($value)) {
            // Mantém o número exato, sem formatação
            return (string) $value;
        }
        
        // Se for data, mantém no formato original
        if (strtotime($value) !== false) {
            return $value;
        }
        
        // Retorna o valor original
        return $value;
    }

    /**
     * Prepara dados para exportação (remove dados sensíveis)
     */
    public static function prepararDados($dados, $camposRemover = ['senha', 'token', 'password'])
    {
        if (empty($dados)) {
            return [];
        }

        $resultado = [];
        foreach ($dados as $row) {
            $linha = [];
            foreach ($row as $key => $value) {
                if (!in_array($key, $camposRemover)) {
                    $linha[$key] = $value;
                }
            }
            $resultado[] = $linha;
        }

        return $resultado;
    }
}