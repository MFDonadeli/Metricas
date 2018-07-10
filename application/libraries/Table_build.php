<?php

require_once APPPATH . '/libraries/PHPExcel/IOFactory.php';
define('START_ROW',1);

class Table_build
{
    private $linhas_planilhas = array();
    private $linhas_conversoes_personalizadas = array();
    private $geral = array();
    private $produto = '';

    function evalmath($equation)
    {
        $result = 0;
        // sanitize imput
        $equation = preg_replace("/[^a-z0-9+\-.*\/()%]/","",$equation);
        // convert alphabet to $variabel 
        $equation = preg_replace("/([a-z])+/i", "\$$0", $equation); 
        // convert percentages to decimal
        $equation = preg_replace("/([+-])([0-9]{1})(%)/","*(1\$1.0\$2)",$equation);
        $equation = preg_replace("/([+-])([0-9]+)(%)/","*(1\$1.\$2)",$equation);
        $equation = preg_replace("/([0-9]{1})(%)/",".0\$1",$equation);
        $equation = preg_replace("/([0-9]+)(%)/",".\$1",$equation);
        if ( $equation != "" ){
            $result = @eval("return " . $equation . ";" );
        }
        if ($result == null || $result == INF) {
            $result = 0;
        }

        
        return $result;
    }
    
    /**
    * generate_excel
    *
    * Função que abre o template em Excel, escreve os dados e salva para mostrar
    * ao usuário
    * @param	$dados(array): Dados trazidos do banco de dados 
    * @param    $db_metricas(object): Library do Banco de Dados
    * @param    $sem_dado_venda(boolean): Caso não haja postback vinculado, usa dados do purchase
    * @param    $comissao(float): Valor da comissao padrão 
    * @param    $preset(int): Modelo da planilha a ser gerada
    * @param    $id: Do tipo pesquisado
    * @param    $tipo: Tipo pesquisado (ad, adset, campaign)
    * @return   string nome do arquivo gerado
    */
    function generate_table($dados, $db_metricas, $sem_dado_venda, $comissao, $preset, $id, $tipo)
    {

        $diasemana = array('Dom', 'Seg', 'Ter', 'Quar', 'Qui', 'Sex', 'Sáb');

        $config = $db_metricas->getConfigPlanilha($preset);
        $ordem =  $db_metricas->getPlanilhaOrdem($preset);

        if(isset($dados[0]->conversao))
            $item_conversoes = $dados[0]->conversao;
        else
            $item_conversoes = null;

        if($sem_dado_venda)
            $array_valores['VENDAS'] = 'offsite_conversion.fb_pixel_purchase';
        else
            $array_valores['VENDAS'] = '(cartoes+boletos_pagos)';


        //Coloca as conversões na planilha
        $item_conversoes = $this->inserirConversoes($item_conversoes);

        $return = "<table id='table_numero' class='table table-xtra-condensed'>";
        foreach($ordem as $ord)
        {   
            //Trata exclusivamente as conversões
            if($ord->campo_descricao == '#TpConversao')
            {
                if($item_conversoes)
                {
                    foreach($item_conversoes as $key=>$val)
                    {
                        $return .= "<tr>";
                        for($i=0;$i<count($dados);$i++)
                        {
                            $date_start = explode(" ", $dados[$i]->date_start)[0];
                            $date = DateTime::createFromFormat('Y-m-d', $date_start);
                            $col_name = "col_".$date->format('dM');
                            $row_name = "row_".str_replace(' ', '', preg_replace("/[^a-zA-Z0-9\s]/", "", $val));

                            if($i == 0)
                                $return .= "<th class='$row_name $col_name'>" . $val . "</th>";  

                            $valor = $dados[$i]->conversao->{$key};
                            if(strpos($valor,'.') !== false)
                            {
                                $valor = round(floatval($valor), 2);
                            }
                                
                            $return .= "<td class='$row_name $col_name'>" . $valor . "</td>";    
                        
                        }
                        $return .= "</tr>";
                    }
                }
            }
            //Fim do tratamento das conversões

            if($ord->campo_descricao == '#TpConversao' || 
                    $ord->campo_descricao == '$Custo / TpConversão:')
                continue;
            
            $return .= "<tr>";
            for($i=0;$i<count($dados);$i++)
            {
                $date_start = explode(" ", $dados[$i]->date_start)[0];
                $date = DateTime::createFromFormat('Y-m-d', $date_start);
                $col_name = "col_".$date->format('dM');
                $row_name = "row_".str_replace(' ', '', preg_replace("/[^a-zA-Z0-9\s]/", "", $ord->campo_descricao));

                if($i == 0)
                {
                    if($ord->origem_bd == 'date_start')
                        $return .= "<th class='freeze_both $row_name $col_name'>" . $ord->campo_descricao . "</td>";
                    else
                        $return .= "<th class='freeze_vertical $row_name $col_name'>" . $ord->campo_descricao . "</td>";
                }
                    

                if(isset($ord->origem_bd))
                {
                    if($ord->origem_bd != '')
                    {
                        if(!isset($dados[$i]->{$ord->origem_bd}))
                            $valor = "-";
                        else
                            $valor = $dados[$i]->{$ord->origem_bd};

                        //Substituti valores variaveis
                        foreach($array_valores as $key=>$val)
                        {
                            $ord->origem_bd = str_replace($key, $val, $ord->origem_bd);
                        } 

                        if($ord->origem_bd == 'date_start' && $ord->campo_descricao == 'Dia')
                        {
                            $date_start = explode(" ", $valor)[0];
                            $date = DateTime::createFromFormat('Y-m-d', $date_start);
                            $valor = $diasemana[$date->format('w')]; 

                            if($dados[$i]->bydate != '1')
                                $valor = 'Geral';
                        }
                        else if($ord->origem_bd == 'date_start')
                        {
                            $date_start = explode(" ", $valor)[0];
                            $date = DateTime::createFromFormat('Y-m-d', $date_start);
                            $valor = $date->format('d/M');

                            if($dados[$i]->bydate != '1')
                                $valor = 'Geral';
                        }
                        elseif(strpos($valor,'.') !== false)
                        {
                            $valor = round(floatval($valor), 2);
                        }
                        elseif($ord->origem_bd[0] == '=')
                        {
                            $var = substr($ord->origem_bd,1);

                            $array=preg_split('/([\\=\\+\\-\\*\/\\(\\)])/',$ord->origem_bd);     

                            for($j=0;$j<count($array);$j++)
                            {
                                if($array[$j] != '')
                                {
                                    if(isset($dados[$i]->{$array[$j]}))
                                        $v = $dados[$i]->{$array[$j]};
                                    else
                                        $v = '';
                                    if($v=='') $v = 0;    
                                    $var = str_replace($array[$j], $v, $var);
                                }
                            }
   
                            $valor = $this->evalmath($var);
                            
                            $valor = round(floatval($valor), 2);
                        }
                        if($ord->origem_bd == 'date_start')
                            $return .= "<th class='freeze_horizontal $row_name $col_name'>" . $valor . "</th>";
                        else
                            $return .= "<td class='$row_name $col_name'>" . $valor . "</td>";
                    }
                }
            }
            $return .= "</tr>";
        }
        $return .= "</table>";

        $vendendo = $db_metricas->get_dados_vendendo($this->produto);
        $kpis = "";
        
        if($item_conversoes)
            $kpis = $this->processa_kpis($dados, $comissao, $sem_dado_venda, $vendendo, $config);

        $return = $this->monta_html($return, $kpis);

        return $return;
    }

