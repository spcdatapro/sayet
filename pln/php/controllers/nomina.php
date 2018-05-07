<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(dirname(dirname(__DIR__))) . '/php/vendor/autoload.php';
require dirname(dirname(dirname(__DIR__))) . '/php/ayuda.php';
require dirname(__DIR__) . '/Principal.php';
require dirname(__DIR__) . '/models/Prestamo.php';
require dirname(__DIR__) . '/models/Empleado.php';
require dirname(__DIR__) . '/models/Nomina.php';
require dirname(__DIR__) . '/models/General.php';

$app = new \Slim\Slim();

$app->get('/buscar', function(){
	$b = new Nomina();

	$datos  = ['exito' => 0];
	$fecha  = $_GET['fecha'];
	$dia    = date('d', strtotime($fecha));
	$ultimo = date('t', strtotime($fecha));

	if (in_array($dia, array(15, $ultimo))) {
		$datos['resultados'] = $b->buscar($_GET);
		$datos['exito']      = 1;
	} else {
		$datos['mensaje'] = "Fecha incorrecta, por favor verifique.";
	}
	
	enviar_json($datos);
});

$app->get('/imprimir_recibo', function(){
	$b = new Nomina();
	$g = new General();

	if (elemento($_GET, 'fdel') && elemento($_GET, 'fal')) {
		require dirname(dirname(dirname(__DIR__))) . '/libs/tcpdf/tcpdf.php';
		$s = [215.9, 279.4]; # Carta mm

		$pdf = new TCPDF('P', 'mm', $s);
		$pdf->SetAutoPageBreak(TRUE, 0);

		$cantidad = 2; # Cantidad de recibos por página

		$datos = $b->get_datos_recibo($_GET);

		if (count($datos) > 0) {
			foreach ($datos as $key => $fila) {
				if ($key%$cantidad == 0) {
					$pdf->AddPage();
					$cont = 0;
				} else {
					$cont++;
				}

				foreach ($fila as $row) {
					$conf = $g->get_campo_impresion($row['campo'], 1);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$conf->psy = ($key%$cantidad == 0)?$conf->psy:($conf->psy+(($s[1]/$cantidad)*$cont));

						if (is_numeric($row["valor"]) && !in_array($row['campo'], ['vcodigo', 'vdiastrabajados'])) {
							$valor = number_format($row["valor"], 2);
						} else {
							$valor = $row["valor"];
						}

						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}
			}

			$pdf->Output("recibo.pdf", 'I');
			die();
		} else {
			echo "Nada que mostrar";
		}
	} else {
		echo "Faltan datos obligatorios";
	}
});

