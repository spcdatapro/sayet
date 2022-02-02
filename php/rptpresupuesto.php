<?php
set_time_limit(0);
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function getQueryPresupuesto($idpresupuesto){
    $queryPresupuesto = "SELECT a.id, a.fechasolicitud, a.idempresa, b.nomempresa AS empresa, a.idproyecto, c.nomproyecto AS proyecto, a.idtipogasto, d.desctipogast AS tipogasto, 'Q' AS moneda, ";
    $queryPresupuesto.= "(SELECT SUM(monto * tipocambio) FROM detpresupuesto WHERE idpresupuesto = a.id) AS totalpresupuesto, ";
    $queryPresupuesto.= "(SELECT SUM((z.monto * y.tipocambio) - IFNULL(x.retorno, 0)) FROM detpagopresup z INNER JOIN detpresupuesto y ON y.id = z.iddetpresup ";
    $queryPresupuesto.= "LEFT JOIN (SELECT iddetpagopresup, SUM((monto * tipocambio)) AS retorno FROM tranban WHERE tipotrans = 'R' AND iddetpagopresup <> 0 GROUP BY iddetpagopresup) x ON z.id = x.iddetpagopresup ";
    $queryPresupuesto.= "WHERE y.idpresupuesto = a.id) AS totalpagosprogramados, ";
    $queryPresupuesto.= "(SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio) * IF(z.tipotrans IN('C', 'B'), 1, -1)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda ";
    $queryPresupuesto.= "WHERE z.iddetpresup IN(SELECT id FROM detpresupuesto WHERE idpresupuesto = a.id) AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULADO%') AS montoavance, ";
    $queryPresupuesto.= "IFNULL(getMontoISROT(a.id, 1), 0.00) AS isrpresupuesto, ";
    $queryPresupuesto.= "a.notas, e.iniciales AS usrcrea, a.fechacreacion, f.iniciales AS usraprueba, a.fhaprobacion, g.iniciales AS usrmodifica, a.fechamodificacion, IF(a.tipo = 1, 'SIMPLE', 'MULTIPLE') AS tipo ";
    $queryPresupuesto.= "FROM presupuesto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN proyecto c ON c.id = a.idproyecto INNER JOIN tipogasto d ON d.id = a.idtipogasto LEFT JOIN usuario e ON e.id = a.idusuario ";
    $queryPresupuesto.= "LEFT JOIN usuario f ON f.id = a.idusuarioaprueba LEFT JOIN usuario g ON g.id = a.lastuser ";
    $queryPresupuesto.= "WHERE a.id = $idpresupuesto";
    return $queryPresupuesto;
}

function getQueryOTs($id, $espresupuesto = true){
    $queryOTs = "
        SELECT a.id, a.correlativo, a.idproveedor, TRIM(b.nombre) AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.tipocambio, 'Q' AS moneda, IF(d.eslocal = 1, a.monto, a.monto * a.tipocambio) AS monto,
        IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, 
        (SELECT SUM((z.monto * y.tipocambio) - IFNULL(x.retorno, 0)) FROM detpagopresup z 
         INNER JOIN detpresupuesto y ON y.id = z.iddetpresup
         LEFT JOIN (SELECT iddetpagopresup, SUM((monto * tipocambio)) AS retorno FROM tranban WHERE (tipotrans = 'R' OR (tipotrans IN('C', 'B') AND (beneficiario LIKE '%ANULA%' OR concepto LIKE '%ANULA%'))) AND iddetpagopresup <> 0 
         GROUP BY iddetpagopresup) x ON z.id = x.iddetpagopresup 
         WHERE y.id = a.id) AS pagosprogramados,
        (SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio) * IF(z.tipotrans IN('C', 'B'), 1, -1)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda 
        WHERE z.iddetpresup = a.id AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULAD%'
        ) AS montoavance, a.notas, IFNULL(getMontoISROT(a.id, 0), 0.00) AS isr, e.desctipogast AS tipogasto, g.nomempresa AS empresa, DATE_FORMAT(f.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, h.nomproyecto AS proyecto, a.idpresupuesto,
        a.monto AS montoot, d.simbolo AS monedaot, IF(d.eslocal = 1, NULL, 1) imprimemontoot
        FROM detpresupuesto a 
        INNER JOIN proveedor b ON b.id = a.idproveedor
        INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto
        INNER JOIN moneda d ON d.id = a.idmoneda
        LEFT JOIN tipogasto e ON e.id = c.idtipogasto
        LEFT JOIN presupuesto f ON f.id = a.idpresupuesto
        LEFT JOIN empresa g ON g.id = f.idempresa
        LEFT JOIN proyecto h ON h.id = f.idproyecto
        WHERE a.origenprov = 1 AND ";
    $queryOTs.= $espresupuesto ? "a.idpresupuesto = $id " : "a.id = $id ";
    $queryOTs.= "UNION ALL ";
    $queryOTs.= "
        SELECT a.id, a.correlativo, a.idproveedor, TRIM(b.nombre) AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.tipocambio, 'Q' AS moneda, IF(d.eslocal = 1, a.monto, a.monto * a.tipocambio) AS monto,
        IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, 
        (SELECT SUM((z.monto * y.tipocambio) - IFNULL(x.retorno, 0)) FROM detpagopresup z 
         INNER JOIN detpresupuesto y ON y.id = z.iddetpresup
         LEFT JOIN (SELECT iddetpagopresup, SUM((monto * tipocambio)) AS retorno FROM tranban WHERE (tipotrans = 'R' OR (tipotrans IN('C', 'B') AND (beneficiario LIKE '%ANULA%' OR concepto LIKE '%ANULA%'))) AND iddetpagopresup <> 0 
         GROUP BY iddetpagopresup) x ON z.id = x.iddetpagopresup 
         WHERE y.id = a.id) AS pagosprogramados,
        (SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio) * IF(z.tipotrans IN('C', 'B'), 1, -1)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda 
        WHERE z.iddetpresup = a.id AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULAD%'
        ) AS montoavance, a.notas, IFNULL(getMontoISROT(a.id, 0), 0.00) AS isr, e.desctipogast AS tipogasto, g.nomempresa AS empresa, DATE_FORMAT(f.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, h.nomproyecto AS proyecto, a.idpresupuesto,
        a.monto AS montoot, d.simbolo AS monedaot, IF(d.eslocal = 1, NULL, 1) imprimemontoot
        FROM detpresupuesto a 
        INNER JOIN beneficiario b ON b.id = a.idproveedor
        INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto
        INNER JOIN moneda d ON d.id = a.idmoneda
        LEFT JOIN tipogasto e ON e.id = c.idtipogasto
        LEFT JOIN presupuesto f ON f.id = a.idpresupuesto
        LEFT JOIN empresa g ON g.id = f.idempresa
        LEFT JOIN proyecto h ON h.id = f.idproyecto
        WHERE a.origenprov = 2 AND ";
    $queryOTs.= $espresupuesto ? "a.idpresupuesto = $id " : "a.id = $id ";
    return $queryOTs;
}

