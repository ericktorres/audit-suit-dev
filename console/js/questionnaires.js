
var openCreateQuestionnaire = function(){
	$('#questionnnaire-create').modal('toggle');
}

var getQuestionnaires = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/get',
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_questionnaires');
			var total = response.length;
			var rows = '';
			var questionnaire = null;

			for(var i=0; i<total; i++){
				questionnaire = response[i];

				rows += '<tr>';
  				rows += '<td>'+questionnaire.id+'</td>';
  				rows += '<td>'+questionnaire.code+'</td>';
  				rows += '<td>'+questionnaire.name+'</td>';
  				rows += '<td>'+questionnaire.creation_date+'</td>';
  				rows += '<td class="td-row-actions">';  				
  				rows += '<button type="button" class="btn btn-default" onclick="goToAddSections('+questionnaire.id+', \''+questionnaire.name+'\', \''+questionnaire.code+'\')"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span></button>';
  				rows += '<button type="button" class="btn btn-default" onclick="getQuestionnaire('+questionnaire.id+');"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>';
  				rows += '<button type="button" class="btn btn-default" onclick="deleteQuestionnaire('+questionnaire.id+');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';
  				rows += '</td>';
  				rows += '</tr>';
			}

			tbody_table.html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var createQuestionnaire = function(){
	var code = $('#txt_code_create').val();
	var name = $('#txt_name_create').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/create',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({code: code, name: name}),
		success: function(response){
			$('#alert-questionnaire').css('display', 'inline');
			$('#alert-questionnaire-message').html(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var getQuestionnaire = function(questionnaire_id){
	$('#questionnnaire-edit').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/get/'+questionnaire_id,
		dataType: 'json',
		success: function(response){
			// Filling form with company data
			$('#txt_id_edit').val(response.id);
			$('#txt_code_edit').val(response.code);
			$('#txt_name_edit').val(response.name);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var editQuestionnaire = function(){
	var id = $('#txt_id_edit').val();
	var code = $('#txt_code_edit').val();
	var name = $('#txt_name_edit').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/modify',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({id:id, code: code, name: name}),
		success: function(response){
			$('#alert-questionnaire-edit').css('display', 'inline');
			$('#alert-questionnaire-message-edit').html(response.message);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});

	return false;
}

var goToAddSections = function(id, name, code){
	window.location = "questionnaires-sections.html";

	// Setting data in local storage
	if (typeof(Storage) !== "undefined") {
    	localStorage.setItem("questionnaire_id", id);
    	localStorage.setItem("questionnaire_name", name);
    	localStorage.setItem("questionnaire_code", code);
	} else {
    	alert('Lo sentimos, tu navegador no soporta almacenamiento local. Contacta al administrador.');
	}
}

var deleteQuestionnaire = function(questionnaire_id){
	var confirm_delete = confirm('¿Está seguro de eliminar este registro?');

	if(confirm_delete == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/delete/'+questionnaire_id,
			dataType: 'json',
			success: function(response){
				//$('#alert-companies').css('display', 'inline');
				//$('#alert-message').html(response.message);
				console.log(response.message);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

		location.reload();
	}
}


// Functions for sections questionaire

var getQuestionnaireData = function(){
	$('#questionnaire_title').html('<b>' + localStorage.getItem("questionnaire_name") + '</b> Código: <b>' + localStorage.getItem("questionnaire_code") + '</b>');

	// Set questionnaire id to the field txt
	$('#txt_idq_create').val(localStorage.getItem('questionnaire_id'));
}

var openCreateSection = function(){
	$('#section-create').modal('toggle');
}

var getSections = function(){
	var questionnaire_id = localStorage.getItem('questionnaire_id');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/sections/get/'+questionnaire_id,
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_section_questionnaires');
			var total = response.length;
			var rows = '';
			var section = null;

			for(var i=0; i<total; i++){
				section = response[i];

				rows += '<tr>';
  				rows += '<td>'+section.section_number+'</td>';
  				rows += '<td><a onclick="showHideRows('+section.id+');" style="cursor:point;">'+section.name+'</a></td>';
  				rows += '<td>'+section.description+'</td>';
  				rows += '<td>'+section.value+'</td>';
  				rows += '<td class="td-row-actions">';
  				rows += '<button type="button" class="btn btn-default" onclick="getSection('+section.id+');"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>';
  				rows += '<button type="button" class="btn btn-default" onclick="openAddQuestion('+section.id+', '+section.section_number+');"><span class="glyphicon glyphicon-question-sign"></span></button>';
  				rows += '</td>';
  				rows += '</tr>';

  				if(section.questions.length > 0){
  					for(var j=0; j<section.questions.length; j++){
  						var question = section.questions[j];
  						rows += '<tr class="row_question_sections hide-rows" id="'+section.id+'">';
  						rows += '<td>'+section.section_number+'.'+question.question_number+'</td>';
  						rows += '<td>'+question.question+'</td>';
  						rows += '<td>'+question.help_text+'</td>';
  						rows += '<td>'+question.value+'</td>';
  						rows += '<td class="td-row-actions">';
  						rows += '<button type="button" class="btn btn-default" onclick="getQuestion('+question.question_id+');"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>';
  						rows += '<button type="button" class="btn btn-danger" onclick="deleteQuestion('+question.question_id+');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';
  						rows += '</td>';
  						rows += '</tr>';	
  					}
  				}

  				/*rows += '<tr><td colspan="5"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">Panel title</h3></div><div class="panel-body">';
    			rows += 'Panel content</div></div></td></tr>';*/
			}

			tbody_table.html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var getQuestion = function(questionId){
	$('#edit-question').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/sections/question/get/'+questionId,
		dataType: 'json',
		success: function(response){
			// Filling form with company data
			$('#txt_section_id_edit').val(response.section_id);
			$('#txt_question_edit').val(response.question);
			$('#txt_num_question_edit').val(response.question_number);
			$('#txt_help_text_edit').val(response.help_text);
			$('#txt_value_q_edit').val(response.value);
			$('#slc_question_type_edit').val(response.type);
			$('#hdn_question_id').val(response.question_id);
			$('#txt_max_char_edit').val(response.max_char);

			var checked = (response.is_critic == '1') ? $('#chk_is_critic_edit').prop('checked', true) : $('#chk_is_critic_edit').prop('checked', false);

			if(response.type == '2'){
				var input_type = 'radio';
				$('#txt_max_char_edit').prop('disabled', true);
			}else if(response.type == '3'){
				var input_type = 'checkbox';
				$('#txt_max_char_edit').prop('disabled', true);
			}else{
				$('#txt_max_char_edit').prop('disabled', false);
				$('#question_options_edit').empty();
			}

			
			if(response.options.length > 0){
				var html = '';
				html += '<table class="form-tbl">';
				html += '<tbody id="tbody_options_edit">';

				for(var i=0; i<response.options.length; i++){					
					//html += '<tr><td colspan="4" align="center"><button class="btn btn-default" onclick="addOption(3);"><span class="glyphicon glyphicon-plus"></span> Añadir opción</button></td></tr>';
					html += '<tr>';
					html += '<td><input type="'+input_type+'"><input type="hidden" class="option_id_edit" value="'+response.options[i].id+'"></td>';
					html += '<td><input type="text" class="form-control option_text_edit" placeholder="Ingrese opción" value="'+response.options[i].question_option+'"></td>';
					html += '<td><input type="text" class="form-control option_value_edit" placeholder="Ingrese valor" value="'+response.options[i].value+'"></td>';
					html += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();" disabled><span class="glyphicon glyphicon-trash"></span></button></td>';
					html += '</tr>';
					
				}

				html += '</tbody>';
				html += '</table>';

				$('#question_options_edit').html(html);
			}	
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var editQuestion = function(){

	var critic = ($('#chk_is_critic_edit').is(':checked')) ? '1' : '0';
	var photo = ($('#chk_add_photo_edit').is(':checked')) ? '1' : '0';
	

	var question_id = $('#hdn_question_id').val();
	var num_question = $('#txt_num_question_edit').val();
	var type = $('#slc_question_type_edit').val();
	var question = $('#txt_question_edit').val();
	var help_text = $('#txt_help_text_edit').val();
	var value = $('#txt_value_q_edit').val();
	var is_critic = critic;
	var upload_photo = photo;
	var max_char = $('#txt_max_char_edit').val();
	var options = [];

	if(type > 1){
		console.log('Question with options');
		// Creating the options object to update these
		$('#tbody_options_edit > tr').each(function(){
			var id = $(this).children('td').find('.option_id_edit').val();
			var description = $(this).children('td').find('.option_text_edit').val();
			var value = $(this).children('td').find('.option_value_edit').val();
			
			options.push({option_id: id, option: description, value: value});
		});
	}
	
	console.log(options);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/sections/question/edit',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({question_id: question_id, num_question: num_question, type: type, question: question, help_text: help_text, value: value, is_critic: is_critic, upload_photo: upload_photo, max_char: max_char, options: options}),
		success: function(response){

			$('#alert-question-edit').css('display', 'inline');
			$('#alert-question-message-edit').html(response.message);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
			console.error(error);
		}
	});
}


var deleteQuestion = function(question_id){
	var confirm_delete = confirm('¿Seguro que desea eliminar esta pregunta?');

	if(confirm_delete == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/sections/question/delete/'+question_id,
			dataType: 'json',
			success: function(response){
				alert(response.message);
				console.log(response.message);
				location.reload();
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

		location.reload();
	}
}


var createSection = function(){
	var questionnaire_id = $('#txt_idq_create').val();
	var name = $('#txt_name_create').val();
	var description = $('#txt_description_create').val();
	var value = $('#txt_value_create').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/section/create',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({questionnaire_id: questionnaire_id, name: name, description: description, value: value}),
		success: function(response){
			$('#alert-section').css('display', 'inline');
			$('#alert-section-message').html(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});	
}

var getSection = function(sectionId){
	$('#section-edit').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/section/get/'+sectionId,
		dataType: 'json',
		success: function(response){
			$('#txt_id_edit').val(response.id);
			$('#txt_idq_edit').val(response.id_questionnaire);
			$('#txt_num_secc_edit').val(response.section_number);
			$('#txt_name_edit').val(response.name);
			$('#txt_description_edit').val(response.description);
			$('#txt_value_edit').val(response.value);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var editSection = function(){
	var name = $('#txt_name_edit').val();
	var description = $('#txt_description_edit').val();
	var value = $('#txt_value_edit').val();
	var section_id = $('#txt_id_edit').val();
	var num_section = $('#txt_num_secc_edit').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/section/modify',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({name: name, description: description, value: value, section_id: section_id, num_section: num_section}),
		success: function(response){
			$('#alert-section-edit').css('display', 'inline');
			$('#alert-section-message-edit').html(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var openAddQuestion = function(sectionId, sectionNumber){
	$('#add-question').modal('toggle');
	$('#txt_section_id').val(sectionId);
	$('#txt_section_number').val(sectionNumber);
}

var loadQuestionType = function(){
	var curr_value = $('#slc_question_type').val();
	var html = '';

	if(curr_value == 1){
		// For open questions
		html += '<table class="form-tbl">';
		html += '<tbody>';
		html += '<tr>';
		html += '<td><input type="text" id="txt_max_char" class="form-control" placeholder="MAX. CARACTERES"></td>';
		html += '</tr>';
		html += '</tbody>';
		html += '</table>';
	}else if(curr_value == 2){
		// For multiple options (radio buttons)
		html += '<table class="form-tbl">';
		html += '<tbody id="tbody_options_radio">';
		html += '<tr><td colspan="4" align="center"><button class="btn btn-default" onclick="addOption(2);"><span class="glyphicon glyphicon-plus"></span> Añadir opción</button></td></tr>';
		html += '<tr>';
		html += '<td><input type="radio"></td>';
		html += '<td><input type="text" class="form-control option_text" value="Cumple"></td>';
		html += '<td><input type="text" class="form-control option_value" value="100"></td>';
		html += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();"><span class="glyphicon glyphicon-trash"></span></button></td>';
		html += '</tr>';
		html += '<tr>';
		html += '<td><input type="radio"></td>';
		html += '<td><input type="text" class="form-control option_text" value="Cumple parcialmente"></td>';
		html += '<td><input type="text" class="form-control option_value" value="50"></td>';
		html += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();"><span class="glyphicon glyphicon-trash"></span></button></td>';
		html += '</tr>';
		html += '<tr>';
		html += '<td><input type="radio"></td>';
		html += '<td><input type="text" class="form-control option_text" value="No cumple"></td>';
		html += '<td><input type="text" class="form-control option_value" value="0"></td>';
		html += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();"><span class="glyphicon glyphicon-trash"></span></button></td>';
		html += '</tr>';
		html += '<tr>';
		html += '<td><input type="radio"></td>';
		html += '<td><input type="text" class="form-control option_text" value="No aplica"></td>';
		html += '<td><input type="text" class="form-control option_value" value=""></td>';
		html += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();"><span class="glyphicon glyphicon-trash"></span></button></td>';
		html += '</tr>';
		html += '</tbody>';
		html += '</table>';
	}else if(curr_value == 3){
		// For verification field (checkbox)
		html += '<table class="form-tbl">';
		html += '<tbody id="tbody_options_check">';
		html += '<tr><td colspan="4" align="center"><button class="btn btn-default" onclick="addOption(3);"><span class="glyphicon glyphicon-plus"></span> Añadir opción</button></td></tr>';
		html += '<tr>';
		html += '<td><input type="checkbox"></td>';
		html += '<td><input type="text" class="form-control option_text" placeholder="Ingrese opción"></td>';
		html += '<td><input type="text" class="form-control option_value" placeholder="Ingrese valor"></td>';
		html += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();"><span class="glyphicon glyphicon-trash"></span></button></td>';
		html += '</tr>';
		html += '</tbody>';
		html += '</table>';
	}

	$('#question_options').html(html);
}

var addOption = function(type){
	var newOption = '';

	if(type == 2){
		newOption += '<tr>';
		newOption += '<td><input type="radio"></td>';
		newOption += '<td><input type="text" class="form-control option_text"></td>';
		newOption += '<td><input type="text" class="form-control option_value"></td>';
		newOption += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();"><span class="glyphicon glyphicon-trash"></span></button></td>';
		newOption += '</tr>';

		$('#tbody_options_radio').append(newOption);
	}else if(type == 3){
		newOption += '<tr>';
		newOption += '<td><input type="checkbox"></td>';
		newOption += '<td><input type="text" class="form-control option_text" placeholder="Ingrese opción"></td>';
		newOption += '<td><input type="text" class="form-control option_value" placeholder="Ingrese valor"></td>';
		newOption += '<td><button class="btn btn-danger" onclick="$(this).parent().parent().remove();"><span class="glyphicon glyphicon-trash"></span></button></td>';
		newOption += '</tr>';

		$('#tbody_options_check').append(newOption);
	}

}

var createQuestion = function(){
	var critic = ($('#chk_is_critic').is(':checked')) ? '1' : '0';
	var photo = ($('#chk_add_photo').is(':checked')) ? '1' : '0';

	var section_id = $('#txt_section_id').val();
	var section_number = $('#txt_section_number').val();
	var question = $('#txt_question').val(); 
	var help_text = $('#txt_help_text').val(); 
	var value = $('#txt_value').val(); 
	var is_critic = critic;
	var upload_photo = photo;
	var max_char = $('#txt_max_char').val();
	var type = $('#slc_question_type').val();
	var question_options = [];
	var active_table = '';
	
	// Getting the question options
	if(type == 2){
		$('#tbody_options_radio > tr').each(function(index){
			if(index > 0){
				var description = $(this).children('td').find('.option_text').val();
				var value = $(this).children('td').find('.option_value').val();
				question_options.push({option: description, value: value});
			}
		});
	}else if(type == 3){
		$('#tbody_options_check > tr').each(function(index){
			if(index > 0){
				var description = $(this).children('td').find('.option_text').val();
				var value = $(this).children('td').find('.option_value').val();
				question_options.push({option: description, value: value});
			}
		});
	}

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/section/questions/add',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({section_id: section_id, section_number: section_number, question: question, help_text: help_text, value: value, is_critic: is_critic, upload_photo: upload_photo, max_char: max_char, type: type, question_options: question_options}),
		success: function(response){
			$('#alert-question').css('display', 'inline');
			$('#alert-question-message').html(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var showHideRows = function(rows_id){
	var rows = $('[id='+rows_id+']');

	$.each(rows, function(index){
		if(rows[index].style.display == 'none'){
			rows[index].style.display = 'table-row';
		}
		else{
			rows[index].style.display = 'none';
		}
	});
}
