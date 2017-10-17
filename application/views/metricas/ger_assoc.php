
<?php require_once("inc/init.php"); ?>

<div class="row">
	<div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
		<h1 class="page-title txt-color-blueDark">
			<i class="fa fa-list-alt"></i> 
			Vendas na Plataforma
		</h1>
	</div>
</div>

<div class="well">
    <div class='form-group'>
        <label class='col-md-2 control-label' for="cmbAnuncio">Selecione um anúncio:</label>
        <select class='form-control' id="cmbAnuncio">
            <option value="-1">Selecione</option>
            <?php
                foreach($anuncios as $anuncio)
                {
            ?>
                    <option value='<?php echo $anuncio->id ?>'>
                        Anuncio: <?php echo $anuncio->name; ?> - Tag: <?php echo $anuncio->url_tags; ?> -
                        Status: <?php echo $anuncio->effective_status; ?> - Conjunto: <?php echo $anuncio->conjunto; ?> - 
                        Campanha: <?php echo $anuncio->campanha; ?> - Conta: <?php echo $anuncio->conta; ?>
                    </option>
            <?php        
                }
            ?>
        </select>
    </div>

    <div id="div_vendas">
        
    </div>
</div>

<script>
    var id;
    var plataforma;

    $('#cmbAnuncio').change(function (){
        var form_data = { ad_id: $('#cmbAnuncio').val() };

        $.ajax({
            url: '<?php echo base_url(); ?>app/show_vendas_assoc',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 

                $('#div_vendas').html(msg);
                $('#div_novaVenda').hide();
                setUpGrids();

            }
        });
    });

    $(document).on('click', '#btn_venda_manual', function(e)  {
        $('#div_novaVenda').show();    
    });

    $(document).on('click', '#btnsalvar', function(e)  {
        e.preventDefault();
        var form_data = { data: $('#dt_venda').val(),
            ad_id: $('#cmbAnuncio').val(),
            tipo: $('#cmbtipo').find(':selected').text(),
            plataforma: $('#cmbplataforma').find(':selected').text(),
            produto: $('#cmbproduto').find(':selected').text(),
            comissao: $('#txt_comissao').val()
        };

        $.ajax({
            url: '<?php echo base_url(); ?>app/grava_ad_venda_manual',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 
                $('#div_vendas').html(msg);
                $('#div_novaVenda').hide();
            }
        });    
    });

    $(document).on('change', '#cmbplataforma', function(e)  {
        var form_data = { 
            plataforma: $('#cmbplataforma').find(':selected').text()
        };

        $.ajax({
            url: '<?php echo base_url(); ?>app/get_produtos_plataforma',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 

                $('#cmbproduto').html(msg);

                var produto = ($('#nome_produto').html()).trim();
                if(produto != '')
                {
                    console.log('Produto: [' + produto + ']');
                    var a = $("#cmbproduto option:contains(" + produto + ")").attr('selected', true);
                    console.log(a);
                    $('#cmbproduto').trigger('change');
                }

            }
        });    
    });

    $(document).on('change', '#cmbproduto', function(e)  {
        $('#txt_comissao').val($(this).find(':selected').data('comissao'));  
    });

    $(document).on('click', '.btn_cancelavenda', function (e){
        var form_data = { 
            id_ads_vendas: $(this).attr('id')
        };

        $.ajax({ 
            url: '<?php echo base_url(); ?>app/cancela_associacao_postback',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 
                
            }
        });    
    });

    var responsiveHelper_dt_vendas_associadas;
    var table_dt_vendas_associadas;


    function setUpGrids()
    {
        $(".desktop[data-rel='tooltip']").tooltip();
        $(".phone[data-rel='tooltip']").tooltip({placement: tooltip_placement});
        function tooltip_placement(context, source) {
            var $source = $(source);
            var $parent = $source.closest("table")
            var off1 = $parent.offset();
            var w1 = $parent.width();

            var off2 = $source.offset();
            var w2 = $source.width();

            if( parseInt(off2.left) < parseInt(off1.left) + parseInt(w1 / 2) ) return "right";
            return "left";
        }

        $(document).on("click", "#dt_vendas_associadas a i[data-toggle='row-detail']", function (e) {
            var nTr = $(this).parents("tr")[0];
            if ( table_dt_vendas_associadas.fnIsOpen(nTr) )
            {
                /* This row is already open - close it */
                $(this).removeClass("fa-chevron-down").addClass("fa-chevron-right");
                this.title = "Show Details";
                table_dt_vendas_associadas.fnClose( nTr );
            }
            else
            {
                /* Open this row */
                $(this).removeClass("fa-chevron-right").addClass("fa-chevron-down");
                this.title = "Hide Details";
                table_dt_vendas_associadas.fnOpen( nTr, fnFormatDetails(table_dt_vendas_associadas, nTr), "details" );
            }
            return false;
        });
        table_dt_vendas_associadas = $("#dt_vendas_associadas").dataTable({
            "sDom": "<'dt-toolbar'<'col-xs-12 col-sm-6 hidden-xs'f><'col-sm-6 col-xs-12 hidden-xs'<'toolbar'>>r>"+
            "t"+
            "<'dt-toolbar-footer'<'col-sm-6 col-xs-12 hidden-xs'i><'col-xs-12 col-sm-6'p>>",
        });

        // remove pagination div row ? TO-DO

        

        function fnFormatDetails ( table_dt_vendas_associadas, nTr ) {
            var aData = table_dt_vendas_associadas.fnGetData( nTr );

            var form_data = { 
                data: aData[1],
                ad_id: $('#cmbAnuncio').val()
            };

            $.ajax({
                url: '<?php echo base_url(); ?>app/get_vendas_dia',
                type: 'POST',
                data: form_data,
                global: false,
                async:true,
                success: function(msg) { 

                    $('#div_vendas_dias' + aData[1]).html(msg);

                }
            }); 

            return "<div id='div_vendas_dias" + aData[1] + "'></div>";
            
        }

        $("#dt_vendas_associadas th input:checkbox").on("click" , function(){
            var that = this;
            $(this).closest("table").find("tr > td input:checkbox").each(function(){
                this.checked = that.checked;
                //$(this).closest("tr").toggleClass("selected");
            });
        });
    }
