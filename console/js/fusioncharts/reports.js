
var user_id = sessionStorage.getItem('audit-suite-user-id');
var user_privileges = localStorage.getItem('audit-suite-privilege-user');

var getReports = function(){
	$.ajax({
		url: 'https://bluehand.com.mx/backend/api/v1/reports/get/'+user_id+'/'+user_privileges,
		dataType: 'json',
		success: function(response){
			var html = '';
			var report = null;

			for(var i=0; i<response.length; i++){
				report = response[i];

				html += '<tr>';
				html += '<td>'+report.report_id+'</td>';
				html += '<td>'+report.client_name+'</td>';
				html += '<td>'+report.auditor_name+'</td>';
				html += '<td>'+report.questionnaire_name+'</td>';
				html += '<td>'+report.application_date+'</td>';
				html += '<td><button class="btn btn-default" onclick="viewReport('+report.report_id+');"><span class="glyphicon glyphicon-eye-open"></span></button></td>';
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
	localStorage.setItem('audit-suit-report-id', report_id);
	window.location.replace('https://bluehand.com.mx/console/report.html');
}