function queryDocsOt($filtro, $todos = true, $esIdTranBan = true){
    $query = "SELECT ".($todos ? "idtranban, GROUP_CONCAT(DISTINCT documento SEPARATOR ', ') AS documento, SUM(totfact) AS totfact, monedafactura, " : "");
    // 14/01/2020: se quita la conversión a Quetzales.
    $query.= "SUM(isr) AS isr
            FROM(
            SELECT z.idtranban, GROUP_CONCAT(CONCAT(y.serie, y.documento) SEPARATOR ', ') AS documento, 
            SUM(y.totfact) AS totfact, 
            SUM(y.isr) AS isr, x.simbolo AS monedafactura
            FROM detpagocompra z
            INNER JOIN compra y ON y.id = z.idcompra
            INNER JOIN moneda x ON x.id = y.idmoneda
            INNER JOIN tranban w ON w.id = z.idtranban
            WHERE ".($esIdTranBan ? "z.idtranban = $filtro " : "w.iddetpresup = $filtro ")." 
            UNION
            SELECT z.idtranban, GROUP_CONCAT(CONCAT(y.serie, y.documento) SEPARATOR ', ') AS documento, 
            SUM(y.totfact) AS totfact, 
            SUM(y.isr) AS isr, x.simbolo AS monedafactura 
            FROM doctotranban z
            INNER JOIN compra y ON y.id = z.iddocto
            INNER JOIN moneda x ON x.id = y.idmoneda
            INNER JOIN tranban w ON w.id = z.idtranban
            WHERE ".($esIdTranBan ? "z.idtranban = $filtro " : "w.iddetpresup = $filtro ")." AND z.idtipodoc = 1 AND 
            z.iddocto NOT IN(SELECT idcompra FROM detpagocompra WHERE idtranban IN(".($esIdTranBan ? $filtro : "SELECT id FROM tranban WHERE iddetpresup = $filtro")."))
            UNION
            SELECT z.idtranban, GROUP_CONCAT(DISTINCT CONCAT(IF(y.idtiporeembolso = 1, 'REE', 'CC'), y.id) SEPARATOR ', ') AS documento, 
            SUM(x.totfact) AS totfact, 
            SUM(x.isr) AS isr, w.simbolo AS monedafactura
            FROM doctotranban z
            INNER JOIN reembolso y ON y.id = z.iddocto
            INNER JOIN compra x ON y.id = x.idreembolso
            INNER JOIN moneda w ON w.id = x.idmoneda
            INNER JOIN tranban v ON v.id = z.idtranban
            WHERE ".($esIdTranBan ? "z.idtranban = $filtro " : "v.iddetpresup = $filtro ")." AND z.idtipodoc = 2
            ) v WHERE idtranban IS NOT NULL"
    ;
    return $query;
}

function getDocumentosOT($db, $idot){
    $query = "SELECT a.fecha AS fechaOrd, a.id, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, b.siglas, a.tipotrans, a.numero, a.beneficiario, IF(a.tipocambio = 1, '', FORMAT(a.tipocambio, 2)) AS tipocambio, ";
    $query.= "'Q' AS moneda, IF(c.eslocal = 1, a.monto, a.monto * a.tipocambio) * IF(a.tipotrans IN('C', 'B'), 1, -1) AS monto, NULL AS documento, NULL AS totfact, NULL AS isr, a.concepto, ";
    $query.= "IF(a.concepto LIKE '%anulad%' OR a.beneficiario LIKE '%anulad%', 1, NULL) AS anulado, a.iddetpagopresup, d.nopago, f.simbolo AS monedapagodetpresup, ";
    $query.= "FORMAT(d.monto, 2) AS montopago, IFNULL(g.simbolo, c.simbolo) AS monedapago, NULL AS monedafactura ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN detpagopresup d ON d.id = a.iddetpagopresup INNER JOIN detpresupuesto e ON e.id = d.iddetpresup ";
    $query.= "INNER JOIN moneda f ON f.id = e.idmoneda LEFT JOIN moneda g ON g.id = d.idmoneda ";
    $query.= "WHERE a.tipotrans IN('C', 'B', 'R') AND a.iddetpresup = $idot ";
    $query.= "ORDER BY 1, a.numero";
    $documentos = $db->getQuery($query);
    $cntDocs = count($documentos);
    for($i = 0; $i < $cntDocs; $i++){
        $doc = $documentos[$i];
        $datos = $db->getQuery(queryDocsOt($doc->id));
        if(count($datos) > 0){
            $doc->documento = $datos[0]->documento;
            $doc->totfact = $datos[0]->totfact;
            $doc->isr = $datos[0]->isr;
            $doc->monedafactura = $datos[0]->monedafactura;
        }
    }
    return $documentos;
}

