<?php
require_once 'fpdf.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

class PDF extends FPDF{
    // Tabla simple
    function BasicTable($header, $data){
        // Cabecera
        foreach($header as $col)
            $this->Cell(40,7,$col,0);
        $this->Ln();
        // Datos
        foreach($data as $row)
        {
            foreach($row as $col)
                $this->Cell(40,6,$col,0);
            $this->Ln();
        }
    }
    // Una tabla más completa
    function ImprovedTable($header, $data, $w, $h = null)
    {
        // Anchuras de las columnas
        // Cabeceras
        /*for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],0,0,'C');
        $this->Ln();*/
        // Datos
        $h = is_null($h) ? 6 : $h;
        //$mov = 15.5;
		$mov = 1;
        //$numItems = count($data);
        //$cont = 0;
        foreach($data as $row){
            $this->Cell($mov);
            $this->Cell($w[0],$h,$row[0],0,0,'C');
            $this->Cell($w[1],$h,$row[1],0,0,'L');
            $this->Cell($w[2],$h,$row[2],0,0,'R');
            $this->Cell($w[3],$h,$row[3],0,0,'R');
            $this->Ln();
        }
        // Línea de cierre
        $this->Cell($mov);
        $this->Cell(array_sum($w),0,'',0);
    }
}

function getConceptoExtra($db, $idtranban, $idproyecto, $iddetpagopresup){
    $nomproyecto = '';
    if((int)$idproyecto > 0){
        $nomproyecto = $db->getOneField("SELECT a.nomproyecto AS proyecto FROM proyecto a WHERE a.id = $idproyecto");
    }
    $laot = '';
    if((int)$iddetpagopresup > 0){
        $laot = $db->getOneField("SELECT CONCAT(b.idpresupuesto, '-', b.correlativo) FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup WHERE a.id = $iddetpagopresup");
    }

    $facturas = '';
    $query = "SELECT GROUP_CONCAT(CONCAT(TRIM(d.siglas), TRIM(a.serie), a.documento) SEPARATOR ', ') AS facturas, GROUP_CONCAT(DISTINCT TRIM(e.nomproyecto) ORDER BY e.nomproyecto SEPARATOR ', ') AS proyectos ";
    $query.= "FROM compra a INNER JOIN detpagocompra b ON a.id = b.idcompra INNER JOIN proveedor c ON c.id = a.idproveedor INNER JOIN tipofactura d ON d.id = a.idtipofactura LEFT JOIN proyecto e ON e.id = a.idproyecto ";
    $query.= "WHERE b.idtranban = $idtranban";
    $tmpFacts = $db->getQuery($query);
    if(count($tmpFacts) > 0){
        //$facturas = trim($tmpFacts[0]->facturas);
        if($nomproyecto == '' && trim($tmpFacts[0]->proyectos) !== ''){
            $nomproyecto = trim($tmpFacts[0]->proyectos);
        }
    }

    $conceptoext = $nomproyecto != '' ? ('Proyectos: '.$nomproyecto) : '';
    if($conceptoext != ''){ $conceptoext.= ' - '; }
    $conceptoext.= $laot != '' ? ('OT: '.$laot) : '';
    //if($conceptoext != ''){ $conceptoext.= ' - '; }
    $conceptoext.= $facturas != '' ? ('Facturas: '.$facturas) : '';

    return $conceptoext;
}

$meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
$db = new dbcpm();
$numCheque = (int)$_GET['c'];
$uid = (int)$_GET['uid'];
$query = "SELECT a.id, a.numero, a.fecha, DAY(a.fecha) AS dia, MONTH(a.fecha) AS mes, YEAR(a.fecha) AS anio, FORMAT(a.monto, 2) AS montostr, ";
$query.= "a.monto, a.beneficiario, ";
$query.= "IF(LENGTH(a.concepto) < 70, a.concepto, CONCAT(SUBSTR(a.concepto, 1, 73), '...')) AS concepto, a.esnegociable, ";
$query.= "CONCAT('Cheque No. ', a.numero, ' / ', d.abreviatura) AS banco, d.nomempresa AS empresa, a.idproyecto, a.iddetpagopresup ";
$query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN empresa d ON d.id = b.idempresa ";
$query.= "WHERE a.id = ".$numCheque;
$cheque = $db->getQuery($query)[0];
//var_dump($cheque);
$conceptoextra = getConceptoExtra($db, $numCheque, $cheque->idproyecto, $cheque->iddetpagopresup);
$n2l = new NumberToLetterConverter();

