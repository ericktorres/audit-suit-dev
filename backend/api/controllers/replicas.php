<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// This controller gets all the plants that are assigned to the user (For filtering the wrong questions)
$app->get('/v1/reply/get-plants/{user_id}/{user_privileges}', function($user_id, $user_privileges) use ($app){
	
	if($user_privileges == "2"){
		// Getting the plants ID
		$sql_plants = $app['db']->createQueryBuilder();
		$sql_plants
			->select('rge.id_empresa AS id, e.nombre_comercial AS company_name')
			->from('r_gerentes_empresas', 'rge')
			->leftJoin('rge', 'empresas', 'e', 'e.id_empresa = rge.id_empresa')
			->where('rge.id_gerente = ?');
		$stmt = $app['db']->prepare($sql_plants);
		$stmt->bindValue(1, $user_id);
		$stmt->execute();
		$plants = $stmt->fetchAll();
	}

	return $app->json($plants);

});

// This controller gets the reports that are available for the selected plant
$app->get('/v1/reply/get-reports-by-plant/{plant_id}', function($plant_id) use ($app){
	// Getting the reports that are available for the user
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,\' \',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'cuestionarios', 'c', 'c.id_cuestionario = cr.id_cuestionario')
		->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
		->leftJoin('cr', 'usuarios', 'u', 'u.id_usuario = cr.id_auditor')
		->where('cr.id_cliente = ?')
		->orderBy('cr.id_auditor', 'ASC');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $plant_id);
	$stmt->execute();
	$reports = $stmt->fetchAll();

	return $app->json($reports);
});

// This controller gets the wrong question of the selected report
$app->get('/v1/reply/get-wrong-questions-by-report/{report_id}', function($report_id) use ($app){
	$sql_questions = $app['db']->createQueryBuilder();
	$sql_questions
		->select('rc.id_pregunta AS question_id, p.pregunta AS question, rc.valor AS score')
		->from('respuestas_cuestionarios', 'rc')
		->leftJoin('rc', 'preguntas', 'p', 'p.id_pregunta = rc.id_pregunta')
		->where('rc.id_cuestionario_respondido = ?')
		->andWhere('rc.valor < 100');
	$stmt = $app['db']->prepare($sql_questions);
	$stmt->bindValue(1, $report_id);
	$stmt->execute();
	$questions = $stmt->fetchAll();

	return $app->json($questions);
});