</script>

<script type="text/javascript">
	
	/* DO NOT REMOVE : GLOBAL FUNCTIONS!
	 *
	 * pageSetUp(); WILL CALL THE FOLLOWING FUNCTIONS
	 *
	 * // activate tooltips
	 * $("[rel=tooltip]").tooltip();
	 *
	 * // activate popovers
	 * $("[rel=popover]").popover();
	 *
	 * // activate popovers with hover states
	 * $("[rel=popover-hover]").popover({ trigger: "hover" });
	 *
	 * // activate inline charts
	 * runAllCharts();
	 *
	 * // setup widgets
	 * setup_widgets_desktop();
	 *
	 * // run form elements
	 * runAllForms();
	 *
	 ********************************
	 *
	 * pageSetUp() is needed whenever you load a page.
	 * It initializes and checks for all basic elements of the page
	 * and makes rendering easier.
	 *
	 */

	pageSetUp();
	
	/*
	 * ALL PAGE RELATED SCRIPTS CAN GO BELOW HERE
	 * eg alert("my home function");
	 * 
	 * var pagefunction = function() {
	 *   ...
	 * }
	 * loadScript("js/plugin/_PLUGIN_NAME_.js", pagefunction);
	 * 
	 * TO LOAD A SCRIPT:
	 * var pagefunction = function (){ 
	 *  loadScript(".../plugin.js", run_after_loaded);	
	 * }
	 * 
	 * OR you can load chain scripts by doing
	 * 
	 * loadScript(".../plugin.js", function(){
	 * 	 loadScript("../plugin.js", function(){
	 * 	   ...
	 *   })
	 * });
	 */
	
	// pagefunction
	
	var pagefunction = function() {
        $( "#tabs" ).tabs();
	};
	
	// end pagefunction
	
	// run pagefunction
	pagefunction();

    // load related plugins

    var path = "<?php echo base_url(); ?>assets/";
	
	loadScript(path+"js/plugin/datatables/jquery.dataTables.min.js", function(){
		loadScript(path+"js/plugin/datatables/dataTables.colVis.min.js", function(){
			loadScript(path+"js/plugin/datatables/dataTables.tableTools.min.js", function(){
				loadScript(path+"js/plugin/datatables/dataTables.bootstrap.min.js", function(){
					loadScript(path+"js/plugin/datatable-responsive/datatables.responsive.min.js", pagefunction)
				});
			});
		});
	});
	
</script>