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
                ROUND(a.monto, 2) AS monto
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

    getPagos($orden, $db, false, null);
    getTotales($orden, $db, false, null);

    print json_encode(['orden' => $orden]);
});

// nueva version
$app->post('/avanceotm', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $letra = new stdClass();

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $ids = array();

    $query = "SELECT 
                a.id AS otm,
                DATE_FORMAT(fechacreacion, '%d/%m/%Y') AS fecha,
                b.nomproyecto AS proyecto,
                c.nomempresa AS empresa,
                d.desctipogast AS tipogasto,
                e.simbolo AS moneda,
                SUBSTRING(a.notas, 1, 20) AS concepto,
                a.notas,
                a.idmoneda,
                f.iniciales AS creador,
                IF(a.idestatuspresupuesto = 5, true, null) AS terminada,
                g.iniciales AS modificador,
                DATE_FORMAT(a.fechamodificacion, '%d/%m/%Y') AS modificacion
            FROM
                presupuesto a
                    INNER JOIN
                proyecto b ON a.idproyecto = b.id
                    INNER JOIN
                empresa c ON a.idempresa = c.id
                    INNER JOIN
                tipogasto d ON a.idtipogasto = d.id
                    INNER JOIN
                moneda e ON a.idmoneda = e.id
                    INNER JOIN
                usuario f ON a.idusuario = f.id
                    LEFT JOIN 
                usuario g ON a.lastuser = g.id
            WHERE
                a.id = $d->idot";
            $orden = $db->getQuery($query)[0];

            $query = "SELECT 
                a.id, 
                CONCAT(a.idpresupuesto, '-', a.correlativo) AS numero,
                IFNULL(b.nombre, c.nombre) AS proveedor,
                d.descripcion AS subtipogasto,
                a.monto,
                a.notas,
                e.simbolo AS moneda,
                a.idmoneda AS idmoneda,
                a.tipocambio,
                DATE_FORMAT(a.fechamodificacion, '%d/%m/%Y') AS fecha,
                f.iniciales AS usuario
            FROM
                detpresupuesto a
                    LEFT JOIN
                proveedor b ON a.idproveedor = b.id
                    AND a.origenprov = 1
                    LEFT JOIN
                beneficiario c ON a.idproveedor = c.id
                    AND a.origenprov = 2
                    INNER JOIN
                subtipogasto d ON a.idsubtipogasto = d.id
                    INNER JOIN
                moneda e ON a.idmoneda = e.id
                    LEFT JOIN
                usuario f ON a.lastuser = f.id
            WHERE
                a.idpresupuesto = $d->idot
                    AND a.idestatuspresupuesto IN(1, 2, 3, 5)
            ORDER BY a.correlativo";
            $orden->ots = $db->getQuery($query);

            $cntsOts = count($orden->ots);

            for ($i = 0; $i < $cntsOts; $i++) {
                $ot = $orden->ots[$i];
                array_push($ids, $ot->id);
            }

            getPagos($orden, $db, true, $ids);
            getTotales($orden, $db, true, $ids);

            $iniciales = "";
            $palabras = explode(" ", $orden->tipogasto);

            foreach ($palabras as $palabra) {
                $iniciales .= substr($palabra, 0, 1);
            }

            $orden->iniciales = strtoupper($iniciales);

            print json_encode(['fechas' => $letra, 'orden' => $orden]);
});

