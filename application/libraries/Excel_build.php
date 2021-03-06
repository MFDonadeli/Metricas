<?php

require_once APPPATH . '/libraries/PHPExcel/IOFactory.php';
define('START_ROW',1);

class Excel_build
{
    private $linhas_planilhas = array();
    private $linhas_conversoes_personalizadas = array();
    private $geral = array();
    private $produto = '';
    
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
    function generate_excel($dados, $db_metricas, $sem_dado_venda, $comissao, $preset, $id, $tipo)
    {

        $diasemana = array('Dom', 'Seg', 'Ter', 'Quar', 'Qui', 'Sex', 'Sáb');

        $config = $db_metricas->getConfigPlanilha($preset);

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
        $objPHPExcel->setActiveSheetIndex(0);

        $column = 1;

        if(isset($dados[0]->conversao))
            $item_conversoes = $dados[0]->conversao;
        else
            $item_conversoes = null;

        $linha_faturamento = $this->procura_valor("#faturamento_boleto", 1, $objPHPExcel->getActiveSheet());

        //Coloca as conversões na planilha
        $this->inserirConversoes($item_conversoes, $objPHPExcel->getActiveSheet());

        $qtde_colunas = count($dados);

        $linha_faturamento = $this->procura_valor("#faturamento_boleto", 1, $objPHPExcel->getActiveSheet());

        $linha_roi = $this->linhas_planilhas["%ROI:"];
        $linha_cpv = $this->linhas_planilhas["\$CPV:"];

        //Para cada dado a ser inserido
        foreach($dados as $dado)
        {
            //Cria uma nova coluna se não for a última
            if($column != $qtde_colunas)
                $this->duplicate_column($column, $objPHPExcel->getActiveSheet());

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
                    $dado->date_start = "Geral";
                    $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->setValue("Geral"); 
                    if(isset($dado->produto))
                        $this->produto = $dado->produto;
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
                            if($dado->bydate != 1 && $qtde_colunas > 1)
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

                    if($row == $linha_cpv)
                    {
                        $coluna_atual = PHPExcel_Cell::stringFromColumnIndex($column);
                        if($sem_dado_venda)
                        {
                            if(isset($dado->{"offsite_conversion.fb_pixel_purchase"}))
                                $subst_vendas = $coluna_atual . $this->linhas_planilhas['conversoes']['fb_pixel_purchase'];
                            else
                                $subst_vendas = 0;
                        }
                        else
                        {
                            if(isset($dado->{"offsite_conversion.fb_pixel_purchase"}))
                            {
                                $cpv_cartao = $coluna_atual . $this->linhas_planilhas['#Cartões:'];
                                $cpv_boleto = $coluna_atual . $this->linhas_planilhas['#Boletos Pagos:'];

                                $subst_vendas = "(" . $cpv_boleto . "+" . $cpv_cartao . ")";
                            }  
                            else
                                $subst_vendas = 0;    
                        }

                        $value = str_replace("Vendas", $subst_vendas, $value);
                
                    
                        //Escreve na célula
                        $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->setValue($value);
                    }

                    //Se for um valor que começa com #. Serve para identificar um campo a ser alterado no template
                    if($value[0] == '#')
                    {
                        $campo = str_replace('#', '', $value);  

                        //PROCESSA GERAL: É PROVISÓRIO. O GERAL VEM DA SOMA DAS DATAS
                        if($dado->bydate != 1 && $qtde_colunas > 1)
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

                if($dado->bydate != 1)
                {
                    $this->geral[] = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->getFormattedValue();
                }
            }

            $dado->roi = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $linha_roi)->getCalculatedValue();
            //Acabou os dados para esta coluna, vamos para a próxima
            $column++;

        }

        //Coloca formatação condicional no ROI
        $this->formata_roi($qtde_colunas, $objPHPExcel->getActiveSheet());

        if($tipo == 'ad' && array_key_exists('conversoes', $this->linhas_planilhas))
        {
            $vendendo = $db_metricas->get_dados_vendendo($this->produto);
            $kpi = $this->processa_kpis($dados, $comissao, $sem_dado_venda, $vendendo, $objPHPExcel, $config);

            $db_metricas->saveKpis($kpi, $id);
        }
        else
            $objPHPExcel->removeSheetByIndex(1);

        //$chart = build_chart($qtde_colunas, '%CTR', $objPHPExcel->getActiveSheet());

        //$objPHPExcel->getActiveSheet()->addChart($chart);
        //Posiciona a seleção na célula A1
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setSelectedCell('A1'); 

