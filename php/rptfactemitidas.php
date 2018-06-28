<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/srchcli/:idempresa/:qstra+', function($idempresa, $qstra){
    $db = new dbcpm();
    $qstr = $qstra[0];

    $query = "SELECT DISTINCT a.idcliente, a.facturara, a.nit, a.retisr, a.retiva, a.direccion ";
    $query.= "FROM detclientefact a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN contrato c ON b.id = c.idcliente ";
    $query.= "WHERE c.idempresa = $idempresa AND a.fal IS NULL AND (a.facturara LIKE '%$qstr%' OR b.nombre LIKE '%$qstr%')";
    $query.= "ORDER BY 2";
    print json_encode(['results' => $db->getQuery($query)]);
});

$app->post('/factemitidas', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $info = new stdclass();

    if(!isset($d->idproyecto)){ $d->idproyecto = 0; }

    $query = "SELECT DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS fdel, ";
    $query.= "DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal, 0.00 AS totfacturado, DATE_FORMAT(NOW(), '%d/%m/%Y') AS hoy, ";
    $query.= ((int)$d->tipo == 2 ? "'PAGADAS'" : ((int)$d->tipo == 3 ? "'NO PAGADAS'" : "''"))." AS tipo ";
    //print $query;
    $info->general = $db->getQuery($query)[0];

    $qGen = "SELECT a.id, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempre, a.serie, a.numero, 
    IF(a.anulada = 0, TRIM(a.nombre), 'ANULADA') AS cliente, IF(c.tipo IS NULL, TRIM(SUBSTR(a.conceptomayor, LOCATE('(', a.conceptomayor) + 1, LOCATE(')', a.conceptomayor) - 10)), c.tipo) AS tipo, 
    IF(a.anulada = 0, a.subtotal, 0.00) AS total, IF(c.periodo IS NULL, TRIM(SUBSTR(a.conceptomayor, (LOCATE(')', a.conceptomayor) + 1))), c.periodo) AS periodo, b.ordensumario 
    FROM factura a 
    INNER JOIN empresa b ON b.id = a.idempresa 
    LEFT JOIN (
        SELECT x.idfactura,
        GROUP_CONCAT(DISTINCT y.desctiposervventa ORDER BY y.desctiposervventa SEPARATOR ', ') AS tipo, 
        GROUP_CONCAT(DISTINCT CONCAT(z.nombrecorto, '. / ', x.anio) ORDER BY x.mes, x.anio SEPARATOR ', ') AS periodo
        FROM detfact x
        INNER JOIN tiposervicioventa y ON y.id = x.idtiposervicio
        INNER JOIN mes z ON z.id = x.mes
        INNER JOIN factura w ON w.id = x.idfactura
        WHERE w.fecha >= '$d->fdelstr' AND w.fecha <= '$d->falstr' ";
    $qGen.= $d->idempresa != '' ? "AND w.idempresa IN($d->idempresa) ": '';
    $qGen.= (int)$d->tipo == 2 ? "AND w.pagada = 1 " : ((int)$d->tipo == 3 ? "AND w.pagada = 0 " : '');
    $qGen.="GROUP BY x.idfactura";
    $qGen.= ") c ON a.id = c.idfactura LEFT JOIN cliente d ON d.id = a.idcliente ";
    $qGen.= "LEFT JOIN (SELECT v.id AS idcontrato, v.idproyecto, u.nomproyecto AS proyecto FROM contrato v INNER JOIN proyecto u ON u.id = v.idproyecto) e ON a.idcontrato = e.idcontrato ";
    $qGen.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.esparqueo = 0 AND LENGTH(a.numero) > 0 ";
    $qGen.= $d->idempresa != '' ? "AND a.idempresa IN($d->idempresa) " : '';
    $qGen.= trim($d->cliente) != '' && (int)$d->idcliente > 0 ? "AND a.anulada = 0 AND (a.idcliente = $d->idcliente OR a.nombre LIKE '%$d->cliente%' OR a.nit LIKE '%$d->cliente%' OR d.nombre LIKE '%$d->cliente%' OR d.nombrecorto LIKE '%$d->cliente%') " : '';
    $qGen.= trim($d->cliente) != '' && (int)$d->idcliente == 0 ? "AND a.anulada = 0 AND (a.nombre LIKE '%$d->cliente%' OR a.nit LIKE '%$d->cliente%' OR d.nombre LIKE '%$d->cliente%' OR d.nombrecorto LIKE '%$d->cliente%') " : '';
    $qGen.= (int)$d->tipo == 2 ? "AND a.pagada = 1 " : ((int)$d->tipo == 3 ? "AND a.pagada = 0 " : '');
    $qGen.= (int)$d->idproyecto > 0 ? "AND e.idproyecto = $d->idproyecto " : '';
    $qGen.= (int)$d->idtsventa > 0 ? "AND (SELECT COUNT(idfactura) FROM detfact WHERE idfactura = a.id AND idtiposervicio = $d->idtsventa) > 0 " : '';
    $qGen.= "ORDER BY a.numero";

    $query = "SELECT DISTINCT z.idempresa, z.empresa, 0.00 AS totfacturado FROM ($qGen) z ORDER BY z.ordensumario";
    $info->facturas = $db->getQuery($query);
    $cntEmpresas = count($info->facturas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $info->facturas[$i];
        $query = "SELECT z.id, z.idempresa, z.empresa, z.abreviaempre, z.serie, z.numero, z.cliente, z.tipo, FORMAT(z.total, 2) AS total, z.periodo ";
        $query.= "FROM ($qGen) z ";
        $query.= "WHERE z.idempresa = $empresa->idempresa ";
        $query.= "ORDER BY z.numero";
        $empresa->facturas = $db->getQuery($query);
        if(count($empresa->facturas) > 0){
            $query = "SELECT FORMAT(SUM(z.total), 2) FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa";
            $empresa->totfacturado = $db->getOneField($query);
        }
    }

    $query = "SELECT FORMAT(SUM(z.total), 2) FROM ($qGen) z ";
    $info->general->totfacturado = $db->getOneField($query);

    print json_encode($info);
});

