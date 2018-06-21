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

    $query = "SELECT nomempresa AS empresa, abreviatura AS abreviaempre, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS fdel, ";
    $query.= "DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal, 0.00 AS totfacturado, DATE_FORMAT(NOW(), '%d/%m/%Y') AS hoy, ";
    $query.= ((int)$d->tipo == 2 ? "'PAGADAS'" : ((int)$d->tipo == 3 ? "'NO PAGADAS'" : "''"))." AS tipo ";
    $query.= "FROM empresa WHERE id = $d->idempresa";
    //print $query;
    $info->general = $db->getQuery($query)[0];

    $query = "SELECT a.id, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempre, a.serie, a.numero, 
    IF(a.anulada = 0, TRIM(a.nombre), 'ANULADA') AS cliente, IF(c.tipo IS NULL, TRIM(SUBSTR(a.conceptomayor, LOCATE('(', a.conceptomayor) + 1, LOCATE(')', a.conceptomayor) - 10)), c.tipo) AS tipo, 
    IF(a.anulada = 0, FORMAT(a.subtotal, 2), 0.00) AS total, IF(c.periodo IS NULL, TRIM(SUBSTR(a.conceptomayor, (LOCATE(')', a.conceptomayor) + 1))), c.periodo) AS periodo
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
        WHERE w.fecha >= '$d->fdelstr' AND w.fecha <= '$d->falstr' AND w.idempresa = $d->idempresa ";
    $query.= (int)$d->tipo == 2 ? "AND w.pagada = 1 " : ((int)$d->tipo == 3 ? "AND w.pagada = 0 " : '');
    $query.="GROUP BY x.idfactura";
    $query.= ") c ON a.id = c.idfactura LEFT JOIN cliente d ON d.id = a.idcliente ";
    $query.= "LEFT JOIN (SELECT v.id AS idcontrato, v.idproyecto, u.nomproyecto AS proyecto FROM contrato v INNER JOIN proyecto u ON u.id = v.idproyecto) e ON a.idcontrato = e.idcontrato ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.idempresa = $d->idempresa AND LENGTH(a.numero) > 0 ";
    $query.= trim($d->cliente) != '' && (int)$d->idcliente > 0 ? "AND a.anulada = 0 AND (a.idcliente = $d->idcliente OR a.nombre LIKE '%$d->cliente%' OR a.nit LIKE '%$d->cliente%' OR d.nombre LIKE '%$d->cliente%' OR d.nombrecorto LIKE '%$d->cliente%') " : '';
    $query.= trim($d->cliente) != '' && (int)$d->idcliente == 0 ? "AND a.anulada = 0 AND (a.nombre LIKE '%$d->cliente%' OR a.nit LIKE '%$d->cliente%' OR d.nombre LIKE '%$d->cliente%' OR d.nombrecorto LIKE '%$d->cliente%') " : '';
    $query.= (int)$d->tipo == 2 ? "AND a.pagada = 1 " : ((int)$d->tipo == 3 ? "AND a.pagada = 0 " : '');
    $query.= (int)$d->idproyecto > 0 ? "AND e.idproyecto = $d->idproyecto " : '';
    $query.= "ORDER BY a.numero";
    $info->facturas = $db->getQuery($query);

    $query = "SELECT FORMAT(SUM(a.subtotal), 2) AS total ";
    $query.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa LEFT JOIN cliente d ON d.id = a.idcliente ";
    $query.= "LEFT JOIN (SELECT v.id AS idcontrato, v.idproyecto, u.nomproyecto AS proyecto FROM contrato v INNER JOIN proyecto u ON u.id = v.idproyecto) e ON a.idcontrato = e.idcontrato ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.idempresa = $d->idempresa AND a.anulada = 0 AND LENGTH(a.numero) > 0 ";
    $query.= trim($d->cliente) != '' && (int)$d->idcliente > 0 ? "AND a.anulada = 0 AND (a.idcliente = $d->idcliente OR a.nombre LIKE '%$d->cliente%' OR a.nit LIKE '%$d->cliente%' OR d.nombre LIKE '%$d->cliente%' OR d.nombrecorto LIKE '%$d->cliente%') " : '';
    $query.= trim($d->cliente) != '' && (int)$d->idcliente == 0 ? "AND a.anulada = 0 AND (a.nombre LIKE '%$d->cliente%' OR a.nit LIKE '%$d->cliente%' OR d.nombre LIKE '%$d->cliente%' OR d.nombrecorto LIKE '%$d->cliente%') " : '';
	$query.= (int)$d->tipo == 2 ? "AND a.pagada = 1 " : ((int)$d->tipo == 3 ? "AND a.pagada = 0 " : '');
    $query.= (int)$d->idproyecto > 0 ? "AND e.idproyecto = $d->idproyecto " : '';
    $info->general->totfacturado = $db->getOneField($query);

    print json_encode($info);

});

