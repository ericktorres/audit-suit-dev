<?php

use Symfony\Component\HttpFoundation\Request;

// Get all companies data by type
$app->get('/v1/companies/get/{type}', function($type) use ($app){

	$sql = "SELECT 
    			emp.id_empresa AS id,
    			emp.id_tipo_empresa AS id_type,
    			temp.tipo AS type_of_company,
    			emp.nombre_comercial AS trade_name,
    			emp.razon_social AS business_name,
    			emp.telefono AS telephone_number,
    			emp.calle_numero AS street_and_number,
    			emp.colonia AS suburb,
    			emp.delegacion AS municipality,
    			emp.estado AS state,
    			emp.codigo_postal AS zip_code,
    			emp.fecha_alta AS creation_date,
    			emp.fecha_modificacion AS modification_date
			FROM
    			empresas AS emp    		
        	LEFT JOIN
    			tipos_empresa AS temp ON temp.id_tipo_empresa = emp.id_tipo_empresa
    		WHERE emp.id_tipo_empresa = ?
			ORDER BY emp.fecha_alta ASC";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $type);
	$stmt->execute();
	$companies = $stmt->fetchAll();

	return $app->json($companies);

});


// This controller get all the companies
$app->get('/v1/companies/get-all', function() use ($app){

	$sql = "SELECT 
    			emp.id_empresa AS id,
    			emp.id_tipo_empresa AS id_type,
    			temp.tipo AS type_of_company,
    			emp.nombre_comercial AS trade_name,
    			emp.razon_social AS business_name,
    			emp.telefono AS telephone_number,
    			emp.calle_numero AS street_and_number,
    			emp.colonia AS suburb,
    			emp.delegacion AS municipality,
    			emp.estado AS state,
    			emp.codigo_postal AS zip_code,
    			emp.fecha_alta AS creation_date,
    			emp.fecha_modificacion AS modification_date
			FROM
    			empresas AS emp    		
        	LEFT JOIN
    			tipos_empresa AS temp ON temp.id_tipo_empresa = emp.id_tipo_empresa
			ORDER BY emp.fecha_alta ASC"; //WHERE emp.id_tipo_empresa = ?
	$stmt = $app['db']->prepare($sql);
	//$stmt->bindValue(1, $type);
	$stmt->execute();
	$companies = $stmt->fetchAll();

	return $app->json($companies);

});


// Get branches and suppliers companies
$app->get('/v1/companies/get-branches-suppliers/{client_id}', function($client_id) use ($app){

	$sql = "SELECT 
    			emp.id_empresa AS id,
    			emp.id_tipo_empresa AS id_type,
    			temp.tipo AS type_of_company,
    			emp.nombre_comercial AS trade_name,
    			emp.razon_social AS business_name,
    			emp.telefono AS telephone_number,
    			emp.calle_numero AS street_and_number,
    			emp.colonia AS suburb,
    			emp.delegacion AS municipality,
    			emp.estado AS state,
    			emp.codigo_postal AS zip_code,
    			emp.fecha_alta AS creation_date,
    			emp.fecha_modificacion AS modification_date
			FROM
    			empresas AS emp
        	LEFT JOIN
    			tipos_empresa AS temp ON temp.id_tipo_empresa = emp.id_tipo_empresa
    		WHERE emp.id_tipo_empresa != 1
			ORDER BY emp.fecha_alta ASC";
	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$companies = $stmt->fetchAll();
	$arr_companies = array();

	// Query to know if the current company was added to the main client
	for($i=0; $i<count($companies); $i++){
		$sql_check = "SELECT COUNT(*) AS exist FROM r_clientes_plantas_proveedores WHERE id_empresa = ? AND id_planta_proveedor = ?";
		$stmt = $app['db']->prepare($sql_check);
		$stmt->bindValue(1, $client_id);
		$stmt->bindValue(2, $companies[$i]['id']);
		$stmt->execute();
		$is_branch = $stmt->fetch();

		$arr_companies[$i]['id'] = $companies[$i]['id'];
		$arr_companies[$i]['id_type'] = $companies[$i]['id_type'];
		$arr_companies[$i]['type_of_company'] = $companies[$i]['type_of_company'];
		$arr_companies[$i]['trade_name'] = $companies[$i]['trade_name'];
		$arr_companies[$i]['business_name'] = $companies[$i]['business_name'];
		$arr_companies[$i]['telephone_number'] = $companies[$i]['telephone_number'];
		$arr_companies[$i]['street_and_number'] = $companies[$i]['street_and_number'];
		$arr_companies[$i]['suburb'] = $companies[$i]['suburb'];
		$arr_companies[$i]['municipality'] = $companies[$i]['municipality'];
		$arr_companies[$i]['state'] = $companies[$i]['state'];
		$arr_companies[$i]['zip_code'] = $companies[$i]['zip_code'];
		$arr_companies[$i]['creation_date'] = $companies[$i]['creation_date'];
		$arr_companies[$i]['modification_date'] = $companies[$i]['modification_date'];
		$arr_companies[$i]['is_branch'] = $is_branch['exist'];
	}
	

	return $app->json($arr_companies);

});

