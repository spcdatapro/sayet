<?php 

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
define('PLNPATH', BASEPATH . '/pln/php');

require BASEPATH . "/php/vendor/autoload.php";
require BASEPATH . "/php/ayuda.php";
require PLNPATH . '/Principal.php';
require PLNPATH . '/models/General.php';
require PLNPATH . '/models/Reporte.php';


$app = new \Slim\Slim();

$app->get('/antiguedad_empleado', function(){
	if (elemento($_GET, 'fal')) {
		$rep   = new Reporte();
		$g     = new General();
		$datos = $rep->get_antiguedad_empleado($_GET);

		require $_SERVER['DOCUMENT_ROOT'] . '/sayet/libs/tcpdf/tcpdf.php';

		$s = [215.9, 279.4]; # Carta mm

		$pdf = new TCPDF('P', 'mm', $s);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->AddPage();
		$registros = 0;
		$tipoImpresion = 16;

		$cabecera = [
			'titulo'     => 'Módulo de Planillas',
			'subtitulo'  => 'ANTIGÜEDAD DE EMPLEADOS',
			'fecha'      => "Al ".formatoFecha($_GET['fal'], 1),
			'fimpresion' => date('d/m/Y H:i'),
			'tcodigo'    => 'Código',
			'tnombre'    => 'Nombre',
			'tingreso'   => 'Ingreso',
			'tdias'      => 'Días',
			'tanios'     => 'Años',
			'tmeses'     => 'Meses',
			'tlinea'    => str_repeat("_", 160)
		];

		$rpag    = 45; # Registros por página
		$espacio = 0;

		$registros++;

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

			foreach ($empresa['empleados'] as $empleado) {
				$registros++;
				foreach ($empleado as $campo => $valor) {
					$conf = $g->get_campo_impresion($campo, $tipoImpresion);
					if (!isset($conf->scalar) && $conf->visible == 1 && $conf->cabecera == 0) {
						$conf->psy = ($conf->psy+$espacio);
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

			$espacio += $confe->espacio;	
		}

		for ($i=1; $i <= $pdf->getNumPages(); $i++) { 
			$pdf->setPage($i);

			foreach ($cabecera as $campo => $valor) {
				$conf = $g->get_campo_impresion($campo, $tipoImpresion);

				if (!isset($conf->scalar) && $conf->visible == 1 && $conf->cabecera == 1) {
					$pdf = generar_fimpresion($pdf, $valor, $conf);
				}
			}
		}
		$pdf->Output("antiguedad_empleado_" . time() . ".pdf", 'I');
		die();
	} else {
		die('forbidden');
	}
});

$app->run();