$app->post('/factspend', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $info = new stdclass();

    if(!isset($d->idproyecto)){ $d->idproyecto = 0; }

    $query = "SELECT DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal, 0.00 AS totpendiente, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy ";
    $info->generales = $db->getQuery($query)[0];

    $qGen = "SELECT d.nombre AS cliente, d.nombrecorto AS abreviacliente, e.desctiposervventa AS tipo, (((a.monto - a.descuento) * IF(f.eslocal = 0, 7.40, 1)) * 1.12) AS montoconiva, DATE_FORMAT(a.fechacobro, '%d/%m/%Y') AS fechacobro, c.idempresa 
        FROM cargo a
        INNER JOIN detfactcontrato b ON b.id = a.iddetcont
        INNER JOIN contrato c ON c.id = b.idcontrato
        INNER JOIN cliente d ON d.id = c.idcliente
        INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa
        INNER JOIN moneda f ON f.id = b.idmoneda
        WHERE a.fechacobro <= '$d->falstr' AND a.facturado = 0 AND a.anulado = 0 AND c.inactivo = 0 AND (a.monto - a.descuento) > 0 ";
    $qGen.= $d->idempresa != '' ? "AND c.idempresa IN($d->idempresa) " : '';
    $qGen.= (int)$d->idproyecto > 0 ? "AND c.idproyecto = $d->idproyecto " : '';
    $qGen.= (int)$d->idtsventa > 0 ? "AND b.idtipoventa = $d->idtsventa " : '';
    $qGen.= "
        UNION ALL
        SELECT d.nombre AS cliente, d.nombrecorto AS abreviacliente, 'Agua' AS tipo,
        IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00) AS montoconiva,
        DATE_FORMAT(a.fechacorte, '%d/%m/%Y') AS fechacobro, b.idempresa 
        FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico INNER JOIN contrato c ON c.id = (SELECT b.id FROM contrato b WHERE FIND_IN_SET(a.idunidad, b.idunidad) LIMIT 1)
        INNER JOIN cliente d ON d.id = c.idcliente INNER JOIN tiposervicioventa f ON f.id = b.idtiposervicio
        INNER JOIN proyecto g ON g.id = a.idproyecto INNER JOIN unidad h ON h.id = a.idunidad
        WHERE a.estatus IN(1, 2) AND b.pagacliente = 0 AND
        a.mes <= MONTH('$d->falstr') AND a.anio <= YEAR('$d->falstr') AND (c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->falstr')) AND
        IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00 ) > 0 ";
    $qGen.= $d->idempresa != '' ? "AND b.idempresa IN($d->idempresa) " : '';
    $qGen.= (int)$d->idproyecto > 0 ? "AND c.idproyecto = $d->idproyecto " : '';
    $qGen.= !in_array((int)$d->idtsventa, [0, 4]) ? " AND 0 = 1 " : '';
    $qGen.= "ORDER BY 1, 3";

    $query = "SELECT DISTINCT z.idempresa, y.nomempresa AS empresa, 0.00 totpendiente FROM ($qGen) z INNER JOIN empresa y ON y.id = z.idempresa ORDER BY y.ordensumario";
    $info->pendientes = $db->getQuery($query);
    $cntEmpresas = count($info->pendientes);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $info->pendientes[$i];
        $query = "SELECT z.cliente, z.abreviacliente, z.tipo, FORMAT(z.montoconiva, 2) AS montoconiva, z.fechacobro ";
        $query.= "FROM ($qGen) z ";
        $query.= "WHERE z.idempresa = $empresa->idempresa ";
        $query.= "ORDER BY z.cliente, z.tipo";
        $empresa->pendientes = $db->getQuery($query);
        if(count($empresa->pendientes) > 0){
            $query = "SELECT FORMAT(SUM(z.montoconiva), 2) FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa";
            $empresa->totpendiente = $db->getOneField($query);
        }
    }

    $query = "SELECT FORMAT(SUM(z.montoconiva), 2) FROM ($qGen) z";
    $info->generales->totpendiente = $db->getOneField($query);

    print json_encode($info);
});

