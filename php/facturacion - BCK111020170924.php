<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/pendientes', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $d->retisr = $db->getOneField("SELECT retisr FROM empresa WHERE id = $d->idempresa");

    //RetIVA(39, 4), RetISR(39, 4);

    $query = "SELECT a.idcontrato, a.idcliente, a.cliente, a.idtipocliente, a.facturara, GROUP_CONCAT(DISTINCT a.tipo ORDER BY a.tipo SEPARATOR ', ') AS tipo, SUM(a.montosiniva) AS montosiniva, ";
    $query.= "SUM(a.montoconiva) AS montoconiva, 0.00 AS retisr, a.retiva, 0.00 AS ivaaretener, 0.00 AS totapagar, a.proyecto, a.unidades, 1 AS facturar, '$d->params' AS paramstr, 0 AS numfact, ";
    $query.= "'' AS serirefact, SUM(a.descuento) AS descuento, a.retenerisr, clientecorto, GROUP_CONCAT(DISTINCT a.idtipoventa SEPARATOR ',') AS idtipoventa, a.nit, a.direccion ";
    $query.= "FROM(";
    $query.= "SELECT c.id as idcontrato, c.idcliente, d.nombre AS cliente, FacturarA(c.idcliente, b.idtipoventa) AS facturara, CONCAT(e.desctiposervventa, ' ', DATE_FORMAT(a.fechacobro, '%m/%Y')) AS tipo, ";
    $query.= "ROUND(((a.monto) * IF(h.eslocal = 0, $d->tc, 1)), 7) AS montosiniva, ";
    $query.= "ROUND(((a.monto) * IF(h.eslocal = 0, $d->tc, 1)) * 1.12, 7) AS montoconiva, RetIVA(c.idcliente, b.idtipoventa) AS retiva, c.idtipocliente, j.nomproyecto AS proyecto, UnidadesPorContrato(a.idcontrato) AS unidades, ";
    $query.= "ROUND((a.descuento * IF(h.eslocal = 0, $d->tc, 1)), 7) AS descuento, RetISR(c.idcliente, b.idtipoventa) AS retenerisr, d.nombrecorto AS clientecorto, b.idtipoventa, ";
    $query.= "NitFacturarA(c.idcliente, b.idtipoventa) AS nit, DirFacturarA(c.idcliente, b.idtipoventa) AS direccion ";
    $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = c.idcliente ";
    $query.= "INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa INNER JOIN tipocliente g ON g.id = c.idtipocliente ";
    $query.= "INNER JOIN moneda h ON h.id = b.idmoneda INNER JOIN empresa i ON i.id = c.idempresa ";
    $query.= "INNER JOIN proyecto j ON j.id = c.idproyecto ";
    $query.= "WHERE a.fechacobro <= '$d->fvencestr' AND a.facturado = 0 AND c.idempresa = $d->idempresa AND ";
    $query.= "(c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->fvencestr'))";
    $query.= $d->idtipo != '' ? "AND e.id IN($d->idtipo) " : "";
    $query.= ") a ";
    $query.= "GROUP BY a.idcontrato, a.idcliente, a.facturara ";
    $query.= "ORDER BY 3, 5, 6";
    //echo $query."<br/>";
    $resumen = $db->getQuery($query);

    $empresa = $db->getQuery("SELECT congface, seriefact, correlafact FROM empresa WHERE id = $d->idempresa")[0];
    $empresa->correlafact = (int)$empresa->correlafact;

    foreach($resumen as $r){
        //$r->retisr = (int)$d->retisr > 0 ? $db->calculaISR((float)$r->montosiniva) : 0.00;
        $r->retisr = (int)$r->retenerisr > 0 ? $db->calculaISR((float)$r->montosiniva - (float)$r->descuento) : 0.00;
        $r->ivaaretener = (int)$r->retiva > 0 ? $db->calculaRetIVA(((float)$r->montosiniva - (float)$r->descuento), ((int)$r->idtipocliente == 1 ? true : false), ((float)$r->montoconiva - ((float)$r->descuento * 1.12)), ((int)$r->idtipocliente == 2 ? true : false)) : 0.00;
        $r->totapagar = (float)$r->montoconiva - ($r->retisr + $r->ivaaretener);

        if((int)$empresa->congface == 0){
            $r->seriefact = $empresa->seriefact;
            $r->numfact = $empresa->correlafact;
            $empresa->correlafact++;
        }

        $query = "SELECT DISTINCT c.id AS idcontrato, e.desctiposervventa AS tipo, MONTH(a.fechacobro) AS mes, YEAR(a.fechacobro) AS anio, ";
        $query.= "ROUND(((a.monto) * IF(h.eslocal = 0, $d->tc, 1)), 7) AS montosiniva, 1 AS facturar, a.id, e.id AS idtiposervicio, ";
        $query.= "ROUND((a.descuento * IF(h.eslocal = 0, $d->tc, 1)), 7) AS descuento ";
        $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = c.idcliente ";
        $query.= "INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa INNER JOIN detclientefact f ON d.id = f.idcliente INNER JOIN tipocliente g ON g.id = c.idtipocliente ";
        $query.= "INNER JOIN moneda h ON h.id = b.idmoneda INNER JOIN empresa i ON i.id = c.idempresa ";
        $query.= "WHERE a.fechacobro <= '$d->fvencestr' AND a.facturado = 0 AND c.idempresa = $d->idempresa AND f.fal IS NULL AND c.id = $r->idcontrato AND ";
        $query.= "b.idtipoventa IN($r->idtipoventa) ";
        $query.= $d->idtipo != '' ? "AND e.id IN($d->idtipo) " : "";
        $query.= "ORDER BY a.fechacobro, e.desctiposervventa";
        $r->detalle = $db->getQuery($query);
        foreach($r->detalle as $det){
            $det->nommes = $db->nombreMes($det->mes);
        }
    }

    print json_encode($resumen);
});

