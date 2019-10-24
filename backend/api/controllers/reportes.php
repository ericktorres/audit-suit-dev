<?php

require("lib/phpToPDF.php");
use Symfony\Component\HttpFoundation\Request;

// This controller gets the answered questionnaire data of a client and auditor
$app->get('/v1/reports/general-data/{answered_questionnaire_id}', function($answered_questionnaire_id) use ($app){


//fecha_inicia_auditoria, fecha_termina_auditoria, hora_inicio, hora_finalizacion

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('(SELECT e.nombre_comercial FROM empresas AS e LEFT JOIN r_clientes_plantas_proveedores AS rcpp 
 ON rcpp.id_empresa = e.id_empresa WHERE rcpp.id_planta_proveedor = cr.id_cliente) AS client,
					cr.id_cuestionario_respondido AS report_id,
					e.nombre_comercial AS branch,
					cr.liberado AS process_report, 
					c.codigo AS questionnaire_code, 
					c.nombre AS questionnaire_name, 
					CONCAT(u.nombre,\' \',u.apellido_paterno,\' \',u.apellido_materno) AS auditor, 
					cr.hora_inicio AS start_time, 
					cr.fecha_inicia_auditoria AS start_date,
					cr.auditor_atiende AS audit_atiende,
					cr.hora_finalizacion AS end_time, 
					cr.firma AS firma,
					cr.firma_auditor AS firma2,
					cr.fecha_termina_auditoria AS end_date,
					cr.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
		->leftJoin('cr', 'cuestionarios', 'c', 'c.id_cuestionario = cr.id_cuestionario')
		->leftJoin('cr', 'usuarios', 'u', 'u.id_usuario = cr.id_auditor')
		->where('cr.id_cuestionario_respondido = ?');
	/*$sql
		->select('(SELECT e.nombre_comercial FROM empresas AS e LEFT JOIN r_clientes_plantas_proveedores AS rcpp 
 ON rcpp.id_empresa = e.id_empresa WHERE rcpp.id_planta_proveedor = cr.id_cliente) AS client,
					cr.id_cuestionario_respondido AS report_id,
					e.nombre_comercial AS branch, 
					c.codigo AS questionnaire_code, 
					c.nombre AS questionnaire_name, 
					CONCAT(u.nombre,\' \',u.apellido_paterno,\' \',u.apellido_materno) AS auditor, 
					cr.hora_inicio AS start_time, 
					cr.fecha_de_inicio AS start_date, 
					cr.hora_finalizacion AS end_time, 
					cr.fecha_finalizacion AS end_date,
					cr.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
		->leftJoin('cr', 'cuestionarios', 'c', 'c.id_cuestionario = cr.id_cuestionario')
		->leftJoin('cr', 'usuarios', 'u', 'u.id_usuario = cr.id_auditor')
		->where('cr.id_cuestionario_respondido = ?'); */
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->execute();
	$report_data = $stmt->fetch();

	return $app->json($report_data);
});

// This controller get the score by section of a answered questionnaire
$app->get('/v1/reports/score-by-section/{answered_questionnaire_id}', function($answered_questionnaire_id) use ($app){

	$not_apply = 0;
	
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('sc.nombre_seccion AS section, sc.valor AS value, SUM(rc.valor) AS score')
		->from('respuestas_cuestionarios', 'rc')
		->leftJoin('rc', 'secciones_cuestionario', 'sc', 'sc.id_seccion = rc.id_seccion')
		->where('rc.id_cuestionario_respondido = ?')
		->andWhere('rc.no_aplica = ?')
		->groupBy('rc.id_seccion');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->bindValue(1, $not_apply);
	$stmt->execute();
	$result = $stmt->fetchAll();

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the percentage by section
$app->get('/v1/reports/percentage-by-section/{answered_questionnaire_id}', function($answered_questionnaire_id) use($app){

	$not_apply = 0;

	// Getting the questionnaire id
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('cr.id_cuestionario AS id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cuestionario_respondido = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->execute();
	$questionnaire_id = $stmt->fetch();

	// Getting sections name
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id['id']);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();

	for($i=0; $i<count($sections); $i++){
		$sql_percentage = $app['db']->createQueryBuilder();
		/*
		$sql_percentage
			->select('TRUNCATE((SUM(rc.puntaje) * 100 / sc.valor), 2) AS section_percentage, TRUNCATE(SUM(rc.puntaje), 2) AS got_value')
			->from('respuestas_cuestionarios', 'rc')
			->leftJoin('rc', 'secciones_cuestionario', 'sc', 'sc.id_seccion = rc.id_seccion')
			->where('rc.id_seccion = ?')
			->andWhere('rc.id_cuestionario_respondido = ?')
			->andWhere('rc.no_aplica = ?');
		*/
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
											WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$sections[$i]['section_id']." AND rc.id_cuestionario_respondido = $answered_questionnaire_id
										), 0)
									)
								), 2
							) AS section_percentage, TRUNCATE(SUM(rc.puntaje), 2) AS got_value FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$sections[$i]['section_id']." AND rc.id_cuestionario_respondido = $answered_questionnaire_id AND rc.no_aplica = 0";

		$stmt = $app['db']->prepare($sql_percentage);
		//$stmt->bindValue(1, $sections[$i]['section_id']);
		//$stmt->bindValue(2, $answered_questionnaire_id);
		//$stmt->bindValue(3, $not_apply);
		$stmt->execute();
		$percentage_got = $stmt->fetch();

		$result[$i]['section'] = $sections[$i]['section_name'];
		$result[$i]['value'] = $sections[$i]['value'];
		$result[$i]['got_value'] = $percentage_got['got_value'];
		$result[$i]['score'] = $percentage_got['section_percentage'];
	}

	return $app->json($result);
});


// This controller get the score by section of a answered questionnaire test
$app->get('/v1/reports/score-by-section-chart/{answered_questionnaire_id}', function($answered_questionnaire_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('sc.nombre_seccion AS label, SUM(rc.valor) AS value')
		->from('respuestas_cuestionarios', 'rc')
		->leftJoin('rc', 'secciones_cuestionario', 'sc', 'sc.id_seccion = rc.id_seccion')
		->where('rc.id_cuestionario_respondido = ?')
		->groupBy('rc.id_seccion');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->execute();
	$result = $stmt->fetchAll();

	return $app->json($result)->setEncodingOptions(JSON_NUMERIC_CHECK);
});


// This controller gets the section and the score of each one to build the chart in the PDF file
$app->get('/v1/reports/percentage-by-section-chart/{answered_questionnaire_id}', function($answered_questionnaire_id) use ($app){
	$not_apply = 0;

	// Getting the questionnaire
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('cr.id_cuestionario AS id')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.id_cuestionario_respondido = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->execute();
	$questionnaire_id = $stmt->fetch();

	// Getting sections name
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, $questionnaire_id['id']);
	$stmt->execute();
	$sections = $stmt->fetchAll();

	$result = array();

	for($i=0; $i<count($sections); $i++){
		$sql_percentage = $app['db']->createQueryBuilder();
		$sql_percentage
			->select('(SUM(rc.valor) * 100) / (COUNT(*) * 100) AS section_percentage')
			->from('respuestas_cuestionarios', 'rc')
			->where('rc.id_seccion = ?')
			->andWhere('rc.id_cuestionario_respondido = ?')
			->andWhere('rc.no_aplica = ?');
		$stmt = $app['db']->prepare($sql_percentage);
		$stmt->bindValue(1, $sections[$i]['section_id']);
		$stmt->bindValue(2, $answered_questionnaire_id);
		$stmt->bindValue(3, $not_apply);
		$stmt->execute();
		$percentage_got = $stmt->fetch();

		$result[$i]['label'] = $sections[$i]['section_name'];
		//$result[$i]['value'] = $sections[$i]['value'];
		$result[$i]['value'] = $percentage_got['section_percentage'];
	}

	return $app->json($result);
});


// This controller gets the question where the client did not get the 100% en informe preliminar
$app->get('/v1/reports/opportunity-areas-informe/{answered_questionnaire_id}', function($answered_questionnaire_id) use ($app){

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('rc.id_respuesta AS answer_id, sc.nombre_seccion AS section, p.pregunta AS question, p.texto_ayuda AS help_text, rop.opcion AS selected_option, rc.valor AS value, rc.observaciones_auditor AS observations, rc.no_conformidad AS nonconformity')
		->from('informe_preliminar', 'rc')
		->leftJoin('rc', 'r_opciones_preguntas', 'rop', 'rop.id_opcion = rc.id_opcion')
		->leftJoin('rc', 'preguntas', 'p', 'p.id_pregunta = rc.id_pregunta')
		->leftJoin('p', 'secciones_cuestionario', 'sc', 'sc.id_seccion = p.id_seccion')
		->where('rc.id_cuestionario_respondido = ?')
		->andWhere('rc.valor < 100');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->execute();
	$wrong_questions = $stmt->fetchAll();

	return $app->json($wrong_questions);


});



// This controller gets the question where the client did not get the 100%
$app->get('/v1/reports/opportunity-areas/{answered_questionnaire_id}', function($answered_questionnaire_id) use ($app){

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('rc.id_respuesta AS answer_id, sc.nombre_seccion AS section, p.pregunta AS question, p.texto_ayuda AS help_text, rop.opcion AS selected_option, rc.valor AS value, rc.observaciones_auditor AS observations, rc.no_conformidad AS nonconformity')
		->from('respuestas_cuestionarios', 'rc')
		->leftJoin('rc', 'r_opciones_preguntas', 'rop', 'rop.id_opcion = rc.id_opcion')
		->leftJoin('rc', 'preguntas', 'p', 'p.id_pregunta = rc.id_pregunta')
		->leftJoin('p', 'secciones_cuestionario', 'sc', 'sc.id_seccion = p.id_seccion')
		->where('rc.id_cuestionario_respondido = ?')
		->andWhere('rc.valor < 100');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->execute();
	$wrong_questions = $stmt->fetchAll();

	return $app->json($wrong_questions);
});


