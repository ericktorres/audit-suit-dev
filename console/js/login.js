
// Login method
var login = function(){
	var user_name = $('#txt_email').val();
	var password = $('#txt_password').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/users/login',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({ user_name: user_name, password: password}),
		success: function(response){
			//console.log(response);

			if(response.result_code == 1){
				sessionStorage.setItem('audit-suite-user', response.email);
				location.href = 'dashboard.html';
				//window.location.replace('https://bluehand.com.mx/console/dashboard.html');

			}else if(response.result_code == 0){
				alert(response.message);
			}

		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});

	return false;
}