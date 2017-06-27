<?php
    include 'mysqldb.php';
    $db = new MyDB();

    $tipos = array("minimo", "medio", "maximo");

    if($_POST['id_produto']>0 || isset($_POST['produto']))
    {
        $produto = array();
        if($_POST['id_produto'] > 0)
        {
            $id = $_POST['id_produto'];
            $insert = false;     
        }
        else
        {
            $insert = true;         
        }

        $produto['produto'] = $_POST['produto'];  
        $produto['comissao'] = $_POST['comissao'];

        if($insert)
        {
            $sql = "insert into produto (nome, comissao) values ('" . implode("', '", $produto) . "');";
            $db->execSQL($sql);            
            $sql = "SELECT last_insert_rowid()";
            $res = $db->execSQLQuery($sql); 
            $row = $res->fetchArray();
            $id = $row[0];    
        }  
        else
        {
            $sql = "update produto set comissao = '" . $_POST['comissao'] . "' WHERE id = " . $_POST['id_produto'] . ";";
            $db->execSQL($sql);  
            $id = $_POST['id_produto'];          
        }  


        foreach($tipos as $tipo)
        {
            $insere = false;
            $ret['id_produto'] = $id;
            if($_POST['cliques_' . $tipo]) 
            {
                $insere = true;
                $ret['cliques'] = $_POST['cliques_' . $tipo];                                  
            }
            if($_POST['cpc_' . $tipo] != "") 
            {
                $insere = true;
                $ret['cpc'] = $_POST['cpc_' . $tipo];                                  
            }
            if($_POST['cpm_' . $tipo] != "") 
            {
                $insere = true;
                $ret['cpm'] = $_POST['cpm_' . $tipo];                                    
            }
            if($_POST['ctr_' . $tipo] != "") 
            {
                $insere = true;
                $ret['ctr'] = $_POST['ctr_' . $tipo];                                  
            }
            if($_POST['impressao_' . $tipo] != "") 
            {
                $insere = true;
                $ret['impressao'] = $_POST['impressao_' . $tipo];                                  
            }

            if($insere)
            {
                $ret['tipo'] = $tipo;
                $sql = "delete from produto_metricas where id_produto = " . $id . " and tipo = '" . $tipo . "';";
                $db->execSQL($sql);
                $sql = "insert into produto_metricas (" . implode(", ", array_keys($ret)) . ") values ('" .
                    implode("','", $ret) . "');";
                $db->execSQL($sql);
            }
                
        }
                

    }
?>