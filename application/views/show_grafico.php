<head>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
</head>

<button id='btnlista'>Gerar Gráfico</button>

<select name="cmbgrafico1" id="cmbgrafico1">
</select>
<select name="cmbgrafico2" id="cmbgrafico2">
</select>

<br>
<canvas id="lineChart" height="120"></canvas>  

<script>
    var dados;
    var LineConfig;
    var myLine = null;

    function getDado(dado)
    {
        var retorno = [];
        $.each(dados, function(key, value) {
            retorno.push(value[dado]);
         });

       retorno.pop();

       return retorno;
    }

    $('#cmbgrafico1').change(function(){
        $('#cmbgrafico2').val('-1').change();

        var randomColorFactor = function() {
            return Math.round(Math.random() * 255);
        };
        var randomColor = function(opacity) {
            return 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',' + (opacity || '.3') + ')';
        };
        
        if(myLine != null)
        {
            console.log(myLine);
            myLine.destroy();
        }

        var x = getDado('date_start');
        var y = getDado($(this).val());

        LineConfig = {
		            type: 'line',
		            data: {
		                labels: x,
		                datasets: [{
		                    label: $(this).find(':selected').text(),
		                    data: y,
		                    
		                }]
		            },
		            options: {
		                responsive: true,
		                tooltips: {
		                    mode: 'label'
		                },
		                hover: {
		                    mode: 'dataset'
		                },
		                scales: {
		                    xAxes: [{
		                        display: true,
		                        scaleLabel: {
		                            show: true,
		                            labelString: 'Month'
		                        }
		                    }],
		                    yAxes: [{
		                        display: true,
		                        scaleLabel: {
		                            show: true,
		                            labelString: 'Value'
		                        },
		                        ticks: {
		                            suggestedMin: Math.min.apply(null, y),
		                            suggestedMax: Math.max.apply(null, y),
		                        }
		                    }]
		                }
		            }
		        };
		        $.each(LineConfig.data.datasets, function(i, dataset) {
		            dataset.borderColor = 'rgba(0,0,0,0.15)';
		            dataset.backgroundColor = randomColor(0.5);
		            dataset.pointBorderColor = 'rgba(0,0,0,0.15)';
		            dataset.pointBackgroundColor = randomColor(0.5);
		            dataset.pointBorderWidth = 1;
		        });

        myLine = new Chart(document.getElementById("lineChart"), LineConfig);
    });

    $('#cmbgrafico2').change(function(){
        var randomColorFactor = function() {
            return Math.round(Math.random() * 255);
        };
        var randomColor = function(opacity) {
            return 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',' + (opacity || '.3') + ')';
        };
        
        if(myLine != null)
        {
            console.log(myLine);
            myLine.destroy();
        }

        var x = getDado('date_start');
        var y1 = getDado($('#cmbgrafico1').val());
        var y2 = getDado($(this).val());

        LineConfig = {
		            type: 'line',
		            data: {
		                labels: x,
		                datasets: [{
		                    label: $('#cmbgrafico1').find(':selected').text(),
		                    data: y1, 
                            yAxisID: 'y-axis-1',  
		                },
                        {
		                    label: $(this).find(':selected').text(),
		                    data: y2,  
                            yAxisID: 'y-axis-2', 
		                }],
		            },
		            options: {
		                responsive: true,
		                tooltips: {
		                    mode: 'label'
		                },
		                hover: {
		                    mode: 'dataset'
		                },
		                scales: {
		                    xAxes: [{
		                        display: true,
		                        scaleLabel: {
		                            show: true,
		                            labelString: 'Month'
		                        }
		                    }],
		                    yAxes: [{
		                        display: true,
								position: "left",
		                        scaleLabel: {
		                            show: true,
		                            labelString: 'Value'
		                        },
		                        ticks: {
		                            suggestedMin: Math.min.apply(null, y1),
		                            suggestedMax: Math.max.apply(null, y1),
		                        },
                                id: 'y-axis-1'
		                    },
                            {
                                display: true,
								position: "right",
		                        scaleLabel: {
		                            show: true,
		                            labelString: 'Value'
		                        },
		                        ticks: {
		                            suggestedMin: Math.min.apply(null, y2),
		                            suggestedMax: Math.max.apply(null, y2),
		                        },
                                id: 'y-axis-2'    
                            }]
		                }
		            }
		        };
		        $.each(LineConfig.data.datasets, function(i, dataset) {
		            dataset.borderColor = 'rgba(0,0,0,0.15)';
		            dataset.backgroundColor = randomColor(0.5);
		            dataset.pointBorderColor = 'rgba(0,0,0,0.15)';
		            dataset.pointBackgroundColor = randomColor(0.5);
		            dataset.pointBorderWidth = 1;
		        });

        myLine = new Chart(document.getElementById("lineChart"), LineConfig);
    });

    $('#btnlista').click(function(){
        $.ajax({
            url: 'http://localhost/~mfdonadeli/metricas/app/show_table/23842632230480642/campaign/10',
            type: 'POST',
            data: '',
            global: false,
            async:true,
            success: function(msg) { 
                var obj = $.parseJSON(msg);
                processa_retorno(obj);
            }
        });
    });

    function processa_retorno(obj)
    {
       console.log(obj);
	   console.log(obj.resumo);
	   console.log(obj.resumo.body);
       
       var aaa = "<option value='-1'>Selecione</option>";
       aaa += "<option value='cost_per_inline_link_click'>CPC</option>";
       aaa += "<option value='cpm'>CPM</option>";
       aaa += "<option value='inline_link_click_ctr'>CTR</option>";
       aaa += "<option value='roi'>ROI</option>";

       $.each(obj.nomes_conversoes, function(key, value) {
           aaa += "<option value='" + key + "'>" + value + "</option>";
           aaa += "<option value='Custo por " + key + "'>Custo por " + value + "</option>"  
       });

       $('#cmbgrafico1').append(aaa);
       $('#cmbgrafico2').append(aaa);

       dados = obj.dados;
    }
    
</script>