// Get one company data
$app->get('/v1/company/get/{company_id}', function($company_id) use ($app){

	$sql = "SELECT 
    			emp.id_empresa AS id,
    			emp.id_tipo_empresa AS id_type,
    			temp.tipo AS type_of_company,
    			emp.nombre_comercial AS trade_name,
    			emp.razon_social AS business_name,
    			emp.telefono AS telephone_number,
    			emp.calle_numero AS street_and_number,
    			emp.colonia AS suburb,
    			emp.delegacion AS municipality,
    			emp.estado AS state,
    			emp.codigo_postal AS zip_code,
    			emp.fecha_alta AS creation_date,
    			emp.fecha_modificacion AS modification_date
			FROM
    			empresas AS emp
        	LEFT JOIN
    			tipos_empresa AS temp ON temp.id_tipo_empresa = emp.id_tipo_empresa
    		WHERE id_empresa = ?
			ORDER BY emp.fecha_alta DESC";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $company_id);
	$stmt->execute();
	$company = $stmt->fetch();

	return $app->json($company);

});


// Create a new company
$app->post('/v1/companies/create', function(Request $request) use ($app){

	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$trade_name = $request->request->get('trade_name');
	$business_name = $request->request->get('business_name');
	$telephone_number = $request->request->get('telephone_number');
	$street_and_number = $request->request->get('street_and_number');
	$suburb = $request->request->get('suburb');
	$municipality = $request->request->get('municipality');
	$state = $request->request->get('state');
	$zip_code = $request->request->get('zip_code');
	$id_company_type = $request->request->get('id_company_type');
	$date = date('Y-m-d H:i:s');

	// Validate if the business name already exists
	$sql_validate = $app['db']->createQueryBuilder();
	$sql_validate
		->select('COUNT(*) AS exist')
		->from('empresas', 'emp')
		->where('LOWER(emp.razon_social) = LOWER(?)')
		->andWhere('emp.id_tipo_empresa = ?');
	$stmt = $app['db']->prepare($sql_validate);
	$stmt->bindValue(1, $business_name);
	$stmt->bindValue(2, $id_company_type);
	$stmt->execute();
	$validate = $stmt->fetch();

	if($validate['exist'] === "0"){

		$sql = "INSERT INTO empresas
					(nombre_comercial,
					razon_social,
					telefono,
					calle_numero,
					colonia,
					delegacion,
					estado,
					codigo_postal,
					id_tipo_empresa,
					fecha_alta)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $trade_name);
		$stmt->bindValue(2, $business_name);
		$stmt->bindValue(3, $telephone_number);
		$stmt->bindValue(4, $street_and_number);
		$stmt->bindValue(5, $suburb);
		$stmt->bindValue(6, $municipality);
		$stmt->bindValue(7, $state);
		$stmt->bindValue(8, $zip_code);
		$stmt->bindValue(9, $id_company_type);
		$stmt->bindValue(10, $date);
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

	}else{
		$response = array(
			"result_code" => 0,
			"message" => "La razón social ingresada ya fue registrada anteriormente."
		);
	}

	return $app->json($response);

});


