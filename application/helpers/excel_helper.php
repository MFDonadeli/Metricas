<?php

require_once APPPATH . '/libraries/PHPExcel/IOFactory.php';
define('START_ROW',1);

/**
* generate_excel
*
* Função que abre o template em Excel, escreve os dados e salva para mostrar
* ao usuário
*
* @param	$dados(array): Dados trazidos do banco de dados 
* @param    $excel(object): Library do Excel
* @param    $sem_dado_venda(boolean): Caso não haja postback vinculado, usa dados do purchase
* @param    $comissao(float): Valor da comissao padrão 
* @return	(string): Nome do arquivo
*/
function generate_excel($dados, $excel, $sem_dado_venda, $comissao)
{
    $diasemana = array('Dom', 'Seg', 'Ter', 'Quar', 'Qui', 'Sex', 'Sáb');

    //Pega o caminho do template
    $file_name_old = FCPATH."template/Template.xlsx";
    //Novo nome do arquivo de template
    $raw_file_name = "Template".md5(mt_rand() . time()).".xlsx";
    //Novo nome com caminho completo
    $file_name = FCPATH."template/".$raw_file_name;

    //Copia o novo arquivo
    copy($file_name_old, $file_name);

    //Abre o arquivo
    $objPHPExcel = PHPExcel_IOFactory::load($file_name);

    $column = 1;

    if(isset($dados[0]->conversao))
        $item_conversoes = $dados[0]->conversao;
    else
        $item_conversoes = null;

    $linha_faturamento = procura_valor("#faturamento_boleto", 1, $objPHPExcel->getActiveSheet());

    //Coloca as conversões na planilha
    inserirConversoes($item_conversoes, $objPHPExcel->getActiveSheet());

    $qtde_colunas = count($dados);

    $linha_faturamento = procura_valor("#faturamento_boleto", 1, $objPHPExcel->getActiveSheet());

    //Para cada dado a ser inserido
    foreach($dados as $dado)
    {
        //Cria uma nova coluna se não for a última
        if($column != $qtde_colunas)
            duplicate_column($column, $objPHPExcel->getActiveSheet());

        //Coloca as conversões no primeiro nível do array
        if(isset($dado->conversao))
        {
            foreach($dado->conversao as $key => $val)
            {
                $dado->{$key} = $val;
            }
            unset($dado->conversao);
        }

        for($row = START_ROW; $row <= $objPHPExcel->getActiveSheet()->getHighestRow(); $row++)
        {    
            //Se for geral e estiver na primeira linha, coloca o título geral   
            if(($row == START_ROW || $row == START_ROW+1) && $dado->bydate != 1)
            {
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->setValue("Geral"); 
            }
            else
            {
                $value = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->getValue();

                //Se não tiver dados de postback
                if($sem_dado_venda)
                {
                    //Se estiver na linha de faturamento
                    if($row == $linha_faturamento)
                    {
                        //Pega a coluna atual
                        $coluna_atual = PHPExcel_Cell::stringFromColumnIndex($column);
                        //Se for geral, troca pela fórmula que soma os dados por dia
                        if($dado->bydate != 1)
                        {
                            $coluna_anterior = PHPExcel_Cell::stringFromColumnIndex($column-1);
                            $value = "=SUM(B" . $row . ":" . $coluna_anterior . $row . ")";    
                        }
                        //Se for um dia, pega os dados do purchase
                        elseif(isset($dado->{"offsite_conversion.fb_pixel_purchase"}))
                        {
                            $value = intval($dado->{"offsite_conversion.fb_pixel_purchase"}) * floatval($comissao);
                        }
                        else
                            $value = 0;

                        //Escreve na célula
                        $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->setValue($value);
                    }
                }

                //Se for um valor que começa com #. Serve para identificar um campo a ser alterado no template
                if($value[0] == '#')
                {
                    $campo = str_replace('#', '', $value);  

                    //PROCESSA GERAL: É PROVISÓRIO. O GERAL VEM DA SOMA DAS DATAS
                    if($dado->bydate != 1)
                    {
                        $coluna_anterior = PHPExcel_Cell::stringFromColumnIndex($column-1);
                        $coluna_atual = PHPExcel_Cell::stringFromColumnIndex($column);
                        if($value == '#inline_link_click_ctr' || $value == '#cost_per_inline_link_click'
                         || $value == '#cpm' || $value == '#relevance_score_score' || 
                         strpos($value, "Custo por") !== false || $value == '#checkout_view' ||
                         $value == '#purchase_view' || $value == '#purchase_checkout')
                        {
                            $dado->{$campo} = '=IFERROR(ROUND(AVERAGE(B' . $row . ':' . $coluna_anterior . $row . '),2),"")';
                        }
                        else
                        {
                            $dado->{$campo} = "=SUM(B" . $row . ":" . $coluna_anterior . $row . ")";    
                        }
                    }
                    /////

                    //Troca o valor #<campo> pelo valor da tabela
                    if(isset($dado->{$campo}))
                    {
                        $valor = $dado->{$campo};
                        if($campo == 'date_start')
                        {
                            $date_start = explode(" ", $dado->date_start)[0];
                            $date = DateTime::createFromFormat('Y-m-d', $date_start);
                            $dado->{$campo} = $date->format('d/M');
                        }
                        else if($campo == 'dia_da_semana')
                        {
                            $date_start = explode(" ", $dado->date_start)[0];
                            $date = DateTime::createFromFormat('Y-m-d', $date_start);
                            $dado->{$campo} = $diasemana[$date->format('w')];    
                        }
                        elseif(strpos($valor,'.') !== false)
                        {
                            $dado->{$campo} = round(floatval($valor), 2);
                        }

                        $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->setValue($dado->{$campo});
                    }
                    else
                        $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->setValue('');                              
                }     
            }
        }

        //Acabou os dados para esta coluna, vamos para a próxima
        $column++;

    }

   //Coloca formatação condicional no ROI
    formata_roi($qtde_colunas, $objPHPExcel->getActiveSheet());

    //$chart = build_chart($qtde_colunas, '%CTR', $objPHPExcel->getActiveSheet());

    //$objPHPExcel->getActiveSheet()->addChart($chart);
    //Posiciona a seleção na célula A1
    $objPHPExcel->getActiveSheet()->setSelectedCell('A1'); 

    //Salva o arquivo
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($file_name);

    return $raw_file_name;
}

