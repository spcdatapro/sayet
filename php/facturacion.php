<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/tst', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    print json_encode(['isr' => $db->calculaISR((float)$d->subtotal) ]);

});

$app->post('/pendientes', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $d->retisr = $db->getOneField("SELECT retisr FROM empresa WHERE id = $d->idempresa");

    //RetIVA(39, 4), RetISR(39, 4);
    //El total de la factura (factura.total) = sumatoria de todos los montos con iva
    //IVA de toda la factura = Round((factura.total) - (factura.total / 1.12), 2)
    //Base o monto sin iva = (factura.total) - Round((factura.total) - (factura.total / 1.12), 2)
    //La base es la sumatoria de todos los montos con iva - iva

    $query = "SELECT a.idcontrato, a.idcliente, a.cliente, a.idtipocliente, a.facturara, GROUP_CONCAT(DISTINCT a.tipo ORDER BY a.tipo SEPARATOR ', ') AS tipo, SUM(a.montosiniva) AS montosiniva, ";
    $query.= "SUM(a.montoconiva) AS montoconiva, 0.00 AS retisr, a.retiva, 0.00 AS ivaaretener, 0.00 AS totapagar, a.proyecto, a.unidades, 1 AS facturar, '$d->params' AS paramstr, 0 AS numfact, ";
    $query.= "'' AS serirefact, SUM(a.descuento) AS descuento, a.retenerisr, clientecorto, GROUP_CONCAT(DISTINCT a.idtipoventa SEPARATOR ',') AS idtipoventa, a.nit, a.direccion, ";
    $query.= "SUM(a.montocargoconiva) AS montocargoconiva, SUM(a.montocargoflat) AS montocargoflat, ROUND(SUM(a.montoconiva) - (SUM(a.montoconiva) / 1.12), 2) AS iva, porcentajeretiva, ";
    $query.= "idmonedafact, idmonedacargo, monedafact, monedacargo ";
    $query.= "FROM(";

    $query.= "SELECT c.id as idcontrato, c.idcliente, d.nombre AS cliente, FacturarA(c.idcliente, b.idtipoventa) AS facturara, CONCAT(e.desctiposervventa, ' ', DATE_FORMAT(a.fechacobro, '%m/%Y')) AS tipo, ";
    $query.= "ROUND(((a.monto - a.descuento) * IF(h.eslocal = 0, $d->tc, 1)), 2) AS montosiniva, ";

    $query.= "ROUND(((a.monto - a.descuento) * IF(h.eslocal = 0, $d->tc, 1)) * 1.12, 2) AS montoconiva, ";

    $query.= "RetIVA(c.idcliente, b.idtipoventa) AS retiva, c.idtipocliente, j.nomproyecto AS proyecto, UnidadesPorContrato(a.idcontrato) AS unidades, ";

    $query.= "ROUND((a.descuento * IF(h.eslocal = 0, $d->tc, 1)) * 1.12, 2) AS descuento, ";

    $query.= "ROUND((a.monto * IF(h.eslocal = 0, $d->tc, 1)) * 1.12, 2) AS montocargoconiva, ";
    $query.= "a.monto AS montocargoflat, ";

    $query.= "RetISR(c.idcliente, b.idtipoventa) AS retenerisr, d.nombrecorto AS clientecorto, b.idtipoventa, ";
    $query.= "NitFacturarA(c.idcliente, b.idtipoventa) AS nit, DirFacturarA(c.idcliente, b.idtipoventa) AS direccion, PorcentajeRetIVA(c.idcliente, b.idtipoventa) AS porcentajeretiva, ";
    $query.= "b.idmonedafact, b.idmoneda AS idmonedacargo, k.simbolo AS monedafact, h.simbolo AS monedacargo ";
    
    $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = c.idcliente ";
    $query.= "INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa INNER JOIN tipocliente g ON g.id = c.idtipocliente ";
    $query.= "INNER JOIN moneda h ON h.id = b.idmoneda INNER JOIN empresa i ON i.id = c.idempresa ";
    $query.= "INNER JOIN proyecto j ON j.id = c.idproyecto INNER JOIN moneda k ON k.id = b.idmonedafact ";
    $query.= "WHERE a.fechacobro <= '$d->fvencestr' AND a.facturado = 0 AND a.anulado = 0 AND c.idempresa = $d->idempresa AND ";
    $query.= "(c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->fvencestr')) AND (a.monto - a.descuento) <> 0 ";
    $query.= $d->idtipo != '' ? "AND e.id IN($d->idtipo) " : "";

    $query.= ") a ";
    $query.= "GROUP BY a.idcontrato, a.idcliente, a.facturara, a.idmonedafact ";
    $query.= "ORDER BY 3, 5, 6";
    //echo $query."<br/>";
    $resumen = $db->getQuery($query);

    $empresa = $db->getQuery("SELECT congface, seriefact, correlafact FROM empresa WHERE id = $d->idempresa")[0];
    $empresa->correlafact = (int)$empresa->correlafact;

    foreach($resumen as $r){
        $r->retisr = (int)$r->retenerisr > 0 ? $db->calculaISR((float)$r->montosiniva) : 0.00;
        $r->ivaaretener = (int)$r->retiva > 0 ? $db->calculaRetIVA((float)$r->montosiniva, ((int)$r->idtipocliente == 1 ? true : false), (float)$r->montoconiva, ((int)$r->idtipocliente == 2 ? true : false), (float)$r->iva, (float)$r->porcentajeretiva) : 0.00;
        $r->totapagar = (float)$r->montoconiva - ($r->retisr + $r->ivaaretener);

        if((int)$empresa->congface == 0){
            $r->seriefact = $empresa->seriefact;
            $r->numfact = $empresa->correlafact;
            $empresa->correlafact++;
        }

        $query = "SELECT DISTINCT c.id AS idcontrato, e.desctiposervventa AS tipo, MONTH(a.fechacobro) AS mes, YEAR(a.fechacobro) AS anio, ";

        $query.= "ROUND(((a.monto - a.descuento) * IF(h.eslocal = 0, $d->tc, 1)), 2) AS montosiniva, ";
        $query.= "ROUND(((a.monto - a.descuento) * IF(h.eslocal = 0, $d->tc, 1)) * 1.12, 2) AS montoconiva, ";
        $query.= "ROUND((a.monto * IF(h.eslocal = 0, $d->tc, 1)) * 1.12, 2) AS montoflatconiva, ";

        $query.= "1 AS facturar, a.id, e.id AS idtiposervicio, ";

        $query.= "ROUND((a.descuento * IF(h.eslocal = 0, $d->tc, 1)) * 1.12, 2) AS descuento, ";

        $query.= "a.monto AS montocargoflat, a.conceptoadicional ";

        $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = c.idcliente ";
        $query.= "INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa INNER JOIN detclientefact f ON d.id = f.idcliente INNER JOIN tipocliente g ON g.id = c.idtipocliente ";
        $query.= "INNER JOIN moneda h ON h.id = b.idmoneda INNER JOIN empresa i ON i.id = c.idempresa ";
        $query.= "WHERE a.fechacobro <= '$d->fvencestr' AND a.facturado = 0 AND a.anulado = 0 AND c.idempresa = $d->idempresa AND f.fal IS NULL AND c.id = $r->idcontrato AND ";
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

function getQueryCargos($d) {
    $query = "SELECT c.id as idcontrato, c.idcliente, d.nombrecorto AS clientecorto, FacturarA(c.idcliente, b.idtipoventa) AS facturara, a.id AS idcargo, b.idmonedafact, k.simbolo AS monedafact,

    @factor := IF(ExentoIVA(c.idcliente, b.idtipoventa) = 0, 1.12, 1) AS factor,

    @montoconiva := ROUND(((a.monto - a.descuento) * IF(h.eslocal = 0, $d->tc, 1)) * @factor, 2) AS montoconiva, 
    @montosiniva := ROUND(@montoconiva / @factor, 2) AS montosiniva,
    ROUND(@montoconiva - @montosiniva, 2) AS iva, 

    @montoconivacnv := ROUND((@montoconiva / $d->tc), 2) AS montoconivacnv, 
    @montosinivacnv := ROUND(@montosiniva / $d->tc, 2) AS montosinivacnv,
    ROUND(@montoconivacnv - @montosinivacnv, 2) AS ivacnv, 

    @descuentoconiva := ROUND((a.descuento * IF(h.eslocal = 0, $d->tc, 1)) * @factor, 2) AS descuentoconiva,
    @descuentosiniva := ROUND(@descuentoconiva / @factor, 2) AS descuentosiniva,
    ROUND(@descuentoconiva - @descuentosiniva, 2) AS descuentoiva,

    @descuentoconivacnv := ROUND(@descuentoconiva / $d->tc, 2) AS descuentoconivacnv,
    @descuentosinivacnv := ROUND(@descuentosiniva / $d->tc, 2) AS descuentosinivacnv,
    ROUND(@descuentoconivacnv - @descuentosinivacnv, 2) AS descuentoivacnv,
    
    @precio := ROUND((a.monto * IF(h.eslocal = 0, $d->tc, 1)) * @factor, 2) AS precio,
    @importebruto := ROUND(@precio * 1, 2) AS importebruto,
    @porcentajedescuento := ROUND((@descuentoconiva * 100) / @importebruto, 4) AS porcentajedescuento,
    @importeneto := ROUND((@importebruto - @descuentoconiva) / @factor, 2) AS importeneto, 
    @importeiva := ROUND((@importebruto - @descuentoconiva - @importeneto), 2) AS importeiva, 
    @importetotal := ROUND((@importeneto + @importeiva), 2) AS importetotal, 

    ROUND(@precio / $d->tc, 2) AS preciocnv, @importebrutocnv := ROUND(@importebruto / $d->tc, 2) AS importebrutocnv, ROUND(@importeneto / $d->tc, 2) AS importenetocnv, ROUND(@importeiva / $d->tc, 2) AS importeivacnv, 
    @importetotalcnv := ROUND(@importetotal / $d->tc, 2) AS importetotalcnv,

    @importeexento := IF(@factor = 1, @importetotal, 0.00) AS importeexento,
    @importeexentocnv := IF(@factor = 1, @importetotalcnv, 0.00) AS importeexentocnv,
    
    CONCAT(e.desctiposervventa, ' ', DATE_FORMAT(a.fechacobro, '%m/%Y')) AS tipo, e.id as idtipo, j.nomproyecto AS proyecto, 
    UnidadesPorContrato(a.idcontrato) AS unidades, RetISR(c.idcliente, b.idtipoventa) AS retenerisr, RetIVA(c.idcliente, b.idtipoventa) AS reteneriva, c.idtipocliente, 
    PorcentajeRetIVA(c.idcliente, b.idtipoventa) AS porcentajeretiva, a.conceptoadicional, MONTH(a.fechacobro) AS mes, YEAR(a.fechacobro) AS anio, 
    NitFacturarA(c.idcliente, b.idtipoventa) AS nit, DirFacturarA(c.idcliente, b.idtipoventa) AS direccion, ExentoIVA(c.idcliente, b.idtipoventa) AS exentoiva, 1 AS facturar
    FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = c.idcliente 
    INNER JOIN tiposervicioventa e ON e.id = b.idtipoventa INNER JOIN tipocliente g ON g.id = c.idtipocliente 
    INNER JOIN moneda h ON h.id = b.idmoneda INNER JOIN empresa i ON i.id = c.idempresa 
    INNER JOIN proyecto j ON j.id = c.idproyecto INNER JOIN moneda k ON k.id = b.idmonedafact 
    WHERE a.fechacobro <= '$d->fvencestr' AND a.facturado = 0 AND a.anulado = 0 AND c.idempresa = $d->idempresa AND 
    (c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->fvencestr')) AND (a.monto - a.descuento) <> 0 ";
    $query.= $d->idtipostr != '' ? "AND e.id IN($d->idtipostr) " : "";
    $query.= $d->idcargo != '' ? "AND a.id IN($d->idcargo) " : "";
    // print $query;
    return $query;
}

$app->post('/pendientesfel', function() {    
    $d = json_decode(file_get_contents('php://input'));    
    $db = new dbcpm();
    $d->idcargo = '';
    if (!isset($d->idtipostr)) { $d->idtipostr = ''; }

    $queryCargos = getQueryCargos($d);
    $query = "
        SELECT z.idcontrato, z.idcliente, z.clientecorto, z.facturara, GROUP_CONCAT(DISTINCT z.idcargo SEPARATOR ', ') AS idcargo, z.idmonedafact, z.monedafact, 
        SUM(z.montoconiva) AS montoconiva, SUM(z.montosiniva) AS montosiniva, SUM(z.iva) AS iva,
        SUM(z.montoconivacnv) AS montoconivacnv, SUM(z.montosinivacnv) AS montosinivacnv, SUM(z.ivacnv) AS ivacnv,
        GROUP_CONCAT(DISTINCT z.tipo ORDER BY z.tipo SEPARATOR ', ') AS tipo, GROUP_CONCAT(DISTINCT z.idtipo SEPARATOR ', ') AS idtipo, $d->tc AS tc, z.proyecto, z.unidades, 
        z.retenerisr, z.reteneriva, z.porcentajeretiva, z.idtipocliente, 0.00 AS isrporretener, 0.00 AS isrporretenercnv, 0.00 AS ivaporretener, 0.00 AS ivaporretenercnv, 
        0.00 AS totapagar, 0.00 AS totapagarcnv, z.nit, z.direccion, z.exentoiva,

        SUM(z.descuentoconiva) AS descuentoconiva, SUM(z.descuentosiniva) AS descuentosiniva, SUM(z.descuentoiva) AS descuentoiva,
        SUM(z.descuentoconivacnv) AS descuentoconivacnv, SUM(z.descuentosinivacnv) AS descuentosinivacnv, SUM(z.descuentoivacnv) AS descuentoivacnv,
        SUM(z.importebruto) AS importebruto, SUM(z.importeneto) AS importeneto, SUM(z.importeiva) AS importeiva, SUM(z.importetotal) AS importetotal,
        SUM(z.importebrutocnv) AS importebrutocnv, SUM(z.importenetocnv) AS importenetocnv, SUM(z.importeivacnv) AS importeivacnv, SUM(z.importetotalcnv) AS importetotalcnv,
        SUM(z.importeexento) AS importeexento, SUM(z.importeexentocnv) AS importeexentocnv,

        1 AS facturar
        FROM ($queryCargos) z
        GROUP BY z.idcontrato, z.idcliente, z.facturara, z.idmonedafact
        ORDER BY z.clientecorto, z.facturara, z.tipo
    ";
    $pendientes = $db->getQuery($query);
    $cntPendientes = count($pendientes);
    for($i = 0; $i < $cntPendientes; $i++) {
        $pendiente = calculaImpuestosYTotal($db, $d, $pendientes[$i]);
        $d->idcargo = $pendiente->idcargo;
        $query = getQueryCargos($d);
        $pendiente->detalle = $db->getQuery($query);
    }    
    print json_encode($pendientes);
});

function calculaImpuestosYTotal($db, $d, $factura) {
    $noEsExentoIVA = (int)$factura->exentoiva === 0;
    $factura->isrporretener = $noEsExentoIVA ? ((int)$factura->retenerisr > 0 ? $db->calculaISR((float)$factura->montosiniva) : 0.00) : 0.00;
    $factura->isrporretenercnv = round($factura->isrporretener / (float)$d->tc, 2);
    $factura->ivaporretener = $noEsExentoIVA ? ((int)$factura->reteneriva > 0 ? $db->calculaRetIVA((float)$factura->montosiniva, ((int)$factura->idtipocliente == 1 ? true : false), (float)$factura->montoconiva, ((int)$factura->idtipocliente == 2 ? true : false), (float)$factura->iva, (float)$factura->porcentajeretiva) : 0.00) : 0.00;
    $factura->ivaporretenercnv = round($factura->ivaporretener / (float)$d->tc, 2);
    $factura->totapagar = round((float)$factura->montoconiva - ($factura->isrporretener + $factura->ivaporretener), 2);
    $factura->totapagarcnv = round($factura->totapagar / (float)$d->tc, 2);
    return $factura;
}

$app->post('/recalcularfel', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $factura = calculaImpuestosYTotal($db, $d, $d->factura);
    print json_encode($factura);
});

$app->post('/detpendientefel', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    if (!isset($d->idtipostr)) { $d->idtipostr = ''; }
    $queryCargos = getQueryCargos($d);
    print $db->doSelectASJson($queryCargos);
});

$app->post('/proyfact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $factorIVA = (int)$d->coniva == 1 ? "1.12" : "1";

    $queryEnding = "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN tiposervicioventa c ON c.id = b.idtipoventa INNER JOIN moneda d ON d.id = b.idmoneda ";
    $queryEnding.= "INNER JOIN contrato e ON e.id = a.idcontrato INNER JOIN cliente f ON f.id = e.idcliente INNER JOIN proyecto g ON g.id = e.idproyecto INNER JOIN empresa h ON h.id = e.idempresa ";
    $queryEnding.= "WHERE a.facturado = 0 AND a.anulado = 0 AND ";
    $queryEnding.= "((e.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR (";
    $queryEnding.= "e.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND e.fechainactivo > '$d->falstr')) ";
    $queryEnding.= $d->empresa != '' ? "AND h.id IN($d->empresa) " : "";
    $queryEnding.= $d->proyecto != '' ? "AND g.id IN($d->proyecto) " : "";
    $queryOBy = "ORDER BY h.ordensumario, h.nomempresa, g.nomproyecto, f.nombre, CAST(digits(UnidadesPorContrato(e.id)) AS UNSIGNED), UnidadesPorContrato(e.id), c.desctiposervventa";

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
                if(count($cliente->locales) > 0 && $d->tc != ''){
                    //Agregará la suma solo cuando la moneda sea la misma.
                    $query = "SELECT FORMAT(";
                    $query.= "SUM(IF(d.eslocal = 1, (a.monto - a.descuento), (a.monto - a.descuento) * $d->tc) * $factorIVA), 2) AS monto ";
                    $query.= $queryEnding."AND e.idempresa = $empresa->idempresa AND e.idproyecto = $proyecto->idproyecto AND e.idcliente = $cliente->idcliente ";
                    $query.= $queryOBy;
                    $suma = $db->getOneField($query);
                    $cliente->locales[] = [
                        'locales' => '', 'idtipoventa' => '', 'servicio' => 'Total:', 'moneda' => 'Q', 'monto' => $suma, 'fechainicia' => '', 'fechavence' => ''
                    ];
                }
            }
        }
    }    

    print json_encode($proyeccion);

});