// This controller gets all the question where the client did not get the 100 percent
$app->get('/v1/reply/get-wrong-questions/{user_id}/{user_privileges}', function($user_id, $user_privileges) use ($app){

	if($user_privileges == "2"){
		// Getting the plants ID
		$sql_plants = $app['db']->createQueryBuilder();
		$sql_plants
			->select('rge.id_empresa AS id')
			->from('r_gerentes_empresas', 'rge')
			->where('rge.id_gerente = ?');
		$stmt = $app['db']->prepare($sql_plants);
		$stmt->bindValue(1, $user_id);
		$stmt->execute();
		$plants_id = $stmt->fetchAll();

		$separator = ',';
		$ids = '';
		$total = count($plants_id) - 1;

		for($i=0; $i<count($plants_id); $i++){
			if($i == $total){ $separator = ''; }else{ $separator = ','; }
			
			$ids .= $plants_id[$i]['id'] . $separator;
		}

		$result = array();

		// Getting the reports that are available for the user
		$sql = $app['db']->createQueryBuilder();
		$sql
			->select('cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,\' \',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date')
			->from('cuestionarios_respondidos', 'cr')
			->leftJoin('cr', 'cuestionarios', 'c', 'c.id_cuestionario = cr.id_cuestionario')
			->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
			->leftJoin('cr', 'usuarios', 'u', 'u.id_usuario = cr.id_auditor')
			->where('cr.id_cliente IN (?)')
			->orderBy('cr.id_auditor', 'ASC');
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $ids);
		$stmt->execute();
		$reports = $stmt->fetchAll();

		for($i=0; $i<count($reports); $i++){
			$sql_question = $app['db']->createQueryBuilder();
			$sql_question
				->select('rc.id_pregunta AS question_id, p.pregunta AS question, rc.valor AS score')
				->from('respuestas_cuestionarios', 'rc')
				->leftJoin('rc', 'preguntas', 'p', 'p.id_pregunta = rc.id_pregunta')
				->where('rc.id_cuestionario_respondido = ?')
				->andWhere('rc.valor < 100');
			$stmt = $app['db']->prepare($sql_question);
			$stmt->bindValue(1, $reports[$i]['report_id']);
			$stmt->execute();
			$questions = $stmt->fetchAll();

			$result[$i]['questionnaire_code'] = $reports[$i]['questionnaire_code'];
			$result[$i]['questionnaire_answered_id'] = $reports[$i]['report_id'];
			$result[$i]['client_name'] = $reports[$i]['client_name'];
			$result[$i]['questionnaire_name'] = $reports[$i]['questionnaire_name'];
			$result[$i]['questions'] = $questions;

		}
	}

	/*$sql_questionnaires = $app['db']->createQueryBuilder();
	$sql_questionnaires
		->select('cr.id_cuestionario_respondido AS questionnaire_answered_id, c.codigo AS questionnaire_code')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'cuestionarios', 'c', 'c.id_cuestionario = cr.id_cuestionario')
		->where('cr.id_cliente = ?');
	$stmt = $app['db']->prepare($sql_questionnaires);
	$stmt->bindValue(1, $client_id);
	$stmt->execute();
	$questionnaires_id = $stmt->fetchAll();

	$result = array();

	for($i=0; $i<count($questionnaires_id); $i++){
		$sql_question = $app['db']->createQueryBuilder();
		$sql_question
			->select('rc.id_pregunta AS question_id, p.pregunta AS question, rc.valor AS score')
			->from('respuestas_cuestionarios', 'rc')
			->leftJoin('rc', 'preguntas', 'p', 'p.id_pregunta = rc.id_pregunta')
			->where('rc.id_cuestionario_respondido = ?')
			->andWhere('rc.valor < 100');
		$stmt = $app['db']->prepare($sql_question);
		$stmt->bindValue(1, $questionnaires_id[$i]['questionnaire_answered_id']);
		$stmt->execute();
		$questions = $stmt->fetchAll();

		$result[$i]['questionnaire_answered_id'] = $questionnaires_id[$i]['questionnaire_answered_id'];
		$result[$i]['questionnaire_code'] = $questionnaires_id[$i]['questionnaire_code'];
		$result[$i]['questions'] = $questions;
	}*/

	return $app->json($result);

});


