
var getQuestions = function(){
	// Setting the questionnaire data
	var questionnaireHead = '<b>CUESTIONARIO:</b> ' + localStorage.getItem("questionnaire_name");
	questionnaireHead += ' <br><b>CÓDIGO:</b> ' + localStorage.getItem("questionnaire_code");
	questionnaireHead += '<br><b>CLIENTE:</b> ' + localStorage.getItem("company_name");
	$('#answer_questionnaire_name').html(questionnaireHead);

	var html = '';
	var section = null;
	var question = null;
	var option = null;
	var questionnaire_id = localStorage.getItem('questionnaire_id');
	var idSeccionPreg = '';

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/sections/get/'+questionnaire_id+'/1',
		dataType: 'json',
		success: function(response){
			for(var i=0; i<response.length; i++){
				section = response[i];
				idSeccionPreg = "preg_section_"+section.id+'_'+section.questionnaire_id+'_'+section.section_number;

				html += '<button class="accordion">';
				html += '<div class="progress"><div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 10%;"></div></div>';
				html += '<span style="font-weight:bolder;">' + section.section_number + '.- ' + section.name.toUpperCase(); + '</span>';
				html += '</button>';
				html += '<div class="paneles '+idSeccionPreg+'" style="color:#1C1C1C;">';
				

				for(var j=0; j<section.questions.length; j++){
					question = section.questions[j];
					html += '<div class="pregts" style="color:#1C1C1C; margin: 12px 0px 10px 0px;">';
					html += section.section_number + '.' + question.question_number + '.- ' + question.question;
					html += '<br><span style="font-style:italic;color:#6E6E6E;">' + question.help_text + '</span>';
					html += '<input type="hidden" id="hdn_question_value_'+question.question_id+'" value="'+question.value+'">';

					if(question.type == '1'){
						html += '<table>';
						html += '<tr>';
						html += '<td>';
						html += '<textarea class="form-control" cols="60"></textarea>';
						html += '</td>';
						html += '</tr>';
						html += '</table>';
					}else if(question.type == '2'){
						html += '<table class="tbl-answer-questionnaire">';

						for(var k=0; k<question.options.length; k++){
							var string_selected_value = questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value;
							console.log(string_selected_value);

							if(question.question_result.string_selected_value != false){
								console.log(question.question_result.string_selected_value);

								if(question.question_result.string_selected_value == string_selected_value){
									var radio_status = ' checked';
								}else{
									var radio_status = '';
								}	
							}
							
							option = question.options[k];
							
							html += '<tr>';
							html += '<td width="25">';
							html += '<input type="radio" name="rdo_question_'+question.question_id+'" value="'+questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value+'"'+radio_status+'>';
							html += '</td>';
							html += '<td>'+option.question_option+'</td>';
							html += '</tr>';							
						}

						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_obs_'+question.question_id+'" placeholder="Ingrese aquí sus observaciones" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_nonconformity_'+question.question_id+'" placeholder="Ingrese aquí sus no conformidades" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2">No Aplica <input type="checkbox" class="not_applyy" id="chk_not_apply_'+question.question_id+'"></td></tr>';
						html += '</table>';
					}else if(question.type == '3'){
						html += '<table class="tbl-answer-questionnaire">';

						for(var k=0; k<question.options.length; k++){
							var string_selected_value = questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value;

							if(question.question_result.string_selected_value != false){
								if(question.question_result.string_selected_value == string_selected_value){
									var radio_status = ' checked';
								}else{
									var radio_status = '';
								}	
							}

							option = question.options[k];
							
							html += '<tr>';
							html += '<td width="25"><input type="checkbox" name="rdo_question_'+question.question_id+'" value="'+questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value+'"></td>';
							html += '<td>'+option.question_option+'</td>';
							html += '</tr>';							
						}

						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_obs_'+question.question_id+'" placeholder="Ingrese aquí sus observaciones" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_nonconformity_'+question.question_id+'" placeholder="Ingrese aquí sus no conformidades" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2">No Aplica <input type="checkbox" class="not_applyy" id="chk_not_apply_'+question.question_id+'"></td></tr>';
						html += '</table>';
					}
					html += '<hr></div>';
				}
				html +='<button type="button" class="btn btn-info pull-right saveQuestionnaire" name="'+idSeccionPreg+'" style="margin:0px 0px 12px 0"><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Guardar Avance</button>';
				html += '</div>';
			}
	
			// Adding sections
			$('#answer_questionnaire_questions').html(html);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


	


$('#answer_questionnaire_questions').on("click", ".saveQuestionnaire",function(){
	divRadios = $(this).attr('name');
	
	var questionnaire_respondido_id = localStorage.getItem('questionnaire_respondido_id');
	var questionnaire_id = localStorage.getItem('questionnaire_id');
	var client_id = localStorage.getItem('company_id');
	var auditor_id = sessionStorage.getItem('audit-suite-user-id');
	var coordinates = $('#hdn_coords').val();
	var answers = [];
	var answer = null;

//	console.log(questionnaire_respondido_id);
//	console.log(localStorage);

	var params;
	
	$('.'+divRadios+' input[type=radio]:checked').each(function(){
		
		
		
		var arr_answer = $(this).val().split('_');
		var section_id = arr_answer[1];
		var question_id = arr_answer[2];
		var option_id = arr_answer[3];
		var value = arr_answer[4];
		var score = (value * $('#hdn_question_value_'+question_id).val()) / 100;
		var observations = $('#txa_question_obs_'+question_id).val();
		var nonconformity = $('#txa_question_nonconformity_'+question_id).val();
		var not_apply = ($('#chk_not_apply_'+question_id).is(':checked')) ? 1 : 0;
		
		//console.log('ID: ' + question_id + ' - ' + score + ' NA: ' + not_apply);

		answer = {questionnaire_respondido_id: questionnaire_respondido_id, questionnaire_id: questionnaire_id, section_id: section_id, question_id: question_id, option_id: option_id, value: value, score: score, observations: observations, nonconformity: nonconformity, not_apply: not_apply};
		answers.push(answer);

		params = {questionnaire_id: questionnaire_id, client_id: client_id, auditor_id: auditor_id, coordinates: coordinates, save_type: "complete", answers: answers};
		

	});

	//console.log(localStorage);
	
		$.ajax({
		 	url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/save',
		 	method: 'POST',
		 	dataType: 'json',
		 	data: JSON.stringify(params),
		 	success: function(response){
		 		alert(response.message);
		 		console.log(response);
		 	},
		 	error: function(error){
		 		console.log(error);
		 	}
		 });
	
});