$app->post('/recalcular', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

     /* Esta es la forma correcta que funciona.
     $r->retisr = (int)$r->retenerisr > 0 ? $db->calculaISR((float)$r->montosiniva) : 0.00;
     $r->ivaaretener = (int)$r->retiva > 0 ? $db->calculaRetIVA((float)$r->montosiniva, ((int)$r->idtipocliente == 1 ? true : false), (float)$r->montoconiva, ((int)$r->idtipocliente == 2 ? true : false), (float)$r->iva, (float)$r->porcentajeretiva) : 0.00;
     $r->totapagar = (float)$r->montoconiva - ($r->retisr + $r->ivaaretener);
     */
    $r = new stdClass();
    //$r->retisr = (int)$d->retenerisr > 0 ? $db->calculaISR((float)$d->montosiniva - (float)$d->descuento) : 0.00;
    $r->retisr = (int)$d->retenerisr > 0 ? $db->calculaISR((float)$d->montosiniva) : 0.00;
    $r->ivaaretener = (int)$d->retiva > 0 ? $db->calculaRetIVA((float)$d->montosiniva, ((int)$d->idtipocliente == 1 ? true : false), (float)$d->montoconiva, ((int)$d->idtipocliente == 2 ? true : false), $d->iva, (float)$d->porcentajeretiva) : 0.00;
    $r->totapagar = (float)$d->montoconiva - ($r->retisr + $r->ivaaretener);

    print json_encode($r);
});

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
        $query.= "retisr, retiva, totdescuento, nit, nombre, direccion, montocargoiva, montocargoflat, idmonedafact,";
        $query.= "subtotalcnv, totalcnv, retivacnv, retisrcnv, totdescuentocnv";
        $query.= ") VALUES (";
        $query.= "$params->idempresa, $p->tipofact, $p->idcontrato, $p->idcliente, $p->seriefact, $p->numfact, ";
        $query.= "NOW(), MONTH('$params->ffacturastr'), '$params->ffacturastr', 2, '". str_replace(',', ', ', strip_tags($p->tipo))."', $p->iva, ";
        $query.= "$p->totapagar, 0.00, $p->montoconiva, '".$n2l->to_word($p->totapagar, 'GTQ')."', 1, $params->tc, ";
        $query.= "$p->retisr, $p->ivaaretener, $p->descuento, '$p->nit', '$p->facturara', '$p->direccion', $p->montocargoconiva, $p->montocargoflat, $p->idmonedafact, ";

        if((int)$p->idmonedafact !== 1) {
            $query.= "($p->montoconiva / $params->tc), ($p->totapagar / $params->tc), ($p->ivaaretener / $params->tc), ($p->retisr / $params->tc), ($p->descuento / $params->tc)";
        } else {
            $query.= "$p->montoconiva, $p->totapagar, $p->ivaaretener, $p->retisr, $p->descuento";
        }

        $query.= ")";
        //print $query.'<br/><br/>';
        $lastid = 0;
        if((float)$p->montoconiva != 0){
            $db->doQuery($query);
            $lastid = $db->getLastId();
        }

        if((int)$lastid > 0){
            foreach($p->detalle as $det) {
                if($det->facturar == 1){
                    $conceptoAdicional = 'NULL';
                    if(isset($det->conceptoadicional)){
                        if(trim($det->conceptoadicional) !== ''){
                            $conceptoAdicional = "'".trim($det->conceptoadicional)."'";
                        }
                    }

                    $query = "INSERT INTO detfact(";
                    $query.= "idfactura, cantidad, descripcion, preciounitario, preciotot, idtiposervicio, mes, anio, descuento, montoconiva, montoflatconiva, ";
                    $query.= "conceptoadicional, montoflatconivacnv, preciounitariocnv, descuentocnv";
                    $query.= ") VALUES(";
                    $query.= "$lastid, 1, '".($det->tipo.' de '.$det->nommes.' '.$det->anio)."', $det->montoconiva, $det->montoconiva, $det->idtiposervicio, $det->mes, $det->anio, $det->descuento, $det->montoconiva, $det->montoflatconiva,";
                    $query.= "$conceptoAdicional, ";

                    if((int)$p->idmonedafact !== 1) {
                        $query.= "($det->montoflatconiva / $params->tc), ($det->montoconiva / $params->tc), ($det->descuento / $params->tc)";
                    } else {
                        $query.= "$det->montoflatconiva, $det->montoconiva, $det->descuento";
                    }

                    $query.= ")";
                    //print $query;
                    if((float)$det->montoconiva != 0){
                        $db->doQuery($query);
                    }
                    $query = "UPDATE cargo SET facturado = 1, idfactura = $lastid WHERE id = $det->id";
                    $db->doQuery($query);
                }
            }


            $url = 'http://localhost/sayet/php/genpartidasventa.php/genpost';
            $data = ['ids' => $lastid, 'idcontrato' => 1];
            $db->CallJSReportAPI('POST', $url, json_encode($data));
        }
    }

    if((int)$empresa->congface == 0){
        $query = "UPDATE empresa SET correlafact = $empresa->correlafact WHERE id = $params->idempresa";
        $db->doQuery($query);
    }

    print json_encode('Generación de facturas completada...');

});