$app->post('/proyfact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    //$proyeccion = new stdClass();
    $factorIVA = (int)$d->coniva == 1 ? "1.12" : "1";

    $queryEnding = "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN tiposervicioventa c ON c.id = b.idtipoventa INNER JOIN moneda d ON d.id = b.idmoneda ";
    $queryEnding.= "INNER JOIN contrato e ON e.id = a.idcontrato INNER JOIN cliente f ON f.id = e.idcliente INNER JOIN proyecto g ON g.id = e.idproyecto INNER JOIN empresa h ON h.id = e.idempresa ";
    $queryEnding.= "WHERE a.facturado = 0 AND ";
    $queryEnding.= "((e.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR (";
    $queryEnding.= "e.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND e.fechainactivo > '$d->falstr')) ";
    $queryEnding.= $d->empresa != '' ? "AND h.id IN($d->empresa) " : "";
    $queryEnding.= $d->proyecto != '' ? "AND g.id IN($d->proyecto) " : "";
    $queryOBy = "ORDER BY h.nomempresa, g.nomproyecto, f.nombre, CAST(digits(UnidadesPorContrato(e.id)) AS UNSIGNED), UnidadesPorContrato(e.id), c.desctiposervventa";

    $query = "SELECT DISTINCT e.idempresa, h.nomempresa ".$queryEnding.$queryOBy;
    $proyeccion = $db->getQuery($query);
    $cntProyeccion = count($proyeccion);
    for($i = 0; $i < $cntProyeccion; $i++){
        $empresa = $proyeccion[$i];
        $query = "SELECT DISTINCT e.idproyecto, g.nomproyecto ".$queryEnding;
        $query.= "AND e.idempresa = $empresa->idempresa ";
        $query.= $queryOBy;
        $empresa->proyectos = $db->getQuery($query);
        $cntProyectos = count($empresa->proyectos);
        for($j = 0; $j < $cntProyectos; $j++){
            $proyecto = $empresa->proyectos[$j];
            $query = "SELECT DISTINCT e.idcliente, f.nombre AS cliente, f.nombrecorto ".$queryEnding;
            $query.= "AND e.idempresa = $empresa->idempresa AND e.idproyecto = $proyecto->idproyecto ";
            $query.= $queryOBy;
            $proyecto->clientes = $db->getQuery($query);
            $cntClientes = count($proyecto->clientes);
            for($k = 0; $k < $cntClientes; $k++){
                $cliente = $proyecto->clientes[$k];
                $query = "SELECT UnidadesPorContrato(e.id) AS locales, b.idtipoventa, c.desctiposervventa AS servicio, ";
                $query.= ($d->tc != '' ? "'Q'" : "d.simbolo")." AS moneda, FORMAT(";
                $query.= ($d->tc == '' ? "ROUND((a.monto - a.descuento) * $factorIVA, 7)" :
                        "ROUND(
                            IF(
                                d.eslocal = 1,
                                (a.monto - a.descuento),
                                (a.monto - a.descuento) * $d->tc
                            ) * $factorIVA
                        , 7)"
                    ).", 2) AS monto, ";
                $query.= "DATE_FORMAT(e.fechainicia, '%d/%m/%Y') AS fechainicia, DATE_FORMAT(e.fechavence, '%d/%m/%Y') AS fechavence ";
                $query.= $queryEnding."AND e.idempresa = $empresa->idempresa AND e.idproyecto = $proyecto->idproyecto AND e.idcliente = $cliente->idcliente ";
                $query.= $queryOBy;
                //print $query;
                $cliente->locales = $db->getQuery($query);
            }
        }
    }

    print json_encode($proyeccion);

});

