<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/resumen', function () use ($db) {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->idunidad)) {
        $d->idunidad = 0;
    }

    $datos = new stdClass();

    $query = "SELECT a.id, a.nomproyecto, a.referencia, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempresa, ";
    $query .= "(SELECT nombre FROM mes WHERE id = $d->mes) AS mes, $d->anio AS anio, DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha, 0.00 AS diferencia ";
    $query .= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query .= "WHERE a.id = $d->idproyecto";
    $datos->proyecto = $db->getQuery($query)[0];

    $query = "SELECT IF( '$d->anio-" . ((int) $d->mes < 10 ? ('0' . $d->mes) : $d->mes) . "-01' > '2017-08-31', 1, 0)";
    //print $query;
    $antesSept = (int) $db->getOneField($query) == 0;

    if ($antesSept) {
        //Ingresos
        $query = "SELECT DISTINCT TRIM(a.conceptomayor) AS concepto ";
        $query .= "FROM factura a INNER JOIN contrato b ON b.id = a.idcontrato ";
        $query .= "WHERE b.idproyecto = $d->idproyecto AND a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND a.idempresa = $d->idempresa ";
        $query .= "ORDER BY 1";
        $conceptos = $db->getQuery($query);
        $cntConceptos = count($conceptos);
        $totIngresos = 0.00;
        for ($i = 0; $i < $cntConceptos; $i++) {
            $query = "SELECT SUM(a.subtotal) AS monto ";
            $query .= "FROM factura a INNER JOIN contrato b ON b.id = a.idcontrato ";
            $query .= "WHERE b.idproyecto = $d->idproyecto AND a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND a.idempresa = $d->idempresa AND TRIM(a.conceptomayor) = '" . trim($conceptos[$i]->concepto) . "'";
            $montoIngreso = $db->getOneField($query);
            $datos->ingresos[] = ['concepto' => $conceptos[$i]->concepto, 'monto' => $montoIngreso];
            $totIngresos += (float) $montoIngreso;
        }
        $datos->ingresos[] = ['concepto' => 'TOTAL DE INGRESOS', 'monto' => $totIngresos];

        //Egresos
        $query = "SELECT DISTINCT a.idcuenta, c.nombrecta AS concepto ";
        $query .= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta INNER JOIN detpagocompra d ON b.id = d.idcompra ";
        $query .= "WHERE a.origen = 2 AND (c.codigo LIKE '5%' OR TRIM(c.codigo) = '1120299') AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio ";
        $query .= "AND b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa ";
        $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
        $query .= "ORDER BY 2";
        $conceptos = $db->getQuery($query);
        $cntConceptos = count($conceptos);
        $totEgresos = 0.00;
        for ($i = 0; $i < $cntConceptos; $i++) {
            $query = "SELECT SUM(a.debe) ";
            $query .= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta INNER JOIN detpagocompra d ON b.id = d.idcompra ";
            $query .= "WHERE a.origen = 2 AND (c.codigo LIKE '5%' OR TRIM(c.codigo) = '1120299') AND b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND ";
            $query .= "MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND a.idcuenta = " . $conceptos[$i]->idcuenta . " ";
            $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
            $montoEgreso = $db->getOneField($query);
            $datos->egresos[] = ['concepto' => $conceptos[$i]->concepto, 'monto' => $montoEgreso];
            $totEgresos += (float) $montoEgreso;
        }

        //Egresos de planilla
        $query = "SELECT a.monto FROM plaproy a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = b.idempresa WHERE a.mes = $d->mes AND a.anio = $d->anio AND a.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa";
        $montoPlanilla = (float) $db->getOneField($query);
        $datos->egresos[] = ['concepto' => 'PLANILLA', 'monto' => $montoPlanilla];
        $totEgresos += $montoPlanilla;

        $datos->egresos[] = ['concepto' => 'TOTAL DE EGRESOS', 'monto' => $totEgresos];

        $datos->proyecto->diferencia = (float) $totIngresos - (float) $totEgresos;
    } else {
        //Ingresos
        $query = "SELECT DISTINCT b.idtiposervicio, d.desctiposervventa AS concepto ";
        $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato ";
        $query .= "INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio ";
        $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND c.idproyecto = $d->idproyecto AND b.idtiposervicio NOT IN(1) AND a.idempresa = $d->idempresa ";
        $query .= "ORDER BY 1";
        $conceptos = $db->getQuery($query);
        $cntConceptos = count($conceptos);
        $totIngresos = 0.00;
        for ($i = 0; $i < $cntConceptos; $i++) {
            $query = "SELECT ROUND(SUM((b.preciotot * IF(a.idtipofactura <> 9, 1, -1)) / 1.12), 2) ";
            $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio ";
            $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND c.idproyecto = $d->idproyecto AND a.idempresa = $d->idempresa AND b.idtiposervicio = " . $conceptos[$i]->idtiposervicio;
            $montoIngreso = $db->getOneField($query);
            $datos->ingresos[] = ['concepto' => $conceptos[$i]->concepto, 'monto' => $montoIngreso];
            $totIngresos += (float) $montoIngreso;
        }
        $datos->ingresos[] = ['concepto' => 'TOTAL DE INGRESOS', 'monto' => $totIngresos];

        //Egresos
        $query = "SELECT DISTINCT a.idcuentac AS idcuenta, c.nombrecta AS concepto
                FROM compraproyecto a
                INNER JOIN compra b ON b.id = a.idcompra
                INNER JOIN cuentac c ON c.id = a.idcuentac
                WHERE b.idproyecto = $d->idproyecto AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND b.idempresa = $d->idempresa AND 
                b.idreembolso = 0 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') ";
        $query .= (int) $d->idunidad == 0 ? '' : "AND a.idunidad = $d->idunidad ";
        $query .= "UNION ";
        $query .= "SELECT DISTINCT a.idcuenta, c.nombrecta AS concepto
                FROM detallecontable a
                INNER JOIN compra b ON b.id = a.idorigen
                INNER JOIN cuentac c ON c.id = a.idcuenta
                WHERE a.origen = 2 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND
                b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND idreembolso > 0 ";
        $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
        $query .= "ORDER BY 2";
        $conceptos = $db->getQuery($query);
        $cntConceptos = count($conceptos);
        $totEgresos = 0.00;
        for ($i = 0; $i < $cntConceptos; $i++) {
            $concepto = $conceptos[$i];
            $query = "SELECT SUM(z.monto) ";
            $query .= "FROM (";
            $query .= "SELECT a.idcuentac AS idcuenta, a.monto
                    FROM compraproyecto a
                    INNER JOIN compra b ON b.id = a.idcompra
                    INNER JOIN cuentac c ON c.id = a.idcuentac
                    WHERE b.idproyecto = $d->idproyecto AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND b.idempresa = $d->idempresa AND 
                    b.idreembolso = 0 AND a.idcuentac = $concepto->idcuenta AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') ";
            $query .= (int) $d->idunidad == 0 ? '' : "AND a.idunidad = $d->idunidad ";
            $query .= "UNION ";
            $query .= "SELECT a.idcuenta, a.debe AS monto
                    FROM detallecontable a
                    INNER JOIN compra b ON b.id = a.idorigen
                    INNER JOIN cuentac c ON c.id = a.idcuenta
                    WHERE a.origen = 2 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND
                    b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND idreembolso > 0 AND a.idcuenta = $concepto->idcuenta ";
            $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
            $query .= ") z";
            $montoEgreso = $db->getOneField($query);
            $datos->egresos[] = ['concepto' => $conceptos[$i]->concepto, 'monto' => $montoEgreso];
            $totEgresos += (float) $montoEgreso;
        }

        //Egresos de planilla
        $query = "SELECT IF( '$d->anio-" . ((int) $d->mes < 10 ? ('0' . $d->mes) : $d->mes) . "-01' > '2018-03-31', 1, 0)";
        $antesAbrPla = (int) $db->getOneField($query) == 0;
        if ($antesAbrPla) {
            $query = "SELECT a.monto FROM plaproy a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = b.idempresa WHERE a.mes = $d->mes AND a.anio = $d->anio AND a.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa";
            $montoPlanilla = (float) $db->getOneField($query);
            $datos->egresos[] = ['concepto' => 'PLANILLA', 'monto' => $montoPlanilla];
            $totEgresos += $montoPlanilla;
        } else {
            $query = "SELECT ";
            $query .= "(SUM(a.descigss) + SUM(a.descisr) + ROUND(SUM((a.sueldoordinario + a.sueldoextra + a.vacaciones) * 0.1267), 2) + SUM(a.descanticipo + a.liquido + a.descprestamo)) AS totplanilla ";
            $query .= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado ";
            $query .= "WHERE a.esbonocatorce <> 1 AND a.fecha > '$d->anio-$d->mes-15' AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND b.idproyecto = $d->idproyecto";
            $datosPlanilla = $db->getQuery($query);
            if (count($datosPlanilla) > 0) {
                $pln = $datosPlanilla[0];
                $datos->egresos[] = [
                    'concepto' => 'PLANILLA',
                    'monto' => (float) $pln->totplanilla
                ];
                $totEgresos += (float) $pln->totplanilla;
            }
        }
        //Fin de Egresos de planilla
        //Inicia depreciaciones
        $depre = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $d->mes, $d->anio));
        $cntDepre = count($depre);
        $totDepre = 0.00;
        if ($cntDepre > 0) {
            $totDepre = (float) $depre[0]->debe;
        }
        $datos->egresos[] = [
            'concepto' => 'DEPRECIACIONES',
            'monto' => $totDepre,
        ];
        $totEgresos += $totDepre;
        //Finaliza depreciaciones

        //Inicia gastos por partida directa
        $ogpd = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $d->mes, $d->anio, true, false));
        $cntOgpd = count($ogpd);
        $totOgpd = 0.00;
        if ($cntOgpd > 0) {
            $totOgpd = (float) $ogpd[0]->debe;
        }
        $datos->egresos[] = [
            'concepto' => 'OTROS GASTOS',
            'monto' => $totOgpd,
        ];
        $totEgresos += $totOgpd;        
        //Finaliza gastos por partida directa

        $datos->egresos[] = ['concepto' => 'TOTAL DE EGRESOS', 'monto' => $totEgresos];

        $datos->proyecto->diferencia = (float) $totIngresos - (float) $totEgresos;
    }

    print json_encode($datos);
});

