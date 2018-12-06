<?php 

if ( ! function_exists('elemento')) {
	function elemento($arreglo, $indice, $return = NULL)
	{
		if (is_array($arreglo) && isset($arreglo[$indice]) && !empty($arreglo[$indice])) {
			return $arreglo[$indice];
		}

		return $return;
	}
}

if ( ! function_exists('depurar')) {
	function depurar($datos)
	{
		echo "<pre>";
		print_r($datos);
		echo "</pre>";
	}
}

if ( ! function_exists('get_limite')) {
	function get_limite()
	{
		return 10;
	}
}

if ( ! function_exists('enviar_json')) {
	function enviar_json($arreglo)
	{
		header('Content-Type: application/json');
		echo json_encode($arreglo);
	}
}

if ( ! function_exists('mostrar_errores')) {
	function mostrar_errores()
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
	}
} 

if ( ! function_exists('fecha_angularjs')) {
	function fecha_angularjs($fecha, $tipo='')
	{
		$fecha = substr($fecha, 0, strpos($fecha, '('));
		
		if ($fecha !== false) {
			switch ($tipo) {
				case 1: # 
					return date('Y-m-d h:i:s', strtotime($fecha));
					break;
				
				default:
					return date('Y-m-d', strtotime($fecha));
					break;
			}
		} else {
			return NULL;
		}
	}
}

if ( ! function_exists('get_meses')) {
	function get_meses($mes = '') {
		$meses = [
			1  => 'enero',
			2  => 'febrero',
			3  => 'marzo',
			4  => 'abril',
			5  => 'mayo',
			6  => 'junio',
			7  => 'julio',
			8  => 'agosto',
			9  => 'septiembre',
			10 => 'octubre',
			11 => 'noviembre',
			12 => 'diciembre'
		];

		if (empty($mes)) {
			return $meses;
		} else {
			return $meses[(int)$mes];
		}
	}
}

if (! function_exists('generar_fimpresion')) {
	/**
	 * [generar_fimpresion description]
	 * @param  [type] $pdf  [description]
	 * @param  string $dato [description]
	 * @param  [type] $conf [description]
	 * @return [type]       [description]
	 */
	function generar_fimpresion($pdf, $dato, $conf)
	{
		$pdf->SetY($conf->psy);
		$pdf->SetX($conf->psx);
		$pdf->SetFont($conf->letra, $conf->estilo, $conf->tamanio);

		if ($dato === 'linea') {
			$pdf->Line(
				$conf->psx, 
				$conf->psy, 
				$conf->psx, 
				($conf->psy+$conf->ancho)
			);

			return $pdf;
		}

		if ($dato === 'rectangulo') {
			$pdf->RoundedRect(
				$conf->psx, 
				$conf->psy, 
				$conf->ancho, 
				$conf->espacio, 
				3, 
				'1111', 
				'DF', 
				[], 
				[255,255,255]
			);

			return $pdf;
		}

		if ($conf->multilinea == 1) {
			$pdf->MultiCell(
				$conf->ancho, 
				$conf->espacio, 
				$dato, 
				$conf->borde, 
				$conf->alineacion 
			);
		} else {					
			$pdf->Cell(
				$conf->ancho, 
				$conf->espacio, 
				$dato, 
				$conf->borde, 
				0, 
				$conf->alineacion 
			);
		}

		return $pdf;
	}
}

if ( ! function_exists('formatoFecha')) {
	function formatoFecha($fecha, $tipo = '')
	{
		$date = new DateTime($fecha);

		switch ($tipo) {
			case 1:
				$formato = 'd/m/Y';
				break;
			case 2: # Devuelve el día
				$formato = 'd';
				break;
			case 3: # Devuelve mes
				$formato = 'm';
				break;
			case 4: # Devuelve año
				$formato = 'Y';
				break;
			case 5: # Devuelve primer día del mes ingresado
				$formato = 'Y-m-01';
				break;
			default:
				$formato = "d/m/Y H:i";
				break;
		}
		
		return $date->format($formato);
	}
}

if ( ! function_exists('totalCampo')) {
	/**
	 * Ayuda a sumar todos los valores de un índice determinado de un arreglo
	 * @param  [array] $arreglo [arreglo de datos]
	 * @param  [string || int] $indice   [indice a sumar]
	 * @return [decimal]
	 */
	function totalCampo($arreglo, $indice) {
		$total = 0;
		foreach ($arreglo as $fila) {
			foreach ($fila as $key => $value) {
				if ($key == $indice) {
					$total += $value;
				}
			}
		}
		return $total;
	}
}

if ( ! function_exists('totalesIndice')) {
	function totalesIndice($etotales, $campo, $valor) {
		if (isset($etotales[$campo])) {
			$etotales[$campo] += $valor;
		} else {
			$etotales[$campo] = $valor;
		}
		return $etotales;
	}
}