$app->post('/recalcular', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $r = new stdClass();
    //$d->retisr = $db->getOneField("SELECT retisr FROM empresa WHERE id = $d->idempresa");
    $r->retisr = (int)$d->retenerisr > 0 ? $db->calculaISR((float)$d->montosiniva - (float)$d->descuento) : 0.00;
    $r->ivaaretener = (int)$d->retiva > 0 ? $db->calculaRetIVA(((float)$d->montosiniva - (float)$d->descuento), ((int)$d->idtipocliente == 1 ? true : false), ($d->montoconiva - ((float)$d->descuento * 1.12)), ((int)$d->idtipocliente == 2 ? true : false)) : 0.00;
    $r->totapagar = (float)$d->montoconiva - ($r->retisr + $r->ivaaretener);

    print json_encode($r);
});

function recalc($d){
    $db = new dbcpm();

    $r = new stdClass();
    $d->retisr = $db->getOneField("SELECT retisr FROM empresa WHERE id = $d->idempresa");
    $r->retisr = (int)$d->retisr > 0 ? $db->calculaISR((float)$d->montosiniva) : 0.00;
    $r->ivaaretener = (int)$d->retiva > 0 ? $db->calculaRetIVA((float)$d->montosiniva, ((int)$d->idtipocliente == 1 ? true : false), $d->montoconiva, ((int)$d->idtipocliente == 2 ? true : false)) : 0.00;
    $r->totapagar = (float)$d->montoconiva - ($r->retisr + $r->ivaaretener);

    return $r;
}

$app->post('/genfact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $params = $d->params;
    $pendientes = $d->pendientes;
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();

    $empresa = $db->getQuery("SELECT congface, seriefact, correlafact FROM empresa WHERE id = $params->idempresa")[0];
    $empresa->correlafact = (int)$empresa->correlafact;
    //$obj = new stdClass();

    foreach($pendientes as $p){

        if((int)$empresa->congface == 0){
            $p->seriefact = "'$empresa->seriefact'";
            $p->numfact = "'$empresa->correlafact'";
            $p->tipofact = "7";
            $empresa->correlafact++;
        }
        else{
            $p->seriefact = "NULL";
            $p->numfact = "NULL";
            $p->tipofact = "1";
        }

        $query = "INSERT INTO factura(";
        $query.= "idempresa, idtipofactura, idcontrato, idcliente, serie, numero, ";
        $query.= "fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, iva, ";
        $query.= "total, noafecto, subtotal, totalletras, idmoneda, tipocambio, ";
        $query.= "retisr, retiva, totdescuento, nit, nombre, direccion";
        $query.= ") VALUES (";
        $query.= "$params->idempresa, $p->tipofact, $p->idcontrato, $p->idcliente, $p->seriefact, $p->numfact, ";
        $query.= "NOW(), MONTH('$params->ffacturastr'), '$params->ffacturastr', 2, '". str_replace(',', ', ', strip_tags($p->tipo))."', ".round((float)$p->montoconiva - (float)$p->montosiniva, 7).", ";
        $query.= "$p->totapagar, 0.00, $p->montoconiva, '".$n2l->to_word($p->totapagar, 'GTQ')."', 1, $params->tc, ";
        $query.= "$p->retisr, $p->ivaaretener, $p->descuento, '$p->nit', '$p->facturara', '$p->direccion'";
        $query.= ")";
        //echo $query.'<br/><br/>';
        $db->doQuery($query);
        $lastid = $db->getLastId();
        foreach($p->detalle as $det) {
            if($det->facturar == 1){
                /*
                $obj->retisr = 0;
                $obj->retiva = $p->retiva;
                $obj->idempresa = $params->idempresa;
                $obj->montosiniva = $det->montosiniva;
                $obj->idtipocliente = $p->idtipocliente;
                $obj->montoconiva = round(((float)$det->montosiniva * 1.12), 2);
                $res = recalc($obj);
                */
                $montoconiva = round(((float)$det->montosiniva * 1.12), 7);
                $query = "INSERT INTO detfact(idfactura, cantidad, descripcion, preciounitario, preciotot, idtiposervicio, mes, anio, descuento) VALUES(";
                //$query.= "$lastid, 1, '".($det->tipo.' de '.$det->nommes.' '.$det->anio)."', $res->totapagar, $res->totapagar, $det->idtiposervicio, $det->mes, $det->anio, $det->descuento";
                $query.= "$lastid, 1, '".($det->tipo.' de '.$det->nommes.' '.$det->anio)."', $montoconiva, $montoconiva, $det->idtiposervicio, $det->mes, $det->anio, $det->descuento";
                $query.= ")";
                $db->doQuery($query);
                $query = "UPDATE cargo SET facturado = 1, idfactura = $lastid WHERE id = $det->id";
                $db->doQuery($query);
            }
        }
    }

    if((int)$empresa->congface == 0){
        $query = "UPDATE empresa SET correlafact = $empresa->correlafact WHERE id = $params->idempresa";
        $db->doQuery($query);
    }

    print json_encode('Generación de facturas completada...');

});

