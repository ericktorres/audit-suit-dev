var user_type = localStorage.getItem('audit-suite-user-type');
var user_id = sessionStorage.getItem('audit-suite-user-id');
var user_privileges = localStorage.getItem('audit-suite-privilege-user');
var is_reviser = localStorage.getItem('audit-suite-user-reviser');


//Listado para usuario Administrador

	$("#myInput").show();
	$("#myInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tbody_reports tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
var getReportsAdmin = function(){
	console.log("Administrador");
	//tfinalizado

	var html = '';
	$('#thead_reports').html('<tr><th>No.</th><th>SITIO</th><th>AUDITOR</th><th>CUESTIONARIO</th><th>FECHA AUDITORIA</th><th style="text-align: center;">REVISADO</th><th style="text-align: center;">APROBADO</th><th style="text-align: center;">LIBERADO</th><th>REPORTE</th></tr>');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-all-questionnaires-finalizados/',
		dataType: 'json',
		success: function(response){
			

			var status = '';
			var report = null;

			for(var i=0; i<response.length; i++){
				report = response[i];

			/*	if(report.report_status == 0){
					status = ' class="warning" style="font-weight:bold;"';
				}else{
					status = ' class="active"';
				}
*/				//console.log(report);
				html += '<tr '+status+'>';
				html += '<td>'+report.id_cuestionario_respondido+'</td>';
				html += '<td>'+report.company_name+'</td>';
				html += '<td>'+report.nombre_auditor+'</td>';
				html += '<td>'+report.questionnaire_code+'</td>';
				html += '<td>'+report.fecha_auditoria+'</td>';

				html += '<td align="center">'+ report.reviewed+ '</td>';
				html += '<td align="center">'+ report.approved+ '</td>';
				html += '<td align="center">'+ report.liberado+ '</td>';


/*				if(report.released == 1){
					html += '<td><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></td>';
				}else{
					html += '<td><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></td>';
				}
				
*/				
				html += '<td>';
				html += '<button class="btn btn-default" onclick="viewReport('+report.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-eye-open"></span></button>&nbsp;';
				
				if(user_privileges == 1){
					html += '<button class="btn btn-danger" onclick="deleteReport('+report.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-trash"></span></button>';
				}else if(user_privileges == 2 && is_reviser == 1){
					html += '<button class="btn btn-danger" onclick="deleteReport('+report.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-trash"></span></button>';
				}

				html += '</td>';
				html += '</tr>';
			}
			
			$('#tbody_reports').html(html);

		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
	

}

//listado para usuarios gerente.
var getReportsGerente = function(){
	
	if (user_type=="Externo") {
		console.log("Gerente externo a secas");
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-eventos-gerente-externo-auditor-fin/'+user_id,
			dataType: 'json',
			success: function(response){
				
				//console.log(response);

				var tbody_table = $('#tbody_reports');
				var total = response.length;
				var rows = '';
				var questionnaire = null;
				rows = '<tr><th>No.</th><th>SITIO</th><th>AUDITOR</th><th>CUESTIONARIO</th><th>FECHA AUDITORIA</th><th style="text-align: center;">REVISADO</th><th style="text-align: center;">APROBADO</th><th style="text-align: center;">LIBERADO</th><th>REPORTE</th></tr>';
				
				for(var i=0; i<total; i++){
					questionnaire = response[i];
					//console.log(questionnaire);
					rows += '<tr>';
	  				rows += '<td>'+questionnaire.id_cuestionario_respondido+'</td>';
	  				rows += '<td>'+questionnaire.nombre_comercial+'</td>';
	  				rows += '<td>'+questionnaire.nombre_auditor+'</td>';
	  				rows += '<td>'+questionnaire.codigo+'</td>';
	  				rows += '<td>'+questionnaire.fecha_auditoria+'</td>';
	  				rows += '<td align="center">'+ questionnaire.reviewed+ '</td>';
					rows += '<td align="center">'+ questionnaire.approved+ '</td>';
					rows += '<td align="center">'+ questionnaire.liberado+ '</td>';
					rows += '<td><button class="btn btn-default" onclick="viewReport('+questionnaire.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-eye-open"></span></button>&nbsp;</td>';
	  				rows += '</tr>';
				}

				tbody_table.html(rows);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});
		

	}else{ //Es usuario gerente
		console.log("Gerente normal");
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-eventos-gerente-auditor-fin/'+user_id,
			dataType: 'json',
			success: function(response){
				
				//console.log(response);

				var tbody_table = $('#tbody_reports');
				var total = response.length;
				var rows = '';
				var questionnaire = null;
				rows = '<tr><th>No.</th><th>SITIO</th><th>AUDITOR</th><th>CUESTIONARIO</th><th>FECHA AUDITORIA</th><th style="text-align: center;">REVISADO</th><th style="text-align: center;">APROBADO</th><th style="text-align: center;">LIBERADO</th><th>REPORTE</th></tr>';
				
				for(var i=0; i<total; i++){
					questionnaire = response[i];
					//console.log(questionnaire);
					rows += '<tr>';
	  				rows += '<td>'+questionnaire.id_cuestionario_respondido+'</td>';
	  				rows += '<td>'+questionnaire.nombre_comercial+'</td>';
	  				rows += '<td>'+questionnaire.nombre_auditor+'</td>';
	  				rows += '<td>'+questionnaire.codigo+'</td>';
	  				rows += '<td>'+questionnaire.fecha_auditoria+'</td>';
	  				rows += '<td align="center">'+ questionnaire.reviewed+ '</td>';
					rows += '<td align="center">'+ questionnaire.approved+ '</td>';
					rows += '<td align="center">'+ questionnaire.liberado+ '</td>';
					rows += '<td><button class="btn btn-default" onclick="viewReport('+questionnaire.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-eye-open"></span></button>&nbsp;</td>';
	  				rows += '</tr>';
				}

				tbody_table.html(rows);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});
	}

}