$app->post('/factsparqueo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS fdel, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal, 0.00 AS totfacturado, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy";
    $generales = $db->getQuery($query)[0];

    $qGen = "SELECT a.idempresa, b.nomempresa AS empresa, a.idproyecto, c.nomproyecto AS proyecto, a.serie, MIN(a.numero) AS defactura, MAX(a.numero) AS afactura, SUM(a.subtotal) AS subtotal, b.ordensumario ";
    $qGen.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN proyecto c ON c.id = a.idproyecto ";
    $qGen.= "WHERE a.esparqueo = 1 AND a.fecha >= '$d->fdelstr' and a.fecha <= '$d->falstr' ";
    $qGen.= $d->idempresa != '' ? "AND a.idempresa IN ($d->idempresa) " : '';
    $qGen.= $d->idproyecto != '' ? "AND a.idproyecto IN ($d->idproyecto) " : '';
    $qGen.= "GROUP BY a.idempresa, a.idproyecto, a.serie";

    $query = "SELECT DISTINCT z.idempresa, z.empresa, 0.00 AS totempresa FROM ($qGen) z ORDER BY z.ordensumario";
    $facturas = $db->getQuery($query);
    $cntEmpresas = count($facturas);
    $totfacturado = 0.00;
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $facturas[$i];
        $query = "SELECT DISTINCT z.idproyecto, z.proyecto, 0.00 AS totproyecto FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa ORDER BY z.proyecto";
        $empresa->proyectos = $db->getQuery($query);
        $cntProyectos = count($empresa->proyectos);
        $totempresa = 0.00;
        for($j = 0; $j < $cntProyectos; $j++){
            $proyecto = $empresa->proyectos[$j];
            $query = "SELECT z.serie, z.defactura, z.afactura, FORMAT(z.subtotal, 2) AS totfact FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa AND z.idproyecto = $proyecto->idproyecto ORDER BY z.serie";
            $proyecto->facturas = $db->getQuery($query);
            if(count($proyecto->facturas) > 0){
                $query = "SELECT SUM(z.subtotal) FROM ($qGen) z WHERE z.idempresa = $empresa->idempresa AND z.idproyecto = $proyecto->idproyecto";
                $suma = (float)$db->getOneField($query);
                $totempresa += $suma;
                $proyecto->totproyecto = number_format($suma, 2);
            }
        }
        $totfacturado += $totempresa;
        $empresa->totempresa = number_format($totempresa, 2);
    }

    $generales->totfacturado = number_format($totfacturado, 2);

    print json_encode(['generales' => $generales, 'facturas' => $facturas]);

});


$app->run();