$app->get('/imprimir', function(){
	$b = new Nomina();
	$g = new General();

	if (elemento($_GET, 'fdel') && elemento($_GET, 'fal')) {
		require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';
		
		$s = [215.9, 330.2]; # Oficio mm

		$pdf = new TCPDF('L', 'mm', $s);
		$pdf->SetAutoPageBreak(TRUE, 0);

		$todos = $b->get_datos_recibo($_GET);

		if (count($todos) > 0) {
			$registros = 0;
			$datos = [];

			foreach ($todos as $fila) {
				if (isset($datos[$fila[0]['valor']])) {
					$datos[$fila[0]['valor']]['empleados'][] = $fila;
				} else {
					$datos[$fila[0]['valor']] = [
						'nombre'    => $fila[1]['valor'], 
						'conf'      => $g->get_campo_impresion('vidempresa', 2), 
						'empleados' => [$fila]
					];
				}
			}

			$hojas = 1;
			$rpag = 32; # Registros por página

			$mes  = date('m', strtotime($_GET['fal']));
			$anio = date('Y', strtotime($_GET['fal']));
			$dia  = date('d', strtotime($_GET['fal']));

			$cabecera = $b->get_cabecera([
				'dia'  => $dia, 
				'mes'  => $mes, 
				'anio' => $anio
			]);
			
			for ($i=0; $i < ((count($todos)+(count($datos)*2))/$rpag) ; $i++) { 
				$pdf->AddPage();

				foreach ($cabecera as $campo => $valor) {
					$conf = $g->get_campo_impresion($campo, 2);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}
			}

			$pagina = 1;

			$pdf->setPage($pagina);

			$espacio = 0;
			$totales = [];

			foreach ($datos as $key => $empresa) {
				$registros++;

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pagina++;
					$pdf->setPage($pagina);
				}
				
				$confe      = $g->get_campo_impresion('idempresa', 2);
				$confe->psy = ($confe->psy+$espacio);
				$espacio    += $confe->espacio;
				$pdf        = generar_fimpresion($pdf, "{$key} {$empresa['nombre']}", $confe);

				$etotales = [];

				foreach ($empresa['empleados'] as $empleado) {
					$registros++;

					foreach ($empleado as $row) {
						$conf = $g->get_campo_impresion($row['campo'], 2);

						if (!isset($conf->scalar) && $conf->visible == 1) {
							$conf->psy = ($conf->psy+$espacio);

							if (is_numeric($row["valor"]) && !in_array($row['campo'], ['vcodigo', 'vdiastrabajados'])) {
								$valor = number_format($row["valor"], 2);
							} else {
								$valor = $row["valor"];
							}

							$pdf      = generar_fimpresion($pdf, $valor, $conf);
							$sintotal = ['vdiastrabajados', 'vcodigo'];

							if (is_numeric($row['valor']) && !in_array($row['campo'], $sintotal)) {
								if (isset($etotales[$row['campo']])) {
									$etotales[$row['campo']] += $row['valor'];
								} else {
									$etotales[$row['campo']] = $row['valor'];
								}
								
								if (isset($totales[$pdf->getPage()][$row['campo']])) {
									$totales[$pdf->getPage()][$row['campo']] += $row['valor'];
								} else {
									if (isset($totales[$pdf->getPage()-1][$row['campo']])) {
										$totales[$pdf->getPage()][$row['campo']] = $row['valor']+$totales[$pdf->getPage()-1][$row['campo']];
									} else {
										$totales[$pdf->getPage()][$row['campo']] = $row['valor'];
									}
								}
							}
						}
					}

					# $pdf = generar_fimpresion($pdf, $valor, $conf);

					$espacio += $confe->espacio;

					if ($registros == $rpag) {
						$espacio   = 0;
						$registros = 0;
						$pagina++;
						$pdf->setPage($pagina);
					}
				}

				$registros++;

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pagina++;
					$pdf->setPage($pagina);
				}

				$pdf->SetLineStyle(array(
					'width' => 0.2, 
					'cap' => 'butt', 
					'join' => 'miter', 
					'dash' => 0, 
					'color' => array(0, 0, 0)
				));

				foreach ($etotales as $campo => $total) {
					$conf = $g->get_campo_impresion($campo, 2);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$conf->psy = ($conf->psy+$espacio);
						$pdf       = generar_fimpresion($pdf, number_format($total, 2), $conf);

						$pdf->Line($conf->psx, $conf->psy, ($conf->psx+$conf->ancho), $conf->psy);

						$y = ($conf->psy+$conf->espacio);

						$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
						$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
					}
				}

				$espacio += $confe->espacio;	
			}

			$espacio += 20;

			foreach ($b->get_firmas() as $campo => $valor) {
				$conf = $g->get_campo_impresion($campo, 2);

				if (!isset($conf->scalar) && $conf->visible == 1) {
					$pdf = generar_fimpresion($pdf, $valor, $conf);
				}
			}

			$pie  = $g->get_campo_impresion("vtotalespie", 2);

			foreach ($totales as $key => $subtotales) {
				$pdf->setPage($key);

				foreach ($subtotales as $campo => $total) {
					$conf = $g->get_campo_impresion($campo, 2);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$conf->psy = $pie->psy;
						$pdf       = generar_fimpresion($pdf, number_format($total, 2), $conf);

						$y = ($conf->psy+$conf->espacio);

						$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
						$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
					}
				}

				$conf = $g->get_campo_impresion("vnopagina", 2);
				if (!isset($conf->scalar) && $conf->visible == 1) {
					$pdf = generar_fimpresion($pdf, $key, $conf);
				}
			}

			$pdf->Output("nomina" . time() . ".pdf", 'I');
			die();
		} else {
			echo "Nada que mostrar";
		}
	} else {
		echo "Faltan datos obligatorios";
	}
});

$app->post('/actualizar', function(){
	$n = new Nomina();

	$datos = ['exito' => 0];

	if ($n->actualizar($_POST)) {
		$datos['exito']   = 1;
		$datos['mensaje'] = "Se guardó con éxito.";
	} else {
		$datos['mensaje'] = $n->get_mensaje();
	}

	enviar_json($datos);
});