$app->post('/rptpresupuesto', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $suma = new stdClass();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy";
    $generales = $db->getQuery($query)[0];

    $qGenPres = getQueryPresupuesto($d->idpresupuesto);
    //print $qGenPres;

    $query = "SELECT id, tipo, DATE_FORMAT(fechasolicitud, '%d/%m/%Y') AS fechasolicitud, empresa, proyecto, tipogasto, moneda, FORMAT(totalpresupuesto, 2) AS totalpresupuesto, ";
    $query.= "FORMAT(totalpagosprogramados, 2) AS totalpagosprogramados, FORMAT(IFNULL(montoavance, 0.00) + isrpresupuesto, 2) AS montoavance, notas, usrcrea, DATE_FORMAT(fechacreacion, '%d/%m/%Y') AS fechacreacion, ";
    $query.= "usraprueba, DATE_FORMAT(fhaprobacion, '%d/%m/%Y') AS fhaprobacion, usrmodifica, DATE_FORMAT(fechamodificacion, '%d/%m/%Y') AS fechamodificacion, ";
    $query.= "FORMAT(IF(totalpresupuesto > totalpagosprogramados, totalpresupuesto, totalpagosprogramados), 2) AS totalreal, ";
    $query.= "CONCAT(FORMAT(IF(totalpresupuesto > totalpagosprogramados, (IFNULL(montoavance, 0.00) + isrpresupuesto) * 100 / totalpresupuesto, (IFNULL(montoavance, 0.00) + isrpresupuesto) * 100 / totalpagosprogramados), 2), '%') AS avancegeneral ";
    $query.= "FROM ($qGenPres) l";

    $presupuesto = $db->getQuery($query)[0];

    $qGenOTs = getQueryOTs($d->idpresupuesto);
    $query = "SELECT id, correlativo, proveedor, subtipogasto, IF(tipocambio = 1, '', FORMAT(tipocambio, 4)) AS tipocambio, moneda, FORMAT(monto, 2) AS monto, coniva, ";
    $query.= "FORMAT(pagosprogramados, 2) AS pagosprogramados, FORMAT((IFNULL(montoavance, 0.00) + isr), 2) AS montoavance, notas, ";
    $query.= "FORMAT(IF(monto > pagosprogramados, monto, pagosprogramados), 2) AS montoreal, ";
    //$query.= "CONCAT(FORMAT(IF(monto > pagosprogramados, (IFNULL(montoavance, 0.00) + isr) * 100 / monto, (IFNULL(montoavance, 0.00) + isr) * 100 / pagosprogramados), 2), '%') AS avanceot ";
    $query.= "CONCAT(FORMAT((IFNULL(montoavance, 0.00) + isr) * 100 / monto, 2), '%') AS avanceot ";
    $query.= "FROM($qGenOTs) l ORDER BY correlativo";

    $presupuesto->ots = $db->getQuery($query);

    $query = "SELECT b.descripcion AS evento, DATE_FORMAT(a.fechahora, '%d/%m/%Y %H:%i:%s') AS fechahora, c.iniciales ";
    $query.= "FROM bitacorapresupuesto a INNER JOIN eventobitapresup b ON a.evento = b.abreviatura INNER JOIN usuario c ON c.id = a.idusuario ";
    $query.= "WHERE origen = 1 AND idpresupuesto = $d->idpresupuesto ";
    $query.= "ORDER BY a.fechahora";

    $presupuesto->eventos = $db->getQuery($query);

    if((int)$d->detallado == 1){
        $cntOTs = count($presupuesto->ots);
        for($i = 0; $i < $cntOTs; $i++){
            $ot = $presupuesto->ots[$i];
            $ot->documentos = getDocumentosOT($db, $ot->id);
            $cntDocs = count($ot->documentos);
            if($cntDocs > 0){
                $suma->monto = 0.00;
                $suma->totfact = 0.00;
                $suma->isr = 0.00;
                for($j = 0; $j < $cntDocs; $j++){
                    $doc = $ot->documentos[$j];
                    $suma->monto += ((int)$doc->anulado == 0 ? $doc->monto : 0);
                    $suma->totfact += $doc->totfact;
                    $suma->isr += $doc->isr;
                    $doc->monto = number_format($doc->monto, 2);
                    $doc->totfact = number_format($doc->totfact, 2);
                    $doc->isr = $doc->isr != 0 ? number_format($doc->isr, 2) : '';
                }
                $ot->documentos[] = [
                    'id' => '', 'fecha' => '', 'siglas' => '', 'tipotrans' => '', 'numero' => '', 'beneficiario' => 'Totales:', 'tipocambio' => '',
                    'moneda' => 'Q', 'monto' => number_format($suma->monto, 2), 'documento' => '', 'totfact' => number_format($suma->totfact, 2),
                    'isr' => $suma->isr != 0 ? number_format($suma->isr, 2) : ''
                ];
            }
        }
    }

    print json_encode([ 'generales' => $generales, 'presupuesto' => $presupuesto]);

});

$app->post('/rptot', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS fecha";
    $generales = $db->getQuery($query)[0];

    $qGenOTs = getQueryOTs($d->idot, false);
    $query = "SELECT id, correlativo, proveedor, subtipogasto, IF(tipocambio = 1, '', FORMAT(tipocambio, 4)) AS tipocambio, moneda, monto, coniva, ";
    $query.= "FORMAT(pagosprogramados, 2) AS pagosprogramados, FORMAT((IFNULL(montoavance, 0.00) + isr), 2) AS montoavance, notas AS concepto, ";
    $query.= "IF(monto > pagosprogramados, monto, pagosprogramados) AS montoreal, ";
    $query.= "IF(monto > pagosprogramados, (IFNULL(montoavance, 0.00) + isr) * 100 / monto, (IFNULL(montoavance, 0.00) + isr) * 100 / pagosprogramados) AS poravance, ";
    $query.= "tipogasto, empresa, fechasolicitud, proyecto, idpresupuesto, FORMAT(monto, 2) AS montooriginal, 0.00 AS sumaavance, ";
    $query.= "montoot, monedaot, imprimemontoot ";
    $query.= "FROM($qGenOTs) l ORDER BY correlativo";
    //print $query;
    $ot = $db->getQuery($query)[0];

    $ot->formaspago = []; //Esto lo hago para que ya no hale las formas de pago en el reporte.
    $ot->notas = [];

    //Avance de la OT
    $suma = new stdClass();
    $ot->avance = getDocumentosOT($db, $d->idot);
    $suma->monto = 0.00;
    $suma->totfact = 0.00;
    $suma->isr = 0.00;
    $cntDocs = count($ot->avance);
    if($cntDocs > 0){
        for($j = 0; $j < $cntDocs; $j++){
            $doc = $ot->avance[$j];
            $suma->monto += ((int)$doc->anulado == 0 ? $doc->monto : 0);
            $suma->totfact += $doc->totfact;
            $suma->isr += $doc->isr;
            $doc->monto = number_format($doc->monto, 2);
            $doc->totfact = number_format($doc->totfact, 2);
            $doc->isr = $doc->isr != 0 ? number_format($doc->isr, 2) : '';
        }
        $ot->avance[] = [
            'id' => '', 'fecha' => '', 'siglas' => '', 'tipotrans' => '', 'numero' => '', 'beneficiario' => 'Totales:', 'tipocambio' => '',
            'moneda' => 'Q', 'monto' => number_format($suma->monto, 2), 'documento' => '', 'totfact' => number_format($suma->totfact, 2),
            'isr' => $suma->isr != 0 ? number_format($suma->isr, 2) : '', 'anulado' => '', 'iddetpagopresup' => '', 'nopago' => '', 'monedapago' => '', 'montopago' => ''
        ];
    }

    $ot->poravance = number_format(($suma->monto + $suma->isr) * 100 / $ot->monto, 2).'%';
    $ot->montoreal = number_format((float)$ot->montoreal, 2);
    $ot->sumaavance = number_format($suma->monto + $suma->isr, 2);

    print json_encode(['generales' => $generales, 'ot' => $ot]);

});

