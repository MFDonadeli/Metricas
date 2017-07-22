<?php

require_once APPPATH . '/libraries/PHPExcel/IOFactory.php';
define('START_ROW',1);

function generate_excel($dados, $excel)
{
    $file_name_old = FCPATH."template/Template.xlsx";
    $raw_file_name = "Template".md5(mt_rand() . time()).".xlsx";
    $file_name = FCPATH."template/".$raw_file_name;

    copy($file_name_old, $file_name);

    $objPHPExcel = PHPExcel_IOFactory::load($file_name);

    $column = 1;

    inserirConversoes($dados[0]->conversao, $objPHPExcel->getActiveSheet());

    $qtde_colunas = count($dados);

    foreach($dados as $dado)
    {
        if($column != $qtde_colunas)
            duplicate_column($column, $objPHPExcel->getActiveSheet());

        foreach($dado->conversao as $key => $val)
        {
            $dado->{$key} = $val;
        }
        unset($dado->conversao);

        for($row = START_ROW; $row < $objPHPExcel->getActiveSheet()->getHighestRow(); $row++)
        {
            if(($row == START_ROW || $row == START_ROW+1) && $dado->bydate != 1)
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->setValue("Geral"); 
            else
            {
                $value = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->getValue();
                if($value[0] == '#')
                {
                    $campo = str_replace('#', '', $value);    
                    if(isset($dado->{$campo}))
                    {
                        $valor = $dado->{$campo};
                        if($campo == 'date_start')
                        {
                            $date_start = explode(" ", $dado->date_start)[0];
                            $date = DateTime::createFromFormat('Y-m-d', $date_start);
                            $dado->{$campo} = $date->format('d/M');
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

        $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($column, $row)->getValue(); 
        $column++;

    }

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($file_name);

    return $raw_file_name;
}

function duplicate_column($col, $sheet)
{
    for($row=START_ROW; $row<=$sheet->getHighestRow(); $row++)
    {
        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        $style = $sheet->getStyleByColumnAndRow($col, $row);
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

function inserirConversoes($conversion_array, $sheet)
{
    $color = '';
    $names = $conversion_array->name;
    unset($conversion_array->name);
    $color_array=array("E4DFEC", "D9EAD3", "DDD9C4", "FCE9D9");

    $row = procura_valor('#TpConversao:', 0, $sheet);
    $sheet->insertNewRowBefore($row+1, count(get_object_vars($conversion_array)) - 2);

    $row = procura_valor('#TpConversao:', 0, $sheet)-1;

    foreach($conversion_array as $key => $val)
    {
        $valor_b = "#".$key;
        
        if(strpos($key,'Valor por ') !== false)
        {
            $valor = str_replace('Valor por ', '', $key); 
            $valor = 'Valor por ' . $names->{$valor};
        }
        else
        {
            $color = $color_array[$row % count($color_array)];
            $valor = $names->{$key};
        }
            
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
?>