$app->post('/gengface', function() use($app){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT CONCAT(LPAD(YEAR(a.fecha), 4, ' '), LPAD(MONTH(a.fecha), 4, ' '), LPAD(DAY(a.fecha), 4, ' ')) AS fecha, 'FACE' AS tipodoc, ";
    $query.= "TRIM(a.nit) AS nit, '1' AS codmoneda, a.id AS idfactura, 'S' AS tipoventa, ";
    $query.= "TRIM(a.nombre) AS nombre, ";
    $query.= "TRIM(a.direccion) AS direccion, b.nombrecorto, ";
    $query.= "CONCAT('$ ', FORMAT(ROUND((a.total + a.retisr + a.retiva + (a.totdescuento * 1.12 * -1)) / a.tipocambio, 7), 2)) AS montodol, ";
    $query.= "FORMAT(a.tipocambio, 6) AS tipocambio, ";
    $query.= "CONCAT('$ ', FORMAT(ROUND((a.total + (a.totdescuento * 1.12 * -1)) / a.tipocambio, 7), 2)) AS pagonetodol, CONCAT('Q ', ";
    $query.= "FORMAT((a.total + (a.totdescuento * 1.12 * -1)), 2)) AS pagoneto, CONCAT('Q ', FORMAT(a.retiva, 2)) AS retiva, ";
    $query.= "CONCAT('Q ', FORMAT(a.retisr, 2)) AS isr, CONCAT('Q ', FORMAT((a.total + a.retisr + a.retiva + (a.totdescuento * 1.12 * -1)), 2)) AS monto ";
    $query.= "FROM factura a INNER JOIN cliente b ON b.id = a.idcliente ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.anulada = 0 AND (ISNULL(a.firmaelectronica) OR TRIM(a.firmaelectronica) = '') ";
    $query.= "AND a.id > 3680 ";
    $query.= "UNION ALL ";
    $query.= "SELECT CONCAT(LPAD(YEAR(a.fecha), 4, ' '), LPAD(MONTH(a.fecha), 4, ' '), LPAD(DAY(a.fecha), 4, ' ')) AS fecha, 'FACE' AS tipodoc, a.nit, '1' AS codmoneda, a.id AS idfactura, 'S' AS tipoventa, ";
    $query.= "a.nombre, '' AS direccion, '' AS nombrecorto, CONCAT('$ ', FORMAT(ROUND((a.total + a.retisr + a.retiva + (a.totdescuento * 1.12 * -1)) / a.tipocambio, 7), 2)) AS montodol, FORMAT(a.tipocambio, 4) AS tipocambio, ";
    $query.= "CONCAT('$ ', FORMAT(ROUND((a.total + (a.totdescuento * 1.12 * -1)) / a.tipocambio, 7), 2)) AS pagonetodol, ";
    $query.= "CONCAT('Q ', FORMAT((a.total + (a.totdescuento * 1.12 * -1)), 2)) AS pagoneto, CONCAT('Q ', FORMAT(a.retiva, 2)) AS retiva, ";
    $query.= "CONCAT('Q ', FORMAT(a.retisr, 2)) AS isr, CONCAT('Q ', FORMAT((a.total + a.retisr + a.retiva + (a.totdescuento * 1.12 * -1)), 2)) AS monto ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.idcliente = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.anulada = 0 AND (ISNULL(a.firmaelectronica) OR TRIM(a.firmaelectronica) = '') ";
    $query.= "AND a.id > 3680 ";
    //print $query;
    $facturas = $db->getQuery($query);
    $cntFact = count($facturas);
    if($cntFact > 0){
        for($i = 0; $i < $cntFact; $i++){
            $factura = $facturas[$i];
            //Detalle de cada factura
            //iconv("UTF-8", "Windows-1252", $csv);

            //Para cuando la periodicidad es diferente a un mes
            $meses = [2 => 2, 3 => 5];
            $periodo = '';
            $query = "SELECT a.idperiodicidad FROM contrato a INNER JOIN factura b ON a.id = b.idcontrato WHERE b.id = $factura->idfactura LIMIT 1";
            $periodicidad = (int)$db->getOneField($query);
            if($periodicidad > 1){
                $query = "SELECT ";
                $query.= "IF(a.cobro = 1, MONTH(DATE_SUB(b.fecha, INTERVAL 1 MONTH)), MONTH(b.fecha)) AS mesini, ";
                $query.= "IF(a.cobro = 1, MONTH(DATE_SUB(DATE_SUB(b.fecha, INTERVAL 1 MONTH), INTERVAL ".$meses[$periodicidad]." MONTH)), MONTH(DATE_ADD(b.fecha, INTERVAL ".$meses[$periodicidad]." MONTH))) AS mesfin ";
                $query.= "FROM contrato a INNER JOIN factura b ON a.id = b.idcontrato ";
                $query.= "WHERE b.id = $factura->idfactura LIMIT 1";
                $rango = $db->getQuery($query)[0];

                $query = "SELECT GROUP_CONCAT(nombre ORDER BY id SEPARATOR ', ') AS rangomeses FROM mes WHERE ";
                if((int)$rango->mesini < (int)$rango->mesfin){
                    $query.= "id >= $rango->mesini AND id <= $rango->mesfin";
                }else{
                    $query.= "id >= $rango->mesfin AND id <= $rango->mesini";
                }
                $periodo = $db->getOneField($query);
            }
            //
            /*
            $query = "SELECT ROUND(a.preciotot, 7) AS montoconiva, ROUND(a.preciotot / 1.12, 2)  AS montosiniva, ROUND(ROUND(a.preciotot, 7) - (a.preciotot / 1.12), 2) AS iva, a.idtiposervicio, ";
            $query.= "IF(b.esinsertada = 0, ";
            $query.= "IF(a.idtiposervicio <> 4, ";
            $query.= "CONCAT(CONVERT(UPPER(TRIM(e.desctiposervventa)), CHAR CHARACTER SET latin1), ' DE ', CONVERT(TRIM(d.nomproyecto), CHAR CHARACTER SET latin1), ' ', ";
            $query.= "CONVERT(TRIM(UnidadesPorContrato(c.id)), CHAR CHARACTER SET latin1), ', Mes de ', ".($periodo == '' ? "f.nombre" : ("'".$periodo." '")).", ";
            $query.= "' del ".iconv("UTF-8", "Windows-1252", utf8_encode('año'))." ', FORMAT(a.anio, 0)), TRIM(a.descripcion)), ";
            $query.= "TRIM(a.descripcion)) AS descripcion ";
            $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN proyecto d ON d.id = c.idproyecto ";
            $query.= "INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes ";
            $query.= "WHERE a.idfactura = $factura->idfactura ";
            $query.= "UNION ALL ";
            $query.= "SELECT a.preciotot AS montoconiva, ROUND(a.preciotot / 1.12, 2)  AS montosiniva, ROUND(a.preciotot - (a.preciotot / 1.12), 2) AS iva, a.idtiposervicio, a.descripcion ";
            $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes ";
            $query.= "WHERE b.idcliente = 0 AND a.idfactura = $factura->idfactura ";
            */
            $query = "SELECT ROUND(a.preciotot, 7) AS montoconiva, ROUND(a.preciotot / 1.12, 2)  AS montosiniva, ROUND(ROUND(a.preciotot, 7) - (a.preciotot / 1.12), 2) AS iva, a.idtiposervicio, ";
            $query.= "IF(b.esinsertada = 0, ";
            $query.= "IF(a.idtiposervicio <> 4, ";
            $query.= "CONCAT(UPPER(TRIM(e.desctiposervventa)), ' DE ', TRIM(d.nomproyecto), ' ', ";
            $query.= "TRIM(UnidadesPorContrato(c.id)), ', Mes de ', ".($periodo == '' ? "f.nombre" : ("'".$periodo."'")).", ";
            $query.= "' del año ', FORMAT(a.anio, 0)), TRIM(a.descripcion)), ";
            $query.= "TRIM(a.descripcion)) AS descripcion ";
            $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN proyecto d ON d.id = c.idproyecto ";
            $query.= "INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes ";
            $query.= "WHERE a.idfactura = $factura->idfactura ";
            $query.= "UNION ALL ";
            $query.= "SELECT a.preciotot AS montoconiva, ROUND(a.preciotot / 1.12, 2)  AS montosiniva, ROUND(a.preciotot - (a.preciotot / 1.12), 2) AS iva, a.idtiposervicio, a.descripcion ";
            $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes ";
            $query.= "WHERE b.idcliente = 0 AND a.idfactura = $factura->idfactura ";
            //print $query;
            $factura->detfact = $db->getQuery($query);
            $cntLineasDetalle = count($factura->detfact);
            //Linea de total de descuento por factura
            $query = "SELECT ROUND(a.totdescuento * 1.12 * -1, 7) AS totdescconiva, ROUND((a.totdescuento * -1), 2) AS totdesc, ROUND((ROUND((a.totdescuento * 1.12), 7) - a.totdescuento) * -1, 2) AS ivadesc, 'DESCUENTO' AS descripcion ";
            $query.= "FROM factura a ";
            $query.= "WHERE a.id = $factura->idfactura";
            $factura->descuento = $db->getQuery($query)[0];

            //Linea de totales por factura
            $totalConIva = 0.00; $totalSinIva = 0.00; $totalIva = 0.00;
            for($j = 0; $j < $cntLineasDetalle; $j++){
                if(array_key_exists($j, $factura->detfact)){
                    $det = $factura->detfact[$j];
                    $totalConIva += round((float)$det->montoconiva, 2);
                    $totalSinIva += round((float)$det->montosiniva, 2);
                    $totalIva += round((float)$det->iva, 2);
                }
            }

            if((float)$factura->descuento->totdescconiva != 0){
                $cntLineasDetalle++;
                $totalConIva += round((float)$factura->descuento->totdescconiva, 2);
                $totalSinIva += round((float)$factura->descuento->totdesc, 2);
                $totalIva += round((float)$factura->descuento->ivadesc, 2);
            }

            $factura->totales = ['totalconiva' => round($totalConIva, 2), 'totalsiniva' => round($totalSinIva, 2), 'iva' => round($totalIva, 2), 'lineasdet' => $cntLineasDetalle];
        }
    }

    //$app->response->headers->set('Content-Language', 'es');
    $app->response->headers->set('Content-Type', 'application/json;charset=windows-1252');
    print json_encode($facturas);

});

