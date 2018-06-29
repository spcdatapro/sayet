<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptctrlcc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $cajas = new stdClass();

    $query = "SELECT nombre, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') as fecha, 0.00 AS grantotal, 0.00 AS totasignado, 0.00 AS resultado ";
    $query.= "FROM beneficiario WHERE id = $d->idbeneficiario";
    $cajas->generales = $db->getQuery($query)[0];

    $query = "SELECT DISTINCT a.idempresa, TRIM(b.nomempresa) AS empresa, b.abreviatura AS abreviaempre ";
    $query.= "FROM reembolso a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tiporeembolso c ON c.id = a.idtiporeembolso ";
    $query.= "LEFT JOIN beneficiario d ON d.id = a.idbeneficiario LEFT JOIN tranban e ON e.id = a.idtranban ";
    $query.= "WHERE a.esrecprov = 0 AND a.idbeneficiario = $d->idbeneficiario ";
    $query.= (int)$d->solocc != 0 ? "AND a.idtiporeembolso = 2 " : '';
    $query.= $d->fdinistr != "" ? "AND a.finicio >= '$d->fdinistr' " : "";
    $query.= $d->fainistr != "" ? "AND a.finicio <= '$d->fainistr' " : "";
    $query.= $d->fdfinstr != "" ? "AND a.ffin >= '$d->fdfinstr' " : "";
    $query.= $d->fafinstr != "" ? "AND a.ffin >= '$d->fafinstr' " : "";
    $query.= $d->empresas != "" ? "AND a.idempresa IN($d->empresas) " : "";
    $query.= (int)$d->estatus > 0 ? "AND a.estatus = $d->estatus " : "";
	$query.= "ORDER BY b.ordensumario";
    $cajas->cajas = $db->getQuery($query);
    $cntCajas = count($cajas->cajas);
    $granTotal = 0.00;
    $totAsignado = 0.00;
    for($i = 0; $i < $cntCajas; $i++){
        $caja = $cajas->cajas[$i];
        $query = "SELECT a.id, a.idtiporeembolso, c.desctiporeembolso AS tipo, DATE_FORMAT(a.finicio, '%d/%m/%Y') AS finicio, DATE_FORMAT(a.ffin, '%d/%m/%Y') AS ffin, ";
        $query.= "a.idbeneficiario, d.nombre AS beneficiario, ";
        $query.= "a.beneficiario AS beneficiariostr, a.estatus, IF(a.estatus = 1, 'ABIERTA', 'CERRADA') AS estatusstr, FORMAT(a.fondoasignado, 2) AS fondoasignado, ";
        $query.= "CONCAT(e.tipotrans, e.numero) as tranban, LPAD(a.id, 5, '0') AS nocaja ";
        $query.= "FROM reembolso a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tiporeembolso c ON c.id = a.idtiporeembolso ";
        $query.= "LEFT JOIN beneficiario d ON d.id = a.idbeneficiario ";
        $query.= "LEFT JOIN tranban e ON e.id = a.idtranban ";
        $query.= "WHERE a.esrecprov = 0 AND a.idbeneficiario = $d->idbeneficiario AND a.idempresa = $caja->idempresa ";
        $query.= (int)$d->solocc != 0 ? "AND a.idtiporeembolso = 2 " : '';
        $query.= $d->fdinistr != "" ? "AND a.finicio >= '$d->fdinistr' " : "";
        $query.= $d->fainistr != "" ? "AND a.finicio <= '$d->fainistr' " : "";
        $query.= $d->fdfinstr != "" ? "AND a.ffin >= '$d->fdfinstr' " : "";
        $query.= $d->fafinstr != "" ? "AND a.ffin >= '$d->fafinstr' " : "";
        $query.= (int)$d->estatus > 0 ? "AND a.estatus = $d->estatus " : "";
        $caja->detalles = $db->getQuery($query);        
        $cntDetalles = count($caja->detalles);

        if($cntDetalles > 0){
            $query = "SELECT SUM(a.fondoasignado) ";
            $query.= "FROM reembolso a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tiporeembolso c ON c.id = a.idtiporeembolso ";
            $query.= "LEFT JOIN beneficiario d ON d.id = a.idbeneficiario ";
            $query.= "LEFT JOIN tranban e ON e.id = a.idtranban ";
            $query.= "WHERE a.esrecprov = 0 AND a.idbeneficiario = $d->idbeneficiario AND a.idempresa = $caja->idempresa ";
            $query.= (int)$d->solocc != 0 ? "AND a.idtiporeembolso = 2 " : '';
            $query.= $d->fdinistr != "" ? "AND a.finicio >= '$d->fdinistr' " : "";
            $query.= $d->fainistr != "" ? "AND a.finicio <= '$d->fainistr' " : "";
            $query.= $d->fdfinstr != "" ? "AND a.ffin >= '$d->fdfinstr' " : "";
            $query.= $d->fafinstr != "" ? "AND a.ffin >= '$d->fafinstr' " : "";
            $query.= (int)$d->estatus > 0 ? "AND a.estatus = $d->estatus " : "";
            //$totAsignado += (float)$db->getOneField($query);
        }

        for($j = 0; $j < $cntDetalles; $j++){
            $det = $caja->detalles[$j];
            $tamanio = 30;
            $query = "SELECT DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fechafactura, a.serie, a.documento, a.nit, ";
            $query.= "RPAD(SUBSTR(TRIM(a.proveedor), 1, $tamanio), $tamanio, ' ') AS proveedor, ";
            $query.= "RPAD(SUBSTR(TRIM(a.conceptomayor), 1, $tamanio), $tamanio, ' ') AS conceptomayor, FORMAT(a.totfact, 2) AS totfact ";
            $query.= "FROM compra a WHERE a.idreembolso = $det->id ";
            $query.= "ORDER BY a.id";
            $det->compras = $db->getQuery($query);
            $tfact = 0.00;
            if(count($det->compras) > 0){
                $query = "SELECT SUM(a.totfact) AS totfact ";
                $query.= "FROM compra a WHERE a.idreembolso = $det->id ";                
                $tfact = (float)$db->getOneField($query);
                $granTotal += $tfact;
                $det->compras[] = [
                    'fechafactura' => '', 'serie' => '', 'documento' => '', 'nit' => '', 'proveedor' => '', 'conceptomayor' => 'TOTAL', 'totfact' => number_format($tfact, 2)
                    ];
            }
        }        
    }

    $cajas->generales->grantotal = number_format($granTotal, 2);
    $cajas->generales->totasignado = number_format($totAsignado, 2);
    $cajas->generales->resultado = number_format(($totAsignado - $granTotal), 2);

    print json_encode($cajas);
});

$app->run();