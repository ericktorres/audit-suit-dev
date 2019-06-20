
var user_id = sessionStorage.getItem('audit-suite-user-id');
var user_privileges = localStorage.getItem('audit-suite-privilege-user');
var is_reviser = localStorage.getItem('audit-suite-user-reviser');

var getReports = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/get/'+user_id+'/'+user_privileges,
		dataType: 'json',
		success: function(response){
			var html = '';
			var status = '';
			var report = null;

			for(var i=0; i<response.length; i++){
				report = response[i];

				if(report.report_status == 0){
					status = ' class="warning" style="font-weight:bold;"';
				}else{
					status = ' class="active"';
				}

				html += '<tr '+status+'>';
				html += '<td>'+report.report_id+'</td>';
				html += '<td>'+report.client_name+'</td>';
				html += '<td>'+report.auditor_name+'</td>';
				html += '<td>'+report.questionnaire_code+'</td>';
				html += '<td>'+report.audit_date+'</td>';
				html += '<td>'+report.application_date+'</td>';

				if(report.released == 1){
					html += '<td><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></td>';
				}else{
					html += '<td><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></td>';
				}
				
				html += '<td>';
				html += '<button class="btn btn-default" onclick="viewReport('+report.report_id+');"><span class="glyphicon glyphicon-eye-open"></span></button>&nbsp;';
				
				if(user_privileges == 1){
					html += '<button class="btn btn-danger" onclick="deleteReport('+report.report_id+');"><span class="glyphicon glyphicon-trash"></span></button>';
				}else if(user_privileges == 2 && is_reviser == 1){
					html += '<button class="btn btn-danger" onclick="deleteReport('+report.report_id+');"><span class="glyphicon glyphicon-trash"></span></button>';
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

