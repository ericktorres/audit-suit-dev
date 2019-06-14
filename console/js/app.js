// Check if the user is logged
if(sessionStorage.getItem('audit-suite-user') == undefined){
	alert('Para acceder al sistema debe iniciar sesión.');

	window.location.replace('https://dev.bluehand.com.mx/console/');	
}


// Logout function
var logout = function(){
	sessionStorage.clear();
	localStorage.clear();
	window.location.replace('https://dev.bluehand.com.mx/console/');
}

// Set the name of the user
var setSessionData = function(){
	// Setting the name of the user
	$('#lbl_user_name').html(sessionStorage.getItem('audit-suite-name'));
}

// Set the options menu according to the type of user
var setMenu = function(activeDashboard, activeCompanies, activeUsers, activeQuest, activeReports, activeReply, asignarEvent){
	
	var email = sessionStorage.getItem('audit-suite-user');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/user/get',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({ email: email }),
		success: function(response){
			var userPrivileges = response.privilege;
			var menu = '';
			
			//console.log(userPrivileges);

			if(userPrivileges == '1'){
				menu += '<li'+activeDashboard+'><a href="dashboard.html">Dashboard</a></li>';
				menu += '<li'+activeCompanies+'><a href="companies.html">Empresas</a></li>';
				menu += '<li'+activeUsers+'><a href="users.html">Usuarios</a></li>';
				menu += '<li'+activeQuest+'><a href="questionnaires.html">Cuestionarios</a></li>';
				menu += '<li'+activeReports+'><a href="reports.html">Reportes</a></li>';
				menu += '<li'+activeReply+'><a href="reply.html">Réplicas</a></li>';
			}else if(userPrivileges == '2'){
				menu += '<li'+activeDashboard+'><a href="dashboard.html">Dashboard</a></li>';
				//menu += '<li'+activeQuest+'><a href="questionnaires.html">Cuestionarios</a></li>';
				menu += '<li'+activeReports+'><a href="reports.html">Reportes</a></li>';
				menu += '<li'+activeReply+'><a href="reply.html">Réplicas</a></li>';
				menu += '<li'+asignarEvent+'><a href="asignar-eventos.html">Asignar Eventos</a></li>';
			}else if(userPrivileges == '3'){
				menu += '<li'+activeDashboard+'><a href="dashboard.html">Dashboard</a></li>';
				menu += '<li'+activeQuest+'><a href="questionnaires-assigned.html">Auditorías</a></li>';
				menu += '<li'+activeReports+'><a href="reports.html">Reportes</a></li>';
				menu += '<li'+activeReply+'><a href="reply.html">Réplicas</a></li>';
			}

			$('#ul_menu').html(menu);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});

}


// This function sets the email for the user that requires change password
var setEmail = function(){
	$('#txt_email').val(sessionStorage.getItem('audit-suite-user'));
}


// Change password
var changePassword = function(){
	var email = $('#txt_email').val();
	var current_password = $('#txt_cur_password').val();
	var new_password = $('#txt_new_password').val();
	var confirm_new_password = $('#txt_confirm_new_password').val();

	if(new_password == confirm_new_password){
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/user/change-password',
			method: 'POST',
			dataType: 'json',
			data: JSON.stringify({ email: email, current_password: current_password, new_password: new_password }),
			success: function(response){
				alert(response.message);
			},
			error: function(error){
				console.log('Ha ocurrido un error: ' + error);
			}
		});	
	}else{
		alert('La contraseña nueva no coincide con el campo de confirmación.');
	}
	
}


var loadLogo = function(){
	var company_id = localStorage.getItem('audit-suite-client-id');
	console.log(company_id);

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/companies/get-main-logo/'+company_id,
		dataType: 'json',
		success: function(response){
			if(response.logo != undefined){
				var html = '<img alt="Audit Suit" src="'+response.logo+'">';	
			}
			else{
				var html = '<img alt="Audit Suit" src="https://bluehand.com.mx/console/img/main-logo/logo_default.png">';
			}
			
			$('#logo_container').html(html);
		},
		error: function(error){
			console.log(error);
		}
	});
}