function revisaMontos($db, $p) {
    $p->montoconiva = $p->importetotal;
    $p->montoconivacnv = $p->importetotalcnv;
    $p->iva = $p->importeiva;
    if((float)$p->isrporretener > 0) {
        $p->isrporretener = $db->calculaISR($p->importeneto);
        $p->isrporretenercnv = round($p->isrporretener / (float)$p->tc, 2);
    }
    if((float)$p->ivaporretener > 0) {
        $p->ivaporretener = $db->calculaRetIVA((float)$p->importeneto, ((int)$p->idtipocliente == 1), (float)$p->importetotal, ((int)$p->idtipocliente == 2), (float)$p->importeiva, (float)$p->porcentajeretiva);
        $p->ivaporretenercnv = round($p->ivaporretener / (float)$p->tc, 2);
    }
    $p->totapagar = (float)$p->importetotal - ((float)$p->isrporretener + (float)$p->ivaporretener);
    $p->totapagarcnv = round($p->totapagar / (float)$p->tc, 2);
    return $p;
}

$app->post('/genfactfel', function() {
    $d = json_decode(file_get_contents('php://input'));
    $params = $d->params;
    $pendientes = $d->pendientes;
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();
    
    //print json_encode($pendientes);
    $query = "SELECT IFNULL(seriefel, 'A') AS seriefel, IFNULL(correlativofel, 0) AS correlativofel FROM empresa WHERE id = $params->idempresa";
    $datosFel = $db->getQuery($query)[0];
    $datosFel->correlativofel = (int)$datosFel->correlativofel;
    
    foreach($pendientes as $p) {

        $datosFel->correlativofel++;

        $p = revisaMontos($db, $p);
        $montoletras = (int)$p->idmonedafact == 1 ? $n2l->to_word($p->totapagar, 'GTQ') : $n2l->to_word($p->totapagarcnv, 'USD');        
        $p->nit = strtoupper(preg_replace("/[^a-zA-Z0-9]+/", "", $p->nit));        

        $query = "INSERT INTO factura(";
        $query.= "idempresa, idtipofactura, idcontrato, idcliente, ";
        $query.= "fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, iva, ";
        $query.= "total, subtotal, totalletras, idmoneda, tipocambio, ";
        $query.= "retisr, retiva, totdescuento, nit, nombre, direccion, idmonedafact,";
        $query.= "subtotalcnv, totalcnv, retivacnv, retisrcnv, totdescuentocnv,";
        $query.= "importebruto, importeneto, importeiva, importetotal, descuentosiniva, descuentoiva, ";
        $query.= "importebrutocnv, importenetocnv, importeivacnv, importetotalcnv, descuentosinivacnv, descuentoivacnv, ";
        $query.= "serieadmin, numeroadmin, porretiva, importeexento, importeexentocnv, exentoiva";
        $query.= ") VALUES (";
        $query.= "$params->idempresa, 1, $p->idcontrato, $p->idcliente, ";
        $query.= "NOW(), MONTH('$params->ffacturastr'), '$params->ffacturastr', 2, '". str_replace(',', ', ', strip_tags($p->tipo))."', $p->iva, ";
        $query.= "$p->totapagar, $p->montoconiva, '$montoletras', 1, $p->tc, ";
        $query.= "$p->isrporretener, $p->ivaporretener, $p->descuentoconiva, '$p->nit', '$p->facturara', '$p->direccion', $p->idmonedafact, ";
        $query.= "$p->montoconivacnv, $p->totapagarcnv, $p->ivaporretenercnv, $p->isrporretenercnv, $p->descuentoconivacnv, ";
        $query.= "$p->importebruto, $p->importeneto, $p->importeiva, $p->importetotal, $p->descuentosiniva, $p->descuentoiva, ";
        $query.= "$p->importebrutocnv, $p->importenetocnv, $p->importeivacnv, $p->importetotalcnv, $p->descuentosinivacnv, $p->descuentoivacnv, ";
        $query.= "'$datosFel->seriefel', $datosFel->correlativofel, $p->porcentajeretiva, $p->importeexento, $p->importeexentocnv, $p->exentoiva";
        $query.= ")";
        //print $query;
        //die();
        $lastid = 0;
        if((float)$p->montoconiva != 0){
            $db->doQuery($query);
            $lastid = $db->getLastId();
        }
        if((int)$lastid > 0) {
            $query = "UPDATE empresa SET correlativofel = $datosFel->correlativofel WHERE id = $params->idempresa";
            $db->doQuery($query);
            foreach($p->detalle as $det) {
                if($det->facturar == 1){
                    $conceptoAdicional = 'NULL';
                    if(isset($det->conceptoadicional)){
                        if(trim($det->conceptoadicional) !== ''){
                            $conceptoAdicional = "'".trim($det->conceptoadicional)."'";
                        }
                    }                   

                    $query = "INSERT INTO detfact(";
                    $query.= "idfactura, cantidad, descripcion, preciounitario, preciounitariocnv, preciotot, idtiposervicio, mes, anio, descuento, ";
                    $query.= "conceptoadicional, descuentocnv, ";
                    $query.= "importebruto, importeneto, importeiva, importetotal, descuentosiniva, descuentoiva, ";
                    $query.= "importebrutocnv, importenetocnv, importeivacnv, importetotalcnv, descuentosinivacnv, descuentoivacnv, porcentajedescuento, ";
                    $query.= "precio, preciocnv, importeexento, importeexentocnv";
                    $query.= ") VALUES(";
                    $query.= "$lastid, 1, '$det->tipo', $det->montoconiva, $det->importebrutocnv, $det->montoconiva, $det->idtipo, $det->mes, $det->anio, $det->descuentoconiva, ";
                    $query.= "$conceptoAdicional, $det->descuentoconivacnv, ";
                    $query.= "$det->importebruto, $det->importeneto, $det->importeiva, $det->importetotal, $det->descuentosiniva, $det->descuentoiva, ";
                    $query.= "$det->importebrutocnv, $det->importenetocnv, $det->importeivacnv, $det->importetotalcnv, $det->descuentosinivacnv, $det->descuentoivacnv, $det->porcentajedescuento,";
                    $query.= "$det->precio, $det->preciocnv, $det->importeexento, $det->importeexentocnv";
                    $query.= ")";
                    //print $query;
                    if((float)$det->montoconiva != 0){
                        $db->doQuery($query);
                        $lastidDetalle = $db->getLastId();
                        $descripcionLarga = getDescripcionLarga($lastid, $lastidDetalle);
                        $query = "UPDATE detfact SET descripcionlarga = '$descripcionLarga' WHERE id = $lastidDetalle";
                        $db->doQuery($query);
                    }
                    $query = "UPDATE cargo SET facturado = 1, idfactura = $lastid WHERE id = $det->idcargo";
                    $db->doQuery($query);
                }
            }
            $url = 'http://localhost/sayet/php/genpartidasventa.php/genpost';
            $data = ['ids' => $lastid, 'idcontrato' => 1];
            $db->CallJSReportAPI('POST', $url, json_encode($data));
        }
    }
});

