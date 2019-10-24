var user_id = sessionStorage.getItem('audit-suite-user-id');
var user_privileges = localStorage.getItem('audit-suite-privilege-user');
var user_type = localStorage.getItem('audit-suite-user-type');

var openCreateReply = function(){
	var selected_report_id = localStorage.getItem('reply_report_id');
	$('#add-new-reply').modal('toggle');
	$('#slc_report').html('<option value="'+selected_report_id+'">No '+selected_report_id+'</option>');
}

var getPlants = function(){

	var plant_id = localStorage.getItem('reply_client_id');
	var plant_name = localStorage.getItem('reply_client_name');
	var options = '<option value="'+plant_id+'">'+plant_name+'</option>';

	$('#slc_plant').html(options);
}

var getReports = function(){
	var plant_id = $('#slc_plant').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/get-reports-by-plant/'+plant_id,
		dataType: 'json',
		success: function(response){
			var options = '<option value="">Seleccione reporte</option>';
			var report = null;

			for(var i=0; i<response.length; i++){
				report = response[i];

				options += '<option value="'+report.report_id+'">No '+report.report_id+' - '+report.questionnaire_code+'</option>';
			}

			$('#slc_report').html(options);
		},
		error: function(error){
			console.log('Ha ocurrido un error al cargar los reportes. Error: ' + error);
		}
	});
}

var getQuestions = function(){
	var report_id = localStorage.getItem('reply_report_id');
	//var report_id = $('#slc_report').val();

	$('#slc_question').html('');
	
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/get-wrong-questions-by-report/'+report_id,
		dataType: 'json',
		success: function(response){
			var options = '<option value="">Seleccione pregunta</option>';
			var question = null;

			for(var i=0; i<response.length; i++){
				question = response[i];

				options += '<option value="'+question.question_id+'">No '+question.question_id+' - '+question.question+'</option>';
			}

			$('#slc_question').html(options);
		},
		error: function(error){
			console.log('Ha ocurrido un error al cargar las preguntas. Error: ' + error);
		}
	})
}