$app->post('/detalle', function () use ($db) {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->idunidad)) {
        $d->idunidad = 0;
    }

    $datos = new stdClass();

    $query = "SELECT a.id, a.nomproyecto, a.referencia, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempresa, ";
    $query .= "(SELECT nombre FROM mes WHERE id = $d->mes) AS mes, $d->anio AS anio, DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha, 0.00 AS diferencia ";
    $query .= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query .= "WHERE a.id = $d->idproyecto";
    $datos->proyecto = $db->getQuery($query)[0];

    //Ingresos con detalle
    $query = "SELECT DISTINCT b.idtiposervicio, d.desctiposervventa AS concepto ";
    $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato ";
    $query .= "INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio ";
    $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND c.idproyecto = $d->idproyecto AND b.idtiposervicio NOT IN(1) AND a.idempresa = $d->idempresa ";
    $query .= "ORDER BY 1";
    $conceptos = $db->getQuery($query);
    $cntConceptos = count($conceptos);
    $totIngresos = 0.00;
    for ($i = 0; $i < $cntConceptos; $i++) {
        $query = "SELECT ROUND(SUM((b.preciotot * IF(a.idtipofactura <> 9, 1, -1)) / 1.12), 2) ";
        $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio ";
        $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND c.idproyecto = $d->idproyecto AND a.idempresa = $d->idempresa AND b.idtiposervicio = " . $conceptos[$i]->idtiposervicio;
        $montoIngreso = $db->getOneField($query);

        $query = "SELECT a.id AS idfactura, e.nombre AS cliente, e.nombrecorto AS abreviacliente, c.nocontrato, UnidadesPorContrato(c.id) AS unidadescontrato, ";
        $query.= "TRIM(CONCAT(a.serie, IFNULL(CONCAT(' (', a.serieadmin, ')'), ''), ' - ')) AS serie, TRIM(CONCAT(a.numero, IF(a.numeroadmin > 0, CONCAT(' (', a.numeroadmin, ')'), ''))) AS numero, ";        
        // $query.= "FORMAT(TRUNCATE((b.preciotot * IF(a.idtipofactura <> 9, 1, -1)) / 1.12, 2), 2) AS totalneto, ";
        $query.= "@totalneto := FORMAT(TRUNCATE((b.preciotot * IF(a.idtipofactura <> 9, 1, -1)) / 1.12, 2), 2) AS totalnetocalc, IF(a.idtipofactura <> 9, @totalneto, CONCAT( REPLACE(@totalneto, '-', '('), ')' )) AS totalneto, ";
        $query .= "FORMAT(MCuadPorContrato(c.id), 4) AS mcuadcontrato, FORMAT(IF(MCuadPorContrato(c.id) > 0, ((b.preciotot / 1.12) / MCuadPorContrato(c.id)), 0.00), 2) AS montomcuad ";
        $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio INNER JOIN cliente e ON e.id = a.idcliente ";
        $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND c.idproyecto = $d->idproyecto AND a.idempresa = $d->idempresa AND b.idtiposervicio = " . $conceptos[$i]->idtiposervicio . " ";
        $query .= "ORDER BY e.nombre";
        $deting = $db->getQuery($query);
        if (count($deting) > 0) {
            $query = "SELECT FORMAT(SUM(MCuadPorContrato(c.id)), 4) ";
            $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio INNER JOIN cliente e ON e.id = a.idcliente ";
            $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND c.idproyecto = $d->idproyecto AND a.idempresa = $d->idempresa AND b.idtiposervicio = " . $conceptos[$i]->idtiposervicio . " ";
            $suma = $db->getOneField($query);
            $deting[] = [
                'idfactura' => '', 'cliente' => '', 'abreviacliente' => '', 'nocontrato' => '', 'unidadescontrato' => 'Total:',
                'serie' => '', 'numero' => 'Total:', 'totalneto' => number_format($montoIngreso, 2), 'mcuadcontrato' => $suma, 'montomcuad' => ''
            ];
        }

        $datos->ingresos[] = ['concepto' => $conceptos[$i]->concepto, 'monto' => $montoIngreso, 'detalle' => $deting];
        $totIngresos += (float) $montoIngreso;
    }
    // Locales vacíos
    $query = "SELECT '' AS idfactura, '' AS cliente, '' AS abreviacliente, '' AS nocontrato, a.nombre AS unidadescontrato, '' AS serie, '' AS numero, '' AS totalneto, FORMAT(a.mcuad, 4) AS mcuadcontrato, '' AS montomcuad ";
    $query .= "FROM unidad a WHERE a.id NOT IN(";
    $query .= "SELECT unidad.id FROM unidad, contrato WHERE unidad.idproyecto = $d->idproyecto AND contrato.inactivo = 0 AND contrato.idempresa = $d->idempresa AND FIND_IN_SET(unidad.id, contrato.idunidad)";
    $query .= ") AND a.idproyecto = $d->idproyecto ";
    $query .= "ORDER BY a.nombre";
    $vacios = $db->getQuery($query);
    if (count($vacios) > 0) {
        $query = "SELECT FORMAT(SUM(a.mcuad), 4)  ";
        $query .= "FROM unidad a WHERE a.id NOT IN(";
        $query .= "SELECT unidad.id FROM unidad, contrato WHERE unidad.idproyecto = $d->idproyecto AND contrato.inactivo = 0 AND contrato.idempresa = $d->idempresa AND FIND_IN_SET(unidad.id, contrato.idunidad)";
        $query .= ") AND a.idproyecto = $d->idproyecto ";
        $query .= "ORDER BY a.nombre";
        $suma = $db->getOneField($query);
        $vacios[] = [
            'idfactura' => '', 'cliente' => '', 'abreviacliente' => '', 'nocontrato' => '', 'unidadescontrato' => 'Total:',
            'serie' => '', 'numero' => '', 'totalneto' => '', 'mcuadcontrato' => $suma, 'montomcuad' => ''
        ];
        $datos->ingresos[] = ['concepto' => 'LOCALES VACÍOS', 'monto' => '', 'detalle' => $vacios];
    }

    $datos->ingresos[] = ['concepto' => 'TOTAL DE INGRESOS', 'monto' => $totIngresos, 'detalle' => []];

    //Egresos con detalle
    //Agregar cuenta 1120299
    $query = "SELECT DISTINCT a.idcuentac AS idcuenta, c.nombrecta AS concepto
                FROM compraproyecto a
                INNER JOIN compra b ON b.id = a.idcompra
                INNER JOIN cuentac c ON c.id = a.idcuentac
                WHERE a.idproyecto = $d->idproyecto AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND b.idempresa = $d->idempresa AND 
                b.idreembolso = 0 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') ";
    $query .= (int) $d->idunidad == 0 ? '' : "AND a.idunidad = $d->idunidad ";
    $query .= "UNION ";
    $query .= "SELECT DISTINCT a.idcuenta, c.nombrecta AS concepto
                FROM detallecontable a
                INNER JOIN compra b ON b.id = a.idorigen
                INNER JOIN cuentac c ON c.id = a.idcuenta
                WHERE a.origen = 2 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND
                b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND idreembolso > 0 ";
    $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
    $query .= "ORDER BY 2";
    $conceptos = $db->getQuery($query);
    $cntConceptos = count($conceptos);
    $totEgresos = 0.00;
    for ($i = 0; $i < $cntConceptos; $i++) {
        $concepto = $conceptos[$i];
        $query = "SELECT SUM(z.monto) ";
        $query .= "FROM (";
        $query .= "SELECT a.idcuentac AS idcuenta, a.monto
                    FROM compraproyecto a
                    INNER JOIN compra b ON b.id = a.idcompra
                    INNER JOIN cuentac c ON c.id = a.idcuentac
                    WHERE a.idproyecto = $d->idproyecto AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND b.idempresa = $d->idempresa AND 
                    b.idreembolso = 0 AND a.idcuentac = $concepto->idcuenta AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') ";
        $query .= (int) $d->idunidad == 0 ? '' : "AND a.idunidad = $d->idunidad ";
        $query .= "UNION ALL ";
        $query .= "SELECT a.idcuenta, a.debe AS monto
                    FROM detallecontable a
                    INNER JOIN compra b ON b.id = a.idorigen
                    INNER JOIN cuentac c ON c.id = a.idcuenta
                    WHERE a.origen = 2 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND
                    b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND idreembolso > 0 AND a.idcuenta = $concepto->idcuenta ";
        $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
        $query .= ") z";
        //print $query;
        $montoEgreso = $db->getOneField($query);

        $query = "SELECT e.fecha AS fechaOrd, e.id AS idtranban, e.tipotrans, e.numero, DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fecha, e.beneficiario, e.concepto, g.simbolo AS moneda, e.monto AS montotranban, ";
        $query .= "b.id AS idcompra, IF(h.id IS NULL, b.proveedor, h.nombre) AS proveedor, IF(h.id IS NULL, b.nit, h.nit) AS nit, b.serie, b.documento, i.simbolo AS monedafact, a.monto AS montofact, ";
        $query .= "IFNULL(CONCAT(k.idpresupuesto, '-', k.correlativo), '') AS ot, DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafactura ";
        $query .= "FROM compraproyecto a INNER JOIN compra b ON b.id = a.idcompra INNER JOIN cuentac c ON c.id = a.idcuentac LEFT JOIN detpagocompra d ON b.id = d.idcompra LEFT JOIN tranban e ON e.id = d.idtranban ";
        $query .= "LEFT JOIN banco f ON f.id = e.idbanco LEFT JOIN moneda g ON g.id = f.idmoneda LEFT JOIN moneda i ON i.id = b.idmoneda LEFT JOIN proveedor h ON h.id = b.idproveedor ";
        $query .= "LEFT JOIN detpagopresup j ON j.id = e.iddetpagopresup LEFT JOIN detpresupuesto k ON k.id = e.iddetpresup ";
        $query .= "WHERE a.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND a.idcuentac = $concepto->idcuenta AND b.idreembolso = 0 ";
        $query .= (int) $d->idunidad == 0 ? '' : "AND a.idunidad = $d->idunidad ";
        $query .= "UNION ALL ";
        $query .= "SELECT e.fecha AS fechaOrd, e.id AS idtranban, e.tipotrans, e.numero, DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fecha, e.beneficiario, e.concepto, g.simbolo AS moneda, e.monto AS montotranban, ";
        $query .= "b.id AS idcompra, IF(h.id IS NULL, b.proveedor, h.nombre) AS proveedor, IF(h.id IS NULL, b.nit, h.nit) AS nit, b.serie, b.documento, i.simbolo AS monedafact, a.debe AS montofact, ";
        $query .= "IFNULL(CONCAT(k.idpresupuesto, '-', k.correlativo), '') AS ot, DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafactura ";
        $query .= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta INNER JOIN reembolso d ON d.id = b.idreembolso LEFT JOIN tranban e ON e.id = d.idtranban ";
        $query .= "LEFT JOIN banco f ON f.id = e.idbanco LEFT JOIN moneda g ON g.id = f.idmoneda LEFT JOIN moneda i ON i.id = b.idmoneda LEFT JOIN proveedor h ON h.id = b.idproveedor ";
        $query .= "LEFT JOIN detpagopresup j ON j.id = e.iddetpagopresup LEFT JOIN detpresupuesto k ON k.id = e.iddetpresup ";
        $query .= "WHERE a.origen = 2 AND b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND MONTH(b.fechafactura) = $d->mes AND YEAR(b.fechafactura) = $d->anio AND a.idcuenta = $concepto->idcuenta AND b.idreembolso > 0 ";
        $query .= "AND b.idsubtipogasto NOT IN(1) ";
        $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
        $query .= "ORDER BY 1, 6";
        //print $query;
        $detegr = $db->getQuery($query);
        if (count($detegr) > 0) {
            $querySum = "SELECT SUM(z.montofact) ";
            $querySum .= "FROM ($query) z";
            $sumaegr = $db->getOneField($querySum);
            $detegr[] = [
                'fechaOrd' => '', 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => '', 'beneficiario' => '', 'concepto' => '', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => $sumaegr, 'ot' => '', 'fechafactura' => 'Total:'
            ];
        }

        $datos->egresos[] = ['concepto' => $conceptos[$i]->concepto, 'monto' => $montoEgreso, 'detalle' => $detegr];
        $totEgresos += (float) $montoEgreso;
    }


    //Egresos de planilla
    $query = "SELECT IF( '$d->anio-" . ((int) $d->mes < 10 ? ('0' . $d->mes) : $d->mes) . "-01' > '2018-03-31', 1, 0)";
    $antesAbrPla = (int) $db->getOneField($query) == 0;
    if ($antesAbrPla) {
        $query = "SELECT a.monto FROM plaproy a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = b.idempresa WHERE a.mes = $d->mes AND a.anio = $d->anio AND a.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa";
        $montoPlanilla = (float) $db->getOneField($query);
        $datos->egresos[] = [
            'concepto' => 'PLANILLA', 'monto' => $montoPlanilla,
            'detalle' => [
                [
                    'fechaOrd' => '', 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => '', 'beneficiario' => '', 'concepto' => '', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => $montoPlanilla, 'ot' => '', 'fechafactura' => 'Total:'
                ]
            ]
        ];
        $totEgresos += $montoPlanilla;
    } else {
        $query = "SELECT b.idproyecto, SUM(a.descigss) AS descigss, SUM(a.descisr) AS descisr, ROUND(SUM((a.sueldoordinario + a.sueldoextra + a.vacaciones) * 0.1267), 2) AS cuotapatronal, SUM(a.descanticipo + a.liquido + a.descprestamo) AS liquido, ";
        $query .= "DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.fecha AS fechaOrd, ";
        $query .= "(SUM(a.descigss) + SUM(a.descisr) + ROUND(SUM((a.sueldoordinario + a.sueldoextra + a.vacaciones) * 0.1267), 2) + SUM(a.descanticipo + a.liquido + a.descprestamo)) AS totplanilla, SUM(a.devengado) AS devengado ";
        $query .= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado ";
        $query .= "WHERE a.esbonocatorce <> 1 AND a.fecha > '$d->anio-$d->mes-15' AND MONTH(a.fecha) = $d->mes AND YEAR(a.fecha) = $d->anio AND b.idproyecto = $d->idproyecto";
        $datosPlanilla = $db->getQuery($query);
        if (count($datosPlanilla) > 0) {
            $pln = $datosPlanilla[0];
            $detPln = [
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'I.G.S.S.', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->descigss, 'ot' => '', 'fechafactura' => ''
                ],
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'I.S.R.', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->descisr, 'ot' => '', 'fechafactura' => ''
                ],
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'Cuota Patronal', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->cuotapatronal, 'ot' => '', 'fechafactura' => ''
                ],
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'Devengado', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->liquido, 'ot' => '', 'fechafactura' => ''
                ],
                [
                    'fechaOrd' => '', 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => '', 'beneficiario' => '',
                    'concepto' => '', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->totplanilla, 'ot' => '', 'fechafactura' => 'Total:'
                ]
            ];
            $datos->egresos[] = [
                'concepto' => 'PLANILLA',
                'monto' => (float) $pln->totplanilla,
                'detalle' => $detPln
            ];
            $totEgresos += (float) $pln->totplanilla;
        }
    }
    //Fin de Egresos de planilla

    //Inicia depreciaciones
    $depre = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $d->mes, $d->anio));
    $cntDepre = count($depre);
    //print "DEPRE = $cntDepre";
    $detDepre = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $d->mes, $d->anio, false));
    $cntDetDepre = count($detDepre);
    //var_dump($detDepre);
    $detalle = [];
    $totDepre = 0.00;
    if ($cntDepre > 0) {
        $totDepre = (float) $depre[0]->debe;
        if ($totDepre > 0) {
            for ($i = 0; $i < $cntDetDepre; $i++) {
                $det = $detDepre[$i];
                $detalle[] = [
                    'fechaOrd' => $det->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $det->fecha, 'beneficiario' => '',
                    'concepto' => $det->cuenta, 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $det->debe, 'ot' => '', 'fechafactura' => ''
                ];
            }
        }
    }
    $datos->egresos[] = [
        'concepto' => 'DEPRECIACIONES',
        'monto' => $totDepre,
        'detalle' => $detalle
    ];
    $totEgresos += $totDepre;
    //Finaliza depreciaciones

    //Inicia gastos por partida Directa
    $depre = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $d->mes, $d->anio, true, false));
    $cntDepre = count($depre);
    //print "DEPRE = $cntDepre";
    $detDepre = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $d->mes, $d->anio, false, false));
    $cntDetDepre = count($detDepre);
    //var_dump($detDepre);
    $detalle = [];
    $totDepre = 0.00;
    if ($cntDepre > 0) {
        $totDepre = (float) $depre[0]->debe;
        if ($totDepre > 0) {
            for ($i = 0; $i < $cntDetDepre; $i++) {
                $det = $detDepre[$i];
                $detalle[] = [
                    'fechaOrd' => $det->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $det->fecha, 'beneficiario' => '',
                    'concepto' => $det->cuenta, 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $det->debe, 'ot' => '', 'fechafactura' => ''
                ];
            }
        }
    }
    $datos->egresos[] = [
        'concepto' => 'OTROS GASTOS',
        'monto' => $totDepre,
        'detalle' => $detalle
    ];
    $totEgresos += $totDepre;
    //Finaliza gastos por partida directa

    usort($datos->egresos, function ($a, $b) {
        if ((float) $a['monto'] === (float) $b['monto']) {
            return 0;
        }
        return (float) $a['monto'] > (float) $b['monto'] ? -1 : 1;
    });

    $datos->egresos[] = ['concepto' => 'TOTAL DE EGRESOS', 'monto' => $totEgresos, 'detalle' => []];

    $datos->proyecto->diferencia = (float) $totIngresos - (float) $totEgresos;

    print json_encode($datos);
});

