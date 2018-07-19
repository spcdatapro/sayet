<?php
use \setasign\Fpdi;

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
*/

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');

require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require BASEPATH . "/php/NumberToLetterConverter.class.php";
require PLNPATH . '/Principal.php';
require PLNPATH . '/models/Empleado.php';
require PLNPATH . '/models/General.php';

$app = new \Slim\Slim();

$app->get('/get_empleado/:empleado', function($empleado){
    $e = new Empleado($empleado);

    enviar_json(['emp' => $e->emp]);
});

$app->get('/get_bitacora/:empleado', function($empleado){
    $e = new Empleado($empleado);

    enviar_json($e->get_bitacora());
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

$app->get('/get_empresas', function(){
	$g = new General();

	enviar_json(['empresas' => $g->get_plnempresa()]);
});

$app->post('/finiquito', function(){
	if (elemento($_POST, 'empleado', FALSE)) {
		require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';

		# $_GET['fdel'] = formatoFecha($_GET['fal'], 4).'-'.formatoFecha($_GET['fal'], 3).'-16';

		$s = [215.9, 279.4]; # Carta mm

		$pdf = new TCPDF('P', 'mm', $s);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->AddPage();

		$emp = new Empleado($_POST['empleado']);
		$gen = new General();

		if (elemento($_POST, 'fecha_egreso')) {
			$emp->emp->baja = $_POST['fecha_egreso'];
		}

		$emp->set_meses_calculo($_POST['meses_calculo']);
		$emp->set_sueldo_promedio();
		$emp->set_finiquito_indemnizacion();
		$emp->set_finiquito_vacaciones($_POST);
		$emp->set_finiquito_aguinaldo();
		$emp->set_finiquito_bono14();
		$emp->set_finiquito_sueldo($_POST);

		foreach ($emp->get_datos_finiquito($_POST) as $campo => $valor) {
			$conf = $gen->get_campo_impresion($campo, 7);

			if (!isset($conf->scalar) && $conf->visible == 1) {
				$pdf = generar_fimpresion($pdf, $valor, $conf);
			}
		}

		# Detalle de sueldos
		$pdf->AddPage();

		$empresa = $emp->get_empresa_debito();

		$dos = [
			'dos_titulo'     => 'Detalle de cálculo sueldo promedio',
			'dos_fecha'      => $lugarFecha = "Guatemala, ".formatoFecha($_POST['fecha_egreso'],2)." de ".get_meses(formatoFecha($_POST['fecha_egreso'], 3))." de ".formatoFecha($_POST['fecha_egreso'],4),
			'dos_empleado'   => $emp->emp->nombre.' '.$emp->emp->apellidos,
			'dos_empresa'    => $empresa->nomempresa,
			'dos_linea1'     => str_repeat('_', 90),
			'dos_tspromedio' => "Sueldo Promedio sobre {$_POST['meses_calculo']} meses:",
			'dos_spromedio'  => number_format($emp->sueldoPromedio, 2),
			'dos_linea2'     => str_repeat('_', 90)
		];

		foreach ($dos as $campo => $valor) {
			$conf = $gen->get_campo_impresion($campo, 7);

			if (!isset($conf->scalar) && $conf->visible == 1) {
				$pdf = generar_fimpresion($pdf, $valor, $conf);
			}
		}

		$totales = [];
		$espacio = 0;

		foreach ($emp->get_sueldo_promedio(['detallado' => true]) as $row) {
			foreach ($row as $campo => $valor) {
				if (in_array($campo, ['sueldoordinario', 'mes', 'diastrabajados', 'anio'])) {
					$conf = $gen->get_campo_impresion("dos_{$campo}", 7);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						if ($campo == 'sueldoordinario') {
							if (isset($totales["dos_{$campo}"])) {
								$totales["dos_{$campo}"] += $valor;
							} else {
								$totales["dos_{$campo}"] = $valor;
							}
						}

						$conf->psy += $espacio;
						$valor = $campo == 'mes' ? ucfirst(get_meses($valor)) : ($campo=='sueldoordinario'?number_format($valor,2):$valor);
						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}
			}
			$espacio += 5;
		}

		$conf = $gen->get_campo_impresion('dos_linea3', 7);

		if (!isset($conf->scalar) && $conf->visible == 1) {
			$conf->psy += ($espacio-5);
			$pdf = generar_fimpresion($pdf, str_repeat('_', 25), $conf);
		}

		foreach ($totales as $campo => $valor) {
			$conf = $gen->get_campo_impresion($campo, 7);

			if (!isset($conf->scalar) && $conf->visible == 1) {
				$conf->psy += $espacio;
				$pdf = generar_fimpresion($pdf, number_format($valor,2), $conf);
			}
		}

		$pdf->Output("finiquito_laboral_" . time() . ".pdf", 'I');
		die();
	} else {
		echo "forbidden";
	}
});

$app->get('/descargar', function(){
	$bus = new General();
	$params = $_GET;
	$params['sin_limite'] = TRUE;
	$params['ordenar_proyecto'] = TRUE;

	$todos = $bus->buscar_empleado($params);

	require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';

	$s = [215.9, 330.2]; # Oficio mm

	$pdf = new TCPDF('L', 'mm', $s);
	$pdf->SetAutoPageBreak(TRUE, 0);

	$datos = [];

	foreach ($todos as $fila) {
		$emp = new Empleado($fila['id']);

		$idproyecto = empty($fila['idproyecto']) ? 0 : $fila['idproyecto'];

		if (isset($datos[$idproyecto])) {
			$datos[$idproyecto]['empleados'][] = $emp->get_datos_impresion();
		} else {
			

			if ($idproyecto === 0) {
				$nomproyecto = 'SIN PROYECTO';
			} else {
				$pro = $emp->get_proyecto();

				if (isset($pro->scalar)) {
					$nomproyecto = 'SIN CONFIGURAR';
				} else {
					$nomproyecto = $pro->nomproyecto;
				}
			}

			$datos[$idproyecto] = [
				'nombre'    => $nomproyecto, 
				'empleados' => [$emp->get_datos_impresion()]
			];
		}
	}

	$hojas = 1;
	$rpag = 32; # Registros por página
	$fecha = date("d/m/Y H:i");

	switch ($_GET['estatus']) {
		case 1:
			$subtitulo = "Activos";
			break;
		case 2:
			$subtitulo = "de Baja";
			break;
		case 3:
			$subtitulo = "Todos";
			break;
		default:
			$subtitulo = "N/A";
			break;
	}

	$cabecera = [
		'titulo'         => "Reporte de Empleados",
		'subtitulo'      => $subtitulo,
		'mes'            => $fecha,
		'tcodigo'        => 'Código',
		'tnombre'        => 'Nombre',
		'ttelefono'      => 'Teléfono',
		'tdpi'           => 'DPI',
		'tingreso'       => 'Ingreso',
		'tbaja'          => 'Baja',
		'tempresadebito' => 'Empresa Débito',
		'tsueldo'        => 'Sueldo',
		'tbonificacion'  => 'Bonificación', 
		'tisr'           => 'ISR',
		'tlineat'		 => str_repeat("_", 250),
		'tlineapiet'     => str_repeat("_", 250),
		'tnopaginat'     => "Página No. "
	];

	$totalPaginas = ceil((count($todos)+count($datos))/$rpag);

	for ($i=0; $i < $totalPaginas ; $i++) { 
		$pdf->AddPage();

		foreach ($cabecera as $campo => $valor) {
			$conf = $bus->get_campo_impresion($campo, 8);

			if (!isset($conf->scalar) && $conf->visible == 1) {
				$pdf = generar_fimpresion($pdf, $valor, $conf);
			}
		}

		$conf = $bus->get_campo_impresion("vnopagina", 8);
		if (!isset($conf->scalar) && $conf->visible == 1) {
			$pdf = generar_fimpresion($pdf, ($i+1), $conf);
		}
	}

	$pagina = 1;

	$pdf->setPage($pagina);

	$espacio   = 0;
	$registros = 0;

	foreach ($datos as $key => $proyecto) {
		if ($registros == $rpag) {
			$espacio   = 0;
			$registros = 0;
			$pagina++;
			$pdf->setPage($pagina);
		}

		$confe      = $bus->get_campo_impresion('idproyecto', 8);
		$confe->psy = ($confe->psy+$espacio);
		$espacio    += $confe->espacio;
		$pdf        = generar_fimpresion($pdf, "{$key} {$proyecto['nombre']}", $confe);

		$registros++;

		foreach ($proyecto['empleados'] as $empleado) {
			unset($empleado['idproyecto']);

			if ($registros == $rpag) {
				$espacio   = 0;
				$registros = 0;
				$pagina++;
				$pdf->setPage($pagina);
			}

			foreach ($empleado as $campo => $valor) {
				$conf = $bus->get_campo_impresion($campo, 8);

				if (!isset($conf->scalar) && $conf->visible == 1) {
					$conf->psy = ($conf->psy+$espacio);

					$numericos = [
						'bonificacionley',
						'sueldo',
						'descuentoisr'
					];

					if (in_array($campo, $numericos)) {
						$valor = number_format($valor, 2);
					} else {
						$valor = $valor;
					}

					$pdf = generar_fimpresion($pdf, $valor, $conf);
				}
			}

			$espacio += $confe->espacio;
			$registros++;
		}
	}

	$pdf->Output("reporte_empleados_" . time() . ".pdf", 'I');
	die();
});

$app->get('/printbit/:empleado/:id', function($empleado,$id){
	$gen = new General();
    $emp = new Empleado($empleado);
    $datos = $emp->get_datos_movimiento(['id' => $id]);

    require BASEPATH . '/libs/tcpdf/tcpdf.php';
    require_once(PLNPATH . '/libraries/fpdi/src/autoload.php');

    $pdf = new Fpdi\TcpdfFpdi();
	$pdf->AddPage();

	$pdf->setSourceFile(PLNPATH . '/files/movimiento.pdf');
	// import page 1
	$tplIdx = $pdf->importPage(1);
	$pdf->useImportedPage($tplIdx);


	foreach ($datos as $campo => $valor) {
		$conf = $gen->get_campo_impresion($campo, 10);

		if (!isset($conf->scalar) && $conf->visible == 1) {
			$pdf = generar_fimpresion($pdf, $valor, $conf);
		}
	}

    $pdf->Output("bitacora_" . time() . ".pdf", 'I');
	die();
});

$app->get('/altasbajas', function(){
	$bus = new General();
	$params = $_GET;
	$params['sin_limite'] = TRUE;
	$params['ordenar_proyecto'] = TRUE;

	$todos = $bus->buscar_empleado($params);

	if (count($todos) > 0) {
		require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';
		$tipoImpresion = 11;

		$s = [215.9, 279.4]; # Carta mm

		$pdf = new TCPDF('P', 'mm', $s);
		$pdf->SetAutoPageBreak(TRUE, 0);

		$datos = [];

		foreach ($todos as $fila) {
			$emp = new Empleado($fila['id']);

			$idempresadebito = empty($fila['idempresadebito']) ? 0 : $fila['idempresadebito'];

			if (isset($datos[$idempresadebito])) {
				$datos[$idempresadebito]['empleados'][] = $emp->get_datos_impresion();
			} else {

				if ($idempresadebito === 0) {
					$nomempresa = 'SIN EMPRESA';
				} else {
					$empresa = $emp->get_empresa_debito();

					if (isset($empresa->scalar)) {
						$nomempresa = 'SIN CONFIGURAR';
					} else {
						$nomempresa = $empresa->nomempresa;
					}
				}

				$datos[$idempresadebito] = [
					'nombre'    => $nomempresa, 
					'empleados' => [$emp->get_datos_impresion()]
				];
			}
		}

		$hojas    = 1;
		$rpag     = 45; # Registros por página
		$fecha    = date("d/m/Y H:i");
		$colFecha = "Fecha";

		switch ($_GET['estatus']) {
			case 1:
				$subtitulo = "Altas";
				$colFecha  .= " de Ingreso";
				break;
			case 2:
				$subtitulo = "Bajas";
				$colFecha  .= " de Baja";
				break;
			case 3:
				$subtitulo = "Todos";
				break;
			default:
				$subtitulo = "N/A";
				break;
		}

		$cabecera = [
			'titulo'         => "Reporte de Empleados",
			'subtitulo'      => $subtitulo,
			'mes'            => "Del ".formatoFecha($_GET['fdel'],1)." al ".formatoFecha($_GET['fal'], 1),
			'fimpresion'     => $fecha, # Fecha de impresión
			'tcodigo'        => 'Código',
			'tnombre'        => 'Nombre',
			'taltabaja'      => $colFecha,
			'tempresadebito' => 'Empresa Débito',
			'tsueldo'        => 'Sueldo',
			'tbonificacion'  => 'Bonificación Ley', 
			'tsueldo_total'  => 'Total Q',
			'tformapago'     => 'Pago',
			'tlineat'		 => str_repeat("_", 160),
			'tlineapiet'     => str_repeat("_", 160),
			'tnopaginat'     => "Página No. "
		];

		$totalPaginas = ceil((count($todos)+count($datos))/$rpag);

		for ($i=0; $i < $totalPaginas ; $i++) { 
			$pdf->AddPage();

			foreach ($cabecera as $campo => $valor) {
				$conf = $bus->get_campo_impresion($campo, $tipoImpresion);

				if (!isset($conf->scalar) && $conf->visible == 1) {
					$pdf = generar_fimpresion($pdf, $valor, $conf);
				}
			}

			$conf = $bus->get_campo_impresion("vnopagina", $tipoImpresion);
			if (!isset($conf->scalar) && $conf->visible == 1) {
				$pdf = generar_fimpresion($pdf, ($i+1), $conf);
			}
		}

		$pagina = 1;

		$pdf->setPage($pagina);

		$espacio   = 0;
		$registros = 0;

		foreach ($datos as $key => $empresa) {
			if ($registros == $rpag) {
				$espacio   = 0;
				$registros = 0;
				$pagina++;
				$pdf->setPage($pagina);
			}

			$confe      = $bus->get_campo_impresion('idempresadebito', $tipoImpresion);
			$confe->psy = ($confe->psy+$espacio);
			$espacio    += $confe->espacio;
			$pdf        = generar_fimpresion($pdf, "{$key} {$empresa['nombre']}", $confe);

			$registros++;

			foreach ($empresa['empleados'] as $empleado) {
				unset($empleado['idempresadebito']);

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pagina++;
					$pdf->setPage($pagina);
				}

				if ($_GET['estatus'] == 1) {
					unset($empleado['baja']);
				} elseif ($_GET['estatus'] == 2) {
					unset($empleado['ingreso']);
				}
				

				foreach ($empleado as $campo => $valor) {
					$conf = $bus->get_campo_impresion($campo, $tipoImpresion);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$conf->psy = ($conf->psy+$espacio);

						$numericos = [
							'bonificacionley',
							'sueldo_total',
							'sueldo'
						];

						if (in_array($campo, $numericos)) {
							$valor = number_format($valor, 2);
						} else {
							$valor = $valor;
						}

						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}

				$espacio += $confe->espacio;
				$registros++;
			}
		}

		$pdf->Output("reporte_empleados_" . time() . ".pdf", 'I');
		die();
	} else {
		die("No encontré empleados que coincidan.");
	}
});

$app->get('/ficha/:empleado', function($empleado){
	$gen = new General();
    $emp = new Empleado($empleado);

    require BASEPATH . '/libs/tcpdf/tcpdf.php';
    require_once(PLNPATH . '/libraries/fpdi/src/autoload.php');

    $pdf = new Fpdi\TcpdfFpdi();
	$pdf->AddPage();

	$pdf->setSourceFile(PLNPATH . '/files/ficha.pdf');
	// import page 1
	$tplIdx = $pdf->importPage(1);
	$pdf->useImportedPage($tplIdx);


	foreach ($emp->get_datos_impresion() as $campo => $valor) {
		$conf = $gen->get_campo_impresion($campo, 12);

		if (!isset($conf->scalar) && $conf->visible == 1) {
			$pdf = generar_fimpresion($pdf, $valor, $conf);
		}
	}

    $pdf->Output("bitacora_" . time() . ".pdf", 'I');
	die();
});

$app->run();