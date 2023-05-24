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

    // encabezado
    $query = "SELECT
                a.id,
                a.idmoneda, 
                CONCAT(a.idpresupuesto, '-', a.correlativo) AS presupuesto,
                DATE_FORMAT(a.fhenvioaprobacion, '%d/%m/%Y') AS fecha,
                c.nomproyecto AS proyecto,
                IFNULL(d.nombre, e.nombre) AS proveedor,
                f.nomempresa AS empresa,
                g.desctipogast AS tipogasto,
                h.descripcion AS subtipogasto,
                a.notas,
                b.notas AS descripcion, 
                SUBSTRING(a.notas, 1, 90) AS concepto,
                b.notas AS notag,
                i.simbolo AS moneda,
                DATE_FORMAT(b.fechacreacion, '%d/%m/%Y') AS creacion,
                j.iniciales AS creador,
                DATE_FORMAT(a.fhaprobacion, '%d%/%m/%Y') AS aprobacion,
                k.iniciales AS aprobador,
                DATE_FORMAT(a.fechamodificacion, '%d/%m/%Y') AS modificacion,
                l.iniciales AS modificador,
                DATE_FORMAT(a.fhanulacion, '%d/%m/%Y') AS anulacion, 
                n.iniciales AS anulador,
                IF(b.tipo = 2, TRUE, NULL) AS esotm,
                IF(m.id = 5, TRUE, NULL) AS terminada,
                IF(m.id = 6, TRUE, NULL) AS anulada,
                ROUND(a.tipocambio, 2) AS tipocambio,
                ROUND(a.monto, 2) AS monto,
                NULL AS compras,
                NULL AS isr,
                NULL AS cheques,
                NULL AS totgastado,
                NULL AS avance,
                NULL AS diferencia
            FROM
                detpresupuesto a
                    INNER JOIN
                presupuesto b ON a.idpresupuesto = b.id
                    INNER JOIN
                proyecto c ON b.idproyecto = c.id
                    LEFT JOIN
                proveedor d ON a.idproveedor = d.id
                    AND a.origenprov = 1
                    LEFT JOIN
                beneficiario e ON a.idproveedor = e.id
                    AND a.origenprov = 2
                    INNER JOIN
                empresa f ON b.idempresa = f.id
                    INNER JOIN
                tipogasto g ON b.idtipogasto = g.id
                    INNER JOIN
                subtipogasto h ON a.idsubtipogasto = h.id
                    INNER JOIN
                moneda i ON a.idmoneda = i.id
                    INNER JOIN
                usuario j ON b.idusuario = j.id
                    INNER JOIN
                usuario k ON a.idusuarioaprueba = k.id
                    LEFT JOIN
                usuario l ON a.lastuser = l.id
                    INNER JOIN
                estatuspresupuesto m ON a.idestatuspresupuesto = m.id
                    LEFT JOIN 
                usuario n ON a.idusuarioanula = n.id
            WHERE
                a.id = $d->idot";
    $orden = $db->getQuery($query)[0];

    getPagos($orden, $db, false);
    getTotales($orden, $db, false);

    print json_encode(['orden' => $orden]);
});