function getDescripcionLarga($idfactura, $iddetallefactura) {
    $db = new dbcpm();
    $meses = [2 => 2, 3 => 5, 4 => 1];
    $periodo = '';
    $query = "SELECT a.idperiodicidad FROM contrato a INNER JOIN factura b ON a.id = b.idcontrato WHERE b.id = $idfactura LIMIT 1";
    //print $query;
    $periodicidad = (int)$db->getOneField($query);
    if($periodicidad > 1){
        $query = "SELECT a.cobro, ";
        $query.= "IF(a.cobro = 1, MONTH(DATE_SUB(b.fecha, INTERVAL 1 MONTH)), MONTH(b.fecha)) AS mesini, ";
        $query.= "IF(a.cobro = 1, MONTH(DATE_SUB(DATE_SUB(b.fecha, INTERVAL 1 MONTH), INTERVAL ".$meses[$periodicidad]." MONTH)), MONTH(DATE_ADD(b.fecha, INTERVAL ".$meses[$periodicidad]." MONTH))) AS mesfin, ";

        $query.= "IF(a.cobro = 1, YEAR(DATE_SUB(b.fecha, INTERVAL ".$meses[$periodicidad]." MONTH)), YEAR(b.fecha)) AS anioini, ";
        $query.= "IF(a.cobro = 1, YEAR(DATE_SUB(DATE_SUB(b.fecha, INTERVAL 1 MONTH), INTERVAL ".$meses[$periodicidad]." MONTH)), YEAR(DATE_ADD(b.fecha, INTERVAL ".$meses[$periodicidad]." MONTH))) AS aniofin ";
        $query.= "FROM contrato a INNER JOIN factura b ON a.id = b.idcontrato ";
        $query.= "WHERE b.id = $idfactura LIMIT 1";
        $rango = $db->getQuery($query)[0];

        switch (true){
            case (int)$rango->anioini === (int)$rango->aniofin:
                if((int)$rango->mesini < (int)$rango->mesfin){
                    $periodo = $db->nombreMes((int)$rango->mesini).' a '.$db->nombreMes((int)$rango->mesfin).' del año '.$rango->anioini;
                }else{
                    $periodo = $db->nombreMes((int)$rango->mesfin).' a '.$db->nombreMes((int)$rango->mesini).' del año '.$rango->anioini;
                }
                break;
            case (int)$rango->anioini < (int)$rango->aniofin:
                $periodo = $db->nombreMes((int)$rango->mesini).' del año '.$rango->anioini.' a '.$db->nombreMes((int)$rango->mesfin).' del año '.$rango->aniofin;
                break;
            case (int)$rango->anioini > (int)$rango->aniofin:
                $periodo = $db->nombreMes((int)$rango->mesfin).' del año '.$rango->aniofin.' a '.$db->nombreMes((int)$rango->mesini).' del año '.$rango->anioini;
                break;
        }
    }

    $query = "SELECT DISTINCT TRIM(CONCAT(IF(b.esinsertada = 0, IF(a.idtiposervicio <> 4, CONCAT(UPPER(TRIM(e.desctiposervventa)), ', ', TRIM(d.nomproyecto), ', ', 
    TRIM(UnidadesPorContrato(c.id)), ', Mes de ', ".($periodo == '' ? "f.nombre, ' del año ', a.anio" : ("'".$periodo."'"))."), TRIM(a.descripcion)), TRIM(a.descripcion)), ' ', 
    IFNULL(a.conceptoadicional, ''))) AS descripcion 
    FROM detfact a INNER JOIN factura b ON b.id = a.idfactura LEFT JOIN contrato c ON c.id = b.idcontrato LEFT JOIN proyecto d ON d.id = c.idproyecto 
    LEFT JOIN tiposervicioventa e ON e.id = a.idtiposervicio LEFT JOIN mes f ON f.id = a.mes 
    WHERE a.id = $iddetallefactura 
    UNION 
    SELECT DISTINCT TRIM(CONCAT(a.descripcion, ' ', IFNULL(a.conceptoadicional, ''))) AS descripcion
    FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes 
    WHERE b.idcliente = 0 AND a.id = $iddetallefactura";
    $descripcion = $db->getOneField($query);
    return $descripcion;
}