// This controller gets all the reply
$app->get('/v1/reply/get-reply/{client_id}/{user_id}/{user_privileges}/{report_id}', function($client_id, $user_id, $user_privileges, $report_id) use ($app){
	$user_id_origin = $user_id;
	// For internal users
	if($client_id == 0){
		if($user_privileges == 3){ // For auditors
			// Getting the plants
			$sql_plants = $app['db']->createQueryBuilder();
			$sql_plants
				->select('rae.id_empresa AS client_id')
				->from('r_auditores_empresas', 'rae')
				->where('rae.id_auditor = ?');
			
		}else if($user_privileges == 2){ // For managers
			// Getting the plants
			$sql_plants = $app['db']->createQueryBuilder();
			$sql_plants
				->select('rge.id_empresa AS client_id')
				->from('r_gerentes_empresas', 'rge')
				->where('rge.id_gerente = ?');
		}else if($user_privileges == 1){ // For administrators
			$user_id = 2;
			// Getting the plants
			$sql_plants = $app['db']->createQueryBuilder();
			$sql_plants
				->select('e.id_empresa AS client_id')
				->from('empresas', 'e')
				->where('e.id_tipo_empresa = ?');
		}
		
		$stmt = $app['db']->prepare($sql_plants);
		$stmt->bindValue(1, $user_id);
		$stmt->execute();
		$plants = $stmt->fetchAll();

		$separator = ',';
		$arr_plants_assigned = '';
		$total = count($plants) - 1;

		for($i=0; $i<count($plants); $i++){
			if($i == $total){ $separator = ''; }else{ $separator = ','; }		
			$arr_plants_assigned .= $plants[$i]['client_id'] . $separator;
		}
	}else{
		// For external users
		// Getting the plants or supplier that belongs to the client
		$sql_plants = $app['db']->createQueryBuilder();
		$sql_plants
			->select('rcpp.id_planta_proveedor AS client_id')
			->from('r_clientes_plantas_proveedores', 'rcpp')
			->where('rcpp.id_empresa = ?');
		$stmt = $app['db']->prepare($sql_plants);
		$stmt->bindValue(1, $client_id);
		$stmt->execute();
		$plants = $stmt->fetchAll();

		$separator = ',';
		$arr_plants = '';
		$total = count($plants) - 1;

		for($i=0; $i<count($plants); $i++){
			if($i == $total){ $separator = ''; }else{ $separator = ','; }
				
			$arr_plants .= $plants[$i]['client_id'] . $separator;
		}
		
		// Getting the plants or supplier that are assigned to the user
		$sql_plants_assigned = "SELECT id_empresa AS client_id
								FROM r_gerentes_empresas AS rge
								WHERE id_gerente = $user_id AND id_empresa IN ($arr_plants)";
		$stmt = $app['db']->prepare($sql_plants_assigned);
		$stmt->execute();
		$plants_assigned = $stmt->fetchAll();

		$separator2 = ',';
		$arr_plants_assigned = '';
		$total2 = count($plants_assigned) - 1;

		for($j=0; $j<count($plants_assigned); $j++){
			if($j == $total2){ $separator2 = ''; }else{ $separator2 = ','; }
				
			$arr_plants_assigned .= $plants_assigned[$j]['client_id'] . $separator2;
		}
	}

	$sql = "SELECT r.id_replica AS id, r.id_cuestionario_respondido AS questionnaire_answered_id, p.id_pregunta AS question_id, p.pregunta AS question, r.causa_raiz AS root_cause, r.accion_correctiva AS corrective_action, r.responsables AS responsible, r.fecha_compromiso AS commitment_date, r.archivo_evidencia AS evidence_file, validacion AS is_closed, r.satisfactorio AS satisfactory 
			FROM replicas AS r 
			LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_cuestionario_respondido = r.id_cuestionario_respondido
			LEFT JOIN preguntas AS p ON p.id_pregunta = r.id_pregunta
			WHERE cr.id_cliente IN ($arr_plants_assigned) AND r.id_cuestionario_respondido = $report_id
			ORDER BY r.id_cuestionario_respondido ASC";
	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$reply = $stmt->fetchAll();

	$arr_reply = array();

	for($i=0; $i<count($reply); $i++){
		// Getting the observations and the nonconformity for each reply
		$sql_reply_data = $app['db']->createQueryBuilder();
		$sql_reply_data
			->select('rc.observaciones_auditor AS observations, rc.no_conformidad AS nonconformity')
			->from('respuestas_cuestionarios', 'rc')
			->where('rc.id_cuestionario_respondido = ?')
			->andWhere('rc.id_pregunta = ?');
		$stmt = $app['db']->prepare($sql_reply_data);
		$stmt->bindValue(1, $report_id);
		$stmt->bindValue(2, $reply[$i]['question_id']);
		$stmt->execute();
		$reply_data = $stmt->fetch();

		// Getting the comments for reply
		$sql_comments = $app['db']->createQueryBuilder();
		$sql_comments
			->select('id_detalle_replica AS comment_id, CONCAT(u.nombre,\' \',u.apellido_paterno) AS user_name, dr.comentario AS comment, dr.evidencia AS evidence_file, DATE(dr.fecha_alta) AS comment_date, TIME(dr.fecha_alta) AS comment_time')
			->from('detalle_replicas', 'dr')
			->leftJoin('dr', 'usuarios', 'u', 'u.id_usuario = dr.id_usuario')
			->where('dr.id_replica = ?')
			->orderBy('dr.id_detalle_replica', 'ASC');
		$stmt = $app['db']->prepare($sql_comments);
		$stmt->bindValue(1, $reply[$i]['id']);
		$stmt->execute();
		$comments = $stmt->fetchAll();

		$comment_data[$i] = array();

		// Getting the comment status for the logged user (If was opened)
		for($x=0; $x<count($comments); $x++){
			$sql_comment_status = "SELECT COUNT(*) AS exist FROM comentarios_replicas_abiertos WHERE id_detalle_replica = ? AND id_usuario = ?";
			$stmt = $app['db']->prepare($sql_comment_status);
			$stmt->bindValue(1, $comments[$x]['comment_id']);
			$stmt->bindValue(2, $user_id_origin);
			$stmt->execute();
			$comment_status = $stmt->fetch();

			$comment_data[$i][$x]['comment_id'] = $comments[$x]['comment_id'];
			$comment_data[$i][$x]['user_name'] = $comments[$x]['user_name'];
			$comment_data[$i][$x]['comment'] = $comments[$x]['comment'];
			$comment_data[$i][$x]['evidence_file'] = $comments[$x]['evidence_file'];
			$comment_data[$i][$x]['comment_date'] = $comments[$x]['comment_date'];
			$comment_data[$i][$x]['comment_time'] = $comments[$x]['comment_time'];
			$comment_data[$i][$x]['status'] = $comment_status['exist'];
		}

		// Getting the status (If was opened)
		$sql_status = "SELECT COUNT(*) AS opened FROM replicas_abiertas WHERE id_usuario = ? AND id_replica = ? AND id_reporte = ?";
		$stmt = $app['db']->prepare($sql_status);
		$stmt->bindValue(1, $user_id_origin);
		$stmt->bindValue(2, $reply[$i]['id']);
		$stmt->bindValue(3, $report_id);
		$stmt->execute();
		$reply_status = $stmt->fetch();

		$arr_reply[$i]['id'] = $reply[$i]['id'];
		$arr_reply[$i]['questionnaire_answered_id'] = $reply[$i]['questionnaire_answered_id'];
		$arr_reply[$i]['question_id'] = $reply[$i]['question_id'];
		$arr_reply[$i]['question'] = $reply[$i]['question'];
		$arr_reply[$i]['root_cause'] = $reply[$i]['root_cause'];
		$arr_reply[$i]['corrective_action'] = $reply[$i]['corrective_action'];
		$arr_reply[$i]['responsible'] = $reply[$i]['responsible'];
		$arr_reply[$i]['commitment_date'] = $reply[$i]['commitment_date'];
		$arr_reply[$i]['evidence_file'] = $reply[$i]['evidence_file'];
		$arr_reply[$i]['is_closed'] = $reply[$i]['is_closed'];
		$arr_reply[$i]['observations'] = $reply_data['observations'];
		$arr_reply[$i]['nonconformity'] = $reply_data['nonconformity'];
		$arr_reply[$i]['status'] = $reply_status['opened'];
		$arr_reply[$i]['satisfactory'] = $reply[$i]['satisfactory'];
		$arr_reply[$i]['comments'] = $comment_data[$i];
	}

	return $app->json($arr_reply);
});


