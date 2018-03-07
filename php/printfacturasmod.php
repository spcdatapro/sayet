<?php
require_once 'fpdf.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$idfacturas = $_GET['idfacturas'];

$db = new dbcpm();
$n2l = new NumberToLetterConverter();

$lpad = 15;
$query = "SELECT a.id, TRIM(a.nombre) AS nombre, TRIM(a.nit) AS nit, IF(a.direccion = NULL, 'CIUDAD', TRIM(a.direccion)) AS direccion, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, ";
$query.= "TRIM(a.serie) AS serie, TRIM(a.numero) AS numero, LPAD(FORMAT(a.total, 2), $lpad, ' ') AS pagoneto, LPAD(FORMAT(a.retiva, 2), $lpad, ' ') AS retiva, ";
$query.= "LPAD(FORMAT(a.retisr, 2), $lpad, ' ') AS retisr, ";
$query.= "FORMAT(a.tipocambio, 5) AS tipocambio, CONCAT('$ ', FORMAT(ROUND((a.total + a.retisr + a.retiva) / a.tipocambio, 2), 2)) AS pagonetodol, '' AS montoenletras, ";
$query.= "LPAD(FORMAT((a.total + a.retisr + a.retiva), 2), $lpad, ' ') AS monto, ";
$query.= "ROUND((a.total + a.retisr + a.retiva), 2) AS total, ";
$query.= "(SELECT nombrecorto FROM cliente WHERE id = a.idcliente) AS nombrecorto ";
$query.= "FROM factura a ";
$query.= "WHERE a.id IN($idfacturas) ORDER BY 7";
//print $query;
$facturas = $db->getQuery($query);
$cntFacturas = count($facturas);
for($i = 0; $i < $cntFacturas; $i++){
    $factura = $facturas[$i];
    $factura->montoenletras = $n2l->to_word_int($factura->total);
    $query = "SELECT FORMAT(a.preciotot, 2) AS montoconiva, FORMAT(ROUND(a.preciotot / 1.12, 2), 2)  AS montosiniva, ";
    $query.= "FORMAT(ROUND(a.preciotot - (a.preciotot / 1.12), 2), 2) AS iva, a.idtiposervicio, ";
    //$query.= "IF(b.esinsertada = 0, CONCAT(UPPER(TRIM(e.desctiposervventa)), ' DE ', TRIM(d.nomproyecto), ' ', 
    //TRIM(UnidadesPorContrato(c.id)), ', Mes de ', f.nombre, ' del año ', a.anio), TRIM(a.descripcion)) AS descripcion, ";
    $query.= "IF(b.esinsertada = 0, ";
    $query.= "IF(a.idtiposervicio <> 4, ";
    $query.= "CONCAT(CONVERT(UPPER(TRIM(e.desctiposervventa)), CHAR CHARACTER SET latin1), ' DE ', CONVERT(TRIM(d.nomproyecto), CHAR CHARACTER SET latin1), ' ', ";
    $query.= "CONVERT(TRIM(UnidadesPorContrato(c.id)), CHAR CHARACTER SET latin1), ', Mes de ', f.nombre, ";
    $query.= "' del ".iconv("UTF-8", "Windows-1252", utf8_encode('año'))." ', FORMAT(a.anio, 0)), TRIM(a.descripcion)), ";
    $query.= "TRIM(a.descripcion)) AS descripcion, ";

    $query.= "a.cantidad 
    FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN proyecto d ON d.id = c.idproyecto 
    INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes 
    WHERE a.idfactura = $factura->id 
    UNION ALL 
    SELECT FORMAT(a.preciotot, 2) AS montoconiva, FORMAT(ROUND(a.preciotot / 1.12, 2), 2)  AS montosiniva, FORMAT(ROUND(a.preciotot - (a.preciotot / 1.12), 2), 2) AS iva, 
    a.idtiposervicio, a.descripcion, a.cantidad 
    FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes 
    WHERE b.idcliente = 0 AND a.idfactura = $factura->id";
    //print $query;
    $factura->detfact = $db->getQuery($query);

    $query = "SELECT ROUND(a.totdescuento * 1.12, 2) AS totdescconiva, a.totdescuento AS totdesc, ROUND((a.totdescuento * 1.12) - a.totdescuento, 2) AS ivadesc, 'DESCUENTO' AS descripcion ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.id = $factura->id";
    $descuento = $db->getQuery($query);

    if(count($descuento) > 0){
        if((float)$descuento[0]->totdescconiva != 0){
            $factura->detfact[] = [
                'montoconiva' => $descuento[0]->totdescconiva,
                'montosiniva' => $descuento[0]->totdesc,
                'iva' => $descuento[0]->ivadesc,
                'idtiposervicio' => '0',
                'descripcion' => 'Descuento',
                'cantidad' => '1'
            ];
        }        
    }
}