// This controller gets all the question of the report
$app->get('/v1/reports/questions/{answered_questionnaire_id}', function($answered_questionnaire_id) use ($app){

	$sql = $app['db']->createQueryBuilder();
	$sql='select rc.id_respuesta AS answer_id, sc.nombre_seccion AS section,sc.id_seccion AS id_section 
from respuestas_cuestionarios AS  rc
left join r_opciones_preguntas AS rop on rop.id_opcion = rc.id_opcion
left join preguntas AS p on p.id_pregunta = rc.id_pregunta
left join secciones_cuestionario AS sc on sc.id_seccion = p.id_seccion
where rc.id_cuestionario_respondido = ? group by id_section order by id_section asc';
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answered_questionnaire_id);
	$stmt->execute();

	$secciones =$stmt->fetchAll();

	for ($i=0; $i < count($secciones); $i++) { 

		$sql2 = $app['db']->createQueryBuilder();
		$sql2 ='select rc.id_respuesta AS answer_id, sc.nombre_seccion AS section,sc.id_seccion AS id_section, p.pregunta AS question, p.texto_ayuda AS help_text, rop.opcion AS selected_option, rc.valor AS value, rc.observaciones_auditor AS observations, rc.no_conformidad AS nonconformity, p.num_pregunta 
	from respuestas_cuestionarios AS  rc
	left join r_opciones_preguntas AS rop on rop.id_opcion = rc.id_opcion
	left join preguntas AS p on p.id_pregunta = rc.id_pregunta
	left join secciones_cuestionario AS sc on sc.id_seccion = p.id_seccion
	where rc.id_cuestionario_respondido = '.$answered_questionnaire_id.' and sc.id_seccion ='.$secciones[$i]['id_section'];
		$stmt2 = $app['db']->prepare($sql2);
		$stmt2->execute();

		$respuestas2 =$stmt2->fetchAll();
		$response[$i] = array('seccion_name' => $respuestas2[$i]['section'],
				'seccion_id' => $respuestas2[$i]['id_section'],
				'objeto' => $respuestas2,
		);
			/*$response[$i] = array(
				'section' => $respuestas2[$i]['section'],
					'answer_id' => $respuestas2[$i]['answer_id'],
				'id_section' => $respuestas2[$i]['id_section'],
				'question' => $respuestas2[$i]['question'],
				'help_text' => $respuestas2[$i]['help_text'],
				'selected_option' => $respuestas2[$i]['selected_option'],
				'value' => $respuestas2[$i]['value'],
				'observations' => $respuestas2[$i]['observations'],
				'nonconformity' => $respuestas2[$i]['nonconformity']						
			);*/
	}

	
	//$wrong_que[$i]  Arreglo que contiene las secciones en orden




//	for ($i=0; $i < count($respuestas2); $i++) {
		//if ($respuestas2[$i]['id_section'] == $respuestas[$i]['id_section']) {
			//$wrong_que[$i] = array('seccion' => $respuestas[$i]); // $respuestas2[$i];
		//}
		/*
		} */


		/*$response[$i] = array('answer_id' => $respuestas2[$i]['answer_id'],
						'id_section' => $respuestas2[$i]['id_section'],
						'question' => $respuestas2[$i]['question'],
						'help_text' => $respuestas2[$i]['help_text'],
						'selected_option' => $respuestas2[$i]['selected_option'],
						'value' => $respuestas2[$i]['value'],
						'observations' => $respuestas2[$i]['observations'],
						'nonconformity' => $respuestas2[$i]['nonconformity']						
					);*/
//	}






	return $app->json($response);


});


// This controller gets all the reports availables for a manager
$app->get('/v1/reports/get/{user_id}/{user_privileges}', function($user_id, $user_privileges) use ($app){
	// Getting the user type and knowing if is reviser
	$sql_user_data = $app['db']->createQueryBuilder();
	$sql_user_data
		->select('u.id_tipo_usuario AS user_type, u.es_revisor AS reviser, u.es_regional AS is_regional')
		->from('usuarios', 'u')
		->where('u.id_usuario = ?');
	$stmt = $app['db']->prepare($sql_user_data);
	$stmt->bindValue(1, $user_id);
	$stmt->execute();
	$user_data = $stmt->fetch();

	if($user_privileges == "2"){

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

		if($user_data['user_type'] == 1){
			// Getting the reports that are available for the manager
			$sql = $app['db']->createQueryBuilder();
			$sql = "SELECT cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,' ',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date, cr.fecha_auditoria AS audit_date, cr.liberado AS released, cr.cerrar_replicas AS close_replies
					FROM cuestionarios_respondidos AS cr
					LEFT JOIN cuestionarios AS c ON c.id_cuestionario = cr.id_cuestionario
					LEFT JOIN empresas AS e ON e.id_empresa = cr.id_cliente
					LEFT JOIN usuarios AS u ON u.id_usuario = cr.id_auditor
					WHERE cr.id_cliente IN ($ids)
					ORDER BY cr.fecha_auditoria ASC";
		}else if($user_data['user_type'] == 2){
			if($user_data['reviser'] == 1){
				// Getting the reports that are available for the manager
				$sql = $app['db']->createQueryBuilder();
				$sql = "SELECT cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,' ',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date, cr.fecha_auditoria AS audit_date, cr.liberado AS released, cr.cerrar_replicas AS close_replies
						FROM cuestionarios_respondidos AS cr
						LEFT JOIN cuestionarios AS c ON c.id_cuestionario = cr.id_cuestionario
						LEFT JOIN empresas AS e ON e.id_empresa = cr.id_cliente
						LEFT JOIN usuarios AS u ON u.id_usuario = cr.id_auditor
						WHERE cr.id_cliente IN ($ids)
						ORDER BY cr.fecha_auditoria ASC";
			}else if($user_data['is_regional'] == 1){
				// Getting the reports that are available for the regional manager
				$sql = $app['db']->createQueryBuilder();
				$sql = "SELECT cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,' ',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date, cr.fecha_auditoria AS audit_date, cr.liberado AS released, cr.cerrar_replicas AS close_replies
						FROM cuestionarios_respondidos AS cr
						LEFT JOIN cuestionarios AS c ON c.id_cuestionario = cr.id_cuestionario
						LEFT JOIN empresas AS e ON e.id_empresa = cr.id_cliente
						LEFT JOIN usuarios AS u ON u.id_usuario = cr.id_auditor
						WHERE cr.id_cliente IN ($ids) AND cr.estado = 1
						ORDER BY cr.fecha_auditoria ASC"; //AND cr.liberado = 1
			}else{
				// Getting the reports that are available for the manager
				$sql = $app['db']->createQueryBuilder();
				/*$sql = "SELECT cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,' ',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date, cr.fecha_auditoria AS audit_date, cr.liberado AS released, cr.cerrar_replicas AS close_replies
						FROM cuestionarios_respondidos AS cr
						LEFT JOIN cuestionarios AS c ON c.id_cuestionario = cr.id_cuestionario
						LEFT JOIN empresas AS e ON e.id_empresa = cr.id_cliente
						LEFT JOIN usuarios AS u ON u.id_usuario = cr.id_auditor
						WHERE cr.id_cliente IN ($ids) AND cr.liberado = 1 AND cr.estado = 2
						ORDER BY cr.fecha_auditoria ASC";*/
				$sql = "SELECT cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,' ',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date, cr.fecha_auditoria AS audit_date, cr.liberado AS released, cr.cerrar_replicas AS close_replies
						FROM cuestionarios_respondidos AS cr
						LEFT JOIN cuestionarios AS c ON c.id_cuestionario = cr.id_cuestionario
						LEFT JOIN empresas AS e ON e.id_empresa = cr.id_cliente
						LEFT JOIN usuarios AS u ON u.id_usuario = cr.id_auditor
						WHERE cr.id_cliente IN ($ids) AND cr.liberado = 3 AND cr.estado = 1
						ORDER BY cr.fecha_auditoria ASC";
			}
			
		}
		
		$stmt = $app['db']->prepare($sql);
		$stmt->execute();
		$reports = $stmt->fetchAll();

		$result = $reports;
	}elseif($user_privileges == "3"){
		$sql_plants = $app['db']->createQueryBuilder();
		$sql_plants
			->select('rae.id_empresa AS id')
			->from('r_auditores_empresas', 'rae')
			->where('rae.id_auditor = ?');
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

		$sql = $app['db']->createQueryBuilder();
		$sql = "SELECT cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,' ',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date, cr.fecha_auditoria AS audit_date, cr.liberado AS released, cr.cerrar_replicas AS close_replies
				FROM cuestionarios_respondidos AS cr
				LEFT JOIN cuestionarios AS c ON c.id_cuestionario = cr.id_cuestionario
				LEFT JOIN empresas AS e ON e.id_empresa = cr.id_cliente
				LEFT JOIN usuarios AS u ON u.id_usuario = cr.id_auditor
				WHERE cr.id_cliente IN ($ids) AND cr.id_auditor = $user_id
				ORDER BY cr.fecha_auditoria ASC";
		$stmt = $app['db']->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();

	}elseif($user_privileges == "1"){
		$sql = $app['db']->createQueryBuilder();
		$sql
			->select('cr.id_cuestionario_respondido AS report_id, cr.id_cuestionario AS questionnaire_id, c.nombre AS questionnaire_name, c.codigo AS questionnaire_code, cr.id_cliente AS client_id, e.nombre_comercial AS client_name, cr.id_auditor AS auditor_id, CONCAT(u.nombre,\' \',u.apellido_paterno) AS auditor_name, cr.fecha_finalizacion AS application_date, cr.fecha_auditoria AS audit_date, cr.liberado AS released, cr.cerrar_replicas AS close_replies')
			->from('cuestionarios_respondidos', 'cr')
			->leftJoin('cr', 'cuestionarios', 'c', 'c.id_cuestionario = cr.id_cuestionario')
			->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
			->leftJoin('cr', 'usuarios', 'u', 'u.id_usuario = cr.id_auditor')
			->orderBy('cr.fecha_auditoria', 'ASC');
		$stmt = $app['db']->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
	}

	$main_result = array();

	for($i=0; $i<count($result); $i++){
		// Getting the report status (If was opened)
		$sql_status = "SELECT COUNT(*) AS opened FROM reportes_abiertos WHERE id_reporte = ? AND id_usuario = ?";
		$stmt = $app['db']->prepare($sql_status);
		$stmt->bindValue(1, $result[$i]['report_id']);
		$stmt->bindValue(2, $user_id);
		$stmt->execute();
		$report_status = $stmt->fetch();

		// Getting the compliance leve for replies
		$sql_compliance_level = "SELECT TRUNCATE(((SELECT COUNT(*) AS total_success FROM replicas WHERE id_cuestionario_respondido = ".$result[$i]['report_id']." AND satisfactorio = 1) * 100) / (SELECT COUNT(*) AS total FROM replicas WHERE id_cuestionario_respondido = ".$result[$i]['report_id']."), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_compliance_level);
		$stmt->execute();
		$reply_compliance_level = $stmt->fetch();

		// Getting the percentage of replies added based in the number of opportunity areas
		$sql_replies_added = "SELECT TRUNCATE((((SELECT COUNT(DISTINCT(id_pregunta)) AS total_added FROM replicas WHERE id_cuestionario_respondido = ".$result[$i]['report_id'].") * 100) / (SELECT COUNT(*) AS total FROM respuestas_cuestionarios WHERE valor < 100 AND id_cuestionario_respondido = ".$result[$i]['report_id'].")), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_replies_added);
		$stmt->execute();
		$replies_added = $stmt->fetch();

		// Getting the number of new reply for the logged user
		$sql_new_reply = "SELECT (SELECT COUNT(*) FROM replicas WHERE id_cuestionario_respondido = ".$result[$i]['report_id'].") - (SELECT COUNT(*) AS opened FROM replicas_abiertas WHERE id_reporte = ".$result[$i]['report_id']." AND id_usuario = ".$user_id.") AS new_reply";
		$stmt = $app['db']->prepare($sql_new_reply);
		$stmt->execute();
		$new_reply = $stmt->fetch();

		$main_result[$i]['report_id'] = $result[$i]['report_id'];
		$main_result[$i]['questionnaire_id'] = $result[$i]['questionnaire_id'];
		$main_result[$i]['questionnaire_name'] = $result[$i]['questionnaire_name'];
		$main_result[$i]['questionnaire_code'] = $result[$i]['questionnaire_code'];
		$main_result[$i]['client_id'] = $result[$i]['client_id'];
		$main_result[$i]['client_name'] = $result[$i]['client_name'];
		$main_result[$i]['auditor_id'] = $result[$i]['auditor_id'];
		$main_result[$i]['auditor_name'] = $result[$i]['auditor_name'];
		$main_result[$i]['application_date'] = $result[$i]['application_date'];
		$main_result[$i]['audit_date'] = $result[$i]['audit_date'];
		$main_result[$i]['report_status'] = $report_status['opened'];
		$main_result[$i]['released'] = $result[$i]['released'];
		$main_result[$i]['reply_compliance_level'] = $reply_compliance_level['percentage'];
		$main_result[$i]['replies_added'] = $replies_added['percentage'];
		$main_result[$i]['close_replies'] = $result[$i]['close_replies'];
		$main_result[$i]['new_reply'] = $new_reply['new_reply'];
	}

	return $app->json($main_result);

});








