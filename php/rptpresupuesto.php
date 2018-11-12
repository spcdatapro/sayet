<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function getQueryPresupuesto($idpresupuesto){
    $queryPresupuesto = "SELECT a.id, a.fechasolicitud, a.idempresa, b.nomempresa AS empresa, a.idproyecto, c.nomproyecto AS proyecto, a.idtipogasto, d.desctipogast AS tipogasto, 'Q' AS moneda, ";
    $queryPresupuesto.= "(SELECT SUM(monto * tipocambio) FROM detpresupuesto WHERE idpresupuesto = a.id) AS totalpresupuesto, ";
    $queryPresupuesto.= "(SELECT SUM(z.monto * y.tipocambio) FROM detpagopresup z INNER JOIN detpresupuesto y ON y.id = z.iddetpresup WHERE y.idpresupuesto = a.id) AS totalpagosprogramados, ";
    $queryPresupuesto.= "(SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda ";
    $queryPresupuesto.= "WHERE z.iddetpresup IN(SELECT id FROM detpresupuesto WHERE idpresupuesto = a.id) AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULADO%') AS montoavance, ";
    $queryPresupuesto.= "IFNULL(getMontoISROT(a.id, 1), 0.00) AS isrpresupuesto, ";
    $queryPresupuesto.= "a.notas, e.iniciales AS usrcrea, a.fechacreacion, f.iniciales AS usraprueba, a.fhaprobacion, g.iniciales AS usrmodifica, a.fechamodificacion, IF(a.tipo = 1, 'SIMPLE', 'MULTIPLE') AS tipo ";
    $queryPresupuesto.= "FROM presupuesto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN proyecto c ON c.id = a.idproyecto INNER JOIN tipogasto d ON d.id = a.idtipogasto INNER JOIN usuario e ON e.id = a.idusuario ";
    $queryPresupuesto.= "INNER JOIN usuario f ON f.id = a.idusuarioaprueba INNER JOIN usuario g ON g.id = a.lastuser ";
    $queryPresupuesto.= "WHERE a.id = $idpresupuesto";
    return $queryPresupuesto;
}