// nueva version
// $app->post('/avanceotm', function(){
//     $d = json_decode(file_get_contents('php://input'));
//     $db = new dbcpm();

    // $query = "SELECT 
    //             a.id AS otm,
    //             DATE_FORMAT(fechacreacion, '%d/%m/%Y') AS fecha,
    //             b.nomproyecto AS proyecto,
    //             c.nomempresa AS empresa,
    //             d.desctipogast AS tipogasto,
    //             e.simbolo AS moneda,
    //             SUBSTRING(a.notas, 1, 20) AS concepto,
    //             a.notas,
    //             a.idmoneda,
    //             f.iniciales AS creador, 
    //             NULL AS monto,
    //             NULL AS gastado,
    //             NULL AS diferencia,
    //             NULL AS avance,
    //             NULL AS ots
    //         FROM
    //             presupuesto a
    //                 INNER JOIN
    //             proyecto b ON a.idproyecto = b.id
    //                 INNER JOIN
    //             empresa c ON a.idempresa = c.id
    //                 INNER JOIN
    //             tipogasto d ON a.idtipogasto = d.id
    //                 INNER JOIN
    //             moneda e ON a.idmoneda = e.id
    //                 INNER JOIN
    //             usuario f ON a.idusuario = f.id
    //         WHERE
    //             a.id = $d->idot";
    //         $orden = $db->getQuery($query)[0];

    //         $query = "SELECT 
    //             a.id, 
    //             CONCAT(a.idpresupuesto, '-', a.correlativo) AS numero,
    //             SUBSTRING(IFNULL(b.nombre, c.nombre), 1, 26) AS proveedor,
    //             SUBSTRING(d.descripcion, 1, 20) AS subtipogasto,
    //             a.monto,
    //             a.notas,
    //             e.simbolo AS moneda,
    //             e.id AS idmoneda,
    //             a.tipocambio,
    //             DATE_FORMAT(a.fechamodificacion, '%d/%m/%Y') AS fecha,
    //             f.iniciales AS usuario,
    //             NULL AS totgastado,
    //             NULL AS avance,
    //             NULL AS tcheques,
    //             NULL AS tcompras,
    //             NULL AS diferencia,
    //             NULL AS afectar,
    //             NULL AS isr,
    //             NULL AS compras,
    //             NULL AS cheques
    //         FROM
    //             detpresupuesto a
    //                 LEFT JOIN
    //             proveedor b ON a.idproveedor = b.id
    //                 AND a.origenprov = 1
    //                 LEFT JOIN
    //             beneficiario c ON a.idproveedor = c.id
    //                 AND a.origenprov = 2
    //                 INNER JOIN
    //             subtipogasto d ON a.idsubtipogasto = d.id
    //                 INNER JOIN
    //             moneda e ON a.idmoneda = e.id
    //                 LEFT JOIN
    //             usuario f ON a.lastuser = f.id
    //         WHERE
    //             a.idpresupuesto = $d->idot
    //                 AND a.idestatuspresupuesto IN(1, 2, 3, 5)
    //         ORDER BY a.correlativo";
    //         $orden->ots = $db->getQuery($query);

//             getPagos($orden->ots, $db, true);
//             getTotales($orden, $db, true);

//             print json_encode(['orden' => $orden]);
// });