$app->post('/generar', function(){
	$n = new Nomina();

	$datos  = ['exito' => 0];
	$fecha  = $_POST['fecha'];
	$dia    = date('d', strtotime($fecha));
	$ultimo = date('t', strtotime($fecha));

	if (in_array($dia, array(15, $ultimo))) {
		if ($n->generar($_POST)) {
			$datos['exito']   = 1;
			$datos['mensaje'] = "Nómina generada con éxito.";
		} else {
			$datos['mensaje'] = $n->get_mensaje();
		}
	} else {
		$datos['mensaje'] = "Fecha incorrecta, por favor verifique.";
	}
	
	enviar_json($datos);
});

$app->get('/imprimir_igss', function(){
	$b = new Nomina();
	$g = new General();

	if (elemento($_GET, 'fal')) {
		require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';

		$_GET['fdel'] = formatoFecha($_GET['fal'], 4).'-'.formatoFecha($_GET['fal'], 3).'-16';
		
		$s = [215.9, 279.4]; # Carta mm

		$pdf = new TCPDF('P', 'mm', $s);
		$pdf->SetAutoPageBreak(TRUE, 0);

		$todos = $b->get_datos_recibo($_GET);

		if (count($todos) > 0) {

			$registros = 0;
			$hojas = 1;
			$rpag = 40; # Registros por página

			$mes  = date('m', strtotime($_GET['fal']));
			$anio = date('Y', strtotime($_GET['fal']));
			$dia  = date('d', strtotime($_GET['fal']));

			$emp = $g->get_empresa(['id' => $_GET['empresa']])[0];

			$cabecera = $b->get_cabecera_igss([
				'dia'               => $dia, 
				'mes'               => $mes, 
				'anio'              => $anio,
				'razon_social'      => $emp['nomempresa'],
				'direccion_patrono' => $emp['direccion'],
				'numero_patronal'   => $emp['numero_patronal']
			]);

			$totales = [];
			
			# Se imprime un encabezado más para agregar la tabla final
			$totalPaginas = ceil(count($todos)/$rpag)+1;
			for ($i=1; $i <= $totalPaginas; $i++) { 
				$pdf->AddPage();

				foreach ($cabecera as $campo => $valor) {
					$conf = $g->get_campo_impresion($campo, 3);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}
			}

			$pagina = 1;

			$pdf->setPage($pagina);

			$espacio = 0;
			$totales = [];

			foreach ($todos as $empleado) {
				$registros++;
				$espaciotmp = 0;

				foreach ($empleado as $row) {
					$conf = $g->get_campo_impresion($row['campo'], 3);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						if ($espaciotmp === 0) {
							$espaciotmp = $conf->espacio;
						}
						
						$conf->psy = ($conf->psy+$espacio);

						if (is_numeric($row["valor"]) && !in_array($row['campo'], ['vcodigo', 'vafiliacionigss'])) {
							$valor = number_format($row["valor"], 2);
						} else {
							$valor = $row["valor"];
						}

						$pdf = generar_fimpresion($pdf, $valor, $conf);

						$sintotal = ['vdiastrabajados', 'vcodigo', 'vafiliacionigss'];

						if (is_numeric($row['valor']) && !in_array($row['campo'], $sintotal)) {
							if (isset($totales[$row['campo']])) {
								$totales[$row['campo']] += $row['valor'];
							} else {
								$totales[$row['campo']] = $row['valor'];
							}
						}
					}
				}

				# $pdf = generar_fimpresion($pdf, $valor, $conf);

				$espacio += $espaciotmp;

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pagina++;
					$pdf->setPage($pagina);
				}

				$pdf->SetLineStyle(array(
					'width' => 0.2, 
					'cap' => 'butt', 
					'join' => 'miter', 
					'dash' => 0, 
					'color' => array(0, 0, 0)
				));
			}

			$pdf->setPage($totalPaginas);
			$conf = $g->get_campo_impresion('t_cantidad_empleado', 3);
			if (!isset($conf->scalar) && $conf->visible == 1) {
				$pdf = generar_fimpresion($pdf, "Empleados: " . count($todos), $conf);
			}

			foreach ($totales as $campo => $total) {
				$conf = $g->get_campo_impresion($campo, 3);

				if (!isset($conf->scalar) && $conf->visible == 1) {
					$pdf = generar_fimpresion($pdf, number_format($total, 2), $conf);

					$y = ($conf->psy+$conf->espacio);

					$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
					$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
				}
			}

			$dresumen = [
				'cp_igss'    => ($totales['vsueldototal'] * 0.1067),
				'cp_intecap' => ($totales['vsueldototal'] * 0.01),
				'cp_irtra'   => ($totales['vsueldototal'] * 0.01),
				'ct_igss'    => $totales['vigss'],
				'ct_total'   => $totales['vigss']
			];

			foreach ($b->get_resumen_igss($dresumen) as $campo => $valor) {
				$conf = $g->get_campo_impresion($campo, 3);

				if (!isset($conf->scalar) && $conf->visible == 1) {
					$pdf = generar_fimpresion($pdf, $valor, $conf);
				}
			}

			#~$conf = $g->get_campo_impresion('')

			$pdf->Output("planilla_igss_" . time() . ".pdf", 'I');
			die();
		} else {
			echo "Nada que mostrar";
		}
	} else {
		echo "Faltan datos obligatorios";
	}
});

