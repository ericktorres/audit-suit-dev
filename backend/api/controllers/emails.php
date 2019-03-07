<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// This controller sends an email when a report is sent to review
$app->get('/v1/emails/send-review-notification/{report_id}', function($report_id) use ($app){

	// Getting the client data for the selected report
	$sql_client_id = $app['db']->createQueryBuilder();
	$sql_client_id
		->select('cr.id_cliente AS id, e.nombre_comercial AS name, cr.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
		->where('cr.id_cuestionario_respondido = ?');
	$stmt = $app['db']->prepare($sql_client_id);
	$stmt->bindValue(1, $report_id);
	$stmt->execute();
	$client = $stmt->fetch();

	// Getting the destination address
	$sql_email_address = $app['db']->createQueryBuilder();
	$sql_email_address
		->select('u.correo_electronico AS email')
		->from('usuarios', 'u')
		->leftJoin('u', 'r_gerentes_empresas', 'rge', 'rge.id_gerente = u.id_usuario')
		->where('rge.id_empresa = ? AND u.es_regional = 1 OR rge.id_empresa = ? AND u.es_revisor = 1 OR u.id_privilegios = 1');
	$stmt = $app['db']->prepare($sql_email_address);
	$stmt->bindValue(1, $client['id']);
	$stmt->bindValue(2, $client['id']);
	$stmt->execute();
	$emails = $stmt->fetchAll();

	// Building the email body
	$message = '<!DOCTYPE html>
			<html>
				<head>
					<title>AuditSuit - Notificaciones</title>
				</head>
				<body>
					<table>
						<tr>
							<td colspan="2"><font face="arial" size="4"><b>Estimado usuario de AuditSuit,</b></font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">Un nuevo reporte de auditor&iacute;a esta disponible para su <b>aprobaci&oacute;n</b>, corresponde a la planta <b>'.$client['name'].'</b>, con fecha de auditor&iacute;a '.$client['audit_date'].', y <b>n&uacute;mero de reporte '.$report_id.'</b>.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">Para consultarlo inicie sesi&oacute;n en <a href="https://bluehand.com.mx/console/" target="_blank">AuditSuit</a> y dir&iacute;jase a la secci&oacute;n de Reportes.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">Si requiere apoyo o tiene alguna duda sobre el uso de la plataforma, env&iacute;enos un correo a <a href="mailto:auditsuit_gepp@bluehand.com.mx">auditsuit_gepp@bluehand.com.mx</a>, su administrador se pondr&aacute; en contacto con usted a la brevedad posible.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><img src="https://bluehand.com.mx/console/img/email-footer.png" width="320" height="200"></td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">
								<p><font color="black"><b>POL&Iacute;TICA DE PRIVACIDAD:</b></font> BH Consulting Group M&eacute;xico - Bluehand S.A.P.I. de C.V. utilizar&aacute;n cualquier dato personal expuesto en el presente correo electr&oacute;nico, &uacute;nica y exclusivamente para cuestiones acad&eacute;micas, administrativas, de comunicaci&oacute;n, o bien para las finalidades expresadas en cada asunto en concreto, esto en cumplimiento con la Ley Federal de Protecci&oacute;n de Datos Personales en Posesi&oacute;n de los Particulares. Para mayor informaci&oacute;n acerca del tratamiento y de los derechos que puede hacer valer, usted puede acceder al aviso de privacidad integral a trav&eacute;s de la siguiente liga:</p>
								<p><a href="https://bluehand.com.mx/console/aviso-privacidad/aviso-privacidad.pdf" target="_blank">AVISO DE PRIVACIDAD</a></p>
								<p>La informaci&oacute;n contenida en este correo es privada y confidencial, dirigida exclusivamente a su destinatario. Si usted no es el destinatario del mismo debe destruirlo y notificar al remitente absteni&eacute;ndose de obtener copias, ni difundirlo por ning&uacute;n sistema, ya que est&aacute; prohibido y goza de la protecci&oacute;n legal de las comunicaciones.</p></font>
							</td>
						</tr>
					</table>
				</body>
			</html>';

	//$to = 'ericktorres87@gmail.com, erick@brightteam.com.mx';
	$subject = 'Nuevo reporte para aprobación.';
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
	//$headers .= 'Cc: raul.romo@bh-cg.com.mx, ericktorres87@gmail.com' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $to = '';
    $separator = ', ';

    for($i=0; $i<count($emails); $i++){

		$to .= $emails[$i]['email'] . $separator;
	}

	$send = mail($to, $subject, $message, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	
});


// This controller sends an email when a report is sent to approval
$app->get('/v1/emails/send-approval-notification/{report_id}', function($report_id) use ($app){

	// Getting the client data for the selected report
	$sql_client_id = $app['db']->createQueryBuilder();
	$sql_client_id
		->select('cr.id_cliente AS id, e.nombre_comercial AS name, cr.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
		->where('cr.id_cuestionario_respondido = ?');
	$stmt = $app['db']->prepare($sql_client_id);
	$stmt->bindValue(1, $report_id);
	$stmt->execute();
	$client = $stmt->fetch();

	// Getting the destination address
	$sql_email_address = $app['db']->createQueryBuilder();
	$sql_email_address
		->select('u.correo_electronico AS email')
		->from('usuarios', 'u')
		->leftJoin('u', 'r_gerentes_empresas', 'rge', 'rge.id_gerente = u.id_usuario')
		->where('rge.id_empresa = ? AND u.es_revisor = 1 OR u.id_privilegios = 1');
	$stmt = $app['db']->prepare($sql_email_address);
	$stmt->bindValue(1, $client['id']);
	$stmt->execute();
	$emails = $stmt->fetchAll();

	// Building the email body
	$message = '<!DOCTYPE html>
			<html>
				<head>
					<title>AuditSuit - Notificaciones</title>
				</head>
				<body>
					<table>
						<tr>
							<td colspan="2"><font face="arial" size="4"><b>Estimado usuario de AuditSuit,</b></font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">Un nuevo reporte de auditor&iacute;a esta disponible para su <b>liberaci&oacute;n</b>, corresponde a la planta <b>'.$client['name'].'</b>, con fecha de auditor&iacute;a '.$client['audit_date'].', y <b>n&uacute;mero de reporte '.$report_id.'</b>.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">Para consultarlo inicie sesi&oacute;n en <a href="https://bluehand.com.mx/console/" target="_blank">AuditSuit</a> y dir&iacute;jase a la secci&oacute;n de Reportes.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">Si requiere apoyo o tiene alguna duda sobre el uso de la plataforma, env&iacute;enos un correo a <a href="mailto:auditsuit_gepp@bluehand.com.mx">auditsuit_gepp@bluehand.com.mx</a>, su administrador se pondr&aacute; en contacto con usted a la brevedad posible.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><img src="https://bluehand.com.mx/console/img/email-footer.png" width="320" height="200"></td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">
								<p><font color="black"><b>POL&Iacute;TICA DE PRIVACIDAD:</b></font> BH Consulting Group M&eacute;xico - Bluehand S.A.P.I. de C.V. utilizar&aacute;n cualquier dato personal expuesto en el presente correo electr&oacute;nico, &uacute;nica y exclusivamente para cuestiones acad&eacute;micas, administrativas, de comunicaci&oacute;n, o bien para las finalidades expresadas en cada asunto en concreto, esto en cumplimiento con la Ley Federal de Protecci&oacute;n de Datos Personales en Posesi&oacute;n de los Particulares. Para mayor informaci&oacute;n acerca del tratamiento y de los derechos que puede hacer valer, usted puede acceder al aviso de privacidad integral a trav&eacute;s de la siguiente liga:</p>
								<p><a href="https://bluehand.com.mx/console/aviso-privacidad/aviso-privacidad.pdf" target="_blank">AVISO DE PRIVACIDAD</a></p>
								<p>La informaci&oacute;n contenida en este correo es privada y confidencial, dirigida exclusivamente a su destinatario. Si usted no es el destinatario del mismo debe destruirlo y notificar al remitente absteni&eacute;ndose de obtener copias, ni difundirlo por ning&uacute;n sistema, ya que est&aacute; prohibido y goza de la protecci&oacute;n legal de las comunicaciones.</p></font>
							</td>
						</tr>
					</table>
				</body>
			</html>';

	//$to = 'ericktorres87@gmail.com, erick@brightteam.com.mx';
	$subject = 'Nuevo reporte para liberación.';
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
	//$headers .= 'Cc: raul.romo@bh-cg.com.mx, ericktorres87@gmail.com' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $to = '';
    $separator = ', ';

    for($i=0; $i<count($emails); $i++){

		$to .= $emails[$i]['email'] . $separator;
	}

	$send = mail($to, $subject, $message, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	
});


// This controller sends an email when a report is sent to release
$app->get('/v1/emails/send-release-notification/{report_id}', function($report_id) use ($app){

	// Getting the client data for the selected report
	$sql_client_id = $app['db']->createQueryBuilder();
	$sql_client_id
		->select('cr.id_cliente AS id, e.nombre_comercial AS name, cr.fecha_auditoria AS audit_date')
		->from('cuestionarios_respondidos', 'cr')
		->leftJoin('cr', 'empresas', 'e', 'e.id_empresa = cr.id_cliente')
		->where('cr.id_cuestionario_respondido = ?');
	$stmt = $app['db']->prepare($sql_client_id);
	$stmt->bindValue(1, $report_id);
	$stmt->execute();
	$client = $stmt->fetch();

	// Getting the destination address
	$sql_email_address = $app['db']->createQueryBuilder();
	$sql_email_address
		->select('u.correo_electronico AS email')
		->from('usuarios', 'u')
		->leftJoin('u', 'r_gerentes_empresas', 'rge', 'rge.id_gerente = u.id_usuario')
		->where('rge.id_empresa = ? OR u.id_privilegios = 1');
	$stmt = $app['db']->prepare($sql_email_address);
	$stmt->bindValue(1, $client['id']);
	$stmt->execute();
	$emails = $stmt->fetchAll();

	// Building the email body
	$message = '<!DOCTYPE html>
			<html>
				<head>
					<title>AuditSuit - Notificaciones</title>
				</head>
				<body>
					<table>
						<tr>
							<td colspan="2"><font face="arial" size="4"><b>Estimado usuario de AuditSuit,</b></font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">Un nuevo reporte de auditor&iacute;a esta disponible para <b>consulta</b>, corresponde a la planta <b>'.$client['name'].'</b>, con fecha de auditor&iacute;a '.$client['audit_date'].', y <b>n&uacute;mero de reporte '.$report_id.'</b>.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">Para consultarlo inicie sesi&oacute;n en <a href="https://bluehand.com.mx/console/" target="_blank">AuditSuit</a> y dir&iacute;jase a la secci&oacute;n de Reportes.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">Si requiere apoyo o tiene alguna duda sobre el uso de la plataforma, env&iacute;enos un correo a <a href="mailto:auditsuit_gepp@bluehand.com.mx">auditsuit_gepp@bluehand.com.mx</a>, su administrador se pondr&aacute; en contacto con usted a la brevedad posible.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><img src="https://bluehand.com.mx/console/img/email-footer.png" width="320" height="200"></td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">
								<p><font color="black"><b>POL&Iacute;TICA DE PRIVACIDAD:</b></font> BH Consulting Group M&eacute;xico - Bluehand S.A.P.I. de C.V. utilizar&aacute;n cualquier dato personal expuesto en el presente correo electr&oacute;nico, &uacute;nica y exclusivamente para cuestiones acad&eacute;micas, administrativas, de comunicaci&oacute;n, o bien para las finalidades expresadas en cada asunto en concreto, esto en cumplimiento con la Ley Federal de Protecci&oacute;n de Datos Personales en Posesi&oacute;n de los Particulares. Para mayor informaci&oacute;n acerca del tratamiento y de los derechos que puede hacer valer, usted puede acceder al aviso de privacidad integral a trav&eacute;s de la siguiente liga:</p>
								<p><a href="https://bluehand.com.mx/console/aviso-privacidad/aviso-privacidad.pdf" target="_blank">AVISO DE PRIVACIDAD</a></p>
								<p>La informaci&oacute;n contenida en este correo es privada y confidencial, dirigida exclusivamente a su destinatario. Si usted no es el destinatario del mismo debe destruirlo y notificar al remitente absteni&eacute;ndose de obtener copias, ni difundirlo por ning&uacute;n sistema, ya que est&aacute; prohibido y goza de la protecci&oacute;n legal de las comunicaciones.</p></font>
							</td>
						</tr>
					</table>
				</body>
			</html>';

	//$to = 'ericktorres87@gmail.com, erick@brightteam.com.mx';
	$subject = 'Reporte de auditoría GEPP liberado.';
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
	//$headers .= 'Cc: raul.romo@bh-cg.com.mx, ericktorres87@gmail.com' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $to = '';
    $separator = ', ';

    for($i=0; $i<count($emails); $i++){

		$to .= $emails[$i]['email'] . $separator;
	}

	$send = mail($to, $subject, $message, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	
});


// This controller sends an email when a user creates a new reply, adds a comment or close a reply
$app->post('/v1/emails/send-reply-notification', function(Request $request) use ($app){

	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$case_option = $request->request->get('case_option');
	$plant_name = $request->request->get('plant_name');
	$report_id = $request->request->get('report_id');
	$reply_number = $request->request->get('reply_number');

	// Getting the reply_number
	$sql_reply_num = "SELECT (COUNT(*) + 1) AS reply_number FROM replicas WHERE id_cuestionario_respondido = ?";
	$stmt = $app['db']->prepare($sql_reply_num);
	$stmt->bindValue(1, $report_id);
	$stmt->execute();
	$reply_num = $stmt->fetch();

	switch($case_option){
		case "new_reply":
			$reply_number = $reply_num['reply_number'];

			// Getting the destination emails
			$sql_emails = "SELECT u.id_usuario AS id, correo_electronico AS email 
							FROM usuarios AS u 
							LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_auditor = u.id_usuario
							WHERE cr.id_cuestionario_respondido = ?
							UNION
							SELECT u.id_usuario AS id, u.correo_electronico AS email 
							FROM usuarios AS u
							LEFT JOIN r_gerentes_empresas AS rge ON rge.id_gerente = u.id_usuario
							LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_cliente = rge.id_empresa
							WHERE cr.id_cuestionario_respondido = ? AND u.id_tipo_usuario = 1
							UNION
							SELECT u.id_usuario AS id, u.correo_electronico AS email
							FROM usuarios AS u
							WHERE u.id_privilegios = 1";
			$stmt = $app['db']->prepare($sql_emails);
			$stmt->bindValue(1, $report_id);
			$stmt->bindValue(2, $report_id);
			$stmt->execute();
			$emails = $stmt->fetchAll();

			$subject = "Réplicas - Nueva réplica añadida.";
			$main_text = "Se ha añadido la r&eacute;plica n&uacute;mero ".$reply_number." a la planta ".$plant_name." en el reporte No ".$report_id.".";
			break;
		case "new_comment":
			$sql_emails = "SELECT u.id_usuario AS id, u.correo_electronico AS email 
							FROM usuarios AS u
							LEFT JOIN r_gerentes_empresas AS rge ON rge.id_gerente = u.id_usuario
							LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_cliente = rge.id_empresa
							WHERE cr.id_cuestionario_respondido = ? AND u.id_tipo_usuario = 2 AND u.es_regional = 0 AND u.es_revisor = 0
							UNION
							SELECT u.id_usuario AS id, u.correo_electronico AS email
							FROM usuarios AS u
							WHERE u.id_privilegios = 1";
			$stmt = $app['db']->prepare($sql_emails);
			$stmt->bindValue(1, $report_id);
			$stmt->execute();
			$emails = $stmt->fetchAll();

			$subject = "Réplicas - Nuevo comentario añadido.";
			$main_text = "Se ha añadido un nuevo comentario en la r&eacute;plica n&uacute;mero (ID) ".$reply_number." que pertenece al reporte No ".$report_id." de la planta ".$plant_name.".";
			break;
		case "close_reply":
			$sql_emails = "SELECT u.id_usuario AS id, u.correo_electronico AS email 
							FROM usuarios AS u
							LEFT JOIN r_gerentes_empresas AS rge ON rge.id_gerente = u.id_usuario
							LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_cliente = rge.id_empresa
							WHERE cr.id_cuestionario_respondido = ? AND u.id_tipo_usuario = 1
							UNION
							SELECT u.id_usuario AS id, u.correo_electronico AS email
							FROM usuarios AS u
							WHERE u.id_privilegios = 1";
			$stmt = $app['db']->prepare($sql_emails);
			$stmt->bindValue(1, $report_id);
			$stmt->execute();
			$emails = $stmt->fetchAll();

			$subject = "Réplicas - Se ha cerrado una réplica.";
			$main_text = "Se ha cerrado la r&eacute;plica n&uacute;mero (ID) ".$reply_number." que pertnece al reporte No ".$report_id." de la planta ".$plant_name.".";
			break;
		case "close_report":
			$sql_emails = "SELECT u.id_usuario AS id, u.correo_electronico AS email 
							FROM usuarios AS u
							LEFT JOIN r_gerentes_empresas AS rge ON rge.id_gerente = u.id_usuario
							LEFT JOIN cuestionarios_respondidos AS cr ON cr.id_cliente = rge.id_empresa
							WHERE cr.id_cuestionario_respondido = ?
							UNION
							SELECT u.id_usuario AS id, u.correo_electronico AS email
							FROM usuarios AS u
							WHERE u.id_privilegios = 1";
			$stmt = $app['db']->prepare($sql_emails);
			$stmt->bindValue(1, $report_id);
			$stmt->execute();
			$emails = $stmt->fetchAll();

			$subject = "Réplicas - Se ha cerrado un reporte para réplicas.";
			$main_text = "Se ha cerrado el reporte de r&eacute;plicas n&uacute;mero ".$report_id." de la planta ".$plant_name.".";
			break;
	}

	// Building the email body
	$message = '<!DOCTYPE html>
			<html>
				<head>
					<title>AuditSuit - Notificaciones</title>
				</head>
				<body>
					<table>
						<tr>
							<td colspan="2"><font face="arial" size="4"><b>Estimado usuario de AuditSuit,</b></font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">'.$main_text.'</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="verdana" size="3">Para consultarlo inicie sesi&oacute;n en <a href="https://bluehand.com.mx/console/" target="_blank">AuditSuit</a> y dir&iacute;jase a la secci&oacute;n de R&eacute;plicas.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">Si requiere apoyo o tiene alguna duda sobre el uso de la plataforma, env&iacute;enos un correo a <a href="mailto:auditsuit_gepp@bluehand.com.mx">auditsuit_gepp@bluehand.com.mx</a>, su administrador se pondr&aacute; en contacto con usted a la brevedad posible.</font></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><img src="https://bluehand.com.mx/console/img/email-footer.png" width="320" height="200"></td>
						</tr>
						<tr>
							<td colspan="2"><font face="arial" size="2" color="#585858">
								<p><font color="black"><b>POL&Iacute;TICA DE PRIVACIDAD:</b></font> BH Consulting Group M&eacute;xico - Bluehand S.A.P.I. de C.V. utilizar&aacute;n cualquier dato personal expuesto en el presente correo electr&oacute;nico, &uacute;nica y exclusivamente para cuestiones acad&eacute;micas, administrativas, de comunicaci&oacute;n, o bien para las finalidades expresadas en cada asunto en concreto, esto en cumplimiento con la Ley Federal de Protecci&oacute;n de Datos Personales en Posesi&oacute;n de los Particulares. Para mayor informaci&oacute;n acerca del tratamiento y de los derechos que puede hacer valer, usted puede acceder al aviso de privacidad integral a trav&eacute;s de la siguiente liga:</p>
								<p><a href="https://bluehand.com.mx/console/aviso-privacidad/aviso-privacidad.pdf" target="_blank">AVISO DE PRIVACIDAD</a></p>
								<p>La informaci&oacute;n contenida en este correo es privada y confidencial, dirigida exclusivamente a su destinatario. Si usted no es el destinatario del mismo debe destruirlo y notificar al remitente absteni&eacute;ndose de obtener copias, ni difundirlo por ning&uacute;n sistema, ya que est&aacute; prohibido y goza de la protecci&oacute;n legal de las comunicaciones.</p></font>
							</td>
						</tr>
					</table>
				</body>
			</html>';

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: Audit Suit <reportes@bluehand.com.mx>' . "\r\n";
    $headers .= 'Reply-To: reportes@bluehand.com.mx' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $to = '';
    $separator = ', ';

    for($i=0; $i<count($emails); $i++){
		$to .= $emails[$i]['email'] . $separator;
	}

	$send = mail($to, $subject, $message, $headers);

	$result = array(
		"code_result" => $send
	);

	return $app->json($result);
	
});