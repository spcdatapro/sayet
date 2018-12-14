<?php 

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');

set_time_limit(0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

		if ($_POST["accion"] == 1) {
			$datos = [
				"estatus" => 1,
				"sin_limite" => true
			];

			if (elemento($_POST, "idplnempleado")) {
				$datos["empleado"] = $_POST["idplnempleado"];
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
		}

		$res["exito"] = 1;
		$res["mensaje"] = "Datos generados con éxito.";
		$res["empleados"] = $bus->getDatosVacas($_POST);
	} else {
		$res["mensaje"] = "Por favor ingrese año de cálculo.";
	}
	
	enviar_json($res);
});

$app->post('/actualizar', function(){
	$bus = new General();
	$vcn = new Vacaciones();
	$vcn->cargar_empleado($_POST["idplnempleado"]);
	$vcn->guardar_extra([
		"id" => $_POST["id"],
		"datos" => [
			"vacasdescuento" => $_POST["vacasdescuento"],
			"vacasdias" => $_POST["vacasdias"],
			"vacasgozar" => elemento($_POST, "vacasgozar"),
			"vacasliquido" => ($_POST["vacastotal"]-$_POST["vacasdescuento"]),
			"vacastotal" => $_POST["vacastotal"],
			"vacasultimas" => elemento($_POST, "vacasultimas"),
			"vacasusados" => $_POST["vacasusados"]
		]
	]);
	
	enviar_json($bus->getDatosVacas(["id" => $_POST["id"], "uno" => true]));
});

$app->get('/imprimir', function(){
	if (elemento($_GET, "anio")) {
		if (isset($_GET["carta"])) {
			require BASEPATH . '/libs/tcpdf/tcpdf.php';
			$s = [215.9, 279.4]; # Carta mm

			$pdf = new TCPDF('P', 'mm', $s);
			$pdf->SetAutoPageBreak(TRUE, 0);
			$pdf->SetFont('times', '', 12);
			
			$tipoImpresion = 20;

			$bus = new General();
		
			$datos = [
				"estatus" => 1,
				"sin_limite" => true
			];

			if (elemento($_GET, "idplnempleado")) {
				$datos["empleado"] = $_GET["idplnempleado"];
			}

			if (elemento($_GET, "empresa")) {
				$datos["actual"] = $_GET["empresa"];
			}

			$empleados = $bus->buscar_empleado($datos);
			
			foreach ($empleados as $key => $value) {
				$pdf->AddPage();

				$vcn = new Vacaciones();
				$vcn->cargar_empleado($value["id"]);

				$impresion = $vcn->getImpresionVacas($_GET);
				
				if ($impresion) {
					foreach ($impresion as $campo => $valor) {
						$conf = $bus->get_campo_impresion($campo, $tipoImpresion);

						if (!isset($conf->scalar) && $conf->visible == 1) {
							$pdf = generar_fimpresion($pdf, $valor, $conf);
						}
					}
				} else {
					$pdf->MultiCell(0, 5, "No encontré datos generados para este año.", 0, 'L', 0, 0, '', '', true);
				}
			}
			
			$pdf->Output("carta_vacaciones_" . time() . ".pdf", 'I');
			die();
		} else {
			$g = new General();

			require BASEPATH . '/libs/tcpdf/tcpdf.php';
			$s = [215.9, 330.2]; # Oficio mm

			$pdf = new TCPDF('L', 'mm', $s);
			$pdf->SetAutoPageBreak(TRUE, 0);
			$pdf->AddPage();

			$todos = $g->getDatosVacas($_GET);

			$tipoImpresion = 19;

			if (count($todos) > 0) {
				$registros = 0;
				$datos = [];

				foreach ($todos as $fila) {
					if (isset($datos[$fila['idempresaactual']])) {
						$datos[$fila['idempresaactual']]['empleados'][] = $fila;
					} else {
						$datos[$fila['idempresaactual']] = [
							'nombre'    => $fila['empresaactual'], 
							'conf'      => $g->get_campo_impresion('idempresaactual', $tipoImpresion), 
							'empleados' => [$fila]
						];
					}
				}

				$rpag = 30; # Registros por página
				$anio = $_GET["anio"];

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
					'tvacasdias'  => "Días",
					'tdescvacasdias' => "Días Desc",
					'tvacastotal' => "Líquido a Recibir",
					'tlineat'     => str_repeat("_", 250),
					'tlineapiet'  => str_repeat("_", 250),
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

								$sintotal = ['idplnempleado'];

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