if ( ! function_exists('totalesPagina')) {
	function totalesPagina($totales, $pdf, $campo, $valor) {
		if (isset($totales[$pdf->getPage()][$campo])) {
			$totales[$pdf->getPage()][$campo] += $valor;
		} else {
			if (isset($totales[$pdf->getPage()-1][$campo])) {
				$totales[$pdf->getPage()][$campo] = $valor+$totales[$pdf->getPage()-1][$campo];
			} else {
				$totales[$pdf->getPage()][$campo] = $valor;
			}
		}
		return $totales;
	}
}

if ( ! function_exists('imprimirTotalesEmpresa')) {
	function imprimirTotalesEmpresa($pdf, $bus, $tipoImpresion, $etotales, $espacio) {
		foreach ($etotales as $campo => $total) {
			$conf = $bus->get_campo_impresion($campo, $tipoImpresion);

			if (!isset($conf->scalar) && $conf->visible == 1) {
				$conf->psy = ($conf->psy+$espacio);
				$pdf       = generar_fimpresion($pdf, number_format($total, 2), $conf);

				$pdf->Line($conf->psx, $conf->psy, ($conf->psx+$conf->ancho), $conf->psy);

				$y = ($conf->psy+$conf->espacio);

				$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
				$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
			}
		}

		return $pdf;
	}
}

if ( ! function_exists('imprimirTotalesPagina')) {
	function imprimirTotalesPagina($pdf, $bus, $tipoImpresion, $totales) {
		$pie  = $bus->get_campo_impresion("vtotalespie", $tipoImpresion);

		foreach ($totales as $key => $subtotales) {
			$pdf->setPage($key);

			foreach ($subtotales as $campo => $total) {
				$conf = $bus->get_campo_impresion($campo, $tipoImpresion);

				if (!isset($conf->scalar) && $conf->visible == 1) {
					$conf->psy = $pie->psy;
					$pdf       = generar_fimpresion($pdf, number_format($total, 2), $conf);

					$y = ($conf->psy+$conf->espacio);

					$pdf->Line($conf->psx, $y, $conf->psx+$conf->ancho, $y);
					$pdf->Line($conf->psx, $y+1, $conf->psx+$conf->ancho, $y+1);
				}
			}
		}

		return $pdf;
	}
}

if ( ! function_exists('imprimirEncabezado')) {
	function imprimirEncabezado($pdf, $bus, $tipoImpresion, $cabecera) {
		for ($i=1; $i <= $pdf->getNumPages(); $i++) { 
			$pdf->setPage($i);

			foreach ($cabecera as $campo => $valor) {
				$conf = $bus->get_campo_impresion($campo, $tipoImpresion);

				if (!isset($conf->scalar) && $conf->visible == 1) {
					$pdf = generar_fimpresion($pdf, $valor, $conf);
				}
			}

			$conf = $bus->get_campo_impresion("vnopagina", $tipoImpresion);
			if (!isset($conf->scalar) && $conf->visible == 1) {
				$pdf = generar_fimpresion($pdf, ($i), $conf);
			}
		}

		return $pdf;
	}
}

if ( ! function_exists('estadoCivil')) {
	function estadoCivil($estado, $genero) {
		$letra = $genero == 2 ? 'a':'o';

		switch ($estado) {
			case 1:
				$nombre = "Solter{$letra}";
				break;
			case 2:
				$nombre = "Casad{$letra}";
				break;
			
			default:
				$nombre = 'Sin Definir';
				break;
		}

		return $nombre;
	}
}

if ( ! function_exists('getCartaVacaciones')) {
	function getCartaVacaciones($args=[])
	{
		$fecha = date('d/m/Y');
		$inicio = formatoFecha($args["inicio"], 1);
		$fin = formatoFecha($args["fin"], 1);
		$vacasgozar = formatoFecha($args["vacasgozar"], 1);
		$fingoce = formatoFecha($args["fingoce"], 1);
		$presentar = formatoFecha($args["presentar"], 1);
		
		$carta = <<<EOT
\n\n\n
Guatemala {$fecha}
\n
Señores {$args['empresa']}
Presente

\tHago constar que de conformidad con el artículo No. 130 del código de trabajo, desde esta fecha he principiado a gozar de mi período de vacaciones por el término de 15 días.

\nVacaciones que me corresponden por el úlitmo año de trabajo en su establecimiento comercial comprendido del {$inicio} al {$fin}.

\nA gozar del {$vacasgozar} al {$fingoce}.
	debiendo presentarme a mis labores el día {$presentar}.

\nAsí mismo hago constar que el importe del sueldo correspondiente a dichas vacaciones me ha sido cubierto por anticipado; y que firmo la presente de conformidad con el artículo No. 137 del código de trabajo.
\n
Atentamente,
\n\n
_________________________________
{$args['empleado']}
\n\n\n
Autorizado por:
\n\n
_________________________________

EOT;
		return $carta;
	}
}