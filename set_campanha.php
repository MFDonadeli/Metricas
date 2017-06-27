<?php
    include 'mysqldb.php';
    $db = new MyDB();
    

    $tipos = array("minimo", "medio", "maximo");

    if($_POST['id_campanha']>0 || isset($_POST['nome_campanha']))
    {
        $campanha = array();
        if($_POST['id_campanha'] != '')
        {
            $id = $_POST['id_campanha'];
            $insert = false;     
        }
        else
        {
            $insert = true;  
            $campanha['nome'] = $_POST['nome_campanha'];                             
        }

        $campanha['id_produto'] = $_POST['produto'];  

        if($insert)
        {
            $sql = "insert into campanha (nome, id_produto) values ('" . implode("', '", $campanha) . "');";
            $db->execSQL($sql);            
            $sql = "SELECT last_insert_rowid()";
            $res = $db->execSQLQuery($sql); 
            $row = $res->fetchArray();
            $id = $row[0];    
        }  

        $anuncios = $_POST['anuncios'];
        $addescricao = $_POST['conteudo']; 
        for($i=0; $i<count($anuncios); $i++)
        {
            $anuncio_campanha[$anuncios[$i]] = $addescricao[$i];
        }

        if(isset($_POST['existentes']))
        {
            $existentes = $_POST['existentes'];
            $desc_existentes = $_POST['descexiste'];

            for($i=0; $i<count($existentes); $i++)
            {
                $anuncio_campanha[$existentes[$i]] = $desc_existentes[$i];
            }
        }

        $sql = "delete from campanha_ads where id_campanha = '" . $id . "';";
        $db->execSQL($sql);
        $sqls = '';
        foreach($anuncio_campanha as $key => $val)
        {
            $sql = "insert into campanha_ads (id_campanha, ad, ad_name) values ('" .
                $id . "', '" . $key . "', '" . $val . "');";
            $r = $db->execSQL($sql); 
            if(!$r)
                $erro = $db->lastErrorMsg();
            else
                $sqls .= $sql;
        }
        echo $sqls;        

    }
?>