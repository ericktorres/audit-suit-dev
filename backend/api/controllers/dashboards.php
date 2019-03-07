<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


// This controller gets all the plants available for the logged user
$app->get('/v1/dashboards/get-plants/{email}', function($email) use ($app){
	// Getting the privileges and user id
	$sql_user_data = $app['db']->createQueryBuilder();
	$sql_user_data
		->select('u.id_usuario AS id, id_privilegios AS privileges')
		->from('usuarios', 'u')
		->where('u.correo_electronico = ?');
	$stmt = $app['db']->prepare($sql_user_data);
	$stmt->bindValue(1, $email);
	$stmt->execute();
	$user_data = $stmt->fetch();
	$user_privileges = $user_data['privileges'];
	$user_id = $user_data['id'];

	if($user_privileges == 3){ // For auditors
		// Getting the plants
		$sql_plants = $app['db']->createQueryBuilder();
		$sql_plants
			->select('rae.id_empresa AS client_id, e.nombre_comercial AS name, e.razon_social AS business_name')
			->from('r_auditores_empresas', 'rae')
			->leftJoin('rae', 'empresas', 'e', 'e.id_empresa = rae.id_empresa')
			->where('rae.id_auditor = ?');
	}else if($user_privileges == 2){ // For managers
		// Getting the plants
		$sql_plants = $app['db']->createQueryBuilder();
		$sql_plants
			->select('rge.id_empresa AS client_id, e.nombre_comercial AS name, e.razon_social AS business_name')
			->from('r_gerentes_empresas', 'rge')
			->leftJoin('rge', 'empresas', 'e', 'e.id_empresa = rge.id_empresa')
			->where('rge.id_gerente = ?');
	}else if($user_privileges == 1){ // For administrators
		$user_id = 2;
		// Getting the plants
		$sql_plants = $app['db']->createQueryBuilder();
		$sql_plants
			->select('e.id_empresa AS client_id, e.nombre_comercial AS name, e.razon_social AS business_name')
			->from('empresas', 'e')
			->where('e.id_tipo_empresa = ?');
	}
		
	$stmt = $app['db']->prepare($sql_plants);
	$stmt->bindValue(1, $user_id);
	$stmt->execute();
	$plants = $stmt->fetchAll();

	return $app->json($plants)->setEncodingOptions(JSON_NUMERIC_CHECK);

});