// This controller generates the report that is sent to the clients
$app->get('/v1/report/{report_id}', function($report_id) use ($app){

	return $app['twig']->render('report.html.twig', array(
        'report_id' => $report_id,
    ));
});


// This controller sends the report by email
$app->get('/v1/report/send-by-email/{report_id}', function($report_id) use ($app){
	// Getting the users email
	$sql_client = "SELECT id_cliente AS client_id FROM cuestionarios_respondidos WHERE id_cuestionario_respondido = $report_id";
	$stmt = $app['db']->prepare($sql_client);
	$stmt->execute();
	$client_id = $stmt->fetch();

	$sql_emails = "SELECT u.id_usuario AS id, u.correo_electronico AS email
					FROM usuarios AS u
					LEFT JOIN r_gerentes_empresas AS rge ON rge.id_gerente = u.id_usuario
					WHERE rge.id_empresa = ".$client_id['client_id']." ORDER BY u.id_usuario ASC";
	$stmt = $app['db']->prepare($sql_emails);
	$stmt->execute();
	$emails = $stmt->fetchAll();
	$separator = ', ';
	$to = '';

	for($i=0; $i<count($emails); $i++){
		//if($i == $total){ $separator = ''; }else{ $separator = ', '; }

		$to .= $emails[$i]['email'] . $separator;
	}
	
	$subject = 'Reporte No '.$report_id;
	
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: Reportes Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
	$headers .= 'Cc: raul.romo@bh-cg.com.mx, ericktorres87@gmail.com' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $message = '<!DOCTYPE html>';
    $message .= '<html>';
    $message .= '<head>';
    $message .= '<title>Audit Suit - Reporte No '.$report_id.'</title>';
    $message .= '</head>';
    $message .= '<body>';
    $message .= '<table width="650">
					<tbody>
						<tr>
							<td>Estimado cliente,</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td>un nuevo reporte est&aacute; disponible para usted, <b>Reporte No '.$report_id.'</b>, para consultarlo inicie sesi&oacute;n en la plataforma y dirijase a la secci&oacute;n de Reportes.
							</a></td>
						</tr>
						<tr>
							<td><a href="https://bluehand.com.mx/console">Audit Suit</a></td>
						</tr>
					</tbody>
				</table>';
    $message .= '</body>';
    $message .= '</html>';

    //consultelo en el siguiente enlace: <a href="http://bluehand.com.mx/backend/api/v1/report/'.$report_id.'">http://bluehand.com.mx/backend/api/v1/report/'.$report_id.

	$send = mail($to, $subject, $message, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	
});


// This controller generates the report in PDF file
$app->get('/v1/report/generate-pdf/{report_id}', function($report_id) use ($app){

	//$html = file_get_contents('http://bluehand.com.mx/console/reports/create_pdf.php?report_id='.$report_id);

	if(file_exists('../../console/reports/report-no-'.$report_id.'.pdf')){
		$result = array(
			"message" => "El reporte No ".$report_id." ya se ha generado.",
			"report_url" => "https://bluehand.com.mx/console/reports/report-no-".$report_id.".pdf"
		);
	}else{
		$pdf_options = array(
			"source_type" => 'url',
		  	"source" => 'https://bluehand.com.mx/console/reports/create_pdf.php?report_id='.$report_id,
		  	"action" => 'save',
		  	"save_directory" => '../../console/reports/',
		  	"file_name" => 'report-no-'.$report_id.'.pdf'
		);

		/*$pdf_options = array(
	    	"source_type" => 'html',
	      	"source" => $html,
	      	"action" => 'save',
	      	"save_directory" => '../../console/reports/',
	      	"file_name" => 'report-no-'.$report_id.'.pdf'
	    );*/

		phptopdf($pdf_options);

		$result = array(
			"message" => "Se ha generado el reporte No ".$report_id,
			"report_url" => "https://bluehand.com.mx/console/reports/report-no-".$report_id.".pdf"
		);
	}

	return $app->json($result);

});


// This controller delete a report from database
$app->get('/v1/report/delete/{report_id}', function($report_id) use ($app){
	// Deleting report detail
	$sql = "DELETE FROM respuestas_cuestionarios WHERE id_cuestionario_respondido = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $report_id);
	$delete = $stmt->execute();

	if($delete == true){
		$sql_delete = "DELETE FROM cuestionarios_respondidos WHERE id_cuestionario_respondido = ?";
		$stmt = $app['db']->prepare($sql_delete);
		$stmt->bindValue(1, $report_id);
		$delete_main = $stmt->execute();

		if($delete_main == true){
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


// This controller release a report
$app->get('/v1/report/release-report/{report_id}', function($report_id) use ($app){
	$sql_release = "UPDATE cuestionarios_respondidos SET liberado = 3 WHERE id_cuestionario_respondido = ?";
	$stmt = $app['db']->prepare($sql_release);
	$stmt->bindValue(1, $report_id);
	$release = $stmt->execute();

	if($release == true){
		$response = array(
			"result_code" => 1,
			"message" => "El reporte se ha liberado exitosamente"
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
		);	
	}

	return $app->json($response);
});


// This controller get one answer from the selected report
$app->get('/v1/report/answer/get/{answer_id}', function($answer_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('rc.id_respuesta AS answer_id, rc.id_pregunta AS question_id, p.ponderacion AS question_value, rc.id_opcion AS selected_option_id, sc.nombre_seccion AS section, p.pregunta AS question, rop.opcion AS selected_option, rc.valor AS value, rc.observaciones_auditor AS observations, rc.no_conformidad AS nonconformity, rc.no_aplica AS not_apply')
		->from('respuestas_cuestionarios', 'rc')
		->leftJoin('rc', 'r_opciones_preguntas', 'rop', 'rop.id_opcion = rc.id_opcion')
		->leftJoin('rc', 'preguntas', 'p', 'p.id_pregunta = rc.id_pregunta')
		->leftJoin('p', 'secciones_cuestionario', 'sc', 'sc.id_seccion = p.id_seccion')
		->where('rc.id_respuesta = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $answer_id);
	$stmt->execute();
	$answer = $stmt->fetch();

	// Getting question options
	$sql_options = $app['db']->createQueryBuilder();
	$sql_options
		->select('rop.id_opcion AS option_id, rop.opcion AS option_desc, rop.valor AS option_value')
		->from('r_opciones_preguntas', 'rop')
		->where('rop.id_pregunta = ?');
	$stmt = $app['db']->prepare($sql_options);
	$stmt->bindValue(1, $answer['question_id']);
	$stmt->execute();
	$question_options = $stmt->fetchAll();

	$result = array();
	$result['answer_id'] = $answer['answer_id'];
	$result['question_id'] = $answer['question_id'];
	$result['question_value'] = $answer['question_value'];
	$result['selected_option_id'] = $answer['selected_option_id'];
	$result['section'] = $answer['section'];
	$result['question'] = $answer['question'];
	$result['selected_option'] = $answer['selected_option'];
	$result['value'] = $answer['value'];
	$result['observations'] = $answer['observations'];
	$result['nonconformity'] = $answer['nonconformity'];
	$result['not_apply'] = $answer['not_apply'];
	$result['question_options'] = $question_options;


	return $app->json($result);
});


// This controller edit a report answer
$app->post('/v1/report/answer/edit', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$answer_id = $request->request->get('answer_id');
	$option_id = $request->request->get('option_id');
	$score = $request->request->get('score');
	$value = $request->request->get('value');
	$observations = $request->request->get('observations');
	$nonconformity = $request->request->get('nonconformity');
	$not_apply = $request->request->get('not_apply');

	$sql_update = "UPDATE respuestas_cuestionarios SET 
					id_opcion = ?,
					puntaje = ?,
					valor = ?,
					observaciones_auditor = ?,
					no_conformidad = ?,
					no_aplica = ?
					WHERE id_respuesta = ?";
	$stmt = $app['db']->prepare($sql_update);
	$stmt->bindValue(1, $option_id);
	$stmt->bindValue(2, $score);
	$stmt->bindValue(3, $value);
	$stmt->bindValue(4, $observations);
	$stmt->bindValue(5, $nonconformity);
	$stmt->bindValue(6, $not_apply);
	$stmt->bindValue(7, $answer_id);
	$update = $stmt->execute();

	if($update == true){
		$response = array(
			"result_code" => 1,
			"message" => "El registro se ha modificado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error, intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// This controller send a report to review
$app->get('/v1/report/send-to-review/{report_id}', function($report_id) use ($app){

	$sql = "UPDATE cuestionarios_respondidos SET liberado = 1 WHERE id_cuestionario_respondido = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $report_id);
	$update = $stmt->execute();

	if($update == true){
		$response = array(
			"result_code" => 1,
			"message" => "Se ha enviado el reporte a revisión."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error, intente de nuevo más tarde."
		);
	}

	return $app->json($response);

});


// This controller approves a report for release
$app->get('/v1/report/approves-for-release/{report_id}', function($report_id) use ($app){

	$sql = "UPDATE cuestionarios_respondidos SET liberado = 2 WHERE id_cuestionario_respondido = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $report_id);
	$update = $stmt->execute();

	if($update == true){
		$response = array(
			"result_code" => 1,
			"message" => "El reporte ha sido aprobado exitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error, intente de nuevo más tarde."
		);
	}

	return $app->json($response);

});

$app->post('/v1/report/assign-audit-date', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$report_id = $request->request->get('report_id');
	$audit_date = $request->request->get('audit_date');

	$sql = "UPDATE cuestionarios_respondidos SET fecha_auditoria = ? WHERE id_cuestionario_respondido = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $audit_date);
	$stmt->bindValue(2, $report_id);
	$update = $stmt->execute();

	if($update == true){
		$response = array(
			"result_code" => 1,
			"message" => "La fecha de auditoría se ha asignado éxitosamente."
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Ha ocurrido un error, intente de nuevo más tarde."
		);
	}

	return $app->json($response);
});


// This controller gets the amount of points that should be discounted from the total score in a questionnaire (NA Questions)
$app->get('/v1/report/get-na-questions-values/{report_id}', function($report_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('SUM(rc.puntaje) AS discount_points')
		->from('respuestas_cuestionarios', 'rc')
		->where('id_cuestionario_respondido = ?')
		->andWhere('no_aplica = 1');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $report_id);
	$stmt->execute();
	$discount_points = $stmt->fetch();

	return $app->json($discount_points);
});


// This controller set the status of a opened report
$app->post('/v1/report/set-status', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$report_id = $request->request->get('report_id');
	$user_id = $request->request->get('user_id');
	$status = 1;
	$open_date = date('Y-m-d H:i:s');

	// Checking if the report was already opened
	$sql_check = "SELECT COUNT(*) AS status FROM reportes_abiertos WHERE id_reporte = ? AND id_usuario = ?";
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $report_id);
	$stmt->bindValue(2, $user_id);
	$stmt->execute();
	$exist = $stmt->fetch();

	if($exist['status'] == 0){

		$sql = "INSERT INTO reportes_abiertos (id_reporte, id_usuario, estado, fecha_apertura) VALUES (?, ?, ?, ?)";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $report_id);
		$stmt->bindValue(2, $user_id);
		$stmt->bindValue(3, $status);
		$stmt->bindValue(4, $open_date);
		$insert = $stmt->execute();

		if($insert == true){
			$response = array(
				"result_code" => 1,
				"message" => "Se ha asignado el estado del reporte de forma exitosa."
			);
		}else{
			$response = array(
				"result_code" => 0,
				"message" => "Ha ocurrido un error, intente de nuevo más tarde."
			);
		}
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "El reporte ya había sido abierto con anterioridad."
		);
	}

	return $app->json($response);
});


// This controller manage the status for open or close a report for replies
$app->get('/v1/report/close-replies/{report_id}/{status}', function($report_id, $status) use ($app){
	// Validate if the report can be closed
	//$sql_validate = "SELECT (SELECT COUNT(*) FROM respuestas_cuestionarios WHERE valor < 100 AND id_cuestionario_respondido = $report_id) - (SELECT COUNT(*) FROM replicas WHERE validacion = 1 AND id_cuestionario_respondido = $report_id) AS opened_replies";
	$sql_validate = "SELECT (SELECT COUNT(*) FROM replicas WHERE id_cuestionario_respondido = $report_id) - (SELECT COUNT(*) FROM replicas WHERE validacion = 1 AND id_cuestionario_respondido = $report_id) AS opened_replies";
	$stmt = $app['db']->prepare($sql_validate);
	$stmt->execute();
	$validate = $stmt->fetch();

	if($validate['opened_replies'] <= 0){

		$sql = "UPDATE cuestionarios_respondidos SET cerrar_replicas = ? WHERE id_cuestionario_respondido = ?";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $status);
		$stmt->bindValue(2, $report_id);
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
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "Este reporte aún cuenta con réplicas abiertas, para cerrar el reporte asegúrese de concluir todas las réplicas."
		);
	}

	return $app->json($response);
});


// This controller assign the due date to a report (Is used for the reply module)
$app->get('/v1/report/assign-due-date/{report_id}/{due_date}', function($report_id, $due_date) use ($app){
	if($report_id != ""){
		$sql = "UPDATE cuestionarios_respondidos SET fecha_vencimiento = ? WHERE id_cuestionario_respondido = ?";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $due_date);
		$stmt->bindValue(2, $report_id);
		$update = $stmt->execute();

		if($update == true){
			$response = array(
				"result_code" => 1,
				"message" => "La fecha de vencimiento se ha asignado exitosamente."
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


// This controller storage the report result data in the reports table
$app->post('/v1/reports/save-report-data', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$report_id = $request->request->get('report_id');
	$client_name = $request->request->get('client_name');
	$plant_name = $request->request->get('plant_name');
	$auditor_name = $request->request->get('auditor_name');
	$total_score = $request->request->get('total_score');
	$audit_date = $request->request->get('audit_date');

	// Cheking if the report already exist
	$sql_exists = $app['db']->createQueryBuilder();
	$sql_exists
		->select('COUNT(*) AS exist')
		->from('reportes', 'r')
		->where('r.id_reporte = ?');
	$stmt = $app['db']->prepare($sql_exists);
	$stmt->bindValue(1, $report_id);
	$stmt->execute();
	$exist = $stmt->fetch();

	//Getting report data
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('r.id_reporte AS report_id, r.resultado_total AS total_score')
		->from('reportes', 'r')
		->where('r.id_reporte = ?')
		->andWhere('r.resultado_total = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $report_id);
	$stmt->bindValue(2, $total_score);
	$stmt->execute();
	$report = $stmt->fetch();

	if($exist['exist'] == 1){
		// Already exists just validate if the update is necesary
		if($total_score != $report['total_score']){
			// Update the total score
			$update_data = "UPDATE reportes SET resultado_total = ? WHERE id_reporte = ?";
			$stmt = $app['db']->prepare($update_data);
			$stmt->bindValue(1, $total_score);
			$stmt->bindValue(2, $report_id);
			$update = $stmt->execute();

			if($update == true){
				$response = array(
					"result_code" => 1,
					"message" => "El reporte $report_id se ha actualizado exitosamente."
				);
			}else{
				$response = array(
					"result_code" => 0,
					"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
				);
			}
		}
	}else{
		// Insert the report data
		$insert_data = "INSERT INTO reportes (id_reporte, cliente, planta_sucursal, auditor, resultado_total, fecha_auditoria) VALUES (?, ?, ?, ?, ?, ?)";
		$stmt = $app['db']->prepare($insert_data);
		$stmt->bindValue(1, $report_id);
		$stmt->bindValue(2, $client_name);
		$stmt->bindValue(3, $plant_name);
		$stmt->bindValue(4, $auditor_name);
		$stmt->bindValue(5, $total_score);
		$stmt->bindValue(6, $audit_date);
		$insert = $stmt->execute();

		if($insert == true){
			$response = array(
				"result_code" => 1,
				"message" => "El reporte $report_id ha sido ingresado exitosamente."
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


// This controller send the resume email to the managers
$app->get('/v1/reports/email-for-managers/{manager_id}/{manager_email}', function($manager_id, $manager_email) use ($app){
	// Getting the number of total reports
	$sql_total_reports = "SELECT COUNT(*) AS total_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_total_reports);
	$stmt->execute();
	$total_reports = $stmt->fetch();

	// Getting the number of reviewed reports
	$sql_reviewed_reports = "SELECT COUNT(*) AS reviewed_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE cr.estado = 1 AND cr.liberado = 0 AND rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_reviewed_reports);
	$stmt->execute();
	$reviewed_reports = $stmt->fetch();

	// Getting the number of approved reports
	$sql_approved_reports = "SELECT COUNT(*) AS approved_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE cr.estado = 2 AND cr.liberado = 0 AND rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_approved_reports);
	$stmt->execute();
	$approved_reports = $stmt->fetch();

	// Getting the number of released reports
	$sql_released_reports = "SELECT COUNT(*) AS released_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE cr.estado = 2 AND cr.liberado = 1 AND rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_released_reports);
	$stmt->execute();
	$released_reports = $stmt->fetch();

	// Getting the reports information
	$sql_reports_data = $app['db']->createQueryBuilder();
	$sql_reports_data
		->select('r.id_reporte AS report_id, r.planta_sucursal AS branch, r.resultado_total AS score, r.fecha_auditoria AS audit_date, (CASE WHEN r.resultado_total <= 70  THEN \'#FE2E2E\' WHEN r.resultado_total >= 71 && r.resultado_total <= 85 THEN \'#F6EC67\' WHEN r.resultado_total >= 86 && r.resultado_total < 95 THEN \'#22B718\' WHEN r.resultado_total >= 95 THEN \'#3D46F7\' END) AS color')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'reportes', 'r', 'r.id_reporte = cr.id_cuestionario_respondido')
		->leftJoin('cr', 'r_gerentes_empresas', 'rge', 'rge.id_empresa = cr.id_cliente')
		->where('rge.id_gerente = ?')
		->orderBy('score', 'DESC');
	$stmt = $app['db']->prepare($sql_reports_data);
	$stmt->bindValue(1, $manager_id);
	$stmt->execute();
	$reports_data = $stmt->fetchAll();

	// Getting the PAC information
	$sql_pac = $app['db']->createQueryBuilder();
	$sql_pac
		->select('r.id_reporte AS report_id, r.planta_sucursal AS branch, r.auditor AS auditor, r.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'reportes', 'r', 'r.id_reporte = cr.id_cuestionario_respondido')
		->leftJoin('cr', 'r_gerentes_empresas', 'rge', 'rge.id_empresa = cr.id_cliente ')
		->where('rge.id_gerente = ?')
		->orderBy('r.resultado_total', 'DESC');
	$stmt = $app['db']->prepare($sql_pac);
	$stmt->bindValue(1, $manager_id);
	$stmt->execute();
	$pac = $stmt->fetchAll();
	$pac_data = array();

	for($x=0; $x<count($pac); $x++){
		// Getting the pac detail
		$sql_pac_percentage = "SELECT TRUNCATE((((SELECT COUNT(DISTINCT(id_pregunta)) AS total_added FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].") * 100) / (SELECT COUNT(*) AS total FROM respuestas_cuestionarios WHERE valor < 100 AND id_cuestionario_respondido = ".$pac[$x]['report_id'].")), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_pac_percentage);
		$stmt->execute();
		$pac_percentage = $stmt->fetch();

		$sql_pac_accomplishment = "SELECT TRUNCATE(((SELECT COUNT(*) AS total_success FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id']." AND satisfactorio = 1) * 100) / (SELECT COUNT(*) AS total FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id']."), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_pac_accomplishment);
		$stmt->execute();
		$pac_accomplishment = $stmt->fetch();

		$sql_new_pac = "SELECT (SELECT COUNT(*) FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].") - (SELECT COUNT(*) AS opened FROM replicas_abiertas WHERE id_reporte = ".$pac[$x]['report_id']." AND id_usuario = ".$manager_id.") AS new_reply";
		$stmt = $app['db']->prepare($sql_new_pac);
		$stmt->execute();
		$new_pac = $stmt->fetch();

		$sql_pac_due_date = "SELECT cr.fecha_vencimiento AS due_date, TIMESTAMPDIFF(DAY, CURDATE(), cr.fecha_vencimiento) AS left_days FROM cuestionarios_respondidos AS cr WHERE cr.id_cuestionario_respondido = ".$pac[$x]['report_id'];
		$stmt = $app['db']->prepare($sql_pac_due_date);
		$stmt->execute();
		$pac_due_date = $stmt->fetch();

		$pac_data[$x]['report_id'] = $pac[$x]['report_id'];
		$pac_data[$x]['branch'] = $pac[$x]['branch'];
		$pac_data[$x]['auditor'] = $pac[$x]['auditor'];
		$pac_data[$x]['audit_date'] = $pac[$x]['audit_date'];
		$pac_data[$x]['pac_percentage'] = $pac_percentage['percentage'];
		$pac_data[$x]['pac_accomplishment'] = $pac_accomplishment['percentage'];
		$pac_data[$x]['new_pac'] = $new_pac['new_reply'];
		$pac_data[$x]['due_date'] = $pac_due_date['due_date'];
		$pac_data[$x]['left_days'] = $pac_due_date['left_days'];
	}

	$total_reports = $total_reports['total_reports'];
	$reviewed_reports = $reviewed_reports['reviewed_reports'];
	$approved_reports = $approved_reports['approved_reports'];
	$released_reports = $released_reports['released_reports'];

	// Managing the report status logic
	if($total_reports == $released_reports){
		$reviewed_reports = $released_reports;
		$approved_reports = $released_reports;
	}

	// Calculating the color percentages
	$blue = 0;
	$green = 0;
	$yellow = 0;
	$red = 0;

	for($i=0; $i<count($reports_data); $i++){
		if($reports_data[$i]['color'] == '#3D46F7'){
			$blue++;
		}else if($reports_data[$i]['color'] == '#22B718'){
			$green++;
		}else if($reports_data[$i]['color'] == '#F6EC67'){
			$yellow++;
		}else if($reports_data[$i]['color'] == '#FE2E2E'){
			$red++;
		}
	}

	$blue_percentage = round(($blue * 100) / $total_reports);
	$green_percentage = round(($green * 100) / $total_reports);
	$yellow_percentage = round(($yellow * 100) / $total_reports);
	$red_percentage = round(($red * 100) / $total_reports);

	// Building the email body
	$html = '<!DOCTYPE html>';
	$html .= '<html>';
	$html .= '<head>';
	$html .= '<meta charset="utf-8">';
	$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
	$html .= '<title>Audit Suit - Resumen semanal</title>';
	$html .= '</head>';
	$html .= '<body>';
	$html .= '<div style="background-color:#222; display:block; width:100%; height:20px; text-align:center; padding:15px;"><span style="color:#FFFFFF; font-family:arial;">Audit Suit: Resumen semanal</span></div><br><br>';
	$html .= '<table style="font-family:arial;" width="100%" height="100%" cellspacing="0">';
	$html .= '<tr>';

	$html .= '<td style="border-right:2px solid gray; border-bottom:2px solid gray;" width="50%" align="center">';
	$html .= '<table>';
	$html .= '<tr><td colspan="2">&nbsp;</td></tr>';
	$html .= '<tr>';
	$html .= '<td colspan="2"><font face="verdana"><b>ESTADO DE REPORTES</b></font></td>';
	$html .= '</tr>';
	$html .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
	$html .= '<tr>';
	$html .= '<td><b>Total de reportes</b></td>';
	$html .= '<td>'.$total_reports.'</td>';
	$html .= '</tr>';
	$html .= '<tr style="color:#f0ad4e;">';
	$html .= '<td><b>Revisados</b></td>';
	$html .= '<td>'.$reviewed_reports.'</td>';
	$html .= '</tr>';
	$html .= '<tr style="color:#337ab7;">';
	$html .= '<td><b>Aprobados</b></td>';
	$html .= '<td>'.$approved_reports.'</td>';
	$html .= '</tr>';
	$html .= '<tr style="color:#5cb85c;">';
	$html .= '<td><b>Liberados</b></td>';
	$html .= '<td>'.$released_reports.'</td>';
	$html .= '</tr>';
	$html .= '</table>';
	$html .= '</td>';

	$html .= '<td align="center" style="border-left:2px solid gray; border-bottom:2px solid gray;">';
	$html .= '<font face="verdana"><b>PLANES DE ACCIONES CORRECTIVAS</b></font><br><br>';
	$html .= '<table border="1" cellspacing="0" font>';
	$html .= '<tr style="background-color:#848484; color:#FFFFFF;">';
	$html .= '<th><font size="1">No</font></th>';
	$html .= '<th><font size="1">PLANTA</font></th>';
	$html .= '<th><font size="1">% INGRESADO</font></th>';
	$html .= '<th><font size="1">% CUMPLIMIENTO</font></th>';
	$html .= '<th><font size="1">PAC NUEVOS</font></th>';
	$html .= '<th><font size="1">DÍAS RESTANTES</font></th>';
	$html .= '</tr>';
	// Filling table with the result query
	for($y=0; $y<count($pac_data); $y++){
		$html .= '<tr>';
		$html .= '<td><font size="1">'.$pac_data[$y]['report_id'].'</font></td>';
		$html .= '<td><font size="1">'.$pac_data[$y]['branch'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['pac_percentage'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['pac_accomplishment'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['new_pac'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['left_days'].'</font></td>';
		$html .= '</tr>';
	}
	$html .= '</table><br><br><br>';
	$html .= '</td>';

	$html .= '</tr>';
	$html .= '<tr>';
	
	$html .= '<td align="center" style="border-top:2px solid gray; border-right:2px solid gray;">';
	$html .= '<table>';
	$html .= '<tr><td colspan="4">&nbsp;</td></tr>';
	$html .= '<tr>';
	$html .= '<td colspan="4"><font face="verdana"><b>SEMÁFORO DE RESULTADOS</b></font></td>';
	$html .= '</tr>';
	$html .= '<tr><td colspan="4">&nbsp;</td></tr>';
	$html .= '<tr style="font-size:11px; font-weight:bold; text-align:center;">';
	$html .= '<td style="color:#3D46F7;">Excelente <br>(Mayor o igual a 95%)</td>';
	$html .= '<td style="color:#22B718;">Bien <br>(Mayor a 88% y menor a 95%)</td>';
	$html .= '<td style="color:#F6EC67;">Regular <br>(Mayor a 70% y menor a 88%)</td>';
	$html .= '<td style="color:#FE2E2E;">Mal <br>(Menor a 70%)</td>';
	$html .= '</tr>';
	$html .= '<tr style="text-align:center; font-size:12px; font-weight:bold; color:#FFFFFF;">';
	$html .= '<td><div style="width:20px; height:18px; background-color:#3D46F7; display:block; margin:auto; padding:5px;">'.$blue.'</div></td>';
	$html .= '<td><div style="width:20px; height:18px; background-color:#22B718; display:block; margin:auto; padding:5px;">'.$green.'</div></td>';
	$html .= '<td><div style="width:20px; height:18px; background-color:#F6EC67; display:block; margin:auto; padding:5px;">'.$yellow.'</div></td>';
	$html .= '<td><div style="width:20px; height:18px; background-color:#FE2E2E; display:block; margin:auto; padding:5px;">'.$red.'</div></td>';
	$html .= '</tr>';
	$html .= '<tr><td colspan="4">&nbsp;</td></tr>';
	$html .= '<tr>';
	$html .= '<td align="center" colspan="4">';
	$html .= '<div style="width:50px; height:200px; display:block;">';
	$html .= '<div style="background-color:#3D46F7; width:50px; height:'.$blue_percentage.'%"><b>'.($blue_percentage == 0 ? '' : $blue_percentage . ' %').'</b></div>';
	$html .= '<div style="background-color:#22B718; width:50px; height:'.$green_percentage.'%"><b>'.($green_percentage == 0 ? '' : $green_percentage . ' %').'</b></div>';
	$html .= '<div style="background-color:#F6EC67; width:50px; height:'.$yellow_percentage.'%"><b>'.($yellow_percentage == 0 ? '' : $yellow_percentage . ' %').'</b></div>';
	$html .= '<div style="background-color:#FE2E2E; width:50px; height:'.$red_percentage.'%"><b>'.($red_percentage == 0 ? '' : $red_percentage . ' %').'</b></div>';
	$html .= '</div>';
	$html .= '</td>';
	$html .= '</tr>';
	$html .= '</table>';
	$html .= '</td>';

	$html .= '<td style="border-left:2px solid gray; border-top:2px solid gray;" align="center">';
	$html .= '<font face="verdana"><b>RESULTADOS POR PLANTAS</b></font><br><br>';
	$html .= '<table border="1" cellspacing="0" cellpadding="3">';
	$html .= '<tr style="background-color:#848484; color:#FFFFFF;">';
	$html .= '<th>PLANTA</th>';
	$html .= '<th>FECHA AUDITORÍA</th>';
	$html .= '<th>RESULTADO</th>';
	$html .= '</tr>';
	// Filling the table with the result query
	for($j=0; $j<count($reports_data); $j++){
		$report = $reports_data[$j];

		$html .= '<tr>';
		$html .= '<td>'.$report['branch'].'</td>';
		$html .= '<td align="center">'.$report['audit_date'].'</td>';
		$html .= '<td align="center">'.$report['score'].'</td>';
		$html .= '<tr>';
	}
	$html .= '</table>';
	$html .= '</td>';

	$html .= '</tr>';
	$html .= '</table>';
	$html .= '<table>';
	$html .= '<tr><td colspan="2"><img src="https://bluehand.com.mx/console/img/email-footer.png" width="320" height="200"></td>';
	$html .= '<td colspan="2"><font face="arial" size="2" color="#585858">';
	$html .= '<p><font color="black"><b>POL&Iacute;TICA DE PRIVACIDAD:</b></font> BH Consulting Group M&eacute;xico - Bluehand S.A.P.I. de C.V. utilizar&aacute;n cualquier dato personal expuesto en el presente correo electr&oacute;nico, &uacute;nica y exclusivamente para cuestiones acad&eacute;micas, administrativas, de comunicaci&oacute;n, o bien para las finalidades expresadas en cada asunto en concreto, esto en cumplimiento con la Ley Federal de Protecci&oacute;n de Datos Personales en Posesi&oacute;n de los Particulares. Para mayor informaci&oacute;n acerca del tratamiento y de los derechos que puede hacer valer, usted puede acceder al aviso de privacidad integral a trav&eacute;s de la siguiente liga:</p>';
	$html .= '<p><a href="https://bluehand.com.mx/console/aviso-privacidad/aviso-privacidad.pdf" target="_blank">AVISO DE PRIVACIDAD</a></p>';
	$html .= '<p>La informaci&oacute;n contenida en este correo es privada y confidencial, dirigida exclusivamente a su destinatario. Si usted no es el destinatario del mismo debe destruirlo y notificar al remitente absteni&eacute;ndose de obtener copias, ni difundirlo por ning&uacute;n sistema, ya que est&aacute; prohibido y goza de la protecci&oacute;n legal de las comunicaciones.</p>';
	$html .= '</td></tr></table>';
	$html .= '</body>';
	$html .= '</html>';

	//return $html;
	//$to = 'raul.romo@bh-cg.com.mx';
	$to = $manager_email;
	$subject = 'Resumen semanal';
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf8' . "\r\n";
	$headers .= 'From: Reportes Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
	$headers .= 'Cc: raul.romo@bh-cg.com.mx, ericktorres87@gmail.com' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
	
	$send = mail($to, $subject, $html, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	//return $html;

});


// This controller send the resume email for plants
$app->get('/v1/reports/email-for-plants/{manager_id}', function($manager_id) use ($app){
	// Getting the number of total reports
	$sql_total_reports = "SELECT COUNT(*) AS total_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_total_reports);
	$stmt->execute();
	$total_reports = $stmt->fetch();

	// Getting the number of reviewed reports
	$sql_reviewed_reports = "SELECT COUNT(*) AS reviewed_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE cr.estado = 1 AND cr.liberado = 0 AND rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_reviewed_reports);
	$stmt->execute();
	$reviewed_reports = $stmt->fetch();

	// Getting the number of approved reports
	$sql_approved_reports = "SELECT COUNT(*) AS approved_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE cr.estado = 2 AND cr.liberado = 0 AND rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_approved_reports);
	$stmt->execute();
	$approved_reports = $stmt->fetch();

	// Getting the number of released reports
	$sql_released_reports = "SELECT COUNT(*) AS released_reports FROM cuestionarios_respondidos AS cr LEFT JOIN r_gerentes_empresas AS rge ON rge.id_empresa = cr.id_cliente WHERE cr.estado = 2 AND cr.liberado = 1 AND rge.id_gerente = $manager_id";
	$stmt = $app['db']->prepare($sql_released_reports);
	$stmt->execute();
	$released_reports = $stmt->fetch();

	// Getting the reports information
	$sql_reports_data = $app['db']->createQueryBuilder();
	$sql_reports_data
		->select('r.id_reporte AS report_id, r.planta_sucursal AS branch, r.resultado_total AS score, r.fecha_auditoria AS audit_date, (CASE WHEN r.resultado_total <= 70  THEN \'#FE2E2E\' WHEN r.resultado_total >= 71 && r.resultado_total <= 85 THEN \'#F6EC67\' WHEN r.resultado_total >= 86 && r.resultado_total < 95 THEN \'#22B718\' WHEN r.resultado_total >= 95 THEN \'#3D46F7\' END) AS color')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'reportes', 'r', 'r.id_reporte = cr.id_cuestionario_respondido')
		->leftJoin('cr', 'r_gerentes_empresas', 'rge', 'rge.id_empresa = cr.id_cliente')
		->where('rge.id_gerente = ?')
		->orderBy('score', 'DESC');
	$stmt = $app['db']->prepare($sql_reports_data);
	$stmt->bindValue(1, $manager_id);
	$stmt->execute();
	$reports_data = $stmt->fetchAll();

	// Getting the PAC information
	$sql_pac = $app['db']->createQueryBuilder();
	$sql_pac
		->select('r.id_reporte AS report_id, r.planta_sucursal AS branch, r.auditor AS auditor, r.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'reportes', 'r', 'r.id_reporte = cr.id_cuestionario_respondido')
		->leftJoin('cr', 'r_gerentes_empresas', 'rge', 'rge.id_empresa = cr.id_cliente ')
		->where('rge.id_gerente = ?')
		->orderBy('r.resultado_total', 'DESC');
	$stmt = $app['db']->prepare($sql_pac);
	$stmt->bindValue(1, $manager_id);
	$stmt->execute();
	$pac = $stmt->fetchAll();
	$pac_data = array();

	for($x=0; $x<count($pac); $x++){
		// Getting the pac detail
		$sql_pac_percentage = "SELECT TRUNCATE((((SELECT COUNT(DISTINCT(id_pregunta)) AS total_added FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].") * 100) / (SELECT COUNT(*) AS total FROM respuestas_cuestionarios WHERE valor < 100 AND id_cuestionario_respondido = ".$pac[$x]['report_id'].")), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_pac_percentage);
		$stmt->execute();
		$pac_percentage = $stmt->fetch();

		$sql_pac_accomplishment = "SELECT TRUNCATE(((SELECT COUNT(*) AS total_success FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id']." AND satisfactorio = 1) * 100) / (SELECT COUNT(*) AS total FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id']."), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_pac_accomplishment);
		$stmt->execute();
		$pac_accomplishment = $stmt->fetch();

		/*$sql_new_pac = "SELECT (SELECT COUNT(*) FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].") - (SELECT COUNT(*) AS opened FROM replicas_abiertas WHERE id_reporte = ".$pac[$x]['report_id']." AND id_usuario = ".$manager_id.") AS new_reply";
		$stmt = $app['db']->prepare($sql_new_pac);
		$stmt->execute();
		$new_pac = $stmt->fetch();*/

		$sql_pac_due_date = "SELECT cr.fecha_vencimiento AS due_date, TIMESTAMPDIFF(DAY, CURDATE(), cr.fecha_vencimiento) AS left_days FROM cuestionarios_respondidos AS cr WHERE cr.id_cuestionario_respondido = ".$pac[$x]['report_id'];
		$stmt = $app['db']->prepare($sql_pac_due_date);
		$stmt->execute();
		$pac_due_date = $stmt->fetch();

		// Getting the number of new comments
		$sql_new_comments = "SELECT (SELECT COUNT(*) AS total_comments FROM detalle_replicas WHERE id_replica IN (SELECT DISTINCT(id_replica) FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].")) - (SELECT COUNT(*) FROM comentarios_replicas_abiertos WHERE id_usuario = ".$manager_id." AND id_detalle_replica IN (SELECT dr.id_detalle_replica FROM detalle_replicas AS dr LEFT JOIN replicas AS r ON r.id_replica = dr.id_replica WHERE r.id_cuestionario_respondido = ".$pac[$x]['report_id'].")) AS new_comments";
		$stmt = $app['db']->prepare($sql_new_comments);
		$stmt->execute();
		$new_pac_comments = $stmt->fetch();

		$pac_data[$x]['report_id'] = $pac[$x]['report_id'];
		$pac_data[$x]['branch'] = $pac[$x]['branch'];
		$pac_data[$x]['auditor'] = $pac[$x]['auditor'];
		$pac_data[$x]['audit_date'] = $pac[$x]['audit_date'];
		$pac_data[$x]['pac_percentage'] = $pac_percentage['percentage'];
		$pac_data[$x]['pac_accomplishment'] = $pac_accomplishment['percentage'];
		$pac_data[$x]['new_comments'] = $new_pac_comments['new_comments'];
		$pac_data[$x]['due_date'] = $pac_due_date['due_date'];
		$pac_data[$x]['left_days'] = $pac_due_date['left_days'];
	}

	// Getting the result by section
	$sql_sections = $app['db']->createQueryBuilder();
	$sql_sections
		->select('sc.id_seccion AS section_id, sc.nombre_seccion AS section_name, sc.valor AS value')
		->from('secciones_cuestionario', 'sc')
		->where('sc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_sections);
	$stmt->bindValue(1, 7);
	$stmt->execute();
	$sections = $stmt->fetchAll();
	$html_section = '';

	for($j=0; $j<count($sections); $j++){
		$section = $sections[$j];

		$sql_percentage = "SELECT * FROM (SELECT TRUNCATE((SUM(rc.puntaje) * 100 / (sc.valor - IFNULL((SELECT SUM(p.ponderacion) FROM preguntas AS p LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports_data[0]['report_id']."), 0))), 2) AS percentage1 FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports_data[0]['report_id']." AND rc.no_aplica = 0) AS result1, (SELECT TRUNCATE((SUM(rc.puntaje) * 100 / (sc.valor - IFNULL((SELECT SUM(p.ponderacion) FROM preguntas AS p LEFT JOIN respuestas_cuestionarios AS rc ON rc.id_pregunta = p.id_pregunta WHERE rc.no_aplica = 1 AND rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports_data[1]['report_id']."), 0))), 2) AS percentage2 FROM respuestas_cuestionarios AS rc LEFT JOIN secciones_cuestionario AS sc ON sc.id_seccion = rc.id_seccion WHERE rc.id_seccion = ".$section['section_id']." AND rc.id_cuestionario_respondido = ".$reports_data[1]['report_id']." AND rc.no_aplica = 0) AS result2";
		$stmt = $app['db']->prepare($sql_percentage);
		$stmt->execute();
		$percentage_got = $stmt->fetch();

		$result[$j]['section_name'] = $section['section_name'];
		$result[$j]['percentage1'] = $percentage_got['percentage1'];
		$result[$j]['percentage2'] = $percentage_got['percentage2'];

		if($percentage_got['percentage1'] <= 70){
			$td_background = ' style="background-color:#FE2E2E; color:#FFFFFF;"';
		}else if($percentage_got['percentage1'] > 70 && $percentage_got['percentage1'] < 88){
			$td_background = ' style="background-color:#F6EC67"';
		}else if($percentage_got['percentage1'] >= 88 && $percentage_got['percentage1'] < 95){
			$td_background = ' style="background-color:#22B718; color:#FFFFFF;"';
		}else if($percentage_got['percentage1'] >= 95){
			$td_background = ' style="background-color:#3D46F7; color:#FFFFFF;"';
		}

		if($percentage_got['percentage2'] <= 70){
			$td_background2 = ' style="background-color:#FE2E2E; color:#FFFFFF;"';
		}else if($percentage_got['percentage2'] > 70 && $percentage_got['percentage2'] < 88){
			$td_background2 = ' style="background-color:#F6EC67"';
		}else if($percentage_got['percentage2'] >= 88 && $percentage_got['percentage2'] < 95){
			$td_background2 = ' style="background-color:#22B718; color:#FFFFFF;"';
		}else if($percentage_got['percentage2'] >= 95){
			$td_background2 = ' style="background-color:#3D46F7; color:#FFFFFF;"';
		}

		$html_section .= '<tr><td>'.$section['section_name'].'</td><td'.$td_background.'>'.$percentage_got['percentage1'].'</td><td'.$td_background2.'>'.$percentage_got['percentage2'].'</td></tr>';
	}

	$total_reports = $total_reports['total_reports'];
	$reviewed_reports = $reviewed_reports['reviewed_reports'];
	$approved_reports = $approved_reports['approved_reports'];
	$released_reports = $released_reports['released_reports'];

	// Managing the report status logic
	if($total_reports == $released_reports){
		$reviewed_reports = $released_reports;
		$approved_reports = $released_reports;
	}

	// Calculating the color percentages
	$blue = 0;
	$green = 0;
	$yellow = 0;
	$red = 0;

	for($i=0; $i<count($reports_data); $i++){
		if($reports_data[$i]['color'] == '#3D46F7'){
			$blue++;
		}else if($reports_data[$i]['color'] == '#22B718'){
			$green++;
		}else if($reports_data[$i]['color'] == '#F6EC67'){
			$yellow++;
		}else if($reports_data[$i]['color'] == '#FE2E2E'){
			$red++;
		}
	}

	$blue_percentage = round(($blue * 100) / $total_reports);
	$green_percentage = round(($green * 100) / $total_reports);
	$yellow_percentage = round(($yellow * 100) / $total_reports);
	$red_percentage = round(($red * 100) / $total_reports);

	// Building the email body
	$html = '<!DOCTYPE html>';
	$html .= '<html>';
	$html .= '<head>';
	$html .= '<meta charset="utf-8">';
	$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
	$html .= '<title>Audit Suit - Resumen semanal</title>';
	$html .= '</head>';
	$html .= '<body>';
	$html .= '<div style="background-color:#222; display:block; width:100%; height:20px; text-align:center; padding:15px;"><span style="color:#FFFFFF; font-family:arial;">Audit Suit: Resumen semanal</span></div><br><br>';
	$html .= '<table style="font-family:arial;" width="100%" height="100%" cellspacing="0">';
	$html .= '<tr>';

	$html .= '<td style="border-right:2px solid gray; border-bottom:2px solid gray;" width="50%" align="center">';
	$html .= '<font face="verdana"><b>RESULTADOS POR PLANTAS</b></font><br><br>';
	$html .= '<table border="1" cellspacing="0" cellpadding="3">';
	$html .= '<tr style="background-color:#848484; color:#FFFFFF;">';
	$html .= '<th>PLANTA</th>';
	$html .= '<th>FECHA AUDITORÍA</th>';
	$html .= '<th>RESULTADO</th>';
	$html .= '</tr>';
	// Filling the table with the result query
	for($j=0; $j<count($reports_data); $j++){
		$report = $reports_data[$j];

		$html .= '<tr>';
		$html .= '<td>'.$report['branch'].'</td>';
		$html .= '<td align="center">'.$report['audit_date'].'</td>';
		$html .= '<td align="center">'.$report['score'].'</td>';
		$html .= '<tr>';
	}
	$html .= '</table>';
	$html .= '</td>';

	$html .= '<td align="center" style="border-left:2px solid gray; border-bottom:2px solid gray;">';
	$html .= '<font face="verdana"><b>PLANES DE ACCIONES CORRECTIVAS</b></font><br><br>';
	$html .= '<table border="1" cellspacing="0">';
	$html .= '<tr style="background-color:#848484; color:#FFFFFF;">';
	$html .= '<th><font size="1">No</font></th>';
	$html .= '<th><font size="1">PLANTA</font></th>';
	$html .= '<th><font size="1">% INGRESADO</font></th>';
	$html .= '<th><font size="1">% CUMPLIMIENTO</font></th>';
	$html .= '<th><font size="1">COMENTARIOS</font></th>';
	$html .= '<th><font size="1">DÍAS RESTANTES</font></th>';
	$html .= '</tr>';
	// Filling table with the result query
	for($y=0; $y<count($pac_data); $y++){
		$html .= '<tr>';
		$html .= '<td><font size="1">'.$pac_data[$y]['report_id'].'</font></td>';
		$html .= '<td><font size="1">'.$pac_data[$y]['branch'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['pac_percentage'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['pac_accomplishment'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['new_comments'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['left_days'].'</font></td>';
		$html .= '</tr>';
	}
	$html .= '</table><br><br><br>';
	$html .= '</td>';

	$html .= '</tr>';
	$html .= '<tr>';
	
	$html .= '<td colspan="2" align="center" style="border-top:2px solid gray;">';
	$html .= '<font face="verdana"><b>SEMÁFORO DE RESULTADOS</b></font><br><br>';
	$html .= '<table>';
	$html .= '<tr style="font-size:11px; font-weight:bold; text-align:center;">';
	$html .= '<td style="color:#3D46F7;">Excelente <br>(Mayor o igual a 95%)</td>';
	$html .= '<td style="color:#22B718;">Bien <br>(Mayor a 88% y menor a 95%)</td>';
	$html .= '<td style="color:#F6EC67;">Regular <br>(Mayor a 70% y menor a 88%)</td>';
	$html .= '<td style="color:#FE2E2E;">Mal <br>(Menor a 70%)</td>';
	$html .= '</tr>';
	$html .= '</table>';
	$html .= '<br><br>';
	$html .= '<table border="1" cellspacing="0" style="font-size:13px;">';
	$html .= '<tr style="background-color:#848484; color:#FFFFFF;"><th>SECCIÓN</th><th>'.date('Y', strtotime($reports_data[0]['audit_date'])).'</th><th>'.date('Y', strtotime($reports_data[1]['audit_date'])).'</th></tr>';
	$html .= $html_section;
	$html .= '</table>';
	$html .= '</td>';

	$html .= '</tr>';
	$html .= '</table>';
	$html .= '<table>';
	$html .= '<tr><td colspan="2"><img src="https://bluehand.com.mx/console/img/email-footer.png" width="320" height="200"></td>';
	$html .= '<td colspan="2"><font face="arial" size="2" color="#585858">';
	$html .= '<p><font color="black"><b>POL&Iacute;TICA DE PRIVACIDAD:</b></font> BH Consulting Group M&eacute;xico - Bluehand S.A.P.I. de C.V. utilizar&aacute;n cualquier dato personal expuesto en el presente correo electr&oacute;nico, &uacute;nica y exclusivamente para cuestiones acad&eacute;micas, administrativas, de comunicaci&oacute;n, o bien para las finalidades expresadas en cada asunto en concreto, esto en cumplimiento con la Ley Federal de Protecci&oacute;n de Datos Personales en Posesi&oacute;n de los Particulares. Para mayor informaci&oacute;n acerca del tratamiento y de los derechos que puede hacer valer, usted puede acceder al aviso de privacidad integral a trav&eacute;s de la siguiente liga:</p>';
	$html .= '<p><a href="https://bluehand.com.mx/console/aviso-privacidad/aviso-privacidad.pdf" target="_blank">AVISO DE PRIVACIDAD</a></p>';
	$html .= '<p>La informaci&oacute;n contenida en este correo es privada y confidencial, dirigida exclusivamente a su destinatario. Si usted no es el destinatario del mismo debe destruirlo y notificar al remitente absteni&eacute;ndose de obtener copias, ni difundirlo por ning&uacute;n sistema, ya que est&aacute; prohibido y goza de la protecci&oacute;n legal de las comunicaciones.</p>';
	$html .= '</td></tr></table>';
	$html .= '</body>';
	$html .= '</html>';

	//return $html;
	$to = 'raul.romo@bh-cg.com.mx';
	$subject = 'Resumen semanal';
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf8' . "\r\n";
	$headers .= 'From: Reportes Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
	$headers .= 'Cc: ericktorres87@gmail.com' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
	
	$send = mail($to, $subject, $html, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	//return $html;

});


// This controller send the resume email for auditors
$app->get('/v1/reports/email-for-auditors/{auditor_id}', function($auditor_id) use ($app){

	// Getting the PAC information
	$sql_pac = $app['db']->createQueryBuilder();
	$sql_pac
		->select('r.id_reporte AS report_id, r.planta_sucursal AS branch, r.auditor AS auditor, r.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'reportes', 'r', 'r.id_reporte = cr.id_cuestionario_respondido')
		->leftJoin('cr', 'r_auditores_empresas', 'rae', 'rae.id_empresa = cr.id_cliente ')
		->where('rae.id_auditor = ?')
		->orderBy('r.resultado_total', 'DESC');
	$stmt = $app['db']->prepare($sql_pac);
	$stmt->bindValue(1, $auditor_id);
	$stmt->execute();
	$pac = $stmt->fetchAll();
	$pac_data = array();

	for($x=0; $x<count($pac); $x++){
		// Getting the pac detail
		$sql_pac_percentage = "SELECT TRUNCATE((((SELECT COUNT(DISTINCT(id_pregunta)) AS total_added FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].") * 100) / (SELECT COUNT(*) AS total FROM respuestas_cuestionarios WHERE valor < 100 AND id_cuestionario_respondido = ".$pac[$x]['report_id'].")), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_pac_percentage);
		$stmt->execute();
		$pac_percentage = $stmt->fetch();

		$sql_pac_accomplishment = "SELECT TRUNCATE(((SELECT COUNT(*) AS total_success FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id']." AND satisfactorio = 1) * 100) / (SELECT COUNT(*) AS total FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id']."), 2) AS percentage";
		$stmt = $app['db']->prepare($sql_pac_accomplishment);
		$stmt->execute();
		$pac_accomplishment = $stmt->fetch();

		$sql_pac_due_date = "SELECT cr.fecha_vencimiento AS due_date, TIMESTAMPDIFF(DAY, CURDATE(), cr.fecha_vencimiento) AS left_days FROM cuestionarios_respondidos AS cr WHERE cr.id_cuestionario_respondido = ".$pac[$x]['report_id'];
		$stmt = $app['db']->prepare($sql_pac_due_date);
		$stmt->execute();
		$pac_due_date = $stmt->fetch();

		// Getting the number of new comments
		$sql_new_comments = "SELECT (SELECT COUNT(*) AS total_comments FROM detalle_replicas WHERE id_replica IN (SELECT DISTINCT(id_replica) FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].")) - (SELECT COUNT(*) FROM comentarios_replicas_abiertos WHERE id_usuario = ".$auditor_id." AND id_detalle_replica IN (SELECT dr.id_detalle_replica FROM detalle_replicas AS dr LEFT JOIN replicas AS r ON r.id_replica = dr.id_replica WHERE r.id_cuestionario_respondido = ".$pac[$x]['report_id'].")) AS new_comments";
		$stmt = $app['db']->prepare($sql_new_comments);
		$stmt->execute();
		$new_pac_comments = $stmt->fetch();

		$sql_new_pac = "SELECT (SELECT COUNT(*) FROM replicas WHERE id_cuestionario_respondido = ".$pac[$x]['report_id'].") - (SELECT COUNT(*) AS opened FROM replicas_abiertas WHERE id_reporte = ".$pac[$x]['report_id']." AND id_usuario = ".$auditor_id.") AS new_reply";
		$stmt = $app['db']->prepare($sql_new_pac);
		$stmt->execute();
		$new_pac = $stmt->fetch();

		$new_entries = $new_pac_comments['new_comments'] + $new_pac['new_reply'];

		$pac_data[$x]['report_id'] = $pac[$x]['report_id'];
		$pac_data[$x]['branch'] = $pac[$x]['branch'];
		$pac_data[$x]['auditor'] = $pac[$x]['auditor'];
		$pac_data[$x]['audit_date'] = $pac[$x]['audit_date'];
		$pac_data[$x]['pac_percentage'] = $pac_percentage['percentage'];
		$pac_data[$x]['pac_accomplishment'] = $pac_accomplishment['percentage'];
		$pac_data[$x]['new_comments'] = $new_entries;
		$pac_data[$x]['due_date'] = $pac_due_date['due_date'];
		$pac_data[$x]['left_days'] = $pac_due_date['left_days'];
	}

	// Building the email body
	$html = '<!DOCTYPE html>';
	$html .= '<html>';
	$html .= '<head>';
	$html .= '<meta charset="utf-8">';
	$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
	$html .= '<title>Audit Suit - Resumen semanal</title>';
	$html .= '</head>';
	$html .= '<body>';
	$html .= '<div style="background-color:#222; display:block; width:100%; height:20px; text-align:center; padding:15px;"><span style="color:#FFFFFF; font-family:arial;">Audit Suit: Resumen semanal</span></div><br><br>';
	$html .= '<table style="font-family:arial;" width="100%" height="100%" cellspacing="0">';
	$html .= '<tr>';
	$html .= '<td align="center">';

	$html .= '<font face="verdana"><b>PLANES DE ACCIONES CORRECTIVAS</b></font><br><br>';
	$html .= '<table border="1" cellspacing="0">';
	$html .= '<tr style="background-color:#848484; color:#FFFFFF;">';
	$html .= '<th><font size="1">No</font></th>';
	$html .= '<th><font size="1">PLANTA</font></th>';
	$html .= '<th><font size="1">% INGRESADO</font></th>';
	$html .= '<th><font size="1">% CUMPLIMIENTO</font></th>';
	$html .= '<th><font size="1">COMENTARIOS</font></th>';
	$html .= '<th><font size="1">DÍAS RESTANTES</font></th>';
	$html .= '</tr>';
	// Filling table with the result query
	for($y=0; $y<count($pac_data); $y++){
		$html .= '<tr>';
		$html .= '<td><font size="1">'.$pac_data[$y]['report_id'].'</font></td>';
		$html .= '<td><font size="1">'.$pac_data[$y]['branch'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['pac_percentage'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['pac_accomplishment'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['new_comments'].'</font></td>';
		$html .= '<td align="center"><font size="1">'.$pac_data[$y]['left_days'].'</font></td>';
		$html .= '</tr>';
	}
	$html .= '</table><br><br><br>';
	
	$html .= '<table>';
	$html .= '<tr><td colspan="2"><img src="https://bluehand.com.mx/console/img/email-footer.png" width="320" height="200"></td>';
	$html .= '<td colspan="2"><font face="arial" size="2" color="#585858">';
	$html .= '<p><font color="black"><b>POL&Iacute;TICA DE PRIVACIDAD:</b></font> BH Consulting Group M&eacute;xico - Bluehand S.A.P.I. de C.V. utilizar&aacute;n cualquier dato personal expuesto en el presente correo electr&oacute;nico, &uacute;nica y exclusivamente para cuestiones acad&eacute;micas, administrativas, de comunicaci&oacute;n, o bien para las finalidades expresadas en cada asunto en concreto, esto en cumplimiento con la Ley Federal de Protecci&oacute;n de Datos Personales en Posesi&oacute;n de los Particulares. Para mayor informaci&oacute;n acerca del tratamiento y de los derechos que puede hacer valer, usted puede acceder al aviso de privacidad integral a trav&eacute;s de la siguiente liga:</p>';
	$html .= '<p><a href="https://bluehand.com.mx/console/aviso-privacidad/aviso-privacidad.pdf" target="_blank">AVISO DE PRIVACIDAD</a></p>';
	$html .= '<p>La informaci&oacute;n contenida en este correo es privada y confidencial, dirigida exclusivamente a su destinatario. Si usted no es el destinatario del mismo debe destruirlo y notificar al remitente absteni&eacute;ndose de obtener copias, ni difundirlo por ning&uacute;n sistema, ya que est&aacute; prohibido y goza de la protecci&oacute;n legal de las comunicaciones.</p>';
	$html .= '</td>';
	$html .= '</tr>';
	$html .= '</table>';

	$html .= '</td>';
	$html .= '</tr>';
	$html .= '</table>';
	$html .= '</body>';
	$html .= '</html>';

	//return $html;
	$to = 'raul.romo@bh-cg.com.mx';
	$subject = 'Resumen semanal';
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf8' . "\r\n";
	$headers .= 'From: Reportes Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
	$headers .= 'Cc: ericktorres87@gmail.com' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
	
	$send = mail($to, $subject, $html, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	//return $html;

});


// This controller gets the managers emails
$app->get('/v1/reports/get-emails/{type}', function($type) use ($app){
	if($type == "managers"){
		$sql = $app['db']->createQueryBuilder();
		$sql
			->select('u.id_usuario AS manager_id, u.correo_electronico AS email')
			->from('usuarios','u')
			->where('u.id_privilegios = 2')
			->andWhere('u.id_tipo_usuario = 2');
		$stmt = $app['db']->prepare($sql);
		$stmt->execute();
		$emails = $stmt->fetchAll();
	}

	return $app->json($emails);
});


// This controller close the opened reports based on the due date
$app->get('/v1/reports/close-expired-reports', function() use ($app){
	$sql_reports = $app['db']->createQueryBuilder();
	$sql_reports
		->select('cr.id_cuestionario_respondido AS report_id, cr.fecha_vencimiento AS due_date')
		->from('cuestionarios_respondidos', 'cr')
		->where('cr.cerrar_replicas = 0');
	$stmt = $app['db']->prepare($sql_reports);
	$stmt->execute();
	$reports = $stmt->fetchAll();
	$today = date("Y-m-d");

	for($i=0; $i<count($reports); $i++){
		if($reports[$i]['due_date'] != NULL){
			if(strtotime($reports[$i]['due_date']) < strtotime($today)){
				$sql_update = "UPDATE cuestionarios_respondidos SET cerrar_replicas = 1 WHERE id_cuestionario_respondido = ".$reports[$i]['report_id'];
				$stmt = $app['db']->prepare($sql_update);
				$update = $stmt->execute();

				if($update == true){
					$response = array(
						"result_code" => 1,
						"message" => "El reporte ".$reports[$i]['report_id']." se ha cerrado exitosamente."
					);
				}else{
					$response = array(
						"result_code" => 0,
						"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
					);
				}
			}
		}
	}

	return $app->json($response);
});