$app->post('/gengface', function() use($app){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->listafact)){ $d->listafact = ''; }
    $db = new dbcpm();

    $query = "SELECT CONCAT(LPAD(YEAR(a.fecha), 4, ' '), LPAD(MONTH(a.fecha), 4, ' '), LPAD(DAY(a.fecha), 4, ' ')) AS fecha, 'FACE' AS tipodoc, ";
    $query.= "TRIM(a.nit) AS nit, IF(a.idmonedafact = 1, '1', '2') AS codmoneda, a.id AS idfactura, 'S' AS tipoventa, ";
    $query.= "TRIM(a.nombre) AS nombre, ";
    $query.= "TRIM(a.direccion) AS direccion, b.nombrecorto, ";

    $query.= "CONCAT('$ ', FORMAT(IF(a.idmonedafact = 1, ROUND(a.subtotal / a.tipocambio, 2), a.subtotalcnv), 2)) AS montodol, ";
    $query.= "FORMAT(a.tipocambio, 4) AS tipocambio, ";
    $query.= "CONCAT('$ ', FORMAT(IF(a.idmonedafact = 1, ROUND(a.total / a.tipocambio, 2), a.totalcnv), 2)) AS pagonetodol, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = 1, a.total, a.totalcnv), 2)) AS pagoneto, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = 1, a.retiva, a.retivacnv), 2)) AS retiva, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = 1, a.retisr, a.retisrcnv), 2)) AS isr, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = a.idmoneda, a.subtotal, a.subtotalcnv), 2)) AS monto, a.id AS idfactura, 1 AS descargar, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechastr ";

    $query.= "FROM factura a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN moneda c ON c.id = a.idmonedafact ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.anulada = 0 AND (ISNULL(a.firmaelectronica) OR TRIM(a.firmaelectronica) = '') ";
    $query.= "AND a.id > 3680 AND a.total <> 0 ";
    $query.=  $d->listafact != '' ? "AND a.id IN($d->listafact) " : '';
    $query.= "UNION ";
    $query.= "SELECT CONCAT(LPAD(YEAR(a.fecha), 4, ' '), LPAD(MONTH(a.fecha), 4, ' '), LPAD(DAY(a.fecha), 4, ' ')) AS fecha, 'FACE' AS tipodoc, a.nit, IF(a.idmonedafact = 1, '1', '2') AS codmoneda, ";
    $query.= "a.id AS idfactura, 'S' AS tipoventa, a.nombre, IFNULL(a.direccion, '') AS direccion, '' AS nombrecorto, ";

    $query.= "CONCAT('$ ', FORMAT(IF(a.idmonedafact = 1, ROUND(a.subtotal / a.tipocambio, 2), a.subtotalcnv), 2)) AS montodol, ";
    $query.= "FORMAT(a.tipocambio, 4) AS tipocambio, ";
    $query.= "CONCAT('$ ', FORMAT(IF(a.idmonedafact = 1, ROUND(a.total / a.tipocambio, 2), a.totalcnv), 2)) AS pagonetodol, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = 1, a.total, a.totalcnv), 2)) AS pagoneto, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = 1, a.retiva, a.retivacnv), 2)) AS retiva, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = 1, a.retisr, a.retisrcnv), 2)) AS isr, ";
    $query.= "CONCAT(c.simbolo, ' ', FORMAT(IF(a.idmonedafact = 1, a.subtotal, a.subtotalcnv), 2)) AS monto, a.id AS idfactura, 1 AS descargar, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fechastr ";

    $query.= "FROM factura a INNER JOIN moneda c ON c.id = a.idmonedafact ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.idcliente = 0 AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.anulada = 0 AND (ISNULL(a.firmaelectronica) OR TRIM(a.firmaelectronica) = '') ";
    $query.= "AND a.id > 3680 AND a.total <> 0 ";
    $query.=  $d->listafact != '' ? "AND a.id IN($d->listafact) " : '';
    //print $query;
    $facturas = $db->getQuery($query);
    $cntFact = count($facturas);
    if($cntFact > 0){
        for($i = 0; $i < $cntFact; $i++){
            $factura = $facturas[$i];
            //Detalle de cada factura
            //iconv("UTF-8", "Windows-1252", $csv);

            //Para cuando la periodicidad es diferente a un mes
            $meses = [2 => 2, 3 => 5, 4 => 1];
            $periodo = '';
            $query = "SELECT a.idperiodicidad FROM contrato a INNER JOIN factura b ON a.id = b.idcontrato WHERE b.id = $factura->idfactura LIMIT 1";
            //print $query;
            $periodicidad = (int)$db->getOneField($query);
            if($periodicidad > 1){
                $query = "SELECT a.cobro, ";
                $query.= "IF(a.cobro = 1, MONTH(DATE_SUB(b.fecha, INTERVAL 1 MONTH)), MONTH(b.fecha)) AS mesini, ";
                $query.= "IF(a.cobro = 1, MONTH(DATE_SUB(DATE_SUB(b.fecha, INTERVAL 1 MONTH), INTERVAL ".$meses[$periodicidad]." MONTH)), MONTH(DATE_ADD(b.fecha, INTERVAL ".$meses[$periodicidad]." MONTH))) AS mesfin, ";

                $query.= "IF(a.cobro = 1, YEAR(DATE_SUB(b.fecha, INTERVAL ".$meses[$periodicidad]." MONTH)), YEAR(b.fecha)) AS anioini, ";
                $query.= "IF(a.cobro = 1, YEAR(DATE_SUB(DATE_SUB(b.fecha, INTERVAL 1 MONTH), INTERVAL ".$meses[$periodicidad]." MONTH)), YEAR(DATE_ADD(b.fecha, INTERVAL ".$meses[$periodicidad]." MONTH))) AS aniofin ";
                $query.= "FROM contrato a INNER JOIN factura b ON a.id = b.idcontrato ";
                $query.= "WHERE b.id = $factura->idfactura LIMIT 1";
                $rango = $db->getQuery($query)[0];

                switch (true){
                    case (int)$rango->anioini === (int)$rango->aniofin:
                        if((int)$rango->mesini < (int)$rango->mesfin){
                            $periodo = $db->nombreMes((int)$rango->mesini).' a '.$db->nombreMes((int)$rango->mesfin).' del año '.$rango->anioini;
                        }else{
                            $periodo = $db->nombreMes((int)$rango->mesfin).' a '.$db->nombreMes((int)$rango->mesini).' del año '.$rango->anioini;
                        }
                        break;
                    case (int)$rango->anioini < (int)$rango->aniofin:
                        $periodo = $db->nombreMes((int)$rango->mesini).' del año '.$rango->anioini.' a '.$db->nombreMes((int)$rango->mesfin).' del año '.$rango->aniofin;
                        break;
                    case (int)$rango->anioini > (int)$rango->aniofin:
                        $periodo = $db->nombreMes((int)$rango->mesfin).' del año '.$rango->aniofin.' a '.$db->nombreMes((int)$rango->mesini).' del año '.$rango->anioini;
                        break;
                }
            }
            $query = "SELECT DISTINCT ";

            $query.= "TRUNCATE(IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv), 2) AS montoconiva, ";
            $query.= "ROUND(IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv) / 1.12, 2)  AS montosiniva, ";
            $query.= "ROUND(IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv) - (IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv) / 1.12), 2) AS iva, ";
            $query.= "TRUNCATE(IF(b.idmonedafact = 1, a.preciounitario, a.preciounitariocnv) + IF(b.idmonedafact = 1, a.descuento, a.descuentocnv), 2) AS montounitario, ";

            $query.= "a.idtiposervicio, ";

            $query.= "TRIM(CONCAT(";
            $query.= "IF(b.esinsertada = 0, ";
            $query.= "IF(a.idtiposervicio <> 4, ";
            $query.= "CONCAT(UPPER(TRIM(e.desctiposervventa)), ', ', TRIM(d.nomproyecto), ', ', ";
            $query.= "TRIM(UnidadesPorContrato(c.id)), ', Mes de ', ".($periodo == '' ? "f.nombre, ' del año ', a.anio" : ("'".$periodo."'"))."), ";
            $query.= "TRIM(a.descripcion)), ";
            $query.= "TRIM(a.descripcion)), ' ', IFNULL(a.conceptoadicional, '')))  AS descripcion, ";

			$query.= "a.cantidad ";
            $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura LEFT JOIN contrato c ON c.id = b.idcontrato LEFT JOIN proyecto d ON d.id = c.idproyecto ";
            $query.= "LEFT JOIN tiposervicioventa e ON e.id = a.idtiposervicio LEFT JOIN mes f ON f.id = a.mes ";
            $query.= "WHERE a.idfactura = $factura->idfactura ";
            $query.= "UNION ";
            $query.= "SELECT DISTINCT ";

            $query.= "TRUNCATE(IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv), 2) AS montoconiva, ";
            $query.= "ROUND(IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv) / 1.12, 2)  AS montosiniva, ";
            $query.= "ROUND(IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv) - (IF(b.idmonedafact = 1, a.montoflatconiva, a.montoflatconivacnv) / 1.12), 2) AS iva, ";
            $query.= "TRUNCATE(IF(b.idmonedafact = 1, a.preciounitario, a.preciounitariocnv) + IF(b.idmonedafact = 1, a.descuento, a.descuentocnv), 2) AS montounitario, ";

            $query.= "a.idtiposervicio, TRIM(CONCAT(a.descripcion, ' ', IFNULL(a.conceptoadicional, ''))) AS descripcion, ";
			$query.= "a.cantidad ";
            $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes ";
            $query.= "WHERE b.idcliente = 0 AND a.idfactura = $factura->idfactura ";
            //print $query;
            $factura->detfact = $db->getQuery($query);
            $cntLineasDetalle = count($factura->detfact);
            //Linea de total de descuento por factura
            $query = "SELECT ";

            $query.= "TRUNCATE((IF(a.idmonedafact = a.idmoneda, a.totdescuento, a.totdescuentocnv) * -1), 2) AS totdescconiva, ";
            $query.= "ROUND((IF(a.idmonedafact = a.idmoneda, a.totdescuento, a.totdescuentocnv) / 1.12) * -1, 2) AS totdesc, ";
            $query.= "ROUND((IF(a.idmonedafact = a.idmoneda, a.totdescuento, a.totdescuentocnv) - (IF(a.idmonedafact = a.idmoneda, a.totdescuento, a.totdescuentocnv) / 1.12)) * -1, 2) AS ivadesc, ";

            $query.= "'DESCUENTO' AS descripcion, 1 AS cantidad ";
            $query.= "FROM factura a ";
            $query.= "WHERE a.id = $factura->idfactura";
            //print $query;
            $factura->descuento = $db->getQuery($query)[0];

            //Linea de totales por factura
            $totalConIva = 0.00; $totalSinIva = 0.00; $totalIva = 0.00;
            for($j = 0; $j < $cntLineasDetalle; $j++){
                if(array_key_exists($j, $factura->detfact)){
                    $det = $factura->detfact[$j];
                    $totalConIva += (float)$det->montoconiva;
                    $totalSinIva += (float)$det->montosiniva;
                    $totalIva += (float)$det->iva;
                }
            }

            if((float)$factura->descuento->totdescconiva != 0){
                $cntLineasDetalle++;
                $totalConIva += (float)$factura->descuento->totdescconiva;
                $totalSinIva += (float)$factura->descuento->totdesc;
                $totalIva += (float)$factura->descuento->ivadesc;
            }

            $factura->totales = ['totalconiva' => round($totalConIva, 2), 'totalsiniva' => round($totalSinIva, 2), 'iva' => round($totalIva, 2), 'lineasdet' => $cntLineasDetalle];
        }
    }

    //$app->response->headers->set('Content-Language', 'es');
    $app->response->headers->set('Content-Type', 'application/json;charset=windows-1252');
    print json_encode($facturas);

});

