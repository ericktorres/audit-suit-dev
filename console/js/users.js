var onInit = function(){
	getCompanies(); // Loading companies in the select input

	$('#slc_company_create').prop('disabled', true);
	
}

var getCompanies = function(){
	
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/get/1',
		dataType: 'json',
		success: function(response){
			var select_company = $('#slc_company_create');
			var select_company_edit = $('#slc_company_edit');
			var options = '<option value="">Seleccione empresa</option>';

			for(var i=0; i<response.length; i++){
				options += '<option value="'+response[i].id+'">'+response[i].trade_name+'</option>';
			}

			select_company.html(options);
			select_company_edit.html(options);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var enableCompanies = function(operation){
	if(operation == 1){
		if($('#slc_type_create').val() == 2){
			$('#slc_company_create').prop('disabled', false);
		}else{
			$('#slc_company_create').prop('disabled', true);
			$('#slc_company_create').val('');
		}
	}else if(operation == 2){
		if($('#slc_type_edit').val() == 2){
			$('#slc_company_edit').prop('disabled', false);
		}else{
			$('#slc_company_edit').prop('disabled', true);
			$('#slc_company_edit').val('');
		}
	}
}

var getUsers = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/users/get',
		dataType: 'json',
		success: function(response){

			var tbody_table = $('#tbody_users');
			var total = response.length;
			var rows = '';
			var user = null;
			var date = null;

			for(var i=0; i<total; i++){
				user = response[i];

				date = user.creation_date.split(' ');

				if(user.id_privileges == 2){
  					var btn_enabled = '';	
  				}else{
  					var btn_enabled = 'disabled';		
  				}

				rows += '<tr>';
  				rows += '<td>'+user.id+'</td>';
  				rows += '<td>'+user.name+'</td>';
  				rows += '<td>'+user.lastname+'</td>';
  				rows += '<td>'+user.email+'</td>';
  				rows += '<td>'+user.privilege+'</td>';
  				rows += '<td>'+user.user_type+'</td>';
  				rows += '<td class="td-row-actions">';  				
  				rows += '<button type="button" class="btn btn-default" aria-label="Left Align" onclick="getUser('+user.id+')"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>';
				rows += '<button type="button" class="btn btn-default" onclick="openAddUsers('+user.id+', '+user.company_id+', '+user.id_user_type+');" '+btn_enabled+'><span class="glyphicon glyphicon-user"></span></button>';  				
  				
  				if(user.id_privileges == 2){
  					rows += '<button type="button" class="btn btn-default" onclick="openAddCompanies('+user.id+', '+user.id_user_type+');"><span class="glyphicon glyphicon-home"></span></button>';
  				}else if(user.id_privileges == 3){
  					rows += '<button type="button" class="btn btn-default" onclick="openAddCompaniesAuditors('+user.id+');"><span class="glyphicon glyphicon-home"></span></button>';
  				}else{
  					rows += '<button type="button" class="btn btn-default" disabled><span class="glyphicon glyphicon-home"></span></button>';		
  				}
  				
  				
  				rows += '<button type="button" class="btn btn-danger" onclick="deleteUser('+user.id+');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';  				
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

var getUser = function(user_id){
	$('#user-actions').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/user/get-by-id/'+user_id,
		dataType: 'json',
		success: function(response){
			// Filling form with company data
			$('#txt_id_edit').val(response.id);
			$('#slc_type_edit').val(response.id_user_type);
			$('#slc_company_edit').val(response.company_id);
			$('#slc_privileges_edit').val(response.privilege_level);
			$('#slc_is_regional_edit').val(response.is_regional);
			$('#txt_name_edit').val(response.name);
			$('#txt_lastname_edit').val(response.lastname);
			$('#txt_second_lastname_edit').val(response.second_lastname);
			$('#txt_username_edit').val(response.username);
			$('#txt_email_edit').val(response.email);
			$('#txt_password_edit').val(response.password);
			$('#slc_status_edit').val(response.status);
			$('#slc_reviser_edit').val(response.reviser);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var editUser = function(){
	var id_company = $('#slc_company_edit').val();
	var id_user_type = $('#slc_type_edit').val();
	var id_privileges = $('#slc_privileges_edit').val();
	var is_regional_manager = $('#slc_is_regional_edit').val();
	var name = $('#txt_name_edit').val();
	var lastname = $('#txt_lastname_edit').val();
	var second_lastname = $('#txt_second_lastname_edit').val();
	var user_name = $('#txt_username_edit').val();
	var email = $('#txt_email_edit').val();
	var status = $('#slc_status_edit').val();
	var user_id = $('#txt_id_edit').val();
	var is_reviser = $('#slc_reviser_edit').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/users/modify',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({id_company: id_company, id_user_type: id_user_type, id_privileges: id_privileges, is_regional_manager: is_regional_manager, name: name, lastname: lastname, second_lastname: second_lastname, user_name: user_name, email: email, status: status, user_id: user_id, is_reviser: is_reviser}),
		success: function(response){
			$('#alert-users').css('display', 'inline');
			$('#alert-message').html(response.message);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});

	return false;
}

var deleteUser = function(user_id){
	var confirm_delete = confirm('¿Está seguro de eliminar este registro?');

	if(confirm_delete == true){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/users/delete/'+user_id,
			dataType: 'json',
			success: function(response){
				$('#alert-users').css('display', 'inline');
				$('#alert-message').html(response.message);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});

		location.reload();
	}
}

var openCreateUser = function(){
	$('#user-create').modal('toggle');
}

var createUser = function(){
	var id_company = $('#slc_company_create').val();
	var id_user_type = $('#slc_type_create').val();
	var id_privileges = $('#slc_privileges_create').val();
	var is_regional_manager = $('#slc_is_regional_create').val();
	var name = $('#txt_name_create').val();
	var lastname = $('#txt_lastname_create').val();
	var second_lastname = $('#txt_second_lastname_create').val();
	var user_name = $('#txt_username_create').val();
	var email = $('#txt_email_create').val();
	var password = $('#txt_password_create').val();
	var status = $('#slc_status_create').val();
	var is_reviser = $('#slc_reviser_create').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/users/create',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({id_company: id_company, id_user_type: id_user_type, id_privileges: id_privileges, is_regional_manager: is_regional_manager, name: name, lastname: lastname, second_lastname: second_lastname, user_name: user_name, email: email, password: password, status: status, is_reviser: is_reviser}),
		success: function(response){
			$('#alert-users-create').css('display', 'inline');
			$('#alert-message-create').html(response.message);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});	
}

var openAddUsers = function(manager_id, company_id, user_type_id){
	$('#add-users').modal('toggle');

	// Loading all the users that belong to the same company and are the same type of the selected user
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/users/get-by-company-and-type/'+manager_id+'/'+company_id+'/'+user_type_id,
		dataType: 'json',
		success: function(response){
			var rows = '';
			var user = null;

			for(var i=0; i<response.length; i++){
				user = response[i];

				var checked = (user.is_added == 1) ? 'checked' : '';

				rows += '<tr>';
				rows += '<td>'+user.id+'</td>';
				rows += '<td>'+user.privilege+'</td>';
				rows += '<td>'+user.name+'</td>';
				rows += '<td>'+user.lastname+'</td>';
				rows += '<td><input id="chk_add_user['+i+']" type="checkbox" value="'+user.id+'" onchange="addUsers('+i+', '+manager_id+', '+user.id+', \''+user.name+' '+user.lastname+'\');" '+checked+'></td>';
				rows += '</tr>';
			}

			$('#tbody_add_users').html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var addUsers = function(index, manager_id, auditor_id, auditor_name){
	var checkbox_input = document.getElementById('chk_add_user['+index+']');

	if(checkbox_input.checked == true){
		var confirm_add = confirm('¿Desea asignar al auditor "'+auditor_name+'" a el gerente seleccionado?');
		if(confirm_add == true){
			// Try to save the record in DB
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/users/add-users-to-managers',
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify({manager_id: manager_id, auditor_id: auditor_id}),
				success: function(response){
					$('#alert-users-add').css('display', 'inline');
					$('#alert-message-add').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}
	}else{
		var confirm_delete = confirm('¿Desea eliminar al auditor "'+auditor_name+'" del gerente seleccionado?');
		if(confirm_delete == true){
			// Deleting the record
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/users/delete-users-to-managers/'+manager_id+'/'+auditor_id,
				dataType: 'json',
				success: function(response){
					$('#alert-users-add').css('display', 'inline');
					$('#alert-message-add').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}
	}
}


var openAddCompanies = function(manager_id, manager_type){
	$('#add-clients-plants').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/users/get-companies/'+manager_id+'/'+manager_type,
		dataType: 'json',
		success: function(response){
			var rows = '';
			var user = null;

			for(var i=0; i<response.length; i++){
				company = response[i];

				var checked = (company.is_added == 1) ? 'checked' : '';

				rows += '<tr class="row-client">';
				rows += '<td>'+company.id+'</td>';
				rows += '<td><a onclick="showHideRows('+company.id+')" class="company-title">'+company.trade_name+'</a></td>';
				rows += '<td>'+company.business_name+'</td>';
				rows += '<td>'+company.type+'</td>';
				rows += '<td></td>'; //<input id="chk_add_client['+i+']" type="checkbox" value="'+company.id+'" onchange="addClientToUser('+i+', '+manager_id+', '+company.id+', \''+company.trade_name+'\');" '+checked+'>
				rows += '</tr>';

				// Cheking if the client has plants or suppliers
				if(company.plants_and_suppliers.length > 0){

					for(var j=0; j<company.plants_and_suppliers.length; j++){
						var branch = company.plants_and_suppliers[j];

						var checked_branch = (branch.is_added == 1) ? 'checked' : '';

						rows += '<tr class="row-plants-supplier hide-rows" id="'+company.id+'">';
						rows += '<td>'+branch.id+'</td>';
						rows += '<td>'+branch.trade_name+'</td>';
						rows += '<td>'+branch.business_name+'</td>';
						rows += '<td>'+branch.type+'</td>';
						rows += '<td><input id="chk_add_client['+j+']" type="checkbox" value="'+branch.id+'" onchange="addClientToUser('+j+', '+manager_id+', '+branch.id+', \''+branch.trade_name+'\');" '+checked_branch+'></td>';
						rows += '</tr>';						
					}
				}
			}

			$('#tbody_add_clients').html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
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


var addClientToUser = function(index, manager_id, company_id, company_name){
	// Agregar confirmación indicando el nombre del lciente y del proveedor o planta
	var checkbox_input = document.getElementById('chk_add_client['+index+']');
	
	if(checkbox_input.checked == true){
		console.log('Asignar');
		var confirm_add = confirm('¿Desea asignar la empresa "'+company_name+'" a el usuario seleccionado?');
		if(confirm_add == true){
			// Try to save the record in DB
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/users/add-user-to-company',
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify({manager_id: manager_id, company_id: company_id}),
				success: function(response){
					$('#alert-clients-plants').css('display', 'inline');
					$('#alert-message-clients-plants').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}else{
			checkbox_input.checked = false;
		}
	}else{
		console.log('Quitar');
		var confirm_delete = confirm('¿Desea quitar la empresa "'+company_name+'" del usuario seleccionado?');
		if(confirm_delete == true){
			// Deleting the record
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/users/delete-managers-to-companies/'+manager_id+'/'+company_id,
				dataType: 'json',
				success: function(response){
					$('#alert-clients-plants').css('display', 'inline');
					$('#alert-message-clients-plants').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}else{
			checkbox_input.checked = true;
		}
	}
}


var openAddCompaniesAuditors = function(auditor_id){
	$('#add-clients-to-auditors').modal('toggle');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/users/get-companies-for-auditors/'+auditor_id,
		dataType: 'json',
		success: function(response){
			var rows = '';
			var user = null;

			for(var i=0; i<response.length; i++){
				company = response[i];

				var checked = (company.is_assigned == 1) ? 'checked' : '';

				rows += '<tr>';
				rows += '<td>'+company.company_id+'</td>';
				rows += '<td>'+company.trade_name+'</td>';
				rows += '<td><input type="checkbox" id="chk_auditor_company['+i+']" '+checked+' onchange="addClientToAuditor('+i+', '+auditor_id+', '+company.company_id+', \''+company.trade_name+'\');"></td>';
				rows += '</tr>';
			}

			$('#tbody_add_clients_auditors').html(rows);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var addClientToAuditor = function(index, auditor_id, company_id, company_name){
	// Agregar confirmación indicando el nombre del lciente y del proveedor o planta
	var checkbox_input = document.getElementById('chk_auditor_company['+index+']');
	
	if(checkbox_input.checked == true){
		console.log('Asignar');
		var confirm_add = confirm('¿Desea asignar la empresa "'+company_name+'" a el usuario seleccionado?');
		if(confirm_add == true){
			// Try to save the record in DB
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/users/add-auditor-to-company',
				method: 'POST',
				dataType: 'json',
				data: JSON.stringify({auditor_id: auditor_id, company_id: company_id}),
				success: function(response){
					$('#alert-clients-auditors').css('display', 'inline');
					$('#alert-message-clients-auditors').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}else{
			checkbox_input.checked = false;
		}
	}else{
		console.log('Quitar');
		var confirm_delete = confirm('¿Desea quitar la empresa "'+company_name+'" del usuario seleccionado?');
		if(confirm_delete == true){
			// Deleting the record
			$.ajax({
				url: 'https://dev.bluehand.com.mx/backend/api/v1/users/delete-auditors-to-companies/'+auditor_id+'/'+company_id,
				dataType: 'json',
				success: function(response){
					$('#alert-clients-auditors').css('display', 'inline');
					$('#alert-message-clients-auditors').html(response.message);
				},
				error: function(error){
					console.log('Ha ocurrido un error: ' + error);
				}
			});
		}else{
			checkbox_input.checked = true;
		}
	}
}