// This controller gets a reply by id
$app->get('/v1/reply/get-reply-by-id/{reply_id}', function($reply_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('r.id_replica AS id, r.id_cuestionario_respondido AS questionnaire_answered_id, p.pregunta AS question, r.accion_correctiva AS corrective_action, r.responsables AS responsibles, r.fecha_compromiso AS commitment_date, r.archivo_evidencia AS evidence_file')
		->from('replicas', 'r')
		->leftJoin('r', 'cuestionarios_respondidos', 'cr', 'cr.id_cuestionario_respondido = r.id_cuestionario_respondido')
		->leftJoin('r', 'preguntas', 'p', 'p.id_pregunta = r.id_pregunta')
		->where('r.id_replica = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $reply_id);
	$stmt->execute();
	$reply = $stmt->fetch();

	return $app->json($reply);
});


// This controller create a new reply
$app->post('/v1/reply/create-reply', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$questionnaire_answered_id = $request->request->get('questionnaire_answered_id');
	$question_id = $request->request->get('question_id');
	$user_id = $request->request->get('user_id');
	$root_cause = $request->request->get('root_cause');
	$corrective_action = $request->request->get('corrective_action');
	$responsibles = $request->request->get('responsibles');
	$commitment_date = $request->request->get('commitment_date');
	$evidence_file = $request->request->get('evidence_file');
	$date = date('Y-m-d H:i:s');

	// Validate that there is no reply for the selected client and questionnaire
	$sql_check = $app['db']->createQueryBuilder();
	$sql_check
		->select('COUNT(*) AS exist')
		->from('replicas', 'r')
		->where('r.id_cuestionario_respondido = ?')
		->andWhere('r.id_pregunta = ?');
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $questionnaire_answered_id);
	$stmt->bindValue(2, $question_id);
	$stmt->execute();
	$exist = $stmt->fetch();

	//if($exist['exist'] == "0"){
		$sql = "INSERT INTO replicas
					(id_cuestionario_respondido,
					id_pregunta,
					id_usuario,
					causa_raiz,
					accion_correctiva,
					responsables,
					fecha_compromiso,
					archivo_evidencia,
					fecha_alta)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $questionnaire_answered_id);
		$stmt->bindValue(2, $question_id);
		$stmt->bindValue(3, $user_id);
		$stmt->bindValue(4, $root_cause);
		$stmt->bindValue(5, $corrective_action);
		$stmt->bindValue(6, $responsibles);
		$stmt->bindValue(7, $commitment_date);
		$stmt->bindValue(8, $evidence_file);
		$stmt->bindValue(9, $date);
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
	/*}else{
		$response = array(
			"result_code" => 0,
			"message" => "La réplica que intenta guardar ya fue creada anteriormente."
		);
	}*/

	return $app->json($response);
});


