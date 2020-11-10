//var answered_questionnaire_id = null;

//console.log(localStorage);
if($('#hdn_report_id').val() != ""){
	var answered_questionnaire_id = $('#hdn_report_id').val();
	//console.log('1 ' + answered_questionnaire_id);
}else{
	//var answered_questionnaire_id = localStorage.getItem('audit-suit-report-id');
	var answered_questionnaire_id = localStorage.getItem('questionnaire_respondido_id');
	
	//console.log('2 ' + answered_questionnaire_id);
}

var viewInform = function(){
	localStorage.getItem('questionnaire_respondido_id');
	location.href = 'informe.html';
}


var getGeneralData = function(){
	// Validating user privileges
	var user_privileges = localStorage.getItem('audit-suite-privilege-user');
	var user_reviser = localStorage.getItem('audit-suite-user-reviser');

	if(user_privileges == 1){
		$('#txt_audit_date').removeAttr('disabled');
	}else if(user_privileges == 2 && user_reviser == 1){
		$('#txt_audit_date').removeAttr('disabled');
	}else{
		$('#txt_audit_date').attr('disabled', 'disabled');
	}

	//console.log(answered_questionnaire_id);
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/general-data/'+answered_questionnaire_id,
		dataType: 'json',
		success: function(response){

			$('#sp_report_number').html(answered_questionnaire_id);
			$('#sp_client').html(response.client);
			$('#sp_branch').html(response.branch);
			$('#sp_questionnaire').html(response.questionnaire_name);
			$('#sp_questionnaire_code').html(response.questionnaire_code);
			$('#sp_auditor').html(response.auditor);
			$('#start_date').html(response.start_date+' '+response.start_time);
			$('#end_date').html(response.end_date+' '+response.end_time);
			$('#txt_audit_date').val(response.audit_date);
			$('#hdn_report_id_date').val(response.report_id);
			$("#txt_res_audit").text(response.audit_atiende);
			$("#imag_firma").attr("src", response.firma);
			$("#imag_firma2").attr("src", response.firma2);

			manageReportActions(response.process_report);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var getResultsBySection = function(show_chart){
	var labels = [];
	var chart_data = [];
	var total_percentage = 0;
	var percentage = 0;
	var total_got_score = 0;
	var max_score = 0;
	var discount_points = null;
	var section_percentage = null;
	var na_sections_values = 0;

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/get-na-questions-values/'+answered_questionnaire_id,
		dataType: 'json',
		success: function(response){
			discount_points = response.discount_points;
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
	
	// Validate that first get the discount points and then calculate the percentages
	setTimeout(function(){

		$.ajax({
			//url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/score-by-section/'+answered_questionnaire_id,
			url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/percentage-by-section/'+answered_questionnaire_id,
			dataType: 'json',
			success: function(response){
				var sections_html = '';
				var section_number = 1;
				var color_class = '';

				for(var i=0; i<response.length; i++){
					// Validating null sections just for NA sections
					if(response[i].got_value == null){
						response[i].got_value = 0;
					}
					
					labels.push(response[i]['section']);
					//labels.push(section_number);
					//console.log(utf8_encode());

					chart_data.push(response[i].score);
					total_percentage += parseFloat(response[i].score);
					max_score += parseInt(response[i].value);
					total_got_score += parseFloat(response[i].got_value);

					// Logic for rows color
					if(parseFloat(response[i].score) <= parseFloat(70)){
						color_class = 'bg-row-bad';
					}else if(parseFloat(response[i].score) > parseFloat(70) && parseFloat(response[i].score) < parseFloat(85)){
						color_class = 'bg-row-improvement';
					}else if(parseFloat(response[i].score) > parseFloat(85) && parseFloat(response[i].score) < parseFloat(95)){
						color_class = 'bg-row-satisfactory';
					}else if(parseFloat(response[i].score) > parseFloat(95)){
						color_class = 'bg-row-excellent';
					}else if(response[i].score == null){
						// For NA sections
						color_class = 'bg-row-excellent';
					}

					//section_percentage = parseFloat(response[i].score).toFixed(1);
					section_percentage = (response[i].score == null) ? 'NA' : parseFloat(response[i].score).toFixed(1) + ' %';

					sections_html += '<tr>';
					sections_html += '<td style="text-align:center;">'+section_number+'</td>';
					sections_html += '<td colspan="2">'+response[i].section+'</td>';
					sections_html += '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
					sections_html += '<td class="'+color_class+'">'+section_percentage+' </td>';
					sections_html += '</tr>';

					section_number++;
				}

				// Calculating the total score
				max_score = max_score - na_sections_values;
				//console.log('Max score: ' + max_score);
				//console.log('Got score: ' + total_got_score);
				//console.log('Disccount points: ' + discount_points);
				var scored_result = (total_got_score * 100) / (max_score - discount_points);
				//console.log('Result score: ' + scored_result);

				// Adding rows to the table for the section results
				$('#tbody_sections_result').html(sections_html);

				// Total percentage
				//percentage = (parseFloat(total_percentage) * 100) / (parseInt(response.length) * 100);
				//$('#span_total_percentage').html(percentage.toFixed(2));
				

				// Total score based on the values of each question
				$('#span_total_percentage').html(scored_result.toFixed(2));

				var ctx = $('#results-by-section');

				
				if(show_chart == 1){
//					console.log("entra a la grafica");
//					console.log(chart_data);
//					console.log(labels);


					
					var chart = new Chart(ctx, {
				    	type: 'horizontalBar',
				    	data: {
				        	labels: labels,
				        	datasets: [{
					            label: "Porcentaje por sección",
					            backgroundColor: '#5882FA',
					            borderColor: '#5882FA',
					            data: chart_data,
				        	}]
				    	},
				    	options: {
				    		scales: {
				            	yAxes: [{
				            		gridLines: {
								        color: "black",
								        borderDash: [2, 5],
								      },
				                	ticks: {
				                		//callback: function(value, index, values) {
					                    //    return value + '%';
					                    //},
				                    	beginAtZero:true
				                	}
				            	}],
				            	xAxes: [{
							                ticks: {
												callback: function(value, index, values) {
												    return value + '%';
												},
							                    autoSkip: false
							                }
							    }]
				        	},
				        	responsive: true
				    	}
					});
				}

			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

	}, 1000);

				


}

var getOpportunityAreas = function(){
	var user_privileges = localStorage.getItem('audit-suite-privilege-user');
	var user_reviser = localStorage.getItem('audit-suite-user-reviser');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/opportunity-areas/'+answered_questionnaire_id,
		dataType: 'json',
		success: function(response){
			var html = '';
			var question = null;
			var class_row = '';

			for(var i=0; i<response.length; i++){
				question = response[i];

				if(i % 2 == 0){
					class_row = ' class="table-active"';
				}else{
					class_row = '';
				}

				html += '<tr'+class_row+'>';
				html += '<td>'+question.section+'</td>';
				html += '<td>'+question.question+'</td>';
				html += '<td>'+question.selected_option+'</td>';
				html += '<td>'+question.value+'</td>';

				if(user_privileges == 1){
					html += '<td class="print-hide"><button type="button" class="btn btn-primary" onclick="openEditAnswer('+question.answer_id+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
				}else if(user_privileges == 2 && user_reviser == 1){
					html += '<td class="print-hide"><button type="button" class="btn btn-primary" onclick="openEditAnswer('+question.answer_id+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
				}else if(user_privileges == 2 && user_reviser == 0){
					html += '<td class="print-hide"><button type="button" class="btn btn-primary" onclick="openEditAnswer('+question.answer_id+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
				}else{
					html += '<td class="print-hide"><button disabled type="button" class="btn btn-primary" onclick="openEditAnswer('+question.answer_id+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
				}
				
				html += '</tr>';

				html += '<tr'+class_row+'>';
				html += '<td><b>AYUDA</b></td>';
				html += '<td colspan="3">'+question.help_text+'</td>';
				html += '<td class="print-hide"></td>';
				html += '</tr>';

				html += '<tr'+class_row+'>';
				html += '<td><b>OBSERVACIONES</b></td>';
				html += '<td colspan="3">'+question.observations+'</td>';
				html += '<td class="print-hide"></td>';
				html += '</tr>';

				html += '<tr'+class_row+'>';
				html += '<td><b>NO CONFORMIDAD</b></td>';
				html += '<td colspan="3">'+question.nonconformity+'</td>';
				html += '<td class="print-hide"></td>';
				html += '</tr>';
			}

			$('#tbody_opportunity').html(html);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var getReportQuestions = function(){
	var user_privileges = localStorage.getItem('audit-suite-privilege-user');
	var user_reviser = localStorage.getItem('audit-suite-user-reviser');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/questions/'+answered_questionnaire_id,
		dataType: 'json',
		success: function(response){
			var html = '';
			var question = null;
			var class_row = '';

			for(var i=0; i<response.length; i++){
				
				html += '<tr><td colspan="5" style="text-align:center;"><b>SECCIÓN '+ response[i].seccion_id + ' ' + response[i].seccion_name+'</b></td></tr>';

				for (var j = 0; j < response[i].objeto.length; j++) {
					if(j % 2 == 0){
						class_row = ' class="table-active"';
					}else{
						class_row = '';
					}
					
					html += '<tr'+class_row+'>';
					html += '<td><b>PREGUNTA ' + response[i].objeto[j]['id_section'] + '.' +response[i].objeto[j]['num_pregunta']+ '</b></td>';
					html += '<td>'+response[i].objeto[j]['question']+'</td>';
					html += '<td>'+response[i].objeto[j]['selected_option']+'</td>';
					html += '<td>'+response[i].objeto[j]['value']+'</td>';
					
					if(user_privileges == 1){
						html += '<td class="print-hide"><button type="button" class="btn btn-primary" onclick="openEditAnswer('+response[i].objeto[j]['answer_id']+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
					}else if(user_privileges == 2 && user_reviser == 1){
						html += '<td class="print-hide"><button type="button" class="btn btn-primary" onclick="openEditAnswer('+response[i].objeto[j]['answer_id']+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
					}else if(user_privileges == 2 && user_reviser == 0){
						html += '<td class="print-hide"><button type="button" class="btn btn-primary" onclick="openEditAnswer('+response[i].objeto[j]['answer_id']+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
					}else{
						html += '<td class="print-hide"><button disabled type="button" class="btn btn-primary" onclick="openEditAnswer('+response[i].objeto[j]['answer_id']+');"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button></td>';	
					}

					html += '</tr>';

					html += '<tr'+class_row+'>';

					html += '<td><b>AYUDA</b></td>';
					html += '<td colspan="3">'+response[i].objeto[j]['help_text']+'</td>';
					html += '<td class="print-hide"></td>';
					html += '</tr>';

					html += '<tr'+class_row+'>';
					html += '<td><b>OBSERVACIONES</b></td>';
					html += '<td colspan="3">'+response[i].objeto[j]['observations']+'</td>';
					html += '<td class="print-hide"></td>';
					html += '</tr>';

					html += '<tr'+class_row+'>';
					html += '<td><b>NO CONFORMIDAD</b></td>';
					html += '<td colspan="3">'+response[i].objeto[j]['nonconformity']+'</td>';
					html += '<td class="print-hide"></td>';
					html += '</tr>';

					if(j == 2){
						html += '<tr'+class_row+'>';
						html += '<td colspan="5" text-align="center"><img src="https://cdn.forbes.com.mx/2015/02/Grupo-Posadas-e1593120598600.jpg" width="100%"></td>';
						html += '</tr>';
					}else if(j == 3){
						html += '<tr'+class_row+'>';
						html += '<td colspan="5" text-align="center"><img src="https://realestatemarket.com.mx/images/2020/03-Marzo/2403/grupo_posadas_p.jpg" width="100%"></td>';
						html += '</tr>';
					}

				}

			}

			$('#tbody_report_questions').html(html);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var backToReports = function(){
	location.href = 'reports.html';
	//window.location.replace('https://dev.bluehand.com.mx/console/reports.html');
}


var getPdf = function(){
    var specialElementHandlers = {
        '#editor': function (element, renderer){
            return true;
        }
    };
       
    var doc = new jsPDF();
    
    doc.fromHTML($('#target').html(), 15, 15, {
        'width': 170,'elementHandlers': specialElementHandlers
    });
    
    doc.save('sample-file.pdf');
}


var downloadReport = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/generate-pdf/'+answered_questionnaire_id,
		data: 'json',
		success: function(response){
			var url_file = response.report_url;
			var download_link = document.createElement('a');
			download_link.download = 'report-no-'+answered_questionnaire_id+'.pdf';
			download_link.href = url_file;
			document.body.appendChild(download_link);
  			download_link.click();
  			document.body.removeChild(download_link);
  			delete download_link;

     		//window.open(link, 'Download'); 
			console.log(url_file);
		},
		error: function(error){
			console.log(error);
		}
	})

}


var manageReleaseButtons = function(){
	var btn_release = $('#btn_release_report');
	var user_privileges = localStorage.getItem('audit-suite-privilege-user');
	var user_type = localStorage.getItem('audit-suite-user-type');
	var user_reviser = localStorage.getItem('audit-suite-user-reviser');

	if(user_privileges == 1){
		btn_release.removeAttr('disabled');
	}else if(user_privileges == 2){
		if(user_type == "Externo"){
			if(user_reviser == 1){
				btn_release.removeAttr('disabled');
			}else{
				btn_release.attr('disabled', true);
			}
		}else{
			btn_release.removeAttr('disabled');
		}
	}
}


var openEditAnswer = function(answer_id){
	$('#edit-answer').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/answer/get/'+answer_id,
		dataType: 'json',
		success: function(response){
			// Filling question options
			var options = '';
			for(var i=0; i<response.question_options.length; i++){
				options += '<option value="'+response.question_options[i].option_id+'_'+response.question_options[i].option_value+'">'+response.question_options[i].option_desc+'</option>';
			}
			$('#slc_answer').html(options);

			$('#txt_answer_id').val(response.answer_id);
			$('#txt_section').val(response.section);
			$('#txt_question').val(response.question);
			$('#slc_answer').val(response.selected_option_id+'_'+response.value);
			$('#txt_value').val(response.value);
			$('#txa_observations').val(response.observations);
			$('#txa_nonconformity').val(response.nonconformity);
			$('#hdn_question_max_value').val(response.question_value);

			if(response.not_apply == "1"){
				$('#chk_notapply').prop('checked', true);
			}else{
				$('#chk_notapply').prop('checked', false);
			}
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var editAnswerValue = function(){	
	var answer_value = $('#slc_answer').val();
	var arr_answer_values = answer_value.split('_');

	$('#txt_value').val(arr_answer_values[1]);
}


var editQuestionAnswer = function(){
	var answer_value = $('#slc_answer').val();
	var arr_answer_values = answer_value.split('_');

	var answer_id = $('#txt_answer_id').val();
	var option_id = arr_answer_values[0];
	var score = ( $('#txt_value').val() * $('#hdn_question_max_value').val() ) / 100;
	var value = $('#txt_value').val();
	var observations = $('#txa_observations').val();
	var nonconformity = $('#txa_nonconformity').val();
	var not_apply = 0;

	if($('#chk_notapply').is(':checked')){

		not_apply = 1;
	}else{
		not_apply = 0;
	}

	var params = {answer_id: answer_id, option_id: option_id, score: score, value: value, observations: observations, nonconformity: nonconformity, not_apply: not_apply};
	
	//console.log(JSON.stringify(params));

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/answer/edit',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify(params),
		success: function(response){
			alert(response.message);
		},
		error: function(error){
			console.log(error);
		}
	})
}


var assignAuditDate = function(){
	var report_id = $('#hdn_report_id_date').val();
	var audit_date = $('#txt_audit_date').val();
	var params = {report_id: report_id, audit_date: audit_date};

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/assign-audit-date',
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


var reportOpened = function(){
	var report_id = answered_questionnaire_id;
	var user_id = sessionStorage.getItem('audit-suite-user-id');
	var params = {report_id: report_id, user_id: user_id};
	//console.log(params);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/set-status',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify(params),
		success: function(response){
			//console.log(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});	
}


var manageReportActions = function(process_report){
	var user_privileges = localStorage.getItem('audit-suite-privilege-user');
	var user_reviser = localStorage.getItem('audit-suite-user-reviser');
	var user_regional = localStorage.getItem('audit-suite-user-regional');
	var btn_review = $('#btn_send_to_review');
	var btn_approve = $('#btn_approves');
	var btn_release = $('#btn_release_report');

	console.log("revisor" + user_reviser);
	console.log("regional" + user_regional);
	console.log("proceso de reporte" + process_report);
	console.log("privilegios" + user_privileges);	
	
	
	//Cualquier usuario que no tiene asignado un rol adicional, no es revisor ni aprobador, ni liberador y no es administradpr
	if (user_reviser == 0 && user_regional == 0 && user_privileges != 1) {
		btn_review.attr('disabled', 'disabled');
		btn_approve.attr('disabled', 'disabled');
		btn_release.attr('disabled', 'disabled');
	}

	//Reportes sin revisiones, aprobados ni liberados 
	if (process_report == 0) { 
		if (user_reviser == 1 || user_privileges == 1) { //usuario revisor cuando no se ha inicio un proceso y es administrador
			btn_review.attr('disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
		
		if (user_regional == 1) { //usuario regional cuando no se ha iniciado el proceso
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
	}

	//reporte revisado
	if (process_report == 1){
		if (user_reviser == 1) { //usuario revisort no puede hacer nada cuando abre un reporte con proceso revisado
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
		if (user_regional == 1  || user_privileges == 1) { //usuario regional cuando se reviso, puede aprobar tambien si es administrador 
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled');
			btn_release.attr('disabled', 'disabled');
		}
	}

	//reporte aprobado
	if (process_report == 2){
		if (user_regional == 1) { //usuario revisor no puede hacer nada cuando abre un reporte con proceso aprobado
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
		
		if (user_reviser == 1 || user_privileges == 1) { //usuario regional cuando se aprobo, puede liberar tambien si es administrador
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled');
		}		
	}

	//reporte liberado
	if (process_report == 3){
		if (user_reviser == 1) { //usuario revisor no puede hacer nada cuando abre un reporte con proceso aprobado y porque ya está liberado
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
		if (user_regional == 1) { //usuario regional no puede hacer nada porque ya está liberado
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
		if (user_privileges == 1) { //usuario regional no puede hacer nada porque ya está liberado
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
		
	}


	/*
	if(user_privileges == 1){
		btn_review.removeAttr('disabled');
		btn_approve.removeAttr('disabled');
		btn_release.removeAttr('disabled');
	}
	else if(user_privileges == 2){
		if(user_reviser == 1 && user_regional == 0){
			btn_review.removeAttr('disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.removeAttr('disabled');	
		}else if(user_reviser == 0 && user_regional == 1){
			btn_review.attr('disabled', 'disabled');
			btn_approve.removeAttr('disabled');
			btn_release.attr('disabled', 'disabled');
		}else{
			btn_review.attr('disabled', 'disabled');
			btn_approve.attr('disabled', 'disabled');
			btn_release.attr('disabled', 'disabled');
		}
	}else{
		btn_review.attr('disabled', 'disabled');
		btn_approve.attr('disabled', 'disabled');
		btn_release.attr('disabled', 'disabled');
	}*/
}


//reviewReport revisado
//approveReport aprobado
//releaseReport liberado


var reviewReport = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/send-to-review/'+answered_questionnaire_id,
		dataType: 'json',
		success: function(response){
			alert(response.message);
			location.reload();
		},
		error: function(error){
			console.log(error);
		}
	});

	// Sending the email notification
	//sendReviewNotification(answered_questionnaire_id);
}


var sendReviewNotification = function(report_id){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/emails/send-review-notification/'+report_id,
		dataType: 'json',
		success: function(response){
			console.log(response.code_result);
		},
		error: function(error){
			console.log(error);
		}
	});
}


var approveReport = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/report/approves-for-release/'+answered_questionnaire_id,
		dataType: 'json',
		success: function(response){
			alert(response.message);
			location.reload();
		},
		error: function(error){
			console.log(error);
		}
	});

	//sendApprovalNotification(answered_questionnaire_id);
}


var sendApprovalNotification = function(report_id){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/emails/send-approval-notification/'+report_id,
		dataType: 'json',
		success: function(response){
			console.log(response.code_result);
		},
		error: function(error){
			console.log(error);
		}
	});
}


var releaseReport = function(){
	var confirm_release = confirm('¿Está seguro de liberar este reporte?');

	if(confirm_release == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/report/release-report/'+answered_questionnaire_id,
			data: 'json',
			success: function(response){
				alert(response.message);
				location.reload();
			},
			error: function(error){
				console.log(error);
			}
		});

		// Sending the email notification
		//sendReleaseNotification(answered_questionnaire_id);
	}
}


var sendReleaseNotification = function(report_id){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/emails/send-release-notification/'+report_id,
		data: 'json',
		success: function(response){
			console.log(response.code_result);
		},
		error: function(error){
			console.log(error);
		}
	});
}



var saveReportData = function(){
	var report_id = $("#sp_report_number").html();
	var client_name = $("#sp_client").html();
	var plant_name = $("#sp_branch").html();
	var auditor_name = $("#sp_auditor").html();
	var total_score = $("#span_total_percentage").html();
	var audit_date = $("#txt_audit_date").val();
	var params = {report_id: report_id, client_name: client_name, plant_name: plant_name, auditor_name: auditor_name, total_score: total_score, audit_date: audit_date};
	console.log(params);
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/save-report-data',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify(params),
		success: function(response){
			console.log(response.message);
		},
		error: function(error){
			console.log(error);
		}
	});	
}