// version antigua
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
                                0.00)),
                    2) AS montogastado,
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
                    AND b.idestatuspresupuesto IN (3 , 5) 
            ORDER BY 
                b.correlativo";
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
                    SUBSTRING(b.concepto, 1, 70) AS conceptomayor,
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
                        LEFT JOIN
				    doctotranban f ON f.idtranban = b.id
                WHERE
                    a.id = $ot->id AND b.idfact IS NULL
                        AND e.id IS NULL
                        AND b.idreembolso IS NULL 
                        AND f.id IS NULL
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
                    SUBSTRING(f.conceptomayor, 1, 70) AS conceptomayor,
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
                    SUBSTRING(b.conceptomayor, 1, 70) AS conceptomayor,
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
                    -- reembolsos
                UNION SELECT 
                    d.finicio AS OrdenFch,
                    b.numero AS OrdenNum,
                    DATE_FORMAT(d.finicio, '%d-%m-%Y') AS fechafactura,
                    CONCAT(SUBSTRING(e.siglas, 1, 2),
                            '-',
                            b.tipotrans,
                            '-',
                            SUBSTRING(e.siglas, 4, 5),
                            '-',
                            b.numero) AS datosbanco,
                    f.simbolo AS monedacheq,
                    FORMAT(c.monto, 2) AS montocheq,
                    b.numero,
                    'Q' AS monedafact,
                    (SELECT 
                            FORMAT(SUM(h.totfact), 2)
                        FROM
                            compra h
                        WHERE
                            h.idreembolso = d.id) AS montofac,
                    0.00 AS isr,
                    CONCAT('REE-', LPAD(d.id, '5', '0')) AS fact,
                    1.00 AS tipocambio,
                    '' concepto,
                    g.nombre AS beneficiario,
                    IF((b.anulado = 1
                            OR (b.anulado = 0
                            AND (b.beneficiario LIKE '%anula%'
                            OR b.concepto LIKE '%anula%'))),
                        1,
                        NULL) AS anulado,
                    NULL AS esreingreso,
                    NULL AS reingreso,
                    NULL AS resta,
                    NULL AS reembolso
                FROM
                    detpresupuesto a
                        INNER JOIN
                    tranban b ON b.iddetpresup = a.id
                        INNER JOIN
                    doctotranban c ON c.idtranban = b.id AND c.idtipodoc = 2
                        JOIN
                    reembolso d ON c.iddocto = d.id
                        INNER JOIN
                    banco e ON b.idbanco = e.id
                        INNER JOIN
                    moneda f ON e.idmoneda = f.id
                        INNER JOIN
                    beneficiario g ON d.idbeneficiario = g.id
                WHERE
                    a.id = $ot->id
                ORDER BY OrdenFch , OrdenNum DESC ";
        $ot->documento = $db->getQuery($query);
    }

    // $cntoOt = count($ordentrabajo);

    // for ($i = $cntoOt -1; $i > -1; $i--) { 
    //     $cheque = $ordentrabajo[$i]->numero;
    //     if ($i > 0) {
    //     $cheque2 = $ordentrabajo[$i - 1]->numero;
    //     } else {
    //         $cheque2 = 0;
    //     }
    //     if ($cheque == $cheque2) {
    //         $ordentrabajo[$i]->numero = NULL;
    //     }
    // }

    $query = "SELECT DISTINCT
                a.id AS ot,
                DATE_FORMAT(a.fechasolicitud, '%d-%m-%Y') AS fechasolicitud,
                c.nomproyecto AS proyecto,
                d.nomempresa AS empresa,
                e.desctipogast AS tipogasto,
                f.simbolo AS moneda,
                SUBSTRING(a.notas, 1, 70) AS concepto,
                a.notas,
                FORMAT(a.total, 2) AS montoot,
                FORMAT(IFNULL((SELECT 
                                    SUM(c.monto)
                                FROM
                                    detpresupuesto b
                                        INNER JOIN
                                    tranban c ON b.id = c.iddetpresup
                                        INNER JOIN
                                    banco d ON d.id = c.idbanco
                                WHERE
                                    d.idmoneda = a.idmoneda
                                        AND b.idpresupuesto = a.id
                                        AND c.anulado = 0
                                        AND c.beneficiario NOT LIKE '%ANULA%'
                                        AND c.concepto NOT LIKE '%ANULA%'
                                        AND c.liquidado = 0
                                        AND c.tipotrans != 'R'
                                        AND c.beneficiario NOT LIKE '%REINGRE%'
                                        AND c.iddocliquida = 0),
                            0.00) - IFNULL((SELECT 
                                    SUM(c.monto)
                                FROM
                                    detpresupuesto b
                                        INNER JOIN
                                    tranban c ON b.id = c.iddetpresup
                                        INNER JOIN
                                    banco d ON d.id = c.idbanco
                                WHERE
                                    d.idmoneda = a.idmoneda
                                        AND b.idpresupuesto = a.id
                                        AND c.tipotrans = 'R'
                                        AND c.iddocliquida = 0),
                            0.00) + IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(c.monto) * c.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        tranban c ON b.id = c.iddetpresup
                                            INNER JOIN
                                        banco d ON d.id = c.idbanco
                                    WHERE
                                        d.idmoneda != a.idmoneda
                                            AND b.idpresupuesto = a.id
                                            AND c.anulado = 0
                                            AND c.beneficiario NOT LIKE '%ANULA%'
                                            AND c.concepto NOT LIKE '%ANULA%'
                                            AND c.liquidado = 0
                                            AND c.tipotrans != 'R'
                                            AND c.beneficiario NOT LIKE '%REINGRE%'
                                            AND c.iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(c.monto) / c.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        tranban c ON b.id = c.iddetpresup
                                            INNER JOIN
                                        banco d ON d.id = c.idbanco
                                    WHERE
                                        d.idmoneda != a.idmoneda
                                            AND b.idpresupuesto = a.id
                                            AND c.anulado = 0
                                            AND c.beneficiario NOT LIKE '%ANULA%'
                                            AND c.concepto NOT LIKE '%ANULA%'
                                            AND c.liquidado = 0
                                            AND c.tipotrans != 'R'
                                            AND c.beneficiario NOT LIKE '%REINGRE%'
                                            AND c.iddocliquida = 0),
                                0.00)) - IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(c.monto) * c.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        tranban c ON b.id = c.iddetpresup
                                            INNER JOIN
                                        banco d ON d.id = c.idbanco
                                    WHERE
                                        d.idmoneda != a.idmoneda
                                            AND b.idpresupuesto = a.id
                                            AND c.tipotrans = 'R'
                                            AND c.iddocliquida = 0),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(c.monto) / c.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        tranban c ON b.id = c.iddetpresup
                                            INNER JOIN
                                        banco d ON d.id = c.idbanco
                                    WHERE
                                        d.idmoneda != a.idmoneda
                                            AND b.idpresupuesto = b.id
                                            AND c.tipotrans = 'R'
                                            AND c.iddocliquida = 0),
                                0.00)) + IFNULL((SELECT 
                                    SUM(c.isr)
                                FROM
                                    detpresupuesto b
                                        INNER JOIN
                                    compra c ON b.id = c.ordentrabajo
                                WHERE
                                    c.idmoneda = a.idmoneda
                                        AND b.idpresupuesto = a.id),
                            0.00) + IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(c.isr) * c.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        compra c ON b.id = c.ordentrabajo
                                    WHERE
                                        c.idmoneda != a.idmoneda
                                            AND b.idpresupuesto = a.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(c.isr) / c.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        compra c ON b.id = c.ordentrabajo
                                    WHERE
                                        c.idmoneda != a.idmoneda
                                            AND b.idpresupuesto = a.id),
                                0.00)) + IFNULL((SELECT 
                                    SUM(d.isr)
                                FROM
                                    detpresupuesto b
                                        INNER JOIN
                                    tranban c ON c.iddetpresup = b.id
                                        INNER JOIN
                                    compra d ON c.idreembolso = d.idreembolso
                                WHERE
                                    d.idmoneda = a.idmoneda
                                        AND b.idpresupuesto = a.id),
                            0.00) + IF(f.eslocal = 1,
                        IFNULL((SELECT 
                                        SUM(d.isr) * d.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        tranban c ON c.iddetpresup = b.id
                                            INNER JOIN
                                        compra d ON c.idreembolso = d.idreembolso
                                    WHERE
                                        d.idmoneda = a.idmoneda
                                            AND b.idpresupuesto = a.id),
                                0.00),
                        IFNULL((SELECT 
                                        SUM(d.isr) / d.tipocambio
                                    FROM
                                        detpresupuesto b
                                            INNER JOIN
                                        tranban c ON c.iddetpresup = b.id
                                            INNER JOIN
                                        compra d ON c.idreembolso = d.idreembolso
                                    WHERE
                                        d.idmoneda = a.idmoneda
                                            AND b.idpresupuesto = a.id),
                                0.00)),
                    2) AS montogastado,
                CONCAT(ROUND((IFNULL((SELECT 
                                                SUM(c.monto)
                                            FROM
                                                detpresupuesto b
                                                    INNER JOIN
                                                tranban c ON b.id = c.iddetpresup
                                                    INNER JOIN
                                                banco d ON d.id = c.idbanco
                                            WHERE
                                                d.idmoneda = a.idmoneda
                                                    AND b.idpresupuesto = a.id
                                                    AND c.anulado = 0
                                                    AND c.beneficiario NOT LIKE '%ANULA%'
                                                    AND c.concepto NOT LIKE '%ANULA%'
                                                    AND c.liquidado = 0
                                                    AND c.tipotrans != 'R'
                                                    AND c.beneficiario NOT LIKE '%REINGRE%'
                                                    AND c.iddocliquida = 0),
                                        0.00) - IFNULL((SELECT 
                                                SUM(c.monto)
                                            FROM
                                                detpresupuesto b
                                                    INNER JOIN
                                                tranban c ON b.id = c.iddetpresup
                                                    INNER JOIN
                                                banco d ON d.id = c.idbanco
                                            WHERE
                                                d.idmoneda = a.idmoneda
                                                    AND b.idpresupuesto = a.id
                                                    AND c.tipotrans = 'R'
                                                    AND c.iddocliquida = 0),
                                        0.00) + IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(c.monto) * c.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    tranban c ON b.id = c.iddetpresup
                                                        INNER JOIN
                                                    banco d ON d.id = c.idbanco
                                                WHERE
                                                    d.idmoneda != a.idmoneda
                                                        AND b.idpresupuesto = a.id
                                                        AND c.anulado = 0
                                                        AND c.beneficiario NOT LIKE '%ANULA%'
                                                        AND c.concepto NOT LIKE '%ANULA%'
                                                        AND c.liquidado = 0
                                                        AND c.tipotrans != 'R'
                                                        AND c.beneficiario NOT LIKE '%REINGRE%'
                                                        AND c.iddocliquida = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(c.monto) / c.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    tranban c ON b.id = c.iddetpresup
                                                        INNER JOIN
                                                    banco d ON d.id = c.idbanco
                                                WHERE
                                                    d.idmoneda != a.idmoneda
                                                        AND b.idpresupuesto = a.id
                                                        AND c.anulado = 0
                                                        AND c.beneficiario NOT LIKE '%ANULA%'
                                                        AND c.concepto NOT LIKE '%ANULA%'
                                                        AND c.liquidado = 0
                                                        AND c.tipotrans != 'R'
                                                        AND c.beneficiario NOT LIKE '%REINGRE%'
                                                        AND c.iddocliquida = 0),
                                            0.00)) - IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(c.monto) * c.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    tranban c ON b.id = c.iddetpresup
                                                        INNER JOIN
                                                    banco d ON d.id = c.idbanco
                                                WHERE
                                                    d.idmoneda != a.idmoneda
                                                        AND b.idpresupuesto = a.id
                                                        AND c.tipotrans = 'R'
                                                        AND c.iddocliquida = 0),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(c.monto) / c.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    tranban c ON b.id = c.iddetpresup
                                                        INNER JOIN
                                                    banco d ON d.id = c.idbanco
                                                WHERE
                                                    d.idmoneda != a.idmoneda
                                                        AND b.idpresupuesto = b.id
                                                        AND c.tipotrans = 'R'
                                                        AND c.iddocliquida = 0),
                                            0.00)) + IFNULL((SELECT 
                                                SUM(c.isr)
                                            FROM
                                                detpresupuesto b
                                                    INNER JOIN
                                                compra c ON b.id = c.ordentrabajo
                                            WHERE
                                                c.idmoneda = a.idmoneda
                                                    AND b.idpresupuesto = a.id),
                                        0.00) + IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(c.isr) * c.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    compra c ON b.id = c.ordentrabajo
                                                WHERE
                                                    c.idmoneda != a.idmoneda
                                                        AND b.idpresupuesto = a.id),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(c.isr) / c.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    compra c ON b.id = c.ordentrabajo
                                                WHERE
                                                    c.idmoneda != a.idmoneda
                                                        AND b.idpresupuesto = a.id),
                                            0.00)) + IFNULL((SELECT 
                                                SUM(d.isr)
                                            FROM
                                                detpresupuesto b
                                                    INNER JOIN
                                                tranban c ON c.iddetpresup = b.id
                                                    INNER JOIN
                                                compra d ON c.idreembolso = d.idreembolso
                                            WHERE
                                                d.idmoneda = a.idmoneda
                                                    AND b.idpresupuesto = a.id),
                                        0.00) + IF(f.eslocal = 1,
                                    IFNULL((SELECT 
                                                    SUM(d.isr) * d.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    tranban c ON c.iddetpresup = b.id
                                                        INNER JOIN
                                                    compra d ON c.idreembolso = d.idreembolso
                                                WHERE
                                                    d.idmoneda = a.idmoneda
                                                        AND b.idpresupuesto = a.id),
                                            0.00),
                                    IFNULL((SELECT 
                                                    SUM(d.isr) / d.tipocambio
                                                FROM
                                                    detpresupuesto b
                                                        INNER JOIN
                                                    tranban c ON c.iddetpresup = b.id
                                                        INNER JOIN
                                                    compra d ON c.idreembolso = d.idreembolso
                                                WHERE
                                                    d.idmoneda = a.idmoneda
                                                        AND b.idpresupuesto = a.id),
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
                    AND b.idestatuspresupuesto IN (3 , 5)";
    $general = $db->getQuery($query)[0];

    

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS fecha";
    $generales = $db->getQuery($query)[0];

    print json_encode(['general' => $general, 'ordentrabajo' => $ordentrabajo, 'generales' => $generales]);
});