function getPagos($orden, $db, $esmultiple, $ids = null) {
    $cntsOts = $esmultiple ? count($orden->ots) : 1;

    // crear variable para id's de ot's si no es multiple usar id de orden
    $ids_str = $esmultiple ? implode(',', $ids) : $orden->id;

    $cheques = array();
    $compras = array();
    $reembolsos = array();

    // cheques
    $query = "SELECT
    -- cheques sin factura/reembolso
                a.id,
                a.fecha AS fechaOrd,
                DATE_FORMAT(a.fecha, '%d/%m/%y') AS fecha,
                CONCAT(SUBSTRING(b.siglas, 1, 2),
                        '-',
                        a.tipotrans,
                        '-',
                        SUBSTRING(b.siglas, 4, 5),
                        '-',
                        a.numero) AS datosbanco,
                SUBSTRING(a.beneficiario, 1, 22) AS beneficiario,
                a.monto,
                ROUND(a.tipocambio, 2) AS tipocambio,
                a.anticipo,
                SUBSTRING(a.concepto, 1, 45) AS concepto,
                c.simbolo AS moneda,
                c.id AS idmoneda,
                IF(a.anulado = 1 OR liquidado = 1,
                    TRUE,
                    NULL) AS anulado,
                IF(a.iddocliquida > 0 OR a.tipotrans = 'R',
                    TRUE,
                    NULL) AS reintegro,
                a.iddetpresup AS ot
            FROM
                tranban a
                    INNER JOIN
                banco b ON a.idbanco = b.id
                    INNER JOIN
                moneda c ON b.idmoneda = c.id
            WHERE
                a.iddetpresup IN ($ids_str)
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
            -- cheques con factura
            UNION ALL SELECT 
                a.id,
                a.fecha AS fechaOrd,
                DATE_FORMAT(a.fecha, '%d/%m/%y') AS fecha,
                CONCAT(SUBSTRING(b.siglas, 1, 2),
                        '-',
                        a.tipotrans,
                        '-',
                        SUBSTRING(b.siglas, 4, 5),
                        '-',
                        a.numero) AS datosbanco,
                SUBSTRING(a.beneficiario, 1, 22) AS beneficiario,
                a.monto,
                ROUND(a.tipocambio, 2) AS tipocambio,
                a.anticipo,
                SUBSTRING(a.concepto, 1, 45) AS concepto,
                c.simbolo AS moneda,
                c.id AS idmoneda,
                IF(a.anulado = 1 OR liquidado = 1,
                    TRUE,
                    NULL) AS anulado,
                IF(a.iddocliquida > 0 OR a.tipotrans = 'R',
                    TRUE,
                    NULL) AS reintegro,
                a.iddetpresup AS ot
            FROM
                tranban a
                    INNER JOIN
                banco b ON a.idbanco = b.id
                    INNER JOIN
                moneda c ON b.idmoneda = c.id
            WHERE
                a.iddetpresup IN ($ids_str)
                    AND (SELECT 
                        COUNT(b.id)
                    FROM
                        doctotranban b
                    WHERE
                        b.idtranban = a.id AND b.idtipodoc = 2) = 0
                    AND (SELECT 
                        COUNT(b.id)
                    FROM
                        detpagocompra b
                    WHERE
                        b.idtranban = a.id) > 0 
            -- cheques con reembolso
            UNION ALL SELECT 
                a.id,
                a.fecha AS fechaOrd,
                DATE_FORMAT(a.fecha, '%d/%m/%y') AS fecha,
                CONCAT(SUBSTRING(b.siglas, 1, 2),
                        '-',
                        a.tipotrans,
                        '-',
                        SUBSTRING(b.siglas, 4, 5),
                        '-',
                        a.numero) AS datosbanco,
                SUBSTRING(a.beneficiario, 1, 22) AS beneficiario,
                a.monto,
                ROUND(a.tipocambio, 2) AS tipocambio,
                a.anticipo,
                SUBSTRING(a.concepto, 1, 45) AS concepto,
                c.simbolo AS moneda,
                c.id AS idmoneda,
                IF(a.anulado = 1 OR liquidado = 1,
                    TRUE,
                    NULL) AS anulado,
                IF(a.iddocliquida > 0 OR a.tipotrans = 'R',
                    TRUE,
                    NULL) AS reintegro,
                a.iddetpresup AS ot
            FROM
                tranban a
                    INNER JOIN
                banco b ON a.idbanco = b.id
                    INNER JOIN
                moneda c ON b.idmoneda = c.id
            WHERE
                a.iddetpresup IN ($ids_str)
                    AND ((SELECT 
                        COUNT(b.id)
                    FROM
                        doctotranban b
                    WHERE
                        b.idtranban = a.id AND b.idtipodoc = 2) > 0
                    OR (SELECT 
                        COUNT(b.id)
                    FROM
                        dettranreem b
                    WHERE
                        b.idtranban = a.id) > 0)
            ORDER BY fechaOrd";
    $cheques = $db->getQuery($query);

    $cntsChq = count($cheques);

    // insertar facturas/reembolsos en cheques
    for ($i = 0; $i < $cntsChq; $i++) {
        $cheque = $cheques[$i];
            
        // traer facturas por medio de id de cheques
        $query = "SELECT 
                    a.id,
                    DATE_FORMAT(a.fechafactura, '%d/%m/%y') AS fecha,
                    SUBSTRING(c.nombre, 1, 22) AS proveedor,
                    a.documento AS factura,
                    ROUND(a.totfact, 2) AS monto,
                    ROUND(a.isr, 2) AS isr,
                    ROUND(a.tipocambio, 2) AS tipocambio,
                    SUBSTRING(LOWER(a.conceptomayor), 1, 48) AS concepto,
                    e.simbolo AS moneda,
                    e.id AS idmoneda,
                    IF(a.idtipofactura > 8, TRUE, NULL) AS nc,
                    a.ordentrabajo AS ot
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
                    d.idtranban = $cheque->id
                        ORDER BY a.fechafactura";
        $cheque->compras = $db->getQuery($query);

        // traer reembolso por medio de cheques
        $query = "SELECT 
                    b.id,
                    DATE_FORMAT(b.finicio, '%d/%m/%y') AS fecha,
                    SUBSTRING(b.beneficiario, 1, 22) AS proveedor,
                    CONCAT('REE-', LPAD(b.id, 5, '0')) AS factura,
                    ROUND(SUM(a.totfact), 2) AS monto,
                    ROUND(SUM(a.isr), 2) AS isr,
                    SUBSTRING(a.conceptomayor, 1, 48) AS concepto,
                    c.simbolo AS moneda,
                    ROUND(a.tipocambio, 2) AS tipocambio,
                    a.ordentrabajo AS ot
                FROM
                    compra a
                        INNER JOIN
                    reembolso b ON a.idreembolso = b.id
                        INNER JOIN
                    moneda c ON a.idmoneda = c.id
                        INNER JOIN
                    dettranreem d ON d.idreembolso = b.id
                WHERE
                    d.idtranban = $cheque->id
                HAVING SUM(a.totfact) IS NOT NULL";
        $cheque->reembolsos = $db->getQUery($query);

        if ($cheque->reembolsos > 0) {
            $cntReem = count($cheque->reembolsos);

            // compras de reembolso
            for ($j = 0; $j < $cntReem; $j++) {
                $reembolso = $cheque->reembolsos[$j];
                $query = "SELECT 
                            a.id,
                            DATE_FORMAT(a.fechafactura, '%d/%m/%y') AS fecha,
                            SUBSTRING(IFNULL(c.nombre, a.proveedor), 1, 22) AS proveedor,
                            a.documento AS factura,
                            ROUND(a.totfact, 2) AS monto,
                            ROUND(a.isr, 2) AS isr,
                            ROUND(a.tipocambio, 2) AS tipocambio,
                            SUBSTRING(a.conceptomayor, 1, 48) AS concepto,
                            d.simbolo AS moneda,
                            d.id AS idmoneda,
                            IF(a.idtipofactura > 8, TRUE, NULL) AS nc,
                            a.ordentrabajo AS ot
                        FROM
                            compra a
                                INNER JOIN
                            tipofactura b ON a.idtipofactura = b.id
                                LEFT JOIN
                            proveedor c ON a.idproveedor = c.id
                                INNER JOIN
                            moneda d ON a.idmoneda = d.id
                        WHERE
                            a.idreembolso = $reembolso->id
                                AND (SELECT 
                                    COUNT(b.id)
                                FROM
                                    detpagocompra b
                                WHERE
                                    b.idcompra = a.id) = 0
                                ORDER BY fechafactura";
                $reembolso->compras = $db->getQuery($query);
            }
        }
    }

    // traer compras individuales
    $query = "SELECT 
                a.id, 
                a.fechafactura AS fechaOrd,
                DATE_FORMAT(a.fechafactura, '%d/%m/%y') AS fecha,
                SUBSTRING(IFNULL(c.nombre, a.proveedor), 1, 22) AS proveedor,
                a.documento AS factura,
                ROUND(a.totfact, 2) AS monto,
                ROUND(a.isr, 2) AS isr,
                ROUND(a.tipocambio, 2) AS tipocambio,
                SUBSTRING(a.conceptomayor, 1, 48) AS concepto,
                d.simbolo AS moneda,
                d.id AS idmoneda,
                IF(a.idtipofactura > 8, TRUE, NULL) AS nc,
                a.ordentrabajo AS ot
            FROM
                compra a
                    INNER JOIN
                tipofactura b ON a.idtipofactura = b.id
                    LEFT JOIN
                proveedor c ON a.idproveedor = c.id
                    INNER JOIN
                moneda d ON a.idmoneda = d.id
            WHERE
                a.ordentrabajo IN($ids_str)
                    AND (SELECT 
                        COUNT(b.id)
                    FROM
                        detpagocompra b
                    WHERE
                        b.idcompra = a.id) = 0
                    AND a.idreembolso = 0
                    AND (SELECT 
                        COUNT(b.id)
                    FROM 
                        doctotranban b
                    WHERE 
                        b.iddocto = a.id 
                    AND b.idtipodoc = 1) = 0
                    ORDER BY fechafactura";
    $compras = $db->getQuery($query);

    // reembolsos atados a la orden
    $query = "SELECT 
                b.id,
                b.finicio AS fechaOrd,
                DATE_FORMAT(b.finicio, '%d/%m/%y') AS fecha,
                SUBSTRING(b.beneficiario, 1, 22) AS proveedor,
                CONCAT('REE-', LPAD(b.id, 5, '0')) AS factura,
                ROUND(SUM(a.totfact), 2) AS monto,
                ROUND(SUM(a.isr), 2) AS isr,
                SUBSTRING(a.conceptomayor, 1, 48) AS concepto,
                c.simbolo AS moneda,
                ROUND(a.tipocambio, 2) AS tipocambio,
                a.ordentrabajo AS ot
            FROM
                compra a
                    INNER JOIN
                reembolso b ON a.idreembolso = b.id
                    INNER JOIN
                moneda c ON a.idmoneda = c.id
            WHERE
                b.ordentrabajo IN($ids_str)
                AND (SELECT 
                    COUNT(d.id)
                FROM 
                    dettranreem d
                WHERE 
                    d.idreembolso = b.id) = 0
            HAVING SUM(a.totfact) IS NOT NULL";
    $reembolsos = $db->getQuery($query);

    $cntRee = count($reembolsos); 

    for ($i = 0; $i < $cntRee; $i++) {
        $reembolso = $reembolsos[$j];

        // traer compras de reembolso 
        $query = "SELECT 
                    a.id, 
                    DATE_FORMAT(a.fechafactura, '%d/%m/%y') AS fecha,
                    SUBSTRING(IFNULL(c.nombre, a.proveedor), 1, 22) AS proveedor,
                    a.documento AS factura,
                    ROUND(a.totfact, 2) AS monto,
                    ROUND(a.isr, 2) AS isr,
                    ROUND(a.tipocambio, 2) AS tipocambio,
                    SUBSTRING(a.conceptomayor, 1, 48) AS concepto,
                    d.simbolo AS moneda,
                    d.id AS idmoneda,
                    IF(a.idtipofactura > 8, TRUE, NULL) AS nc,
                    a.ordentrabajo AS ot
                FROM
                    compra a
                        INNER JOIN
                    tipofactura b ON a.idtipofactura = b.id
                        LEFT JOIN
                    proveedor c ON a.idproveedor = c.id
                        INNER JOIN
                    moneda d ON a.idmoneda = d.id
                WHERE
                    a.idreembolso = $reembolso->id
                        AND (SELECT 
                            COUNT(b.id)
                        FROM
                            detpagocompra b
                        WHERE
                            b.idcompra = a.id) = 0
                        ORDER BY fechafactura";
        $reembolso->compras = $db->getQuery($query);
    }

    $cntsCompras = count($compras);

    for ($i = 0; $i < $cntsOts; $i++) {
        $ot = $esmultiple ? $orden->ots[$i] : $orden;

        $compras_ot = array();
        $transacciones_ot = array();
        $reembolsos_ot = array();

        // compras
        for ($j = 0; $j < $cntsCompras; $j++) {
            $compra = $compras[$j];
            if ($compra->ot == $ot->id) {
                array_push($compras_ot, $compra);
            }
        }

        // transacciones 
        for ($j = 0; $j < $cntsChq; $j++) {
            $tran = $cheques[$j];
            if ($tran->ot == $ot->id) {
                array_push($transacciones_ot, $tran);
            }
        }

        // reembolsos
        for ($j = 0; $j < $cntRee; $j++) {
            $reembolso = $reembolsos[$j];
            if ($reembolso->ot == $ot->id) {
                array_push($reembolsos_ot, $reembolso);
            }
        }

        // ordenar arrays
        usort($compras_ot, 'compararFechas');
        usort($transacciones_ot, 'compararFechas');
        usort($reembolsos_ot, 'compararFechas');

        // enviar arrays a select general
        $ot->compras = $compras_ot;
        $ot->cheques = $transacciones_ot;
        $ot->reembolsos = $reembolsos_ot;
    }

    return;
}

