<?php
    include 'mysqldb.php';
    $db = new MyDB();

    if($_POST['id_produto']>0)
    {
        $res = $db->execSQLQuery('select * from produto_metricas where id_produto = ' . $_POST['id_produto'] . ';');
        
        while ($row = $res->fetchArray())
        {
            if($row['tipo'] == 'minimo')
            {
                $ret['cliques_minimo'] = $row['cliques'];
                $ret['cpc_minimo'] = $row['cpc'];
                $ret['cpm_minimo'] = $row['cpm'];
                $ret['ctr_minimo'] = $row['ctr'];
                $ret['impressao_minimo'] = $row['impressoes'];                
            } 
            else if($row['tipo'] == 'medio')
            {
                $ret['cliques_medio'] = $row['cliques'];
                $ret['cpc_medio'] = $row['cpc'];
                $ret['cpm_medio'] = $row['cpm'];
                $ret['ctr_medio'] = $row['ctr'];
                $ret['impressao_medio'] = $row['impressoes'];                
            }  
            else if($row['tipo'] == 'maximo')
            {
                $ret['cliques_maximo'] = $row['cliques'];
                $ret['cpc_maximo'] = $row['cpc'];
                $ret['cpm_maximo'] = $row['cpm'];
                $ret['ctr_maximo'] = $row['ctr'];
                $ret['impressao_maximo'] = $row['impressoes'];                
            }     
        }
        $res = $db->execSQLQuery('select * from produto where id = ' . $_POST['id_produto'] . ';');
        if ($row = $res->fetchArray())
        {
            $ret['comissao'] = $row['comissao'];
        }
        
        echo json_encode($ret);
    }
?>