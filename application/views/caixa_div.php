<?php
$arr_status = array(
    "1" => "<font color='green'>ATIVA</font>",
"2" => "<font color='red'>BLOQUEADA</font>",
"3" => "FALHA NO PAGAMENTO",
"7" => "ANÁLISE PENDENTE",
"9" => "FALHA NO PAGAMENTO",
"100" => "FECHAMENTO PENDENTE",
"101" => "FECHADA",
"102" => "PAGAMENTO PENDENTE",
"201" => "ATIVA",
"202" => "FECHADA"
);

foreach($contas as $conta)
{
    if($conta['age'] > 0)
    {
        if($conta_sinc)
            if(array_search($conta['name'],$conta_sinc) !== false)
                continue;
        
        $id = $conta['id'];
        echo "<tr>";
            echo "<td><input type='checkbox' name='chkBoletoGerado' class='chkContaNova' id='" . $id . "'></td>";
            echo "<td>" . $conta['id'] . "</td>"; 
            echo "<td>" . $conta['name'] . "</td>"; 
            echo "<td>" . $arr_status[$conta['account_status']] . "</td>";
        echo "</tr>";
    }
}
?>