function getTotales($orden, $db, $esmultiple, $ids = null) {
    // variables generales para OTM
    $gastos_ot = array();
    $montos_ot = array();

    // crear variable para id's de ot's si no es multiple usar id de orden
    $ids_str = $esmultiple ? implode(',', $ids) : $orden->id;

    // contar cuantas ots tiene otm si no es multiple usar 1
    $cntsOts = $esmultiple ? count($orden->ots) : 1;

    // traer tipo cambio proveedor, primero de compra, transaccion y por ultimo de orden
    $query = "SELECT tipocambio FROM tranban WHERE iddetpresup IN($ids_str) AND tipocambio > 1";
    $tiposcambio = $db->getQuery($query);$cntsTipos = count($tiposcambio) > 0 ? count($tiposcambio) : 1;

    $cntsTipos = count($tiposcambio);

    if ($cntsTipos > 0) {
        $sumtipos = array();

        for ($i = 0; $i < $cntsTipos; $i++) {
            $tc = $tiposcambio[$i];
            array_push($sumtipos, $tc->tipocambio);
        }

        $tipocambioprov = array_sum($sumtipos) / $cntsTipos;
    } else {
        $tipocambioprov = 7.8;
    }

    // traer monto, moneda, idordentrabajo y tipocambio de compra
    $query = "SELECT id, totfact, idmoneda, tipocambio, isr, ordentrabajo AS ot FROM compra WHERE ordentrabajo IN($ids_str) AND idreembolso = 0 
    AND id NOT IN(SELECT idcompra FROM detnotacompra) AND idtipofactura < 8
    UNION ALL SELECT b.id, b.totfact, b.idmoneda, b.tipocambio, b.isr, a.ordentrabajo AS ot FROM reembolso a 
    INNER JOIN compra b ON b.idreembolso = a.id WHERE a.ordentrabajo IN($ids_str)";
    $tcompras = $db->getQuery($query);

    $cntCompras = count($tcompras);

    // traer monto y tipocambio de transaccion bancaria
    $query = "SELECT a.monto * IF(a.tipotrans = 'R', -1, 1) AS monto, a.tipocambio, b.idmoneda, a.iddetpresup AS ot FROM tranban a INNER JOIN banco b ON a.idbanco = b.id 
    WHERE a.iddetpresup IN($ids_str) AND (SELECT COUNT(b.id) FROM doctotranban b WHERE b.idtranban = a.id AND b.idtipodoc = 2) = 0 
    AND a.liquidado = 0 AND a.iddocliquida = 0 AND a.anulado = 0 
    UNION ALL 
    SELECT b.monto, a.tipocambio, c.idmoneda, a.iddetpresup AS ot FROM tranban a INNER JOIN dettranreem b ON b.idtranban = a.id 
    INNER JOIN banco c ON a.idbanco = c.id WHERE a.iddetpresup IN($ids_str)";
    $trans = $db->getQuery($query);

    $cntTranas = count($trans);

    for ($i = 0; $i < $cntsOts; $i++) {
        // crear array total de OT[$i]
        $scompra = array();
        $sisr = array();
        $stran = array();
        $tc_pro = array();

        $ot = $esmultiple ? $orden->ots[$i] : $orden;

        // loop de compras
        for ($j = 0; $j < $cntCompras; $j++) {
            $compra = $tcompras[$j];
            $tc = $compra->tipocambio > 1 ? $compra->tipocambio : $tipocambioprov;

            // validar si la orden de la compra y la orden son iguales
            if ($ot->id == $compra->ot) {
                if ($ot->idmoneda != $compra->idmoneda) {
                    if ($ot->idmoneda == 1) {
                        $monto = $compra->totfact * $tc;
                        $montoisr = $compra->isr * $tc;
                    } else {
                        $monto = $compra->totfact / $tc;
                        $montoisr = $compra->isr / $tc;
                    }
                } else {
                    $monto = $compra->totfact;
                    $montoisr = $compra->isr;
                }
                array_push($scompra, $monto);
                array_push($sisr, $montoisr);
            }
        }
        // sumas compra
        $tcompra = array_sum($scompra);
        $tisr = array_sum($sisr);

        // loop transacciones bancarias
        for ($j = 0; $j < $cntTranas; $j++) {
            $tran = $trans[$j];
            $tc = $tran->tipocambio > 1 ? $tran->tipocambio : $tipocambioprov;

            if ($ot->id == $tran->ot) {
                if ($ot->idmoneda !== $tran->idmoneda) {
                    if ($ot->idmoneda == 1) {
                        $monto = $tran->monto * $tc;
                    } else {
                        $monto = $tran->monto / $tc;
                    }
                } else {
                    $monto = $tran->monto;
                }
                array_push($stran, $monto);
                if ($tran->tipocambio > 1) {
                    array_push($tc_pro, $tran->tipocambio);
                }    
            }
        }
        // sumas transacciones bancarias
        $ttran = array_sum($stran);

        // operaciones para OTS
        $gastado = $ttran + $tisr;
        $avance = (($ttran + $tisr) * 100) / $ot->monto;
        $diferencia = $ot->monto - $gastado;
        $gasto = $gastado;

        $conteo_promedio = count($tc_pro) > 0 ? count($tc_pro) : 1;
        $sum_promedio = array_sum($tc_pro) > 0 ? array_sum($tc_pro) : 1;
        $tc_prom = $sum_promedio / $conteo_promedio;

        if ($esmultiple) {
            $tc_gasto = $tc_prom > 1 ? round($tc_prom, 5) : $ot->tipocambio;
            $tc = $ot->tipocambio > 1 ? $ot->tipocambio : $tipocambioprov;

            // efectos de cada OTS en la OTM
            if ($ot->idmoneda == $orden->idmoneda) {
                $monto = $ot->monto;
                $gasto = $gastado;
            } else {
                if ($orden->idmoneda == 1) {
                    $monto = $ot->monto * $tc;
                    $gasto = $gastado * $tc_gasto;
                } else {
                    $monto = $ot->monto / $tc;
                    $gasto = $gastado / $tc_gasto;
                }
            }

            array_push($montos_ot, $monto);
            array_push($gastos_ot, $gasto);
        }

        $ot->totgastado = number_format($gastado, 2, '.', ',');
        $ot->avance = number_format($avance, 2, '.', ',');
        $ot->tcheques = number_format($ttran, 2, '.', ',');
        $ot->tcompras = number_format($tcompra, 2, '.', ',');
        $ot->diferencia = number_format($diferencia, 2, '.', ',');
        $ot->afecta = number_format($gasto, 2, '.', ',');
        $ot->isr = number_format($tisr, 2, '.', ',');
        $ot->tcprom = round($tc_prom, 5);
    }

    if ($esmultiple) {
        $presupuesto_otm = array_sum($montos_ot);
        $gasto_otm = array_sum($gastos_ot);

        // calculos otm
        $diferencia_otm = $presupuesto_otm - $gasto_otm;
        $avance_otm = ($gasto_otm * 100) / $presupuesto_otm;

        $orden->avance = number_format($avance_otm, 2, '.', ',');
        $orden->diferencia = number_format($diferencia_otm, 2, '.', ',');
        $orden->monto = number_format($presupuesto_otm, 2, '.', ',');
        $orden->gastado = number_format($gasto_otm, 2, '.', ','); 
    }

    return;
}

function compararFechas($a, $b) {
    $fechaA = strtotime($a->fechaOrd);
    $fechaB = strtotime($b->fechaOrd);
    return $fechaA - $fechaB;
}

$app->run();