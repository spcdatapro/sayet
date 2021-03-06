<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptconstproc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $tblname = $db->crearTablasReportesConta('cp');
    //$db->doQuery("DELETE FROM $tblname");
    //$db->doQuery("ALTER TABLE $tblname AUTO_INCREMENT = 1");
    $db->doQuery("INSERT INTO $tblname(idcuentac, codigo, nombrecta, tipocuenta) SELECT id, codigo, nombrecta, tipocuenta FROM cuentac WHERE idempresa = $d->idempresa ORDER BY codigo");
    $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
    foreach($origenes as $k => $v){
        $query = "UPDATE $tblname a INNER JOIN (".getSelectHeader($v, $d, false).") b ON a.idcuentac = b.idcuenta SET a.anterior = a.anterior + b.anterior";
        $db->doQuery($query);
        $query = "UPDATE $tblname a INNER JOIN (".getSelectHeader($v, $d, true).") b ON a.idcuentac = b.idcuenta SET a.debe = a.debe + b.debe, a.haber = a.haber + b.haber";
        $db->doQuery($query);
    }
    $db->doQuery("UPDATE $tblname SET actual = anterior + debe - haber");

    //Calculo de datos para cuentas de totales
    //$tamnivdet = [4 => 7, 2 => 7, 1 => 7];
    $query = "SELECT DISTINCT LENGTH(codigo) AS tamnivel FROM $tblname WHERE tipocuenta = 1 ORDER BY 1 DESC";
    //echo $query."<br/><br/>";
    $tamniveles = $db->getQuery($query);
    foreach($tamniveles as $t){
        //echo "Tamaño del nivel = ".$t->tamnivel."<br/><br/>";
        $query = "SELECT id, idcuentac, codigo FROM $tblname WHERE tipocuenta = 1 AND LENGTH(codigo) = ".$t->tamnivel." ORDER BY codigo";
        //echo $query."<br/><br/>";
        $niveles = $db->getQuery($query);
        foreach($niveles as $n){
            //echo "LENGTH(codigo) = ".$tamnivdet[(int)$t->tamnivel]."<br/><br/>";
            //echo "Codigo = ".$n->codigo."<br/><br/>";
            $query = "SELECT SUM(anterior) AS anterior, SUM(debe) AS debe, SUM(haber) AS haber, SUM(actual) AS actual ";
            $query.= "FROM $tblname ";
            $query.= "WHERE tipocuenta = 0 AND LENGTH(codigo) <= 7 AND codigo LIKE '".$n->codigo."%'";
            //echo $query."<br/><br/>";
            $sumas = $db->getQuery($query)[0];
            $query = "UPDATE $tblname SET anterior = ".$sumas->anterior.", debe = ".$sumas->debe.", haber = ".$sumas->haber.", actual = ".$sumas->actual." ";
            $query.= "WHERE tipocuenta = 1 AND id = ".$n->id." AND idcuentac = ".$n->idcuentac;
            //echo $query."<br/><br/>";
            $db->doQuery($query);
        }
    }

    $query = "SELECT id, idcuentac, codigo, nombrecta, tipocuenta, anterior, debe, haber, actual ";
    $query.= "FROM $tblname ";
    $query.= "WHERE (anterior <> 0 OR debe <> 0 OR haber <> 0 OR actual <> 0) ";

    if((int)$d->filtro == 1){
        if($d->codigo != ''){
            $query.= "AND TRIM(codigo) IN($d->codigo)";
        }
    }else{
        $query.= $d->codigo != '' ? "AND TRIM(codigo) >= $d->codigo " : "";
        $query.= $d->codigoal != '' ? "AND TRIM(codigo) <= $d->codigoal " : "";
    }

    $query.= "ORDER BY codigo";
    //print $query;
    $lm = $db->getQuery($query);
    $cntLm = count($lm);
    for($i = 0; $i < $cntLm; $i++){
        $lm[$i]->dlm = $db->getQuery(getSelectDetail(1, $d, $lm[$i]->idcuentac));
        getDetalle($db, $lm[$i]->dlm);
    }
    //print $db->doSelectASJson("SELECT id, idcuentac, codigo, nombrecta, tipocuenta, anterior, debe, haber, actual FROM $tblname ORDER BY codigo");
    $empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
    $db->eliminarTablasRepConta($tblname);
    print json_encode(['empresa'=>$empresa, 'datos'=>$lm]);
});