$query = "SELECT b.codigo, b.nombrecta, ";
$query.= "IF(a.debe <> 0, FORMAT(a.debe, 2), '') AS debe, ";
$query.= "IF(a.haber <> 0, FORMAT(a.haber, 2), '') AS haber ";
$query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 1 AND a.idorigen = ".$numCheque." ORDER BY a.debe DESC";
$detcont = $db->getQueryAsArray($query);
$query = "SELECT '' AS codigo, 'TOTALES' AS nombrecta, FORMAT(SUM(a.debe), 2) AS debe, FORMAT(SUM(a.haber), 2) AS haber FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 1 AND a.idorigen = ".$numCheque;
$totdet = $db->getQueryAsArray($query);
array_push($detcont, $totdet[0]);
//var_dump($detcont);

$query = "SELECT UPPER(TRIM(iniciales)) FROM usuario WHERE id = $uid";
$iniciales = $db->getOneField($query);
//Comentado el 07/02/2017 para que puedan hacer las pruebas de ajustes de impresión. Al terminar las pruebas de ajuste DEBE habilitarse de nuevo.
$query = "UPDATE tranban SET impreso = 1 WHERE id = ".$numCheque;
$db->doQuery($query);

//Creación del PDF
$um = 'mm';
//$pdf = new FPDF('P', $um, 'Letter');
$pdf = new PDF('P', $um, 'Letter');
$conv = $um == 'mm' ? 10 : 1;
$pdf->SetMargins(0, 0 * $conv, 0);
$pdf->AddPage();
$pdf->SetFont('Arial','', 10);
$borde = 0;
//Generación del cheque
$pdf->Cell(3 * $conv);
$pdf->Cell(11 * $conv, 0.275 * $conv, 'Guatemala, '.$cheque->dia.' de '.$meses[(int)$cheque->mes].' de '.$cheque->anio, $borde, 0);
$pdf->Cell(1 * $conv);
$pdf->Cell(3.5 * $conv, 0.275 * $conv, $cheque->montostr, $borde, 0);
$pdf->Ln();
$pdf->Ln(0.35 * $conv);
$pdf->Cell(3 * $conv);
$pdf->Cell(11.3 * $conv, 0.65 * $conv, iconv('UTF-8', 'windows-1252', $cheque->beneficiario), $borde, 0);
$pdf->Ln();
$pdf->Ln(0.3 * $conv);
$pdf->Cell(2.5 * $conv);
$pdf->SetFont('Arial','', 9);
$pdf->Cell(11.3 * $conv, 0.65 * $conv, $n2l->to_word_int($cheque->monto), $borde, 0);
$pdf->SetFont('Arial','', 10);
$pdf->Ln(1.3 * $conv);

if((int)$cheque->esnegociable == 0){
    $pdf->Cell(3.9 * $conv);
    $pdf->Cell(3.5 * $conv, 0.65 * $conv, 'NO NEGOCIABLE', $borde, 0);
}

$pdf->Ln(4 * $conv);
$pdf->Cell(1.55 * $conv);
$pdf->MultiCell(10 * $conv, 0.45 * $conv, iconv('UTF-8', 'windows-1252', ($cheque->concepto.' / '.$conceptoextra)), $borde, 'L');
//Generación del voucher
$pdf->Ln();

//$pdf->Cell(1.55 * $conv);
//$pdf->Cell(20 * $conv, 0.45 * $conv, '', 0, 2);
$pdf->setxy(150,75);
$pdf->Cell(20 * $conv, 0.45 * $conv, $cheque->banco, 0, 2);
$pdf->Ln(15);
//$pdf->cell(1);
$pdf->SetFont('Arial','', 10);

$header = [iconv('UTF-8', 'windows-1252', 'CÓDIGO'), 'CUENTA', 'Debe', 'Haber'];
$anchura = [28, 95, 31, 31];
$pdf->ImprovedTable($header, $detcont, $anchura);

$pdf->Cell(1.5 * $conv, 1 * $conv, '', 0, 2);
$pdf->Cell(-18 * $conv);
$pdf->Ln(2 * $conv);
$pdf->cell(1.15 * $conv);
$pdf->Cell(3.5 * $conv, 0.275 * $conv, $iniciales, $borde, 0);
$anchura = [45, 45, 45, 45];
$header = ['Hecho por', 'Revisado por', 'Autorizado por', iconv('UTF-8', 'windows-1252', 'Recibí conforme')];
$data = [['', '', '', '']];
//$pdf->ImprovedTable($header, $data, $anchura, 15);

$pdf->Output();