$app->post('/genfel', function() use($app) {
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->listafact)){ $d->listafact = ''; }
    $db = new dbcpm();
    //Encabezado
    $query = "SELECT 1 AS tiporegistro, DATE_FORMAT(a.fecha, '%Y%m%d') AS fechadocumento, b.siglasfel AS tipodocumento, a.nit AS nitcomprador, a.idmonedafact AS codigomoneda, 
    IF(a.idmonedafact = 1, 1, ROUND(a.tipocambio, 4)) AS tasacambio, a.id AS ordenexterno, 'S' AS tipoventa, 1 AS destinoventa, 'S' AS enviarcorreo, 
    IF(a.nit <> 'CF', '', IF(LENGTH(a.nombre) > 0, a.nombre, 'Consumidor final')) AS nombrecomprador, IF(LENGTH(a.direccion) > 0, a.direccion, 'Ciudad') AS direccion, 
    '' AS numeroacceso, IFNULL(a.serieadmin, 'A') AS serieadmin, a.numeroadmin, c.nombrecorto, FORMAT(a.importetotalcnv, 2) AS montodol, ROUND(a.tipocambio, 4) AS tipocambio, FORMAT(TRUNCATE(a.totalcnv, 2), 2) AS pagonetodol, 
    FORMAT(TRUNCATE(IF(a.idmonedafact = 1, a.total, a.totalcnv), 2), 2) AS pagoneto, FORMAT(TRUNCATE(IF(a.idmonedafact = 1, a.retiva, a.retivacnv), 2), 2) AS retiva, 
    FORMAT(TRUNCATE(IF(a.idmonedafact = 1, a.retisr, a.retisrcnv), 2), 2) AS retisr, FORMAT(IF(a.idmonedafact = 1, a.importetotal, a.importetotalcnv), 2) AS monto, 
    DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.nombre, d.simbolo AS monedafact, 1 AS descargar, a.idfacturaafecta
    FROM factura a
    INNER JOIN tipofactura b ON b.id = a.idtipofactura
    LEFT JOIN cliente c ON c.id = a.idcliente
    LEFT JOIN moneda d ON d.id = a.idmonedafact
    WHERE a.id > 3680 AND a.total <> 0 AND a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.anulada = 0 AND (ISNULL(a.firmaelectronica) OR TRIM(a.firmaelectronica) = '') ";
    $query.= $d->listafact != '' ? "AND a.id IN($d->listafact) " : '';
    // print $query;
    $facturas = $db->getQuery($query);
    $cntFacturas = count($facturas);
    for($i = 0; $i < $cntFacturas; $i++) {
        $factura = $facturas[$i];
        //Detalle
        $query = "SELECT 2 AS tiporegistro, a.cantidad, 1 AS unidadmedida, TRUNCATE(IF(b.idmonedafact = 1, a.precio, a.preciocnv), 2) AS precio, a.porcentajedescuento, 
        TRUNCATE(IF(b.idmonedafact = 1, a.descuento, a.descuentocnv), 2) AS importedescuento, IF(b.idmonedafact = 1, a.importebruto, a.importebrutocnv) AS importebruto,
        IF(b.idmonedafact = 1, a.importeexento, a.importeexentocnv) AS importeexento, IF(b.idmonedafact = 1, a.importeneto, a.importenetocnv) AS importeneto,
        IF(b.idmonedafact = 1, a.importeiva, a.importeivacnv) AS importeiva, 0 AS importeotros, 
        IF(b.idmonedafact = 1, a.importetotal, a.importetotalcnv) AS importetotal, a.idtiposervicio AS producto, TRIM(a.descripcionlarga) AS descripcion, 'S' AS tipoventa
        FROM detfact a INNER JOIN factura b ON b.id = a.idfactura
        WHERE a.idfactura = $factura->ordenexterno";
        // print $query;
        $factura->detalle = $db->getQuery($query);
        //Notas de crédito/débito. 22/09/2020.
        $cntDocumentosAsociados = 0;
        $factura->docasoc = [];
        if (in_array(trim($factura->tipodocumento), array('NCRE', 'NDEB'))) {
            $query = "SELECT 3 AS tiporegistro, 'FACT' AS tipodocumento, serie, numero, DATE_FORMAT(fecha, '%Y%m%d') AS fechadocumento FROM factura WHERE id = $factura->idfacturaafecta";
            $factura->docasoc = $db->getQuery($query);
            //$cntDocumentosAsociados = 1;
        }
        //Totales
        $query = "SELECT 4 AS tiporegistro, IF(a.idmonedafact = 1, a.importebruto, a.importebrutocnv) AS importebruto, TRUNCATE(IF(a.idmonedafact = 1, a.totdescuento, a.totdescuentocnv), 2) AS importedescuento, 
        IF(a.idmonedafact = 1, a.importeexento, a.importeexentocnv) AS importeexento, IF(a.idmonedafact = 1, a.importeneto, a.importenetocnv) AS importeneto,
        IF(a.idmonedafact = 1, a.importeiva, a.importeivacnv) AS importeiva, 0 AS importeotros, 
        IF(a.idmonedafact = 1, a.importetotal, a.importetotalcnv) AS importetotal, 0 AS porcentajeisr, 0 AS importeisr, 0 AS registrosdetalle, $cntDocumentosAsociados AS documentosasociados
        FROM factura a
        WHERE a.id = $factura->ordenexterno";
        $factura->totales = $db->getQuery($query)[0];
    }
    print json_encode($facturas);
});