$app->post('/avanceot', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT 
                b.fecha AS OrdenFch,
                b.numero AS OrdenNum,
                DATE_FORMAT(b.fecha, '%d-%m-%Y') AS fechafactura,
                CONCAT(SUBSTRING(c.siglas, 1, 2),
                        '-',
                        b.tipotrans,
                        '-',
                        SUBSTRING(c.siglas, 4, 5),
                        '-',
                        b.numero) AS datosbanco,
                d.simbolo AS monedacheq,
                FORMAT(b.monto, 2) AS montocheq,
                b.numero,
                NULL AS monedafact,
                NULL AS montofac,
                NULL AS isr,
                NULL AS fact,
                ROUND(b.tipocambio, 2) AS tipocambio,
                SUBSTRING(b.concepto, 1, 90) AS conceptomayor,
                b.beneficiario,
                IF((b.anulado = 1
                        OR (b.anulado = 0
                        AND (b.beneficiario LIKE '%anula%'
                        OR b.concepto LIKE '%anula%'))),
                    1,
                    NULL) AS anulado,
                IF(b.iddocliquida != 0, 1, NULL) AS esreingreso,
                IF(b.liquidado = 1, 1, NULL) AS reingresado,
                IF(b.tipotrans = 'R' AND b.iddocliquida = 0,
                    1,
                    NULL) AS resta,
                NULL AS reembolso
            FROM
                detpresupuesto a
                    INNER JOIN
                tranban b ON b.iddetpresup = a.id
                    INNER JOIN
                banco c ON b.idbanco = c.id
                    INNER JOIN
                moneda d ON c.idmoneda = d.id
                    LEFT JOIN
                detpagocompra e ON e.idtranban = b.id
            WHERE
                a.id = $d->idot AND b.idfact IS NULL
                    AND e.id IS NULL
                    AND b.idreembolso IS NULL 
            UNION SELECT 
                f.fechafactura AS OrdenFch,
                b.numero AS OrdenNum,
                DATE_FORMAT(f.fechafactura, '%d-%m-%Y') AS fechafactura,
                CONCAT(SUBSTRING(c.siglas, 1, 2),
                        '-',
                        b.tipotrans,
                        '-',
                        SUBSTRING(c.siglas, 4, 5),
                        '-',
                        b.numero) AS datosbanco,
                d.simbolo AS monedacheq,
                FORMAT(b.monto, 2) AS montocheq,
                b.numero,
                g.simbolo AS monedafact,
                FORMAT(f.totfact, 2) AS montofac,
                FORMAT(f.isr, 2) AS isr,
                CONCAT(f.serie, '-', f.documento) AS fact,
                ROUND(f.tipocambio, 2) AS tipocambio,
                SUBSTRING(f.conceptomayor, 1, 90) AS conceptomayor,
                h.nombre AS beneficiario,
                IF((b.anulado = 1
                        OR (b.anulado = 0
                        AND (b.beneficiario LIKE '%anula%'
                        OR b.concepto LIKE '%anula%'))),
                    1,
                    NULL) AS anulado,
                NULL AS esreingreso,
                NULL AS reingresado,
                IF(b.tipotrans = 'R' AND b.iddocliquida = 0,
                    1,
                    NULL) AS resta,
                NULL AS reembolso
            FROM
                detpresupuesto a
                    INNER JOIN
                tranban b ON b.iddetpresup = a.id
                    INNER JOIN
                banco c ON b.idbanco = c.id
                    INNER JOIN
                moneda d ON c.idmoneda = d.id
                    INNER JOIN
                detpagocompra e ON e.idtranban = b.id
                    INNER JOIN
                compra f ON e.idcompra = f.id
                    INNER JOIN
                moneda g ON f.idmoneda = g.id
                    INNER JOIN
                proveedor h ON f.idproveedor = h.id
            WHERE
                a.id = $d->idot AND b.idreembolso IS NULL
                    AND f.idreembolso = 0 
            UNION SELECT 
                b.fechafactura AS OrdenFch,
                NULL AS OrdenNum,
                DATE_FORMAT(b.fechafactura, '%d-%m-%Y') AS fechafactura,
                NULL AS datosbanco,
                NULL AS monedacheq,
                NULL AS montocheq,
                NULL AS numero,
                c.simbolo AS monedafact,
                FORMAT(b.totfact, 2) AS montofac,
                FORMAT(b.isr, 2) AS isr,
                CONCAT(b.serie, '-', b.documento) AS fact,
                ROUND(b.tipocambio, 2) AS tipocambio,
                SUBSTRING(b.conceptomayor, 1, 90) AS conceptomayor,
                d.nombre AS beneficiario,
                NULL AS anulado,
                NULL AS esreingreso,
                NULL AS reingresado,
                NULL AS resta,
                NULL AS reembolso
            FROM
                detpresupuesto a
                    INNER JOIN
                compra b ON b.ordentrabajo = a.id
                    INNER JOIN
                moneda c ON b.idmoneda = c.id
                    INNER JOIN
                proveedor d ON b.idproveedor = d.id
                    LEFT JOIN
                detpagocompra e ON e.idcompra = b.id
            WHERE
                a.id = $d->idot AND e.id IS NULL 
            UNION SELECT 
                f.fechafactura AS OrdenFch,
                b.numero AS OrdenNum,
                DATE_FORMAT(f.fechafactura, '%d-%m-%Y') AS fechafactura,
                CONCAT(SUBSTRING(c.siglas, 1, 2),
                        '-',
                        b.tipotrans,
                        '-',
                        SUBSTRING(c.siglas, 4, 5),
                        '-',
                        b.numero) AS datosbanco,
                d.simbolo AS monedacheq,
                FORMAT(b.monto, 2) AS montocheq,
                b.numero,
                g.simbolo AS monedafact,
                FORMAT(f.totfact, 2) AS montofac,
                FORMAT(f.isr, 2) AS isr,
                CONCAT(f.serie, '-', f.documento) AS fact,
                ROUND(f.tipocambio, 2) AS tipocambio,
                SUBSTRING(f.conceptomayor, 1, 90) AS conceptomayor,
                IFNULL(h.nombre, f.proveedor) AS beneficiario,
                IF((b.anulado = 1
                        OR (b.anulado = 0
                        AND (b.beneficiario LIKE '%anula%'
                        OR b.concepto LIKE '%anula%'))),
                    1,
                    NULL) AS anulado,
                NULL AS esreingreso,
                NULL AS reingreso,
                IF(b.tipotrans = 'R' AND b.iddocliquida = 0,
                    1,
                    NULL) AS resta,
                IF(b.idreembolso IS NOT NULL
                        OR f.idreembolso > 0,
                    1,
                    NULL) AS reembolso
            FROM
                detpresupuesto a
                    INNER JOIN
                tranban b ON b.iddetpresup = a.id
                    INNER JOIN
                banco c ON b.idbanco = c.id
                    INNER JOIN
                moneda d ON c.idmoneda = d.id
                    INNER JOIN
                reembolso e ON b.idreembolso = e.id
                    INNER JOIN
                compra f ON f.idreembolso = e.id
                    INNER JOIN
                moneda g ON f.idmoneda = g.id
                    LEFT JOIN
                proveedor h ON f.idproveedor = h.id
            WHERE
                a.id = $d->idot
            ORDER BY OrdenFch , OrdenNum DESC ";
    $ordentrabajo = $db->getQuery($query);

    $query = "SELECT 
                CONCAT(a.id, '-', b.correlativo) AS ot,
                DATE_FORMAT(a.fechasolicitud, '%d-%m-%Y') AS fechasolicitud,
                c.nomproyecto AS proyecto,
                IFNULL(d.nombre, e.nombre) AS proveedor,
                f.nomempresa AS empresa,
                g.desctipogast AS tipogasto,
                h.descripcion AS subtipogasto,
                i.simbolo AS moneda,
                FORMAT(b.monto, 2) AS montoot,
                ROUND(a.tipocambio, 2) AS tipocambio,
                a.notas,
                SUBSTRING(a.notas, 1, 20) AS concepto,
                DATE_FORMAT(a.fhenvioaprobacion, '%d-%m-%Y %H:%i') AS creacion,
                j.nombre AS creador,
                DATE_FORMAT(a.fhaprobacion, '%d-%m-%Y %H:%i') AS aprobacion,
                k.nombre AS aprobador,
                DATE_FORMAT(a.fechamodificacion, '%d-%m-%Y %H:%i') AS modificacion,
                l.nombre AS modificador,
                IF(a.tipo = 2, 1, NULL) AS esotm,
                b.notas AS notasotm,
                FORMAT(IFNULL((SELECT 
                                    SUM(a.totfact)
                                FROM
                                    compra a
                                WHERE
                                    a.idmoneda = b.idmoneda
                                        AND a.ordentrabajo = b.id),
                            0.00) + IFNULL((SELECT 
                                    SUM(c.totfact)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    compra c ON a.idreembolso = c.idreembolso
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id),
                            0.00) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.totfact) * a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.totfact) / a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00)) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(c.totfact) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(c.totfact) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00)),
                    2) AS totfact,
                FORMAT(IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    banco c ON c.id = a.idbanco
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id
                                        AND a.anulado = 0
                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                        AND a.concepto NOT LIKE '%ANULA%'
                                        AND a.liquidado = 0
                                        AND a.tipotrans != 'R'
                                        AND a.beneficiario NOT LIKE '%REINGRE%'
                                        AND a.iddocliquida = 0),
                            0.00) - IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    banco c ON c.id = a.idbanco
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id
                                        AND a.tipotrans = 'R'
                                        AND iddocliquida = 0),
                            0.00) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.liquidado = 0
                                            AND a.tipotrans != 'R'
                                            AND a.beneficiario NOT LIKE '%REINGRE%'
                                            AND a.iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.liquidado = 0
                                            AND a.tipotrans != 'R'
                                            AND a.beneficiario NOT LIKE '%REINGRE%'
                                            AND a.iddocliquida = 0),
                                0.00)) - IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.tipotrans = 'R'
                                            AND iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.tipotrans = 'R'
                                            AND iddocliquida = 0),
                                0.00)),
                    2) AS totcheques,
                FORMAT(IFNULL((SELECT 
                                    SUM(a.isr)
                                FROM
                                    compra a
                                WHERE
                                    a.idmoneda = b.idmoneda
                                        AND a.ordentrabajo = b.id),
                            0.00) + IFNULL((SELECT 
                                    SUM(c.isr)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    compra c ON a.idreembolso = c.idreembolso
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id),
                            0.00) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.isr) * a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.isr) / a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00)) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(c.isr) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(c.isr) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00)),
                    2) AS totisr,
                FORMAT(IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    banco c ON c.id = a.idbanco
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id
                                        AND a.anulado = 0
                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                        AND a.concepto NOT LIKE '%ANULA%'
                                        AND a.liquidado = 0
                                        AND a.tipotrans != 'R'
                                        AND a.beneficiario NOT LIKE '%REINGRE%'
                                        AND a.iddocliquida = 0),
                            0.00) - IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    banco c ON c.id = a.idbanco
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id
                                        AND a.tipotrans = 'R'
                                        AND iddocliquida = 0),
                            0.00) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.liquidado = 0
                                            AND a.tipotrans != 'R'
                                            AND a.beneficiario NOT LIKE '%REINGRE%'
                                            AND a.iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.liquidado = 0
                                            AND a.tipotrans != 'R'
                                            AND a.beneficiario NOT LIKE '%REINGRE%'
                                            AND a.iddocliquida = 0),
                                0.00)) - IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.tipotrans = 'R'
                                            AND iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.tipotrans = 'R'
                                            AND iddocliquida = 0),
                                0.00)) + IFNULL((SELECT 
                                    SUM(a.isr)
                                FROM
                                    compra a
                                WHERE
                                    a.idmoneda = b.idmoneda
                                        AND a.ordentrabajo = b.id),
                            0.00) + IFNULL((SELECT 
                                    SUM(c.isr)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    compra c ON a.idreembolso = c.idreembolso
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id),
                            0.00) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.isr) * a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.isr) / a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00)) + IF(i.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(c.isr) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(c.isr) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00)),
                    2) AS totgastado,
                CONCAT(ROUND((IFNULL((SELECT 
                                                SUM(a.monto)
                                            FROM
                                                tranban a
                                                    INNER JOIN
                                                banco c ON c.id = a.idbanco
                                            WHERE
                                                c.idmoneda = b.idmoneda
                                                    AND a.iddetpresup = b.id
                                                    AND a.anulado = 0
                                                    AND a.beneficiario NOT LIKE '%ANULA%'
                                                    AND a.concepto NOT LIKE '%ANULA%'
                                                    AND a.liquidado = 0
                                                    AND a.tipotrans != 'R'
                                                    AND a.beneficiario NOT LIKE '%REINGRE%'
                                                    AND a.iddocliquida = 0),
                                        0.00) - IFNULL((SELECT 
                                                SUM(a.monto)
                                            FROM
                                                tranban a
                                                    INNER JOIN
                                                banco c ON c.id = a.idbanco
                                            WHERE
                                                c.idmoneda = b.idmoneda
                                                    AND a.iddetpresup = b.id
                                                    AND a.tipotrans = 'R'
                                                    AND iddocliquida = 0),
                                        0.00) + IF(i.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.monto) * a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.anulado = 0
                                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                                        AND a.concepto NOT LIKE '%ANULA%'
                                                        AND a.liquidado = 0
                                                        AND a.tipotrans != 'R'
                                                        AND a.beneficiario NOT LIKE '%REINGRE%'
                                                        AND a.iddocliquida = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.monto) / a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.anulado = 0
                                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                                        AND a.concepto NOT LIKE '%ANULA%'
                                                        AND a.liquidado = 0
                                                        AND a.tipotrans != 'R'
                                                        AND a.beneficiario NOT LIKE '%REINGRE%'
                                                        AND a.iddocliquida = 0),
                                            0.00)) - IF(i.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.monto) * a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.tipotrans = 'R'
                                                        AND iddocliquida = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.monto) / a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.tipotrans = 'R'
                                                        AND iddocliquida = 0),
                                            0.00)) + IFNULL((SELECT 
                                                SUM(a.isr)
                                            FROM
                                                compra a
                                            WHERE
                                                a.idmoneda = b.idmoneda
                                                    AND a.ordentrabajo = b.id),
                                        0.00) + IFNULL((SELECT 
                                                SUM(c.isr)
                                            FROM
                                                tranban a
                                                    INNER JOIN
                                                compra c ON a.idreembolso = c.idreembolso
                                            WHERE
                                                c.idmoneda = b.idmoneda
                                                    AND a.iddetpresup = b.id),
                                        0.00) + IF(i.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.isr) * a.tipocambio
                                                FROM
                                                    compra a
                                                WHERE
                                                    a.idmoneda != b.idmoneda
                                                        AND a.ordentrabajo = b.id),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.isr) / a.tipocambio
                                                FROM
                                                    compra a
                                                WHERE
                                                    a.idmoneda != b.idmoneda
                                                        AND a.ordentrabajo = b.id),
                                            0.00)) + IF(i.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(c.isr) * a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    compra c ON a.idreembolso = c.idreembolso
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(c.isr) / a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    compra c ON a.idreembolso = c.idreembolso
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id),
                                            0.00))) * 100 / b.monto,
                                2),
                        '%') AS avanceot
            FROM
                presupuesto a
                    INNER JOIN
                detpresupuesto b ON b.idpresupuesto = a.id
                    INNER JOIN
                proyecto c ON a.idproyecto = c.id
                    LEFT JOIN
                proveedor d ON b.idproveedor = d.id
                    LEFT JOIN
                beneficiario e ON b.idproveedor = e.id
                    INNER JOIN
                empresa f ON a.idempresa = f.id
                    INNER JOIN
                tipogasto g ON a.idtipogasto = g.id
                    INNER JOIN
                subtipogasto h ON b.idsubtipogasto = h.id
                    INNER JOIN
                moneda i ON b.idmoneda = i.id
                    INNER JOIN
                usuario j ON a.idusuario = j.id
                    LEFT JOIN
                usuario k ON a.idusuarioaprueba = k.id
                    INNER JOIN
                usuario l ON b.lastuser = l.id
            WHERE
                b.id = $d->idot ";
    $general = $db->getQuery($query)[0];

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS fecha";
    $generales = $db->getQuery($query)[0];

    print json_encode(['general' => $general, 'ordentrabajo' => $ordentrabajo, 'generales' => $generales]);
});

