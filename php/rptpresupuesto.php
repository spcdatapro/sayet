<?php
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
    $queryPresupuesto.= "(SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio) * IF(z.tipotrans = 'C', 1, -1)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda ";
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
         LEFT JOIN (SELECT iddetpagopresup, SUM((monto * tipocambio)) AS retorno FROM tranban WHERE (tipotrans = 'R' OR (tipotrans = 'C' AND (beneficiario LIKE '%ANULA%' OR concepto LIKE '%ANULA%'))) AND iddetpagopresup <> 0 
         GROUP BY iddetpagopresup) x ON z.id = x.iddetpagopresup 
         WHERE y.id = a.id) AS pagosprogramados,
        (SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio) * IF(z.tipotrans = 'C', 1, -1)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda 
        WHERE z.iddetpresup = a.id AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULAD%'
        ) AS montoavance, a.notas, IFNULL(getMontoISROT(a.id, 0), 0.00) AS isr, e.desctipogast AS tipogasto, g.nomempresa AS empresa, DATE_FORMAT(f.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, h.nomproyecto AS proyecto, a.idpresupuesto
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
         LEFT JOIN (SELECT iddetpagopresup, SUM((monto * tipocambio)) AS retorno FROM tranban WHERE (tipotrans = 'R' OR (tipotrans = 'C' AND (beneficiario LIKE '%ANULA%' OR concepto LIKE '%ANULA%'))) AND iddetpagopresup <> 0 
         GROUP BY iddetpagopresup) x ON z.id = x.iddetpagopresup 
         WHERE y.id = a.id) AS pagosprogramados,
        (SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio) * IF(z.tipotrans = 'C', 1, -1)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda 
        WHERE z.iddetpresup = a.id AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULAD%'
        ) AS montoavance, a.notas, IFNULL(getMontoISROT(a.id, 0), 0.00) AS isr, e.desctipogast AS tipogasto, g.nomempresa AS empresa, DATE_FORMAT(f.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, h.nomproyecto AS proyecto, a.idpresupuesto
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
    $query = "SELECT ".($todos ? "idtranban, GROUP_CONCAT(DISTINCT documento SEPARATOR ', ') AS documento, SUM(totfact) AS totfact, " : "");
    $query.= "SUM(isr) AS isr
            FROM(
            SELECT z.idtranban, GROUP_CONCAT(CONCAT(y.serie, y.documento) SEPARATOR ', ') AS documento, SUM(IF(x.eslocal = 1, y.totfact, y.totfact * y.tipocambio)) AS totfact, SUM(IF(x.eslocal = 1, y.isr, y.isr * y.tipocambio)) AS isr
            FROM detpagocompra z
            INNER JOIN compra y ON y.id = z.idcompra
            INNER JOIN moneda x ON x.id = y.idmoneda
            INNER JOIN tranban w ON w.id = z.idtranban
            WHERE ".($esIdTranBan ? "z.idtranban = $filtro " : "w.iddetpresup = $filtro ")." 
            UNION
            SELECT z.idtranban, GROUP_CONCAT(CONCAT(y.serie, y.documento) SEPARATOR ', ') AS documento, SUM(IF(x.eslocal = 1, y.totfact, y.totfact * y.tipocambio)) AS totfact, SUM(IF(x.eslocal = 1, y.isr, y.isr * y.tipocambio)) AS isr 
            FROM doctotranban z
            INNER JOIN compra y ON y.id = z.iddocto
            INNER JOIN moneda x ON x.id = y.idmoneda
            INNER JOIN tranban w ON w.id = z.idtranban
            WHERE ".($esIdTranBan ? "z.idtranban = $filtro " : "w.iddetpresup = $filtro ")." AND z.idtipodoc = 1 AND 
            z.iddocto NOT IN(SELECT idcompra FROM detpagocompra WHERE idtranban IN(".($esIdTranBan ? $filtro : "SELECT id FROM tranban WHERE iddetpresup = $filtro")."))
            UNION
            SELECT z.idtranban, GROUP_CONCAT(DISTINCT CONCAT(IF(y.idtiporeembolso = 1, 'REE', 'CC'), y.id) SEPARATOR ', ') AS documento, SUM(IF(w.eslocal = 1, x.totfact, x.totfact * x.tipocambio)) AS totfact, 
            SUM(IF(w.eslocal = 1, x.isr, x.isr * x.tipocambio)) AS isr
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
    $query = "SELECT a.fecha AS fechaOrd, a.id, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, b.siglas, a.tipotrans, a.numero, a.beneficiario, IF(a.tipocambio = 1, '', FORMAT(a.tipocambio, 4)) AS tipocambio, ";
    $query.= "'Q' AS moneda, IF(c.eslocal = 1, a.monto, a.monto * a.tipocambio) * IF(a.tipotrans = 'C', 1, -1) AS monto, NULL AS documento, NULL AS totfact, NULL AS isr, a.concepto, ";
    $query.= "IF(a.concepto LIKE '%anulad%' OR a.beneficiario LIKE '%anulad%', 1, NULL) AS anulado, a.iddetpagopresup, d.nopago, f.simbolo AS monedapago, FORMAT(d.monto, 2) AS montopago ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN detpagopresup d ON d.id = a.iddetpagopresup INNER JOIN detpresupuesto e ON e.id = d.iddetpresup ";
    $query.= "INNER JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.tipotrans IN('C', 'R') AND a.iddetpresup = $idot ";
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
    $query.= "CONCAT(FORMAT(IF(monto > pagosprogramados, (IFNULL(montoavance, 0.00) + isr) * 100 / monto, (IFNULL(montoavance, 0.00) + isr) * 100 / pagosprogramados), 2), '%') AS avanceot ";
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
    $query.= "tipogasto, empresa, fechasolicitud, proyecto, idpresupuesto, FORMAT(monto, 2) AS montooriginal, 0.00 AS sumaavance ";
    $query.= "FROM($qGenOTs) l ORDER BY correlativo";
    //print $query;
    $ot = $db->getQuery($query)[0];

    //Formas de pago
    /*
    $sumafp = new stdClass();
    $query = "SELECT a.id, a.iddetpresup, a.nopago, a.porcentaje, a.monto, a.notas, IF(a.pagado = 1, 'PAGADO', '') AS pagado ";
    $query.= "FROM detpagopresup a ";
    $query.= "WHERE a.iddetpresup = $d->idot ";
    $query.= "ORDER BY a.nopago";
    */
    //$ot->formaspago = $db->getQuery($query);
    $ot->formaspago = []; //Esto lo hago para que ya no hale las formas de pago en el reporte. Dejo el resto por si cambian de opinion y lo agregamos de nuevo.
    /*
    $cntfp = count($ot->formaspago);
    if($cntfp > 0){
        $sumafp->porcentaje = 0.0000;
        $sumafp->monto = 0.00;
        for($i = 0; $i < $cntfp; $i++){
            $fp = $ot->formaspago[$i];
            $sumafp->porcentaje += (float)$fp->porcentaje;
            $sumafp->monto += (float)$fp->monto;
            $fp->porcentaje = number_format((float)$fp->porcentaje, 4).'%';
            $fp->monto = number_format((float)$fp->monto, 2);
        }
        $ot->formaspago[] = [
            'id' => '', 'iddetpresup' => '', 'nopago' => '', 'porcentaje' => number_format($sumafp->porcentaje, 4).'%', 'monto' => number_format($sumafp->monto, 2), 'notas' => '', 'pagado' => ''
        ];
    }
    */

    //Notas de la OT
    /*
    $query = "SELECT a.id, a.iddetpresupuesto, DATE_FORMAT(a.fechahora, '%d/%m/%Y %H:%m:%s') AS fechahora, a.nota, a.usuario, b.iniciales, DATE_FORMAT(a.fhcreacion, '%d/%m/%Y %H:%m:%s') AS creadael ";
    $query.= "FROM notapresupuesto a LEFT JOIN usuario b ON b.id = a.usuario ";
    $query.= "WHERE a.iddetpresupuesto = $d->idot ";
    $query.= "ORDER BY a.fechahora DESC";
    $ot->notas = $db->getQuery($query);
    */
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

$app->run();