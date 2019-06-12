
//sessionStorage.getItem('audit-suite-user-id');
var auditor_id = sessionStorage.getItem('audit-suite-user-id');

//console.log(auditor_id);

//obtener auditores que se pueden utilizar por dicho gerente para asignar
$.ajax({
	url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-auditores/'+auditor_id,
	dataType: 'json',
	success: function(response){
		$('#cboauditor').append($('<option></option>').attr('value', 0).text('SELECCIONE'));		
		//console.log(response);
		$.each(response, function (key, entry) {
		    $('#cboauditor').append($('<option></option>').attr('value', entry.id_a).text(entry.nombre_auditor));
	//		console.log(entry.id_a);
	//		console.log(entry.nombre_auditor);
		});
	},
	error: function(error){
		console.log(error);
	}
});

//Obterner plantas asignadas a un auditor
$.ajax({
	url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-planta/'+auditor_id,
	dataType: 'json',
	success: function(response){
		$('#cboplanta').append($('<option></option>').attr('value', 0).text('SELECCIONE'));		
		//console.log(response);
		$.each(response, function (key, entry) {
		    $('#cboplanta').append($('<option></option>').attr('value', entry.id_emp).text(entry.nombre_comercial));
		});
	},
	error: function(error){
		console.log(error);
	}
});

$('#cboplanta').on("change", function(){
	$('#cbocuestionario').empty();
	var idEmpresa = $(this).find(":selected").val();
	

	if (idEmpresa != 0) {
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-cuestionar/'+idEmpresa,
			dataType: 'json',
			success: function(response){
				$('#cbocuestionario').append($('<option></option>').attr('value', 0).text('SELECCIONE'));		
				//console.log(response);
				$.each(response, function (key, entry) {
				    $('#cbocuestionario').append($('<option></option>').attr('value', entry.id_cuestionario).text(entry.nombre));
				});
			},
			error: function(error){
				console.log(error);
			}
		});

	}
});

///v1/questionnaire/create-event

var crearEvento = function(){

	var auditor_id = $('#cboauditor').val();
	var client_id = $('#cboplanta').val();
	var id_cuest = $('#cbocuestionario').val();
	var f_inicio = $('#datepk6').val();
	var f_inicia_audit = $('#datepk7').val();



	params = {questionnaire_id: id_cuest, client_id: client_id, auditor_id: auditor_id, f_inicio: f_inicio,f_inicia_audit: f_inicia_audit};

	if (auditor_id ==0 || client_id ==0 || id_cuest== 0 || f_inicio == "" || f_inicia_audit=="") {
		alert("Llenar todos los campos del formulario");
	}else{
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/create-event',
			method: 'POST',
			dataType: 'json',
			data: JSON.stringify(params),
			success: function(response){
				alert(response.message);
				location.reload();
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
				console.error(error);
			}
		});
	}

} 


//Abrir modalbox
var openCreateQuestionnaire = function(){
	$('#questionnnaire-create').modal('toggle');
}

var getEventos = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-eventos-gerente-auditor/'+auditor_id,
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_questionnaires');
			var total = response.length;
			var rows = '';
			var questionnaire = null;

			for(var i=0; i<total; i++){
				questionnaire = response[i];
				//console.log(questionnaire);
				rows += '<tr>';
  				rows += '<td>'+questionnaire.id_cuestionario_respondido+'</td>';
  				rows += '<td>'+questionnaire.nombre_auditor+'</td>';
  				rows += '<td>'+questionnaire.codigo+'</td>';
  				rows += '<td>'+questionnaire.nombre_comercial+'</td>';
  				rows += '<td>'+questionnaire.fecha_auditoria+'</td>';
  				rows += '<td>'+questionnaire.porcentaje_question+'</td>'; 
  				rows += '</tr>';
			}

			tbody_table.html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


/*
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
}	*/

/*
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
}*/



