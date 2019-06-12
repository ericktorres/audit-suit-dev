

var finalizar = 1;
var botones_guardar = 1;

//Get status informe preliminar
var getInforme = function(){

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/get-informe/'+localStorage.getItem('questionnaire_respondido_id'),
		dataType: 'json',
		success: function(response){
			

			if (response.informe_preliminar == 1) {
				$("#informe-cuest").hide();
				$("#formAuditoria").hide();
				document.getElementById("finalizar-cuest").disabled = false;
				botones_guardar =0;
								//Ya se finalizó el cuestionario
				if (response.finalizado == 1) {
					document.getElementById("finalizar-cuest").disabled = true;
				}else{
					document.getElementById("finalizar-cuest").disabled = false;
					
				}

			}else{
				$("#viewReportPr").hide();
			}

			

		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});

}

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
	var questionnaire_respondido_id = localStorage.getItem('questionnaire_respondido_id');
	var idSeccionPreg = '';


	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/sections/get/'+questionnaire_id+'/'+questionnaire_respondido_id,
		dataType: 'json',
		success: function(response){
			for(var i=0; i<response.length; i++){

				section = response[i];
				idSeccionPreg = "preg_section_"+section.id+'_'+section.questionnaire_id+'_'+section.section_number;

				var res_avance = Math.round(((100 / section.sum_preguntas) * section.sum_respuestas));
				var progresp="";

				if (res_avance >= 0 && res_avance <= 39) {
					progresp = "progress-bar-danger";
					finalizar = 0;
				}
				if (res_avance >= 40 && res_avance <= 99 ) {
					progresp = "progress-bar-warning";
					finalizar = 0;
				}
				if (res_avance==100) {
					progresp = "progress-bar-success";
				}
				//console.log(res_avance + ' Color: ' + progresp);

				html += '<button class="accordion">';
				html += '<div class="progress"><div class="progress-bar '+progresp+'" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: '+res_avance+'%;"></div></div>';
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
							option = question.options[k];

							var string_selected_value = questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value;

							if (question.question_result==false) {
								var obser_value = '';
								var obser_conf = '';
								var radio_status = '';
								var noaply_status = '';
							}else{

								if (question.question_result.not_apply == 1) {
									 noaply_status = ' checked';
								}else{
									 noaply_status = '';
								}

								if(question.question_result.string_selected_value != false){
									if(question.question_result.string_selected_value == string_selected_value){
										 radio_status = ' checked';
									}else{
										 radio_status = '';
									}	
								}

								if(question.question_result.observations !=false) {
									 obser_value = question.question_result.observations;
								}else{
									 obser_value = '';
								}
								
								if(question.question_result.nonconformity !=false) {
									 obser_conf = question.question_result.nonconformity;
								}else{
									 obser_conf = '';
								}
							}

							
							html += '<tr>';
							html += '<td width="25">';
							html += '<input type="radio" name="rdo_question_'+question.question_id+'" value="'+questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value+'"'+radio_status+'>';
							//html += '<input type="radio" name="rdo_question_'+question.question_id+'" value="'+questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value+'">';
							html += '</td>';
							html += '<td>'+option.question_option+'</td>';
							html += '</tr>';							
						}

						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_obs_'+question.question_id+'" placeholder="Ingrese aquí sus observaciones" cols="80">'+obser_value+'</textarea></td></tr>';
						html += '<tr><td colspan="2"><textarea class="form-control" id="txa_question_nonconformity_'+question.question_id+'" placeholder="Ingrese aquí sus no conformidades" cols="80">'+obser_conf+'</textarea></td></tr>';
						html += '<tr><td colspan="2">No Aplica <input type="checkbox" class="not_applyy" id="chk_not_apply_'+question.question_id+'"'+noaply_status+'></td></tr>';
						html += '</table>';
					}else if(question.type == '3'){
						html += '<table class="tbl-answer-questionnaire">';

						for(var k=0; k<question.options.length; k++){

							option = question.options[k];

							html += '<tr>';
							html += '<td width="25"><input type="checkbox" name="rdo_question_'+question.question_id+'" value="'+questionnaire_id+'_'+section.id+'_'+question.question_id+'_'+option.option_id+'_'+option.value+'"'+radio_status+'></td>';
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
				if (botones_guardar == 1) {
					html +='<button type="button" class="btn btn-info pull-right saveQuestionnaire" name="'+idSeccionPreg+'" style="margin:0px 0px 12px 0"><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Guardar Avance</button>';				
				}

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

	if (params === undefined) {
	    alert("Responde al menos una pregunta para continuar.");
	}
	else {
		$.ajax({
		 	url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/save',
		 	method: 'POST',
		 	dataType: 'json',
		 	data: JSON.stringify(params),
		 	success: function(response){
		 		alert(response.message);
		 		//console.log(response);
		 		//console.log(divRadios);
		 		location.reload();
		 	},
		 	error: function(error){
		 		console.log(error);
		 	}
		 });

	}
});
	

	
	setTimeout(function(){

		if(finalizar == 1) {
			document.getElementById("informe-cuest").disabled = false;
			document.getElementById("inputAtendio").disabled = false;
			document.getElementById("datepk6").disabled = false;
			document.getElementById("datepk7").disabled = false;
			$("#pizarra").show();
			$("#pizarra2").show();
			$("#piza").show();
            $("#piza2").show();
			
		}else{
			document.getElementById("informe-cuest").disabled = true;
			document.getElementById("inputAtendio").disabled = true;
			document.getElementById("datepk6").disabled = true;
			document.getElementById("datepk7").disabled = true;
		}
	}, 2000);

	
	

	var viewInform = function(){
		location.href = 'informe.html';
	}

	var informeQuestionnaire = function(){
		var atiende = document.getElementById("inputAtendio").value;
		var dateInicia = document.getElementById("datepk6").value;
		var dateTermina = document.getElementById("datepk7").value;		

		var can = document.getElementById('pizarra');
		var can2 = document.getElementById('pizarra2');
		var img = new Image();
		var img2 = new Image();
		img.src = can.toDataURL();
		img2.src = can2.toDataURL();

		if (firmaOk === true && dateInicia != "" && dateTermina != "" && atiende != "") {
			//console.log("Is OK");
			var params = {id_cuestionario_respondido: localStorage.getItem('questionnaire_respondido_id'), atiende: atiende,firma: img.src, firma2: img2.src,dateInicia: dateInicia, dateTermina: dateTermina};
			//console.log(JSON.stringify(params));
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/create-informe',
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify(params),
				success: function(response){

					if (response.result_code == 1) {
						alert(response.message);
						location.reload();
					}else{
						alert(response.message);
					}
				},
				error: function(error){
					console.log(error);
				}
			});


		}else{
			alert("Responder datos de Auditoria para continuar.");
		}

	}

	var completeQuestionnaire = function(){

		

		var params = {id_cuestionario_respondido: localStorage.getItem('questionnaire_respondido_id')};
	
		//console.log(JSON.stringify(params));

		$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/finaliza-cuestionary',
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify(params),
				success: function(response){
					//console.log(response);
					if (response.result_code == 1) {
						alert(response.message);
						location.reload();
					}else{
						alert(response.message);
					}

					//console.log(localStorage);				
					window.location.href = 'questionnaires-answer.html';
				},
				error: function(error){
					console.log(error);
				}
			});
	

	}





