<?php
    include 'mysqldb.php';
    $db = new MyDB();

    if(isset($_POST['id_campanha']))
    {
        $res = $db->execSQLQuery('select * from campanha_ads where id_campanha = ' . $_POST['id_campanha'] . ';');
        
        while ($row = $res->fetchArray())
        {
            $ret[] = array('key' => $row['ad'], 'val' => $row['ad_name']);  
        }
        
        echo json_encode($ret);
    }
?>