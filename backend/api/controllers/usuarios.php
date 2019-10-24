<?php

use Symfony\Component\HttpFoundation\Request;

// Login user
$app->post('/v1/users/login', function(Request $request) use ($app){

	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$user_name = $request->request->get('user_name');
	$password = sha1($request->request->get('password'));

	$sql_login = $app['db']->createQueryBuilder();
	$sql_login
		->select('COUNT(*) AS login')
		->from('usuarios', 'u')
		->where('correo_electronico = ?')
		->andWhere('contrasenia = ?')
		->andWhere('estado = 1');
	$stmt = $app['db']->prepare($sql_login);
	$stmt->bindValue(1, $user_name);
	$stmt->bindValue(2, $password);
	$stmt->execute();
	$login = $stmt->fetch();

	if($login['login'] == "1"){
		$response = array(
			"result_code" => 1,
			"message" => "Ha iniciado sesión.",
			"email" => $user_name
		);
	}else{
		$response = array(
			"result_code" => 0,
			"message" => "El nombre de usuario y/o contraseña son incorrectos.",
			"email" => null
		);
	}

	return $app->json($response);

});


// Gets all the users (only for administrators)
$app->get('/v1/users/get', function() use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('u.id_usuario AS id, u.id_empresa AS company_id, u.id_tipo_usuario AS id_user_type, tu.tipo AS user_type, u.id_privilegios AS id_privileges, pu.privilegio AS privilege, u.nombre AS name, u.apellido_paterno AS lastname, u.apellido_materno AS second_lastname, u.usuario AS username, u.correo_electronico AS email, u.estado AS status, u.fecha_alta AS creation_date')
		->from('usuarios', 'u')
		->leftJoin('u', 'tipos_usuario', 'tu', 'tu.id_tipo_usuario = u.id_tipo_usuario')
		->leftJoin('u', 'privilegios_usuario', 'pu', 'pu.id_privilegio = u.id_privilegios')
		->orderBy('u.fecha_alta', 'DESC');
	$stmt = $app['db']->prepare($sql);
	$stmt->execute();
	$users = $stmt->fetchAll();

	return $app->json($users);
});


// Create a new user
$app->post('/v1/users/create', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$id_company = $request->request->get('id_company');
	$id_user_type = $request->request->get('id_user_type');
	$id_privileges = $request->request->get('id_privileges');
	$is_regional_manager = $request->request->get('is_regional_manager');
	$name = $request->request->get('name');
	$lastname = $request->request->get('lastname');
	$second_lastname = $request->request->get('second_lastname');
	$user_name = $request->request->get('user_name');
	$email = $request->request->get('email');
	$password = sha1($request->request->get('password'));
	$is_reviser = $request->request->get('is_reviser');
	$status = 1;
	$date = date('Y-m-d H:i:s');

	// Validating if the user is already registered based on the email
	$sql_validate = $app['db']->createQueryBuilder();
	$sql_validate
		->select('COUNT(*) AS exist')
		->from('usuarios', 'u')
		->where('correo_electronico = ?');
	$stmt = $app['db']->prepare($sql_validate);
	$stmt->bindValue(1, $email);
	$stmt->execute();
	$exist = $stmt->fetch();

	if($exist['exist'] == "0"){
		$sql = "INSERT INTO usuarios
					(id_empresa,
					id_tipo_usuario,
					id_privilegios,
					nombre,
					apellido_paterno,
					apellido_materno,
					usuario,
					correo_electronico,
					contrasenia,
					estado,
					es_revisor,
					es_regional,
					fecha_alta)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $id_company);
		$stmt->bindValue(2, $id_user_type);
		$stmt->bindValue(3, $id_privileges);
		$stmt->bindValue(4, $name);
		$stmt->bindValue(5, $lastname);
		$stmt->bindValue(6, $second_lastname);
		$stmt->bindValue(7, $user_name);
		$stmt->bindValue(8, $email);
		$stmt->bindValue(9, $password);
		$stmt->bindValue(10, $status);
		$stmt->bindValue(11, $is_reviser);
		$stmt->bindValue(12, $is_regional_manager);
		$stmt->bindValue(13, $date);
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
			"message" => "El correo electrónico ingresado ya fue registrado anteriormente."
		);
	}

	return $app->json($response);
});


