<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

$app->post('/rptlibmayenc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM rptlibromayor");
    $db->doQuery("ALTER TABLE rptlibromayor AUTO_INCREMENT = 1");
    $db->doQuery("INSERT INTO rptlibromayor(idcuentac, codigo, nombrecta, tipocuenta) SELECT id, codigo, nombrecta, tipocuenta FROM cuentac ORDER BY codigo");
    $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'contrato' => 6];
    foreach($origenes as $k => $v){
        $query = "UPDATE rptlibromayor a INNER JOIN (".getSelectHeader($v, $d, false).") b ON a.idcuentac = b.idcuenta SET a.anterior = a.anterior + b.anterior";
        $db->doQuery($query);
        $query = "UPDATE rptlibromayor a INNER JOIN (".getSelectHeader($v, $d, true).") b ON a.idcuentac = b.idcuenta SET a.debe = a.debe + b.debe, a.haber = a.haber + b.haber";
        $db->doQuery($query);
    }
    $db->doQuery("UPDATE rptlibromayor SET actual = anterior + debe - haber");

    //Calculo de datos para cuentas de totales
    $tamnivdet = [4 => 6, 2 => 6, 1 => 6];
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
            $query.= "WHERE tipocuenta = 0 AND LENGTH(codigo) = ".$tamnivdet[(int)$t->tamnivel]." AND codigo LIKE '".$n->codigo."%'";
            //echo $query."<br/><br/>";
            $sumas = $db->getQuery($query)[0];
            $query = "UPDATE rptlibromayor SET anterior = ".$sumas->anterior.", debe = ".$sumas->debe.", haber = ".$sumas->haber.", actual = ".$sumas->actual." ";
            $query.= "WHERE tipocuenta = 1 AND id = ".$n->id." AND idcuentac = ".$n->idcuentac;
            //echo $query."<br/><br/>";
            $db->doQuery($query);
        }
    }

    print $db->doSelectASJson("SELECT id, idcuentac, codigo, nombrecta, tipocuenta, anterior, debe, haber, actual FROM rptlibromayor ORDER BY codigo");
});

$app->post('/rptlibmaydet', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    print $db->doSelectASJson(getSelectDetail(1, $d));
});

function getSelectHeader($cual, $d, $enrango){
    $query = "";
    switch($cual){
        case 1:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco ";
            $query.= "WHERE a.origen = 1 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND c.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 2:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fechaingreso < '".$d->fdelstr."'" : "b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."'"), $query);
            break;
        case 3:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND a.idempresa = ".$d->idempresa." ";
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
            $query.= "FROM detallecontable a INNER JOIN reembolso b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 5 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa."  AND b.estatus = 2 ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.ffin < '".$d->fdelstr."'" : "b.ffin >= '".$d->fdelstr."' AND b.ffin <= '".$d->falstr."'"), $query);
            break;
        case 6:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN contrato b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 6 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fechacontrato < '".$d->fdelstr."'" : "b.fechacontrato >= '".$d->fdelstr."' AND b.fechacontrato <= '".$d->falstr."'"), $query);
            break;
    }
    return $query;
}