function getQueryDepreciaciones($idempresa, $idproyecto, $mes, $anio, $sinDetalle = true, $soloDepre = true)
{
    $query = "SELECT c.nombrecta AS cuenta, SUM(b.debe) AS debe, a.fecha AS fechaOrd, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha ";
    $query .= "FROM directa a INNER JOIN detallecontable b ON a.id = b.idorigen INNER JOIN cuentac c ON c.id = b.idcuenta ";
    $query .= "WHERE a.idempresa = $idempresa AND MONTH(a.fecha) = $mes AND YEAR(a.fecha) = $anio AND a.idproyecto = $idproyecto AND b.origen = 4 ";
    $query .= $soloDepre ? "AND c.codigo like '51206%' " : "AND c.codigo NOT LIKE '51206%' AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%') ";
    $query .= $sinDetalle ? '' : 'GROUP BY c.id';
    //print $query;
    return $query;
}

function getDepreciaciones($db, $d, $mes, $anio, $solosuma = true, $soloDepre = true)
{
    $depre = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $mes, $anio, true, $soloDepre));
    $cntDepre = count($depre);
    $detDepre = $db->getQuery(getQueryDepreciaciones($d->idempresa, $d->idproyecto, $mes, $anio, false, $soloDepre));
    $cntDetDepre = count($detDepre);
    $detalle = [];
    $totDepre = 0.00;
    if ($cntDepre > 0) {
        $totDepre = (float) $depre[0]->debe;
        if ($totDepre > 0) {
            for ($i = 0; $i < $cntDetDepre; $i++) {
                $det = $detDepre[$i];
                $detalle[] = [
                    'fechaOrd' => $det->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $det->fecha, 'beneficiario' => '',
                    'concepto' => $det->cuenta, 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $det->debe, 'ot' => '', 'fechafactura' => ''
                ];
            }
        }
    }

    if ($solosuma) {
        return $totDepre;
    } else {
        return $detalle;
    }
}

