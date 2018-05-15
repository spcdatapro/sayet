<?php 

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

session_start();

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln');

require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require BASEPATH . "/php/NumberToLetterConverter.class.php";

require PLNPATH . '/php/Principal.php';
require PLNPATH . '/php/models/Prestamo.php';
require PLNPATH . '/php/models/General.php';

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

$app->post('/guardar_omision/:prestamo', function($prestamo){
	$data = ['exito' => 0];

	$pre = new Prestamo($prestamo);
	
	if ($pre->guardar_omision($_POST)) {
		$data['exito']   = 1;
		$data['mensaje'] = 'Se ha guardado con èxito.';
	} else {
		$data['mensaje'] = $pre->get_mensaje();
	}
	
	enviar_json($data);
});

$app->get('/ver_omisiones/:prestamo', function($prestamo){
	$pre = new Prestamo($prestamo);
	enviar_json(['omisiones' => $pre->get_omisiones()]);
});

$app->post('/guardar_abono/:prestamo', function($prestamo){
	$data = ['exito' => 0];

	$pre = new Prestamo($prestamo);
	
	if ($pre->guardar_abono($_POST)) {
		$data['exito']   = 1;
		$data['mensaje'] = 'Se ha guardado con èxito.';
	} else {
		$data['mensaje'] = $pre->get_mensaje();
	}
	
	enviar_json($data);
});

$app->get('/ver_abonos/:prestamo', function($prestamo){
	$pre = new Prestamo($prestamo);
	enviar_json(['abonos' => $pre->get_abonos()]);
});

$app->get('/imprimir/:prestamo', function($prestamo){
	$gen = new General();
	$pre = new Prestamo($prestamo);
	
	require BASEPATH . '/libs/tcpdf/tcpdf.php';

	$s = [215.9, 279.4]; # Carta mm

	$pdf = new TCPDF('P', 'mm', $s);
	$pdf->AddPage();

	foreach ($pre->get_datos_impresion() as $campo => $valor) {
		$conf = $gen->get_campo_impresion($campo, 6);

		if (!isset($conf->scalar) && $conf->visible == 1) {
			$pdf = generar_fimpresion($pdf, $valor, $conf);
		}
	}

	$pdf->Output("prestamo_{$pre->pre->id}.pdf", 'I');
	die();

});

$app->run();