var getWrongQuestions = function(){
	var client_id = localStorage.getItem('audit-suite-client-id');
	
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/get-wrong-questions/'+user_id+'/'+user_privileges,
		dataType: 'json',
		success: function(response){
			var options = '';
			var questionnaire = null;
			var question = null;

			for(var i=0; i<response.length; i++){
				questionnaire = response[i];

				options += '<optgroup label="'+questionnaire.client_name+' - '+questionnaire.questionnaire_code+'">';
				
				for(var j=0; j<questionnaire.questions.length; j++){
					question = questionnaire.questions[j];

					options += '<option value="'+questionnaire.questionnaire_answered_id+'_'+question.question_id+'">'+question.question+'</option>';
				}

				options += '</optgroup>';
			}

			$('#slc_question').html(options);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var createReply = function(){
	//var arr_question_values = $('#slc_question').val().split('_');
	//var questionnaire_answered_id = arr_question_values[0];
	//var question_id = arr_question_values[1];
	var questionnaire_answered_id = $('#slc_report').val();
	var question_id = $('#slc_question').val();
	var user_id = sessionStorage.getItem('audit-suite-user-id');
	var root_cause = $('#txa_root_cause').val();
	var corrective_action = $('#txa_corrective_action').val();
	var responsibles = $('#txa_responsibles').val();
	var commitment_date = $('#dte_commitment_date').val();
	var evidence_file = $('#fle_evidence').val();
	var file = $('#fle_evidence').prop('files')[0];
	var plant_name = $('#slc_plant option:selected').text();

	if(file != undefined){
		var file_name = file.name;	
	}
	//console.log('Nombre Archivo: ' + file.name);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/create-reply',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({questionnaire_answered_id: questionnaire_answered_id, question_id: question_id, user_id: user_id, root_cause: root_cause, corrective_action: corrective_action, responsibles: responsibles, commitment_date: commitment_date, evidence_file: file_name}),
		success: function(response){
			$('#alert-clients-auditors').css('display', 'inline');
			$('#alert-clients-auditors').append(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});

	//case_option, plant_name, report_id, reply_number
	//reply_notification('new_reply', plant_name, questionnaire_answered_id, '');
}

var uploadEvidence = function(){
	var file = $('#fle_evidence').prop('files')[0];
	var form = new FormData();
	form.append('file', file);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/upload-evidence',
		method: 'POST',
		dataType: 'json',
		cache: false,
		contentType: false,
		processData: false,
		data: form,
		success: function(response){
			console.log(response);
			console.log(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var uploadEvidenceComment = function(){
	var file = $('#fle_evidence_comment').prop('files')[0];
	var form = new FormData();
	form.append('file', file);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/upload-evidence',
		method: 'POST',
		dataType: 'json',
		cache: false,
		contentType: false,
		processData: false,
		data: form,
		success: function(response){
			console.log(response);
			console.log(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var goToReplies = function(reportId, clientId, clientName){
	location.href = "replies.html";

	localStorage.setItem('reply_report_id', reportId);
	localStorage.setItem('reply_client_id', clientId);
	localStorage.setItem('reply_client_name', clientName);

}

var getReply = function(){
	var client_id = localStorage.getItem('audit-suite-client-id');
	var report_id = localStorage.getItem('reply_report_id');
	var apicall = 'https://dev.bluehand.com.mx/backend/api/v1/reply/get-reply/'+client_id+'/'+user_id+'/'+user_privileges+'/'+report_id;
	var status = null;
	console.log(apicall);

	//console.log('user type: ' + user_type);
	//console.log('user privileges: ' + user_privileges);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/get-reply/'+client_id+'/'+user_id+'/'+user_privileges+'/'+report_id,
		dataType: 'json',
		success: function(response){
			var html = '';
			var reply = null;
			var counter = 1;
			var comment_counter = 1;
			var success_legend = null;

			for(var i=0; i<response.length; i++){
				reply = response[i];

				if(reply.satisfactory == 1){
					header_color = ' style="background-color:#A9E2F3;"';
					success_legend = 'Si';
				}else if(reply.satisfactory == 0){
					header_color = ' style="background-color:#F78181;"';
					success_legend = 'No';
				}else{
					header_color = '';
				}

				// New structure
				html += '<div class="panel panel-default" onclick="set_reply_status('+user_id+','+reply.id+','+report_id+');">';
  				html += '<div class="panel-heading"'+header_color+'><a data-toggle="collapse" href="#'+reply.id+'" style="color:#000;"><b>'+reply.question_id+'.- '+reply.question+'</b></a></div>';
  				html += '<div id="'+reply.id+'" class="panel-collapse collapse">';
  				html += '<table class="table">';
  				html += '<tr><td colspan="2"><b>NO CONFORMIDAD</b></td><td colspan="2">'+reply.nonconformity+'</td>';
  				html += '<td rowspan="5">';

  				// Validating update button
				if(user_type == 'Externo' && user_privileges == 2 || user_type == 'Interno' && user_privileges == 1){
					var can_update = '';
				}else{
					var can_update = ' disabled';
				}

				html += '<button type="button" class="btn btn-default" onclick="openEditReply('+reply.id+');"'+can_update+' title="Editar réplica"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button><br><br>';
				html += '<button type="button" class="btn btn-default" onclick="openAddComment('+reply.id+', '+reply.question_id+');" title="Añadir nuevo comentario"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button><br><br>';
				
				if(reply.is_closed == 1){
					if(user_privileges == 1){
						html += '<button type="button" class="btn btn-default" onclick="openValidateReply('+reply.id+', '+reply.question_id+');" title="Validar réplica"><span class="glyphicon glyphicon-check" aria-hidden="true"></span></button><br><br>';
					}else{
						html += '<button type="button" class="btn btn-default" onclick="openValidateReply('+reply.id+', '+reply.question_id+');" disabled title="Validar réplica"><span class="glyphicon glyphicon-check" aria-hidden="true"></span></button><br><br>';	
					}
				}else{
					if(user_type == "Interno"){
						html += '<button type="button" class="btn btn-default" onclick="openValidateReply('+reply.id+', '+reply.question_id+');" title="Validar réplica"><span class="glyphicon glyphicon-check" aria-hidden="true"></span></button><br><br>';	
					}
					else{
						html += '<button type="button" class="btn btn-default" onclick="openValidateReply('+reply.id+', '+reply.question_id+');" disabled title="Validar réplica"><span class="glyphicon glyphicon-check" aria-hidden="true"></span></button><br><br>';
					}
				}

				html += '<button type="button" class="btn btn-default" title="Ver comentarios" onclick="showHideRows('+reply.id+')"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></button><br><br>';

				if(user_privileges == 1){
					html += '<button type="button" class="btn btn-danger" onclick="deleteReply('+reply.id+');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';	
				}

  				html += '</td></tr>';
  				html += '<tr><td colspan="2"><b>CAUSA RAÍZ</b></td><td colspan="2">'+reply.root_cause+'</td></tr>';
  				html += '<tr><td colspan="2"><b>ACCIÓN CORRECTIVA</b></td><td colspan="2">'+reply.corrective_action+'</td></tr>';
  				html += '<tr><td colspan="2"><b>RESPONSABLES</b></td><td colspan="2">'+reply.responsible+'</td></tr>';
  				html += '<tr><td colspan="2"><b>EVIDENCIA</b></td><td>';

  				if(reply.evidence_file != null){
					if(reply.evidence_file != ""){
						var extension = reply.evidence_file.split('.');
						var ext = extension[1].toLowerCase();
						var file_icon = '';

						if(ext == 'jpg' || ext == 'png' || ext == 'jpeg' || ext == 'gif'){
							file_icon = '<span class="glyphicon glyphicon-picture" aria-hidden="true" style="font-size: 25px;"></span>';
						}else if(ext == 'doc' || ext == 'docx' || ext == 'pdf'){
							file_icon = '<span class="glyphicon glyphicon-file" aria-hidden="true" style="font-size: 25px;"></span>';
						}else if(ext == 'ppt' || ext == 'pptx'){
							file_icon = '<span class="glyphicon glyphicon-blackboard" aria-hidden="true" style="font-size: 25px;"></span>';
						}else if(ext == 'xls' || ext == 'xlsx'){
							file_icon = '<span class="glyphicon glyphicon-th" aria-hidden="true" style="font-size: 25px;"></span>';
						}
						html += '<a href="https://bluehand.com.mx/console/evidences/'+reply.evidence_file+'" target="_blank">'+file_icon+'</a>';
					}
				}else{
					html += '&nbsp;';
				}

  				html += '</td><td><b>FECHA COMPROMISO</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+reply.commitment_date+' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>ESTADO</b> '+(reply.is_closed == 1 ? '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Cerrada' : '<span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Abierta')+' &nbsp;&nbsp;<b>SATISFACTORIA</b> &nbsp;'+success_legend+'</td></tr>';
  				html += '</table><table class="table">';
  				html += '<tr class="hide-rows" id="'+reply.id+'" style="background-color:#E6E6E6">';
  				html += '<td colspan="4" align="center"><b>COMENTARIOS</b></td>';
  				html += '</tr>';

  				if(reply.comments.length > 0){
					for(var j=0; j<reply.comments.length; j++){
						var comment = reply.comments[j];
						var arr_comment_date = comment.comment_date.split('-');
						var comment_day = arr_comment_date[2];
						var comment_month = arr_comment_date[1];
						var comment_year = arr_comment_date[0];
						var arr_comment_time = comment.comment_time.split(':');
						var comment_hour = arr_comment_time[0];
						var comment_minute = arr_comment_time[1];

						if(comment.status > 0){
							comment_style = '';
						}else{
							comment_style = ' style="background-color:#F5ECCE;"';
						}

						html += '<tr class="hide-rows" id="'+reply.id+'" onclick="set_reply_comment_status('+user_id+', '+comment.comment_id+');"'+comment_style+'>';
						html += '<td>'+reply.question_id+'.'+comment_counter+'</td>';
						html += '<td>'+comment.comment;
						html += '<br>Ingresado por: '+comment.user_name+' el '+comment_day+'-'+comment_month+'-'+comment_year+' a las '+comment_hour+':'+comment_minute+' Hrs.</td>';
						html += '<td><a href="https://bluehand.com.mx/console/evidences/'+comment.evidence_file+'" target="_blank">'+comment.evidence_file+'</a></td>';
						
						if(user_privileges == 1){
							var btn_delete_comment = '';
						}else{
							var btn_delete_comment = ' disabled';
						}

						html += '<td><button type="button" class="btn btn-danger" onclick="deleteReplyComment('+comment.comment_id+');"'+btn_delete_comment+'><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button></td>';
						html += '</tr>';

						comment_counter++;
					}
				}

  				html += '</table>';
  				html += '</div>';
				html += '</div>';

				counter++;
			}

			$('#tbody_reply').html(html);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var openEditReply = function(reply_id){
	$('#edit-a-reply').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/get-reply-by-id/'+reply_id,
		dataType: 'json',
		success: function(response){
			$('#hdn_reply_id').val(reply_id);
			$('#slc_question_edit').html('<option value="">'+response.question+'</option>');
			$('#txa_corrective_action_edit').val(response.corrective_action);
			$('#txa_responsibles_edit').val(response.responsibles);
			$('#dte_commitment_date_edit').val(response.commitment_date);
			$('#hdn_file_name').val(response.evidence_file);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var editReply = function(){
	var reply_id = $('#hdn_reply_id').val();
	var root_cause = $('#txa_root_cause_edit').val();
	var corrective_action = $('#txa_corrective_action_edit').val();
	var responsibles = $('#txa_responsibles_edit').val();
	var commitment_date = $('#dte_commitment_date_edit').val();
	var evidence_file = ($('#fle_evidence_edit').val() != '') ? $('#fle_evidence_edit').val() : $('#hdn_file_name').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/edit-reply',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({reply_id: reply_id, root_cause: root_cause, corrective_action: corrective_action, responsibles: responsibles, commitment_date: commitment_date, evidence_file: evidence_file}),
		success: function(response){
			$('#alert-edit-reply').css('display', 'inline');
			$('#alert-edit-reply').append(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var openValidateReply = function(reply_id, question_id){
	$('#validate-reply').modal('toggle');
	$('#hdn_reply_id_validate').val(reply_id);
	$('#hdn_question_id_validate').val(question_id);
}

var validateReply = function(){
	var reply_id = $('#hdn_reply_id_validate').val();
	var question_id = $('#hdn_question_id_validate').val();
	var validation = $('#slc_action').val();
	var successful = $('#slc_reply_close_result').val();
	var close_comment = $('#txa_close_comment').val();
	var plant_name = $('#slc_plant option:selected').text();
	var report_id = $('#slc_report').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/close-a-reply',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({reply_id: reply_id, validation: validation, successful: successful, close_comment: close_comment}),
		success: function(response){
			$('#alert-close-reply').css('display', 'inline');
			$('#alert-close-reply').append(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});

	if(validation == 1){
		//case_option, plant_name, report_id, reply_number
		//reply_notification('close_reply', plant_name, report_id, question_id);
	}
	
}

var openAddComment = function(reply_id, question_id){
	$('#add-a-comment').modal('toggle');
	$('#hdn_reply_id_cm').val(reply_id);
	$('#txt_user_comment').val(sessionStorage.getItem('audit-suite-name'));
	$('#hdn_question_id_cm').val(question_id);
}

var openAddDueDate = function(report_id){
	$('#hdn_due_report_id').val(report_id);
	$('#add-due-date').modal('toggle');
}

var addDueDate = function(){
	var report_id = $('#hdn_due_report_id').val();
	var due_date = $('#dte_due_date').val();
	
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/assign-due-date/'+report_id+'/'+due_date,
		dataType: 'json',
		success: function(response){
			$('#alert-due-date').css('display', 'inline');
			$('#alert-due-date').append('<div>'+response.message+'</div>');
		},
		error: function(error){
			console.log(error);
		}
	});
}


var addComment = function(){
	var reply_id = $('#hdn_reply_id_cm').val();
	var question_id = $('#hdn_question_id_cm').val();
	var user_id = sessionStorage.getItem('audit-suite-user-id');
	var comment = $('#txa_comment').val();
	var evidence_file = $('#fle_evidence_comment').val();
	var file = $('#fle_evidence_comment').prop('files')[0];
	var plant_name = $('#slc_plant option:selected').text();
	var report_id = $('#hdn_reply_report_id').val();

	if(file != undefined){
		var file_name = file.name;	
	}else{
		var file_name = '';	
	}

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/insert-comment',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({reply_id: reply_id, user_id: user_id, comment: comment, evidence: file_name}),
		success: function(response){
			$('#alert-add-comment').css('display', 'inline');
			$('#alert-add-comment').append(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});

	//case_option, plant_name, report_id, reply_number
	//reply_notification('new_comment', plant_name, report_id, question_id);
}


var showHideRows = function(rows_id){
	var rows = $('tr[id='+rows_id+']');

	$.each(rows, function(index){
		if(rows[index].style.display == 'none'){
			rows[index].style.display = 'table-row';
		}
		else{
			rows[index].style.display = 'none';
		}
	});
}


var getAvailableReports = function(){
	var api_call_r = 'https://dev.bluehand.com.mx/backend/api/v1/reports/get/'+user_id+'/'+user_privileges;
	console.log(api_call_r);
	
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/get/'+user_id+'/'+user_privileges,
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
				//html += '<td>'+report.questionnaire_name+'</td>';
				html += '<td>'+report.audit_date+'</td>';
				//html += '<td>'+report.application_date+'</td>';
				html += '<td>'+report.replies_added+'</td>';
				html += '<td>'+(report.reply_compliance_level == null ? ' ' : report.reply_compliance_level)+'</td>';

				// Validating is the report is closed for replies
				if(report.close_replies == 1){
					var closed_report = ' checked';
					var status_value = 0;

					// Validating if the user is admin (Only admins can open the reports again)
					if(user_privileges == 1){
						var allow_close = '';
					}else{
						var allow_close = ' disabled';
					}
				}else{
					var closed_report = '';
					var status_value = 1;
				}

				html += '<td style="text-align: center;"><span class="badge">'+report.new_reply+'</span></td>';
				html += '<td>';
				html += '<button class="btn btn-default" onclick="goToReplies('+report.report_id+', '+report.client_id+', \''+report.client_name+'\');"><span class="glyphicon glyphicon-eye-open"></span></button>&nbsp;';
				html += '<button class="btn btn-default" onclick="openAddDueDate('+report.report_id+');"><span class="glyphicon glyphicon-calendar"></span></button>';
				html += '</td>';
				html += '<td><label class="switch"><input id="chk_close_replies_'+report.report_id+'" type="checkbox"'+closed_report+''+allow_close+' onclick="closeReportReplies('+report.report_id+', '+status_value+')"><span class="slider round"></span></label></td>';
				html += '</tr>';
			}

			$('#tbody_reports').html(html);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

$("#myInput").show();
$("#myInput").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#tbody_reports tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
});

var backToReply = function(){
	location.href = 'reply.html';
	//window.location.replace('https://bluehand.com.mx/console/reply.html');
}


var deleteReply = function(reply_id){
	var confirm_delete = confirm('¿Está seguro de eliminar esta réplica y sus comentarios?');

	if(confirm_delete == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/delete-reply/'+reply_id,
			dataType: 'json',
			success: function(response){
				alert(response.message);
				//console.log(response.message);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

		location.reload();
	}
}


var getReportGeneralData = function(){
	var selected_report_id = localStorage.getItem('reply_report_id');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/get-general-data/'+selected_report_id,
		dataType: 'json',
		success: function(response){
			$('#hdn_reply_report_id').val(response.report_id);
			$('#sp_report_number').html(response.report_id);
			$('#sp_client').html(response.client);
			$('#sp_branch').html(response.branch);
			$('#sp_questionnaire').html(response.questionnaire_name);
			$('#sp_questionnaire_code').html(response.questionnaire_code);
			$('#sp_auditor').html(response.auditor);
			$('#sp_audit_date').html(response.audit_date);
			$('#sp_total_nonconformities').html(response.total_nonconformities);
			$('#sp_total_replies').html(response.total_replies_added);
			$('#sp_percentage_replies_added').html(response.percentage_of_replies);
			$('#sp_percentage_of_success').html(response.percentage_compliance_level);
			$('#sp_due_date').html(response.due_date);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var closeReportReplies = function(report_id, status){
	var plant_name = $('#slc_plant option:selected').text();
	var close_report = confirm('¿Está seguro de que desea cerrar este reporte?');

	if(close_report == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/report/close-replies/'+report_id+'/'+status,
			dataType: 'json',
			success: function(response){
				alert(response.message);

				if(response.result_code <= 0){
					$('#chk_close_replies_'+report_id).prop('checked', false);
				}
			},
			error: function(){
				console.log(response.message);
			}
		});

		//reply_notification('close_report', plant_name, report_id, '');
	}else{
		$('#chk_close_replies_'+report_id).prop('checked', false);
	}
}

var deleteReplyComment = function(comment_id){
	var delete_comment = confirm('¿Está seguro que desea eliminar este comentario?');

	if(delete_comment == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/delete-reply-comment/'+comment_id,
			dataType: 'json',
			success: function(response){
				alert(response.message);
				location.reload();
			},
			error: function(){
				console.log(response.message);
			}
		});
	}
}

var reply_notification = function(case_option, plant_name, report_id, reply_number){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/emails/send-reply-notification',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({case_option: case_option, plant_name: plant_name, report_id: report_id, reply_number: reply_number}),
		success: function(response){
			console.log(response.code_result);
		},
		error: function(error){
			console.log(error);
		}
	});
}


var set_reply_status = function(user_id, reply_id, report_id){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/set-opened-status/'+user_id+'/'+reply_id+'/'+report_id,
		dataType: 'json',
		success: function(response){
			console.log(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var set_reply_comment_status = function(user_id, reply_detail_id){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reply/set-reply-comment-status/'+user_id+'/'+reply_detail_id,
		dataType: 'json',
		success: function(response){
			console.log(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});
}