function getSelectHeader($cual, $d, $enrango){
    $query = "";
    switch($cual){
        case 1:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco ";
            $query.= "WHERE a.origen = 1 AND a.activada = 1 AND FILTROFECHA AND c.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ?
                //"((b.anulado = 0 AND b.fecha < '$d->fdelstr') OR (b.anulado = 1 AND b.fecha < '$d->fdelstr' AND b.fechaanula >= '$d->fdelstr'))" :
                "((b.anulado = 0 AND b.fecha < '$d->fdelstr') OR (b.anulado = 1 AND b.fecha < '$d->fdelstr'))" :
                //"((b.anulado = 0 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND b.fechaanula > '$d->falstr'))"
                "((b.anulado = 0 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr'))"
            ), $query);
            break;
        case 2:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND b.idreembolso = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fechaingreso < '".$d->fdelstr."'" : "b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."'"), $query);
            break;
        case 3:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 4:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN directa b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 4 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 5:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            //$query.= "FROM detallecontable a INNER JOIN reembolso b ON b.id = a.idorigen ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN reembolso c ON c.id = b.idreembolso ";
            //$query.= "WHERE a.origen = 5 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa."  AND b.estatus = 2 ";
            $query.= "WHERE a.origen = 2 AND a.anulado = 0 AND b.idreembolso > 0 AND FILTROFECHA AND b.idempresa = $d->idempresa ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fechaingreso < '".$d->fdelstr."'" : "b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."'"), $query);
            break;
        /*
        case 6:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN contrato b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 6 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fechacontrato < '".$d->fdelstr."'" : "b.fechacontrato >= '".$d->fdelstr."' AND b.fechacontrato <= '".$d->falstr."'"), $query);
            break;
        */
        case 7:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN reciboprov b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 7 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 8:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN recibocli b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 8 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 9:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco ";
            $query.= "WHERE a.origen = 9 AND a.activada = 1 AND FILTROFECHA AND c.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ?
                //"((b.anulado = 0 AND b.fechaliquida < '$d->fdelstr') OR (b.anulado = 1 AND b.fecha < '$d->fdelstr' AND b.fechaanula >= '$d->fdelstr'))" :
                "((b.anulado = 0 AND b.fechaliquida < '$d->fdelstr') OR (b.anulado = 1 AND b.fecha < '$d->fdelstr'))" :
                //"((b.anulado = 0 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr' AND b.fechaanula > '$d->falstr'))"
                "((b.anulado = 0 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr'))"
            ), $query);
            break;
        case 10:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN ncdcliente b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 10 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 11:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN ncdproveedor b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 11 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
    }
    return $query;
}

