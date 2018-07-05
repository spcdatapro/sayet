<?php
set_time_limit(0);
ini_set('memory_limit', '1536M');
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptlibmay', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->vercierre)){ $d->vercierre = 0; }
    $db = new dbcpm();
    $db->doQuery("DELETE FROM rptlibromayor");
    $db->doQuery("ALTER TABLE rptlibromayor AUTO_INCREMENT = 1");
    $db->doQuery("INSERT INTO rptlibromayor(idcuentac, codigo, nombrecta, tipocuenta) SELECT id, codigo, nombrecta, tipocuenta FROM cuentac WHERE idempresa = $d->idempresa ORDER BY codigo");
    //$origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'contrato' => 6, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
    $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
    foreach($origenes as $k => $v){
        $query = "UPDATE rptlibromayor a INNER JOIN (".getSelectHeader($v, $d, false).") b ON a.idcuentac = b.idcuenta SET a.anterior = a.anterior + b.anterior";
        $db->doQuery($query);
        $query = "UPDATE rptlibromayor a INNER JOIN (".getSelectHeader($v, $d, true).") b ON a.idcuentac = b.idcuenta SET a.debe = a.debe + b.debe, a.haber = a.haber + b.haber";
        $db->doQuery($query);
    }
    $db->doQuery("UPDATE rptlibromayor SET actual = anterior + debe - haber");

    //Calculo de datos para cuentas de totales
    //$tamnivdet = [4 => 7, 2 => 7, 1 => 7];
    $query = "SELECT DISTINCT LENGTH(codigo) AS tamnivel FROM rptlibromayor WHERE tipocuenta = 1 ORDER BY 1 DESC";
    //echo $query."<br/><br/>";
    $tamniveles = $db->getQuery($query);
    foreach($tamniveles as $t){
        //echo "Tamaño del nivel = ".$t->tamnivel."<br/><br/>";
        $query = "SELECT id, idcuentac, codigo FROM rptlibromayor WHERE tipocuenta = 1 AND LENGTH(codigo) = ".$t->tamnivel." ORDER BY codigo";
        //echo $query."<br/><br/>";
        $niveles = $db->getQuery($query);
        foreach($niveles as $n){
            //echo "LENGTH(codigo) = ".$tamnivdet[(int)$t->tamnivel]."<br/><br/>";
            //echo "Codigo = ".$n->codigo."<br/><br/>";
            $query = "SELECT SUM(anterior) AS anterior, SUM(debe) AS debe, SUM(haber) AS haber, SUM(actual) AS actual ";
            $query.= "FROM rptlibromayor ";
            $query.= "WHERE tipocuenta = 0 AND LENGTH(codigo) <= 7 AND codigo LIKE '".$n->codigo."%'";
            //echo $query."<br/><br/>";
            $sumas = $db->getQuery($query)[0];
            $query = "UPDATE rptlibromayor SET anterior = ".$sumas->anterior.", debe = ".$sumas->debe.", haber = ".$sumas->haber.", actual = ".$sumas->actual." ";
            $query.= "WHERE tipocuenta = 1 AND id = ".$n->id." AND idcuentac = ".$n->idcuentac;
            //echo $query."<br/><br/>";
            $db->doQuery($query);
        }
    }

    $query = "SELECT id, idcuentac, codigo, nombrecta, tipocuenta, anterior, debe, haber, actual ";
    $query.= "FROM rptlibromayor ";
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
    }
    //print $db->doSelectASJson("SELECT id, idcuentac, codigo, nombrecta, tipocuenta, anterior, debe, haber, actual FROM rptlibromayor ORDER BY codigo");
    $empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
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
            $query.= (int)$d->vercierre === 0 ? "AND b.tipocierre NOT IN(1, 2, 3, 4) " : '';
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
            $query.= "b.fecha, CONCAT(f.siglas, SPACE($espacios), b.numero, SPACE($espacios), IF(d.id IS NOT NULL, d.nombrecorto, b.nombre)) AS referencia, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen, e.tranban AS transaccion ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen LEFT JOIN cliente d ON d.id = b.idcliente ";
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
            $query.= (int)$d->vercierre === 0 ? "AND b.tipocierre NOT IN(1, 2, 3, 4) " : '';
            $query.= "UNION ALL ";
            //Reembolsos -> origen = 5
            $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(a.origen, 2, '0'), LPAD(a.idorigen, 7, '0')) AS poliza, ";
            $query.= "b.fechaingreso AS fecha, CONCAT(d.siglas, SPACE($espacios), b.documento, SPACE($espacios), TRIM(b.proveedor)) AS referencia, a.conceptomayor, a.debe, a.haber, c.id AS idorigen, 5 AS origen, CONCAT(e.tipotrans, e.numero) AS transaccion ";
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
            /*
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
            */
            /*
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
            */
            $query.= "ORDER BY 2, 3";
            break;
    }
    //print $query;
    return $query;
}

$app->run();