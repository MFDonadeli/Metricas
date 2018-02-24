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
	"principal" => array(
		"title" => "Painel Principal",
		"icon" => "fa-home",
		"url" => "painel"
	),
	"inicio" => array(
		"title" => "Métricas",
		"icon" => "fa-list-alt",
		"url" => "main"
	),
	"config" => array(
		"title" => "Configurações",
		"icon" => "fa-gear",
		"sub" => array(
			"contas" => array(
				"title" => "1. Configure suas contas",
				"icon" => "fa-list-alt",
				"url" => "ger_contas"
			),
			"postback" => array(
				"title" => "2. Configure os postbacks",
				"icon" => "fa-globe",
				"url" => "postback"
			),
			"geral" => array(
				"title" => "3. Configure o comportamento do sistema",
				"icon" => "fa-globe",
				"url" => "config"
			)
		)
	),
	"vendas" => array(
		"title" => "Gerenciamento de Vendas",
		"icon" => "fa-list-alt",
		"sub" => array(
			"associar_vendas" => array(
				"title" => "Associar vendas da plataforma com os anúncios Facebook",
				"icon" => "fa-list-alt",
				"url" => "associa_postback"
			),
			"gerenciar_associacoes" => array(
				"title" => "Gerenciar vendas associadas",
				"icon" => "fa-list-alt",
				"url" => "gerencia_postback"
			),
			"desempenho_vendas" => array(
				"title" => "Desempenho dos seus produtos",
				"icon" => "fa-list-alt",
				"url" => "desempenho_produto"
			)
		)
	),
	"tutorial" => array(
		"title" => "Tutoriais",
		"icon" => "fa-gear",
		"url" => "tutorial"
	)
);

//configuration variables
$page_title = "";
$page_css = array();
$no_main_header = false; //set true for lock.php and login.php
$page_body_prop = array(); //optional properties for <body>
$page_html_prop = array(); //optional properties for <html>
?>