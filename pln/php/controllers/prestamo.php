<?php 

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');

require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require BASEPATH . "/php/NumberToLetterConverter.class.php";
require PLNPATH . '/Principal.php';
require PLNPATH . '/models/Prestamo.php';
require PLNPATH . '/models/General.php';

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

# imprimir saldos prestamos
$app->get('/proyeccion', function(){
	$g = new General();

	if (elemento($_GET, 'fdel')) {
		$todos = $g->buscar_prestamo([
			'orden'      => 'empleado',
			'empresa'    => isset($_GET['empresa']) ? $_GET['empresa'] : NULL,
			'empleado'   => isset($_GET['empleado']) ? $_GET['empleado'] : NULL,
			'finalizado' => 0, 
			'sinlimite'  => TRUE
		]);

		if (count($todos) > 0) {
			require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';
			$tipoImpresion = 13;

			$s = [215.9, 279.4]; # Carta mm

			$pdf = new TCPDF('P', 'mm', $s);
			$pdf->SetAutoPageBreak(TRUE, 0);
			$pdf->AddPage();

			$datos     = [];
			$registros = 0;

			foreach ($todos as $fila) {
				if (isset($datos[$fila['idempresaactual']])) {
					$datos[$fila['idempresaactual']]['prestamos'][] = new Prestamo($fila['id']);
				} else {
					$emp = $g->get_empresa(['id' => $fila['idempresaactual'], 'uno' => TRUE]);

					$datos[$fila['idempresaactual']] = [
						'nombre'    => $emp['nomempresa'], 
						'prestamos' => [new Prestamo($fila['id'])]
					];
				}
			}

			$cabecera = [
				'sp_titulo'              => 'Módulo de Planillas',
				'sp_subtitulo'           => 'PROYECCIÓN DE PRÉSTAMOS',
				'sp_fecha'               => "Del ".formatoFecha($_GET['fdel'], 1),
				't_codigo'               => 'Código',
				't_nombre'               => 'Nombre:',
				't_vale'                 => 'Vale',
				't_fecha'                => 'Fecha',
				't_valor_prestamo'       => "Valor\nPréstamo",
				't_descuento_mensual'    => "Descuento\nMensual",
				't_saldo_anterior'       => "Saldo",
				't_linea'                => str_repeat("_", 160)
			];

			$rpag    = 45; # Registros por página
			$espacio = 0;

			foreach ($datos as $key => $empresa) {
				$registros++;

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pdf->AddPage();
				}
				
				$confe      = $g->get_campo_impresion('v_empresa', $tipoImpresion);
				$confe->psy = ($confe->psy+$espacio);
				$espacio    += $confe->espacio;
				$pdf        = generar_fimpresion($pdf, "{$key} {$empresa['nombre']}", $confe);

				$etotales = [];

				foreach ($empresa['prestamos'] as $prestamo) {
					$registros++;

					$emp = $prestamo->get_empleado();

					$tmpdatos = [
						'v_codigo' => $emp->id,
						'v_nombre' => "{$emp->nombre} {$emp->apellidos}",
						'v_vale' => $prestamo->pre->id,
						'v_fecha' => formatoFecha($prestamo->pre->iniciopago, 1),
						'v_valor_prestamo' => $prestamo->pre->monto,
						'v_descuento_mensual' => $prestamo->pre->cuotamensual,
						'v_saldo_anterior' => $prestamo->get_saldo_anterior(['fecha' => $_GET['fdel']])
					];

					foreach ($tmpdatos as $campo => $valor) {
					
						$conf = $g->get_campo_impresion($campo, $tipoImpresion);

						if (!isset($conf->scalar) && $conf->visible == 1) {
							$conf->psy = ($conf->psy+$espacio);

							$nonumerico = ['v_vale', 'v_codigo'];

							if (is_numeric($valor) && !in_array($campo, $nonumerico)) {
								$valor = number_format($valor, 2);
							} else {
								$valor = $valor;
							}

							$pdf = generar_fimpresion($pdf, $valor, $conf);
						}
					}

					$espacio += $confe->espacio;

					if ($registros == $rpag) {
						$espacio   = 0;
						$registros = 0;
						$pdf->AddPage();
					}

					$proyeccion = $prestamo->get_proyeccion($_GET);

					if (count($proyeccion) > 0) {
						foreach ($proyeccion as $fila) {
							$registros++;

							foreach ($fila as $campo => $valor) {
								$conf = $g->get_campo_impresion($campo, $tipoImpresion);

								if (!isset($conf->scalar) && $conf->visible == 1) {
									$conf->psy = ($conf->psy+$espacio);

									$numericos = ['v_descuento_mensual', 'v_saldo_anterior'];

									if (in_array($campo, $numericos)) {
										$valor = number_format($valor, 2);
									} else {
										$valor = $valor;
									}

									$pdf = generar_fimpresion($pdf, $valor, $conf);
								}			
							}

							$espacio += $confe->espacio;

							if ($registros == $rpag) {
								$espacio   = 0;
								$registros = 0;
								$pdf->AddPage();
							}
						}
					}

						
				}

				$registros++;

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pdf->AddPage();
				}

				$pdf->SetLineStyle(array(
					'width' => 0.2, 
					'cap' => 'butt', 
					'join' => 'miter', 
					'dash' => 0, 
					'color' => array(0, 0, 0)
				));

				$espacio += $confe->espacio;	
			}

			for ($i=1; $i <= $pdf->getNumPages(); $i++) { 
				$pdf->setPage($i);

				foreach ($cabecera as $campo => $valor) {
					$conf = $g->get_campo_impresion($campo, $tipoImpresion);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}
			}

			$pdf->Output("proyeccion_prestamos" . time() . ".pdf", 'I');
			die();
		} else {
			echo "Nada que mostrar.";
		}
	} else {
		echo "Faltan datos obligatorios.";
	}
});

$app->get('/test/:prestamo', function($prestamo){
	$pre = new Prestamo($prestamo);
	#echo "<br>Saldo normal: " . $pre->get_saldo();
	echo "<br>Saldo sin: " . $pre->get_saldo(['sin_idplnnomina' => 4065]);
	echo "<br>Saldo anterior: " . $pre->get_saldo_anterior(['fecha' => '2018-06-30']);
	$pre->finalizar();
});

$app->run();