function getIngresosPorConcepto($db, $d, $mes, $anio, $idtiposervicio, $solosuma = true)
{
    $montoIngreso = 0.00;
    $query = "SELECT a.id AS idfactura, e.nombre AS cliente, e.nombrecorto AS abreviacliente, c.nocontrato, UnidadesPorContrato(c.id) AS unidadescontrato, a.serie, a.numero, FORMAT(TRUNCATE(b.preciotot / 1.12, 2), 2) AS totalneto, ";
    $query .= "FORMAT(MCuadPorContrato(c.id), 4) AS mcuadcontrato, FORMAT(IF(MCuadPorContrato(c.id) > 0, ((b.preciotot / 1.12) / MCuadPorContrato(c.id)), 0.00), 2) AS montomcuad, TRUNCATE(b.preciotot / 1.12, 2) AS preciotot ";
    $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio INNER JOIN cliente e ON e.id = a.idcliente ";
    $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $mes AND YEAR(a.fecha) = $anio AND c.idproyecto = $d->idproyecto AND a.idempresa = $d->idempresa AND b.idtiposervicio = $idtiposervicio ";
    $query .= "ORDER BY e.nombre";
    $deting = $db->getQuery($query);
    $cntDetIng = count($deting);
    if ($cntDetIng > 0) {
        for ($i = 0; $i < $cntDetIng; $i++) {
            $montoIngreso += (float) $deting[$i]->preciotot;
        }

        $query = "SELECT FORMAT(SUM(MCuadPorContrato(c.id)), 4) ";
        $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio INNER JOIN cliente e ON e.id = a.idcliente ";
        $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) = $mes AND YEAR(a.fecha) = $anio AND c.idproyecto = $d->idproyecto AND a.idempresa = $d->idempresa AND b.idtiposervicio = $idtiposervicio ";
        $suma = $db->getOneField($query);
        $deting[] = [
            'idfactura' => '', 'cliente' => '', 'abreviacliente' => '', 'nocontrato' => '', 'unidadescontrato' => 'Total:',
            'serie' => '', 'numero' => 'Total:', 'totalneto' => number_format($montoIngreso, 2), 'mcuadcontrato' => $suma, 'montomcuad' => ''
        ];
    }

    if ($solosuma) {
        return round($montoIngreso, 2);
    } else {
        return $deting;
    }
}

