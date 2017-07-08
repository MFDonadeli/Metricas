<?php
    echo count($metricas[0]);

    foreach($metricas[0] as $key=>$val)
    {
        if($key == 'bydate') continue; 

        $arr_keys[] = $key;        
    }

?>
<table border=1>
<?php    
    foreach($arr_keys as $key)
    {
?>
        <tr>
            <td><?php echo $key; ?></td>
<?php
        foreach($metricas as $metrica)
        {
            if($key == 'date_start')
            {
                if($metrica->bydate == '1')
                {
                    $date_start = explode(" ", $metrica->date_start)[0];
                    $date = DateTime::createFromFormat('Y-m-d', $date_start);
                    //$array_output["Dia_da_Semana"] = $diasemana[$date->format('w')];
?>
                    <td><?php echo $date->format('d/m'); ?></td>
<?php
                }    
                else
                {
?>
                    <td>Geral</td>
<?php
                }
            }
            else
            {
?>
                <td><?php echo $metrica->{$key}; ?></td>
<?php
            }
        }
?>
        </tr>
<?php
    }

    //for($i=0;$i=count($metricas[0])
?>
</table>