// Edit the user information
$app->post('/v1/users/modify', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$id_company = $request->request->get('id_company');
	$id_user_type = $request->request->get('id_user_type');
	$id_privileges = $request->request->get('id_privileges');
	$is_regional_manager = $request->request->get('is_regional_manager');
	$name = $request->request->get('name');
	$lastname = $request->request->get('lastname');
	$second_lastname = $request->request->get('second_lastname');
	$user_name = $request->request->get('user_name');
	$email = $request->request->get('email');
	$status = $request->request->get('status');
	$is_reviser = $request->request->get('is_reviser');
	$date = date('Y-m-d H:i:s');
	$user_id = $request->request->get('user_id');

	$sql = "UPDATE
				usuarios
			SET
				id_empresa = ?,
				id_tipo_usuario = ?,
				id_privilegios = ?,
				nombre = ?,
				apellido_paterno = ?,
				apellido_materno = ?,
				usuario = ?,
				correo_electronico = ?,
				estado = ?,
				es_revisor = ?,
				es_regional = ?,
				fecha_modificacion = ?
			WHERE id_usuario = ?";

	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $id_company);
	$stmt->bindValue(2, $id_user_type);
	$stmt->bindValue(3, $id_privileges);
	$stmt->bindValue(4, $name);
	$stmt->bindValue(5, $lastname);
	$stmt->bindValue(6, $second_lastname);
	$stmt->bindValue(7, $user_name);
	$stmt->bindValue(8, $email);
	$stmt->bindValue(9, $status);
	$stmt->bindValue(10, $is_reviser);
	$stmt->bindValue(11, $is_regional_manager);
	$stmt->bindValue(12, $date);
	$stmt->bindValue(13, $user_id);
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


// Get the user information based on the email
$app->post('/v1/user/get', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$email = $request->request->get('email');

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('u.id_empresa AS company_id, u.id_usuario AS id, u.nombre AS name, u.apellido_paterno AS lastname, u.apellido_materno AS second_lastname, u.usuario AS user_name, u.correo_electronico AS email, u.estado AS status, tu.tipo AS user_type, u.id_privilegios AS privilege, u.es_revisor AS reviser, es_regional AS is_regional')
		->from('usuarios', 'u')
		->leftJoin('u', 'tipos_usuario', 'tu', 'tu.id_tipo_usuario = u.id_tipo_usuario')
		->where('u.correo_electronico = ?');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $email);
	$stmt->execute();
	$user = $stmt->fetch();

	return $app->json($user);
});


// Get one user by id
$app->get('/v1/user/get-by-id/{user_id}', function($user_id) use ($app){

	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('u.id_usuario AS id, tu.tipo AS user_type, u.id_tipo_usuario AS id_user_type, u.id_empresa AS company_id, u.id_privilegios AS privilege_level, u.nombre AS name, u.apellido_paterno AS lastname, u.apellido_materno AS second_lastname, u.usuario AS username, u.correo_electronico AS email, u.estado AS status, u.es_revisor AS reviser, u.es_regional AS is_regional, u.fecha_alta AS creation_date')
		->from('usuarios', 'u')
		->leftJoin('u', 'tipos_usuario', 'tu', 'tu.id_tipo_usuario = u.id_tipo_usuario')
		->where('u.id_usuario = ?')
		->orderBy('u.fecha_alta', 'DESC');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $user_id);
	$stmt->execute();
	$user = $stmt->fetch();

	return $app->json($user);
});


