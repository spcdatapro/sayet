<?php 

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');


require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require PLNPATH . '/Principal.php';
require PLNPATH . '/models/General.php';
require PLNPATH . '/models/Nomina.php';
require PLNPATH . '/models/Empleado.php';
require PLNPATH . '/models/Vacaciones.php';
require PLNPATH . '/models/Prestamo.php';

$app = new \Slim\Slim();

$app->post('/generar', function(){
	$res  = ['exito' => 0];

	if (elemento($_POST, 'anio')) {
		$bus = new General();
		
		$datos = [
			"estatus" => 1,
			"sin_limite" => true
		];

		if (elemento($_POST, "empleado")) {
			$datos["empleado"] = $_POST["empleado"];
		}

		if (elemento($_POST, "empresa")) {
			$datos["actual"] = $_POST["empresa"];
		}

		$empleados = $bus->buscar_empleado($datos);
		
		foreach ($empleados as $key => $value) {
			$vcn = new Vacaciones();
			$vcn->cargar_empleado($value["id"]);
			$vcn->setDiasVacaciones($_POST);
		}

		$res["exito"] = 1;
		$res["mensaje"] = "Datos generados con éxito.";
	} else {
		$res["mensaje"] = "Por favor ingrese año de cálculo.";
	}
	
	enviar_json($res);
});

$app->get('/imprimir', function(){
	if (elemento($_GET, "anio")) {
		if (isset($_GET["carta"])) {
			require BASEPATH . '/libs/tcpdf/tcpdf.php';
			$s = [215.9, 279.4]; # Carta mm

			$pdf = new TCPDF('P', 'mm', $s);
			$pdf->SetAutoPageBreak(TRUE, 0);
			$pdf->SetFont('times', '', 12);

			$bus = new General();
		
			$datos = [
				"estatus" => 1,
				"sin_limite" => true
			];

			if (elemento($_GET, "empleado")) {
				$datos["empleado"] = $_GET["empleado"];
			}

			if (elemento($_GET, "empresa")) {
				$datos["actual"] = $_GET["empresa"];
			}

			$empleados = $bus->buscar_empleado($datos);
			
			foreach ($empleados as $key => $value) {
				$pdf->AddPage();

				$vcn = new Vacaciones();
				$vcn->cargar_empleado($value["id"]);
				$emp = $vcn->get_empresa_debito();
				$vac = $vcn->getDatosVacas($_GET["anio"]);
				$vac['empresa'] = $emp->nomempresa;
				$vac['empleado'] = "{$vcn->emp->nombre} {$vcn->emp->apellidos}";
				
				$pdf->MultiCell(0, 5, getCartaVacaciones($vac), 0, 'L', 0, 0, '', '', true);
			}
			
			$pdf->Output("carta_vacaciones_" . time() . ".pdf", 'I');
			die();
		} else {
			$b = new Nomina();
			$g = new General();

			$anio = $_GET["anio"];

			require BASEPATH . '/libs/tcpdf/tcpdf.php';
			$_GET['fdel'] = "{$anio}-12-16";
			$_GET['fal'] = "{$anio}-12-31";

			$s = [215.9, 330.2]; # Oficio mm

			$pdf = new TCPDF('L', 'mm', $s);
			$pdf->SetAutoPageBreak(TRUE, 0);
			$pdf->AddPage();

			$todos = $b->get_datos_recibo($_GET);

			$tipoImpresion = 19;

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

				$rpag = 45; # Registros por página

				$cabecera = [
					'titulon'     => 'Módulo de Planillas',
					'subtitulo'   => "Lista de Vacaciones",
					'mes'         => "Período del 01/01/{$anio} al 31/12/{$anio}",
					'tcodigot'    => "Código",
					'tnombre'     => "Nombre del Empleado",
					'tsueldo'     => "Sueldo Mensual",
					'tingreso'    => "Fecha Ingreso",
					'tvacaciones' => "Vacaciones",
					'tdescvacas'  => "Descuento",
					'tdescvacasdias' => "Días Desc",
					'tvacastotal' => "Líquido a Recibir",
					'tlineat'     => str_repeat("_", 160),
					'tlineapiet'  => str_repeat("_", 160),
					'tnopaginat'  => "Página No. "
					
				];

				$espacio = 0;
				$totales = [];

				foreach ($datos as $key => $empresa) {
					$registros++;

					if ($registros == $rpag) {
						$espacio   = 0;
						$registros = 0;
						$pdf->AddPage();
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

								$sintotal = ['vcodigo', 'vaguinaldodias'];

								if (is_numeric($valor) && !in_array($campo, $sintotal)) {
									$etotales = totalesIndice($etotales, $campo, $valor);
									$totales  = totalesPagina($totales, $pdf, $campo, $valor);
									$valor    = number_format($valor, 2);
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

					$pdf = imprimirTotalesEmpresa($pdf, $g, $tipoImpresion, $etotales, $espacio);

					$espacio += $confe->espacio;	
				}
	
				$pdf = imprimirTotalesPagina($pdf, $g, $tipoImpresion, $totales);
				$pdf = imprimirEncabezado($pdf, $g, $tipoImpresion, $cabecera);

				$pdf->Output("listaVacaciones_" . time() . ".pdf", 'I');
				die();
			} else {
				echo "Nada que mostrar";
			}
		}
	} else {
		die("Es necesario que ingrese año.");
	}
});

$app->run();