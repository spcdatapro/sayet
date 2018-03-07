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
		$borde = 0;

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
				$borde, 
				$conf->alineacion 
			);
		} else {					
			$pdf->Cell(
				$conf->ancho, 
				$conf->espacio, 
				$dato, 
				$borde, 
				0, 
				$conf->alineacion 
			);
		}

		return $pdf;
	}
}