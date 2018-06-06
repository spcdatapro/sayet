<?php
require_once 'fpdf.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

class PDF extends FPDF{
    // Tabla simple
    function BasicTable($header, $data){
        // Cabecera
        foreach($header as $col)
            $this->Cell(40,7,$col,1);
        $this->Ln();
        // Datos
        foreach($data as $row)
        {
            foreach($row as $col)
                $this->Cell(40,6,$col,1);
            $this->Ln();
        }
    }
    // Una tabla más completa
    function ImprovedTable($header, $data, $w, $h = null)
    {
        // Anchuras de las columnas
        // Cabeceras
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],'',0,'C');
        $this->Ln();
        // Datos
        $h = is_null($h) ? 6 : $h;
        $mov = 15.5;
        //$numItems = count($data);
        //$cont = 0;
        foreach($data as $row){
            $this->Cell($mov);
            $this->Cell($w[0],$h,$row[0],'',0,'C');
            $this->Cell($w[1],$h,$row[1],'',0,'L');
            $this->Cell($w[2],$h,$row[2],'',0,'R');
            $this->Cell($w[3],$h,$row[3],'',0,'R');
            $this->Ln();
        }
        // Línea de cierre
        //$this->Cell($mov);
        //$this->Cell(array_sum($w),0,'','T');
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
        $facturas = trim($tmpFacts[0]->facturas);
        if($nomproyecto == '' && trim($tmpFacts[0]->proyectos) !== ''){
            $nomproyecto = trim($tmpFacts[0]->proyectos);
        }
    }

    $nomproyecto = ''; //Para resolver el punto #79 del listado de requerimientos.
    $conceptoext = $nomproyecto != '' ? ('Proyecto: '.$nomproyecto) : '';
    if($conceptoext != ''){ $conceptoext.= ' - '; }
    $conceptoext.= $laot != '' ? ('OT: '.$laot) : '';
    //if($conceptoext != ''){ $conceptoext.= ' - '; }
    $conceptoext.= $facturas != '' ? ('Facturas: '.$facturas) : '';

    return $conceptoext;
}

$meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
$db = new dbcpm();
$numCheque = (int)$_GET['c'];
$query = "SELECT a.id, a.numero, a.fecha, DAY(a.fecha) AS dia, MONTH(a.fecha) AS mes, YEAR(a.fecha) AS anio, FORMAT(a.monto, 2) AS montostr, a.monto, a.beneficiario, a.concepto, a.esnegociable, ";
$query.= "CONCAT(b.nombre, ' / ', c.nommoneda, ' / ', b.nocuenta, ' / Cheque No. ', a.numero) AS banco, d.nomempresa AS empresa, a.idproyecto, a.iddetpagopresup, CONCAT(b.siglas, ' / #',a.numero) as siglas ";
$query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN empresa d ON d.id = b.idempresa ";
$query.= "WHERE a.id = ".$numCheque;
$cheque = $db->getQuery($query)[0];
//var_dump($cheque);
$conceptoextra = getConceptoExtra($db, $numCheque, $cheque->idproyecto, $cheque->iddetpagopresup);
$n2l = new NumberToLetterConverter();

$query = "SELECT b.codigo, ";
$query.= "IF(LENGTH(b.nombrecta) > 20, CONCAT(SUBSTR(b.nombrecta, 1, 17), '...'), b.nombrecta) AS nombrecta, ";
$query.= "a.debe, a.haber FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 1 AND a.idorigen = ".$numCheque." ORDER BY a.debe DESC";
$detcont = $db->getQueryAsArray($query);
$query = "SELECT '' AS codigo, 'TOTALES' AS nombrecta, SUM(a.debe) AS debe, SUM(a.haber) AS haber FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta WHERE a.origen = 1 AND a.idorigen = ".$numCheque;
$totdet = $db->getQueryAsArray($query);
array_push($detcont, $totdet[0]);
//var_dump($detcont);

//Comentado el 07/02/2017 para que puedan hacer las pruebas de ajustes de impresión. Al terminar las pruebas de ajuste DEBE habilitarse de nuevo.
$query = "UPDATE tranban SET impreso = 1 WHERE id = ".$numCheque;
$db->doQuery($query);


//Creación del PDF
$um = 'mm';
//$pdf = new FPDF('P', $um, 'Letter');
$pdf = new PDF('P', $um, 'Letter');
$conv = $um == 'mm' ? 10 : 1;
$pdf->SetMargins(0, 0 , 0);
$pdf->AddPage();
$pdf->SetFont('Arial','', 9);
$borde = 0;
//Generación del cheque
$pdf->Cell(2 * $conv);
$pdf->Cell(8.5 * $conv, 0.6 * $conv, 'Guatemala, '.$cheque->dia.' de '.$meses[(int)$cheque->mes].' de '.$cheque->anio, $borde, 0);
$pdf->Cell(3 * $conv);
$pdf->SetFont('Arial','', 10);
$pdf->Cell(4.5 * $conv, 0.6 * $conv, $cheque->montostr, $borde, 0);
$pdf->SetFont('Arial','', 9);
$pdf->Ln();
$pdf->Cell(1.5 * $conv);
$pdf->Cell(12.3 * $conv, 0.8 * $conv, iconv('UTF-8', 'windows-1252', $cheque->beneficiario), $borde, 0);
$pdf->Ln();
$pdf->Cell(1.5 * $conv);
$pdf->Cell(12.5 * $conv, 0.7 * $conv, $n2l->to_word_int($cheque->monto), $borde, 0);
$pdf->Ln(1.7 * $conv);

if((int)$cheque->esnegociable == 0){
    $pdf->Cell(3.5 * $conv);
    $pdf->Cell(4.5 * $conv, 0.65 * $conv, 'NO NEGOCIABLE', $borde, 0);
}

$pdf->Ln(2.5 * $conv);
$pdf->Cell(1.55 * $conv);
$pdf->SetFont('Arial','', 11);
$pdf->Cell(20 * $conv, 0.7 * $conv, $cheque->siglas, 0, 2);
$pdf->SetFont('Arial','', 9);
$pdf->Ln();
$pdf->Cell(1.8 * $conv);
//$pdf->Cell(10 * $conv, 0.8 * $conv, iconv('UTF-8', 'windows-1252', $cheque->concepto), $borde, 0, 2);
$pdf->MultiCell(10 * $conv, 0.45 * $conv, iconv('UTF-8', 'windows-1252', ($cheque->concepto.' / '.$conceptoextra)), $borde, 'L');
//Generación del voucher
$pdf->Ln();

//$pdf->Cell(1.55 * $conv);
//$pdf->Cell(20 * $conv, 0.7 * $conv, $cheque->empresa, 0, 2);

//$pdf->Cell(20 * $conv, 0.7 * $conv, $cheque->banco, 0, 2);
$pdf->SetFont('Arial','', 9);

$header = [iconv('UTF-8', 'windows-1252', 'CÓDIGO'), 'CUENTA', 'Debe', 'Haber'];
$anchura = [25, 65, 20, 20];
$pdf->ImprovedTable($header, $detcont, $anchura);

$pdf->Cell(1.5 * $conv, 1 * $conv, '', 0, 2);
$pdf->Cell(-18 * $conv);

$anchura = [45, 45, 45, 45];
$header = ['Hecho por', 'Revisado por', 'Autorizado por', iconv('UTF-8', 'windows-1252', 'Recibí conforme')];
$data = [['', '', '', '']];
//$pdf->ImprovedTable($header, $data, $anchura, 15);

$pdf->Output();