// Edit company data
$app->post('/v1/companies/modify', function(Request $request) use ($app){

	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$company_id = $request->request->get('company_id');
	$trade_name = $request->request->get('trade_name');
	$business_name = $request->request->get('business_name');
	$telephone_number = $request->request->get('telephone_number');
	$street_and_number = $request->request->get('street_and_number');
	$suburb = $request->request->get('suburb');
	$municipality = $request->request->get('municipality');
	$state = $request->request->get('state');
	$zip_code = $request->request->get('zip_code');
	$id_company_type = $request->request->get('id_company_type');
	$date = date('Y-m-d H:i:s');

	$sql = "UPDATE 
				empresas
			SET
				nombre_comercial = ?,
				razon_social = ?,
				telefono = ?,
				calle_numero = ?,
				colonia = ?,
				delegacion = ?,
				estado = ?,
				codigo_postal = ?,
				id_tipo_empresa = ?,
				fecha_modificacion = ?
			WHERE id_empresa = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $trade_name);
	$stmt->bindValue(2, $business_name);
	$stmt->bindValue(3, $telephone_number);
	$stmt->bindValue(4, $street_and_number);
	$stmt->bindValue(5, $suburb);
	$stmt->bindValue(6, $municipality);
	$stmt->bindValue(7, $state);
	$stmt->bindValue(8, $zip_code);
	$stmt->bindValue(9, $id_company_type);
	$stmt->bindValue(10, $date);
	$stmt->bindValue(11, $company_id);
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


// Delete companies
$app->get('/v1/companies/delete/{company_id}', function($company_id) use ($app){
	$sql = "DELETE FROM empresas WHERE id_empresa = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $company_id);
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


// This controller saves the companies (suppliers and plants) that are related to a client company
$app->post('/v1/companies/add-branches-suppliers/', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$id_client = $request->request->get('id_client');
	$id_plant_supplier = $request->request->get('id_plant_supplier');
	$date = date('Y-m-d H:i:s');

	// Checking if the plant or supplier is already added to the client
	$sql_check = $app['db']->createQueryBuilder();
	$sql_check
		->select('COUNT(*) AS exist')
		->from('r_clientes_plantas_proveedores', 'rcpv')
		->where('rcpv.id_empresa = ?')
		->andWhere('rcpv.id_planta_proveedor = ?');
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $id_client);
	$stmt->bindValue(2, $id_plant_supplier);
	$stmt->execute();
	$check = $stmt->fetch();

	if($check['exist'] === "0"){
		// Do the insert
		$sql_save = "INSERT INTO r_clientes_plantas_proveedores (id_empresa, id_planta_proveedor, fecha_alta) VALUES (?, ?, ?)";
		$stmt = $app['db']->prepare($sql_save);
		$stmt->bindValue(1, $id_client);
		$stmt->bindValue(2, $id_plant_supplier);
		$stmt->bindValue(3, $date);
		$insert = $stmt->execute();

		if($insert == true){
			$response = array(
				"result_code" => 1,
				"message" => "El registro ha sido creado exitosamente."
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
			"message" => "El registro que intenta guardar ya existe."
		);
	}

	return $app->json($response);
});


// This controller delete a company that is added to a client
$app->get('/v1/companies/delete-branches-suppliers/{id_client}/{id_plant_supplier}', function($id_client, $id_plant_supplier) use ($app){
	$sql = "DELETE FROM r_clientes_plantas_proveedores WHERE id_empresa = ? AND id_planta_proveedor = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $id_client);
	$stmt->bindValue(2, $id_plant_supplier);
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


// This controller get all the questionnaires tha are available to asign to a client
$app->get('/v1/companies/get-questionnaires/{id_client}', function($id_client) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('c.id_cuestionario AS questionnaire_id, c.codigo AS code, c.nombre AS name, c.status, c.fecha_alta AS creation_date')
		->from('cuestionarios', 'c')
		->orderBy('c.fecha_alta', 'DESC');
	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$questionnaires = $stmt->fetchAll();

	$questionnaires_checked = array();

	// Cheking if the selected client has questionnaires asigned
	for($i=0; $i<count($questionnaires); $i++){
		$sql_check = $app['db']->createQueryBuilder();
		$sql_check
			->select('COUNT(*) AS total')
			->from('r_clientes_cuestionarios', 'rcc')
			->where('rcc.id_cliente = ?')
			->andWhere('rcc.id_cuestionario = ?');
		$stmt = $app['db']->prepare($sql_check);
		$stmt->bindValue(1, $id_client);
		$stmt->bindValue(2, $questionnaires[$i]['questionnaire_id']);
		$stmt->execute();
		$is_assigned = $stmt->fetch();

		$questionnaires_checked[$i]['questionnaire_id'] = $questionnaires[$i]['questionnaire_id'];
		$questionnaires_checked[$i]['code'] = $questionnaires[$i]['code'];
		$questionnaires_checked[$i]['name'] = $questionnaires[$i]['name'];
		$questionnaires_checked[$i]['status'] = $questionnaires[$i]['status'];
		$questionnaires_checked[$i]['assigned'] = $is_assigned['total'];
		$questionnaires_checked[$i]['creation_date'] = $questionnaires[$i]['creation_date'];
	}

	return $app->json($questionnaires_checked);
});


// This controller assign a questionnaire to a client
$app->post('/v1/companies/assign-questionnaire', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$client_id = $request->request->get('client_id');
	$questionnaire_id = $request->request->get('questionnaire_id');
	$date = date('Y-m-d H:i:s');

	// Cheking if the questionnaire is already assigned to the client
	$sql_check = $app['db']->createQueryBuilder();
	$sql_check
		->select('COUNT(*) AS exist')
		->from('r_clientes_cuestionarios', 'rcc')
		->where('rcc.id_cliente = ?')
		->andWhere('rcc.id_cuestionario = ?');
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $client_id);
	$stmt->bindValue(2, $questionnaire_id);
	$stmt->execute();
	$check = $stmt->fetch();

	if($check['exist'] === "0"){

		$sql = "INSERT INTO r_clientes_cuestionarios (id_cliente, id_cuestionario, fecha_alta) VALUES (?, ?, ?)";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $client_id);
		$stmt->bindValue(2, $questionnaire_id);
		$stmt->bindValue(3, $date);
		$insert = $stmt->execute();

		if($insert == true){
			$response = array(
				"result_code" => 1,
				"message" => "El registro ha sido creado exitosamente."
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
			"message" => "El cuestionario que intenta asigar a este cliente ya fue asignado anteriormente."
		);
	}

	return $app->json($response);
});


// This controller delete a questionnaie that is assigned to a client
$app->get('/v1/companies/delete-questionnaire-to-company/{client_id}/{questionnaire_id}', function($client_id, $questionnaire_id) use ($app){
	$sql = "DELETE FROM r_clientes_cuestionarios WHERE id_cliente = ? AND id_cuestionario = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $client_id);
	$stmt->bindValue(2, $questionnaire_id);
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


// This controller upload the logo file to the server
$app->post('/v1/companies/upload-logo', function(Request $request) use ($app){
	$file = $request->files->get('file');

	if($file !== null){
        $path = '../../console/img/main-logo/';
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


// This controller adds the logo file to the company
$app->post('/v1/companies/add-logo', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$logotipo = $request->request->get('logotipo');
	$company_id = $request->request->get('company_id');
	$date = date('Y-m-d H:i:s');

	$sql = "UPDATE empresas SET logotipo = ?, fecha_modificacion = ? WHERE id_empresa = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $logotipo);
	$stmt->bindValue(2, $date);
	$stmt->bindValue(3, $company_id);
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

$app->get('/v1/companies/get-main-logo/{branch_id}', function($branch_id) use ($app){
	/*$sql = "SELECT CONCAT('https://bluehand.com.mx/console/img/main-logo/',e.logotipo) AS logo FROM empresas AS e
			LEFT JOIN r_clientes_plantas_proveedores AS rcpp ON rcpp.id_empresa = e.id_empresa
			WHERE rcpp.id_planta_proveedor = $branch_id";*/

	$sql = "SELECT CONCAT('https://bluehand.com.mx/console/img/main-logo/',e.logotipo) AS logo 
			FROM empresas AS e
			WHERE id_empresa = $branch_id";
	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$logo = $stmt->fetch();

	return $app->json($logo);
});