function getLocalesVacios($db, $d)
{

    $query = "SELECT '' AS idfactura, '' AS cliente, '' AS abreviacliente, '' AS nocontrato, a.nombre AS unidadescontrato, '' AS serie, '' AS numero, '' AS totalneto, FORMAT(a.mcuad, 4) AS mcuadcontrato, '' AS montomcuad ";
    $query .= "FROM unidad a WHERE a.id NOT IN(";
    $query .= "SELECT unidad.id FROM unidad, contrato WHERE unidad.idproyecto = $d->idproyecto AND contrato.inactivo = 0 AND contrato.idempresa = $d->idempresa AND FIND_IN_SET(unidad.id, contrato.idunidad)";
    $query .= ") AND a.idproyecto = $d->idproyecto ";
    $query .= "ORDER BY a.nombre";
    $vacios = $db->getQuery($query);
    if (count($vacios) > 0) {
        $query = "SELECT FORMAT(SUM(a.mcuad), 4)  ";
        $query .= "FROM unidad a WHERE a.id NOT IN(";
        $query .= "SELECT unidad.id FROM unidad, contrato WHERE unidad.idproyecto = $d->idproyecto AND contrato.inactivo = 0 AND contrato.idempresa = $d->idempresa AND FIND_IN_SET(unidad.id, contrato.idunidad)";
        $query .= ") AND a.idproyecto = $d->idproyecto ";
        $query .= "ORDER BY a.nombre";
        $suma = $db->getOneField($query);
        $vacios[] = [
            'idfactura' => '', 'cliente' => '', 'abreviacliente' => '', 'nocontrato' => '', 'unidadescontrato' => 'Total:',
            'serie' => '', 'numero' => '', 'totalneto' => '', 'mcuadcontrato' => $suma, 'montomcuad' => ''
        ];
    }
    return $vacios;
};