//header('Content-Type: application/json');
//print json_encode($facturas);

//Generacion del PDF con todas las facturas
//$pdf = new FPDF('L', 'mm', array(191, 139));
//$pdf = new FPDF('P', 'mm', array(216, 319));
$pdf = new FPDF('p', 'mm', 'Letter');
//$pdf->SetFont('Arial','', 9);
$h = 3.5;

$facpage = 0;
$addy = 0;

for($i = 0; $i < $cntFacturas; $i++){
	
	$facpage++;
	
	$pdf->SetFont('Arial','', 9);
	$pdf->SetMargins(0, 0, 0);
	//$pdf->AddPage();
	
	if($facpage == 1){
		$pdf->AddPage();	
		$pdf->SetAutoPageBreak(true, 0);
		$addy = 0;
	}else{
		$addy = 165;
		//$addy = 0;
	}
	
    $factura = $facturas[$i];

    //Encabezado de la factura
    $pdf->SetXY(17, $addy + 0.5); 
    $pdf->MultiCell(80, $h, utf8_decode($factura->nombre));
    $pdf->SetXY(19, $addy + 9); 
    $pdf->MultiCell(115, $h, utf8_decode($factura->direccion));
    $pdf->SetXY(107, $addy + 0.5);
    $pdf->Cell(30, $h, $factura->nit);
    $pdf->SetXY(145, $addy + 8);
    $pdf->Cell(35, $h, $factura->fecha);

    //Detalle de factura
    $y = $addy + 29;
    foreach($factura->detfact as $det){
        $pdf->SetXY(13, $y); 
        $pdf->MultiCell(110, $h, utf8_decode($det->descripcion));
        $yDescrip = $pdf->GetY();
        $pdf->SetXY(155, $y);
        $pdf->Cell(25, $h, $det->montoconiva, 0, 0, 'R');
        $y = $yDescrip + 2;
    }

    //Pie de factura
    $pdf->SetXY(23, $addy + 106);
    $pdf->Cell(25, $h, 'TC: '.$factura->tipocambio, 0, 2, 'R');
    $pdf->Cell(25, $h, $factura->pagonetodol, 0, 0, 'R');
    $pdf->SetXY(50, $addy + 98);
    $pdf->Cell(35, $h, 'Pago Neto: ', 0, 0, 'R');
    $pdf->Cell(35, $h, $factura->pagoneto, 0, 1, 'R');
    $pdf->SetX(50);
    $pdf->Cell(35, $h, 'Retencion IVA: ', 0, 0, 'R');
    $pdf->Cell(35, $h, $factura->retiva, 0, 1, 'R');
    $pdf->SetX(50);
    $pdf->Cell(35, $h, 'Retencion ISR: ', 0, 0, 'R');
    $pdf->Cell(35, $h, $factura->retisr, 0, 1, 'R');
    $y = $pdf->GetY();
    $pdf->SetX(50);
    $pdf->Cell(35, $h, 'Total: ', 0, 0, 'R');
    $pdf->Cell(35, $h, $factura->monto, 0, 1, 'R');
        
    $pdf->SetXY(155, $y);
    $pdf->Cell(25, $h, $factura->monto, 0, 0, 'R');

    $pdf->SetFont('Arial','', 8.5);
    $pdf->SetXY(80, $addy + 118); 
    $pdf->MultiCell(90, $h, utf8_encode($factura->montoenletras));
	
	$pdf->SetXY(13, $addy + 130);
    $pdf->Cell(25, $h, $factura->nombrecorto, 0, 0, 'R');
	$pdf->SetXY(150, $addy + 130);
    $pdf->Cell(25, $h, $factura->serie."-".$factura->numero, 0, 0, 'R');
	

	if($facpage==2){
		$facpage = 0;
	}

}

$pdf->Output('I', 'Facturas_'.(str_replace(",", "-", $idfacturas)).'.pdf');