    /**
    * processa_kpis
    *
    * Processa aba de KPIs
    *
    * @param	$dados: Informações necessárias para processamento dos KPIs
    * @param	$comissao: Valor da comissão
    * @param    $sem_dado_venda: Caso não haja postback vinculado, usa dados do purchase 
    * @param    $vendendo: Array de métricas que estão vendendo ou false se não houver
    * @param    $config: Qual configuração a planilha vai usar (metas)
    * @return	-
    */
    function processa_kpis($dados, $comissao, $sem_dado_venda, $vendendo, $config_planilha)
    {

        $last = count($dados)-1;
        $cpc = $dados[$last]->cost_per_inline_link_click;

        if(isset($dados[$last]->conversao->{'offsite_conversion.fb_pixel_purchase'}))
            $vendas = $dados[$last]->conversao->{'offsite_conversion.fb_pixel_purchase'};
        else
            $vendas = 0;

        if($vendas == 0)
            $c1v = 200;
        else if($sem_dado_venda)
        {
            $c1v = $dados[$last]->conversao->{'offsite_conversion.fb_pixel_view_content'} / $dados[$last]->conversao->{'offsite_conversion.fb_pixel_purchase'};
        }
        else
        {
            $c1v = $dados[$last]->conversao->{'offsite_conversion.fb_pixel_view_content'} / ($dados[$last]->cartoes + $dados[$last]->boletos_pagos);    
            $comissao = ($dados[$last]->faturamento_cartao + $dados[$last]->faturamento_boleto) / $vendas;
        }

        $cpv = $cpc * $c1v;
        $roi = $comissao / $cpv;

        if(isset($config_planilha[0])) 
        {
            $kpi['Meta1']['Meta'] = $config_planilha[0]->porcentagem . '%';
            $kpi['Meta1']['cpv'] = $comissao/(1+($config_planilha[0]->porcentagem/100));
            $kpi['Meta1']['cpc'] = $kpi['Meta1']['cpv'] / $c1v;
        }
        else
            $kpi['Meta1']['Meta'] = 0;

        if(isset($config_planilha[1])) 
        {
            $kpi['Meta2']['Meta'] = $config_planilha[1]->porcentagem . '%';
            $kpi['Meta2']['cpv'] = $comissao/(1+($config_planilha[1]->porcentagem/100));
            $kpi['Meta2']['cpc'] = $kpi['Meta2']['cpv'] / $c1v;
        }
        else
            $kpi['Meta2']['Meta'] = 0;

        if(isset($config_planilha[2])) 
        {
            $kpi['Meta3']['Meta'] = $config_planilha[2]->porcentagem . '%';
            $kpi['Meta3']['cpv'] = $comissao/(1+($config_planilha[2]->porcentagem/100));
            $kpi['Meta3']['cpc'] = $kpi['Meta3']['cpv'] / $c1v;
        }
        else
            $kpi['Meta3']['Meta'] = 0;

        if(isset($config_planilha[3])) 
        {
            $kpi['Meta4']['Meta'] = $config_planilha[3]->porcentagem . '%';
            $kpi['Meta4']['cpv'] = $comissao/(1+($config_planilha[3]->porcentagem/100));
            $kpi['Meta4']['cpc'] = $kpi['Meta4']['cpv'] / $c1v;
        }
        else
            $kpi['Meta4']['Meta'] = 0;

        $html = "";

        $html .= "<p>KPIs baseados no CPC e no número de cliques por venda do anúncio atual</p>";

        $html .= "<table class='table table-condensed'>";

        $html .= "<tr>";
        $html .= "<td></td>";
        $html .= "<th>ROI Projetado</td>";
        $html .= "<th>Cliques/Venda</td>";
        $html .= "<th>CPV Projetado</td>";
        $html .= "<th>CPC Atual</td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>Anúncio Atual</th>";
        $html .= "<td>" . $roi . "</td>";
        $html .= "<td>" . $c1v . "</td>";
        $html .= "<td>" . $cpv . "</td>";
        $html .= "<td>" . $cpc . "</td>";
        $html .= "</tr>"; 

        $html .= "</table>";

        $html .= "<p>Baseados no número de cliques por venda do anúncio atual, para atingir as seguintes metas de ROI, você deria ter como CPC máximo e CPV os seguintes resultados:</p>";

        $html .= "<table class='table table-condensed'>";

        $html .= "<tr>";
        $html .= "<td></td>";
        $html .= "<th>Metas de ROI</th>";
        $html .= "<th>Cliques/Venda</th>";
        $html .= "<th>CPV Meta</th>";
        $html .= "<th>CPC Máximo</th>";
        $html .= "</tr>";

        foreach($kpi as $key=>$val)
        {
            $html .= "<tr>";
            $html .= "<th> $key </th>";
            $html .= "<td>" . $kpi[$key]['Meta'] . "</td>";
            $html .= "<td>" . $c1v . "</td>";
            $html .= "<td>" . $kpi[$key]['cpv'] . "</td>";
            $html .= "<td>" . $kpi[$key]['cpc'] . "</td>";
            $html .= "</tr>";    
        }

        $html .= "</table>";

        return $html;

    }

