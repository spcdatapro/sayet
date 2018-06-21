<?php 
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');

require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require PLNPATH . '/Principal.php';
require PLNPATH . '/models/Puesto.php';
require PLNPATH . '/models/General.php';
/*
require dirname(dirname(dirname(__DIR__))) . '/php/vendor/autoload.php';
require dirname(dirname(dirname(__DIR__))) . '/php/ayuda.php';
require dirname(__DIR__) . '/Principal.php';
require dirname(__DIR__) . '/models/Puesto.php';
require dirname(__DIR__) . '/models/General.php';
*/
$app = new \Slim\Slim();

$app->get('/get_puesto/:id', function($id){
    $e = new Puesto($id);

    enviar_json(['puesto' => $e->pts]);
});

$app->get('/buscar', function(){
	$b = new General();

	$resultados = $b->buscar_puesto($_GET);
	
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
	$datos = (array)json_decode(file_get_contents('php://input'), TRUE);

	$data = ['exito' => 0, 'up' => 0];

	$p = new Puesto();

	if (elemento($datos, 'id')) {
		$data['up'] = 1;
		$p->cargar_puesto($datos['id']);
	}

	if ($p->guardar($datos)) {
		$data['exito']   = 1;
		$data['mensaje'] = 'Se ha guardado con èxito.';
		$data['puesto']  = $p->pst;
	} else {
		$data['mensaje'] = $p->get_mensaje();
		$data['emp']     = $datos;
	}

    enviar_json($data);
});

$app->run();