$app->get('/imprimir_isr', function(){
	$b = new Nomina();
	$g = new General();

	if (elemento($_GET, 'fal')) {
		require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';

		$_GET['fdel'] = formatoFecha($_GET['fal'], 4).'-'.formatoFecha($_GET['fal'], 3).'-16';

		$s = [215.9, 279.4]; # Carta mm

		$pdf = new TCPDF('P', 'mm', $s);
		$pdf->SetAutoPageBreak(TRUE, 0);

		$todos = $b->get_datos_recibo($_GET);

		if (count($todos) > 0) {
			$registros = 0;
			$datos = [];

			foreach ($todos as $fila) {
				if (isset($datos[$fila[0]['valor']])) {
					$datos[$fila[0]['valor']]['empleados'][] = $fila;
				} else {
					$datos[$fila[0]['valor']] = [
						'nombre'    => $fila[1]['valor'], 
						'conf'      => $g->get_campo_impresion('vidempresa', 4), 
						'empleados' => [$fila]
					];
				}
			}

			$hojas = 1;
			$rpag = 45; # Registros por página

			$mes  = date('m', strtotime($_GET['fal']));
			$anio = date('Y', strtotime($_GET['fal']));
			$dia  = date('d', strtotime($_GET['fal']));

			$cabecera = $b->get_cabecera_isr([
				'dia'  => $dia, 
				'mes'  => $mes, 
				'anio' => $anio
			]);
			
			for ($i=0; $i < ((count($todos)+(count($datos)*2))/$rpag) ; $i++) { 
				$pdf->AddPage();

				foreach ($cabecera as $campo => $valor) {
					$conf = $g->get_campo_impresion($campo, 4);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}
			}

			$pagina = 1;

			$pdf->setPage($pagina);

			$espacio = 0;
			$totales = [];

			foreach ($datos as $key => $empresa) {
				$registros++;

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pagina++;
					$pdf->setPage($pagina);
				}
				
				$confe      = $g->get_campo_impresion('idempresa', 4);
				$confe->psy = ($confe->psy+$espacio);
				$espacio    += $confe->espacio;
				$pdf        = generar_fimpresion($pdf, "{$key} {$empresa['nombre']}", $confe);

				$etotales = [];

				foreach ($empresa['empleados'] as $empleado) {
					$registros++;

					foreach ($empleado as $row) {
						$conf = $g->get_campo_impresion($row['campo'], 4);

						if (!isset($conf->scalar) && $conf->visible == 1) {
							$conf->psy = ($conf->psy+$espacio);

							if (is_numeric($row["valor"]) && !in_array($row['campo'], ['vcodigo', 'vdiastrabajados'])) {
								$valor = number_format($row["valor"], 2);
							} else {
								$valor = $row["valor"];
							}

							$pdf      = generar_fimpresion($pdf, $valor, $conf);
							$sintotal = ['vdiastrabajados', 'vcodigo'];

							if (is_numeric($row['valor']) && !in_array($row['campo'], $sintotal)) {
								if (isset($etotales[$row['campo']])) {
									$etotales[$row['campo']] += $row['valor'];
								} else {
									$etotales[$row['campo']] = $row['valor'];
								}
								
								if (isset($totales[$pdf->getPage()][$row['campo']])) {
									$totales[$pdf->getPage()][$row['campo']] += $row['valor'];
								} else {
									if (isset($totales[$pdf->getPage()-1][$row['campo']])) {
										$totales[$pdf->getPage()][$row['campo']] = $row['valor']+$totales[$pdf->getPage()-1][$row['campo']];
									} else {
										$totales[$pdf->getPage()][$row['campo']] = $row['valor'];
									}
								}
							}
						}
					}

					# $pdf = generar_fimpresion($pdf, $valor, $conf);

					$espacio += $confe->espacio;

					if ($registros == $rpag) {
						$espacio   = 0;
						$registros = 0;
						$pagina++;
						$pdf->setPage($pagina);
					}
				}

				$registros++;

				if ($registros == $rpag) {
					$espacio   = 0;
					$registros = 0;
					$pagina++;
					$pdf->setPage($pagina);
				}

				$pdf->SetLineStyle(array(
					'width' => 0.2, 
					'cap' => 'butt', 
					'join' => 'miter', 
					'dash' => 0, 
					'color' => array(0, 0, 0)
				));

				foreach ($etotales as $campo => $total) {
					$conf = $g->get_campo_impresion($campo, 4);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$conf->psy = ($conf->psy+$espacio);
						$pdf       = generar_fimpresion($pdf, number_format($total, 2), $conf);

						$pdf->Line($conf->psx, $conf->psy, ($conf->psx+$conf->ancho), $conf->psy);

						$y = ($conf->psy+$conf->espacio);

						$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
						$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
					}
				}

				$espacio += $confe->espacio;	
			}

			$pie  = $g->get_campo_impresion("vtotalespie", 4);

			foreach ($totales as $key => $subtotales) {
				$pdf->setPage($key);

				foreach ($subtotales as $campo => $total) {
					$conf = $g->get_campo_impresion($campo, 4);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$conf->psy = $pie->psy;
						$pdf       = generar_fimpresion($pdf, number_format($total, 2), $conf);

						$y = ($conf->psy+$conf->espacio);

						$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
						$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
					}
				}

				$conf = $g->get_campo_impresion("vnopagina", 4);
				if (!isset($conf->scalar) && $conf->visible == 1) {
					$pdf = generar_fimpresion($pdf, $key, $conf);
				}
			}

			$pdf->Output("nomina" . time() . ".pdf", 'I');
			die();
		} else {
			echo "Nada que mostrar";
		}
	} else {
		echo "Faltan datos obligatorios";
	}
});

