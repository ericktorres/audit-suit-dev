
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

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/sections/get/'+questionnaire_id,
		dataType: 'json',
		success: function(response){
			for(var i=0; i<response.length; i++){
				section = response[i];

				html += '<li class="list-group-item">';
				html += '<span style="font-weight:bolder;">' + section.section_number + '.- ' + section.name.toUpperCase(); + '</span>';
				html += '</li>';
				for(var j=0; j<section.questions.length; j++){
					question = section.questions[j];

					html += '<li class="list-group-item" style="color:#1C1C1C;">';
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
							option = question.options[k];
							
							html += '<tr>';
							html += '<td width="25">';
							html += '<input type="radio" name="rdo_question_'+question.question_id+'" value="'+questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value+'">';
							html += '</td>';
							html += '<td>'+option.question_option+'</td>';
							html += '</tr>';							
						}

						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_obs_'+question.question_id+'" placeholder="Ingrese aquí sus observaciones" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_nonconformity_'+question.question_id+'" placeholder="Ingrese aquí sus no conformidades" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2">No Aplica <input type="checkbox" id="chk_not_apply_'+question.question_id+'"></td></tr>';
						html += '</table>';
					}else if(question.type == '3'){
						html += '<table class="tbl-answer-questionnaire">';

						for(var k=0; k<question.options.length; k++){
							option = question.options[k];
							
							html += '<tr>';
							html += '<td width="25"><input type="checkbox" name="rdo_question_'+question.question_id+'" value="'+questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value+'"></td>';
							html += '<td>'+option.question_option+'</td>';
							html += '</tr>';							
						}

						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_obs_'+question.question_id+'" placeholder="Ingrese aquí sus observaciones" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_nonconformity_'+question.question_id+'" placeholder="Ingrese aquí sus no conformidades" cols="80"></textarea></td></tr>';
						html += '<tr><td colspan="2">No Aplica <input type="checkbox" id="chk_not_apply_'+question.question_id+'"></td></tr>';
						html += '</table>';
					}

					html += '</li>';
				}
			}

			// Adding sections
			$('#answer_questionnaire_questions').html(html);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var saveQuestionnaire = function(save_type){
	var questionnaire_id = localStorage.getItem('questionnaire_id');
	var client_id = localStorage.getItem('company_id');
	var auditor_id = sessionStorage.getItem('audit-suite-user-id');
	var coordinates = $('#hdn_coords').val();
	var answers = [];
	var answer = null;

	// Getting all the answers results
	//$('input[type=radio]:checked,input[type=checkbox]:checked').each(function(){
	$('input[type=radio]:checked').each(function(){
		var arr_answer = $(this).val().split('_');
		var section_id = arr_answer[1];
		var question_id = arr_answer[2];
		var option_id = arr_answer[3];
		var value = arr_answer[4];
		var score = (value * $('#hdn_question_value_'+question_id).val()) / 100;
		var observations = $('#txa_question_obs_'+question_id).val();
		var nonconformity = $('#txa_question_nonconformity_'+question_id).val();
		var not_apply = ($('#chk_not_apply_'+question_id).is(':checked')) ? 1 : 0;
		
		console.log('ID: ' + question_id + ' - ' + score + ' NA: ' + not_apply);

		answer = {questionnaire_id: questionnaire_id, section_id: section_id, question_id: question_id, option_id: option_id, value: value, score: score, observations: observations, nonconformity: nonconformity, not_apply: not_apply};
		answers.push(answer);
	});

	var params = {questionnaire_id: questionnaire_id, client_id: client_id, auditor_id: auditor_id, coordinates: coordinates, save_type: save_type, answers: answers};
	console.log(JSON.stringify(params));

	// Saving answers
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/save',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify(params),
		success: function(response){
			alert(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}