function getSelectDetail($cual, $d, $idcuenta){
    $query = ""; $espacios = 2;
    switch($cual){
        case 1:
            //Transacciones bancarias -> origen = 1
            $query = "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha, CONCAT(c.siglas, SPACE($espacios), d.abreviatura, b.numero, SPACE($espacios), b.beneficiario) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, CONCAT(d.abreviatura, b.numero) AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
            $query.= "WHERE a.origen = 1 AND a.activada = 1 AND a.idcuenta = ".$idcuenta." AND ";
            //$query.= "((b.anulado = 0 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND b.fechaanula > '$d->falstr')) AND ";
            $query.= "((b.anulado = 0 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr')) AND ";
            $query.= "c.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Compras -> origen = 2
            $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fechaingreso AS fecha, CONCAT(d.siglas, SPACE($espacios), b.documento, SPACE($espacios), TRIM(c.nombre)) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, e.tranban AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN proveedor c ON c.id = b.idproveedor LEFT JOIN tipofactura d ON d.id = b.idtipofactura ";
            $query.= "LEFT JOIN (SELECT z.idcompra, GROUP_CONCAT(CONCAT(y.tipotrans, y.numero) SEPARATOR ', ') AS tranban FROM detpagocompra z INNER JOIN tranban y ON y.id = z.idtranban GROUP BY z.idcompra) e ON b.id = e.idcompra ";
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND b.idreembolso = 0 AND a.idcuenta = ".$idcuenta." AND b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."' ";
            $query.= "AND b.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Ventas -> origen = 3
            $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha, CONCAT(f.siglas, SPACE($espacios), b.numero, SPACE($espacios), d.nombrecorto) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, e.tranban AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = b.idcliente ";
            $query.= "LEFT JOIN (SELECT z.idfactura, GROUP_CONCAT(CONCAT(x.tipotrans, x.numero) SEPARATOR ', ') AS tranban FROM detcobroventa z INNER JOIN recibocli y ON y.id = z.idrecibocli INNER JOIN tranban x ON x.id = y.idtranban ";
            $query.= "GROUP BY z.idfactura) e ON b.id = e.idfactura LEFT JOIN tipofactura f ON f.id = b.idtipofactura ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND a.idcuenta = ".$idcuenta." AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND c.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha, CONCAT(f.siglas, SPACE($espacios), b.numero, SPACE($espacios), d.nombrecorto) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, e.tranban AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen INNER JOIN cliente d ON d.id = b.idcliente ";
            $query.= "LEFT JOIN (SELECT z.idfactura, GROUP_CONCAT(CONCAT(x.tipotrans, x.numero) SEPARATOR ', ') AS tranban FROM detcobroventa z INNER JOIN recibocli y ON y.id = z.idrecibocli INNER JOIN tranban x ON x.id = y.idtranban ";
            $query.= "GROUP BY z.idfactura) e ON b.id = e.idfactura LEFT JOIN tipofactura f ON f.id = b.idtipofactura ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND a.idcuenta = ".$idcuenta." AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "AND b.idcontrato = 0 ";
            $query.= "UNION ALL ";
            //Directas -> origen = 4
            $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha, CONCAT('Directa ', LPAD(b.id, 5, '0'), ' ', b.concepto) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, '' AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN directa b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 4 AND a.activada = 1 AND a.anulado = 0 AND a.idcuenta = ".$idcuenta." AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Reembolsos -> origen = 5
            $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fechaingreso AS fecha, CONCAT(d.siglas, SPACE($espacios), b.documento, SPACE($espacios), TRIM(b.proveedor)) AS referencia, a.conceptomayor, a.debe, a.haber, b.id AS idorigen, 5 AS origen, CONCAT(e.tipotrans, e.numero) AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN reembolso c ON c.id = b.idreembolso ";
            $query.= "LEFT JOIN tipofactura d ON d.id = b.idtipofactura LEFT JOIN tranban e ON e.id = c.idtranban ";
            $query.= "WHERE a.origen = 2 AND a.anulado = 0 AND b.idreembolso > 0 AND a.idcuenta = $idcuenta AND b.fechaingreso >= '$d->fdelstr' AND b.fechaingreso <= '$d->falstr' AND b.idempresa = $d->idempresa ";
            $query.= "UNION ALL ";
            //Recibos de proveedores -> origen = 7
            $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha AS fecha, CONCAT('RP', LPAD(b.id, 5, '0')) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, '' AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN reciboprov b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 7 AND a.activada = 1 AND a.anulado = 0 AND a.idcuenta = ".$idcuenta." AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Recibos de proveedores -> origen = 8
            $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha AS fecha, CONCAT('RC', LPAD(b.id, 5, '0')) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, '' AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN recibocli b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 8 AND a.activada = 1 AND a.anulado = 0 AND a.idcuenta = ".$idcuenta." AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Liquidación de documentos -> origen = 9
            $query.= "SELECT CONCAT('P', YEAR(b.fechaliquida), LPAD(MONTH(b.fechaliquida), 2, '0'), LPAD(DAY(b.fechaliquida), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fechaliquida AS fecha, CONCAT(c.siglas, SPACE($espacios), d.abreviatura, b.numero) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, CONCAT(d.abreviatura, b.numero) AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
            $query.= "WHERE a.origen = 9 AND a.activada = 1 AND a.idcuenta = ".$idcuenta." AND ";
            //$query.= "((b.anulado = 0 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr' AND b.fechaanula > '$d->falstr')) AND ";
            $query.= "((b.anulado = 0 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr')) AND ";
            $query.= "c.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //NCD clientes -> origen = 10
            $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha AS fecha, CONCAT(IF(b.tipo = 0, 'NdCC', 'NdDC'), TRIM(b.serie), '-', TRIM(b.numero)) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, '' AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN ncdcliente b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 10 AND a.activada = 1 AND a.anulado = 0 AND a.idcuenta = ".$idcuenta." AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //NCD proveedores -> origen = 11
            $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fecha AS fecha, CONCAT(IF(b.tipo = 0, 'NdCP', 'NdDP'), TRIM(b.serie), '-', TRIM(b.numero)) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, '' AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN ncdproveedor b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 11 AND a.activada = 1 AND a.anulado = 0 AND a.idcuenta = ".$idcuenta." AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "ORDER BY 2, 3";
            break;
    }
    //print $query;
    return $query;
}

