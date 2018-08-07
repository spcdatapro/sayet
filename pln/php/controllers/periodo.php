<?php

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');

require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require PLNPATH . '/Principal.php';
require PLNPATH . '/models/Periodo.php';
require PLNPATH . '/models/General.php';

$app = new \Slim\Slim();

$app->get('/get_periodo/:id', function($id){
    $e = new Periodo($id);

    enviar_json(['periodo' => $e->periodo]);
});

$app->get('/buscar', function(){
	$b = new General();

	$resultados = $b->buscar_periodo($_GET);
	
	enviar_json([
		'cantidad'   => count($resultados), 
		'resultados' => $resultados, 
		'maximo'     => get_limite()
	]);
});

$app->get('/lista', function(){
	$b = new General();
	
	enviar_json($b->buscar_periodo($_GET));
});

$app->post('/guardar', function(){
	$datos = (array)json_decode(file_get_contents('php://input'), TRUE);
	$data  = ['exito' => 0, 'up' => 0];

	if (elemento($datos, 'inicio') && elemento($datos, 'fin')) {
		$inicio = $datos['inicio'];
		$fin    = $datos['fin'];

		if (date('m-Y', strtotime($inicio)) === date('m-Y', strtotime($fin))) {
			$diaInicio = date('d', strtotime($inicio));
			$diaFin    = date('d', strtotime($fin));
			$ultimo    = date('t', strtotime($fin));

			if (in_array($diaInicio, [1,16]) && in_array($diaFin, [15,$ultimo])) {
				$prd = new Periodo();

				if (elemento($datos, 'id')) {
					$data['up'] = 1;
					$prd->cargar_periodo($datos['id']);
				}

				if (elemento($datos, 'cerrado', 0) && $prd->hay_abierto()) {
					$data['mensaje'] = 'No puede tener más de un período abierto.';
				} else {
					if ($prd->guardar($datos)) {
						$data['exito']   = 1;
						$data['mensaje'] = 'Se ha guardado con éxito.';
						$data['periodo'] = $prd->periodo;
					} else {
						$data['mensaje'] = $prd->get_mensaje();
						$data['periodo'] = $datos;
					}
				}
			} else {
				$data['mensaje'] = 'Rango de fechas no permitido. Por favor verifique que sea una quincena válida.';
			}
		} else {
			$data['mensaje'] = 'Las fechas deben ser del mismo mes.';
		}
	} else {
		$data['mensaje'] = 'Por favor ingrese los campos de inicio y fin.';
	}

    enviar_json($data);
});

$app->run();