/**
* formata_roi
*
* Coloca a formatação condicional nas células do ROI
*
* @param	colunas: Número de colunas adicionadas
* @param    $sheet(object): Worksheet sendo usada
* @return	-
*/
function formata_roi($colunas, $sheet)
{
    //Formatação condicional para ROI Negativo
    $objConditional1 = new PHPExcel_Style_Conditional();
    $objConditional1->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS)
                    ->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_LESSTHAN)
                    ->addCondition('0');
    $objConditional1->getStyle()->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getEndColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);   

    //Formatação condicional para ROI Positivo
    $objConditional2 = new PHPExcel_Style_Conditional();
    $objConditional2->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS)
                    ->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_GREATERTHANOREQUAL)
                    ->addCondition('0');
    $objConditional2->getStyle()->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getEndColor()->setARGB(PHPExcel_Style_Color::COLOR_GREEN);   

    $linha_faturamento = procura_valor("%ROI:", 0, $sheet)-1;

    //Adiciona formatação condicional em cada célula
    for($col = 1; $col <= $colunas; $col++)
    {
        $celula = PHPExcel_Cell::stringFromColumnIndex($col).$linha_faturamento;
        $conditionalStyles = $sheet->getStyle($celula)->getConditionalStyles();
        array_push($conditionalStyles, $objConditional1);
        array_push($conditionalStyles, $objConditional2);
        $sheet->getStyle($celula)->setConditionalStyles($conditionalStyles);
    }

}

/**
* duplicate_column
*
* Duplica valores e estilo de uma coluna para a coluna ao lado
*
* @param	col: Número da coluna a ser duplicada
* @param    $sheet(object): Worksheet sendo usada
* @return	-
*/
function duplicate_column($col, $sheet)
{
    for($row=START_ROW; $row<=$sheet->getHighestRow(); $row++)
    {
        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        $style = $sheet->getStyleByColumnAndRow($col, $row);
        $conditional = $style->getConditionalStyles();
        $orgCellColumn = '$'.PHPExcel_Cell::stringFromColumnIndex($col);
        $dstCellColumn = '$'.PHPExcel_Cell::stringFromColumnIndex($col+1);
        $dstCell = PHPExcel_Cell::stringFromColumnIndex($col+1) . (string)($row);
        if(!empty($value))
        {
            if($value[0] == '=')
            {
                $value = str_replace($orgCellColumn, $dstCellColumn, $value);
            }
        }
        $sheet->setCellValue($dstCell, $value);
        $sheet->duplicateStyle($style, $dstCell);

        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
    }
}

