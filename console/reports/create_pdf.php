<?php
    require_once('fusioncharts.php');
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Audit Suit - Report</title>
	<link href="https://bluehand.com.mx/console/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://bluehand.com.mx/console/css/app.css" rel="stylesheet">
	<link href="https://bluehand.com.mx/console/css/questionnaires.css" rel="stylesheet">
    <script src="https://bluehand.com.mx/console/js/fusioncharts/fusioncharts.js"></script>
    <script src="https://bluehand.com.mx/console/js/fusioncharts/themes/fusioncharts.theme.ocean.js"></script>
</head>
<body>
	<input type="hidden" id="hdn_report_id" value="<?php echo $_GET['report_id']; ?>">
	<nav class="navbar navbar-inverse navbar-fixed-top">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="#">Audit Suit</a>
			</div>
		</div>
	</nav>
    <br>
	<!-- body -->
	<div class="container fifty-top">
		<div class="page-header">
		</div>

        <div class="panel panel-default">
        	<div class="panel-heading">
    			<h3 class="panel-title">Reporte No: <span id="sp_report_number"></span></h3>
  			</div>
  			
  			<div class="panel-body">
  				<table width="100%">
  					<tr>
  						<td><b>CLIENTE:</b></td>
  						<td><span id="sp_client"></span></td>
  						<td><b>SUCURSAL:</b></td>
  						<td><span id="sp_branch"></span></td>
  					</tr>
  					<tr>
  						<td><b>CUESTIONARIO:</b></td>
  						<td><span id="sp_questionnaire"></span></td>
  						<td><b>CÓDIGO:</b></td>
  						<td><span id="sp_questionnaire_code"></span></td>
  					</tr>
  					<tr>
  						<td><b>AUDITOR:</b></td>
  						<td colspan="3"><span id="sp_auditor"></span></td>
  					</tr>
  					<tr>
  						<td><b>FECHA DE INICIO:</b></td>
  						<td colspan="3"><span id="start_date"></span></td>  						
  					</tr>
  					<tr>
  						<td><b>FECHA DE FINALIZACIÓN:</b></td>
  						<td colspan="3"><span id="end_date"></span></td>  						
  					</tr>
  				</table>
  			</div>
		</div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Resultado total: <span id="span_total_percentage"></span> de 100%.</h3>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Gráfica de resultados</h3>
            </div>
            
            <?php 
                $feed = "http://bluehand.com.mx/backend/api/v1/reports/percentage-by-section-chart/".$_GET['report_id'];

                $arr_json = file_get_contents($feed);

                $column2dChart = new FusionCharts("Column2D", "myFirstChart" , 600, 300, "chart-1", "json",
                '{
                    "chart": {
                        "caption": "Resultados por seccion",
                        "xAxisName": "Secciones",
                        "yAxisName": "Puntuación",
                        "theme": "ocean"
                    },
                    "data": '.$arr_json.'
                }');

                // Render the chart
                $column2dChart->render();
            ?>
            <div class="panel-body" id="chart-1" style="text-align: center;">
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Resultados por sección</h3>
            </div>
            
            <div class="panel-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>SECCIÓN</th>
                            <th>RESULTADO</th>
                        </tr>    
                    </thead>
                    <tbody id="tbody_sections_result"></tbody>
                </table>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Áreas de oportunidad</h3>
            </div>
            
            
                <div class="panel-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>SECCIÓN</th>
                                <th>PREGUNTA</th>
                                <th>RESPUESTA</th>
                                <th>PUNTAJE</th>
                                <!--<th>OBSERVACIONES</th>-->
                            </tr>
                        </thead>
                        <tbody id="tbody_opportunity"></tbody>
                    </table>
                </div>
            
        </div>

	</div>

    <script src="https://bluehand.com.mx/console/js/jquery-1.12.4.min.js"></script>
    <script src="https://bluehand.com.mx/console/js/bootstrap.min.js"></script>
    <script src="https://bluehand.com.mx/console/js/location.js"></script>
    <script src="https://bluehand.com.mx/console/js/report.js"></script>
    <script>
        $(document).ready(function(){
            getGeneralData();
            getResultsBySection(0);
            getReportQuestions();
            getOpportunityAreas();
        });
    </script>
</body>
</html>