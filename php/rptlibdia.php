<?php
set_time_limit(0);
require 'vendor/autoload.php';
require_once 'db.php';
require_once  'conta.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptlibdia', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    //#Transacciones bancarias -> origen = 1
    $query = "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(1, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
    $query.= "CONCAT(d.descripcion, ' ', b.numero, ' ', c.nombre) AS referencia, b.concepto, b.id, 1 AS origen ";
    $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
    $query.= "WHERE b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND c.idempresa = $d->idempresa ";

    //#Compras -> origen = 2
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(2, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechaingreso AS fecha, ";
    $query.= "CONCAT('Compra', ' ', b.serie, '-', b.documento, ' ') AS referencia, b.conceptomayor AS concepto, b.id, 2 AS origen ";
    $query.= "FROM compra b ";
    $query.= "WHERE b.idreembolso = 0 AND b.fechaingreso >= '$d->fdelstr' AND b.fechaingreso <= '$d->falstr' AND b.idempresa = $d->idempresa ";

    //#Ventas -> origen = 3
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(3, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
    $query.= "CONCAT('Venta', ' ', b.serie, '-', b.numero) AS referencia, b.conceptomayor AS concepto, b.id, 3 AS origen ";
    $query.= "FROM factura b ";
    $query.= "WHERE b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND b.anulada = 0 AND b.idempresa = $d->idempresa ";

    //#Directas -> origen = 4
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(4, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
    $query.= "CONCAT('Directa No.', LPAD(b.id, 5, '0')) AS referencia, '' AS concepto, b.id, 4 AS origen ";
    $query.= "FROM directa b ";
    $query.= "WHERE b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND b.idempresa = $d->idempresa ";
    $query.= (int)$d->vercierre === 0 ? "AND b.tipocierre NOT IN(1, 2, 3, 4) " : '';

    //#Reembolsos -> origen = 5
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(5, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechaingreso AS fecha, ";
    $query.= "CONCAT('Compra', ' ', b.serie, '-', b.documento, ' ') AS referencia, b.conceptomayor AS concepto, b.id, 5 AS origen ";
    $query.= "FROM compra b ";
    $query.= "WHERE b.idreembolso > 0 AND b.fechaingreso >= '$d->fdelstr' AND b.fechaingreso <= '$d->falstr' AND b.idempresa = $d->idempresa ";

    $query.= "ORDER BY 2, 1";
    $ld = $db->getQuery($query);
    $cnt = count($ld);

    for($i = 0; $i < $cnt; $i++){
        $query = "SELECT b.codigo, b.nombrecta, a.debe, a.haber, 0 AS estotal ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.origen = ".((int)$ld[$i]->origen != 5 ? $ld[$i]->origen : 2)." ".((int)$ld[$i]->origen != 1 ? "AND a.anulado = 0" : "")." AND a.idorigen = ".$ld[$i]->id." ";
        $query.= ((int)$ld[$i]->origen != 5 ? "AND a.activada = 1 " : "");
        $query.= "ORDER BY a.debe DESC, b.nombrecta";
        $det = $db->getQuery($query);
        $query = "SELECT 0 AS codigo, 'Totales' AS nombrecta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, 1 AS estotal ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
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

$app->post('/librodiario', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $conta = new contabilidad($d->fdelstr, $d->falstr, $d->idempresa, (int)$d->vercierre);

    $query = "SELECT nomempresa, abreviatura, 0.00 AS debe, 0.00 AS haber, DATE_FORMAT(NOW(), '$db->_formatoFechaHora') AS hoy, ";
    $query.= "DATE_FORMAT('$d->fdelstr', '$db->_formatoFecha') AS del, DATE_FORMAT('$d->falstr', '$db->_formatoFecha') AS al ";
    $query.= "FROM empresa WHERE id = $d->idempresa";
    $empresa = $db->getQuery($query)[0];

    $ld = $db->getQuery($conta->getPolizas());
    $cnt = count($ld);
    for($i = 0; $i < $cnt; $i++){
        $ld[$i]->dld = $db->getQuery($conta->getDetallePoliza($ld[$i]->origen, $ld[$i]->id));
    }

    $sumas = $db->getQuery($conta->getSumasDebeHaber());
    if(count($sumas) > 0){
        $empresa->debe = $sumas[0]->debestr;
        $empresa->haber = $sumas[0]->haberstr;
    }

    print json_encode(['empresa'=>$empresa, 'ld'=>$ld]);

});

$app->run();