//cargar lista de reportes para el usuario Auditor
var getReportsAuditor = function(){
	console.log("auditor");
	//console.log(user_id);
	var html = '';

	$('#thead_reports').html('<tr><th>No.</th><th>SITIO</th><th>CUESTIONARIO</th><th>FECHA AUDITORIA</th><th style="text-align: center;">REVISADO</th><th style="text-align: center;">APROBADO</th><th style="text-align: center;">LIBERADO</th><th>REPORTE</th></tr>');

	$.ajax({
		//url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/get/'+user_id+'/'+user_privileges,
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-list-questionnaires-finalizados/'+user_id,	
		dataType: 'json',
		success: function(response){

			var status = '';
			var report = null;

			for(var i=0; i<response.length; i++){
				report = response[i];

			/*	if(report.report_status == 0){
					status = ' class="warning" style="font-weight:bold;"';
				}else{
					status = ' class="active"';
				}
*/				//console.log(report);
				html += '<tr '+status+'>';
				html += '<td>'+report.id_cuestionario_respondido+'</td>';
				html += '<td>'+report.company_name+'</td>';
				
				if(user_privileges == 1){
					html += '<td>Nombre</td>';
				}

				html += '<td>'+report.questionnaire_code+'</td>';
				html += '<td>'+report.fecha_auditoria+'</td>';

				html += '<td align="center">'+ report.reviewed+ '</td>';
				html += '<td align="center">'+ report.approved+ '</td>';
				html += '<td align="center">'+ report.liberado+ '</td>';


/*				if(report.released == 1){
					html += '<td><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></td>';
				}else{
					html += '<td><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></td>';
				}
				
*/				
				html += '<td>';
				html += '<button class="btn btn-default" onclick="viewReport('+report.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-eye-open"></span></button>&nbsp;';
				
				if(user_privileges == 1){
					html += '<button class="btn btn-danger" onclick="deleteReport('+report.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-trash"></span></button>';
				}else if(user_privileges == 2 && is_reviser == 1){
					html += '<button class="btn btn-danger" onclick="deleteReport('+report.id_cuestionario_respondido+');"><span class="glyphicon glyphicon-trash"></span></button>';
				}

				html += '</td>';
				html += '</tr>';
			}
			
			$('#tbody_reports').html(html);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}



var viewReport = function(report_id){
	var answered_questionnaire_id = localStorage.setItem('questionnaire_respondido_id', report_id);

	localStorage.setItem('audit-suit-report-id', report_id);
	//console.log(localStorage);
	location.href = 'report.html';

	//window.location.replace('https://dev.bluehand.com.mx/console/report.html');
}


var deleteReport = function(report_id){
	var confirm_delete = confirm('¿Está seguro de eliminar este reporte?');

	if(confirm_delete == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/report/delete/'+report_id,
			dataType: 'json',
			success: function(response){
				alert(response.message)
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

		location.reload();
	}
}

