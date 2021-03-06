
<?php
set_time_limit(0);
ini_set('memory_limit', '1536M');
require 'vendor/autoload.php';
require_once 'db.php';
require_once  'conta.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptlibmay', function(){
    $d = empty($_POST) ? json_decode(file_get_contents('php://input')) : ((object)$_POST);
    if(!isset($d->vercierre)){ $d->vercierre = 0; }
    $db = new dbcpm();
    $tblname = $db->crearTablasReportesConta('dm');
    //$db->doQuery("DELETE FROM $tblname");
    //$db->doQuery("ALTER TABLE $tblname AUTO_INCREMENT = 1");
    $db->doQuery("INSERT INTO $tblname(idcuentac, codigo, nombrecta, tipocuenta) SELECT id, codigo, nombrecta, tipocuenta FROM cuentac WHERE idempresa = $d->idempresa ORDER BY codigo");
    //$origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'contrato' => 6, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
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
    }
    //print $db->doSelectASJson("SELECT id, idcuentac, codigo, nombrecta, tipocuenta, anterior, debe, haber, actual FROM $tblname ORDER BY codigo");
    $empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
    print json_encode(['empresa'=>$empresa, 'datos'=>$lm]);
    $db->eliminarTablasRepConta($tblname);
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


$app->post('/libromayor', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->vercierre)){ $d->vercierre = 0; }
    $db = new dbcpm();

    if($d->codigo && strlen(trim($d->codigo)) == 0){ $d->codigo = null; }
    if($d->codigoal && strlen(trim($d->codigoal)) == 0){ $d->codigoal = null; }
    $conta = new contabilidad($d->fdelstr, $d->falstr, $d->idempresa, (int)$d->vercierre, $d->codigo, $d->codigoal);
    $queryRawData = $conta->getDatosEnCrudo();
    $queryRawDataAnterior = $conta->getDatosEnCrudoAnterior();

    $query = "SELECT j.id, j.codigo, j.nombrecta, j.tipocuenta, 
                IFNULL(l.anterior, 0.00) AS anterior, IFNULL(l.anteriorstr, 0.00) AS anteriorstr, IFNULL(k.debe, 0.00) AS debe, FORMAT(IFNULL(k.debe, 0.00), 2) AS debestr, IFNULL(k.haber, 0.00) AS haber, FORMAT(IFNULL(k.haber, 0.00), 2) AS haberstr, 
                (IFNULL(l.anterior, 0.00) + IFNULL(k.debe, 0.00) - IFNULL(k.haber, 0.00)) AS actual, FORMAT((IFNULL(l.anterior, 0.00) + IFNULL(k.debe, 0.00) - IFNULL(k.haber, 0.00)), 2) AS actualstr, 1 AS mostrar
                FROM cuentac j
                LEFT JOIN (
                    SELECT idcuentac, SUM(debe) AS debe, SUM(haber) AS haber
                    FROM ($queryRawData) w
                    WHERE idcuentac IS NOT NULL
                    GROUP BY idcuentac
                ) k ON j.id = k.idcuentac
                LEFT JOIN(
                    SELECT idcuentac, (SUM(debe) - SUM(haber)) AS anterior, FORMAT((SUM(debe) - SUM(haber)), 2) AS anteriorstr
                    FROM ($queryRawDataAnterior) w
                    WHERE idcuentac IS NOT NULL 
                    GROUP BY idcuentac
                ) l ON j.id = l.idcuentac
                WHERE j.idempresa = $d->idempresa ";
    $query.= $d->codigo && !$d->codigoal ? "AND TRIM(j.codigo) IN ($d->codigo) " : '';
    $query.= $d->codigo && $d->codigoal ? "AND TRIM(j.codigo) >= $d->codigo AND TRIM(j.codigo) <= $d->codigoal " : '';
    $query.= "ORDER BY j.codigo";

    $cuentas = $db->getQuery($query);
    $cntCuentas = count($cuentas);
    for($i = 0; $i < $cntCuentas; $i++){
        $cuenta = $cuentas[$i];
        if((int)$cuenta->tipocuenta === 1){
            $query = "SELECT SUM(debe) AS debe, FORMAT(SUM(debe), 2) AS debestr, SUM(haber) AS haber, FORMAT(SUM(haber), 2) AS haberstr ";
            $query.= "FROM ($queryRawData) w ";
            $query.= "WHERE codigo LIKE '$cuenta->codigo%'";
            $sumas = $db->getQuery($query);
            if(count($sumas) > 0){
                $cuenta->debe = $sumas[0]->debe;
                $cuenta->debestr = $sumas[0]->debestr;
                $cuenta->haber = $sumas[0]->haber;
                $cuenta->haberstr = $sumas[0]->haberstr;
            }

            $query = "SELECT (SUM(w.debe) - SUM(w.haber)) AS anterior, FORMAT(SUM(w.debe) - SUM(w.haber), 2) AS anteriorstr ";
            $query.= "FROM ($queryRawDataAnterior) w ";
            $query.= "WHERE w.codigo LIKE '$cuenta->codigo%'";
            $anterior = $db->getQuery($query);
            if(count($anterior) > 0){
                $cuenta->anterior = $anterior[0]->anterior;
                $cuenta->anteriorstr = $anterior[0]->anteriorstr;
                $cuenta->actual = (float)$anterior[0]->anterior + (float)$sumas[0]->debe - (float)$sumas[0]->haber;
                $cuenta->actualstr = number_format($cuenta->actual, 2);
            }
        }else{
            $query = "SELECT poliza, fecha, DATE_FORMAT(fecha, '$db->_formatoFecha') AS fechastr, referencia, transaccion, ";
            $query.= "'' AS anterior, debe, FORMAT(debe, 2) AS debestr, haber, FORMAT(haber, 2) AS haberstr, '' AS actual ";
            $query.= "FROM ($queryRawData) w ";
            $query.= "WHERE idcuentac = $cuenta->id ";
            $query.= "ORDER BY fecha, poliza";
            $cuenta->dlm = $db->getQuery($query);
        }

        if((float)$cuenta->anterior === 0.00 && (float)$cuenta->debe === 0.00 && (float)$cuenta->haber === 0.00 && (float)$cuenta->actual === 0.00){
            $cuenta->mostrar = false;
        }
    }

    $query = "SELECT nomempresa, abreviatura, DATE_FORMAT('$d->fdelstr', '$db->_formatoFecha') AS del, DATE_FORMAT('$d->falstr', '$db->_formatoFecha') AS al, DATE_FORMAT(NOW(), '$db->_formatoFechaHora') AS hoy ";
    $query.= "FROM empresa ";
    $query.= "WHERE id = $d->idempresa";
    $empresa = $db->getQuery($query)[0];
    print json_encode(['parametros' => $d, 'empresa' => $empresa, 'lm' => $cuentas, 'rawant' => $queryRawDataAnterior, 'raw' => $queryRawData]);
});

$app->run();