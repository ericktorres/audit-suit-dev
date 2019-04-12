<?php

use Symfony\Component\HttpFoundation\Request;

// This controller get all questionnaires
$app->get('/v1/questionnaires/get', function() use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('c.id_cuestionario AS id, c.codigo AS code, c.nombre AS name, c.status, c.fecha_alta AS creation_date')
		->from('cuestionarios', 'c')
		->orderBy('c.fecha_alta', 'ASC');
	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$questionnaires = $stmt->fetchAll();

	return $app->json($questionnaires);
});

// Create a new questionnaire
$app->post('/v1/questionnaires/create', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$code = $request->request->get('code');
	$name = $request->request->get('name');
	$status = '1';
	$date = date('Y-m-d H:i:s');

	$sql = "INSERT INTO cuestionarios 
				(codigo,
				nombre,
				status,
				fecha_alta) 
			VALUES (?, ?, ?, ?)";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $code);
	$stmt->bindValue(2, $name);
	$stmt->bindValue(3, $status);
	$stmt->bindValue(4, $date);
	$insert = $stmt->execute();

	if($insert == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro ha sido creado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error, intente de nuevo más tarde."
		);
	}

	return $app->json($response);

});


// Get a questionnaire based on the ID
$app->get('/v1/questionnaire/get/{id}', function($id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('c.id_cuestionario AS id, c.codigo AS code, c.nombre AS name, c.status, c.fecha_alta AS creation_date')
		->from('cuestionarios', 'c')
		->where('c.id_cuestionario = ?')
		->orderBy('c.fecha_alta', 'ASC');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $id);
	$stmt->execute();
	$questionnaire = $stmt->fetch();

	return $app->json($questionnaire);
});


// Edit questionnaire data
$app->post('/v1/questionnaires/modify', function(Request $request) use ($app){

	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$id = $request->request->get('id');
	$code = $request->request->get('code');
	$name = $request->request->get('name');
	$date = date('Y-m-d H:i:s');

	$sql = "UPDATE 
				cuestionarios
			SET
				codigo = ?,
				nombre = ?,
				fecha_modificacion = ?
			WHERE id_cuestionario = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $code);
	$stmt->bindValue(2, $name);
	$stmt->bindValue(3, $date);
	$stmt->bindValue(4, $id);
	$update = $stmt->execute();

	if($update == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro se ha modificado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});

