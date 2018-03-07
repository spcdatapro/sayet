<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(dirname(dirname(__DIR__))) . '/php/vendor/autoload.php';
require dirname(dirname(dirname(__DIR__))) . '/php/ayuda.php';
require dirname(__DIR__) . '/Principal.php';
require dirname(__DIR__) . '/models/Prestamo.php';
require dirname(__DIR__) . '/models/General.php';

$app = new \Slim\Slim();

$app->get('/get_puesto/:id', function($id){
    $e = new Puesto($id);

    enviar_json(['puesto' => $e->pts]);
});

$app->get('/buscar', function(){
	$b = new General();

	$resultados = $b->buscar_prestamo($_GET);
	
	enviar_json([
		'cantidad'   => count($resultados), 
		'resultados' => $resultados, 
		'maximo'     => get_limite()
	]);
});

$app->get('/lista', function(){
	$b = new General();
	
	enviar_json($b->buscar_puesto(['sin_limite' => TRUE]));
});

$app->post('/guardar', function(){
	$data = ['exito' => 0, 'up' => 0];

	$p = new Prestamo();

	if (elemento($_POST, 'id')) {
		$data['up'] = 1;
		$p->cargar_prestamo($_POST['id']);
	}

	if ($p->guardar($_POST)) {
		$data['exito']    = 1;
		$data['mensaje']  = 'Se ha guardado con èxito.';
		$data['prestamo'] = $p->pre;
	} else {
		$data['mensaje']  = $p->get_mensaje();
		$data['prestamo'] = $_POST;
	}

    enviar_json($data);
});

$app->run();