# imprimir saldos prestamos
$app->get('/imprimir_sp', function(){
	$g = new General();

	if (elemento($_GET, 'fal')) {
		$todos = $g->buscar_prestamo([
			'fal'        => $_GET['fal'],
			'orden'      => 'empleado',
			'finalizado' => 0
		]);

		if (count($todos) > 0) {
			require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';

			$s = [215.9, 330.2]; # Oficio mm

			$pdf = new TCPDF('L', 'mm', $s);
			$pdf->SetAutoPageBreak(TRUE, 0);

			$datos = [];

			/*foreach ($todos as $fila) {
				if (isset($datos[$fila['idempresaactual']])) {
					$datos[$fila['idempresaactual']]['empleados'][] = $fila;
				} else {
					$emp = $g->get_empresa(['id' => $fila['idempresaactual']]);

					$datos[$fila['idempresaactual']] = [
						'nombre'    => $emp->nomempresa, 
						'conf'      => $g->get_campo_impresion('vidempresa', 2), 
						'empleados' => [$fila]
					];
				}
			}*/

			$cabecera = [
				'sp_titulo'              => 'Módulo de Planillas',
				'sp_subtitulo'           => 'ANTICIPOS A SUELDOS',
				'sp_fecha'               => "Reporte al: " . formatoFecha($_GET['fal'], 1),
				't_codigo'               => 'Código',
				't_nombre'               => 'Nombre:',
				't_vale'                 => 'Vale',
				't_fecha'                => 'Fecha',
				't_valor_prestamo'       => "Valor\nPréstamo",
				't_descuento_mensual'    => "Descuento\nMensual",
				't_saldo_anterior'       => "Saldo\nAnterior",
				't_nuevos_prestamos'     => "Nuevo\nPréstamos",
				't_descuentos_planillas' => "Descuentos\nPlanillas",
				't_otros_abonos'         => "Otros\nAbonos",
				't_total_descuentos'     => "Total\nDescuentos",
				't_saldo_actual'         => "Saldo\nActual",
				't_linea'                => str_repeat("_", 250)
			];

			$rpag = 32; # Registros por página
			$totalPaginas = ceil(count($todos)/$rpag);

			for ($i=0; $i < $totalPaginas ; $i++) { 
				$pdf->AddPage();

				foreach ($cabecera as $campo => $valor) {
					$conf = $g->get_campo_impresion($campo, 5);

					if (!isset($conf->scalar) && $conf->visible == 1) {
						$pdf = generar_fimpresion($pdf, $valor, $conf);
					}
				}
			}

			$pdf->Output("planilla_sp_" . time() . ".pdf", 'I');
			die();
		} else {
			echo "Nada que mostrar.";
		}
	} else {
		echo "Faltan datos obligatorios.";
	}
});

$app->run();