$app->get('/gettxt/:idempresa/:fdelstr/:falstr/:nombre(/:listafact)', function($idempresa, $fdelstr, $falstr, $nombre, $listafact = '') use($app){
    $db = new dbcpm();
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.trim($nombre).'.txt"');

    //$url = 'http://104.197.209.57:5489/api/report';
    $url = 'http://localhost:5489/api/report';
    $data = ['template' => ['shortid' => 'SJ2xzSzKx'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr", 'listafact' => $listafact]];
    //print json_encode($data);

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    $respuesta = str_replace('&amp;', '&', $respuesta);
    print iconv('UTF-8','Windows-1252', $respuesta);
});

$app->post('/convencod', function() use($app) {
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d)) {
        $d = (object)$_REQUEST;
    }
    // var_dump($d);
    // die();
    $d->texto = str_replace('&amp;', '&', $d->texto);
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.trim($d->nombre).'.txt"');
    print iconv($d->de,$d->a, $d->texto);

});

$app->post('/respuesta', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $cntFacts = count($d);
    for($i = 0; $i < $cntFacts; $i++){
        if($d[$i]->id !== NULL){
            $factura = $d[$i];
            $query = "UPDATE factura SET firmaelectronica = '$factura->firma', respuestagface = '".str_replace("'", " ", $factura->respuesta)."', serie = '$factura->serie', numero = '$factura->numero', ";
            $query.= "nit = '$factura->nit', nombre = '".str_replace("'", " ", $factura->nombre)."', pendiente = 1 ";
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

$app->post('/prntfact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();

    $query = "SELECT a.id, TRIM(a.nombre) AS nombre, TRIM(a.nit) AS nit, IF(a.direccion = NULL, 'CIUDAD', TRIM(a.direccion)) AS direccion, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, ";
    $query.= "CONCAT(TRIM(a.serie), ' ', TRIM(a.numero)) AS numero, FORMAT(a.total, 2) AS pagoneto, FORMAT(a.retiva, 2) AS retiva, FORMAT(a.retisr, 2) AS retisr, ";
    $query.= "CONCAT('TC: ', FORMAT(a.tipocambio, 5)) AS tipocambio, ";
    $query.= "CONCAT('$ ', FORMAT(ROUND(a.subtotal / a.tipocambio, 2), 2)) AS pagonetodol, '' AS montoenletras, FORMAT(a.subtotal, 2) AS monto, TRUNCATE(a.subtotal, 2) AS total, ";
    $query.= "(SELECT nombrecorto FROM cliente WHERE id = a.idcliente) AS nombrecorto, FORMAT(a.subtotal, 2) AS totalresumen, ";
    $query.= "'Pago Neto:' AS lblpagoneto, 'Retención IVA:' AS lblretiva, 'Retención ISR:' AS lblretisr, 'Total:' AS lbltotal, c.impresora, c.pagewidth, c.pageheight, c.formato, c.papel ";
    $query.= "FROM factura a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipoimpresioncheque c ON c.formato = b.formatofactura ";
    $query.= "WHERE a.id IN($d->idfacturas) ORDER BY 6";
    $facturas = $db->getQuery($query);

    $cntFacturas = count($facturas);
    for($i = 0; $i < $cntFacturas; $i++){
        $factura = $facturas[$i];

        $campos = [];
        foreach($factura as $key => $value){ $campos[] = $key; }

        $factura->montoenletras = $n2l->to_word_int($factura->total);
        $query = "SELECT FORMAT(a.montoflatconiva, 2) AS montoconiva, ";

        $query.= "TRIM(CONCAT(";
        $query.= "IF(b.esinsertada = 0, ";
        $query.= "IF(a.idtiposervicio <> 4, ";
        $query.= "CONCAT(CONVERT(UPPER(TRIM(e.desctiposervventa)), CHAR CHARACTER SET latin1), ' DE ', CONVERT(TRIM(d.nomproyecto), CHAR CHARACTER SET latin1), ' ', ";
        $query.= "CONVERT(TRIM(UnidadesPorContrato(c.id)), CHAR CHARACTER SET latin1), ', Mes de ', f.nombre, ";
        $query.= "' del ".iconv("UTF-8", "Windows-1252", utf8_encode('año'))." ', FORMAT(a.anio, 0)), TRIM(a.descripcion)), ";
        $query.= "TRIM(a.descripcion)), ' ', IFNULL(a.conceptoadicional, '')))  AS descripcion ";

        $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN proyecto d ON d.id = c.idproyecto ";
        $query.= "INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes ";
        $query.= "WHERE a.idfactura = $factura->id ";
        $query.= "UNION ";
        $query.= "SELECT FORMAT(a.montoflatconiva, 2) AS montoconiva, TRIM(CONCAT(a.descripcion, ' ', IFNULL(a.conceptoadicional, ''))) AS descripcion ";
        $query.= "FROM detfact a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN tiposervicioventa e ON e.id = a.idtiposervicio INNER JOIN mes f ON f.id = a.mes ";
        $query.= "WHERE b.idcliente = 0 AND a.idfactura = $factura->id";
        $factura->detallefactura = $db->getQuery($query);

        $query = "SELECT FORMAT(a.totdescuento, 2) AS totdescconiva FROM factura a WHERE a.id = $factura->id";
        $descuento = $db->getQuery($query);

        if(count($descuento) > 0){
            if((float)$descuento[0]->totdescconiva != 0){
                $factura->detallefactura[] = (object)[
                    'montoconiva' => $descuento[0]->totdescconiva,
                    'descripcion' => 'Descuento'
                ];
            }        
        }


        $cntCampos = count($campos);
        for($j = 0; $j < $cntCampos; $j++){
            $campo = $campos[$j];
            $info = $db->getFieldInfo($factura->formato, $campo);
            if($info){
                $info->valor = $factura->{$campo};
                $factura->{$campo} = $info;
            }        
        }

        $camposdetfact = [];
        foreach($factura->detallefactura[0] as $key => $value){ $camposdetfact[] = $key; }
        $cntCamposDetFact = count($camposdetfact);
        $cntDetFact = count($factura->detallefactura);
        for($j = 0; $j < $cntDetFact; $j++){
            $ld = $factura->detallefactura[$j];
            for($k = 0; $k < $cntCamposDetFact; $k++){
                $campo = $camposdetfact[$k];
                $info = $db->getFieldInfo($factura->formato, $campo);
                if($info){
                    $info->valor = $ld->{$campo};
                    $ld->{$campo} = $info;
                }
            }
        }

    }

    print json_encode($facturas);
});

$app->run();