function getEgresosPorConcepto($db, $d, $mes, $anio, $idcuenta, $solosuma = true)
{
    $montoEgreso = 0.00;
    $query = "SELECT e.fecha AS fechaOrd, e.id AS idtranban, e.tipotrans, e.numero, DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fecha, e.beneficiario, e.concepto, g.simbolo AS moneda, e.monto AS montotranban, ";
    $query .= "b.id AS idcompra, IF(h.id IS NULL, b.proveedor, h.nombre) AS proveedor, IF(h.id IS NULL, b.nit, h.nit) AS nit, b.serie, b.documento, i.simbolo AS monedafact, a.monto AS montofact, ";
    $query .= "IFNULL(CONCAT(k.idpresupuesto, '-', k.correlativo), '') AS ot, DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafactura ";
    $query .= "FROM compraproyecto a INNER JOIN compra b ON b.id = a.idcompra INNER JOIN cuentac c ON c.id = a.idcuentac LEFT JOIN detpagocompra d ON b.id = d.idcompra LEFT JOIN tranban e ON e.id = d.idtranban ";
    $query .= "LEFT JOIN banco f ON f.id = e.idbanco LEFT JOIN moneda g ON g.id = f.idmoneda LEFT JOIN moneda i ON i.id = b.idmoneda LEFT JOIN proveedor h ON h.id = b.idproveedor ";
    $query .= "LEFT JOIN detpagopresup j ON j.id = e.iddetpagopresup LEFT JOIN detpresupuesto k ON k.id = j.iddetpresup ";
    $query .= "WHERE a.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND MONTH(b.fechafactura) = $mes AND YEAR(b.fechafactura) = $anio AND a.idcuentac = $idcuenta AND b.idreembolso = 0 ";
    $query .= (int) $d->idunidad == 0 ? '' : "AND a.idunidad = $d->idunidad ";
    $query .= "UNION ALL ";
    $query .= "SELECT e.fecha AS fechaOrd, e.id AS idtranban, e.tipotrans, e.numero, DATE_FORMAT(e.fecha, '%d/%m/%Y') AS fecha, e.beneficiario, e.concepto, g.simbolo AS moneda, e.monto AS montotranban, ";
    $query .= "b.id AS idcompra, IF(h.id IS NULL, b.proveedor, h.nombre) AS proveedor, IF(h.id IS NULL, b.nit, h.nit) AS nit, b.serie, b.documento, i.simbolo AS monedafact, a.debe AS montofact, ";
    $query .= "IFNULL(CONCAT(k.idpresupuesto, '-', k.correlativo), '') AS ot, DATE_FORMAT(b.fechafactura, '%d/%m/%Y') AS fechafactura ";
    $query .= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta INNER JOIN reembolso d ON d.id = b.idreembolso LEFT JOIN tranban e ON e.id = d.idtranban ";
    $query .= "LEFT JOIN banco f ON f.id = e.idbanco LEFT JOIN moneda g ON g.id = f.idmoneda LEFT JOIN moneda i ON i.id = b.idmoneda LEFT JOIN proveedor h ON h.id = b.idproveedor ";
    $query .= "LEFT JOIN detpagopresup j ON j.id = e.iddetpagopresup LEFT JOIN detpresupuesto k ON k.id = j.iddetpresup ";
    $query .= "WHERE a.origen = 2 AND b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND MONTH(b.fechafactura) = $mes AND YEAR(b.fechafactura) = $anio AND a.idcuenta = $idcuenta AND b.idreembolso > 0 ";
    $query .= "AND b.idsubtipogasto NOT IN(1) ";
    $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
    $query .= "ORDER BY 1, 6";
    $detegr = $db->getQuery($query);
    if (count($detegr) > 0) {
        $querySum = "SELECT SUM(z.montofact) ";
        $querySum .= "FROM ($query) z";
        $sumaegr = round((float) $db->getOneField($querySum), 2);
        $montoEgreso = $sumaegr;
        $detegr[] = [
            'fechaOrd' => '', 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => '', 'beneficiario' => '', 'concepto' => '', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
            'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => $sumaegr, 'ot' => '', 'fechafactura' => 'Total:'
        ];
    }

    if ($solosuma) {
        return $montoEgreso;
    } else {
        return $detegr;
    }
}