/**
* inserirConversoes
*
* Coloca as conversões possíveis dentro da planilha
*
* @param	conversion_array: Array de conversões possíveis
* @param    $sheet(object): Worksheet sendo usada
* @return	-
*/
function inserirConversoes($conversion_array, $sheet)
{
    $color = '';
    $color_array=array("E4DFEC", "D9EAD3", "DDD9C4", "FCE9D9");

    //Procura onde vai adicionar as conversões
    $row = procura_valor('#TpConversao:', 0, $sheet);

    //Se não tiver conversões, remove do template
    if($conversion_array == null)
    {
        $sheet->removeRow($row-1, 3);
        return;
    }

    $names = $conversion_array->name;
    unset($conversion_array->name);
    
    //Se houver mais de 1 conversão (conversão e valor), adiciona a quantidade necessária
    //de linhas de acordo com as conversões. (-2, pois já existem duas linhas)
    if(count(get_object_vars($conversion_array)) > 2)
        $sheet->insertNewRowBefore($row+1, count(get_object_vars($conversion_array)) - 2);

    //Após adicionar o TpConversão sobe, então procura de novo
    $row = procura_valor('#TpConversao:', 0, $sheet)-1;

    //Adiciona as conversões
    foreach($conversion_array as $key => $val)
    {
        $valor_b = "#".$key;
        
        if(strpos($key,'Custo por ') !== false)
        {
            $valor = str_replace('Custo por ', '', $key); 
            $valor = 'Custo por ' . $names->{$valor};
        }
        else
        {
            $color = $color_array[$row % count($color_array)];
            $valor = $names->{$key};
        }

        //Copia e adiciona estilos e bordas    
        $sheet->setCellValue("A".$row, $valor);
        $sheet->setCellValue("B".$row, $valor_b);
        $sheet->getStyle('A'.$row.':B'.$row)->applyFromArray(
        array('fill' 	=> array(
                                    'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
                                    'color'		=> array('argb' => $color)
                                ),
            'borders' => array(
                                    'allborders'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN)
                                )
            )
        );
        //Próxima linha
        $row++;
    }

}

function procura_valor($valor, $col, $sheet)
{
    $row = 0;
    $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
    for($row=0; $row<=$sheet->getHighestRow(); $row++)
    {
        if($value == $valor)
            return $row;

        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
    }

    return -1;
}

function build_chart($qtde_colunas, $titulo, $sheet)
{
    $row = procura_valor($titulo, 0, $sheet);
    $lastCol = PHPExcel_Cell::stringFromColumnIndex($qtde_colunas-1);
    //	Set the Labels for each data series we want to plot
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $dataSeriesLabels = array(
        new PHPExcel_Chart_DataSeriesValues('String', 'Métricas!$B$1:$'.$lastCol.'$1', NULL, 1)
    );
    //	Set the Data values for each data series we want to plot
    //		Datatype
    //		Cell reference for data
    //		Format Code
    //		Number of datapoints in series
    //		Data values
    //		Data Marker
    $dataSeriesValues = array(
        new PHPExcel_Chart_DataSeriesValues('Number', 'Métricas!$B$'.$row.':$'.$lastCol.'$'.$row, NULL, 4)
    );

    //	Build the dataseries
    $series = new PHPExcel_Chart_DataSeries(
        PHPExcel_Chart_DataSeries::TYPE_SCATTERCHART,	// plotType
        NULL,											// plotGrouping (Scatter charts don't have any grouping)
        range(0, count($dataSeriesValues)-1),			// plotOrder
        $dataSeriesLabels,								// plotLabel
        NULL,           								// plotCategory
        $dataSeriesValues,								// plotValues
        NULL,                                           // plotDirection
        NULL,											// smooth line
        PHPExcel_Chart_DataSeries::STYLE_LINEMARKER		// plotStyle
    );

    //	Set the series in the plot area
    $plotArea = new PHPExcel_Chart_PlotArea(NULL, array($series));
    //	Set the chart legend
    $legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_TOPRIGHT, NULL, false);

    $title = new PHPExcel_Chart_Title($titulo);


    //	Create the chart
    $chart = new PHPExcel_Chart(
        'chart1',		// name
        $title,			// title
        $legend,		// legend
        $plotArea,		// plotArea
        true,			// plotVisibleOnly
        0,				// displayBlanksAs
        NULL,			// xAxisLabel
        NULL	    	// yAxisLabel
    );

    //	Set the position where the chart should appear in the worksheet
    $chart->setTopLeftPosition('C30');
    $chart->setBottomRightPosition('H40');

    //	Add the chart to the worksheet
    return $chart;

}
?>