function getQueryOTs($id, $espresupuesto = true){
    $queryOTs = "
        SELECT a.id, a.correlativo, a.idproveedor, TRIM(b.nombre) AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.tipocambio, 'Q' AS moneda, IF(d.eslocal = 1, a.monto, a.monto * a.tipocambio) AS monto,
        IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, 
        (SELECT SUM(z.monto * y.tipocambio) FROM detpagopresup z INNER JOIN detpresupuesto y ON y.id = z.iddetpresup WHERE y.id = a.id) AS pagosprogramados,
        (SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda 
        WHERE z.iddetpresup = a.id AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULAD%'
        ) AS montoavance, a.notas, IFNULL(getMontoISROT(a.id, 0), 0.00) AS isr
        FROM detpresupuesto a 
        INNER JOIN proveedor b ON b.id = a.idproveedor
        INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto
        INNER JOIN moneda d ON d.id = a.idmoneda
        WHERE a.origenprov = 1 AND ";
    $queryOTs.= $espresupuesto ? "a.idpresupuesto = $id " : "a.id = $id ";
    $queryOTs.= "UNION ALL ";
    $queryOTs.= "
        SELECT a.id, a.correlativo, a.idproveedor, TRIM(b.nombre) AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.tipocambio, 'Q' AS moneda, IF(d.eslocal = 1, a.monto, a.monto * a.tipocambio) AS monto,
        IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, 
        (SELECT SUM(z.monto * y.tipocambio) FROM detpagopresup z INNER JOIN detpresupuesto y ON y.id = z.iddetpresup WHERE y.id = a.id) AS pagosprogramados,
        (SELECT SUM(IF(x.eslocal = 1, z.monto, z.monto * z.tipocambio)) FROM tranban z INNER JOIN banco y ON y.id = z.idbanco INNER JOIN moneda x ON x.id = y.idmoneda 
        WHERE z.iddetpresup = a.id AND z.anulado = 0 AND UPPER(z.concepto) NOT LIKE '%ANULAD%'
        ) AS montoavance, a.notas, IFNULL(getMontoISROT(a.id, 0), 0.00) AS isr
        FROM detpresupuesto a 
        INNER JOIN beneficiario b ON b.id = a.idproveedor
        INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto
        INNER JOIN moneda d ON d.id = a.idmoneda
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
    $query.= "'Q' AS moneda, IF(c.eslocal = 1, a.monto, a.monto * a.tipocambio) AS monto, NULL AS documento, NULL AS totfact, NULL AS isr, a.concepto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
    $query.= "WHERE a.iddetpresup = $idot ";
    $query.= "ORDER BY 1";
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
    $query.= "FORMAT(IF(monto > pagosprogramados, monto, pagosprogramados), 2) AS montoreal, CONCAT(FORMAT(IF(monto > pagosprogramados, (IFNULL(montoavance, 0.00) + isr) * 100 / monto, (IFNULL(montoavance, 0.00) + isr) * 100 / pagosprogramados), 2), '%') AS avanceot ";
    $query.= "FROM($qGenOTs) l ORDER BY correlativo";

    $presupuesto->ots = $db->getQuery($query);

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
                    $suma->monto += $doc->monto;
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

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha";
    $generales = $db->getQuery($query)[0];

    $query = "SELECT a.id AS idot, a.idpresupuesto, a.correlativo, DATE_FORMAT(b.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, b.idproyecto, e.nomproyecto AS proyecto, a.idproveedor, c.nombre AS proveedor, b.idempresa, f.nomempresa AS empresa, ";
    $query.= "f.abreviatura AS abreviaempresa, d.idtipogasto, g.desctipogast AS tipogasto, a.idsubtipogasto, d.descripcion AS subtipogasto, FORMAT(a.monto, 2) AS monto, IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, b.idmoneda, h.simbolo AS moneda, b.tipo ";
    $query.= "FROM detpresupuesto a LEFT JOIN presupuesto b ON b.id = a.idpresupuesto LEFT JOIN proveedor c ON c.id = a.idproveedor LEFT JOIN subtipogasto d ON d.id = a.idsubtipogasto LEFT JOIN proyecto e ON e.id = b.idproyecto ";
    $query.= "LEFT JOIN empresa f ON f.id = b.idempresa LEFT JOIN tipogasto g ON g.id = d.idtipogasto LEFT JOIN moneda h ON h.id = b.idmoneda ";
    $query.= "WHERE a.origenprov = 1 AND a.id = $d->idot ";
    $query.= "UNION ";
    $query.= "SELECT a.id AS idot, a.idpresupuesto, a.correlativo, DATE_FORMAT(b.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, b.idproyecto, e.nomproyecto AS proyecto, a.idproveedor, c.nombre AS proveedor, b.idempresa, f.nomempresa AS empresa, ";
    $query.= "f.abreviatura AS abreviaempresa, d.idtipogasto, g.desctipogast AS tipogasto, a.idsubtipogasto, d.descripcion AS subtipogasto, FORMAT(a.monto, 2) AS monto, IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, b.idmoneda, h.simbolo AS moneda, b.tipo ";
    $query.= "FROM detpresupuesto a LEFT JOIN presupuesto b ON b.id = a.idpresupuesto LEFT JOIN beneficiario c ON c.id = a.idproveedor LEFT JOIN subtipogasto d ON d.id = a.idsubtipogasto LEFT JOIN proyecto e ON e.id = b.idproyecto ";
    $query.= "LEFT JOIN empresa f ON f.id = b.idempresa LEFT JOIN tipogasto g ON g.id = d.idtipogasto LEFT JOIN moneda h ON h.id = b.idmoneda ";
    $query.= "WHERE a.origenprov = 2 AND a.id = $d->idot";
    $ot = $db->getQuery($query)[0];

    //Formas de pago
    $query = "SELECT a.id, a.iddetpresup, a.nopago, CONCAT(FORMAT(a.porcentaje, 4), '%') AS porcentaje, FORMAT(a.monto, 2) AS monto, a.notas, IF(a.pagado = 1, 'PAGADO', '') AS pagado ";
    $query.= "FROM detpagopresup a ";
    $query.= "WHERE a.iddetpresup = $d->idot ";
    $query.= "ORDER BY a.nopago";
    $ot->formaspago = $db->getQuery($query);

    //Notas de la OT
    $query = "SELECT a.id, a.iddetpresupuesto, DATE_FORMAT(a.fechahora, '%d/%m/%Y %H:%m:%s') AS fechahora, a.nota, a.usuario, b.iniciales, DATE_FORMAT(a.fhcreacion, '%d/%m/%Y %H:%m:%s') AS creadael ";
    $query.= "FROM notapresupuesto a LEFT JOIN usuario b ON b.id = a.usuario ";
    $query.= "WHERE a.iddetpresupuesto = $d->idot ";
    $query.= "ORDER BY a.fechahora DESC";
    $ot->notas = $db->getQuery($query);

    //Avance de la OT
    $query = "SELECT 1 AS origen, a.id, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, b.siglas AS banco, a.tipotrans, a.numero, c.simbolo AS moneda, FORMAT(a.monto, 2) AS monto, ";
    $query.= "a.concepto, IF(a.tipocambio > 1, FORMAT(a.tipocambio, 2), '') AS tipocambio, 0.00 AS isr, ";
    $query.= "(SELECT GROUP_CONCAT(CONCAT(serie, '-', documento) SEPARATOR ', ') FROM doctotranban WHERE idtranban = a.id GROUP BY idtranban) AS factura, ";
    $query.= "CONCAT(b.siglas, '-', a.tipotrans, '-', a.numero) AS docto ";
    $query.= "FROM tranban a LEFT JOIN banco b ON b.id = a.idbanco LEFT JOIN moneda c ON c.id = b.idmoneda ";
    $query.= "WHERE a.anulado = 0 AND a.iddetpresup = $ot->idot ";
    $query.= "UNION ALL ";
    $query.= "SELECT 2 AS origen, a.id, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fecha, '' AS banco, '' AS tipotrans, CONCAT(a.serie, '-',a.documento) AS numero, b.simbolo AS moneda, FORMAT(a.totfact, 2) AS monto, ";
    $query.= "a.conceptomayor AS concepto, IF(a.tipocambio > 1, FORMAT(a.tipocambio, 2), '') AS tipocambio, FORMAT(a.isr, 2) AS isr, NULL AS factura, ";
    $query.= "CONCAT('FACT - ', a.serie, '-', a.documento) AS docto ";
    $query.= "FROM compra a LEFT JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.ordentrabajo = $ot->idot ";
    $query.= "ORDER BY 3, 4, 5, 6";
    $ot->avance = $db->getQuery($query);

    print json_encode(['generales' => $generales, 'ot' => $ot]);

});

$app->run();