function getSelectDetail($cual, $d){
    $query = "";
    switch($cual){
        case 1:
            //Transacciones bancarias -> origen = 1
            $query = "SELECT a.idcuenta, b.fecha, d.descripcion AS tipomov, c.nombre AS banco, b.numero, b.monto, b.beneficiario, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
            $query.= "WHERE a.origen = 1 AND a.activada = 1 AND a.anulado = 0 AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND c.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Compras -> origen = 2
            $query.= "SELECT a.idcuenta, b.fechaingreso AS fecha, 'Compra' AS tipomov, '' AS banco, CONCAT(b.serie, '-', b.documento) AS numero, b.totfact AS monto, c.nombre AS beneficiario, ";
            $query.= "a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN proveedor c ON c.id = b.idproveedor ";
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND b.idreembolso = 0 AND b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."' ";
            $query.= "AND b.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Ventas -> origen = 3
            $query.= "SELECT a.idcuenta, b.fecha, 'Venta' AS tipomov, '' AS banco, CONCAT(b.serie, '-', b.numero) AS numero, b.total AS monto, ";
            $query.= "CONCAT(d.nombre, ' (GCF', LPAD(c.idcliente, 4, '0'), '-', LPAD(c.correlativo, 4, '0'), ')') AS beneficiario, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen INNER JOIN contrato c ON c.id = b.idcontrato INNER JOIN cliente d ON d.id = b.idcliente ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND c.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            $query.= "SELECT a.idcuenta, b.fecha, 'Venta' AS tipomov, '' AS banco, CONCAT(b.serie, '-', b.numero) AS numero, b.total AS monto, ";
            $query.= "d.nombre AS beneficiario, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen INNER JOIN cliente d ON d.id = b.idcliente ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND a.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Directas -> origen = 4
            $query.= "SELECT a.idcuenta, b.fecha, 'Directa' AS tipomov, '' AS banco, b.id AS numero, 0.00 AS monto, '' AS beneficiario, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen ";
            $query.= "FROM detallecontable a INNER JOIN directa b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 4 AND a.activada = 1 AND a.anulado = 0 AND b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "UNION ALL ";
            //Reembolsos -> origen = 5
            $query.= "SELECT a.idcuenta, b.ffin AS fecha, c.desctiporeembolso AS tipomov, '' AS banco, b.id AS numero, e.totfact AS monto, b.beneficiario, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen ";
            $query.= "FROM detallecontable a INNER JOIN reembolso b ON b.id = a.idorigen INNER JOIN tiporeembolso c ON c.id = b.idtiporeembolso INNER JOIN compra d ON b.id = d.idreembolso ";
            $query.= "INNER JOIN (SELECT idreembolso, SUM(totfact) AS totfact FROM compra WHERE idreembolso > 0 GROUP BY idreembolso) e ON e.idreembolso = b.id ";
            $query.= "WHERE a.origen = 5 AND a.activada = 1 AND a.anulado = 0 AND b.ffin >= '".$d->fdelstr."' AND b.ffin <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." AND b.estatus = 2 ";
            $query.= "UNION ALL ";
            //Contratos -> origen = 6
            $query.= "SELECT a.idcuenta, b.fechacontrato AS fecha, 'Contrato' AS tipomov, '' AS banco, CONCAT('GCF', LPAD(b.idcliente, 4, '0'), '-', LPAD(b.correlativo, 4, '0')) AS numero, ";
            $query.= "0.00 AS monto, '' AS beneficiario, a.conceptomayor, a.debe, a.haber, a.idorigen, a.origen ";
            $query.= "FROM detallecontable a INNER JOIN contrato b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 6 AND a.activada = 1 AND a.anulado = 0 AND b.fechacontrato >= '".$d->fdelstr."' AND b.fechacontrato <= '".$d->falstr."' AND b.idempresa = ".$d->idempresa." ";
            $query.= "ORDER BY 1, 2";
            break;
    }
    return $query;
}

$app->get('/tst', function(){
    $db = new dbcpm();

    $tamnivdet = [4 => 6, 2 => 6, 1 => 6];
    $query = "SELECT DISTINCT LENGTH(codigo) AS tamnivel FROM rptlibromayor WHERE tipocuenta = 1 ORDER BY 1 DESC";
    echo $query."<br/><br/>";
    $tamniveles = $db->getQuery($query);
    foreach($tamniveles as $t){
        echo "Tamaño del nivel = ".$t->tamnivel."<br/><br/>";
        $query = "SELECT id, idcuentac, codigo FROM rptlibromayor WHERE tipocuenta = 1 AND LENGTH(codigo) = ".$t->tamnivel." ORDER BY codigo";
        echo $query."<br/><br/>";
        $niveles = $db->getQuery($query);
        foreach($niveles as $n){
            echo "LENGTH(codigo) = ".$tamnivdet[(int)$t->tamnivel]."<br/><br/>";
            echo "Codigo = ".$n->codigo."<br/><br/>";
            $query = "SELECT SUM(anterior) AS anterior, SUM(debe) AS debe, SUM(haber) AS haber, SUM(actual) AS actual ";
            $query.= "FROM rptlibromayor ";
            $query.= "WHERE tipocuenta = 0 AND LENGTH(codigo) = ".$tamnivdet[(int)$t->tamnivel]." AND codigo LIKE '".$n->codigo."%'";
            echo $query."<br/><br/>";
            $sumas = $db->getQuery($query)[0];
            $query = "UPDATE rptlibromayor SET anterior = ".$sumas->anterior.", debe = ".$sumas->debe.", haber = ".$sumas->haber.", actual = ".$sumas->actual." ";
            $query.= "WHERE tipocuenta = 1 AND id = ".$n->id." AND idcuentac = ".$n->idcuentac;
            echo $query."<br/><br/>";
            $db->doQuery($query);
        }
    }

});

$app->run();