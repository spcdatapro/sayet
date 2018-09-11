<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptpresupuesto', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha";
    $generales = $db->getQuery($query)[0];

    $idot = 0;
    if(isset($d->idot)){ $idot = (int)$d->idot > 0 ? (int)$d->idot : 0; }

    $query = "SELECT a.id AS idpresupuesto, a.idestatuspresupuesto, b.descestatuspresup AS estatuspresupuesto, DATE_FORMAT(a.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, a.idproyecto, c.nomproyecto AS proyecto, ";
    $query.= "a.idempresa, d.nomempresa AS empresa, d.abreviatura AS abreviaempresa, a.idtipogasto, e.desctipogast AS tipogasto, a.idmoneda, f.simbolo AS moneda, FORMAT(a.total, 2) AS totalpresupuesto, a.notas, ";
    $query.= "DATE_FORMAT(a.fechacreacion, '%d/%m/%Y') AS fechacreacion, a.idusuario, g.iniciales AS creadopor, DATE_FORMAT(a.fhenvioaprobacion, '%d/%m/%Y') AS fhaprobacion, a.idusuarioaprueba, h.iniciales AS aprobadopor, ";
    $query.= "DATE_FORMAT(a.fechamodificacion, '%d/%m/%Y') AS modificadoel, a.lastuser, i.iniciales AS modificadopor, IF(a.tipo = 1, 'SIMPLE', 'MÚLTIPLE') AS tipo , FORMAT(SUM(st.total),2) AS totalf , ";
    $query.= "FORMAT(SUM(st.isr),2) AS totisr , FORMAT((SUM(st.total)*100)/a.total,2) AS porecentaje_total ";
    $query.= "FROM presupuesto a LEFT JOIN estatuspresupuesto b ON b.id = a.idestatuspresupuesto LEFT JOIN proyecto c ON c.id = a.idproyecto LEFT JOIN empresa d ON d.id = a.idempresa LEFT JOIN tipogasto e ON e.id = a.idtipogasto ";
    $query.= "LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN usuario g ON g.id = a.idusuario LEFT JOIN usuario h ON h.id = a.idusuarioaprueba LEFT JOIN usuario i ON i.id = a.lastuser ";
    $query.= "LEFT JOIN ( SELECT t.total, t.simbolo ,t.isr ,a.idpresupuesto FROM detpresupuesto a LEFT JOIN subtipogasto c ON c.id = a.idsubtipogasto ";
    $query.= "LEFT JOIN ( SELECT v.idot,SUM(v.monto) AS total , v.simbolo AS simbolo, SUM(v.isr) AS isr FROM ( ";
    $query.= "SELECT a.iddetpresup AS idot, IF(c.simbolo <> f.simbolo, ROUND(a.monto / d.tipocambio, 2), a.monto) AS monto, c.simbolo  , 0.00 AS isr FROM tranban a LEFT JOIN banco b ON b.id = a.idbanco ";
    $query.= "LEFT JOIN moneda c ON c.id = b.idmoneda LEFT JOIN detpresupuesto d ON d.id = a.iddetpresup LEFT JOIN presupuesto e ON e.id = d.idpresupuesto LEFT JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.anulado = 0 AND a.iddetpresup > 0 UNION ALL SELECT a.ordentrabajo AS idot, a.totfact AS monto , b.simbolo  , FORMAT(a.isr, 2) AS isr FROM compra a LEFT JOIN moneda b ON b.id = a.idmoneda ) v ";
    $query.= "GROUP BY v.idot ) t ON a.id = t.idot ) st ON st.idpresupuesto= a.id ";
    $query.= "WHERE a.id = $d->idpresupuesto";
    $presupuesto = $db->getQuery($query)[0];

    $query = "SELECT a.id AS idot, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, FORMAT(a.monto, 2) AS monto, ";
    $query.= "FORMAT(a.tipocambio, 2) AS tipocambio, CONCAT(FORMAT(a.excedente,2), '%') AS excedente,FORMAT(t.total,2) as total, FORMAT((t.total*100)/a.monto, 2) AS porcentaje_avance, t.simbolo, t.isr, FORMAT(t.montoflat, 2) AS montoflat, ";
    $query.= "s.simbolo AS monedapresup ";
    $query.= "FROM detpresupuesto a LEFT JOIN proveedor b ON b.id = a.idproveedor LEFT JOIN subtipogasto c ON c.id = a.idsubtipogasto ";
    $query.= "LEFT JOIN ( SELECT v.idot,SUM(v.monto) AS total , v.simbolo AS simbolo, v.isr AS isr, v.montoflat FROM ( ";
    $query.= "SELECT a.iddetpresup AS idot, IF(c.simbolo <> f.simbolo, ROUND(a.monto / d.tipocambio, 2), a.monto) AS monto, c.simbolo, 0.00 AS isr, a.monto AS montoflat ";
    $query.= "FROM tranban a LEFT JOIN banco b ON b.id = a.idbanco LEFT JOIN moneda c ON c.id = b.idmoneda LEFT JOIN detpresupuesto d ON d.id = a.iddetpresup LEFT JOIN presupuesto e ON e.id = d.idpresupuesto LEFT JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.anulado = 0 AND a.iddetpresup > 0 ";
    $query.= "UNION ALL ";
    $query.= "SELECT a.ordentrabajo AS idot, a.totfact AS monto , b.simbolo  , FORMAT(a.isr, 2) AS isr, a.totfact AS montoflat  ";
    $query.= "FROM compra a LEFT JOIN moneda b ON b.id = a.idmoneda ) v GROUP BY v.idot ) t ON a.id = t.idot LEFT JOIN presupuesto r ON r.id = a.idpresupuesto LEFT JOIN moneda s ON s.id = r.idmoneda ";
    $query.= "WHERE a.origenprov = 1 AND a.idpresupuesto = $d->idpresupuesto ";
    $query.= $idot > 0 ? "AND a.id = $idot " : '';
    $query.= "UNION ";
    $query.= "SELECT a.id AS idot, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, IF(a.coniva = 1, 'Incluye I.V.A.', '') AS coniva, FORMAT(a.monto, 2) AS monto, ";
    $query.= "FORMAT(a.tipocambio, 2) AS tipocambio, CONCAT(FORMAT(a.excedente,2), '%') AS excedente,FORMAT(t.total,2) as total, FORMAT((t.total*100)/a.monto,2) AS porcentaje_avance, t.simbolo, t.isr, FORMAT(t.montoflat, 2) AS montoflat, ";
    $query.= "s.simbolo AS monedapresup ";
    $query.= "FROM detpresupuesto a LEFT JOIN beneficiario b ON b.id = a.idproveedor LEFT JOIN subtipogasto c ON c.id = a.idsubtipogasto ";
    $query.= "LEFT JOIN ( SELECT v.idot,SUM(v.monto) AS total , v.simbolo AS simbolo, v.isr AS isr, v.montoflat FROM ( ";
    $query.= "SELECT a.iddetpresup AS idot, IF(c.simbolo <> f.simbolo, ROUND(a.monto / d.tipocambio, 2), a.monto) AS monto , c.simbolo, 0.00 AS isr, a.monto AS montoflat ";
    $query.= "FROM tranban a LEFT JOIN banco b ON b.id = a.idbanco LEFT JOIN moneda c ON c.id = b.idmoneda LEFT JOIN detpresupuesto d ON d.id = a.iddetpresup LEFT JOIN presupuesto e ON e.id = d.idpresupuesto LEFT JOIN moneda f ON f.id = e.idmoneda ";
    $query.= "WHERE a.anulado = 0 AND a.iddetpresup > 0 ";
    $query.= "UNION ALL ";
    $query.= "SELECT a.ordentrabajo AS idot, a.totfact AS monto , b.simbolo  , FORMAT(a.isr, 2) AS isr, a.totfact AS montoflat  ";
    $query.= "FROM compra a LEFT JOIN moneda b ON b.id = a.idmoneda ) v GROUP BY v.idot ) t ON a.id = t.idot LEFT JOIN presupuesto r ON r.id = a.idpresupuesto LEFT JOIN moneda s ON s.id = r.idmoneda ";
    $query.= "WHERE a.origenprov = 2 AND a.idpresupuesto = $d->idpresupuesto ";
    $query.= $idot > 0 ? "AND a.id = $idot " : '';
    $query.= "ORDER BY 3";

    $presupuesto->ots = $db->getQuery($query);
    $cntOts = count($presupuesto->ots);

    if((int)$d->detallado == 1 && $cntOts > 0){
        for($i = 0; $i < $cntOts; $i++){
            $ot = $presupuesto->ots[$i];
            $query = "SELECT 1 AS origen, a.id, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, b.siglas AS banco, a.tipotrans, a.numero, c.simbolo AS moneda, FORMAT(a.monto, 2) AS monto,  ";
            $query.= "a.concepto, IF(a.tipocambio > 1, FORMAT(a.tipocambio, 2), '') AS tipocambio, ";
            $query.= "(SELECT SUM(y.isr) FROM doctotranban z INNER JOIN compra y ON y.id = z.iddocto WHERE z.idtipodoc = 1 AND z.idtranban = a.id GROUP BY idtranban) AS isr, ";
            $query.= "(SELECT GROUP_CONCAT(CONCAT(serie, documento) SEPARATOR ', ') FROM doctotranban WHERE idtranban = a.id GROUP BY idtranban) AS factura, ";
            $query.= "CONCAT(b.siglas, '-', a.tipotrans, a.numero) AS docto , a.beneficiario ";
            $query.= "FROM tranban a LEFT JOIN banco b ON b.id = a.idbanco LEFT JOIN moneda c ON c.id = b.idmoneda ";
            $query.= "WHERE a.anulado = 0 AND a.iddetpresup = $ot->idot ";
            $query.= "UNION ALL ";
            $query.= "SELECT 2 AS origen, a.id, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fecha, '' AS banco, '' AS tipotrans, CONCAT(a.serie, '-',a.documento) AS numero, b.simbolo AS moneda, FORMAT(a.totfact, 2) AS monto, ";
            $query.= "a.conceptomayor AS concepto, IF(a.tipocambio > 1, FORMAT(a.tipocambio, 2), '') AS tipocambio, FORMAT(a.isr, 2) AS isr, NULL AS factura, ";
            $query.= "CONCAT(z.siglas, a.serie, a.documento) AS docto , m.beneficiario  ";
            $query.= "FROM compra a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN proyecto n ON n.id=a.idproyecto LEFT JOIN tranban m ON m.idproyecto= n.id LEFT JOIN tipofactura z ON z.id = a.idtipofactura ";
            $query.= "WHERE a.ordentrabajo = $ot->idot ";
            $query.= "ORDER BY 3 DESC, 4, 5, 6";
            $ot->avance = $db->getQuery($query);
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