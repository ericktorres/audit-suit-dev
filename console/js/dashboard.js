
// Getting data from the logged user
var getUserData = function(){
	var email = sessionStorage.getItem('audit-suite-user');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/user/get',
		method: 'POST',
		dataType: 'json',
		data: JSON.stringify({ email: email }),
		success: function(response){
			$('#lbl_user_name').html(response.name + ' ' + response.lastname);

			// Setting session variables
			sessionStorage.setItem('audit-suite-name', response.name + ' ' + response.lastname);
			sessionStorage.setItem('audit-suite-user-id', response.id);
			localStorage.setItem('audit-suite-client-id', response.company_id);
			localStorage.setItem('audit-suite-privilege-user', response.privilege);
			localStorage.setItem('audit-suite-user-status', response.status);
			localStorage.setItem('audit-suite-user-reviser', response.reviser);
			localStorage.setItem('audit-suite-user-type', response.user_type);
			localStorage.setItem('audit-suite-user-regional', response.is_regional);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


// Loading the plants in the select field
var getPlants = function(){
	var email = sessionStorage.getItem('audit-suite-user');

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-plants/'+email,
		dataType: 'json',
		success: function(response){
			var options = '<option value="0">TODAS</option>';
			var plant = null;

			// Getting the rest of the plants availables
			for(var i=0; i<response.length; i++){
				plant = response[i];

				options += '<option value="'+plant.client_id+'-'+plant.business_name+'">'+plant.business_name+'</option>';
			}

			$('#slc_plant').html(options);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}

var manageCharts = function(){
	var str_plant = $('#slc_plant').val().split('-');
	var plant = str_plant[0];
	var section = $('#slc_section').val();
	var question = $('#slc_question').val();
	var questionnaire = $('#slc_questionnaire').val();
	var year1 = $('#slc_year').val();
	var year2 = $('#slc_year_2').val();

	if(plant == 0 && section == 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 == 0){
		// Getting all plants by questionnaire
		graphingAllPlants();
		console.log('graphingAllPlants');
	}else if(plant > 0 && section == 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 == 0){
		// Getting one plant by questionnaire
		graphingOnePlant();
		console.log('graphingOnePlant');
	}else if(plant == 0 && section > 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 == 0){
		// Getting all plants by section
		graphingAllPlantsBySection();
		console.log('graphingAllPlantsBySection');
	}
	else if(plant > 0 && section > 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 == 0){
		// Getting one plant by section
		graphingOnePlantBySection();
		console.log('graphingOnePlantBySection');
	}
	else if(plant == 0 && section > 0 && question > 0 && questionnaire > 0 && year1 > 0 && year2 == 0){
		// Getting all plants by question
		graphingAllPlantsByQuestion();
		console.log('graphingAllPlantsByQuestion');
	}
	else if(plant > 0 && section > 0 && question > 0 && questionnaire > 0 && year1 > 0 && year2 == 0){
		// Graphing one plant by question
		graphingOnePlantByQuestion();
		console.log('graphingOnePlantByQuestion');
	}
	else if(plant == 0 && section == 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 > 0){
		// Getting all plants by questionnaire in comparative mode
		graphingAllPlantsComparative();
		console.log('graphingAllPlantsComparative');
	}
	else if(plant > 0 && section == 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 > 0){
		// Getting one plant by questionnaire in comparative mode
		graphingOnePlantComparative();
		console.log('graphingOnePlantComparative');
	}
	else if(plant == 0 && section > 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 > 0){
		// Getting all plants by section in comparative mode
		graphingAllPlantsBySectionComparative();
		console.log('graphingAllPlantsBySectionComparative');
	}
	else if(plant > 0 && section > 0 && question == 0 && questionnaire > 0 && year1 > 0 && year2 > 0){
		// Getting one plant by section comparative
		graphingOnePlantBySectionComparative();
		console.log('graphingOnePlantBySectionComparative');
	}
	else if(plant == 0 && section > 0 && question > 0 && questionnaire > 0 && year1 > 0 && year2 > 0){
		// Getting all plants by question in comparative mode
		graphingAllPlantsByQuestionComparative();
		console.log('graphingAllPlantsByQuestionComparative');
	}
	else if(plant > 0 && section > 0 && question > 0 && questionnaire > 0 && year1 > 0 && year2 > 0){
		// Graphing one plant by question in comparative mode
		graphingOnePlantByQuestionComparative();
		console.log('graphingOnePlantByQuestionComparative');
	}
	else{
		alert('No hay información para los parámetros de búsqueda ingresados.');
	}

}

var graphingOnePlant = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var year = $('#slc_year').val();
	var str_plant = $('#slc_plant').val().split('-');
	var plant_id = str_plant[0];
	var plant_name = str_plant[1];

	// Showing the loading indicator
	$('#spinner').show();

	var chart_data = [];
	chart_data.push(['Sección', 'Puntaje', { 'role': 'annotation' }]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-one-plant-by-questionnaire/'+plant_id+'/'+questionnaire_id+'/'+year,
			dataType: 'json',
			success: function(response){
				
				for(var i = 0; i<response.length; i++){
					section = response[i];
					row = [section.section_name, section.percentage, section.percentage];
					chart_data.push(row);
				}

				console.log(chart_data)

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			if(chart_data.length > 1){
				var data = new google.visualization.arrayToDataTable(chart_data, false);

				// Set chart options
				var options = {
					'title':'PROMETEO MARCA',
					'width':1200,
					'height':750,
					'orientation':'horizontal',
					'vAxis':{'ticks':[10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
				};

				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
				chart.draw(data, options);

				// Printing the data in a table
				var table = '<table class="table" style="width:80%; margin:auto;">';
				table += '<thead>';
				table += '<tr>';
				table += '<th colspan="2">RESULTADO GLOBAL DE LA PLANTA '+plant_name.toUpperCase()+' EN EL PERIODO '+year+'</th>';
				table += '</tr>';
				table += '<tr>';
				table += '<th>SECCIÓN</th>';
				table += '<th>RESULTADO</th>';
				table += '</tr>';
				table += '</thead>';
				table += '<tbody>';

				for(var j=0; j<chart_data.length; j++){
					if(j > 0){
						table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td></tr>';	
					}
				}

				table += '</tbody>';
				table += '</table>';

				$('#table_div').html(table);
			}else{
				alert('No se ha encontrado información para la planta y periodo solicitado.');
			}
		}
	);
}

var graphingAllPlants = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var year = $('#slc_year').val();
	var plants_id = [];
	var plant = null;

	// Showing the loading indicator
	$('#spinner').show();

	// Building the plants array	
	$('#slc_plant option').each(function(i){
		if(i > 0){
			plant = $(this).val().split('-');
			plants_id.push({"id": parseInt(plant[0]), "name":plant[1]});
		}
		
	});

	var params = '{"questionnaire_id": '+questionnaire_id+', "year": '+year+', "plants_id": '+JSON.stringify(plants_id)+'}';

	console.log(params);
	
	var chart_data = [];
	chart_data.push(['Planta', 'Puntaje', { 'role': 'annotation' }]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-plants-by-questionnnaire',
			method: 'POST',
			dataType: 'json',
			data: params,
			success: function(response){
				
				for(var i = 0; i<response.length; i++){
					plant = response[i];

					if(response[i].reports != null){
						for(var j = 0; j<response[i].reports.length; j++){
							report = response[i].reports[j];
							row = [plant.client_name, report.total_score, report.total_score];
							chart_data.push(row);
							x++;
						}	
					}
					
				}

				console.log(chart_data);

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			var data = new google.visualization.arrayToDataTable(chart_data, false);

			// Set chart options
			var options = {
				'title':'GEPP-PLANTAS-VER2017-01',
				'width':1100,
				'height':750,
				'orientation':'horizontal',
				'vAxis':{'ticks':[10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
			};

			// Instantiate and draw our chart, passing in some options.
			var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
			chart.draw(data, options);

			// Printing the data in a table
			var table = '<table class="table" style="width:80%; margin:auto;">';
			table += '<thead>';
			table += '<tr>';
			table += '<th colspan="2" text-align="center">RESULTADO GLOBAL DE TODAS LAS PLANTAS EN EL PERIODO '+year+'</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th>PLANTA</th>';
			table += '<th>RESULTADO</th>';
			table += '</tr>';
			table += '</thead>';
			table += '<tbody>';

			for(var j=0; j<chart_data.length; j++){
				data = chart_data[j];

				if(j > 0){
					table += '<tr><td>'+data[0]+'</td><td>'+data[1]+'</td></tr>';
				}
			}

			table += '</tbody>';
			table += '</table>';

			$('#table_div').html(table);

		}
	);
}


var graphingAllPlantsBySection = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var section_id = $('#slc_section').val();
	var year = $('#slc_year').val();
	var chart_name = '';
	var plants_id = [];

	// Building the plants array	
	$('#slc_plant option').each(function(i){
		if(i > 0){
			plant = $(this).val().split('-');
			plants_id.push({"id": parseInt(plant[0]), "name":plant[1]});
		}
		
	});
	
	var params = '{"questionnaire_id": '+questionnaire_id+', "section_id": '+section_id+', "year": '+year+', "plants_id": '+JSON.stringify(plants_id)+'}';
	
	// Showing the loading indicator
	$('#spinner').show();

	var chart_data = [];
	chart_data.push(['Planta', 'Puntaje', { 'role': 'annotation' }]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-plants-by-section',
			method: 'POST',
			dataType: 'json',
			data: params,
			success: function(response){
				for(var i = 0; i<response.length; i++){
					plant = response[i];
					row = [plant.client_name, plant.section_percentage, plant.section_percentage];
					chart_data.push(row);

					chart_name = plant.section_name;
				}

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			if(chart_data.length > 1){
				var data = new google.visualization.arrayToDataTable(chart_data, false);

				// Set chart options
				var options = {
					'title': chart_name,
					'width': 1200,
					'height': 750,
					'orientation': 'horizontal',
					'vAxis':{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
				};

				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
				chart.draw(data, options);

				// Printing the data in a table
				var table = '<table class="table">';
				table += '<thead>';
				table += '<tr>';
				table += '<th colspan="2">RESULTADO DE TODAS LAS PLANTAS EN LA SECCIÓN '+chart_name.toUpperCase()+' EN EL AÑO '+year+'</th>';
				table += '</tr>';
				table += '<tr>';
				table += '<th>PLANTA</th>';
				table += '<th>RESULTADO</th>';
				table += '</tr>';
				table += '</thead>';
				table += '<tbody>';

				for(var j=0; j<chart_data.length; j++){
					if(j>0){
						table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td></tr>';
					}
				}

				table += '</tbody>';
				table += '</table>';

				$('#table_div').html(table);
			}else{
				alert('No se ha encontrado información para la planta y periodo solicitado.');
			}
		}
	);
}


var graphingOnePlantBySection = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var section_id = $('#slc_section').val();
	var year = $('#slc_year').val();
	var str_plant = $('#slc_plant').val().split('-');
	var plant_id = str_plant[0];
	var plant_name = str_plant[1];
	var chart_name = '';
	
	// Showing the loading indicator
	$('#spinner').show();

	var chart_data = [];
	chart_data.push(['Pregunta', 'Puntaje', { 'role': 'annotation' }]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-questions-by-plant/'+plant_id+'/'+section_id+'/'+questionnaire_id+'/'+year,
			dataType: 'json',
			success: function(response){
				for(var i = 0; i<response.length; i++){
					question = response[i];
					row = [question.question, question.percentage, question.percentage];
					chart_data.push(row);

					chart_name = question.section_name;
				}

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			if(chart_data.length > 1){
				var data = new google.visualization.arrayToDataTable(chart_data, false);

				// Set chart options
				var options = {
					'title': chart_name,
					'width': 1200,
					'height': 750,
					'orientation': 'horizontal',
					'vAxis':{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
				};

				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
				chart.draw(data, options);

				// Printing the data in a table
				var table = '<table class="table">';
				table += '<thead>';
				table += '<tr>';
				table += '<th colspan="2">RESULTADO DE LA PLANTA '+plant_name.toUpperCase()+' EN LA SECCIÓN '+chart_name.toUpperCase()+' EN EL AÑO '+year+'</th>';
				table += '</tr>';
				table += '<tr>';
				table += '<th>PREGUNTA</th>';
				table += '<th>RESULTADO</th>';
				table += '</tr>';
				table += '</thead>';
				table += '<tbody>';

				for(var j=0; j<chart_data.length; j++){
					if(j>0){
						table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td></tr>';
					}
				}

				table += '</tbody>';
				table += '</table>';

				$('#table_div').html(table);
			}else{
				alert('No se ha encontrado información para la planta y periodo solicitado.');
			}
		}
	);
}


var graphingAllPlantsByQuestion = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var section_id = $('#slc_section').val();
	var question_id = $('#slc_question').val();
	var year = $('#slc_year').val();
	var chart_name = '';
	var plants_id = [];

	// Building the plants array	
	$('#slc_plant option').each(function(i){
		if(i > 0){
			plant = $(this).val().split('-');
			plants_id.push({"id": parseInt(plant[0]), "name":plant[1]});
		}
		
	});
	
	var params = '{"questionnaire_id": '+questionnaire_id+', "section_id": '+section_id+', "question_id": '+question_id+', "year": '+year+', "plants_id": '+JSON.stringify(plants_id)+'}';
	
	// Showing the loading indicator
	$('#spinner').show();

	var chart_data = [];
	chart_data.push(['Planta', 'Puntaje', { 'role': 'annotation' }]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-plants-by-question',
			method: 'POST',
			dataType: 'json',
			data: params,
			success: function(response){
				for(var i = 0; i<response.length; i++){
					plant = response[i];
					row = [plant.plant_name, plant.percentage, plant.percentage];
					chart_data.push(row);

					chart_name = plant.question;
				}

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			if(chart_data.length > 1){
				var data = new google.visualization.arrayToDataTable(chart_data, false);

				// Set chart options
				var options = {
					'title': chart_name,
					'width': 1200,
					'height': 750,
					'orientation': 'horizontal',
					'vAxis':{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
				};

				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
				chart.draw(data, options);

				// Printing the data in a table
				var table = '<table class="table">';
				table += '<thead>';
				table += '<tr>';
				table += '<th colspan="2">RESULTADO DE TODAS LAS PLANTAS EN LA PREGUNTA '+chart_name.toUpperCase()+' EN EL AÑO '+year+'</th>';
				table += '</tr>';
				table += '<tr>';
				table += '<th>PLANTA</th>';
				table += '<th>RESULTADO</th>';
				table += '</tr>';
				table += '</thead>';
				table += '<tbody>';

				for(var j=0; j<chart_data.length; j++){
					if(j>0){
						table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td></tr>';
					}
				}

				table += '</tbody>';
				table += '</table>';

				$('#table_div').html(table);
			}else{
				alert('No se ha encontrado información para la planta y periodo solicitado.');
			}
		}
	);
}


var graphingOnePlantByQuestion = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var section_id = $('#slc_section').val();
	var question_id = $('#slc_question').val();
	var year = $('#slc_year').val();
	var chart_name = '';
	var str_plant = $('#slc_plant').val().split('-');
	var plant_id = str_plant[0];
	var plant_name = str_plant[1];
	var bar_color = '';

	// Showing the loading indicator
	$('#spinner').show();

	var chart_data = [];
	chart_data.push(['Opción', 'Puntaje', {'role': 'annotation'}, {'role': 'style'}]);
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-one-plant-by-question/'+plant_id+'/'+questionnaire_id+'/'+question_id+'/'+year,
			dataType: 'json',
			success: function(response){
				for(var i = 0; i<response.length; i++){
					option = response[i];
					
					if(option.percentage == option.percentage_got){
						if(option.percentage_got < 100){
							bar_color = '#DF0101';
						}else{
							bar_color = '#298A08';
						}
					}else{
						bar_color = '#3668C9';
					}

					row = [option.question_option, option.percentage, option.percentage, bar_color];
					chart_data.push(row);

					chart_name = option.question;
				}

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			//if(chart_data.length > 1){
				var data = new google.visualization.arrayToDataTable(chart_data, false);

				// Set chart options
				var options = {
					'title': chart_name,
					'width': 1200,
					'height': 750,
					'orientation': 'horizontal',
					'vAxis':{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
				};

				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
				chart.draw(data, options);

				// Printing the data in a table
				var table = '<table class="table">';
				table += '<thead>';
				table += '<tr>';
				table += '<th colspan="2">RESULTADO DE LA PLANTA '+plant_name.toUpperCase()+' EN LA PREGUNTA '+chart_name.toUpperCase()+' EN EL AÑO '+year+'</th>';
				table += '</tr>';
				table += '<tr>';
				table += '<th>OPCIÓN</th>';
				table += '<th>RESULTADO</th>';
				table += '</tr>';
				table += '</thead>';
				table += '<tbody>';

				for(var j=0; j<chart_data.length; j++){
					if(j>0){
						if(chart_data[j][3] != '#3668C9'){
							table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td></tr>';	
						}
					}
				}

				table += '</tbody>';
				table += '</table>';

				$('#table_div').html(table);
			/*}else{
				alert('No se ha encontrado información para la planta y periodo solicitado.');
			}*/
		}
	);
}


var graphingAllPlantsComparative = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var year1 = $('#slc_year').val();
	var year2 = $('#slc_year_2').val();
	var plants_id = [];
	var plant = null;

	// Showing the loading indicator
	$('#spinner').show();

	// Building the plants array	
	$('#slc_plant option').each(function(i){
		if(i > 0){
			plant = $(this).val().split('-');
			plants_id.push({"id": parseInt(plant[0]), "name":plant[1]});
		}
		
	});

	var params = '{"questionnaire_id": '+questionnaire_id+', "year1": '+year1+', "year2": '+year2+', "plants_id": '+JSON.stringify(plants_id)+'}';
	
	var chart_data = [];
	chart_data.push(['Planta', year1, year2]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-plants-by-questionnnaire-comparative',
			method: 'POST',
			dataType: 'json',
			data: params,
			success: function(response){
				
				for(var i = 0; i<response.length; i++){
					plant = response[i];
					
					if(plant.reports != null){
						if(plant.reports.length > 1){
							period1 = plant.reports[0].total_score;
							period2 = plant.reports[1].total_score;
						}else{
							if(plant.reports[0].year == 2017){
								period1 = plant.reports[0].total_score;
								period2 = 0;
							}else{
								period1 = 0;
								period2 = plant.reports[0].total_score;
							}
						}
					}

					row = [plant.client_name, period1, period2];
					chart_data.push(row);
				}

				console.log(chart_data);

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			var data = new google.visualization.arrayToDataTable(chart_data, false);

			// Set chart options
			var options = {
				width: 1000,
				height: 1200,
				title: 'GEPP-PLANTAS-VER2017-01',
				subtitle: 'Comparativo de auditorías',
				bars: 'horizontal', // Required for Material Bar Charts.
				bar: {groupWidth: "95%"},
				series: {
					0: { axis: 'periodo1' }, // Bind series 0 to an axis named 'distance'.
		            1: { axis: 'periodo2' } // Bind series 1 to an axis named 'brightness'.
				},
				axes: {
					x: {
						periodo1: {label: 'Porcentaje'}, // Bottom x-axis.
						periodo2: {side: 'top', label: 'Porcentaje'} // Top x-axis.
					}
				},
				hAxis:{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
			};

			// Instantiate and draw our chart, passing in some options.
			var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
			chart.draw(data, options);

			// Printing the data in a table
				var table = '<table class="table">';
				table += '<thead>';
				table += '<tr>';
				table += '<th colspan="3">RESULTADO GLOBAL DE TODAS LAS PLANTAS EN LOS PERIODOS '+year1+' y '+year2+'</th>';
				table += '</tr>';
				table += '<tr>';
				table += '<th rowspan="2">PLANTA</th>';
				table += '<th colspan="2">RESULTADOS</th>';
				table += '</tr>';
				table += '<tr>';
				table += '<th>'+year1+'</th>';
				table += '<th>'+year2+'</th>';
				table += '</tr>';
				table += '</thead>';
				table += '<tbody>';

				for(var j=0; j<chart_data.length; j++){
					if(j>0){
						table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td><td>'+chart_data[j][2]+'</td></tr>';
					}
				}

				table += '</tbody>';
				table += '</table>';

				$('#table_div').html(table);
		}
	);
    
}


var graphingOnePlantComparative = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var year1 = $('#slc_year').val();
	var year2 = $('#slc_year_2').val();
	var str_plant = $('#slc_plant').val().split('-');
	var plant_id = str_plant[0];
	var plant_name = str_plant[1];

	// Showing the loading indicator
	$('#spinner').show();
	
	var chart_data = [];
	chart_data.push(['Sección', year1, year2]);
	var x = 0;
	var row = null;

	var apicall = 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-one-plant-by-questionnaire-comparative/'+plant_id+'/'+questionnaire_id+'/'+year1+'/'+year2;
	console.log(apicall);

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-one-plant-by-questionnaire-comparative/'+plant_id+'/'+questionnaire_id+'/'+year1+'/'+year2,
			dataType: 'json',
			success: function(response){

				for(var i=0; i<response.length; i++){
					section = response[i];
					row = [section.section_name, section.percentage1, section.percentage2];
					chart_data.push(row);
				}

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			var data = new google.visualization.arrayToDataTable(chart_data, false);

			// Set chart options
			var options = {
				width: 1000,
				height: 1200,
				title: 'GEPP-PLANTAS-VER2017-01',
				subtitle: 'Comparativo de auditorías',
				bars: 'horizontal', // Required for Material Bar Charts.
				bar: {groupWidth: "95%"},
				series: {
					0: { axis: 'periodo1' }, // Bind series 0 to an axis named 'distance'.
		            1: { axis: 'periodo2' } // Bind series 1 to an axis named 'brightness'.
				},
				axes: {
					x: {
						periodo1: {label: 'Porcentaje'}, // Bottom x-axis.
						periodo2: {side: 'top', label: 'Porcentaje'} // Top x-axis.
					}
				},
				hAxis:{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
			};

			// Instantiate and draw our chart, passing in some options.
			var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
			chart.draw(data, options);

			// Printing the data in a table
			var table = '<table class="table">';
			table += '<thead>';
			table += '<tr>';
			table += '<th colspan="3">RESULTADO GLOBAL DE LA PLANTA '+plant_name.toUpperCase()+' EN LOS PERIODOS '+year1+' Y '+year2+'</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th rowspan="2">SECCIÓN</th>';
			table += '<th colspan="2">RESULTADOS</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th>'+year1+'</th>';
			table += '<th>'+year2+'</th>';
			table += '</tr>';
			table += '</thead>';
			table += '<tbody>';

			for(var j=0; j<chart_data.length; j++){
				if(j>0){
					table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td><td>'+chart_data[j][2]+'</td></tr>';
				}
			}

			table += '</tbody>';
			table += '</table>';

			$('#table_div').html(table);
		}
	);
}


var graphingAllPlantsBySectionComparative = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var section_id = $('#slc_section').val();
	var year = $('#slc_year').val();
	var year2 = $('#slc_year_2').val();
	var plants_id = [];
	var plant = null;
	var graph_name = null;

	// Showing the loading indicator
	$('#spinner').show();

	// Building the plants array	
	$('#slc_plant option').each(function(i){
		if(i > 0){
			plant = $(this).val().split('-');
			plants_id.push({"id": parseInt(plant[0]), "name":plant[1]});
		}
	});

	var params = '{"questionnaire_id": '+questionnaire_id+', "section_id": '+section_id+', "year": '+year+', "year2": '+year2+', "plants_id": '+JSON.stringify(plants_id)+'}';
	
	var chart_data = [];
	chart_data.push(['Planta', year, year2]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-plants-by-section-comparative',
			method: 'POST',
			dataType: 'json',
			data: params,
			success: function(response){

				for(var i=0; i<response.length; i++){
					plant = response[i];
					row = [plant.client_name, plant.percentage1, plant.percentage2];
					chart_data.push(row);

					graph_name = plant.section_name;
				}

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			console.log(chart_data);
			var data = new google.visualization.arrayToDataTable(chart_data, false);

			// Set chart options
			var options = {
				width: 1000,
				height: 1200,
				title: graph_name,
				subtitle: 'Comparativo de auditorías',
				bars: 'horizontal', // Required for Material Bar Charts.
				bar: {groupWidth: "95%"},
				series: {
					0: { axis: 'periodo1' }, // Bind series 0 to an axis named 'distance'.
		            1: { axis: 'periodo2' } // Bind series 1 to an axis named 'brightness'.
				},
				axes: {
					x: {
						periodo1: {label: 'Porcentaje'}, // Bottom x-axis.
						periodo2: {side: 'top', label: 'Porcentaje'} // Top x-axis.
					}
				},
				hAxis:{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
			};

			// Instantiate and draw our chart, passing in some options.
			var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
			chart.draw(data, options);

			// Printing the data in a table
			var table = '<table class="table">';
			table += '<thead>';
			table += '<tr>';
			table += '<th colspan="3">RESULTADO DE TODAS LAS PLANTAS EN LA SECCIÓN '+graph_name.toUpperCase()+' EN LOS PERIODOS '+year+' Y '+year2+'</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th rowspan="2">PLANTA</th>';
			table += '<th colspan="2">RESULTADOS</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th>'+year+'</th>';
			table += '<th>'+year2+'</th>';
			table += '</tr>';
			table += '</thead>';
			table += '<tbody>';

			for(var j=0; j<chart_data.length; j++){
				if(j>0){
					table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td><td>'+chart_data[j][2]+'</td></tr>';
				}
			}

			table += '</tbody>';
			table += '</table>';

			$('#table_div').html(table);
		}
	);
}

var graphingOnePlantBySectionComparative = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var section_id = $('#slc_section').val();
	var year = $('#slc_year').val();
	var year2 = $('#slc_year_2').val();
	var str_plant = $('#slc_plant').val().split('-');
	var plant_id = str_plant[0];
	var plant_name = str_plant[1];
	var question = null;
	var graph_name = null;

	// Showing the loading indicator
	$('#spinner').show();
	
	var chart_data = [];
	chart_data.push(['Pregunta', year, year2]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-questions-by-plant-comparative/'+plant_id+'/'+section_id+'/'+questionnaire_id+'/'+year+'/'+year2,
			dataType: 'json',
			success: function(response){

				for(var i=0; i<response.length; i++){
					question = response[i];
					row = [question.question, question.percentage1, question.percentage2];
					chart_data.push(row);
				}

				graph_name = response[0].section_name;

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			console.log(chart_data);
			var data = new google.visualization.arrayToDataTable(chart_data, false);

			// Set chart options
			var options = {
				width: 1000,
				height: 1200,
				title: graph_name,
				subtitle: 'Comparativo de auditorías',
				bars: 'horizontal', // Required for Material Bar Charts.
				bar: {groupWidth: "95%"},
				series: {
					0: { axis: 'periodo1' }, // Bind series 0 to an axis named 'distance'.
		            1: { axis: 'periodo2' } // Bind series 1 to an axis named 'brightness'.
				},
				axes: {
					x: {
						periodo1: {label: 'Porcentaje'}, // Bottom x-axis.
						periodo2: {side: 'top', label: 'Porcentaje'} // Top x-axis.
					}
				},
				hAxis:{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
			};

			// Instantiate and draw our chart, passing in some options.
			var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
			chart.draw(data, options);

			// Printing the data in a table
			var table = '<table class="table">';
			table += '<thead>';
			table += '<tr>';
			table += '<th colspan="3">RESULTADO DE LA PLANTA '+plant_name.toUpperCase()+' EN LA SECCIÓN '+graph_name.toUpperCase()+' EN LOS PERIODOS '+year+' Y '+year2+'</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th rowspan="2">PREGUNTA</th>';
			table += '<th colspan="2">RESULTADOS</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th>'+year+'</th>';
			table += '<th>'+year2+'</th>';
			table += '</tr>';
			table += '</thead>';
			table += '<tbody>';

			for(var j=0; j<chart_data.length; j++){
				if(j>0){
					table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td><td>'+chart_data[j][2]+'</td></tr>';
				}
			}

			table += '</tbody>';
			table += '</table>';

			$('#table_div').html(table);
		}
	);
}


var graphingAllPlantsByQuestionComparative = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var section_id = $('#slc_section').val();
	var question_id = $('#slc_question').val();
	var year = $('#slc_year').val();
	var year2 = $('#slc_year_2').val();
	var plants_id = [];
	var plant = null;
	var graph_name = null;

	// Showing the loading indicator
	$('#spinner').show();

	// Building the plants array	
	$('#slc_plant option').each(function(i){
		if(i > 0){
			plant = $(this).val().split('-');
			plants_id.push({"id": parseInt(plant[0]), "name":plant[1]});
		}
	});

	var params = '{"questionnaire_id": '+questionnaire_id+', "section_id": '+section_id+', "question_id": '+question_id+', "year": '+year+', "year2": '+year2+', "plants_id": '+JSON.stringify(plants_id)+'}';
	
	var chart_data = [];
	chart_data.push(['Planta', year, year2]);
	var x = 0;
	var row = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-all-plants-by-question-comparative',
			method: 'POST',
			dataType: 'json',
			data: params,
			success: function(response){

				for(var i=0; i<response.length; i++){
					plant = response[i];
					row = [plant.plant_name, plant.percentage1, plant.percentage2];
					chart_data.push(row);

					graph_name = plant.question;
				}

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			console.log(chart_data);
			var data = new google.visualization.arrayToDataTable(chart_data, false);

			// Set chart options
			var options = {
				width: 1000,
				height: 1200,
				title: graph_name,
				subtitle: 'Comparativo de auditorías',
				bars: 'horizontal', // Required for Material Bar Charts.
				bar: {groupWidth: "95%"},
				series: {
					0: { axis: 'periodo1' }, // Bind series 0 to an axis named 'distance'.
		            1: { axis: 'periodo2' } // Bind series 1 to an axis named 'brightness'.
				},
				axes: {
					x: {
						periodo1: {label: 'Porcentaje'}, // Bottom x-axis.
						periodo2: {side: 'top', label: 'Porcentaje'} // Top x-axis.
					}
				},
				hAxis:{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
			};

			// Instantiate and draw our chart, passing in some options.
			var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
			chart.draw(data, options);

			// Printing the data in a table
			var table = '<table class="table">';
			table += '<thead>';
			table += '<tr>';
			table += '<th colspan="3">RESULTADO DE TODAS LAS PLANTAS EN LA PREGUNTA '+graph_name.toUpperCase()+' EN LOS PERIODOS '+year+' Y '+year2+'</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th rowspan="2">PLANTA</th>';
			table += '<th colspan="2">RESULTADOS</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th>'+year+'</th>';
			table += '<th>'+year2+'</th>';
			table += '</tr>';
			table += '</thead>';
			table += '<tbody>';

			for(var j=0; j<chart_data.length; j++){
				if(j>0){
					table += '<tr><td>'+chart_data[j][0]+'</td><td>'+chart_data[j][1]+'</td><td>'+chart_data[j][2]+'</td></tr>';
				}
			}

			table += '</tbody>';
			table += '</table>';

			$('#table_div').html(table);
		}
	);
}


var graphingOnePlantByQuestionComparative = function(){
	var questionnaire_id = $('#slc_questionnaire').val();
	var question_id = $('#slc_question').val();
	var year = $('#slc_year').val();
	var year2 = $('#slc_year_2').val();
	var str_plant = $('#slc_plant').val().split('-');
	var plant_id = str_plant[0];
	var plant_name = str_plant[1];
	var option = null;
	var graph_name = null;

	// Showing the loading indicator
	$('#spinner').show();
	
	var chart_data = [];
	chart_data.push(['Periodo', 'Porcentaje']);
	var x = 0;
	var row = null;
	var table_data = null;

	$.when(
		$.ajax({
			url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-one-plant-by-question-comparative/'+plant_id+'/'+questionnaire_id+'/'+question_id+'/'+year+'/'+year2,
			dataType: 'json',
			success: function(response){

				row = [year, response[0].percentage_got_1];
				chart_data.push(row);
				row = [year2, response[0].percentage_got_2];
				chart_data.push(row);


				table_data = {"question":response[0].question, "percentage1":response[0].percentage_got_1, "percentage2":response[0].percentage_got_2};

				graph_name = response[0].question;

				$('#spinner').hide();
				$('#btn_find').prop('disabled', true);
				$('#btn_new_find').prop('disabled', false);
			},
			error: function(error){
				console.log(error);
			}
		})
	).then(
		function(){
			console.log(chart_data);
			var data = new google.visualization.arrayToDataTable(chart_data, false);

			// Set chart options
			var options = {
				width: 1000,
				height: 1200,
				title: graph_name,
				subtitle: 'Comparativo de auditorías',
				bars: 'horizontal', // Required for Material Bar Charts.
				bar: {groupWidth: "95%"},
				series: {
					0: { axis: 'periodo1' }, // Bind series 0 to an axis named 'distance'.
		            1: { axis: 'periodo2' } // Bind series 1 to an axis named 'brightness'.
				},
				axes: {
					x: {
						periodo1: {label: 'Porcentaje'}, // Bottom x-axis.
						periodo2: {side: 'top', label: 'Porcentaje'} // Top x-axis.
					}
				},
				hAxis:{'ticks': [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]}
			};

			// Instantiate and draw our chart, passing in some options.
			var chart = new google.visualization.BarChart(document.getElementById('chart_div'));
			chart.draw(data, options);

			// Printing the data in a table
			var table = '<table class="table">';
			table += '<thead>';
			table += '<tr>';
			table += '<th colspan="3">RESULTADO DE LA PLANTA '+plant_name.toUpperCase()+' EN LA PREGUNTA '+graph_name.toUpperCase()+' EN LOS PERIODOS '+year+' Y '+year2+'</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th rowspan="2">PREGUNTA</th>';
			table += '<th colspan="2">RESULTADOS</th>';
			table += '</tr>';
			table += '<tr>';
			table += '<th>'+year+'</th>';
			table += '<th>'+year2+'</th>';
			table += '</tr>';
			table += '</thead>';
			table += '<tbody>';

			//for(var j=0; j<chart_data.length; j++){
				//if(j>0){
					table += '<tr><td>'+table_data.question+'</td><td>'+table_data.percentage1+'</td><td>'+table_data.percentage2+'</td></tr>';
				//}
			//}

			table += '</tbody>';
			table += '</table>';

			$('#table_div').html(table);
		}
	);
}

var getSections = function(){
	var questionnaire_id = $('#slc_questionnaire').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-sections/'+questionnaire_id,
		dataType: 'json',
		success: function(response){
			var options = '<option value="0">TODAS</option>';
			var section = null;

			for(var i=0; i<response.length; i++){
				section = response[i];

				options += '<option value="'+section.id+'">'+section.number+'.- '+section.name+'</option>';
			}

			$('#slc_section').html(options);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var getQuestions = function(){
	var section_id = $('#slc_section').val();

	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/dashboards/get-questions/'+section_id,
		dataType: 'json',
		success: function(response){
			var options = '<option value="0">TODAS</option>';
			var question = null;

			for(var i=0; i<response.length; i++){
				question = response[i];

				options += '<option value="'+question.id+'">'+question.number+'.- '+question.question+'</option>';
			}

			$('#slc_question').html(options);
		},
		error: function(error){
			console.log('Ha ocurrido un error: ' + error);
		}
	});
}


var manageButtons = function(){
	$('#btn_new_find').prop('disabled', true);
	$('#btn_find').prop('disabled', false);
	$('#chart_div').empty();
	$('#table_div').empty();
}

// SHC
var exportToPDF = function(){

	if($('#chart_div').html().length == 0){
		alert('Sin contenido para exportar.');
		return;
	}

	var pdfContent = document.getElementById('pdf-content');

	var html = '<!DOCTYPE HTML>';
	html += '<html>';
	html += '<head>	<title>Dashboard</title>';
	html += '<link href="css/bootstrap.css" rel="stylesheet">';
	html += '</head>';
	html += '<body>';
	html += pdfContent.outerHTML;
	html += '<script> setTimeout( function(){ window.print(); },1000); </script>';
	html += '</body></html>';
	
	var w = window.open('','_blank');
	w.document.write(html);
}


var closeExpiredReports = function(){
	$.ajax({
		url: 'https://dev.bluehand.com.mx/backend/api/v1/reports/close-expired-reports',
		dataType: 'json',
		success: function(response){
			console.log(response.message);
		},
		error: function(){
			console.log(response.message);
		}
	});
}