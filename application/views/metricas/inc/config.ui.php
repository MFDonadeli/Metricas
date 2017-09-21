<?php

//CONFIGURATION for SmartAdmin UI

//ribbon breadcrumbs config
//array("Display Name" => "URL");
$breadcrumbs = array(
	"Home" => APP_URL
);

/*navigation array config

ex:
"dashboard" => array(
	"title" => "Display Title",
	"url" => "http://yoururl.com",
	"url_target" => "_blank",
	"icon" => "fa-home",
	"label_htm" => "<span>Add your custom label/badge html here</span>",
	"sub" => array() //contains array of sub items with the same format as the parent
)

*/
$page_nav = array(
	"inicio" => array(
		"title" => "Métricas",
		"icon" => "fa-home",
		"url" => "main"
	),
	"contas" => array(
		"title" => "Gerenciar Contas",
		"icon" => "fa-pencil-square-o",
		"url" => "ger_contas"
	),
	"vendas" => array(
		"title" => "Vendas na Plataforma",
		"icon" => "fa-list-alt",
		"sub" => array(
			"associar_vendas" => array(
				"title" => "Associar Vendas",
				"icon" => "fa-list-alt",
				"url" => "associa_postback"
			),
			"gerenciar_associacoes" => array(
				"title" => "Gerenciar Vendas Atribuídas",
				"icon" => "fa-list-alt",
				"url" => "gerencia_postback"
			)
		)
	),
	"config" => array(
		"title" => "Configurações",
		"icon" => "fa-gear",
		"sub" => array(
			"geral" => array(
				"title" => "Geral",
				"icon" => "fa-list-alt",
				"url" => "config"
			),
			"postback" => array(
				"title" => "Gerenciar Postback",
				"icon" => "fa-globe",
				"url" => "postback"
			)
		)
	)
);

//configuration variables
$page_title = "";
$page_css = array();
$no_main_header = false; //set true for lock.php and login.php
$page_body_prop = array(); //optional properties for <body>
$page_html_prop = array(); //optional properties for <html>
?>