// Delete a user
$app->get('/v1/users/delete/{user_id}', function($user_id) use ($app){
	$sql = "DELETE FROM usuarios WHERE id_usuario = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $user_id);
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


// Get users by company and type
$app->get('/v1/users/get-by-company-and-type/{manager_id}/{company_id}/{user_type_id}', function($manager_id, $company_id, $user_type_id) use ($app){
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('u.id_usuario AS id, u.id_empresa AS company_id, u.id_tipo_usuario AS id_user_type, tu.tipo AS user_type, u.id_privilegios AS privilege_level, pu.privilegio AS privilege, u.nombre AS name, u.apellido_paterno AS lastname, u.apellido_materno AS second_lastname, u.usuario AS username, u.correo_electronico AS email, u.estado AS status, u.fecha_alta AS creation_date')
		->from('usuarios', 'u')
		->leftJoin('u', 'tipos_usuario', 'tu', 'tu.id_tipo_usuario = u.id_tipo_usuario')
		->leftJoin('u', 'privilegios_usuario', 'pu', 'pu.id_privilegio = u.id_privilegios')
		->where('u.id_empresa = ?')
		->andWhere('u.id_tipo_usuario = ?')
		->andWhere('u.id_privilegios = 3');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $company_id);
	$stmt->bindValue(2, $user_type_id);
	$stmt->execute();
	$users = $stmt->fetchAll();
	$arr_users = array();

	for($i=0; $i<count($users); $i++){
		$sql_check = "SELECT COUNT(*) AS is_added FROM r_gerentes_auditores WHERE id_gerente = ? AND id_auditor = ?";
		$stmt = $app['db']->prepare($sql_check);
		$stmt->bindValue(1, $manager_id);
		$stmt->bindValue(2, $users[$i]['id']);
		$stmt->execute();
		$is_added = $stmt->fetch();

		$arr_users[$i]['id'] = $users[$i]['id'];
		$arr_users[$i]['company_id'] = $users[$i]['company_id'];
		$arr_users[$i]['id_user_type'] = $users[$i]['id_user_type'];
		$arr_users[$i]['user_type'] = $users[$i]['user_type'];
		$arr_users[$i]['privilege_level'] = $users[$i]['privilege_level'];
		$arr_users[$i]['privilege'] = $users[$i]['privilege'];
		$arr_users[$i]['name'] = $users[$i]['name'];
		$arr_users[$i]['lastname'] = $users[$i]['lastname'];
		$arr_users[$i]['second_lastname'] = $users[$i]['second_lastname'];
		$arr_users[$i]['username'] = $users[$i]['username'];
		$arr_users[$i]['email'] = $users[$i]['email'];
		$arr_users[$i]['status'] = $users[$i]['status'];
		$arr_users[$i]['creation_date'] = $users[$i]['creation_date'];
		$arr_users[$i]['is_added'] = $is_added['is_added'];
	}

	return $app->json($arr_users);
});


// This controller add user auditors to users managers
$app->post('/v1/users/add-users-to-managers', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$manager_id = $request->request->get('manager_id');
	$auditor_id = $request->request->get('auditor_id');
	$date = date('Y-m-d H:i:s');

	// Cheking if the auditor is already added to rhe manager
	$sql_check = $app['db']->createQueryBuilder();
	$sql_check
		->select('COUNT(*) AS exist')
		->from('r_gerentes_auditores', 'rga')
		->where('rga.id_gerente = ?')
		->andWhere('rga.id_auditor = ?');
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $manager_id);
	$stmt->bindValue(2, $auditor_id);
	$stmt->execute();
	$check = $stmt->fetch();

	if($check['exist'] === "0"){
		$save = "INSERT INTO r_gerentes_auditores (id_gerente, id_auditor, fecha_alta) VALUES (?, ?, ?)";
		$stmt = $app['db']->prepare($save);
		$stmt->bindValue(1, $manager_id);
		$stmt->bindValue(2, $auditor_id);
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


// This controller delete a auditor that is added to a manager
$app->get('/v1/users/delete-users-to-managers/{manager_id}/{auditor_id}', function($manager_id, $auditor_id) use ($app){
	$sql = "DELETE FROM r_gerentes_auditores WHERE id_gerente = ? AND id_auditor = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $manager_id);
	$stmt->bindValue(2, $auditor_id);
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


// This controller relates a manager with a company
$app->post('/v1/users/add-user-to-company', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$manager_id = $request->request->get('manager_id');
	$company_id = $request->request->get('company_id');
	$date = date('Y-m-d H:i:s');

	// Cheking if the manager is already added to the company
	$sql_check = $app['db']->createQueryBuilder();
	$sql_check
		->select('COUNT(*) AS exist')
		->from('r_gerentes_empresas', 'rge')
		->where('rge.id_gerente = ?')
		->andWhere('rge.id_empresa = ?');
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $manager_id);
	$stmt->bindValue(2, $company_id);
	$stmt->execute();
	$check = $stmt->fetch();

	if($check['exist'] === '0'){
		$save = "INSERT INTO r_gerentes_empresas (id_gerente, id_empresa, fecha_alta) VALUES (?, ?, ?)";
		$stmt = $app['db']->prepare($save);
		$stmt->bindValue(1, $manager_id);
		$stmt->bindValue(2, $company_id);
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


// This controller delete a manager that is added to a company
$app->get('/v1/users/delete-managers-to-companies/{manager_id}/{company_id}', function($manager_id, $company_id) use ($app){
	$sql = "DELETE FROM r_gerentes_empresas WHERE id_gerente = ? AND id_empresa = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $manager_id);
	$stmt->bindValue(2, $company_id);
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


// This controller gets all the companies that are available for a manager
$app->get('/v1/users/get-companies/{manager_id}/{manager_type}', function($manager_id, $manager_type) use ($app){
	$arr_companies = array();
	$arr_branches = array();

	// Validating if the user is internal or external
	if($manager_type == 1){
		// Internal users
		$sql = $app['db']->createQueryBuilder();
		$sql
			->select('e.id_empresa AS id, te.tipo AS type, e.nombre_comercial AS trade_name, e.razon_social AS business_name, e.telefono AS telephone_number, e.calle_numero AS street_and_number, e.colonia AS suburb, e.delegacion AS municipality, e.estado AS state, e.codigo_postal AS zip_code, e.fecha_alta AS creation_date')
			->from('empresas', 'e')
			->leftJoin('e', 'tipos_empresa', 'te', 'te.id_tipo_empresa = e.id_tipo_empresa')
			->where('e.id_tipo_empresa = 1')
			->orderBy('e.id_empresa', 'ASC');
		$stmt = $app['db']->prepare($sql);
		$stmt->execute();
		$companies = $stmt->fetchAll();
	}elseif($manager_type == 2){
		$sql_user = $app['db']->createQueryBuilder();
		$sql_user
			->select('u.id_empresa AS id')
			->from('usuarios', 'u')
			->where('u.id_usuario = ?');
		$stmt = $app['db']->prepare($sql_user);
		$stmt->bindValue(1, $manager_id);
		$stmt->execute();
		$company_id = $stmt->fetch();

		// Getting the client that is associated to the user
		$sql = $app['db']->createQueryBuilder();
		$sql
			->select('e.id_empresa AS id, te.tipo AS type, e.nombre_comercial AS trade_name, e.razon_social AS business_name, e.telefono AS telephone_number, e.calle_numero AS street_and_number, e.colonia AS suburb, e.delegacion AS municipality, e.estado AS state, e.codigo_postal AS zip_code, e.fecha_alta AS creation_date')
			->from('empresas', 'e')
			->leftJoin('e', 'tipos_empresa', 'te', 'te.id_tipo_empresa = e.id_tipo_empresa')
			->where('e.id_empresa = ?');
		$stmt = $app['db']->prepare($sql);
		$stmt->bindValue(1, $company_id['id']);
		$stmt->execute();
		$companies = $stmt->fetchAll();
	}

	// Getting all the suppliers and plants that belong to each client
	for($i=0; $i<count($companies); $i++){
		// Check if the client is associated to the user
		$sql_check = $app['db']->createQueryBuilder();
		$sql_check
			->select('COUNT(*) AS is_added')
			->from('r_gerentes_empresas', 'rge')
			->where('rge.id_gerente = ?')
			->andWhere('rge.id_empresa = ?');
		$stmt = $app['db']->prepare($sql_check);
		$stmt->bindValue(1, $manager_id);
		$stmt->bindValue(2, $companies[$i]['id']);
		$stmt->execute();
		$is_added = $stmt->fetch();

		$sql_branches = $app['db']->createQueryBuilder();
		$sql_branches
			->select('e.id_empresa AS id, te.tipo AS type, e.nombre_comercial AS trade_name, e.razon_social AS business_name, e.telefono AS telephone_number, e.calle_numero AS street_and_number, e.colonia AS suburb, e.delegacion AS municipality, e.estado AS state, e.codigo_postal AS zip_code, e.fecha_alta AS creation_date')
			->from('empresas', 'e')
			->leftJoin('e', 'r_clientes_plantas_proveedores', 'rcpp', 'rcpp.id_planta_proveedor = e.id_empresa')
			->leftJoin('e', 'tipos_empresa', 'te', 'te.id_tipo_empresa = e.id_tipo_empresa')
			->where('rcpp.id_empresa = ?')
			->orderBy('e.id_empresa', 'ASC');
		$stmt = $app['db']->prepare($sql_branches);
		$stmt->bindValue(1, $companies[$i]['id']);
		$stmt->execute();
		$branches = $stmt->fetchAll();

		for($j=0; $j<count($branches); $j++){
			// // Check if the branch is associated to the user
			$sql_check = $app['db']->createQueryBuilder();
			$sql_check
				->select('COUNT(*) AS is_added')
				->from('r_gerentes_empresas', 'rge')
				->where('rge.id_gerente = ?')
				->andWhere('rge.id_empresa = ?');
			$stmt = $app['db']->prepare($sql_check);
			$stmt->bindValue(1, $manager_id);
			$stmt->bindValue(2, $branches[$j]['id']);
			$stmt->execute();
			$is_added_branch = $stmt->fetch();

			$arr_branches[$j]['id'] = $branches[$j]['id'];
			$arr_branches[$j]['type'] = $branches[$j]['type'];
			$arr_branches[$j]['trade_name'] = $branches[$j]['trade_name'];
			$arr_branches[$j]['business_name'] = $branches[$j]['business_name'];
			$arr_branches[$j]['telephone_number'] = $branches[$j]['telephone_number'];
			$arr_branches[$j]['street_and_number'] = $branches[$j]['street_and_number'];
			$arr_branches[$j]['suburb'] = $branches[$j]['suburb'];
			$arr_branches[$j]['municipality'] = $branches[$j]['municipality'];
			$arr_branches[$j]['state'] = $branches[$j]['state'];
			$arr_branches[$j]['zip_code'] = $branches[$j]['zip_code'];
			$arr_branches[$j]['creation_date'] = $branches[$j]['creation_date'];
			$arr_branches[$j]['is_added'] = $is_added_branch['is_added'];
		}

		// Getting the final array that is going to be returned
		$arr_companies[$i]['id'] = $companies[$i]['id'];
		$arr_companies[$i]['type'] = $companies[$i]['type'];
		$arr_companies[$i]['trade_name'] = $companies[$i]['trade_name'];
		$arr_companies[$i]['business_name'] = $companies[$i]['business_name'];
		$arr_companies[$i]['telephone_number'] = $companies[$i]['telephone_number'];
		$arr_companies[$i]['street_and_number'] = $companies[$i]['street_and_number'];
		$arr_companies[$i]['suburb'] = $companies[$i]['suburb'];
		$arr_companies[$i]['municipality'] = $companies[$i]['municipality'];
		$arr_companies[$i]['state'] = $companies[$i]['state'];
		$arr_companies[$i]['zip_code'] = $companies[$i]['zip_code'];
		$arr_companies[$i]['creation_date'] = $companies[$i]['creation_date'];
		$arr_companies[$i]['is_added'] = $is_added['is_added'];
		$arr_companies[$i]['plants_and_suppliers'] = $arr_branches;
	}
	

	return $app->json($arr_companies);
});


// This controller gets all the companies that are available for an auditor
$app->get('/v1/users/get-companies-for-auditors/{auditor_id}', function($auditor_id) use ($app){
	// Getting the manager id
	$sql = $app['db']->createQueryBuilder();
	$sql
		->select('rga.id_gerente AS manager_id, e.id_empresa AS company_id, e.nombre_comercial AS trade_name')
		->from('r_gerentes_auditores', 'rga')
		->leftJoin('rga', 'r_gerentes_empresas', 'rge', 'rge.id_gerente = rga.id_gerente')
		->leftJoin('rge', 'empresas', 'e', 'e.id_empresa = rge.id_empresa')
		->where('rga.id_auditor = ? group by e.id_empresa');
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $auditor_id);
	$stmt->execute();
	$companies = $stmt->fetchAll();

	$result = array();

	// Cheking if the company is already assigned to the auditor
	for($i=0; $i<count($companies); $i++){
		$sql_check = $app['db']->createQueryBuilder();
		$sql_check
			->select('COUNT(*) AS assigned')
			->from('r_auditores_empresas', 'rae')
			->where('rae.id_auditor = ?')
			->andWhere('rae.id_empresa = ?');
		$stmt = $app['db']->prepare($sql_check);
		$stmt->bindValue(1, $auditor_id);
		$stmt->bindValue(2, $companies[$i]['company_id']);
		$stmt->execute();
		$is_assigned = $stmt->fetch();

		$result[$i]['manager_id'] = $companies[$i]['manager_id'];
		$result[$i]['company_id'] = $companies[$i]['company_id'];
		$result[$i]['trade_name'] = $companies[$i]['trade_name'];
		$result[$i]['is_assigned'] = $is_assigned['assigned'];
	}
	

	return $app->json($result);
});


// This controller relates an auditor with a company
$app->post('/v1/users/add-auditor-to-company', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$auditor_id = $request->request->get('auditor_id');
	$company_id = $request->request->get('company_id');
	$date = date('Y-m-d H:i:s');

	// Cheking if the manager is already added to the company
	$sql_check = $app['db']->createQueryBuilder();
	$sql_check
		->select('COUNT(*) AS exist')
		->from('r_auditores_empresas', 'rae')
		->where('rae.id_auditor = ?')
		->andWhere('rae.id_empresa = ?');
	$stmt = $app['db']->prepare($sql_check);
	$stmt->bindValue(1, $auditor_id);
	$stmt->bindValue(2, $company_id);
	$stmt->execute();
	$check = $stmt->fetch();

	if($check['exist'] === '0'){
		$save = "INSERT INTO r_auditores_empresas (id_auditor, id_empresa, fecha_alta) VALUES (?, ?, ?)";
		$stmt = $app['db']->prepare($save);
		$stmt->bindValue(1, $auditor_id);
		$stmt->bindValue(2, $company_id);
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


// This controller deletes an auditor that is added to a company
$app->get('/v1/users/delete-auditors-to-companies/{auditor_id}/{company_id}', function($auditor_id, $company_id) use ($app){
	$sql = "DELETE FROM r_auditores_empresas WHERE id_auditor = ? AND id_empresa = ?";
	$stmt = $app['db']->prepare($sql);
	$stmt->bindValue(1, $auditor_id);
	$stmt->bindValue(2, $company_id);
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


// This controller change the user password
$app->post('/v1/user/change-password', function(Request $request) use ($app){
	$data = json_decode($request->getContent(), true);
	$request->request->replace(is_array($data) ? $data : array());

	$email = $request->request->get('email');
	$current_password = sha1($request->request->get('current_password'));
	$new_password = sha1($request->request->get('new_password'));

	// Validating the current password
	$sql_cur_password = $app['db']->createQueryBuilder();
	$sql_cur_password
		->select('u.contrasenia AS current_password')
		->from('usuarios', 'u')
		->where('u.correo_electronico = ?');
	$stmt = $app['db']->prepare($sql_cur_password);
	$stmt->bindValue(1, $email);
	$stmt->execute();
	$response = $stmt->fetch();

	if($current_password == $response['current_password']){
		// Proceed to change the password
		$sql_update_password = "UPDATE usuarios SET contrasenia = ? WHERE correo_electronico = ?";
		$stmt = $app['db']->prepare($sql_update_password);
		$stmt->bindValue(1, $new_password);
		$stmt->bindValue(2, $email);
		$update = $stmt->execute();

		if($update == true){
			$result = array(
				"result_code" => 1,
				"message" => "El registro se ha actualizado exitosamente."
			);
		}else{
			$result = array(
				"result_code" => 0,
				"message" => "Ha ocurrido un error. Intente de nuevo más tarde."
			);
		}

	}else{
		$result = array(
			"result_code" => 0,
			"message" => "La contraseña actual no es correcta."
		);
	}

	return $app->json($result);
});