// Delete a questionnaire
$app->get('/v1/questionnaires/delete/{questionnaire_id}', function($questionnaire_id) use ($app){
	$sql = "DELETE FROM cuestionarios WHERE id_cuestionario = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $questionnaire_id);
	$delete = $stmt->execute();

	if($delete == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro se ha eliminado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// Create a section for a questionnaire
$app->post('/v1/questionnaires/section/create', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$name = $request->request->get('name');
	$description = $request->request->get('description');
	$value = $request->request->get('value');
	$date = date('Y-m-d H:i:s');

	// Getting the number of the new section
	$sql_section_number = $app['db']->createQueryBuilder();
	$sql_section_number
		->select('COUNT(*) AS section_num')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_section_number);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$section_number = $stmt->fetch();
	$section_number = $section_number['section_num'] + 1;

	$sql = "INSERT INTO secciones_cuestionario
			(id_cuestionario,
			num_seccion,
			nombre_seccion,
			descripcion,
			valor,
			fecha_alta) VALUES (?, ?, ?, ?, ?, ?)";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->bindValue(2, $section_number);
	$stmt->bindValue(3, $name);
	$stmt->bindValue(4, $description);
	$stmt->bindValue(5, $value);
	$stmt->bindValue(6, $date);
	$insert = $stmt->execute();

	if($insert == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro ha sido creado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error, intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// Get sections of a questionnaire
$app->get('/v1/questionnaires/sections/get/{questionnaire_id}/{answered_questionnaire_id}', function($questionnaire_id, $answered_questionnaire_id) use ($app){

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('s.id_seccion AS id, s.num_seccion AS section_number, s.id_cuestionario AS questionnaire_id, s.nombre_seccion AS name, s.descripcion AS description, s.valor AS value, s.fecha_alta AS creation_date')
		->from('secciones_cuestionario', 's')
		->where('s.id_cuestionario = ?')
		->orderBy('s.num_seccion', 'ASC');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $questionnaire_id);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();

	// Getting questions of each section
	for($i=0; $i<count($sections); $i++){
		$sql_questions = $app['db']->createQueryBuilder();
		$sql_questions
			->select('p.id_pregunta AS question_id, p.num_pregunta AS question_number, p.pregunta AS question, p.texto_ayuda AS help_text, p.ponderacion AS value, p.critica AS is_critic, p.foto AS upload_photo, p.max_caracteres AS max_char, p.tipo AS type')
			->from('preguntas', 'p')
			->where('p.id_seccion = ?')
			->orderBy('p.num_pregunta', 'ASC');
		$stmt = $app['db']->prepare($sql_questions);
		$stmt->bindValue(1, $sections[$i]['id']);
		$stmt->execute();
		$questions = $stmt->fetchAll();

		$question_options = array();

		if(count($questions) > 0){
			

			for($j=0; $j<count($questions); $j++){
				// This query gets the results or selected values in the respuestas_cuestionarios table
				$sql_get_result = $app['db']->createQueryBuilder();
				$sql_get_result
					->select('rc.id_respuesta AS answer_id, rc.id_pregunta AS question_id, rc.id_opcion AS selected_option, rc.puntaje AS score, rc.valor AS value, rc.observaciones_auditor AS observations, rc.no_conformidad AS nonconformity, rc.no_aplica AS not_apply, CONCAT(id_cuestionario,\'_\',id_seccion,\'_\',id_pregunta,\'_\',id_opcion,\'_\',valor) AS string_selected_value')
					->from('respuestas_cuestionarios', 'rc')
					->where('rc.id_pregunta = ?')
					->andWhere('id_seccion = ?')
					->andWhere('id_cuestionario = ?')
					->andWhere('id_cuestionario_respondido = ?');
				$stmt = $app['db']->prepare($sql_get_result);
				$stmt->bindValue(1, $questions[$j]['question_id']);
				$stmt->bindValue(2, $sections[$i]['id']);
				$stmt->bindValue(3, $questionnaire_id);
				$stmt->bindValue(4, $answered_questionnaire_id);
				$stmt->execute();
				$question_result = $stmt->fetch();

				// Getting options for each question
				$sql_options = $app['db']->createQueryBuilder();
				$sql_options
					->select('rop.id_opcion AS option_id, rop.opcion AS question_option, rop.valor AS value')
					->from('r_opciones_preguntas', 'rop')
					->where('rop.id_pregunta = ?');
				$stmt = $app['db']->prepare($sql_options);
				$stmt->bindValue(1, $questions[$j]['question_id']);
				$stmt->execute();
				$options = $stmt->fetchAll();

				$question_options[$j]['question_id'] = $questions[$j]['question_id'];
				$question_options[$j]['question_number'] = $questions[$j]['question_number'];
				$question_options[$j]['question'] = $questions[$j]['question'];
				$question_options[$j]['help_text'] = $questions[$j]['help_text'];
				$question_options[$j]['value'] = $questions[$j]['value'];
				$question_options[$j]['is_critic'] = $questions[$j]['is_critic'];
				$question_options[$j]['upload_photo'] = $questions[$j]['upload_photo'];
				$question_options[$j]['max_char'] = $questions[$j]['max_char'];
				$question_options[$j]['type'] = $questions[$j]['type'];
				$question_options[$j]['question_result'] = $question_result;
				$question_options[$j]['options'] = $options;
			}	
		}/*else{
			$question_options = array();
		}*/

		$result[$i]['id'] = $sections[$i]['id'];
		$result[$i]['questionnaire_id'] = $sections[$i]['questionnaire_id'];
		$result[$i]['section_number'] = $sections[$i]['section_number'];
		$result[$i]['name'] = $sections[$i]['name'];
		$result[$i]['description'] = $sections[$i]['description'];
		$result[$i]['value'] = $sections[$i]['value'];
		$result[$i]['creation_date'] = $sections[$i]['creation_date'];
		$result[$i]['questions'] = $question_options;
	}

	return $app->json($result);
});


// This controller get the section data by id
$app->get('/v1/questionnaires/section/get/{section_id}', function($section_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('s.id_seccion AS id, s.id_cuestionario AS id_questionnaire, s.num_seccion AS section_number, s.nombre_seccion AS name, s.descripcion AS description, s.valor AS value, s.fecha_alta AS creation_date')
		->from('secciones_cuestionario', 's')
		->where('s.id_seccion = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $section_id);
	$stmt->execute();
	$section = $stmt->fetch();

	return $app->json($section);
});


// Edit a section data
$app->post('/v1/questionnaires/section/modify', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$name = $request->request->get('name');
	$description = $request->request->get('description');
	$value = $request->request->get('value');
	$section_id = $request->request->get('section_id');
	$num_section = $request->request->get('num_section');
	$date = date('Y-m-d H:i:s');

	$sql = "UPDATE 
				secciones_cuestionario
			SET
				num_seccion = ?,
				nombre_seccion = ?,
				descripcion = ?,
				valor = ?,
				fecha_modificacion = ?
			WHERE id_seccion = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $num_section);
	$stmt->bindValue(2, $name);
	$stmt->bindValue(3, $description);
	$stmt->bindValue(4, $value);
	$stmt->bindValue(5, $date);
	$stmt->bindValue(6, $section_id);
	$update = $stmt->execute();

	if($update == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro se ha modificado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($response);	
});


// Create a question
$app->post('/v1/questionnaires/section/questions/add', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$section_id = $request->request->get('section_id');
	$section_number = $request->request->get('section_number');
	$question = $request->request->get('question');
	$help_text = $request->request->get('help_text');
	$value = $request->request->get('value');
	$is_critic = $request->request->get('is_critic');
	$upload_photo = $request->request->get('upload_photo');
	$max_char = $request->request->get('max_char');
	$type = $request->request->get('type');
	$question_options = $request->request->get('question_options');
	$date = date('Y-m-d H:i:s');

	// Getting the number of the new question
	$sql_question_number = $app['db']->createQueryBuilder();
	$sql_question_number
		->select('COUNT(*) AS question_number')
		->from('preguntas', 'p')
		->where('p.id_seccion = ?');
	$stmt = $app['db']->prepare($sql_question_number);
	$stmt->bindValue(1, $section_id);
	$stmt->execute();
	$question_number = $stmt->fetch();
	$question_number = floatval($section_number . '.' . ($question_number['question_number'] + 1));
	//$question_number = ($question_number['question_number'] + 1);

	$sql = "INSERT INTO preguntas
			(id_seccion,
			num_pregunta,
			pregunta,
			texto_ayuda,
			ponderacion,
			critica,
			foto,
			max_caracteres,
			tipo,
			fecha_alta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $section_id);
	$stmt->bindValue(2, $question_number);
	$stmt->bindValue(3, $question);
	$stmt->bindValue(4, $help_text);
	$stmt->bindValue(5, $value);
	$stmt->bindValue(6, $is_critic);
	$stmt->bindValue(7, $upload_photo);
	$stmt->bindValue(8, $max_char);
	$stmt->bindValue(9, $type);
	$stmt->bindValue(10, $date);
	$insert = $stmt->execute();
	$question_id = $app['db']->lastInsertId();

	if($insert == true){
		if(count($question_options) > 0){
			// Saving the question options
			for($i=0; $i<count($question_options); $i++){
				$option = $question_options[$i];

				$sql_option = "INSERT INTO r_opciones_preguntas (id_pregunta, opcion, valor, fecha_alta) VALUES (?, ?, ?, ?)";
				$stmt = $app['db']->prepare($sql_option);
				$stmt->bindValue(1, $question_id);
				$stmt->bindValue(2, $option['option']);
				$stmt->bindValue(3, $option['value']);
				$stmt->bindValue(4, $date);
				$option = $stmt->execute();
			}

			$result = array(
				"result_code" => 1,
				"message" => "El registro ha sido creado exitosamente."
			);
		}else{
			$result = array(
				"result_code" => 1,
				"message" => "El registro ha sido creado exitosamente."
			);
		}
	}else{
		$result = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($result);

});


// Get one question based on the ID
$app->get('/v1/questionnaires/sections/question/get/{question_id}', function($question_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('p.id_pregunta AS question_id, p.id_seccion AS section_id, p.num_pregunta AS question_number, p.pregunta AS question, p.texto_ayuda AS help_text, p.ponderacion AS value, p.critica AS is_critic, p.foto AS upload_photo, p.max_caracteres AS max_char, p.tipo AS type')
		->from('preguntas', 'p')
		->where('p.id_pregunta = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $question_id);
	$stmt->execute();
	$question = $stmt->fetch();

	$result = array();

	if($question['type'] != '1'){
		// For questions with multiple options or multiple selection
		$sql_options = $app['db']->createQueryBuilder();
		$sql_options
			->select('rop.id_opcion AS id, rop.opcion AS question_option, rop.valor AS value')
			->from('r_opciones_preguntas', 'rop')
			->where('rop.id_pregunta = ?');
		$stmt = $app['db']->prepare($sql_options);
		$stmt->bindValue(1, $question['question_id']);
		$stmt->execute();
		$options = $stmt->fetchAll();
	}else{
		$options = array();
	}

	$result['question_id'] = $question['question_id'];
	$result['section_id'] = $question['section_id'];
	$result['question_number'] = $question['question_number'];
	$result['question'] = $question['question'];
	$result['help_text'] = $question['help_text'];
	$result['value'] = $question['value'];
	$result['is_critic'] = $question['is_critic'];
	$result['upload_photo'] = $question['upload_photo'];
	$result['max_char'] = $question['max_char'];
	$result['type'] = $question['type'];
	$result['options'] = $options;

	return $app->json($result);

});


// Edit a question
$app->post('/v1/questionnaires/sections/question/edit', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$question_id = $request->request->get('question_id');
	$num_question = $request->request->get('num_question');
	$type = $request->request->get('type');
	$question = $request->request->get('question');
	$help_text = $request->request->get('help_text');
	$value = $request->request->get('value');
	$is_critic = $request->request->get('is_critic');
	$upload_photo = $request->request->get('upload_photo');
	$max_char = $request->request->get('max_char');
	$options = $request->request->get('options');
	$date = date('Y-m-d H:i:s');

	// Edit the main data of a question
	$sql = "UPDATE preguntas SET 
			num_pregunta = ?,
			pregunta = ?, 
			texto_ayuda = ?, 
			ponderacion = ?, 
			critica = ?, 
			foto = ?, 
			max_caracteres = ?, 
			fecha_modificacion = ?
			WHERE id_pregunta = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $num_question);
	$stmt->bindValue(2, $question);
	$stmt->bindValue(3, $help_text);
	$stmt->bindValue(4, $value);
	$stmt->bindValue(5, $is_critic);
	$stmt->bindValue(6, $upload_photo);
	$stmt->bindValue(7, $max_char);
	$stmt->bindValue(8, $date);
	$stmt->bindValue(9, $question_id);
	$update = $stmt->execute();

	if($type != "1"){
		// Update the options of the question
		for($i=0; $i<count($options); $i++){
			$option = $options[$i];

			$sql_option = "UPDATE r_opciones_preguntas SET opcion = ?, valor = ?, fecha_modificacion = ? WHERE id_opcion = ?";
			$stmt = $app['db']->prepare($sql_option);
			$stmt->bindValue(1, $option['option']);
			$stmt->bindValue(2, $option['value']);
			$stmt->bindValue(3, $date);
			$stmt->bindValue(4, $option['option_id']);
			$updated_option = $stmt->execute();
		}
	}

	if($update == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro se ha modificado exitosamente."
		); 
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// Delete a question
$app->get('/v1/questionnaires/sections/question/delete/{question_id}', function($question_id) use ($app){
	// Deleting the question
	$sql = "DELETE FROM preguntas WHERE id_pregunta = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $question_id);
	$delete = $stmt->execute();

	// Deleting tue question options
	$sql_delete_options = "DELETE FROM r_opciones_preguntas WHERE id_pregunta = ?";
	$stmt = $app['db']->prepare($sql_delete_options);
	$stmt->bindValue(1, $question_id);
	$delete_options = $stmt->execute();

	if($delete == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro se ha eliminado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// This controller get the relation between an auditor an a questionnaire
/*$app->get('/v1/questionnaires/auditors/get-questionnaires-and-clients/{auditor_id}', function($auditor_id) use ($app){

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('rcc.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, e.id_empresa AS company_id, e.nombre_comercial AS company_name')
		->from('r_clientes_cuestionarios', 'rcc')
		->leftJoin('rcc', 'empresas', 'e', 'e.id_empresa = rcc.id_cliente')
		->leftJoin('e', 'r_gerentes_empresas', 'rge', 'rge.id_empresa = e.id_empresa')
		->leftJoin('rge', 'r_gerentes_auditores', 'rga', 'rga.id_gerente = rge.id_gerente')
		->leftJoin('rcc', 'cuestionarios', 'c', 'c.id_cuestionario = rcc.id_cuestionario')
		->where('rga.id_auditor = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $auditor_id);
	$stmt->execute();
	$questionnaires = $stmt->fetchAll();

	return $app->json($questionnaires);
});*/


// This controller get just the companies that are assigned to the auditor
$app->get('/v1/questionnaires/auditors/get-questionnaires-and-clients/{auditor_id}', function($auditor_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('e.id_empresa AS company_id, e.nombre_comercial AS company_name, c.id_cuestionario AS questionnaire_id, c.codigo AS questionnaire_code, c.nombre AS questionnaire_name')
		->from('r_auditores_empresas', 'rae')
		->leftJoin('rae', 'empresas', 'e', 'e.id_empresa = rae.id_empresa')
		->leftJoin('e', 'r_clientes_cuestionarios', 'rcc', 'rcc.id_cliente = e.id_empresa')
		->leftJoin('rcc', 'cuestionarios', 'c', 'c.id_cuestionario = rcc.id_cuestionario')
		->where('rae.id_auditor = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $auditor_id);
	$stmt->execute();
	$questionnaires = $stmt->fetchAll();

	return $app->json($questionnaires);
});

// This controller get just the companies that are assigned to the auditor
// $app->get('/v1/questionnaires/auditors/get-questionnaires-and-clients-dev/{auditor_id}', function($auditor_id) use ($app){
// 	$sql = $app['db']->createQueryBuilder();
// 	$sql
// 		->select('e.id_empresa AS company_id, e.nombre_comercial AS company_name, c.id_cuestionario AS questionnaire_id, c.codigo AS questionnaire_code, c.nombre AS questionnaire_name')
// 		->from('r_auditores_empresas', 'rae')
// 		->leftJoin('rae', 'empresas', 'e', 'e.id_empresa = rae.id_empresa')
// 		->leftJoin('e', 'r_clientes_cuestionarios', 'rcc', 'rcc.id_cliente = e.id_empresa')
// 		->leftJoin('rcc', 'cuestionarios', 'c', 'c.id_cuestionario = rcc.id_cuestionario')
// 		->where('rae.id_auditor = ?');
// 	$stmt = $app['db']->prepare($sql);
// 	$stmt->bindValue(1, $auditor_id);
// 	$stmt->execute();
// 	$questionnaires = $stmt->fetchAll();

// 	$sql = $app['db']->createQueryBuilder();
// 	$sql->
		


// 	return $app->json($questionnaires);

// });


// Saving the questionnaire answers
$app->post('/v1/questionnaire/save', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_id = $request->request->get('questionnaire_id');
	$client_id = $request->request->get('client_id');
	$auditor_id = $request->request->get('auditor_id');
	$coordinates = $request->request->get('coordinates');
	$answers = $request->request->get('answers');
	$save_type = $request->request->get('save_type');
	$start_time = date('H:i:s');
	$start_date = date('Y-m-d');
	$date = date('Y-m-d H:i:s');
	$answer_date = date('Y-m-d');
	$answer_time = date('H:i:s');

	if($save_type == 'complete'){
		$end_time = date('H:i:s');
		$end_date = date('Y-m-d');
		$end_coordinates = $coordinates;
	}else{
		$end_time = null;
		$end_date = null;
		$end_coordinates = null;
	}

	// Saving the main data of the questionnaire
	$sql = "delete from respuestas_cuestionarios where respuestas_cuestionarios.id_cuestionario_respondido = ". $answers[0]['questionnaire_respondido_id'] . " and respuestas_cuestionarios.id_seccion =" . $answers[0]['section_id'];
	$stmt = $app['db']->prepare($sql);
	$delete = $stmt->execute();
	
		//return $app->json($delete);
	if ($delete) {
		for($i=0; $i<count($answers); $i++){
			$answer = $answers[$i];

			$insert_answer = "INSERT INTO respuestas_cuestionarios
								(id_cuestionario_respondido,
								id_cuestionario,
								id_seccion,
								id_pregunta,
								id_opcion,
								puntaje,
								valor,
								fecha_respuesta,
								hora_respuesta,
								observaciones_auditor,
								no_conformidad,
								no_aplica,
								fecha_alta)
							VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt = $app['db']->prepare($insert_answer);
			$stmt->bindValue(1, $answer['questionnaire_respondido_id']);
			$stmt->bindValue(2, $answer['questionnaire_id']);
			$stmt->bindValue(3, $answer['section_id']);
			$stmt->bindValue(4, $answer['question_id']);
			$stmt->bindValue(5, $answer['option_id']);
			$stmt->bindValue(6, $answer['score']);
			$stmt->bindValue(7, $answer['value']);
			$stmt->bindValue(8, $answer_date);
			$stmt->bindValue(9, $answer_time);
			$stmt->bindValue(10, $answer['observations']);
			$stmt->bindValue(11, $answer['nonconformity']);
			$stmt->bindValue(12, $answer['not_apply']);
			$stmt->bindValue(13, $date);
			$inserted_answer = $stmt->execute();
		}

		$result = array(
			"result_code" => 1,
			"message" => "El registro ha sido creado exitosamente."
		);
		return $app->json($result);
	}



});


// Saving the questionnaire answers
$app->post('/v1/questionnaire/create-cuestionary', function(Request $request) use ($app){


	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());	
	$questionnaire_id = $request->request->get('questionnaire_id');
	$auditor_id = $request->request->get('auditor_id');
	$coordinates = $request->request->get('coordinates');
	$company_id = $request->request->get('company_id');
	$save_type = $request->request->get('save_type');

	
	
		$sql = $app['db']->createQueryBuilder();
		$sql
			->select('c.id_cuestionario_respondido AS idC')
			->from('cuestionarios_respondidos', 'c')
			->where('c.id_cuestionario = '.$questionnaire_id.' and c.id_cliente = '.$company_id.' and c.id_auditor='.$auditor_id);	
		$stmt = $app['db']->prepare($sql);
		$stmt->execute();
		

		$questionnaire = $stmt->fetch();
	
		//Si existe un cuestionario
		if ($questionnaire) {
			$result = array(
				"result_code" => 1,
				"message" => "El registro ha sido actualizado exitosamente.",
				"new_cuestionary" => $questionnaire['idC']
			);
			return $app->json($result);
		}else{ //Si no existe cuestionario
			
			
			$start_time = date('H:i:s');
			$start_date = date('Y-m-d');
			$date = date('Y-m-d H:i:s');
			// $answer_date = date('Y-m-d');
			// $answer_time = date('H:i:s');

			if($save_type == 'iniciado'){
				$estatus = '0';
			}

			if($save_type == 'complete'){
				$end_time = date('H:i:s');
				$end_date = date('Y-m-d');
				$end_coordinates = $coordinates;
			}else{
				$end_time = null;
				$end_date = null;
				$end_coordinates = null;
			}

			// Saving the main data of the questionnaire
			$sql = "INSERT INTO cuestionarios_respondidos (
						id_cuestionario,
						id_cliente,
						id_auditor,
						hora_inicio,
						fecha_de_inicio,
						coordenadas_inicio,
						hora_finalizacion,
						fecha_finalizacion,
						coordenadas_finalizacion,
						fecha_alta,
						estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt = $app['db']->prepare($sql);
			$stmt->bindValue(1, $questionnaire_id);
			$stmt->bindValue(2, $company_id);
			$stmt->bindValue(3, $auditor_id);
			$stmt->bindValue(4, $start_time);
			$stmt->bindValue(5, $start_date);
			$stmt->bindValue(6, $coordinates);
			$stmt->bindValue(7, $end_time);
			$stmt->bindValue(8, $end_date);
			$stmt->bindValue(9, $end_coordinates);
			$stmt->bindValue(10, $date);
			$stmt->bindValue(11, $estatus);
			$insert = $stmt->execute();


			// Saving the questionnaire answers
			if($insert == true){

				$sql = $app['db']->createQueryBuilder();
				$sql="SELECT MAX(id_cuestionario_respondido) AS idC FROM cuestionarios_respondidos;";
				$stmt = $app['db']->prepare($sql);
				$stmt->execute();
				$questionnaire = $stmt->fetch();

				
				//$answered_questionnaire_id = $app['db']->lastInsertId();
				$result = array(
					"result_code" => 1,
					"message" => "El registro ha sido creado exitosamente.",
					"new_cuestionary" => $questionnaire['idC']
				);
			}else{

				$result = array(
					"result_code" => 0,
					"message" => "Error al intentar crear cuestionario."
				);
			}

			
			return $app->json($result);	
			//return $answered_questionnaire_id;
			

		}
});