    function monta_html($numeros, $kpis)
    {
    $return =    '<div class="numeros_">';
    $return .=    '        <ul class="nav nav-tabs">';
    $return .=    '            <li class="active"><a data-toggle="tab" href="#numeros">Numeros</a></li>';
    if($kpis != "")
        $return .=    '            <li><a data-toggle="tab" href="#kpis">KPIs</a></li>';
    $return .=    '        </ul>';

    $return .=    '        <div class="tab-content">';
    $return .=    '            <div id="numeros" class="tab-pane fade in active">';
    $return .=      $numeros;
    $return .=    '        </div>';
    $return .=    '<div id="kpis" class="tab-pane fade">';
    $return .=      $kpis;
    $return .=    '</div>';
    $return .=    '</div>';
    $return .=    '</div>';

        return $return;
    }

    function array_avg($array)
    {
        return array_sum($array)/count($array);
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

        $linha_faturamento = $this->procura_valor("%ROI:", 0, $sheet)-1;

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
    * @return	-
    */
    function inserirConversoes($conversion_array)
    {
        //Se não tiver conversões, remove do template
        if($conversion_array == null)
        {
            return false;
        }

        $names = $conversion_array->name;
        unset($conversion_array->name);

        //Adiciona as conversões
        $conversoes = array();
        $i = 0;
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
                $valor = $key;
                if(strpos($valor, 'offsite_conversion.') !== false)
                {
                    $valor = str_replace('offsite_conversion.', '', $valor);
                }

                $conversoes[$valor] = $names->{$key};

                $valor = $names->{$key};
            }

            $itens_planilha[$key] = $valor;
        }

       //$itens_planilha['conversoes'] = $conversoes;
       return $itens_planilha;

    }

    /**
    * procura_valor
    *
    * Busca valor na planilha
    *
    * @param	valor: Valor a ser encontrado
    * @param	col(int): Coluna a ser buscado o valor
    * @param    $sheet(object): Worksheet sendo usada
    * @return	(int): A linha que do resultado encontrado ou -1 se não encontrar
    */
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
        $row = $this->procura_valor($titulo, 0, $sheet);
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
}

?>