$app->post('/avanceotm', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT 
                CONCAT(b.idpresupuesto, '-', b.correlativo) AS ot,
                DATE_FORMAT(b.fhenvioaprobacion, '%d-%m-%Y') AS fhenvioaprobacion,
                IFNULL(c.nombre, d.nombre) AS proveedor,
                e.descripcion AS subtipogasto,
                f.simbolo,
                FORMAT(b.monto, 2) AS montoot,
                b.notas,
                b.id,
                FORMAT(IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                WHERE
                                    a.iddetpresup = b.id
                                        AND a.tipocambio = 1
                                        AND a.anulado = 0
                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                        AND a.concepto NOT LIKE '%ANULA%'
                                        AND a.tipotrans != 'R'
                                        AND a.liquidado = 0),
                            0.00) - IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    banco c ON a.idbanco = c.id
                                WHERE
                                    a.iddetpresup = b.id
                                        AND a.tipocambio = 1
                                        AND a.tipotrans = 'R'
                                        AND a.iddocliquida = 0),
                            0.00) + IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                    WHERE
                                        a.iddetpresup = b.id
                                            AND a.tipocambio != 1
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.tipotrans != 'R'
                                            AND a.liquidado = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                    WHERE
                                        a.iddetpresup = b.id
                                            AND a.tipocambio != 1
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.tipotrans != 'R'
                                            AND a.liquidado = 0),
                                0.00)) - IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON a.idbanco = c.id
                                    WHERE
                                        a.iddetpresup = b.id
                                            AND a.tipocambio != 1
                                            AND a.tipotrans = 'R'
                                            AND a.iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON a.idbanco = c.id
                                    WHERE
                                        a.iddetpresup = b.id
                                            AND a.tipocambio = 1
                                            AND a.tipotrans = 'R'
                                            AND a.iddocliquida = 0),
                                0.00)),
                    2) AS montogastado,
                CONCAT(ROUND((IFNULL((SELECT 
                                                SUM(a.monto)
                                            FROM
                                                tranban a
                                            WHERE
                                                a.iddetpresup = b.id
                                                    AND a.tipocambio = 1
                                                    AND a.anulado = 0
                                                    AND a.beneficiario NOT LIKE '%ANULA%'
                                                    AND a.concepto NOT LIKE '%ANULA%'
                                                    AND a.tipotrans != 'R'
                                                    AND a.liquidado = 0),
                                        0.00) - IFNULL((SELECT 
                                                SUM(a.monto)
                                            FROM
                                                tranban a
                                                    INNER JOIN
                                                banco c ON a.idbanco = c.id
                                            WHERE
                                                a.iddetpresup = b.id
                                                    AND a.tipocambio = 1
                                                    AND a.tipotrans = 'R'
                                                    AND a.iddocliquida = 0),
                                        0.00) + IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.monto) * a.tipocambio
                                                FROM
                                                    tranban a
                                                WHERE
                                                    a.iddetpresup = b.id
                                                        AND a.tipocambio != 1
                                                        AND a.anulado = 0
                                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                                        AND a.concepto NOT LIKE '%ANULA%'
                                                        AND a.tipotrans != 'R'
                                                        AND a.liquidado = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.monto) / a.tipocambio
                                                FROM
                                                    tranban a
                                                WHERE
                                                    a.iddetpresup = b.id
                                                        AND a.tipocambio != 1
                                                        AND a.anulado = 0
                                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                                        AND a.concepto NOT LIKE '%ANULA%'
                                                        AND a.tipotrans != 'R'
                                                        AND a.liquidado = 0),
                                            0.00)) - IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.monto) * a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON a.idbanco = c.id
                                                WHERE
                                                    a.iddetpresup = b.id
                                                        AND a.tipocambio != 1
                                                        AND a.tipotrans = 'R'
                                                        AND a.iddocliquida = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.monto) / a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON a.idbanco = c.id
                                                WHERE
                                                    a.iddetpresup = b.id
                                                        AND a.tipocambio = 1
                                                        AND a.tipotrans = 'R'
                                                        AND a.iddocliquida = 0),
                                            0.00))) * 100 / b.monto,
                                2),
                        '%') AS avanceot
            FROM
                presupuesto a
                    INNER JOIN
                detpresupuesto b ON b.idpresupuesto = a.id
                    LEFT JOIN
                proveedor c ON b.idproveedor = c.id
                    LEFT JOIN
                beneficiario d ON b.idproveedor = d.id
                    INNER JOIN
                subtipogasto e ON b.idsubtipogasto = e.id
                    INNER JOIN
                moneda f ON b.idmoneda = f.id
            WHERE
                a.id = $d->idpresupuesto
                    AND b.idestatuspresupuesto IN (3 , 5); ";
    $ordentrabajo = $db->getQuery($query);

    $cntOrdenes = count($ordentrabajo);
    for($i = 0; $i < $cntOrdenes; $i++) {
        $ot = $ordentrabajo[$i];

        $query = "SELECT 
                    b.fecha AS OrdenFch,
                    b.numero AS OrdenNum,
                    DATE_FORMAT(b.fecha, '%d-%m-%Y') AS fechafactura,
                    CONCAT(SUBSTRING(c.siglas, 1, 2),
                            '-',
                            b.tipotrans,
                            '-',
                            SUBSTRING(c.siglas, 4, 5),
                            '-',
                            b.numero) AS datosbanco,
                    d.simbolo AS monedacheq,
                    FORMAT(b.monto, 2) AS montocheq,
                    b.numero,
                    NULL AS monedafact,
                    NULL AS montofac,
                    NULL AS isr,
                    NULL AS fact,
                    ROUND(b.tipocambio, 2) AS tipocambio,
                    SUBSTRING(b.concepto, 1, 90) AS conceptomayor,
                    b.beneficiario,
                    IF((b.anulado = 1
                            OR (b.anulado = 0
                            AND (b.beneficiario LIKE '%anula%'
                            OR b.concepto LIKE '%anula%'))),
                        1,
                        NULL) AS anulado,
                    IF(b.iddocliquida != 0, 1, NULL) AS esreingreso,
                    IF(b.liquidado = 1, 1, NULL) AS reingresado,
                    IF(b.tipotrans = 'R' AND b.iddocliquida = 0,
                        1,
                        NULL) AS resta,
                    NULL AS reembolso
                FROM
                    detpresupuesto a
                        INNER JOIN
                    tranban b ON b.iddetpresup = a.id
                        INNER JOIN
                    banco c ON b.idbanco = c.id
                        INNER JOIN
                    moneda d ON c.idmoneda = d.id
                        LEFT JOIN
                    detpagocompra e ON e.idtranban = b.id
                WHERE
                    a.id = $ot->id AND b.idfact IS NULL
                        AND e.id IS NULL
                        AND b.idreembolso IS NULL 
                UNION SELECT 
                    f.fechafactura AS OrdenFch,
                    b.numero AS OrdenNum,
                    DATE_FORMAT(f.fechafactura, '%d-%m-%Y') AS fechafactura,
                    CONCAT(SUBSTRING(c.siglas, 1, 2),
                            '-',
                            b.tipotrans,
                            '-',
                            SUBSTRING(c.siglas, 4, 5),
                            '-',
                            b.numero) AS datosbanco,
                    d.simbolo AS monedacheq,
                    FORMAT(b.monto, 2) AS montocheq,
                    b.numero,
                    g.simbolo AS monedafact,
                    FORMAT(f.totfact, 2) AS montofac,
                    FORMAT(f.isr, 2) AS isr,
                    CONCAT(f.serie, '-', f.documento) AS fact,
                    ROUND(f.tipocambio, 2) AS tipocambio,
                    SUBSTRING(f.conceptomayor, 1, 90) AS conceptomayor,
                    h.nombre AS beneficiario,
                    IF((b.anulado = 1
                            OR (b.anulado = 0
                            AND (b.beneficiario LIKE '%anula%'
                            OR b.concepto LIKE '%anula%'))),
                        1,
                        NULL) AS anulado,
                    NULL AS esreingreso,
                    NULL AS reingresado,
                    IF(b.tipotrans = 'R' AND b.iddocliquida = 0,
                        1,
                        NULL) AS resta,
                    NULL AS reembolso
                FROM
                    detpresupuesto a
                        INNER JOIN
                    tranban b ON b.iddetpresup = a.id
                        INNER JOIN
                    banco c ON b.idbanco = c.id
                        INNER JOIN
                    moneda d ON c.idmoneda = d.id
                        INNER JOIN
                    detpagocompra e ON e.idtranban = b.id
                        INNER JOIN
                    compra f ON e.idcompra = f.id
                        INNER JOIN
                    moneda g ON f.idmoneda = g.id
                        INNER JOIN
                    proveedor h ON f.idproveedor = h.id
                WHERE
                    a.id = $ot->id AND b.idreembolso IS NULL
                        AND f.idreembolso = 0 
                UNION SELECT 
                    b.fechafactura AS OrdenFch,
                    NULL AS OrdenNum,
                    DATE_FORMAT(b.fechafactura, '%d-%m-%Y') AS fechafactura,
                    NULL AS datosbanco,
                    NULL AS monedacheq,
                    NULL AS montocheq,
                    NULL AS numero,
                    c.simbolo AS monedafact,
                    FORMAT(b.totfact, 2) AS montofac,
                    FORMAT(b.isr, 2) AS isr,
                    CONCAT(b.serie, '-', b.documento) AS fact,
                    ROUND(b.tipocambio, 2) AS tipocambio,
                    SUBSTRING(b.conceptomayor, 1, 90) AS conceptomayor,
                    d.nombre AS beneficiario,
                    NULL AS anulado,
                    NULL AS esreingreso,
                    NULL AS reingresado,
                    NULL AS resta,
                    NULL AS reembolso
                FROM
                    detpresupuesto a
                        INNER JOIN
                    compra b ON b.ordentrabajo = a.id
                        INNER JOIN
                    moneda c ON b.idmoneda = c.id
                        INNER JOIN
                    proveedor d ON b.idproveedor = d.id
                        LEFT JOIN
                    detpagocompra e ON e.idcompra = b.id
                WHERE
                    a.id = $ot->id AND e.id IS NULL 
                UNION SELECT 
                    f.fechafactura AS OrdenFch,
                    b.numero AS OrdenNum,
                    DATE_FORMAT(f.fechafactura, '%d-%m-%Y') AS fechafactura,
                    CONCAT(SUBSTRING(c.siglas, 1, 2),
                            '-',
                            b.tipotrans,
                            '-',
                            SUBSTRING(c.siglas, 4, 5),
                            '-',
                            b.numero) AS datosbanco,
                    d.simbolo AS monedacheq,
                    FORMAT(b.monto, 2) AS montocheq,
                    b.numero,
                    g.simbolo AS monedafact,
                    FORMAT(f.totfact, 2) AS montofac,
                    FORMAT(f.isr, 2) AS isr,
                    CONCAT(f.serie, '-', f.documento) AS fact,
                    ROUND(f.tipocambio, 2) AS tipocambio,
                    SUBSTRING(f.conceptomayor, 1, 90) AS conceptomayor,
                    IFNULL(h.nombre, f.proveedor) AS beneficiario,
                    IF((b.anulado = 1
                            OR (b.anulado = 0
                            AND (b.beneficiario LIKE '%anula%'
                            OR b.concepto LIKE '%anula%'))),
                        1,
                        NULL) AS anulado,
                    NULL AS esreingreso,
                    NULL AS reingreso,
                    IF(b.tipotrans = 'R' AND b.iddocliquida = 0,
                        1,
                        NULL) AS resta,
                    IF(b.idreembolso IS NOT NULL
                            OR f.idreembolso > 0,
                        1,
                        NULL) AS reembolso
                FROM
                    detpresupuesto a
                        INNER JOIN
                    tranban b ON b.iddetpresup = a.id
                        INNER JOIN
                    banco c ON b.idbanco = c.id
                        INNER JOIN
                    moneda d ON c.idmoneda = d.id
                        INNER JOIN
                    reembolso e ON b.idreembolso = e.id
                        INNER JOIN
                    compra f ON f.idreembolso = e.id
                        INNER JOIN
                    moneda g ON f.idmoneda = g.id
                        LEFT JOIN
                    proveedor h ON f.idproveedor = h.id
                WHERE
                    a.id = $ot->id
                ORDER BY OrdenFch , OrdenNum DESC ";
        $ot->documento = $db->getQuery($query);
    }

    $query = "SELECT 
                a.id AS ot,
                DATE_FORMAT(a.fechasolicitud, '%d-%m-%Y') AS fechasolicitud,
                c.nomproyecto AS proyecto,
                d.nomempresa AS empresa,
                e.desctipogast AS tipogasto,
                f.simbolo AS moneda,
                SUBSTRING(a.notas, 1, 20) AS concepto,
                a.notas,
                FORMAT(a.total, 2) AS montoot,
                FORMAT(SUM(IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    banco c ON c.id = a.idbanco
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id
                                        AND a.anulado = 0
                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                        AND a.concepto NOT LIKE '%ANULA%'
                                        AND a.liquidado = 0
                                        AND a.tipotrans != 'R'
                                        AND a.beneficiario NOT LIKE '%REINGRE%'
                                        AND a.iddocliquida = 0),
                            0.00) - IFNULL((SELECT 
                                    SUM(a.monto)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    banco c ON c.id = a.idbanco
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id
                                        AND a.tipotrans = 'R'
                                        AND iddocliquida = 0),
                            0.00) + IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.liquidado = 0
                                            AND a.tipotrans != 'R'
                                            AND a.beneficiario NOT LIKE '%REINGRE%'
                                            AND a.iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.anulado = 0
                                            AND a.beneficiario NOT LIKE '%ANULA%'
                                            AND a.concepto NOT LIKE '%ANULA%'
                                            AND a.liquidado = 0
                                            AND a.tipotrans != 'R'
                                            AND a.beneficiario NOT LIKE '%REINGRE%'
                                            AND a.iddocliquida = 0),
                                0.00)) - IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.monto) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.tipotrans = 'R'
                                            AND iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.monto) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        banco c ON c.id = a.idbanco
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id
                                            AND a.tipotrans = 'R'
                                            AND iddocliquida = 0),
                                0.00)) + IFNULL((SELECT 
                                    SUM(a.isr)
                                FROM
                                    compra a
                                WHERE
                                    a.idmoneda = b.idmoneda
                                        AND a.ordentrabajo = b.id),
                            0.00) + IFNULL((SELECT 
                                    SUM(c.isr)
                                FROM
                                    tranban a
                                        INNER JOIN
                                    compra c ON a.idreembolso = c.idreembolso
                                WHERE
                                    c.idmoneda = b.idmoneda
                                        AND a.iddetpresup = b.id),
                            0.00) + IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(a.isr) * a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(a.isr) / a.tipocambio
                                    FROM
                                        compra a
                                    WHERE
                                        a.idmoneda != b.idmoneda
                                            AND a.ordentrabajo = b.id),
                                0.00)) + IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(c.isr) * a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(c.isr) / a.tipocambio
                                    FROM
                                        tranban a
                                            INNER JOIN
                                        compra c ON a.idreembolso = c.idreembolso
                                    WHERE
                                        c.idmoneda != b.idmoneda
                                            AND a.iddetpresup = b.id),
                                0.00))),
                    2) AS montogastado,
                CONCAT(ROUND(SUM(IFNULL((SELECT 
                                                SUM(a.monto)
                                            FROM
                                                tranban a
                                                    INNER JOIN
                                                banco c ON c.id = a.idbanco
                                            WHERE
                                                c.idmoneda = b.idmoneda
                                                    AND a.iddetpresup = b.id
                                                    AND a.anulado = 0
                                                    AND a.beneficiario NOT LIKE '%ANULA%'
                                                    AND a.concepto NOT LIKE '%ANULA%'
                                                    AND a.liquidado = 0
                                                    AND a.tipotrans != 'R'
                                                    AND a.beneficiario NOT LIKE '%REINGRE%'
                                                    AND a.iddocliquida = 0),
                                        0.00) - IFNULL((SELECT 
                                                SUM(a.monto)
                                            FROM
                                                tranban a
                                                    INNER JOIN
                                                banco c ON c.id = a.idbanco
                                            WHERE
                                                c.idmoneda = b.idmoneda
                                                    AND a.iddetpresup = b.id
                                                    AND a.tipotrans = 'R'
                                                    AND iddocliquida = 0),
                                        0.00) + IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.monto) * a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.anulado = 0
                                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                                        AND a.concepto NOT LIKE '%ANULA%'
                                                        AND a.liquidado = 0
                                                        AND a.tipotrans != 'R'
                                                        AND a.beneficiario NOT LIKE '%REINGRE%'
                                                        AND a.iddocliquida = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.monto) / a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.anulado = 0
                                                        AND a.beneficiario NOT LIKE '%ANULA%'
                                                        AND a.concepto NOT LIKE '%ANULA%'
                                                        AND a.liquidado = 0
                                                        AND a.tipotrans != 'R'
                                                        AND a.beneficiario NOT LIKE '%REINGRE%'
                                                        AND a.iddocliquida = 0),
                                            0.00)) - IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.monto) * a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.tipotrans = 'R'
                                                        AND iddocliquida = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.monto) / a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    banco c ON c.id = a.idbanco
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id
                                                        AND a.tipotrans = 'R'
                                                        AND iddocliquida = 0),
                                            0.00)) + IFNULL((SELECT 
                                                SUM(a.isr)
                                            FROM
                                                compra a
                                            WHERE
                                                a.idmoneda = b.idmoneda
                                                    AND a.ordentrabajo = b.id),
                                        0.00) + IFNULL((SELECT 
                                                SUM(c.isr)
                                            FROM
                                                tranban a
                                                    INNER JOIN
                                                compra c ON a.idreembolso = c.idreembolso
                                            WHERE
                                                c.idmoneda = b.idmoneda
                                                    AND a.iddetpresup = b.id),
                                        0.00) + IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(a.isr) * a.tipocambio
                                                FROM
                                                    compra a
                                                WHERE
                                                    a.idmoneda != b.idmoneda
                                                        AND a.ordentrabajo = b.id),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(a.isr) / a.tipocambio
                                                FROM
                                                    compra a
                                                WHERE
                                                    a.idmoneda != b.idmoneda
                                                        AND a.ordentrabajo = b.id),
                                            0.00)) + IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(c.isr) * a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    compra c ON a.idreembolso = c.idreembolso
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(c.isr) / a.tipocambio
                                                FROM
                                                    tranban a
                                                        INNER JOIN
                                                    compra c ON a.idreembolso = c.idreembolso
                                                WHERE
                                                    c.idmoneda != b.idmoneda
                                                        AND a.iddetpresup = b.id),
                                            0.00))) * 100 / a.total,
                                2),
                        '%') AS avanceot
            FROM
                presupuesto a
                    INNER JOIN
                detpresupuesto b ON b.idpresupuesto = a.id
                    INNER JOIN
                proyecto c ON a.idproyecto = c.id
                    INNER JOIN
                empresa d ON a.idempresa = d.id
                    INNER JOIN
                tipogasto e ON a.idtipogasto = e.id
                    INNER JOIN
                moneda f ON a.idmoneda = f.id
            WHERE
                a.id = $d->idpresupuesto
                    AND b.idestatuspresupuesto IN (3, 5) ";
    $general = $db->getQuery($query)[0];

    

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS fecha";
    $generales = $db->getQuery($query)[0];

    print json_encode(['general' => $general, 'ordentrabajo' => $ordentrabajo, 'generales' => $generales]);
});

$app->run();