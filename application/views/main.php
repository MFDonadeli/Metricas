<!--
<html>
    <body>
        <h1>Bem vindo ao Métricas</h1>
        <?php if($this->session->userdata('logged_in')): ?>
        Logado!
        <?php else: ?>
          
        <?php endif; ?>     
    </body>
</html>
-->

<?php

//initilize the page
require_once("metricas/inc/init.php");

//require UI configuration (nav, ribbon, etc.)
require_once("metricas/inc/config.ui.php");

/*---------------- PHP Custom Scripts ---------

YOU CAN SET CONFIGURATION VARIABLES HERE BEFORE IT GOES TO NAV, RIBBON, ETC.
E.G. $page_title = "Custom Title" */

$page_title = "Login";

/* ---------------- END PHP Custom Scripts ------------- */

//include header
//you can add your custom css in $page_css array.
//Note: all css files are inside css/ folder
$page_css[] = "your_style.css";
$no_main_header = true;
$page_body_prop = array("id"=>"extr-page", "class"=>"animated fadeInDown");
include("metricas/inc/header.php");

?>
<!-- ==========================CONTENT STARTS HERE ========================== -->
<!-- possible classes: minified, no-right-panel, fixed-ribbon, fixed-header, fixed-width-->
<header style='height:100px' id="header">
	<!--<span id="logo"></span>-->
		<span id="logo"> <img style='width:300px !important;' src="<?php echo ASSETS_URL; ?>/img/super.png" alt="SuperAdMetrics"> </span>
</header>

<div id="main" role="main">

	<!-- MAIN CONTENT -->
	<div id="content" class="container">

		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-7 col-lg-8 hidden-xs hidden-sm">
				<h1 class="txt-color-red login-header-big">SuperAdMetrics</h1>
			</div>
			<div class="col-xs-12 col-sm-12 col-md-5 col-lg-4">
				<div class="well no-padding">
					<form action="<?php echo APP_URL; ?>" id="login-form" class="smart-form client-form">
						<header>
							Sign In
						</header>

                        <div style='margin:10px;'>
						<a href='<?php echo $authUrl; ?>'><img width='60%' src='<?php echo base_url(); ?>assets/facebook.png'></a> 
                        </div> 
                    </form>

				</div>
				
				
			</div>
		</div>
	</div>

</div>
<!-- END MAIN PANEL -->
<!-- ==========================CONTENT ENDS HERE ========================== -->

<?php 
	//include required scripts
	include("metricas/inc/scripts.php"); 
?>

<!-- PAGE RELATED PLUGIN(S) 
<script src="..."></script>-->

<script type="text/javascript">
	runAllForms();

	$(function() {
		// Validation
		$("#login-form").validate({
			// Rules for form validation
			rules : {
				email : {
					required : true,
					email : true
				},
				password : {
					required : true,
					minlength : 3,
					maxlength : 20
				}
			},

			// Messages for form validation
			messages : {
				email : {
					required : 'Please enter your email address',
					email : 'Please enter a VALID email address'
				},
				password : {
					required : 'Please enter your password'
				}
			},

			// Do not change code below
			errorPlacement : function(error, element) {
				error.insertAfter(element.parent());
			}
		});
	});
</script>

<?php 
	//include footer
	include("metricas/inc/google-analytics.php"); 
?>