$app->get('/gettxt/:idempresa/:fdelstr/:falstr/:nombre', function($idempresa, $fdelstr, $falstr, $nombre) use($app){
    $db = new dbcpm();
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.trim($nombre).'.txt"');

    //$url = 'http://52.35.3.1:5489/api/report';
    $url = 'http://localhost:5489/api/report';
    $data = ['template' => ['shortid' => 'SJ2xzSzKx'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr"]];
    //print json_encode($data);

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    print iconv('UTF-8','Windows-1252', $respuesta);
});

$app->post('/respuesta', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $cntFacts = count($d);
    for($i = 0; $i < $cntFacts; $i++){
        if($d[$i]->id !== NULL){
            $factura = $d[$i];
            $query = "UPDATE factura SET firmaelectronica = '$factura->firma', respuestagface = '$factura->respuesta', serie = '$factura->serie', numero = '$factura->numero', ";
            //$query.= "nit = '$factura->nit', nombre = '".iconv('Windows-1252','UTF-8', $factura->nombre)."' ";
            $query.= "nit = '$factura->nit', nombre = '$factura->nombre', pendiente = 1 ";
            $query.= "WHERE id = $factura->id";
            //print $query;
            $db->doQuery($query);
        }
    }
    print json_encode(['estatus' => 'TERMINADO!!!']);
});

$app->post('/lstimpfact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    
    $query = "SELECT a.id, a.serie, a.numero, a.fecha, a.nombre AS cliente, a.nit, a.subtotal AS totfact, 1 AS imprimir ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.anulada = 0 AND a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.numero IS NOT NULL ";
    $query.= "ORDER BY a.serie, a.numero, a.nombre";

    print $db->doSelectASJson($query);

});

$app->run();