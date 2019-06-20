
var getQuestionnaires = function(){
	var auditor_id = sessionStorage.getItem('audit-suite-user-id');
	//console.log('Auditor ID: ' + auditor_id);
	console.log(auditor_id);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-list-questionnaires-and-clients/'+auditor_id,
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_questionnaires_assigned');
			var total = response.length;
			var rows = '';
			var questionnaire = null;
			var j = 1;
			//var hh =[];

			for(var i=0; i<total; i++){ //inicia for
				
				questionnaire = response[i];
				//console.log(questionnaire);
				rows += '<tr>';
  				rows += '<td>'+questionnaire.id_cuestionario_respondido+'</td>';
  				rows += '<td>'+questionnaire.company_name+'</td>';			
  				rows += '<td>'+questionnaire.questionnaire_name+'</td>';
  				rows += '<td>'+questionnaire.fecha_auditoria+'</td>';  	
  				rows += '<td>'+ questionnaire.porcentaje_question +' '+ questionnaire.estados.finalizado +' '+ questionnaire.estados.informe_preliminar+ '</td>';
  				rows += '<td align="center">';
  				rows += '<button type="button" class="btn btn-default" onclick="openQuestionnaire(\''+questionnaire.questionnaire_id+'\', \''+questionnaire.questionnaire_name+'\', \''+questionnaire.questionnaire_code+'\', \''+questionnaire.company_name+'\', \''+questionnaire.company_id+'\', \''+questionnaire.id_cuestionario_respondido+'\');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button>';
  				rows += '</td>';
  				rows += '</tr>';

				j++;

			} //fin for
			tbody_table.html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var openQuestionnaire = function(questionnaire_id, questionnaire_name, questionnaire_code, company_name, company_id,new_cuestionary){
	localStorage.setItem('questionnaire_id', questionnaire_id);
	localStorage.setItem('questionnaire_name', questionnaire_name);
	localStorage.setItem('questionnaire_code', questionnaire_code);
	localStorage.setItem('company_name', company_name);
	localStorage.setItem('company_id', company_id);
	localStorage.setItem('questionnaire_respondido_id', new_cuestionary);

	var auditor_id = sessionStorage.getItem('audit-suite-user-id');
	var coordinates = $('#hdn_coords').val();

	window.location.href = 'questionnaires-answer.html';

	//answers.push(answer);
	
	//var params = {save_type: "iniciado", questionnaire_id: questionnaire_id, company_id: company_id, auditor_id: auditor_id,coordinates:coordinates};
//	console.log(JSON.stringify(params));	

	/*$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/create-cuestionary',
			method: 'POST',
			dataType: 'json',
			data: JSON.stringify(params),
			success: function(response){
				//console.log(response);
				localStorage.setItem('questionnaire_respondido_id', response.new_cuestionary);
				//console.log(localStorage);				
				window.location.href = 'questionnaires-answer.html';
			},
			error: function(error){
				console.log(error);
			}
		});*/
		//console.log(localStorage);
		//setTimeout(function(){ 
		//	window.location.href = 'questionnaires-answer.html';
		//}, 2000);
}