// This controller upload the evidence file to the server
$app->post('/v1/reply/upload-evidence', function(Request $request) use ($app){
	$file = $request->files->get('file');

	if($file !== null){
        $path = '../../console/evidences/';
        $copy = $file->move($path, $file->getClientOriginalName());
        //echo $copy;
        $response = array(
			"result_code" => 1,
			"message" => "Se ha subido el archivo exitosamente. Archivo: " . $file->getClientOriginalName(),
			"file_size" => "Size: " . filesize($path . '/' . $file->getClientOriginalName()) / 1024 . " kb"
		);
    }else{
    	$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde.",
			"file_size" => null
		);
    }

    return $app->json($response);
});


// This controller edit a reply
$app->post('/v1/reply/edit-reply', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$reply_id = $request->request->get('reply_id');
	$root_cause = $request->request->get('root_cause');
	$corrective_action = $request->request->get('corrective_action');
	$responsibles = $request->request->get('responsibles');
	$commitment_date = $request->request->get('commitment_date');
	$evidence_file = $request->request->get('evidence_file');
	$date = date('Y-m-d H:i:s');

	$sql = "UPDATE replicas 
			SET causa_raiz = ?, accion_correctiva = ?, responsables = ?, fecha_compromiso = ?, archivo_evidencia = ?, fecha_modificacion = ?
			WHERE id_replica = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $root_cause);
	$stmt->bindValue(2, $corrective_action);
	$stmt->bindValue(3, $responsibles);
	$stmt->bindValue(4, $commitment_date);
	$stmt->bindValue(5, $evidence_file);
	$stmt->bindValue(6, $date);
	$stmt->bindValue(7, $reply_id);
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