        //Salva o arquivo
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($file_name);

        $db_metricas->saveGeral($this->linhas_planilhas, $this->geral, $this->linhas_conversoes_personalizadas, $id, $tipo);

        return $raw_file_name;
    }

    /**
    * processa_kpis
    *
    * Processa aba de KPIs
    *
    * @param	$dados: Informações necessárias para processamento dos KPIs
    * @param	$comissao: Valor da comissão
    * @param    $objPHPExcel: Excel sendo usado
    * @param    $sem_dado_venda: Caso não haja postback vinculado, usa dados do purchase 
    * @param    $vendendo: Array de métricas que estão vendendo ou false se não houver
    * @param    $config: Qual configuração a planilha vai usar (metas)
    * @return	-
    */
    function processa_kpis($dados, $comissao, $sem_dado_venda, $vendendo, $objPHPExcel, $config_planilha)
    {
        $colunas_7dias = array('I', 'J', 'K', 'L', 'M', 'N', 'O');
        $colunas_3dias = array('P', 'Q', 'R', 'S', 'T', 'U', 'V');
        $sogeral = false;

        $linhas_2_calculo = array(14, 29);
        $linhas_vendendo = array(32, 44);

        $colGeral = $objPHPExcel->getActiveSheet()->getHighestColumn();
        $colNumber = PHPExcel_Cell::columnIndexFromString($colGeral);

        if($colNumber > 1)
        {
            $colUltima = PHPExcel_Cell::stringFromColumnIndex($colNumber-2);
            $kpi['Dias'] = count($dados);
        }
        
        if(count($dados) > 7)
        {
            $colPrimeira7dias = PHPExcel_Cell::stringFromColumnIndex($colNumber-8);
            $kpi['7dias'] = 1;
        }
        
        if(count($dados) > 3)
        {
            $colPrimeira3dias = PHPExcel_Cell::stringFromColumnIndex($colNumber-4);
            $kpi['3dias'] = 1;
        }

        if(count($dados) == 1)
        {
            $sogeral = true;
            $colGeral = "B";
            $colNumber = 2;
            $colUltima = "B";
        }

        if(isset($config_planilha[0])) 
            $kpi['Meta1'] = $config_planilha[0]->porcentagem . '%';
        else
            $kpi['Meta1'] = 0;

        if(isset($config_planilha[1])) 
            $kpi['Meta2'] = $config_planilha[1]->porcentagem . '%';
        else
            $kpi['Meta2'] = 0;

        if(isset($config_planilha[2])) 
            $kpi['Meta3'] = $config_planilha[2]->porcentagem . '%';
        else
            $kpi['Meta3'] = 0;

        if(isset($config_planilha[3])) 
            $kpi['Meta4'] = $config_planilha[3]->porcentagem . '%';
        else
            $kpi['Meta4'] = 0;
            

        $kpi['comissao'] = $comissao;
        $kpi['Geral'] = $colGeral;
        $kpi['Ultima'] = $colUltima;
        if(isset($colPrimeira7dias)) $kpi['Primeiro_7dias'] = $colPrimeira7dias;
        if(isset($colPrimeira3dias)) $kpi['Primeiro_3dias'] = $colPrimeira3dias;
        $kpi['Primeira'] = 'B';

        if(isset($colPrimeira7dias)) $kpi['CTR_7dias'] = "Métricas!" . $colPrimeira7dias . $this->linhas_planilhas['%CTR:'];
        if(isset($colPrimeira3dias)) $kpi['CTR_3dias'] = "Métricas!" . $colPrimeira3dias . $this->linhas_planilhas['%CTR:'];
        if(isset($colPrimeira7dias)) $kpi['CPM_7dias'] = "Métricas!" . $colPrimeira7dias . $this->linhas_planilhas['$CPM:'];
        if(isset($colPrimeira3dias)) $kpi['CPM_3dias'] = "Métricas!" . $colPrimeira3dias . $this->linhas_planilhas['$CPM:'];
        if(isset($colPrimeira7dias)) $kpi['CPC_7dias'] = "Métricas!" . $colPrimeira7dias . $this->linhas_planilhas['$CPC:'];
        if(isset($colPrimeira3dias)) $kpi['CPC_3dias'] = "Métricas!" . $colPrimeira3dias . $this->linhas_planilhas['$CPC:'];
        
        $view_contents = $this->linhas_planilhas['conversoes']['fb_pixel_view_content'];
        
        $kpi['ViewContents'] = "Métricas!" . $colGeral . $this->linhas_planilhas[$view_contents];
        $kpi['Ultimo_ViewContent'] = "Métricas!" . $colUltima . $this->linhas_planilhas[$view_contents];
        if(isset($colPrimeira7dias)) $kpi['ViewContent_7dias'] = "Métricas!" . $colPrimeira7dias . $this->linhas_planilhas[$view_contents];
        if(isset($colPrimeira3dias)) $kpi['ViewContent_3dias'] = "Métricas!" . $colPrimeira3dias . $this->linhas_planilhas[$view_contents];
        
        if(array_key_exists('fb_pixel_initiate_checkout', $this->linhas_planilhas['conversoes']))
        {
            $initiate_checkout = $this->linhas_planilhas['conversoes']['fb_pixel_initiate_checkout'];
        
            $kpi['InitiateCheckout'] = "Métricas!" . $colGeral . $this->linhas_planilhas[$initiate_checkout];
            $kpi['Ultimo_InitiateCheckout'] = "Métricas!" . $colUltima . $this->linhas_planilhas[$initiate_checkout];
            if(isset($colPrimeira7dias)) $kpi['InitiateCheckout_7dias'] = "Métricas!" . $colPrimeira7dias . $this->linhas_planilhas[$initiate_checkout];
            if(isset($colPrimeira3dias)) $kpi['InitiateCheckout_3dias'] = "Métricas!" . $colPrimeira3dias . $this->linhas_planilhas[$initiate_checkout];
        }

        if(array_key_exists('fb_pixel_lead', $this->linhas_planilhas['conversoes']))
        {
            $lead = $this->linhas_planilhas['conversoes']['fb_pixel_lead'];
        
            $kpi['Lead'] = "Métricas!" . $colGeral . $this->linhas_planilhas[$lead];
            $kpi['Ultimo_Lead'] = "Métricas!" . $colUltima . $this->linhas_planilhas[$lead];
            if(isset($colPrimeira7dias)) $kpi['Lead_7dias'] = "Métricas!" . $colPrimeira7dias . $this->linhas_planilhas[$lead];
            if(isset($colPrimeira3dias)) $kpi['Lead_3dias'] = "Métricas!" . $colPrimeira3dias . $this->linhas_planilhas[$lead];
        }

        if($sem_dado_venda)
        {
            if(array_key_exists('fb_pixel_purchase', $this->linhas_planilhas['conversoes']))
            {
                $vendas = $this->linhas_planilhas['conversoes']['fb_pixel_purchase'];    
                $kpi['Vendas'] = "Métricas!" . $colGeral . $this->linhas_planilhas[$vendas];
                
                if(isset($colPrimeira7dias))
                    $kpi['Venda_7dias'] = "Métricas!" . $colPrimeira7dias . $this->linhas_planilhas[$vendas] . ":" .
                     $colUltima . $this->linhas_planilhas[$vendas];
                
                if(isset($colPrimeira3dias))
                    $kpi['Venda_3dias'] = "Métricas!" . $colPrimeira3dias . $this->linhas_planilhas[$vendas] . ":" .
                        $colUltima . $this->linhas_planilhas[$vendas];
            }
            else
            {   
                $kpi['Vendas'] = 0;
                $kpi['Venda_7dias'] = 0;
                $kpi['Venda_3dias'] = 0;

                if($kpi['ViewContents'] < 200)
                    $kpi['ViewContents'] = 200;
                else
                {
                    $vc = (int)($kpi['ViewContents'] / 100);
                    $kpi['ViewContents'] = ($vc * 100) + 100;
                }
            }
        }
        else
        {
            if(array_key_exists('fb_pixel_purchase', $this->linhas_planilhas['conversoes']))
            {
                $kpi['Cartoes'] = "Métricas!" . $colGeral . $this->linhas_planilhas['#Cartões:'];
                $kpi['BoletosGerados'] = "Métricas!" . $colGeral . $this->linhas_planilhas['#Boletos Gerados:'];
                $kpi['BoletosPagos'] = "Métricas!" . $colGeral . $this->linhas_planilhas['#Boletos Pagos:'];
                $kpi['BoletosTotais'] = "(" . $kpi['BoletosPagos'] . "+" . $kpi['BoletosGerados'] . ")";
                $kpi['Vendas'] = "(" . $kpi['BoletosPagos'] . "+" . $kpi['Cartoes'] . ")";
                
                if(isset($colPrimeira7dias))
                {
                    $kpi['Cartao_7dias'] = "SUM(Métricas!" . $colPrimeira7dias . $this->linhas_planilhas['#Cartões:'] . ":" . 
                        $colUltima . $this->linhas_planilhas['#Cartões:'] . ")";
                    $kpi['BoletosGerado_7dias'] = "SUM(Métricas!" . $colPrimeira7dias . $this->linhas_planilhas['#Boletos Gerados:'] . ":" . 
                        $colUltima . $this->linhas_planilhas['#Boletos Gerados:'] . ")";
                    $kpi['BoletosPago_7dias'] = "SUM(Métricas!" . $colPrimeira7dias . $this->linhas_planilhas['#Boletos Pagos:'] . ":" . 
                        $colUltima . $this->linhas_planilhas['#Boletos Pagos:'] . ")";
                    $kpi['BoletosTotal_7dias'] = "(" . $kpi['BoletosPago_7dias']  . "+" . $kpi['BoletosGerado_7dias'] . ")";
                    $kpi['Venda_7dias'] = "(" . $kpi['BoletosPago_7dias']  . "+" . $kpi['Cartao_7dias'] . ")";
                }

                if(isset($colPrimeira3dias))
                {
                    $kpi['Cartao_3dias'] = "SUM(Métricas!" . $colPrimeira3dias . $this->linhas_planilhas['#Cartões:'] . ":" . 
                        $colUltima . $this->linhas_planilhas['#Cartões:'] . ")";
                    $kpi['BoletosGerado_3dias'] = "SUM(Métricas!" . $colPrimeira3dias . $this->linhas_planilhas['#Boletos Gerados:'] . ":" . 
                        $colUltima . $this->linhas_planilhas['#Boletos Gerados:'] . ")";
                    $kpi['BoletosPago_3dias'] = "SUM(Métricas!" . $colPrimeira3dias . $this->linhas_planilhas['#Boletos Pagos:'] . ":" . 
                        $colUltima . $this->linhas_planilhas['#Boletos Pagos:'] . ")";
                    $kpi['BoletosTotal_3dias'] = "(" . $kpi['BoletosPago_3dias']  . "+" . $kpi['BoletosGerado_3dias'] . ")";
                    $kpi['Venda_3dias'] = "(" . $kpi['BoletosPago_3dias']  . "+" . $kpi['Cartao_3dias'] . ")";
                }
            }
            else
            {  
                $kpi['Vendas'] = 0;
                $kpi['Venda_7dias'] = 0;
                $kpi['Venda_3dias'] = 0;

                if($kpi['ViewContents'] < 200)
                    $kpi['ViewContents'] = 200;
                else
                {
                    $vc = (int)($kpi['ViewContents'] / 100);
                    $kpi['ViewContents'] = ($vc * 100) + 100;
                }
            }

        }

        if($vendendo && ($vendendo->cpv != null))
        {
            $kpi['cpv_venda'] = $vendendo->cpv;
            $kpi['ctr_venda'] = $vendendo->ctr;
            $kpi['cpc_venda'] = $vendendo->cpc;
            $kpi['cpm_venda'] = $vendendo->cpm;
            $kpi['roi_venda'] = $vendendo->roi;
            $kpi['spend_venda'] = $vendendo->spend;
            $kpi['boletos_venda'] = $vendendo->p_boletos;
            $kpi['conv_boleto_venda'] = $vendendo->c_boletos;
            $kpi['cartoes_venda'] = $vendendo->p_cartoes;
            $kpi['clpv_venda'] = $vendendo->clpv;
        }
        

        //Ultima_ViewContents

        //Procurar ViewContent Métricas!Geral11 - Geral, Primeira7dias, Primeira3dias
        //Procurar Cartoes Métricas!Geral18 - Geral, Primeira7dias, Primeira3dias, Ultima
        //Cartoes_7dias: SUM(Métricas!Primeira7Dias18:Ultima18)
        //Procurar BoletosGerados Métricas!Geral19
        //Procurar BoletosPagos Métricas!Geral20
        //BoletosPagos_7dias: SUM(Métricas!Primeira7dias20:Ultima20)
        //Vendas: Cartoes + Boletos Pagos (Métricas!Geral18+Métricas!Geral20)
        //Vendas_7dias: (SUM(Métricas!Primeira7Dias18:Ultima18)+SUM(Métricas!Primeira7dias20:Ultima20)
        //BoletosTotais: Boletos gerados + Boletos Pagos (Métricas!Geral19+Métricas!Geral20)
        //BoletosTotais_7dias: (SUM(Métricas!Primeira7dias19:Ultima19)+SUM(Métricas!Primeira7dias20:Ultima20))




        $objPHPExcel->setActiveSheetIndex(1);
        $highestCol = $objPHPExcel->getActiveSheet()->getHighestColumn();
        $highestCol++; 
        $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

        for($col = 'A'; $col != $highestCol; $col++)
        {
            for($row = 1; $row <= $highestRow; $row++)
            {
                $value = $objPHPExcel->getActiveSheet()->getCell($col . $row)->getValue(); 

                foreach($kpi as $key => $val)
                {
                    if(strpos($value,$key) !== false)
                    {   
                        $value = str_replace($key,$val,$value);       
                    }     
                }
                $objPHPExcel->getActiveSheet()->getCell($col . $row)->setValue($value);  
            }
        }   
        
        if(!isset($colPrimeira3dias))
        {
            foreach($colunas_3dias as $coluna)
            {
                $objPHPExcel->getActiveSheet()->getColumnDimension($coluna)->setVisible(FALSE);    
            }
        }

        if(!isset($colPrimeira7dias))
        {
            foreach($colunas_7dias as $coluna)
            {
                $objPHPExcel->getActiveSheet()->getColumnDimension($coluna)->setVisible(FALSE);    
            }
        }

        if($sem_dado_venda)
        {
            for($i=$linhas_2_calculo[0]; $i<=$linhas_2_calculo[1]; $i++)
            {
                $objPHPExcel->getActiveSheet()->getRowDimension($i)->setVisible(FALSE);       
            }
        }

        if($vendendo)
        {
            if(($vendendo->cpv == null))
            {
                for($i=$linhas_vendendo[0]; $i<=$linhas_vendendo[1]; $i++)
                {
                    $objPHPExcel->getActiveSheet()->getRowDimension($i)->setVisible(FALSE);       
                }
            }
        }
        else
        {
            for($i=$linhas_vendendo[0]; $i<=$linhas_vendendo[1]; $i++)
            {
                $objPHPExcel->getActiveSheet()->getRowDimension($i)->setVisible(FALSE);       
            }
        }

        $objPHPExcel->getActiveSheet()->setSelectedCell('A1'); 

        foreach($kpi as $key => $val)
        {
            $objPHPExcel->getActiveSheet()->getCell("ZZ1")->setValue("=" . $val);

            $kpi[$key] = $objPHPExcel->getActiveSheet()->getCell("ZZ1")->getCalculatedValue(true);
        }

        $objPHPExcel->getActiveSheet()->getCell("ZZ1")->setValue("");

        return $kpi;

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
    * @param    $sheet(object): Worksheet sendo usada
    * @return	-
    */
    function inserirConversoes($conversion_array, $sheet)
    {
        $color = '';
        $color_array=array("E4DFEC", "D9EAD3", "DDD9C4", "FCE9D9");

        //Procura onde vai adicionar as conversões
        $row = $this->procura_valor('#TpConversao:', 0, $sheet);

        //Se não tiver conversões, remove do template
        if($conversion_array == null)
        {
            $sheet->removeRow($row-1, 3);

            for($i=1;$i<$sheet->getHighestRow();$i++)
            {
                $value = $sheet->getCell('A' . $i)->getValue();   
                $this->linhas_planilhas[$value] = $i;
                $this->linhas_planilhas[$i] = $value;
            }

            return;
        }

        $names = $conversion_array->name;
        unset($conversion_array->name);
        
        //Se houver mais de 1 conversão (conversão e valor), adiciona a quantidade necessária
        //de linhas de acordo com as conversões. (-2, pois já existem duas linhas)
        if(count(get_object_vars($conversion_array)) > 2)
            $sheet->insertNewRowBefore($row+1, count(get_object_vars($conversion_array)) - 2);

        //Após adicionar o TpConversão sobe, então procura de novo
        $row = $this->procura_valor('#TpConversao:', 0, $sheet)-1;

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
                    if(strpos($valor, 'offsite_conversion.custom') !== false)
                    {
                        $this->linhas_conversoes_personalizadas[] = $row;
                        $this->linhas_conversoes_personalizadas[] = $row+1;
                    }

                    $valor = str_replace('offsite_conversion.', '', $valor);
                }

                $conversoes[$valor] = $names->{$key};

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

        for($i=1;$i<$sheet->getHighestRow();$i++)
        {
            $value = $sheet->getCell('A' . $i)->getValue();   
            $this->linhas_planilhas[$value] = $i;
            $this->linhas_planilhas[$i] = $value;
        }

        $this->linhas_planilhas['conversoes'] = $conversoes;

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