function getPagos($ots, $db, $esmultiple) {
    if($esmultiple){
        $cntOts = count($ots);
    } else {
        $cntOts = 1;
    }

    for ($i = 0; $i < $cntOts; $i++) {
        if ($esmultiple) {
            $ot = $ots[$i];
        } else {
            $ot = $ots;
        }

        // reembolsos atados a la orden
        $query = "SELECT 
                a.id,
                DATE_FORMAT(a.finicio, '%d/%m/%y') AS fecha,
                SUBSTRING(a.beneficiario, 1, 20) AS proveedor,
                CONCAT('REE-', LPAD(a.id, 5, '0')) AS factura,
                ROUND(SUM(b.totfact), 2) AS monto,
                ROUND(SUM(b.isr), 2) AS isr,
                SUBSTRING(b.conceptomayor, 1, 70) AS concepto,
                e.simbolo AS moneda,
                e.id AS idmoneda,
                ROUND(b.tipocambio, 2) AS tipocambio
            FROM
                reembolso a
                    INNER JOIN
                compra b ON b.idreembolso = a.id
                    INNER JOIN
                moneda e ON b.idmoneda = e.id
            WHERE
                a.ordentrabajo = $ot->id
                ORDER BY a.finicio";
        $reembolsos = $db->getQuery($query);

        // traer compras individuales
        $query = "SELECT 
            DATE_FORMAT(a.fechafactura, '%d/%m/%y') AS fecha,
            SUBSTRING(c.nombre, 1, 20) AS proveedor,
            a.documento AS factura,
            ROUND(a.totfact, 2) AS monto,
            ROUND(a.isr, 2) AS isr,
            ROUND(a.tipocambio, 2) AS tipocambio,
            SUBSTRING(a.conceptomayor, 1, 70) AS concepto,
            d.simbolo AS moneda,
            d.id AS idmoneda,
            IF(a.idtipofactura > 8, TRUE, NULL) AS nc
        FROM
            compra a
                INNER JOIN
            tipofactura b ON a.idtipofactura = b.id
                INNER JOIN
            proveedor c ON a.idproveedor = c.id
                INNER JOIN
            moneda d ON a.idmoneda = d.id
        WHERE
            a.ordentrabajo = $ot->id
                AND (SELECT 
                    COUNT(b.id)
                FROM
                    detpagocompra b
                WHERE
                    b.idcompra = a.id) = 0
                ORDER BY fechafactura ";
        $compras = $db->getQuery($query);

        $cntRee = count($reembolsos); 

        for ($j = 0; $j < $cntRee; $j++) {
            $reembolso = $reembolsos[$j];

            if ($reembolso->id > 0) {
                // cheques que cancelan el reembolso
                $query = "SELECT 
                        a.id,
                        DATE_FORMAT(a.fecha, '%d/%m/%y') AS fecha,
                        CONCAT(SUBSTRING(b.siglas, 1, 2),
                                '-',
                                a.tipotrans,
                                '-',
                                SUBSTRING(b.siglas, 4, 5),
                                '-',
                                a.numero) AS datosbanco,
                        SUBSTRING(a.beneficiario, 1, 20) AS beneficiario,
                        c.monto,
                        ROUND(a.tipocambio, 2) AS tipocambio,
                        a.anticipo,
                        SUBSTRING(a.concepto, 1, 70) AS concepto,
                        d.simbolo AS moneda,
                        d.id AS idmoneda,
                        NULL AS compras
                    FROM
                        tranban a
                            INNER JOIN
                        banco b ON a.idbanco = b.id
                            INNER JOIN
                        dettranreem c ON c.idtranban = a.id
                            INNER JOIN
                        moneda d ON b.idmoneda = d.id
                    WHERE
                        a.iddetpresup = $ot->id
                            AND c.idreembolso = $reembolso->id";
                $reembolso->cheques = $db->getQuery($query);
                
                // empujar cheques con reembolso a array de cheques
                array_push($compras, $reembolso);
            }   
        }

        // traer cheques individuales
        $query = "SELECT 
            DATE_FORMAT(a.fecha, '%d/%m/%y') AS fecha,
            CONCAT(SUBSTRING(b.siglas, 1, 2),
                    '-',
                    a.tipotrans,
                    '-',
                    SUBSTRING(b.siglas, 4, 5),
                    '-',
                    a.numero) AS datosbanco,
            IF(iddocliquida > 0, SUBSTRING(a.beneficiario, 9, 20), SUBSTRING(a.beneficiario, 1, 24)) AS beneficiario,
            a.monto,
            ROUND(a.tipocambio, 2) AS tipocambio,
            a.anticipo,
            SUBSTRING(a.concepto, 1, 70) AS concepto,
            c.simbolo AS moneda,
            c.id AS idmoneda,
            IF(a.anulado = 1 OR liquidado = 1, TRUE, NULL) AS anulado,
            IF(iddocliquida > 0, TRUE, NULL) AS reintegro
        FROM
            tranban a
                INNER JOIN
            banco b ON a.idbanco = b.id
                INNER JOIN
            moneda c ON b.idmoneda = c.id
        WHERE
            a.iddetpresup = $ot->id
                AND (SELECT 
                    COUNT(b.id)
                FROM
                    detpagocompra b
                WHERE
                    b.idtranban = a.id) = 0
                AND (SELECT 
                    COUNT(b.id)
                FROM
                    doctotranban b
                WHERE
                    b.idtranban = a.id) = 0
            ORDER BY fecha ";
        $cheques = $db->getQuery($query);

        // cheques con factura
        $query = "SELECT 
            a.id,
            DATE_FORMAT(a.fecha, '%d/%m/%y') AS fecha,
            CONCAT(SUBSTRING(b.siglas, 1, 2),
                    '-',
                    a.tipotrans,
                    '-',
                    SUBSTRING(b.siglas, 4, 5),
                    '-',
                    a.numero) AS datosbanco,
            SUBSTRING(a.beneficiario, 1, 24) AS beneficiario,
            a.monto,
            ROUND(a.tipocambio, 2) AS tipocambio,
            a.anticipo,
            SUBSTRING(a.concepto, 1, 70) AS concepto,
            c.simbolo AS moneda,
            c.id AS idmoneda,
            NULL AS compras
        FROM
            tranban a
                INNER JOIN
            banco b ON a.idbanco = b.id
                INNER JOIN
            moneda c ON b.idmoneda = c.id
        WHERE
            a.iddetpresup = $ot->id
                AND (SELECT 
                    COUNT(b.id)
                FROM
                    doctotranban b
                WHERE
                    b.idtranban = a.id
                        AND b.idtipodoc = 2) = 0
                AND (SELECT 
                    COUNT(b.id) 
                FROM 
                    detpagocompra b 
                WHERE 
                    b.idtranban = a.id) > 0
                    ORDER BY fecha";
        $chequesfac = $db->getQuery($query);

        $cntChq = count($chequesfac);

        for ($j = 0; $j < $cntChq; $j++) {
        $cheque = $chequesfac[$j];
        
        // facturas por medio de id de cheques
        $query = "SELECT 
                DATE_FORMAT(a.fechafactura, '%d/%m/%y') AS fecha,
                SUBSTRING(c.nombre, 1, 20) AS proveedor,
                a.documento AS factura,
                ROUND(a.totfact, 2) AS monto,
                ROUND(a.isr, 2) AS isr,
                ROUND(a.tipocambio, 2) AS tipocambio,
                SUBSTRING(a.conceptomayor, 1, 70) AS concepto,
                e.simbolo AS moneda,
                e.id AS idmoneda,
                IF(a.idtipofactura > 8, TRUE, NULL) AS nc
            FROM
                compra a
                    INNER JOIN
                tipofactura b ON a.idtipofactura = b.id
                    INNER JOIN
                proveedor c ON a.idproveedor = c.id
                    INNER JOIN
                detpagocompra d ON d.idcompra = a.id
                    INNER JOIN
                moneda e ON a.idmoneda = e.id
            WHERE
                a.ordentrabajo = $ot->id
                    AND d.idtranban = $cheque->id
                    ORDER BY a.fechafactura ";
        $cheque->compras = $db->getQuery($query);
        
        // empujar cheques con factura a array de cheques
        array_push($cheques, $cheque);
        }

        // insertar en cada ot sus cheques y compras
        $ot->cheques = $cheques;
        $ot->compras = $compras;
    }

    function getTotales($orden, $db, $esmultiple) {
        $sot = array();
        $savance = array();
        $sgastado = array();

        if ($esmultiple) {
            $cntOts = count($orden->ots);
        } else {
            $cntOts = 1;
        }

        for ($i = 0; $i < $cntOts; $i++) {
                if ($esmultiple) {
                    $ot = $orden->ots[$i];
                } else {
                    $ot = $orden;
                }

                $tipocambioprov = $db->getOneField("SELECT IFNULL(IFNULL((SELECT tipocambio FROM compra WHERE ordentrabajo = $ot->id AND tipocambio > 1 LIMIT 1),
                (SELECT tipocambio FROM tranban WHERE iddetpresup = $ot->id AND tipocambio > 1 LIMIT 1)), 
                (SELECT tipocambio FROM detpresupuesto WHERE id = $ot->id))");

                // crear array para sumas
                $scompra = array();
                $sisr = array();
                $stran = array();
                
                // traer monto, moneda y tipocambio de compra
                $query = "SELECT id, totfact, idmoneda, tipocambio, isr FROM compra WHERE ordentrabajo = $ot->id AND idreembolso = 0 
                AND id NOT IN(SELECT idcompra FROM detnotacompra) AND idtipofactura < 8
                UNION ALL SELECT b.id, b.totfact, b.idmoneda, b.tipocambio, b.isr FROM reembolso a 
                INNER JOIN compra b ON b.idreembolso = a.id WHERE a.ordentrabajo = $ot->id";
                $tcompras = $db->getQuery($query);
        
                $cntCompras = count($tcompras);
        
                for ($j = 0; $j < $cntCompras; $j++){
                    $compra = $tcompras[$j];
                    $tc = $compra->tipocambio > 1 ? $compra->tipocambio : $tipocambioprov;
                    // si moneda de ot diferente a moneda de compra usar t.c
                    if ($ot->idmoneda != $compra->idmoneda) {
                        // si moneda es local multiplicar 
                        if ($ot->idmoneda == 1) {
                            $monto = $compra->totfact * $tc;
                            $montoisr = $compra->isr * $tc;
                        // si moneda no es local divir
                        } else {
                            $monto = $compra->totfact / $tc;
                            $montoisr = $compra->isr / $tc;
                        }
                    // insertar monto
                    } else {
                        $monto = $compra->totfact;
                        $montoisr = $compra->isr;
                    }
                    // empujar montos a un array
                    array_push($scompra, $monto);
                    array_push($sisr, $montoisr);
                }
                
                // sumar montos de array
                $tcompra = array_sum($scompra);
                $tisr = array_sum($sisr);
        
                // traer monto y tipocambio de transaccion bancaria
                $query = "SELECT a.monto, a.tipocambio, b.idmoneda FROM tranban a INNER JOIN banco b ON a.idbanco = b.id 
                WHERE iddetpresup = $ot->id AND (SELECT COUNT(b.id) FROM doctotranban b WHERE b.idtranban = a.id AND b.idtipodoc = 2) = 0 
                AND a.liquidado = 0 AND a.iddocliquida = 0 AND a.anulado = 0 
                UNION ALL 
                SELECT b.monto, a.tipocambio, c.idmoneda FROM tranban a INNER JOIN dettranreem b ON b.idtranban = a.id 
                INNER JOIN banco c ON a.idbanco = c.id WHERE a.iddetpresup = $ot->id";
                // print $query;
                $trans = $db->getQuery($query);

                $cntTranas = count($trans);
        
                for ($j = 0; $j < $cntTranas; $j++){
                    $tran = $trans[$j];
                    $tc = $tran->tipocambio > 0 ? $tran->tipocambio : $tipocambioprov;
                    // si moneda de ot diferente a moneda de cheque usar t.c
                    if ($ot->idmoneda != $tran->idmoneda) {
                        // si moneda es local multiplicar 
                        if ($ot->idmoneda === 1) {
                            $monto = $tran->monto * $tc;
                        // si moneda no es local divir
                        } else {
                            $monto = $tran->monto / $tc;
                        }
                    // insertar monto
                    } else {
                        $monto = $tran->monto;
                    }
                    // empujar montos a un array
                    array_push($stran, $monto);
                }
        
                // sumar array de transacciones
                $ttran = array_sum($stran);
        
                // operacion para total gasto en OT
                $gastado = $ttran + $tisr;
                
                // opracion para proventaje de avance en OT
                $avance = (($ttran + $tisr) * 100) / $ot->monto;

                // operacion diferencia
                $diferencia = $ot->monto - $gastado;

                // cuanto afectara otm
                if ($esmultiple) {
                    if ($ot->idmoneda == $orden->idmoneda){
                        $afectar = $gastado;
                    } else {
                        if ($orden->idmoneda == 1) {
                            $afectar = $gastado * $ot->tipocambio;
                        } else {
                            $afectar = $gastado / $ot->tipocambio;
                        }
                    }
                }
        
                // insertar valores a ot
                $ot->totgastado = number_format($gastado, 2, '.', ',');
                $ot->avance = number_format($avance, 2, '.', ',');
                $ot->tcheques = number_format($ttran, 2, '.', ',');
                $ot->tcompras = number_format($tcompra, 2, '.', ',');
                $ot->isr = number_format($tisr, 2, '.', ',');
                $ot->diferencia = number_format($diferencia, 2, '.', ',');

                if ($esmultiple) {
                    $ot->afectar = number_format($afectar, 2, '.', ',');
                }
                
                if (!$esmultiple) {
                    return;
                }
            // si moneda de ot diferente a moneda otm usar t.c
            if ($ot->idmoneda != $orden->idmoneda) {
                // si moneda es local multiplicar 
                if ($orden->idmoneda == 1) {
                    $monto = $ot->monto * $ot->tipocambio;
                // si moneda no es local divir
                } else {
                    $monto = $ot->monto / $ot->tipocambio;
                }
            // insertar monto
            } else {
                $monto = $ot->monto;
            }
            // empujar montos a un array
            array_push($sot, $monto);
        
            // si moneda de ot diferente a moneda omt usar t.c
            if ($ot->idmoneda != $orden->idmoneda) {
                // si moneda es local multiplicar 
                if ($orden->idmoneda == 1) {
                    $monto = $gastado * $ot->tipocambio;
                // si moneda no es local divir
                } else {
                    $monto = $gastado / $ot->tipocambio;
                }
            // insertar monto
            } else {
                $monto = $gastado;
            }
            // empujar montos a un array
            array_push($sgastado, $monto);

            $montog = array_sum($sot);
            $gastadog = array_sum($sgastado);
            $avanceg = ($gastadog * 100) / $montog;
            $diferencia = $montog - $gastadog;
        
            $orden->gastado = number_format($gastadog, 2, '.', ',');
            $orden->monto = number_format($montog, 2, '.', ',');
            $orden->avance = number_format($avanceg, 2, '.', ',');
            $orden->diferencia = number_format($diferencia, 2, '.', ',');    
        }
    }
}

$app->run();