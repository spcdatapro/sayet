<?php 

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');


require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require PLNPATH . '/Principal.php';
require PLNPATH . '/models/General.php';
require PLNPATH . '/models/Empleado.php';
require PLNPATH . '/models/Vacaciones.php';

$app = new \Slim\Slim();

$app->post('/generar', function(){
	$res  = ['exito' => 0];

	if (elemento($_POST, 'anio')) {
		$bus = new General();
		
		$datos = ["estatus" => 1];

		if (elemento($_POST, "empleado")) {
			$datos["empleado"] = $_POST["empleado"];
		}

		$empleados = $bus->buscar_empleado($datos);
		
		foreach ($empleados as $key => $value) {
			$vcn = new Vacaciones();
			$vcn->cargar_empleado($value["id"]);
			$vcn->setDiasVacaciones($_POST);
		}

		$res["mensaje"] = "Datos generados con éxito.";
	} else {
		$res["mensaje"] = "Por favor ingrese año de cálculo.";
	}
	
	enviar_json($res);
});

$app->get('/imprimir', function(){
	$b = new Nomina();
	$g = new General();

	if (elemento($_GET, 'fal')) {
		if (formatoFecha($_GET['fal'], 2) == 15 && formatoFecha($_GET['fal'], 3) == 12) {
			require BASEPATH . '/libs/tcpdf/tcpdf.php';
			$_GET['fdel'] = formatoFecha($_GET['fal'], 4).'-'.formatoFecha($_GET['fal'], 3).'-01';

			$s = [215.9, 279.4]; # Carta mm

			$pdf = new TCPDF('P', 'mm', $s);
			$pdf->SetAutoPageBreak(TRUE, 0);

			$todos = $b->get_datos_recibo($_GET);

			$tipoImpresion = 14;

			if (count($todos) > 0) {
				$registros = 0;
				$datos = [];

				foreach ($todos as $fila) {
					if (isset($datos[$fila['vidempresa']])) {
						$datos[$fila['vidempresa']]['empleados'][] = $fila;
					} else {
						$datos[$fila['vidempresa']] = [
							'nombre'    => $fila['vempresa'], 
							'conf'      => $g->get_campo_impresion('vidempresa', $tipoImpresion), 
							'empleados' => [$fila]
						];
					}
				}

				$hojas = 1;
				$rpag = 45; # Registros por página

				$mes  = date('m', strtotime($_GET['fal']));
				$anio = date('Y', strtotime($_GET['fal']));
				$dia  = date('d', strtotime($_GET['fal']));

				$cabecera = $b->get_cabecera_aguinaldo($_GET);
				
				for ($i=0; $i < ((count($todos)+(count($datos)*2))/$rpag) ; $i++) { 
					$pdf->AddPage();

					foreach ($cabecera as $campo => $valor) {
						$conf = $g->get_campo_impresion($campo, $tipoImpresion);

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
					
					$confe      = $g->get_campo_impresion('idempresa', $tipoImpresion);
					$confe->psy = ($confe->psy+$espacio);
					$espacio    += $confe->espacio;
					$pdf        = generar_fimpresion($pdf, "{$key} {$empresa['nombre']}", $confe);

					$etotales = [];

					foreach ($empresa['empleados'] as $empleado) {
						$registros++;

						foreach ($empleado as $campo => $valor) {
							$conf = $g->get_campo_impresion($campo, $tipoImpresion);

							if (!isset($conf->scalar) && $conf->visible == 1) {
								$conf->psy = ($conf->psy+$espacio);


								if ($campo === 'vaguinaldo') {
									if (isset($etotales[$campo])) {
										$etotales[$campo] += $valor;
									} else {
										$etotales[$campo] = $valor;
									}
									
									if (isset($totales[$pdf->getPage()][$campo])) {
										$totales[$pdf->getPage()][$campo] += $valor;
									} else {
										if (isset($totales[$pdf->getPage()-1][$campo])) {
											$totales[$pdf->getPage()][$campo] = $valor+$totales[$pdf->getPage()-1][$campo];
										} else {
											$totales[$pdf->getPage()][$campo] = $valor;
										}
									}
								}

								if (is_numeric($valor) && !in_array($campo, ['vcodigo', 'vaguinaldodias'])) {
									$valor = number_format($valor, 2);
								} else {
									$valor = $valor;
								}

								$pdf = generar_fimpresion($pdf, $valor, $conf);
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
						$conf = $g->get_campo_impresion($campo, $tipoImpresion);

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

				$pie  = $g->get_campo_impresion("vtotalespie", $tipoImpresion);

				foreach ($totales as $key => $subtotales) {
					$pdf->setPage($key);

					foreach ($subtotales as $campo => $total) {
						$conf = $g->get_campo_impresion($campo, $tipoImpresion);

						if (!isset($conf->scalar) && $conf->visible == 1) {
							$conf->psy = $pie->psy;
							$pdf       = generar_fimpresion($pdf, number_format($total, 2), $conf);

							$y = ($conf->psy+$conf->espacio);

							$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
							$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
						}
					}

					$conf = $g->get_campo_impresion("vnopagina", $tipoImpresion);
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
			die('Fecha incorrecta, por favor verifique. Debe ser el 15/12.');
		}
	} else {
		echo "Faltan datos obligatorios";
	}
});

$app->run();