function getEgresosPlanilla($db, $d, $mes, $anio, $solosuma = true)
{
    $datos = new stdClass();
    $query = "SELECT IF( '$anio-" . ((int) $mes < 10 ? ('0' . $mes) : $mes) . "-01' > '2018-03-31', 1, 0)";
    $antesAbrPla = (int) $db->getOneField($query) == 0;
    if ($antesAbrPla) {
        $query = "SELECT a.monto FROM plaproy a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = b.idempresa ";
        $query .= "WHERE a.mes = $mes AND a.anio = $anio AND a.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa";
        $montoPlanilla = (float) $db->getOneField($query);
        if ($solosuma) {
            return round($montoPlanilla, 2);
        } else {
            return [
                [
                    'fechaOrd' => '', 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => '', 'beneficiario' => '', 'concepto' => '', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => round($montoPlanilla, 2), 'ot' => '', 'fechafactura' => 'Total:'
                ]
            ];
        }
    } else {
        $query = "SELECT b.idproyecto, SUM(a.bonocatorce) AS bonocatorce, SUM(a.aguinaldo) AS aguinaldo ";
        $query .= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado ";
        $query .= "WHERE a.fecha >= '$anio-$mes-15' AND MONTH(a.fecha) = $mes AND YEAR(a.fecha) = $anio AND b.idproyecto = $d->idproyecto";
        $datosPlanillaEspecial = $db->getQuery($query);

        $query = "SELECT b.idproyecto, SUM(a.descigss) AS descigss, SUM(a.descisr) AS descisr, ROUND(SUM((a.sueldoordinario + a.sueldoextra + a.vacaciones) * 0.1267), 2) AS cuotapatronal, SUM(a.descanticipo + a.liquido + a.descprestamo) AS liquido, ";
        $query .= "DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.fecha AS fechaOrd, ";
        $query .= "(SUM(a.descigss) + SUM(a.descisr) + ROUND(SUM((a.sueldoordinario + a.sueldoextra + a.vacaciones) * 0.1267), 2) + SUM(a.descanticipo + a.liquido + a.descprestamo)) AS totplanilla, SUM(a.devengado) AS devengado, ";
        $query .= "SUM(a.bonocatorce) AS bonocatorce, SUM(a.aguinaldo) AS aguinaldo ";
        $query .= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado ";
        $query .= "WHERE a.esbonocatorce <> 1 AND a.fecha > '$anio-$mes-15' AND MONTH(a.fecha) = $mes AND YEAR(a.fecha) = $anio AND b.idproyecto = $d->idproyecto";
        $datosPlanilla = $db->getQuery($query);
        if (count($datosPlanilla) > 0 || count($datosPlanillaEspecial) > 0) {
            $pln = $datosPlanilla[0];
            $totPlanilla = (float) $pln->totplanilla;
            $detPln = [
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'I.G.S.S.', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->descigss, 'ot' => '', 'fechafactura' => ''
                ],
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'I.S.R.', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->descisr, 'ot' => '', 'fechafactura' => ''
                ],
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'Cuota Patronal', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->cuotapatronal, 'ot' => '', 'fechafactura' => ''
                ],
                [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'Devengado', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $pln->liquido, 'ot' => '', 'fechafactura' => ''
                ]
            ];

            if(count($datosPlanillaEspecial) > 0) {
                $plnEsp = $datosPlanillaEspecial[0];
                $detPln[] = [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'Bono 14', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $plnEsp->bonocatorce, 'ot' => '', 'fechafactura' => ''
                ];

                $totPlanilla += (float) $plnEsp->bonocatorce;

                $detPln[] = [
                    'fechaOrd' => $pln->fechaOrd, 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => $pln->fecha, 'beneficiario' => '',
                    'concepto' => 'Aguinaldo', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                    'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => (float) $plnEsp->aguinaldo, 'ot' => '', 'fechafactura' => ''
                ];

                $totPlanilla += (float) $plnEsp->aguinaldo;
            }

            $detPln[] = [
                'fechaOrd' => '', 'idtranban' => '', 'tipotrans' => '', 'numero' => '', 'fecha' => '', 'beneficiario' => '',
                'concepto' => '', 'moneda' => '', 'montotranban' => '', 'idcompra' => '', 'proveedor' => '', 'nit' => '',
                'serie' => '', 'documento' => '', 'monedafact' => '', 'montofact' => $totPlanilla, 'ot' => '', 'fechafactura' => 'Total:'
            ];

            if ($solosuma) {
                return round((float) $totPlanilla, 2);
            } else {
                return $detPln;
            }
        } else {
            if ($solosuma) {
                return 0.00;
            } else {
                return [];
            }
        }
    }
}

