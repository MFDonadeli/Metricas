<?php
   class MyDB extends SQLite3
   {
      function __construct()
      {
         $this->open('test.db');
      }

      function createTableMetrics($id)
      {
          $sql = "CREATE TABLE IF NOT EXISTS t" . $id;
          $sql .= " (
            `Dia`	TEXT,
            `Cartao`	TEXT,
            `Boleto`	TEXT,
            `Compensados`	TEXT,
            `Total`	TEXT,
            `Comissao`	TEXT,
            `ROI`	TEXT
            );";

          $this->execSQL($sql);            
      }

      function createTableAnuncios()
      {
          $sql = "CREATE TABLE IF NOT EXISTS anuncios";
          $sql .= " (
            `id`	TEXT,
            `nome`	TEXT,
            `id_adset`	TEXT,
            `adset`	TEXT,
            `id_campanha`	TEXT,
            `campanha`	TEXT,
            `id_conta` TEXT,
            `grupo`	INTEGER,
            `configuracoes` TEXT,
            `link` TEXT,
            `tag` TEXT
            );";

          $this->execSQL($sql);            
      }

      function saveExtrasToDb($anuncio, $adset, $configuracoes, $link, $tag)
      {
        $this->execSQLQuery("UPDATE anuncios SET configuracoes = '" . $configuracoes . "', link = '" . $link . "', tag = '" . $tag . "' WHERE id = '" . $anuncio . "';");  
      }

      function checkAnuncio($id)
      {
        $ret = $this->execSQLQuery("SELECT count(*) FROM anuncios WHERE id = '" . $id . "';");
        $row = $ret->fetchArray();

        if($row[0] > 0)
            return true;
        else
            return false;
      }

      function addAnuncio($arr)
      {
          $keys = implode(", ", array_keys($arr));
          $vals = implode("', '", $arr);

          $ret = $this->execSQLQuery("INSERT INTO anuncios (". $keys . ")
           VALUES ('" . $vals . "');");
      }

        

      function saveAnuncio($arr)
      {
          $ret = $this->execSQLQuery("SELECT * FROM anuncios WHERE id = '" . $arr['id'] . "';");
          $retorno = array();
          
            if($ret)
            {
                $this->execSQLQuery("UPDATE anuncios SET configuracoes = '" . $configuracoes . "', link = '" . $link . "', tag = '" . $tag . "' WHERE id = '" . $anuncio . "';");  
            }

            return $retorno;
      }

      function createTables()
      {
          $sql_produto = "CREATE TABLE IF NOT EXISTS `produto` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT, `nome` TEXT, `comissao` TEXT )";
          $sql_produto_metricas = "CREATE TABLE IF NOT EXISTS `produto_metricas` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT, `id_produto` INTEGER, `cliques` INTEGER, `cpm` TEXT, `cpc` TEXT, `ctr` TEXT, `impressoes` INTEGER, `tipo` INTEGER )";
          $sql_campanha = "CREATE TABLE IF NOT EXISTS `campanha` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT, `id_produto` INTEGER, `nome` TEXT )";
          $sql_campanha_ads = "CREATE TABLE IF NOT EXISTS `campanha_ads` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT, `id_campanha` INTEGER, `ad` INTEGER, `ad_name` TEXT, `adset` INTEGER, `adset_name` TEXT, `campaign` INTEGER, `campaign_name` TEXT, `conta` INTEGER, `conta_name` TEXT )";
          $sql_sinc = "CREATE TABLE IF NOT EXISTS `sinc` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT, `id_resultado` TEXT, `data` TEXT )";          

          $this->execSQL($sql_produto);
          $this->execSQL($sql_produto_metricas);
          $this->execSQL($sql_campanha);
          $this->execSQL($sql_campanha_ads);
          $this->execSQL($sql_sinc);          
      }


      function execSQL($sql)
      {
          $ret = $this->exec($sql);
          return $ret;
      }

      function execSQLQuery($sql)
      {
          $ret = $this->query($sql);
          return $ret;
      }

      function getMetricas($id)
      {
          $ret = $this->execSQLQuery("SELECT * FROM t" . $id);
          $retorno = array();
          
            if($ret)
            {
                while ($row = $ret->fetchArray())
                {
                    $retorno[$row['Dia']] = $row;
                }
            }

            return $retorno;
      }

      function getDataSinc($id)
      {
          $retorno = null;
          $ret = $this->execSQLQuery("SELECT data FROM sinc WHERE id_resultado = '" . $id . "'"); 
          if($ret)
          {
            $row = $ret->fetchArray();
            $retorno = $row['data'];
          }

          $date = date('Y-m-d', time());
          $ret = $this->execSQLQuery("DELETE FROM sinc WHERE id_resultado = '" . $id . "'");     
          $this->execSQLQuery("INSERT INTO sinc (id_resultado, data) VALUES ('" . $id . "','" . $date . "')");      

          return $retorno;
      }


      
   }

?>