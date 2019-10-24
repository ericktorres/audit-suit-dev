var user_privileges = localStorage.getItem('audit-suite-privilege-user');

//sessionStorage.getItem('audit-suite-user-id');
var auditor_id = sessionStorage.getItem('audit-suite-user-id');


var getCombos = function(user_privileges){	
	console.log(user_privileges);
	if (user_privileges==1) {

		//Cargar gerentes a usuario administrador
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-gerentes',
			dataType: 'json',
			success: function(response){
				$('#cbogerente').append($('<option></option>').attr('value', 0).text('SELECCIONE'));		
				//console.log(response);
				$.each(response, function (key, entry) {
				    $('#cbogerente').append($('<option></option>').attr('value', entry.id_g).text(entry.nombre_gerente));
				});
				
				//$.each(response, function (key, entry) {
				//    $('#cbogerenterr').append($('<option></option>').attr('value', entry.id_g).text(entry.nombre_gerente));
				//});

			},
			error: function(error){
				console.log(error);
			}
		});

		

		$('#cbogerente').on("change", function(){
			$('#cboauditor').empty();
			$('#cboplanta').empty();
			var idGerente = $(this).find(":selected").val();
			console.log("gerente" + idGerente);
			if (idGerente != 0) {	//Administrador Selecciona un gerenete
				$.ajax({
					url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-auditores/'+idGerente,
					dataType: 'json',
					success: function(response){
						$('#cboauditor').append($('<option></option>').attr('value', 0).text('SELECCIONE'));		
						//console.log(response);
						$.each(response, function (key, entry) {
						    $('#cboauditor').append($('<option></option>').attr('value', entry.id_a).text(entry.nombre_auditor));
						});
					},
					error: function(error){
						console.log(error);
					}
				});

				$.ajax({
					url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-planta/'+idGerente,
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

			}
		});	//fiN COMBO QUE CARGA A LOS AUDITORES



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

		//Fin usuario administrador
		
	}else{
		$('#group-cbogerente').hide();


		//obtener auditores que se pueden utilizar por dicho gerente para asignar
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-auditores/'+auditor_id,
			dataType: 'json',
			success: function(response){
				$('#cboauditor').append($('<option></option>').attr('value', 0).text('SELECCIONE'));		
				//console.log(response);
				$.each(response, function (key, entry) {
				    $('#cboauditor').append($('<option></option>').attr('value', entry.id_a).text(entry.nombre_auditor));
				});
				
				$.each(response, function (key, entry) {
				    $('#cboauditorr').append($('<option></option>').attr('value', entry.id_a).text(entry.nombre_auditor));
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

	}


};



///v1/questionnaire/create-event

var crearEvento = function(){

	var auditor_id = $('#cboauditor').val();
	var client_id = $('#cboplanta').val();
	var id_cuest = $('#cbocuestionario').val();
	var f_inicio_audit = $('#datepk6').val();
	var f_fin_audit = $('#datepk7').val();
	var ver_total = $('input[name="ver_total"]:checked').val();

	if (ver_total == "on") {
		ver_total = 1;
	}
	if (typeof ver_total === 'undefined') {
		ver_total = 0;	
	}

	params = {questionnaire_id: id_cuest, client_id: client_id, auditor_id: auditor_id, f_inicio_audit: f_inicio_audit,f_fin_audit: f_fin_audit, ver_total:ver_total};

	console.log(params);

	
	if (auditor_id ==0 || client_id ==0 || id_cuest== 0 || f_inicio_audit == "" || f_fin_audit=="") {
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
	$("#myInput").show();
	$("#myInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tbody_questionnaires tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

	//console.log(auditor_id);
	if (user_privileges==1) {

		
		$("#ger-admin").css("display", "block");

		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-eventos-administrador-admin',
			dataType: 'json',
			success: function(response){

				var tbody_table = $('#tbody_questionnaires');
				var total = response.length;
				var rows = '';
				var questionnaire = null;

				for(var i=0; i<total; i++){
					questionnaire = response[i];
					//console.log(questionnaire.nombre_gerente);
					rows += '<tr>';
	  				rows += '<td>'+questionnaire.id_cuestionario_respondido+'</td>';
	  				rows += '<td>'+questionnaire.nombre_gerente+'</td>';
	  				rows += '<td>'+questionnaire.nombre_auditor+'</td>';
	  				rows += '<td>'+questionnaire.codigo+'</td>';
	  				rows += '<td>'+questionnaire.nombre_comercial+'</td>';
	  				rows += '<td>'+questionnaire.fecha_auditoria+'</td>';
	  				rows += '<td>'+questionnaire.porcentaje_question+'</td>'; 
					rows += '<td>'+questionnaire.editable+'</td>';
					rows += '<td>'+questionnaire.eliminar+'</td>';
	  				rows += '</tr>';
				}

				tbody_table.html(rows);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

	}else{
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
					rows += '<td>'+questionnaire.editable+'</td>';
					rows += '<td>'+questionnaire.eliminar+'</td>';
	  				rows += '</tr>';
				}

				tbody_table.html(rows);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});
		
	}
}



var editEvent = function(id){
	$('#edit-event').modal('toggle');
	//$('#cbogerenterr').empty();
	
	$.ajax({ 
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/get-informe/'+id,
		dataType: 'json',
		success: function(response){
			//console.log(response);

			if (user_privileges==1) {
				$('#cboauditorr').empty();
				$.ajax({
					url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/auditors/get-all-auditores',
					dataType: 'json',
					success: function(response2){
						$('#cboauditorr').append($('<option></option>').attr('value', 0).text('SELECCIONE'));		
						//console.log(response);

						$.each(response2, function (key, entry) {
							$('#cboauditorr').append($('<option></option>').attr('value', entry.id_a).text(entry.nombre_auditor));	
						});
						$("#cboauditorr").val(response.id_auditor).trigger('change');
						 $("#cboauditorr").prop('disabled', 'disabled');
					},
					error: function(error){
						console.log(error);
					}
				});
				//#cboauditorr
				//$('#cboauditorr').prop('selectedIndex', 91);
				$('#cboauditorr').val(91);
				//console.log(response.id_auditor);
				//$('#cboauditorr').val(response.id_auditor);
			}

			//#cboauditorr
			$('#cboauditorr').val(response.id_auditor);
			
			// Filling form with company data
			if (response.f_inicio_audit === null) {
				response.f_inicio_audit ='0000-00-00';
			}
			if (response.f_termino_audit === null) {
				response.f_termino_audit ='0000-00-00';
			}
			
			$('#idEvent').text(id);
			$('#datepk66').val(response.f_inicio_audit);
			$('#datepk77').val(response.f_termino_audit);
			//$('#ver_totall').val(response.resultado_total);

			if (response.resultado_total ==1) {
				$('#ver_totall').prop("checked",true);
			}else{
				$('#ver_totall').prop("checked",false);
			}

		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var updateEvento = function(){

	var idEvent = $('#idEvent').text();
	var f_inicio_audit = $('#datepk66').val();
	var f_fin_audit  = $('#datepk77').val();
	var id_auditor = $('#cboauditorr').val();
	
	var ver_total = $('input[name="ver_totall"]:checked').val();

	if (ver_total == "on") {
		ver_total = 1;
	}
	if (typeof ver_total === 'undefined') {
		ver_total = 0;	
	}

	params = {idEvent:idEvent,f_inicio_audit: f_inicio_audit, f_fin_audit: f_fin_audit, ver_total: ver_total, id_auditor:id_auditor};
	//Editar informe preliminar

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaires/modify-informe',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify(params),
		success: function(response){
			alert(response.message);
			location.reload();
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});

}


var deleteEvent = function(id){
	var confirmElim = confirm("Quieres eliminar el evento seleccionado ?");
	if (confirmElim == true){
		$.ajax({ 
			url: 'https://dev.bluehand.com.mx/backend/api/v1/questionnaire/delete-informe/'+id,
			dataType: 'json',
			success: function(response){

			alert(response.message);
				location.reload();
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

	} 
	else{
		alert("Operación cancelada !");
	}

	
}