$app->post('/factspend', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $info = new stdclass();

    if(!isset($d->idproyecto)){ $d->idproyecto = 0; }

    $query = "SELECT nomempresa AS empresa, abreviatura AS abreviaempre, ";
    $query.= "DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal, 0.00 AS totpendiente, DATE_FORMAT(NOW(), '%d/%m/%Y') AS hoy ";
    $query.= "FROM empresa WHERE id = $d->idempresa";
    $info->generales = $db->getQuery($query)[0];


    $query = "SELECT d.nombre AS cliente, d.nombrecorto AS abreviacliente, e.desctiposervventa AS tipo, FORMAT(((a.monto - a.descuento) * IF(f.eslocal = 0, 7.40, 1)) * 1.12, 2) AS montoconiva, DATE_FORMAT(a.fechacobro, '%d/%m/%Y') AS fechacobro
        FROM cargo a
        INNER JOIN detfactcontrato b ON b.id = a.iddetcont
        INNER JOIN contrato c ON c.id = b.idcontrato
        INNER JOIN cliente d ON d.id = c.idcliente
        INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa
        INNER JOIN moneda f ON f.id = b.idmoneda
        WHERE a.fechacobro <= '$d->falstr' AND a.facturado = 0 AND a.anulado = 0 AND c.inactivo = 0 AND c.idempresa = $d->idempresa AND (a.monto - a.descuento) > 0 ";
    $query.= (int)$d->idproyecto > 0 ? "AND c.idproyecto = $d->idproyecto " : '';
    $query.= "
        UNION ALL
        SELECT d.nombre AS cliente, d.nombrecorto AS abreviacliente, 'Agua' AS tipo,
        FORMAT(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00 ), 2) AS montoconiva,
        DATE_FORMAT(a.fechacorte, '%d/%m/%Y') AS fechacobro
        FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico INNER JOIN contrato c ON c.id = (SELECT b.id FROM contrato b WHERE FIND_IN_SET(a.idunidad, b.idunidad) LIMIT 1)
        INNER JOIN cliente d ON d.id = c.idcliente INNER JOIN tiposervicioventa f ON f.id = b.idtiposervicio
        INNER JOIN proyecto g ON g.id = a.idproyecto INNER JOIN unidad h ON h.id = a.idunidad
        WHERE a.estatus IN(1, 2) AND b.pagacliente = 0 AND
        a.mes <= MONTH('$d->falstr') AND a.anio <= YEAR('$d->falstr') AND b.idempresa = $d->idempresa AND (c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->falstr')) AND
        IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00 ) > 0 ";
    $query.= (int)$d->idproyecto > 0 ? "AND c.idproyecto = $d->idproyecto " : '';
    $query.= "ORDER BY 1, 3";
    //print $query;
    $info->pendientes = $db->getQuery($query);

    $query = "SELECT FORMAT(SUM(montoconiva), 2)
        FROM(
        SELECT (a.monto * IF(f.eslocal = 0, 7.40, 1)) * 1.12 AS montoconiva
        FROM cargo a
        INNER JOIN detfactcontrato b ON b.id = a.iddetcont
        INNER JOIN contrato c ON c.id = b.idcontrato
        INNER JOIN cliente d ON d.id = c.idcliente
        INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa
        INNER JOIN moneda f ON f.id = b.idmoneda
        WHERE a.fechacobro <= '$d->falstr' AND a.facturado = 0 AND a.anulado = 0 AND c.inactivo = 0 AND c.idempresa = $d->idempresa AND (a.monto - a.descuento) > 0 ";
    $query.= (int)$d->idproyecto > 0 ? "AND c.idproyecto = $d->idproyecto " : '';
    $query.= "
        UNION ALL
        SELECT
        IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00 ) AS montoconiva
        FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico INNER JOIN contrato c ON c.id = (SELECT b.id FROM contrato b WHERE FIND_IN_SET(a.idunidad, b.idunidad) LIMIT 1)
        INNER JOIN cliente d ON d.id = c.idcliente INNER JOIN tiposervicioventa f ON f.id = b.idtiposervicio
        INNER JOIN proyecto g ON g.id = a.idproyecto INNER JOIN unidad h ON h.id = a.idunidad
        WHERE a.estatus IN(1, 2) AND b.pagacliente = 0 AND
        a.mes <= MONTH('$d->falstr') AND a.anio <= YEAR('$d->falstr') AND b.idempresa = $d->idempresa AND (c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->falstr')) AND
        IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00 ) > 0 ";
    $query.= (int)$d->idproyecto > 0 ? "AND c.idproyecto = $d->idproyecto " : '';
    $query.= ") a ";

    $info->generales->totpendiente = $db->getOneField($query);

    print json_encode($info);
});

$app->run();