function getDetalle($db, $dlm){
    $cntDLM = count($dlm);
    for($i = 0; $i < $cntDLM; $i++){
        $doc = $dlm[$i];
        $doc->detalle = [];
        $query = "";
        switch(true){
            case (int)$doc->origen == 1:
                //Documentos de soporte atados a la transacción bancaria
                $query = "SELECT a.id AS idcompra, a.idempresa, c.abreviatura AS abreviaempresa, a.idproyecto, d.nomproyecto, e.siglas AS tipofactura,
                        a.idproveedor, b.nombre AS proveedor, b.nit, a.serie, a.documento, a.fechaingreso, a.idtipocompra, f.desctipocompra AS tipocompra,
                        a.conceptomayor, a.totfact, a.noafecto, a.iva, a.isr, a.tipocambio, '' AS moneda
                        FROM compra a
                        LEFT JOIN proveedor b ON b.id = a.idproveedor
                        LEFT JOIN empresa c ON c.id = a.idempresa
                        LEFT JOIN proyecto d ON d.id = a.idproyecto
                        LEFT JOIN tipofactura e ON e.id = a.idtipofactura
                        LEFT JOIN tipocompra f ON f.id = a.idtipocompra
                        WHERE a.id IN(SELECT iddocto FROM doctotranban WHERE idtranban = $doc->idorigen AND idtipodoc = 1)
                        UNION
                        SELECT a.id AS idcompra, a.idempresa, c.abreviatura AS abreviaempresa, a.idproyecto, d.nomproyecto, e.siglas AS tipofactura,
                        a.idproveedor, a.proveedor, a.nit, a.serie, a.documento, a.fechaingreso, a.idtipocompra, f.desctipocompra AS tipocompra,
                        a.conceptomayor, a.totfact, a.noafecto, a.iva, a.isr, a.tipocambio, '' AS moneda
                        FROM compra a
                        LEFT JOIN proveedor b ON b.id = a.idproveedor
                        LEFT JOIN empresa c ON c.id = a.idempresa
                        LEFT JOIN proyecto d ON d.id = a.idproyecto
                        LEFT JOIN tipofactura e ON e.id = a.idtipofactura
                        LEFT JOIN tipocompra f ON f.id = a.idtipocompra
                        WHERE a.idreembolso IN(SELECT iddocto FROM doctotranban WHERE idtranban = $doc->idorigen AND idtipodoc = 2)
                        UNION
                        SELECT a.id AS idcompra, a.idempresa, c.abreviatura AS abreviaempresa, a.idproyecto, d.nomproyecto, e.siglas AS tipofactura,
                        a.idproveedor, b.nombre AS proveedor, b.nit, a.serie, a.documento, a.fechaingreso, a.idtipocompra, f.desctipocompra AS tipocompra,
                        a.conceptomayor, a.totfact, a.noafecto, a.iva, a.isr, a.tipocambio, '' AS moneda
                        FROM compra a
                        LEFT JOIN proveedor b ON b.id = a.idproveedor
                        LEFT JOIN empresa c ON c.id = a.idempresa
                        LEFT JOIN proyecto d ON d.id = a.idproyecto
                        LEFT JOIN tipofactura e ON e.id = a.idtipofactura
                        LEFT JOIN tipocompra f ON f.id = a.idtipocompra
                        WHERE a.id IN(SELECT idcompra FROM detpagocompra WHERE idtranban = $doc->idorigen AND esrecprov = 0)
                        ORDER BY 12, 8, 10, 11";
                break;
            case in_array((int)$doc->origen, array(2, 5)):
                //Transacciones bancarias relacionados a las compras/reembolsos/cajas chicas
                $query = "SELECT b.idcompra, d.idempresa, f.abreviatura, c.idproyecto, g.nomproyecto, a.tipotrans AS tipofactura,
                        c.idproveedor, a.beneficiario AS proveedor, '' AS nit, '' AS serie, a.numero AS documento, a.fecha AS fechaingreso, c.idtipocompra, '' AS tipocompra,
                        a.concepto AS conceptomayor, a.monto AS totfact, '' AS noafecto, '' AS iva, '' AS isr, a.tipocambio, e.simbolo AS moneda
                        FROM tranban a
                        INNER JOIN detpagocompra b ON a.id = b.idtranban
                        INNER JOIN compra c ON c.id = b.idcompra
                        INNER JOIN banco d ON d.id = a.idbanco
                        INNER JOIN moneda e ON e.id = d.idmoneda
                        INNER JOIN empresa f ON f.id = d.idempresa
                        LEFT JOIN proyecto g ON g.id = c.idproyecto
                        WHERE b.esrecprov = 0 AND c.idreembolso = 0 AND c.id = $doc->idorigen
                        UNION
                        SELECT c.id AS idcompra, d.idempresa, f.abreviatura, c.idproyecto, g.nomproyecto, a.tipotrans AS tipofactura,
                        c.idproveedor, a.beneficiario AS proveedor, '' AS nit, '' AS serie, a.numero AS documento, a.fecha AS fechaingreso, c.idtipocompra, '' AS tipocompra,
                        a.concepto AS conceptomayor, a.monto AS totfact, '' AS noafecto, '' AS iva, '' AS isr, a.tipocambio, e.simbolo AS moneda
                        FROM tranban a
                        INNER JOIN reembolso b ON a.id = b.idtranban
                        INNER JOIN compra c ON b.id = c.idreembolso
                        INNER JOIN banco d ON d.id = a.idbanco
                        INNER JOIN moneda e ON e.id = d.idmoneda
                        INNER JOIN empresa f ON f.id = d.idempresa
                        LEFT JOIN proyecto g ON g.id = c.idproyecto
                        WHERE b.esrecprov = 0 AND c.idreembolso > 0 AND c.id = $doc->idorigen
                        ORDER BY 12";
                break;
        }
        
        if($query != ""){
            $doc->detalle = $db->getQuery($query);
            $cntDetalle = count($doc->detalle);
            if($cntDetalle > 0){
                $qSum = "SELECT SUM(totfact) FROM ($query) z";
                $totMonto = $db->getOneField($qSum);
                $doc->detalle[] = [
                    "idcompra"=> "", "idempresa"=> "", "abreviaempresa"=> "", "idproyecto"=> "", "nomproyecto"=> "", "tipofactura"=> "", "idproveedor"=> "", "proveedor"=> "", "nit"=> "", "serie"=> "", "documento"=> "", "fechaingreso"=> "",
                    "idtipocompra"=> "", "tipocompra"=> "",
                    "conceptomayor"=> "Total de documentos: ",
                    "totfact"=> $totMonto,
                    "noafecto"=> "", "iva"=> "", "isr"=> "", "tipocambio"=> "", "moneda" => ""
                ];
            }
        }
    }
}

$app->run();