// This controller insert a new comment to a reply
$app->post('/v1/reply/insert-comment', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$reply_id = $request->request->get('reply_id');
	$user_id = $request->request->get('user_id');
	$comment = $request->request->get('comment');
	$evidence = $request->request->get('evidence');
	$date = date('Y-m-d H:i:s');

	$sql = "INSERT INTO detalle_replicas (id_replica, id_usuario, comentario, evidencia, fecha_alta) VALUES (?, ?, ?, ?, ?)";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $reply_id);
	$stmt->bindValue(2, $user_id);
	$stmt->bindValue(3, $comment);
	$stmt->bindValue(4, $evidence);
	$stmt->bindValue(5, $date);
	$insert = $stmt->execute();

	if($insert == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro se ha ingresado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// This controller delete a reply and his detail entries
$app->get('/v1/reply/delete-reply/{reply_id}', function($reply_id) use ($app){
	$sql = "DELETE FROM replicas WHERE id_replica = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $reply_id);
	$delete = $stmt->execute();

	if($delete == true){
		$sql_delete = "DELETE FROM detalle_replicas WHERE id_replica = ?";
		$stmt = $app['db']->prepare($sql_delete);
		$stmt->bindValue(1, $reply_id);
		$delete_reply = $stmt->execute();

		if($delete_reply == true){
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
		
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// This controller close a reply or validates it
$app->post('/v1/reply/close-a-reply', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$reply_id = $request->request->get('reply_id');
	$validation = $request->request->get('validation');
	$successful = $request->request->get('successful');
	$close_comment = $request->request->get('close_comment');

	if($validation != "" && $successful != ""){
		$sql = "UPDATE replicas SET validacion = ?, satisfactorio = ?, comentario_cierre = ? WHERE id_replica = ?";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $validation);
		$stmt->bindValue(2, $successful);
		$stmt->bindValue(3, $close_comment);
		$stmt->bindValue(4, $reply_id);
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
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Los datos de Acción y Resultado de cierre son obligatorios."
		);
	}

	return $app->json($response);
});


// This controller gets the general data for the reply report
$app->get('/v1/reply/get-general-data/{report_id}', function($report_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('(SELECT e.nombre_comercial FROM empresas AS e LEFT JOIN r_clientes_plantas_proveedores AS rcpp ON rcpp.id_empresa = e.id_empresa WHERE rcpp.id_planta_proveedor = cr.id_cliente) AS client,
					cr.id_cuestionario_respondido AS report_id,
					e.nombre_comercial AS branch, 
					c.codigo AS questionnaire_code, 
					c.nombre AS questionnaire_name, 
					CONCAT(u.nombre,\' \',u.apellido_paterno,\' \',u.apellido_materno) AS auditor, 
					cr.hora_inicio AS start_time, 
					cr.fecha_de_inicio AS start_date, 
					cr.hora_finalizacion AS end_time, 
					cr.fecha_finalizacion AS end_date,
					cr.fecha_auditoria AS audit_date,
					cr.fecha_vencimiento AS due_date,
					(SELECT TRUNCATE(((SELECT COUNT(*) AS total_success FROM replicas WHERE id_cuestionario_respondido = ? AND satisfactorio = 1) * 100) / (SELECT COUNT(*) AS total FROM replicas WHERE id_cuestionario_respondido = ?), 2)) AS percentage_compliance_level,
					(SELECT TRUNCATE((((SELECT COUNT(DISTINCT(id_pregunta)) AS total_added FROM replicas WHERE id_cuestionario_respondido = ?) * 100) / (SELECT COUNT(*) AS total FROM respuestas_cuestionarios WHERE valor < 100 AND id_cuestionario_respondido = ?)), 2)) AS percentage_of_replies,
					(SELECT COUNT(*) AS total FROM respuestas_cuestionarios WHERE valor < 100 AND id_cuestionario_respondido = ?) AS total_nonconformities,
					(SELECT COUNT(DISTINCT(id_pregunta)) AS total_added FROM replicas WHERE id_cuestionario_respondido = ?) AS total_replies_added')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
		->leftJoin('cr', 'cuestionarios', 'c', 'c.id_cuestionario = cr.id_cuestionario')
		->leftJoin('cr', 'usuarios', 'u', 'u.id_usuario = cr.id_auditor')
		->where('cr.id_cuestionario_respondido = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $report_id);
	$stmt->bindValue(2, $report_id);
	$stmt->bindValue(3, $report_id);
	$stmt->bindValue(4, $report_id);
	$stmt->bindValue(5, $report_id);
	$stmt->bindValue(6, $report_id);
	$stmt->bindValue(7, $report_id);
	$stmt->execute();
	$report_data = $stmt->fetch();

	return $app->json($report_data);
});


// This controller delete a comment for a reply
$app->get('/v1/reply/delete-reply-comment/{comment_id}', function($comment_id) use ($app){
	$sql = "DELETE FROM detalle_replicas WHERE id_detalle_replica = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $comment_id);
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


// This controller sets the status of an opened reply
$app->get('/v1/reply/set-opened-status/{user_id}/{reply_id}/{report_id}', function($user_id, $reply_id, $report_id) use ($app){
	$status = 1;
	$date = date('Y-m-d H:i:s');

	// Checking if the reply was already opened
	$sql_check = "SELECT COUNT(*) AS status FROM replicas_abiertas WHERE id_usuario = ? AND id_replica = ? AND id_reporte = ?";
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $user_id);
	$stmt->bindValue(2, $reply_id);
	$stmt->bindValue(3, $report_id);
	$stmt->execute();
	$exist = $stmt->fetch();

	if($exist['status'] == 0){
		$sql = "INSERT INTO replicas_abiertas (id_usuario, id_replica, id_reporte, estado, fecha_apertura) VALUES (?, ?, ?, ?, ?)";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $user_id);
		$stmt->bindValue(2, $reply_id);
		$stmt->bindValue(3, $report_id);
		$stmt->bindValue(4, $status);
		$stmt->bindValue(5, $date);
		$insert = $stmt->execute();

		if($insert == true){
			$response = array(
				"result_code" => 1,
				"message" => "Se ha asignado el estado de la replica de forma exitosa."
			);
		}else{
			$response = array(
				"result_code" => 0,
				"message" => "Ha ocurrido un error, intente de nuevo más tarde."
			);
		}
	}

	return $app->json($response);
});


// This controller close all the reports that has expired
$app->get('/v1/reports/close-expired-reports', function() use ($app){
	$due_date = date('Y-m-d');
	$status = 1;

	// This controller is called everyday from the dashboard screen
	$sql_reports = $app['db']->createQueryBuilder();
	$sql_reports
		->select('cr.id_cuestionario_respondido AS report_id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.fecha_vencimiento = ?');
	$stmt = $app['db']->prepare($sql_reports);
	$stmt->bindValue(1, $due_date);
	$reports = $stmt->fetchAll();

	if(count($reports) > 0){
		for($i = 0; $i < count($reports); $i++){
			$sql = "UPDATE cuestionarios_respondidos SET cerrar_replicas = ? WHERE id_cuestionario_respondido = ?";
			$stmt = $app['db']->prepare($sql);
			$stmt->bindValue(1, $status);
			$stmt->bindValue(2, $reports[$i]['report_id']);
			$update = $stmt->execute();

			if($update == true){
				$response = array(
					"result_code" => 1,
					"message" => "El estado del reporte ha sido cambiado exitosamente."
				);
			}else{
				$response = array(
					"result_code" => 0,
					"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
				);
			}
		}	
	}
	
	return $app->json($response);
});


// This controller set the status of a reply comment by user (If was opened)
$app->get('/v1/reply/set-reply-comment-status/{user_id}/{reply_detail_id}', function($user_id, $reply_detail_id) use ($app){
	// Checking if the reply detail and the user already exist on table
	$sql_exist = "SELECT COUNT(*) AS exist FROM comentarios_replicas_abiertos WHERE id_detalle_replica = ? AND id_usuario = ?";
	$stmt = $app['db']->prepare($sql_exist);
	$stmt->bindValue(1, $reply_detail_id);
	$stmt->bindValue(2, $user_id);
	$stmt->execute();
	$exist = $stmt->fetch();

	if($exist['exist'] == 0){
		// If does not exist insert the record
		$sql_insert = "INSERT INTO comentarios_replicas_abiertos (id_detalle_replica, id_usuario, estado) VALUES (?, ?, ?)";
		$stmt = $app['db']->prepare($sql_insert);
		$stmt->bindValue(1, $reply_detail_id);
		$stmt->bindValue(2, $user_id);
		$stmt->bindValue(3, '1');
		$insert = $stmt->execute();

		if($insert == true){
			$response = array(
				"result_code" => 1,
				"message" => "El registro ha sido ingresado exitosamente."
			);
		}else{
			$response = array(
				"result_code" => 0,
				"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
			);
		}
	}

	return $app->json($response);
});