$app->post('/ingegr', function () use ($db) {
    $d = json_decode(file_get_contents('php://input'));

    if (!isset($d->idempresa)) {
        $d->idempresa = 6;
    }
    if (!isset($d->idproyecto)) {
        $d->idproyecto = 55;
        //$d->idproyecto = 12;
    }
    if (!isset($d->idunidad)) {
        $d->idunidad = 0;
    }
    if (!isset($d->dmes)) {
        $d->dmes = 6;
    }
    if (!isset($d->ames)) {
        $d->ames = 8;
    }
    if (!isset($d->anio)) {
        $d->anio = 2019;
    }
    if (!isset($d->detallado)) {
        $d->detallado = 1;
    }
    $detallado = (int) $d->detallado === 1;

    $losMeses = $db->getQuery("SELECT nombre FROM mes ORDER BY id");

    $datos = new stdClass();

    $query = "SELECT a.id, a.nomproyecto, a.referencia, a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempresa, ";
    $query .= "(SELECT nombre FROM mes WHERE id = $d->dmes) AS dmes, (SELECT nombre FROM mes WHERE id = $d->ames) AS ames, $d->anio AS anio, ";
    $query .= "DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha, 0.00 AS diferencia ";
    $query .= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa ";
    $query .= "WHERE a.id = $d->idproyecto";
    $datos->proyecto = $db->getQuery($query)[0];

    //Ingresos
    $query = "SELECT DISTINCT b.idtiposervicio, d.desctiposervventa AS concepto, 0.00 AS total ";
    $query .= "FROM factura a INNER JOIN detfact b ON a.id = b.idfactura INNER JOIN contrato c ON c.id = a.idcontrato INNER JOIN tiposervicioventa d ON d.id = b.idtiposervicio ";
    $query .= "WHERE a.anulada = 0 AND MONTH(a.fecha) >= $d->dmes AND MONTH(a.fecha) <= $d->ames AND YEAR(a.fecha) = $d->anio AND c.idproyecto = $d->idproyecto AND ";
    $query .= "b.idtiposervicio NOT IN(1) AND a.idempresa = $d->idempresa ";
    $query .= "ORDER BY 1";
    $datos->ingresos = $db->getQuery($query);
    $cntConceptoIngresos = count($datos->ingresos);
    for ($i = 0; $i < $cntConceptoIngresos; $i++) {
        $conceptoIngreso = $datos->ingresos[$i];
        for ($mes = (int) $d->dmes; $mes <= (int) $d->ames; $mes++) {
            $suma = getIngresosPorConcepto($db, $d, $mes, $d->anio, $conceptoIngreso->idtiposervicio);
            $conceptoIngreso->total += $suma;
            $conceptoIngreso->meses[] = [
                'idmes' => $mes,
                'mes' => $losMeses[$mes - 1]->nombre,
                'total' => $suma,
                'detalle' => $detallado ? getIngresosPorConcepto($db, $d, $mes, $d->anio, $conceptoIngreso->idtiposervicio, false) : []
            ];
        }
    }

    //Locales vacíos    
    if ($detallado) {
        $vacios = getLocalesVacios($db, $d);
        if (count($vacios) > 0) {
            $datos->ingresos[] = [
                'idtiposervicio' => 0,
                'concepto' => 'LOCALES VACÍOS',
                'total' => '',
                'meses' => [
                    'idmes' => 0,
                    'mes' => '',
                    'total' => '',
                    'detalle' => $vacios
                ]
            ];
        }
    }

    //Egresos
    $query = "SELECT DISTINCT a.idcuentac AS idcuenta, c.nombrecta AS concepto, 0.00 AS total
                FROM compraproyecto a
                INNER JOIN compra b ON b.id = a.idcompra
                INNER JOIN cuentac c ON c.id = a.idcuentac
                WHERE a.idproyecto = $d->idproyecto AND MONTH(b.fechafactura) >= $d->dmes AND MONTH(b.fechafactura) <= $d->ames  AND YEAR(b.fechafactura) = $d->anio 
                AND b.idempresa = $d->idempresa AND b.idreembolso = 0 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') ";
    $query .= (int) $d->idunidad == 0 ? '' : "AND a.idunidad = $d->idunidad ";
    $query .= "UNION ";
    $query .= "SELECT DISTINCT a.idcuenta, c.nombrecta AS concepto, 0.00 AS total
                FROM detallecontable a
                INNER JOIN compra b ON b.id = a.idorigen
                INNER JOIN cuentac c ON c.id = a.idcuenta
                WHERE a.origen = 2 AND (c.codigo LIKE '5%' OR c.codigo LIKE '6%' OR TRIM(c.codigo) = '1120299') 
                AND MONTH(b.fechafactura) >= $d->dmes AND MONTH(b.fechafactura) <= $d->ames AND YEAR(b.fechafactura) = $d->anio AND
                b.idproyecto = $d->idproyecto AND b.idempresa = $d->idempresa AND idreembolso > 0 ";
    $query .= (int) $d->idunidad == 0 ? '' : "AND b.idunidad = $d->idunidad ";
    $query .= "ORDER BY 2";
    $datos->egresos = $db->getQuery($query);
    $cntConceptoEgresos = count($datos->egresos);
    for ($i = 0; $i < $cntConceptoEgresos; $i++) {
        $conceptoEgreso = $datos->egresos[$i];
        for ($mes = (int) $d->dmes; $mes <= (int) $d->ames; $mes++) {
            $suma = getEgresosPorConcepto($db, $d, $mes, $d->anio, $conceptoEgreso->idcuenta);
            $conceptoEgreso->total += $suma;
            $conceptoEgreso->meses[] = [
                'idmes' => $mes,
                'mes' => $losMeses[$mes - 1]->nombre,
                'total' => $suma,
                'detalle' => $detallado ? getEgresosPorConcepto($db, $d, $mes, $d->anio, $conceptoEgreso->idcuenta, false) : []
            ];
        }
    }

    //Egresos de planilla
    $datos->egresos[] = (object) [
        'idcuenta' => 0,
        'concepto' => 'PLANILLA',
        'total' => 0.00
    ];
    $conceptoPlanilla = $datos->egresos[count($datos->egresos) - 1];
    for ($mes = (int) $d->dmes; $mes <= (int) $d->ames; $mes++) {
        $suma = getEgresosPlanilla($db, $d, $mes, $d->anio);
        $conceptoPlanilla->total += $suma;
        $conceptoPlanilla->meses[] = [
            'idmes' => $mes,
            'mes' => $losMeses[$mes - 1]->nombre,
            'total' => $suma,
            'detalle' => $detallado ? getEgresosPlanilla($db, $d, $mes, $d->anio, false) : []
        ];
    }

    //Depreciaciones
    $datos->egresos[] = (object) [
        'idcuenta' => 0,
        'concepto' => 'Depreciaciones',
        'total' => 0.00
    ];
    $conceptoDepre = $datos->egresos[count($datos->egresos) - 1];
    for ($mes = (int) $d->dmes; $mes <= (int) $d->ames; $mes++) {
        $suma = getDepreciaciones($db, $d, $mes, $d->anio);
        $conceptoDepre->total += $suma;
        $conceptoDepre->meses[] = [
            'idmes' => $mes,
            'mes' => $losMeses[$mes - 1]->nombre,
            'total' => $suma,
            'detalle' => $detallado ? getDepreciaciones($db, $d, $mes, $d->anio, false) : []
        ];
    }
    if ($conceptoDepre->total == 0) {
        array_pop($datos->egresos);
    }

    //Otros gastos (partidas directas)
    $datos->egresos[] = (object) [
        'idcuenta' => 0,
        'concepto' => 'Otros gastos',
        'total' => 0.00
    ];
    $conceptoOtros = $datos->egresos[count($datos->egresos) - 1];
    for ($mes = (int) $d->dmes; $mes <= (int) $d->ames; $mes++) {
        $suma = getDepreciaciones($db, $d, $mes, $d->anio, true, false);
        $conceptoOtros->total += $suma;
        $conceptoOtros->meses[] = [
            'idmes' => $mes,
            'mes' => $losMeses[$mes - 1]->nombre,
            'total' => $suma,
            'detalle' => $detallado ? getDepreciaciones($db, $d, $mes, $d->anio, false, false) : []
        ];
    }
    if ($conceptoOtros->total == 0) {
        array_pop($datos->egresos);
    }


    print json_encode($datos);
});

$app->run();