// This controller gets all the sections of the selected questionnaire
$app->get('/v1/dashboards/get-sections/{questionnaire_id}', function($questionnaire_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('sc.id_seccion AS id, sc.num_seccion AS number, sc.nombre_seccion AS name')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?')
		->orderBy('sc.num_seccion', 'ASC');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	return $app->json($sections)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controllers gets all the questions of the selected section
$app->get('/v1/dashboards/get-questions/{section_id}', function($section_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('p.id_pregunta AS id, p.num_pregunta AS number, p.pregunta AS question')
		->from('preguntas', 'p')
		->where('p.id_seccion = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $section_id);
	$stmt->execute();
	$questions = $stmt->fetchAll();

	return $app->json($questions)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller return the JSON formated for the chart of all plants by one questionnaire
$app->post('/v1/dashboards/get-all-plants-by-questionnnaire-formatted', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$plants_id = $request->request->get('plants_id');
	$year = 2017;

	// Getting the sections data
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();
	$sections_results = array();
	$report_data = array();
	$result_formatted = array();
	$total_got_value = 0;
	$max_score = 0;
	$z = 0;

	//$result_formatted[0] = array("Planta", "Puntaje", "{'role': 'annotation'}");

	for($i=0; $i<count($plants_id); $i++){
		// Getting the questionnaires answered ids (Report ID) from each plant
		$sql_report_id = "SELECT id_cuestionario_respondido AS report_id FROM cuestionarios_respondidos WHERE id_cliente = ? AND YEAR(fecha_auditoria) = ?";
		$stmt = $app['db']->prepare($sql_report_id);
		$stmt->bindValue(1, $plants_id[$i]['id']);
		$stmt->bindValue(2, $year);
		$stmt->execute();
		$reports_id[$i] = $stmt->fetchAll();

		for($k = 0; $k<count($reports_id[$i]); $k++){
			// Getting the disccount points from the total
			$sql = $app['db']->createQueryBuilder();
			$sql
				->select('SUM(rc.puntaje) AS discount_points')
				->from('respuestas_cuestionarios', 'rc')
				->where('id_cuestionario_respondido = ?')
				->andWhere('no_aplica = 1');
			$stmt = $app['db']->prepare($sql);
			$stmt->bindValue(1, $reports_id[$i][$k]['report_id']);
			$stmt->execute();
			$discount_points[$k] = $stmt->fetch();

			for($j=0; $j<count($sections); $j++){
				$sql_percentage = $app['db']->createQueryBuilder();
				$sql_percentage = "SELECT TRUNCATE
								(
									(
										SUM(rc.puntaje) * 100 / 
										(
											sc.valor - 
											IFNULL((
												SELECT SUM(p.ponderacion) 
												FROM preguntas AS p 
												LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta 
												WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$i][$k]['report_id']."
											), 0)
										)
									), 2
								) AS section_percentage, TRUNCATE(SUM(rc.puntaje), 2) AS got_value FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$i][$k]['report_id']." AND rc.no_aplica = 0";
				$stmt = $app['db']->prepare($sql_percentage);
				$stmt->execute();
				$percentage_got = $stmt->fetch();

				$sections_results[$j]['section_name'] = $sections[$j]['section_name'];
				$sections_results[$j]['section_value'] = $sections[$j]['value'];
				$sections_results[$j]['got_value'] = $percentage_got['got_value'];
				$sections_results[$j]['section_percentage'] = $percentage_got['section_percentage'];
				$total_got_value += $percentage_got['got_value'];
				$max_score += $sections[$j]['value'];


				
			}

			$result_formatted[$z] = [$plants_id[$i]['name'], round(($total_got_value * 100) / ($max_score - $discount_points[$k]['discount_points']), 2), $reports_id[$i][$k]['report_id']];

			$z++;
		}
	}

	return $app->json($result_formatted);
});


// This controller gets the result of one questionnaire for all the plants available
$app->post('/v1/dashboards/get-all-plants-by-questionnnaire', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$plants_id = $request->request->get('plants_id');
	$year = $request->request->get('year');

	// Getting the sections data
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();
	$sections_results = array();
	$report_data = array();

	for($i=0; $i<count($plants_id); $i++){
		// Getting the questionnaires answered ids (Report ID) from each plant
		$sql_report_id = "SELECT id_cuestionario_respondido AS report_id FROM cuestionarios_respondidos WHERE id_cliente = ? AND YEAR(fecha_auditoria) = ?";
		$stmt = $app['db']->prepare($sql_report_id);
		$stmt->bindValue(1, $plants_id[$i]['id']);
		$stmt->bindValue(2, $year);
		$stmt->execute();
		$reports_id = $stmt->fetchAll();

		for($k = 0; $k<count($reports_id); $k++){
			// Getting the disccount points from the total
			$sql = $app['db']->createQueryBuilder();
			$sql
				->select('SUM(rc.puntaje) AS discount_points')
				->from('respuestas_cuestionarios', 'rc')
				->where('id_cuestionario_respondido = ?')
				->andWhere('no_aplica = 1');
			$stmt = $app['db']->prepare($sql);
			$stmt->bindValue(1, $reports_id[$k]['report_id']);
			$stmt->execute();
			$discount_points = $stmt->fetch();

			$total_got_value = 0;
			$max_score = 0;

			for($j=0; $j<count($sections); $j++){
				$sql_percentage = $app['db']->createQueryBuilder();
				$sql_percentage = "SELECT TRUNCATE
								(
									(
										SUM(rc.puntaje) * 100 / 
										(
											sc.valor - 
											IFNULL((
												SELECT SUM(p.ponderacion) 
												FROM preguntas AS p 
												LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta 
												WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$k]['report_id']."
											), 0)
										)
									), 2
								) AS section_percentage, TRUNCATE(SUM(rc.puntaje), 2) AS got_value FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$k]['report_id']." AND rc.no_aplica = 0";
				$stmt = $app['db']->prepare($sql_percentage);
				$stmt->execute();
				$percentage_got = $stmt->fetch();

				$sections_results[$j]['section_name'] = $sections[$j]['section_name'];
				$sections_results[$j]['section_value'] = $sections[$j]['value'];
				$sections_results[$j]['got_value'] = $percentage_got['got_value'];
				$sections_results[$j]['section_percentage'] = $percentage_got['section_percentage'];
				$total_got_value += $percentage_got['got_value'];
				$max_score += $sections[$j]['value'];
				
			}
			$report_data[$i][$k]['report_id'] = $reports_id[$k]['report_id'];
			$report_data[$i][$k]['discount_points'] = $discount_points['discount_points'];
			$report_data[$i][$k]['total_score'] = round(($total_got_value * 100) / ($max_score - $discount_points['discount_points']), 2);
			$report_data[$i][$k]['sections'] = $sections_results;
		}

		$result[$i]['client_id'] = $plants_id[$i]['id'];
		$result[$i]['client_name'] = $plants_id[$i]['name'];
		$result[$i]['reports'] = $report_data[$i];
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the results of all the sections of the questionnaire for the selected plant
$app->get('/v1/dashboards/get-one-plant-by-questionnaire/{plant_id}/{questionnaire_id}/{year}', function($plant_id, $questionnaire_id, $year) use ($app){
	$result = array();

	// Getting the reports id available for the plant
	$sql_reports_id = $app['db']->createQueryBuilder();
	$sql_reports_id
		->select('cr.id_cuestionario_respondido AS id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cliente = ?')
		->andWhere('cr.id_cuestionario = ?')
		->andWhere('YEAR(cr.fecha_auditoria) = ?');
	$stmt = $app['db']->prepare($sql_reports_id);
	$stmt->bindValue(1, $plant_id);
	$stmt->bindValue(2, $questionnaire_id);
	$stmt->bindValue(3, $year);
	$stmt->execute();
	$reports = $stmt->fetchAll();

	// Getting sections name
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	for($i=0; $i<count($reports); $i++){
		$report = $reports[$i];

		for($j=0; $j<count($sections); $j++){
			$section = $sections[$j];

			$sql_percentage = "SELECT TRUNCATE
							(
								(
									SUM(rc.puntaje) * 100 / 
									(
										sc.valor - 
										IFNULL((
											SELECT SUM(p.ponderacion) 
											FROM preguntas AS p 
											LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta 
											WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$report['id']."
										), 0)
									)
								), 2
							) AS section_percentage, TRUNCATE(SUM(rc.puntaje), 2) AS got_value FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$report['id']." AND rc.no_aplica = 0";
			$stmt = $app['db']->prepare($sql_percentage);
			$stmt->execute();
			$percentage_got = $stmt->fetch();

			$result[$j]['section_name'] = $section['section_name'];
			$result[$j]['percentage'] = $percentage_got['section_percentage'];
			$result[$j]['report_id'] = $report['id'];
		}
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result of one section of a questionnaire for all plants
$app->post('/v1/dashboards/get-all-plants-by-section', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$section_id = $request->request->get('section_id');
	$plants_id = $request->request->get('plants_id');
	$year = $request->request->get('year');

	// Getting the section data
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?')
		->andWhere('sc.id_seccion = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->bindValue(2, $section_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();
	$sections_results = array();
	$report_data = array();
	$total_got_value = 0;
	$max_score = 0;
	$x = 0;

	for($i=0; $i<count($plants_id); $i++){
		// Getting the questionnaires answered ids (Report ID) from each plant
		$sql_report_id = "SELECT id_cuestionario_respondido AS report_id FROM cuestionarios_respondidos WHERE id_cliente = ? AND YEAR(fecha_auditoria) = ?";
		$stmt = $app['db']->prepare($sql_report_id);
		$stmt->bindValue(1, $plants_id[$i]['id']);
		$stmt->bindValue(2, $year);
		$stmt->execute();
		$reports_id = $stmt->fetchAll();

		for($k = 0; $k<count($reports_id); $k++){
			// Getting the disccount points from the total
			$sql = $app['db']->createQueryBuilder();
			$sql
				->select('SUM(rc.puntaje) AS discount_points')
				->from('respuestas_cuestionarios', 'rc')
				->where('id_cuestionario_respondido = ?')
				->andWhere('no_aplica = 1');
			$stmt = $app['db']->prepare($sql);
			$stmt->bindValue(1, $reports_id[$k]['report_id']);
			$stmt->execute();
			$discount_points = $stmt->fetch();

			for($j=0; $j<count($sections); $j++){
				$sql_percentage = $app['db']->createQueryBuilder();
				$sql_percentage = "SELECT TRUNCATE
								(
									(
										SUM(rc.puntaje) * 100 / 
										(
											sc.valor - 
											IFNULL((
												SELECT SUM(p.ponderacion) 
												FROM preguntas AS p 
												LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta 
												WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$k]['report_id']."
											), 0)
										)
									), 2
								) AS section_percentage, TRUNCATE(SUM(rc.puntaje), 2) AS got_value FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$k]['report_id']." AND rc.no_aplica = 0";
				$stmt = $app['db']->prepare($sql_percentage);
				$stmt->execute();
				$percentage_got = $stmt->fetch();

				$result[$x]['client_id'] = $plants_id[$i]['id'];
				$result[$x]['client_name'] = $plants_id[$i]['name'];
				$result[$x]['section_name'] = $sections[$j]['section_name'];
				$result[$x]['section_percentage'] = $percentage_got['section_percentage'];
				$x++;
			}
		}

		
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result of all questions of a section by one plant
$app->get('/v1/dashboards/get-all-questions-by-plant/{plant_id}/{section_id}/{questionnaire_id}/{year}', function($plant_id, $section_id, $questionnaire_id, $year) use ($app){
	// Getting the report for the selected plant
	$sql_plant = $app['db']->createQueryBuilder();
	$sql_plant
		->select('cr.id_cuestionario_respondido AS id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cliente = ?')
		->andWhere('YEAR(cr.fecha_auditoria) = ?');
	$stmt = $app['db']->prepare($sql_plant);
	$stmt->bindValue(1, $plant_id);
	$stmt->bindValue(2, $year);
	$stmt->execute();
	$report_id = $stmt->fetch();

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('sc.nombre_seccion AS section_name, rc.id_pregunta AS id, p.num_pregunta AS number, p.pregunta AS question, rc.puntaje AS score, rc.valor AS percentage')
		->from('respuestas_cuestionarios', 'rc')
		->leftJoin('rc', 'preguntas', 'p', 'p.id_pregunta = rc.id_pregunta')
		->leftJoin('rc', 'secciones_cuestionario', 'sc', 'sc.id_seccion = rc.id_seccion')
		->where('rc.id_seccion = ?')
		->andWhere('rc.id_cuestionario = ?')
		->andWhere('rc.id_cuestionario_respondido = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $section_id);
	$stmt->bindValue(2, $questionnaire_id);
	$stmt->bindValue(3, $report_id['id']);
	$stmt->execute();
	$questions = $stmt->fetchAll();

	//$response = $app->json($questions)->setEncodingOptions(JSON_NUMERIC_CHECK);
	/*return new Response($response, 200, array(
        'Cache-Control' => 's-maxage=600',
    ));*/
    return $app->json($questions)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the results of all plants by one question
$app->post('/v1/dashboards/get-all-plants-by-question', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$section_id = $request->request->get('section_id');
	$question_id = $request->request->get('question_id');
	$plants_id = $request->request->get('plants_id');
	$year = $request->request->get('year');
	$result = array();
	$j = 0;

	for($i=0; $i<count($plants_id); $i++){
		// Getting the questionnaires answered id (Report ID) from each plant
		$sql_report_id = "SELECT id_cuestionario_respondido AS id FROM cuestionarios_respondidos WHERE id_cliente = ? AND YEAR(fecha_auditoria) = ?";
		$stmt = $app['db']->prepare($sql_report_id);
		$stmt->bindValue(1, $plants_id[$i]['id']);
		$stmt->bindValue(2, $year);
		$stmt->execute();
		$report_id = $stmt->fetch();

		$sql_question_result = $app['db']->createQueryBuilder();
		$sql_question_result
			->select('p.pregunta AS question, rp.valor AS percentage')
			->from('respuestas_cuestionarios', 'rp')
			->leftJoin('rp', 'preguntas', 'p', 'p.id_pregunta = rp.id_pregunta')
			->leftJoin('rp', 'cuestionarios_respondidos', 'cr', 'cr.id_cuestionario_respondido = rp.id_cuestionario_respondido')
			->where('rp.id_cuestionario_respondido = ?')
			->andWhere('rp.id_pregunta = ?')
			->andWhere('cr.id_cliente = ?');
		$stmt = $app['db']->prepare($sql_question_result);
		$stmt->bindValue(1, $report_id['id']);
		$stmt->bindValue(2, $question_id);
		$stmt->bindValue(3, $plants_id[$i]['id']);
		$stmt->execute();
		$question_result = $stmt->fetch();

		if($question_result != false){
			$result[$j]['plant_id'] = $plants_id[$i]['id'];
			$result[$j]['plant_name'] = $plants_id[$i]['name'];
			$result[$j]['question'] = $question_result['question'];
			$result[$j]['percentage'] = $question_result['percentage'];
			$j++;
		}
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result of one plant by one question
$app->get('/v1/dashboards/get-one-plant-by-question/{plant_id}/{questionnaire_id}/{question_id}/{year}', function($plant_id, $questionnaire_id, $question_id, $year) use ($app){
	// Getting the report id of the plant
	$sql_report_id = $app['db']->createQueryBuilder();
	$sql_report_id
		->select('cr.id_cuestionario_respondido AS id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cliente = ?')
		->andWhere('YEAR(cr.fecha_auditoria) = ?');
	$stmt = $app['db']->prepare($sql_report_id);
	$stmt->bindValue(1, $plant_id);
	$stmt->bindValue(2, $year);
	$stmt->execute();
	$report_id = $stmt->fetch();

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('p.pregunta AS question, rop.id_opcion AS id, rop.opcion AS question_option, rop.valor AS percentage, rc.valor AS percentage_got')
		->from('r_opciones_preguntas', 'rop')
		->leftJoin('rop', 'respuestas_cuestionarios', 'rc', 'rc.id_pregunta = rop.id_pregunta')
		->leftJoin('rc', 'cuestionarios_respondidos', 'cr', 'cr.id_cuestionario_respondido = rc.id_cuestionario_respondido')
		->leftJoin('rop', 'preguntas', 'p', 'p.id_pregunta = rop.id_pregunta')
		->where('rop.id_pregunta = ?')
		->andWhere('rc.id_cuestionario_respondido = ?')
		->andWhere('cr.id_cliente = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $question_id);
	$stmt->bindValue(2, $report_id['id']);
	$stmt->bindValue(3, $plant_id);
	$stmt->execute();
	$question_options = $stmt->fetchAll();

	return $app->json($question_options)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result of the selected plants by one questionnaire in comparative mode
$app->post('/v1/dashboards/get-all-plants-by-questionnnaire-comparative', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$plants_id = $request->request->get('plants_id');
	$year1 = $request->request->get('year1');
	$year2 = $request->request->get('year2');

	// Getting the sections data
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();
	$sections_results = array();
	$report_data = array();

	for($i=0; $i<count($plants_id); $i++){
		// Getting the questionnaires answered ids (Report ID) from each plant
		$sql_report_id = "SELECT id_cuestionario_respondido AS report_id, YEAR(fecha_auditoria) AS year FROM cuestionarios_respondidos WHERE id_cliente = ? AND YEAR(fecha_auditoria) IN (?, ?)";
		$stmt = $app['db']->prepare($sql_report_id);
		$stmt->bindValue(1, $plants_id[$i]['id']);
		$stmt->bindValue(2, $year1);
		$stmt->bindValue(3, $year2);
		$stmt->execute();
		$reports_id = $stmt->fetchAll();

		for($k = 0; $k<count($reports_id); $k++){
			// Getting the disccount points from the total
			$sql = $app['db']->createQueryBuilder();
			$sql
				->select('SUM(rc.puntaje) AS discount_points')
				->from('respuestas_cuestionarios', 'rc')
				->where('id_cuestionario_respondido = ?')
				->andWhere('no_aplica = 1');
			$stmt = $app['db']->prepare($sql);
			$stmt->bindValue(1, $reports_id[$k]['report_id']);
			$stmt->execute();
			$discount_points = $stmt->fetch();

			$total_got_value = 0;
			$max_score = 0;

			for($j=0; $j<count($sections); $j++){
				$sql_percentage = $app['db']->createQueryBuilder();
				$sql_percentage = "SELECT TRUNCATE
								(
									(
										SUM(rc.puntaje) * 100 / 
										(
											sc.valor - 
											IFNULL((
												SELECT SUM(p.ponderacion) 
												FROM preguntas AS p 
												LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta 
												WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$k]['report_id']."
											), 0)
										)
									), 2
								) AS section_percentage, TRUNCATE(SUM(rc.puntaje), 2) AS got_value FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$reports_id[$k]['report_id']." AND rc.no_aplica = 0";
				$stmt = $app['db']->prepare($sql_percentage);
				$stmt->execute();
				$percentage_got = $stmt->fetch();

				$sections_results[$j]['section_name'] = $sections[$j]['section_name'];
				$sections_results[$j]['section_value'] = $sections[$j]['value'];
				$sections_results[$j]['got_value'] = $percentage_got['got_value'];
				$sections_results[$j]['section_percentage'] = $percentage_got['section_percentage'];
				$total_got_value += $percentage_got['got_value'];
				$max_score += $sections[$j]['value'];
				
			}
			$report_data[$i][$k]['report_id'] = $reports_id[$k]['report_id'];
			$report_data[$i][$k]['year'] = $reports_id[$k]['year'];
			$report_data[$i][$k]['total_score'] = round(($total_got_value * 100) / ($max_score - $discount_points['discount_points']), 2);
		}

		$result[$i]['client_id'] = $plants_id[$i]['id'];
		$result[$i]['client_name'] = $plants_id[$i]['name'];
		$result[$i]['reports'] = $report_data[$i];
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result for one plant by one questionnaire in comparative mode
$app->get('/v1/dashboards/get-one-plant-by-questionnaire-comparative/{plant_id}/{questionnaire_id}/{year}/{year2}', function($plant_id, $questionnaire_id, $year, $year2) use ($app){
	$result = array();

	// Getting the reports id available for the plant
	$sql_reports_id = $app['db']->createQueryBuilder();
	$sql_reports_id
		->select('cr.id_cuestionario_respondido AS id, YEAR(cr.fecha_auditoria) AS year')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cliente = ?')
		->andWhere('cr.id_cuestionario = ?')
		->andWhere('YEAR(cr.fecha_auditoria) IN (?, ?)');
	$stmt = $app['db']->prepare($sql_reports_id);
	$stmt->bindValue(1, $plant_id);
	$stmt->bindValue(2, $questionnaire_id);
	$stmt->bindValue(3, $year);
	$stmt->bindValue(4, $year2);
	$stmt->execute();
	$reports = $stmt->fetchAll();

	// Getting sections name
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	for($j=0; $j<count($sections); $j++){
		$section = $sections[$j];

		$sql_percentage = "SELECT * FROM (SELECT TRUNCATE((SUM(rc.puntaje) * 100 / (sc.valor - IFNULL((SELECT SUM(p.ponderacion) FROM preguntas AS p LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports[0]['id']."), 0))), 2) AS percentage1 FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports[0]['id']." AND rc.no_aplica = 0) AS result1, (SELECT TRUNCATE((SUM(rc.puntaje) * 100 / (sc.valor - IFNULL((SELECT SUM(p.ponderacion) FROM preguntas AS p LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports[1]['id']."), 0))), 2) AS percentage2 FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports[1]['id']." AND rc.no_aplica = 0) AS result2";
		$stmt = $app['db']->prepare($sql_percentage);
		$stmt->execute();
		$percentage_got = $stmt->fetch();

		$result[$j]['section_name'] = $section['section_name'];
		$result[$j]['percentage1'] = $percentage_got['percentage1'];
		$result[$j]['percentage2'] = $percentage_got['percentage2'];
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result of one section for all plants in comparative mode
$app->post('/v1/dashboards/get-all-plants-by-section-comparative', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$section_id = $request->request->get('section_id');
	$plants_id = $request->request->get('plants_id');
	$year = $request->request->get('year');
	$year2 = $request->request->get('year2');

	// Getting the section data
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?')
		->andWhere('sc.id_seccion = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->bindValue(2, $section_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();
	$sections_results = array();
	$report_data = array();
	$total_got_value = 0;
	$max_score = 0;
	$x = 0;

	for($i=0; $i<count($plants_id); $i++){
		// Getting the questionnaires answered ids (Report ID) from each plant
		$sql_report_id = "SELECT id_cuestionario_respondido AS report_id FROM cuestionarios_respondidos WHERE id_cliente = ? AND YEAR(fecha_auditoria) IN (?, ?)";
		$stmt = $app['db']->prepare($sql_report_id);
		$stmt->bindValue(1, $plants_id[$i]['id']);
		$stmt->bindValue(2, $year);
		$stmt->bindValue(3, $year2);
		$stmt->execute();
		$reports_id = $stmt->fetchAll();

		//print_r($reports_id);

		//for($k = 0; $k<count($reports_id); $k++){
			
			$report_id_1 = ($reports_id[0]['report_id'] != null ? $reports_id[0]['report_id'] : 0);
			$report_id_2 = ($reports_id[1]['report_id'] != null ? $reports_id[1]['report_id'] : 0);
			// Getting the disccount points from the total (year 1)
			$sql_discount_1 = $app['db']->createQueryBuilder();
			$sql_discount_1
				->select('SUM(rc.puntaje) AS discount_points')
				->from('respuestas_cuestionarios', 'rc')
				->where('id_cuestionario_respondido = ?')
				->andWhere('no_aplica = 1');
			$stmt = $app['db']->prepare($sql_discount_1);
			$stmt->bindValue(1, $reports_id[$k][0]['report_id']);
			$stmt->execute();
			$discount_points1 = $stmt->fetch();

			// Getting the disccount points from the total (year 2)
			$sql_discount_2 = $app['db']->createQueryBuilder();
			$sql_discount_2
				->select('SUM(rc.puntaje) AS discount_points')
				->from('respuestas_cuestionarios', 'rc')
				->where('id_cuestionario_respondido = ?')
				->andWhere('no_aplica = 1');
			$stmt = $app['db']->prepare($sql_discount_2);
			$stmt->bindValue(1, $reports_id[$k][1]['report_id']);
			$stmt->execute();
			$discount_points2 = $stmt->fetch();

			for($j=0; $j<count($sections); $j++){
				$sql_percentage = $app['db']->createQueryBuilder();
				$sql_percentage = "SELECT * FROM (SELECT TRUNCATE((SUM(rc.puntaje) * 100 / (sc.valor - IFNULL((SELECT SUM(p.ponderacion) FROM preguntas AS p LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$report_id_1."), 0))), 2) AS percentage1 FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$report_id_1." AND rc.no_aplica = 0) AS result1, (SELECT TRUNCATE((SUM(rc.puntaje) * 100 / (sc.valor - IFNULL((SELECT SUM(p.ponderacion) FROM preguntas AS p LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$report_id_2."), 0))), 2) AS percentage2 FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$sections[$j]['section_id']." AND rc.id_cuestionario_respondido = ".$report_id_2." AND rc.no_aplica = 0) AS result2";
				$stmt = $app['db']->prepare($sql_percentage);
				$stmt->execute();
				$percentage_got = $stmt->fetch();

				$result[$x]['client_id'] = $plants_id[$i]['id'];
				$result[$x]['client_name'] = $plants_id[$i]['name'];
				$result[$x]['section_name'] = $sections[$j]['section_name'];
				$result[$x]['percentage1'] = $percentage_got['percentage1'];
				$result[$x]['percentage2'] = $percentage_got['percentage2'];
				$x++;
			}
		//}
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result of all questions of a section by one plant in comparative mode
$app->get('/v1/dashboards/get-all-questions-by-plant-comparative/{plant_id}/{section_id}/{questionnaire_id}/{year}/{year2}', function($plant_id, $section_id, $questionnaire_id, $year, $year2) use ($app){
	// Getting the report for the selected plant
	$sql_plant = $app['db']->createQueryBuilder();
	$sql_plant
		->select('cr.id_cuestionario_respondido AS id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cliente = ?')
		->andWhere('cr.id_cuestionario = ?')
		->andWhere('YEAR(cr.fecha_auditoria) IN (?, ?)');
	$stmt = $app['db']->prepare($sql_plant);
	$stmt->bindValue(1, $plant_id);
	$stmt->bindValue(2, $questionnaire_id);
	$stmt->bindValue(3, $year);
	$stmt->bindValue(4, $year2);
	$stmt->execute();
	$report_id = $stmt->fetchAll();

	$sql = $app['db']->createQueryBuilder();

	$sql = "SELECT 
				p.id_pregunta AS id,
				sc.nombre_seccion AS section_name,
				p.pregunta AS question,
				(SELECT rc.valor AS percentage1 FROM respuestas_cuestionarios AS rc LEFT JOIN preguntas AS p ON p.id_pregunta = rc.id_pregunta WHERE rc.id_pregunta = id AND rc.id_seccion = ".$section_id." AND rc.id_cuestionario_respondido = ".$report_id[0]['id'].") AS percentage1,
				(SELECT rc.valor AS percentage1 FROM respuestas_cuestionarios AS rc LEFT JOIN preguntas AS p ON p.id_pregunta = rc.id_pregunta WHERE rc.id_pregunta = id AND rc.id_seccion = ".$section_id." AND rc.id_cuestionario_respondido = ".$report_id[1]['id'].") AS percentage2
			FROM preguntas AS p
			LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = p.id_seccion 
			WHERE p.id_seccion = ".$section_id;

	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$questions = $stmt->fetchAll();

    return $app->json($questions)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the results of all plants by one question in comparative mode
$app->post('/v1/dashboards/get-all-plants-by-question-comparative', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$section_id = $request->request->get('section_id');
	$question_id = $request->request->get('question_id');
	$plants_id = $request->request->get('plants_id');
	$year = $request->request->get('year');
	$year2 = $request->request->get('year2');
	$result = array();
	$j = 0;

	for($i=0; $i<count($plants_id); $i++){
		// Getting the questionnaires answered id (Report ID) from each plant
		$sql_report_id = "SELECT id_cuestionario_respondido AS id FROM cuestionarios_respondidos WHERE id_cliente = ? AND YEAR(fecha_auditoria) IN (?, ?)";
		$stmt = $app['db']->prepare($sql_report_id);
		$stmt->bindValue(1, $plants_id[$i]['id']);
		$stmt->bindValue(2, $year);
		$stmt->bindValue(3, $year2);
		$stmt->execute();
		$report_id = $stmt->fetchAll();

		$sql_question_result = $app['db']->createQueryBuilder();

		$report_id1 = ($report_id[0]['id'] != null ? $report_id[0]['id'] : 0);
		$report_id2 = ($report_id[1]['id'] != null ? $report_id[1]['id'] : 0);

		$sql_question_result = "SELECT p.id_pregunta AS id, p.pregunta AS question, (SELECT rp.valor AS percentage FROM respuestas_cuestionarios AS rp LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_cuestionario_respondido = rp.id_cuestionario_respondido WHERE rp.id_pregunta = ".$question_id." AND rp.id_cuestionario_respondido = ".$report_id1." AND cr.id_cliente = ".$plants_id[$i]['id'].") AS percentage1, (SELECT rp.valor AS percentage FROM respuestas_cuestionarios AS rp LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_cuestionario_respondido = rp.id_cuestionario_respondido WHERE rp.id_pregunta = ".$question_id." AND rp.id_cuestionario_respondido = ".$report_id2." AND cr.id_cliente = ".$plants_id[$i]['id'].") AS percentage2 FROM preguntas AS p WHERE p.id_pregunta = ".$question_id;
		$stmt = $app['db']->prepare($sql_question_result);
		/*$stmt->bindValue(1, $question_id);
		$stmt->bindValue(2, $report_id[0]['id']);
		$stmt->bindValue(3, $plants_id[$i]['id']);
		$stmt->bindValue(4, $question_id);
		$stmt->bindValue(5, $report_id[1]['id']);
		$stmt->bindValue(6, $plants_id[$i]['id']);
		$stmt->bindValue(7, $question_id);*/
		$stmt->execute();
		$question_result = $stmt->fetch();

		//if($question_result != false){
			$result[$j]['plant_id'] = $plants_id[$i]['id'];
			$result[$j]['plant_name'] = $plants_id[$i]['name'];
			$result[$j]['question'] = $question_result['question'];
			$result[$j]['percentage1'] = $question_result['percentage1'];
			$result[$j]['percentage2'] = $question_result['percentage2'];
			$j++;
		//}
	}

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the result of one plant by one question in comparative mode
$app->get('/v1/dashboards/get-one-plant-by-question-comparative/{plant_id}/{questionnaire_id}/{question_id}/{year}/{year2}', function($plant_id, $questionnaire_id, $question_id, $year, $year2) use ($app){
	// Getting the report id of the plant
	$sql_report_id = $app['db']->createQueryBuilder();
	$sql_report_id
		->select('cr.id_cuestionario_respondido AS id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cliente = ?')
		->andWhere('YEAR(cr.fecha_auditoria) IN (?, ?)');
	$stmt = $app['db']->prepare($sql_report_id);
	$stmt->bindValue(1, $plant_id);
	$stmt->bindValue(2, $year);
	$stmt->bindValue(3, $year2);
	$stmt->execute();
	$report_id = $stmt->fetchAll();

	//$sql = $app['db']->createQueryBuilder();
	$sql = "SELECT 
				p.pregunta AS question,
				rop.opcion AS question_option,
				rop.valor AS total_percentage,
				(SELECT rc.valor FROM respuestas_cuestionarios AS rc WHERE rc.id_pregunta = $question_id AND rc.id_cuestionario_respondido = ".$report_id[0]['id']." AND id_cuestionario = $questionnaire_id) AS percentage_got_1,
				(SELECT rc.valor FROM respuestas_cuestionarios AS rc WHERE rc.id_pregunta = $question_id AND rc.id_cuestionario_respondido = ".$report_id[1]['id']." AND id_cuestionario = $questionnaire_id) AS percentage_got_2
			FROM r_opciones_preguntas AS rop
			LEFT JOIN preguntas AS p ON p.id_pregunta = rop.id_pregunta
			WHERE rop.id_pregunta = $question_id";
	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$question_options = $stmt->fetchAll();

	return $app->json($question_options)->setEncodingOptions(JSON_NUMERIC_CHECK);
});
