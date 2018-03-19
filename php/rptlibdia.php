<?php
set_time_limit(0);
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptlibdia', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    //#Transacciones bancarias -> origen = 1
    $query = "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(1, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
    $query.= "CONCAT(d.descripcion, ' ', b.numero, ' ', c.nombre) AS referencia, b.concepto, b.id, 1 AS origen ";
    $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
    //$query.= "WHERE b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND c.idempresa = ".$d->idempresa." ";
    $query.= "WHERE ((b.anulado = 0 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr')) AND c.idempresa = $d->idempresa ";
    //#Compras -> origen = 2
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(2, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechaingreso AS fecha, ";
    $query.= "CONCAT('Compra', ' ', b.serie, '-', b.documento, ' ') AS referencia, b.conceptomayor AS concepto, b.id, 2 AS origen ";
    $query.= "FROM compra b INNER JOIN proveedor c ON c.id = b.idproveedor ";
    $query.= "WHERE b.idreembolso = 0 AND b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
    //#Ventas -> origen = 3
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(3, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
    $query.= "CONCAT('Venta', ' ', b.serie, '-', b.numero) AS referencia, b.conceptomayor AS concepto, b.id, 3 AS origen ";
    $query.= "FROM factura b INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = b.idcliente ";
    $query.= "WHERE b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND c.idempresa = ".$d->idempresa." ";
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(3, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
    $query.= "CONCAT('Venta', ' ', b.serie, '-', b.numero) AS referencia, b.conceptomayor AS concepto, b.id, 3 AS origen ";
    $query.= "FROM factura b LEFT JOIN cliente d ON d.id = b.idcliente ";
    $query.= "WHERE b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
    $query.= "AND b.idcontrato = 0 ";
    //#Directas -> origen = 4
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(4, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
    $query.= "CONCAT('Directa No.', LPAD(b.id, 5, '0')) AS referencia, '' AS concepto, b.id, 4 AS origen ";
    $query.= "FROM directa b ";
    $query.= "WHERE b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
    //#Reembolsos -> origen = 5
    $query.= "UNION ALL ";
    /*
    $query.= "SELECT CONCAT('P', YEAR(b.ffin), LPAD(MONTH(b.ffin), 2, '0'), LPAD(DAY(b.ffin), 2, '0'), LPAD(5, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.ffin AS fecha, ";
    $query.= "CONCAT(c.desctiporeembolso, ' No.', LPAD(b.id, 5, '0')) AS referencia, '' AS concepto, b.id, 5 AS origen ";
    $query.= "FROM reembolso b INNER JOIN tiporeembolso c ON c.id = b.idtiporeembolso ";
    $query.= "WHERE b.ffin >= '".$d->fdelstr."' AND b.ffin <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." AND b.estatus = 2 ";
    */
    $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(5, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechaingreso AS fecha, ";
    $query.= "CONCAT('Compra', ' ', b.serie, '-', b.documento, ' ') AS referencia, b.conceptomayor AS concepto, b.id, 5 AS origen ";
    $query.= "FROM compra b INNER JOIN reembolso d ON d.id = b.idreembolso ";
    $query.= "WHERE b.idreembolso > 0 AND b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
    //#Contratos -> origen = 6
	/*
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fechacontrato), LPAD(MONTH(b.fechacontrato), 2, '0'), LPAD(DAY(b.fechacontrato), 2, '0'), LPAD(6, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechacontrato AS fecha, ";
    $query.= "CONCAT('Contrato', ' ', 'GCF', LPAD(b.idcliente, 4, '0'), '-', LPAD(b.correlativo, 4, '0')) AS referencia, '' AS concepto, b.id, 6 AS origen ";
    $query.= "FROM contrato b ";
    $query.= "WHERE b.fechacontrato >= '".$d->fdelstr."' AND b.fechacontrato <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
    */
	//#Recibos de proveedores -> origen = 7
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(7, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha AS fecha, ";
    $query.= "CONCAT('Recibo de proveedores No. ', LPAD(b.id, 5, '0')) AS referencia, '' AS concepto, b.id, 7 AS origen ";
    $query.= "FROM reciboprov b ";
    $query.= "WHERE b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND b.idempresa = $d->idempresa ";
    //#Recibos de clientes -> origen = 8
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(8, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha AS fecha, ";
    $query.= "CONCAT('Recibo de clientes No. ', LPAD(b.id, 5, '0')) AS referencia, '' AS concepto, b.id, 8 AS origen ";
    $query.= "FROM recibocli b ";
    $query.= "WHERE b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND b.idempresa = $d->idempresa ";
    //#Liquidación de documentos -> origen = 9
    /*
    $query.="UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fechaliquida), LPAD(MONTH(b.fechaliquida), 2, '0'), LPAD(DAY(b.fechaliquida), 2, '0'), LPAD(9, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechaliquida AS fecha, ";
    $query.= "CONCAT(d.descripcion, ' ', b.numero, ' ', c.nombre) AS referencia, 'Liquidación de documento' AS concepto, b.id, 9 AS origen ";
    $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
    $query.= "WHERE b.fechaliquida >= '".$d->fdelstr."' AND b.fechaliquida <= '".$d->falstr."' AND c.idempresa = ".$d->idempresa." ";
    */
    $query.= "ORDER BY 2, 1";
    $ld = $db->getQuery($query);
    $cnt = count($ld);

    for($i = 0; $i < $cnt; $i++){
        $query = "SELECT b.codigo, b.nombrecta, a.debe, a.haber, 0 AS estotal ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        //$query.= "WHERE a.anulado = 0 AND a.origen = ".((int)$ld[$i]->origen != 5 ? $ld[$i]->origen : 2)." AND a.idorigen = ".$ld[$i]->id." ";
        $query.= "WHERE a.origen = ".((int)$ld[$i]->origen != 5 ? $ld[$i]->origen : 2)." ".((int)$ld[$i]->origen != 1 ? "AND a.anulado = 0" : "")." AND a.idorigen = ".$ld[$i]->id." ";
        $query.= ((int)$ld[$i]->origen != 5 ? "AND a.activada = 1 " : "");
        $query.= "ORDER BY a.debe DESC, b.nombrecta";
        $det = $db->getQuery($query);
        $query = "SELECT 0 AS codigo, 'Totales' AS nombrecta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, 1 AS estotal ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        //$query.= "WHERE a.anulado = 0 AND a.origen = ".((int)$ld[$i]->origen != 5 ? $ld[$i]->origen : 2)." AND a.idorigen = ".$ld[$i]->id." ";
        $query.= "WHERE a.origen = ".((int)$ld[$i]->origen != 5 ? $ld[$i]->origen : 2)." ".((int)$ld[$i]->origen != 1 ? "AND a.anulado = 0" : "")." AND a.idorigen = ".$ld[$i]->id." ";
        $query.= ((int)$ld[$i]->origen != 5 ? "AND a.activada = 1 " : "");
        $query.= "GROUP BY a.origen, a.idorigen";
        $sum = $db->getQuery($query);
        if(count($det) > 0){ array_push($det, $sum[0]); }
        $ld[$i]->dld = $det;
    }
	
	$empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
	print json_encode(['empresa'=>$empresa, 'ld'=>$ld]);
    //print json_encode($ld);
});

$app->run();