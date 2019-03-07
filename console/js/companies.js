
// Get all companies from database
var getCompanies = function(){

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/get-all',
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_companies');
			var total = response.length;
			var rows = '';
			var company = null;
			var date = null;

			for(var i=0; i<total; i++){
				company = response[i];

				date = company.creation_date.split(' ');

				var disabled = (company.id_type > 1) ? 'disabled' : '';
				var disabled_questionnaires = (company.id_type == 1) ? 'disabled' : '';

				rows += '<tr>';
  				rows += '<td>'+company.id+'</td>';
  				rows += '<td>'+company.type_of_company+'</td>';
  				rows += '<td>'+company.trade_name+'</td>';
  				rows += '<td>'+company.business_name+'</td>';
  				rows += '<td>'+date[0]+'</td>';
  				rows += '<td class="td-row-actions">';		
  				rows += '<button type="button" class="btn btn-default" aria-label="Left Align" onclick="getCompany('+company.id+')"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>';
  				rows += '<button type="button" class="btn btn-default" onclick="openBranchesAndSuppliers('+company.id+');" '+disabled+'><span class="glyphicon glyphicon-home" aria-hidden="true"></span></button>';
  				rows += '<button type="button" class="btn btn-default" onclick="openCompanyQuestionnaires('+company.id+');" '+disabled_questionnaires+'><span class="glyphicon glyphicon-file" aria-hidden="true"></span></button>';
  				rows += '<button type="button" class="btn btn-default" onclick="openUploadLogo('+company.id+');" '+disabled+'><span class="glyphicon glyphicon-picture" aria-hidden="true"></span></button>';
  				rows += '<button type="button" class="btn btn-danger" onclick="deleteCompany('+company.id+');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';
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

var getCompany = function(company_id){
	$('#company-actions').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/company/get/'+company_id,
		dataType: 'json',
		success: function(response){
			// Filling form with company data
			$('#txt_id').val(response.id);
			$('#slc_type').val(response.id_type);
			$('#txt_trade_name').val(response.trade_name);
			$('#txt_business_name').val(response.business_name);
			$('#txt_phone_number').val(response.telephone_number);
			$('#txt_address').val(response.street_and_number);
			$('#txt_suburb').val(response.suburb);
			$('#txt_municipality').val(response.municipality);
			$('#txt_state').val(response.state);
			$('#txt_zip_code').val(response.zip_code);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var editCompany = function(){
	var company_id = $('#txt_id').val();
	var trade_name = $('#txt_trade_name').val();
	var business_name = $('#txt_business_name').val();
	var telephone_number = $('#txt_phone_number').val();
	var street_and_number = $('#txt_address').val();
	var suburb = $('#txt_suburb').val();
	var municipality = $('#txt_municipality').val();
	var state = $('#txt_state').val();
	var zip_code = $('#txt_zip_code').val();
	var id_company_type = $('#slc_type').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/modify',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({company_id: company_id, trade_name: trade_name, business_name: business_name, telephone_number: telephone_number, street_and_number: street_and_number, suburb: suburb, municipality: municipality, state: state, zip_code: zip_code, id_company_type: id_company_type}),
		success: function(response){
			$('#alert-companies').css('display', 'inline');
			$('#alert-message').html(response.message);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var deleteCompany = function(company_id){
	var confirm_delete = confirm('¿Está seguro de eliminar este registro?');

	if(confirm_delete == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/delete/'+company_id,
			dataType: 'json',
			success: function(response){
				$('#alert-companies').css('display', 'inline');
				$('#alert-message').html(response.message);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

		location.reload();
	}
}

var openCreateCompany = function(){
	$('#company-create').modal('toggle');
}

var createCompany = function(){
	var trade_name = $('#txt_trade_name_create').val();
	var business_name = $('#txt_business_name_create').val();
	var telephone_number = $('#txt_phone_number_create').val();
	var street_and_number = $('#txt_address_create').val();
	var suburb = $('#txt_suburb_create').val();
	var municipality = $('#txt_municipality_create').val();
	var state = $('#txt_state_create').val();
	var zip_code = $('#txt_zip_code_create').val();
	var id_company_type = $('#slc_type_create').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/create',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({trade_name: trade_name, business_name: business_name, telephone_number: telephone_number, street_and_number: street_and_number, suburb: suburb, municipality: municipality, state: state, zip_code: zip_code, id_company_type: id_company_type}),
		success: function(response){
			$('#alert-companies-create').css('display', 'inline');
			$('#alert-message-create').html(response.message);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});	
}

var openBranchesAndSuppliers = function(client_id){
	$('#company-branches').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/get-branches-suppliers/'+client_id,
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_branches');
			var total = response.length;
			var rows = '';
			var company = null;

			for(var i=0; i<total; i++){
				company = response[i];

				var checked = (company.is_branch == 1) ? 'checked' : '';

				rows += '<tr>';
  				rows += '<td>'+company.type_of_company+'</td>';
  				rows += '<td>'+company.trade_name+'</td>';
  				rows += '<td>'+company.business_name+'</td>';
  				rows += '<td align="center"><input id="chk_add_company['+i+']" type="checkbox" value="'+company.id+'" '+checked+' onchange="addBranchesAndSuppliers('+i+', '+client_id+', '+company.id+', \''+company.trade_name+'\');"></td>';
  				rows += '</tr>';
			}

			tbody_table.html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var addBranchesAndSuppliers = function(index, client_id, plant_supplier_id, plant_supplier_name){
	// Agregar confirmación indicando el nombre del lciente y del proveedor o planta
	var checkbox_input = document.getElementById('chk_add_company['+index+']');
	
	if(checkbox_input.checked == true){
		var confirm_add = confirm('¿Desea agregar la planta o proveedor "'+plant_supplier_name+'" a el cliente seleccionado?');
		if(confirm_add == true){
			// Try to save the record in DB
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/add-branches-suppliers/',
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify({id_client: client_id, id_plant_supplier: plant_supplier_id}),
				success: function(response){
					$('#alert-companies-branches').css('display', 'inline');
					$('#alert-message-branches').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}
	}else{
		var confirm_delete = confirm('¿Desea eliminar la planta o proveedor "'+plant_supplier_name+'" del cliente seleccionado?');
		if(confirm_delete == true){
			// Deleting the record
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/delete-branches-suppliers/'+client_id+'/'+plant_supplier_id,
				dataType: 'json',
				success: function(response){
					$('#alert-companies-branches').css('display', 'inline');
					$('#alert-message-branches').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}
	}
}


var openCompanyQuestionnaires = function(client_id){
	$('#company-questionnaires').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/get-questionnaires/'+client_id,
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_company_questionnaires');
			var total = response.length;
			var rows = '';
			var questionnaire = null;

			for(var i=0; i<total; i++){
				questionnaire = response[i];

				var checked = (questionnaire.assigned == "1") ? 'checked' : '';

				rows += '<tr>';
  				rows += '<td>'+questionnaire.code+'</td>';
  				rows += '<td>'+questionnaire.name+'</td>';
  				rows += '<td>'+questionnaire.creation_date+'</td>';
  				rows += '<td align="center"><input id="chk_add_questionnaire['+i+']" type="checkbox" value="'+questionnaire.id+'" '+checked+' onchange="addQuestionnaireToClient('+i+', '+client_id+', '+questionnaire.questionnaire_id+', \''+questionnaire.name+'\');"></td>';
  				rows += '</tr>';
			}

			tbody_table.html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});

}

var addQuestionnaireToClient = function(index, client_id, questionnaire_id, questionnaire_name){
	var checkbox_input = document.getElementById('chk_add_questionnaire['+index+']');
	
	if(checkbox_input.checked == true){
		var confirm_add = confirm('¿Desea asignar el cuestionario "'+questionnaire_name+'" a el cliente seleccionado?');
		if(confirm_add == true){
			// Try to save the record in DB
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/assign-questionnaire',
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify({client_id: client_id, questionnaire_id: questionnaire_id}),
				success: function(response){
					$('#alert-companies-questionnaires').css('display', 'inline');
					$('#alert-message-company-questionnaires').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}
	}else{
		var confirm_delete = confirm('¿Desea eliminar la planta o proveedor "'+plant_supplier_name+'" del cliente seleccionado?');
		if(confirm_delete == true){
			// Deleting the record
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/delete-questionnaire-to-company/'+client_id+'/'+questionnaire_id,
				dataType: 'json',
				success: function(response){
					$('#alert-companies-questionnaires').css('display', 'inline');
					$('#alert-message-company-questionnaires').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}
	}
}


var openUploadLogo = function(company_id){
	$('#company-add-logo').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/company/get/'+company_id,
		dataType: 'json',
		success: function(response){
			$('#txt_company_id_logo').val(company_id);
			$('#txt_company_name_logo').val(response.trade_name);
		},
		error: function(error){
			console.log(error);
		}
	});
}

var uploadLogo = function(){
	var file = $('#fle_company_logo').prop('files')[0];
	var form = new FormData();
	form.append('file', file);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/upload-logo',
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

var addCompanyLogo = function(){
	var file = $('#fle_company_logo').prop('files')[0];

	if(file != undefined){
		var logotipo = file.name;	
	}

	var company_id = $('#txt_company_id_logo').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/add-logo',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({logotipo: logotipo, company_id: company_id}),
		success: function(response){
			$('#alert-companies-add-logo').css('display', 'inline');
			$('#alert-message-company-add-logo').html(response.message);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}
