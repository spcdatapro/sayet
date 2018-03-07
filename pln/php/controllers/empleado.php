<?php

require dirname(dirname(dirname(__DIR__))) . '/php/vendor/autoload.php';
require dirname(dirname(dirname(__DIR__))) . '/php/ayuda.php';
require dirname(__DIR__) . '/Principal.php';
require dirname(__DIR__) . '/models/Empleado.php';
require dirname(__DIR__) . '/models/General.php';

$app = new \Slim\Slim();

$app->get('/get_empleado/:empleado', function($empleado){
    $e = new Empleado($empleado);

    enviar_json(['emp' => $e->emp]);
});

$app->get('/buscar', function(){
	$b = new General();

	$resultados = $b->buscar_empleado($_GET);
	
	enviar_json([
		'cantidad'   => count($resultados), 
		'resultados' => $resultados, 
		'maximo'     => get_limite()
	]);
});

$app->post('/guardar', function(){
	$datos = (array)json_decode(file_get_contents('php://input'), TRUE);

	$data = ['exito' => 0, 'up' => 0];

	$e = new Empleado();

	if (elemento($datos, 'id')) {
		$data['up'] = 1;
		$e->cargar_empleado($datos['id']);
	}

	if ($e->guardar($datos)) {
		$data['exito']   = 1;
		$data['mensaje'] = 'Se ha guardado con èxito.';
		$data['emp']     = $e->emp;
	} else {
		$data['mensaje'] = $e->get_mensaje();
		$data['emp']     = $datos;
	}

    enviar_json($data);
});

$app->post('/agregar_archivo/:id', function($id){
	$data = ['exito' => 0];
	
	$e = new Empleado($id);

	if ($e->agregar_archivo($_POST, $_FILES)) {
		$data['exito'] = 1;
		$data['mensaje'] = 'Se agregó con éxito.';
	} else {
		$data['mensaje'] = $e->get_mensaje();
	}

	enviar_json($data);
});

$app->get('/get_archivos/:id', function($id){
	$e = new Empleado($id);

	enviar_json(['archivos' => $e->get_archivos()]);
});

$app->get('/get_archivotipo', function(){
	$g = new General();

	enviar_json($g->get_archivotipo());
});

$app->get('/prosueldo', function(){
	$b = new General();

	$resultados = $b->verificar_proempleado($_GET);
	
	enviar_json([
		'cantidad'   => count($resultados), 
		'resultados' => $resultados, 
		'maximo'     => get_limite()
	]);
});

$app->get('/buscar_prosueldo', function(){
	$b = new General();
	$b->verificar_proempleado($_GET);

	$datos = [];

	foreach ($b->get_prosueldo($_GET) as $row) {
		$datos[] = [
			'id'         => $row['id'], 
			'empleado'   => $row['idplnempleado'], 
			'nombre'     => $row['nombre'].' '.$row['apellidos'], 
			'enero'      => $row['enero'],
			'febrero'    => $row['febrero'],
			'marzo'      => $row['marzo'],
			'abril'      => $row['abril'],
			'mayo'       => $row['mayo'],
			'junio'      => $row['junio'],
			'julio'      => $row['julio'],
			'agosto'     => $row['agosto'],
			'septiembre' => $row['septiembre'],
			'octubre'    => $row['octubre'],
			'noviembre'  => $row['noviembre'],
			'diciembre'  => $row['diciembre']
		];
	}
	
	enviar_json($datos);
});

$app->post('/guardar_prosueldo', function(){
	$datos = (array)json_decode(file_get_contents('php://input'), TRUE);

	$data = ['exito' => 0];

	if (elemento($datos, 'empleado')) {
		$e = new Empleado();
		$e->cargar_empleado($datos['empleado']);

		if ($e->actualizar_prosueldo($datos)) {
			$data['exito']   = 1;
			$data['mensaje'] = 'Se ha guardado con èxito.';
		} else {
			$data['mensaje'] = $e->get_mensaje();
		}
	}

    enviar_json($data);
});

$app->run();