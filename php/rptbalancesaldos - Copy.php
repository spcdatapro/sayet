<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptbalsal', function(){
    $d = json_decode(file_get_contents('php://input'));
    try{
        $db = new dbcpm();
        $db->doQuery("DELETE FROM rptbalancesaldos");
        $db->doQuery("ALTER TABLE rptbalancesaldos AUTO_INCREMENT = 1");
        $db->doQuery("INSERT INTO rptbalancesaldos(idcuentac, codigo, nombrecta, tipocuenta) SELECT id, codigo, nombrecta, tipocuenta FROM cuentac WHERE idempresa = $d->idempresa ORDER BY codigo");
        //$origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'contrato' => 6, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
        $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
        foreach($origenes as $k => $v){
            $query = "UPDATE rptbalancesaldos a INNER JOIN (".getSelect($v, $d, false).") b ON a.idcuentac = b.idcuenta SET a.anterior = a.anterior + b.anterior";
            $db->doQuery($query);
            $query = "UPDATE rptbalancesaldos a INNER JOIN (".getSelect($v, $d, true).") b ON a.idcuentac = b.idcuenta SET a.debe = a.debe + b.debe, a.haber = a.haber + b.haber";
            $db->doQuery($query);
        }
        $db->doQuery("UPDATE rptbalancesaldos SET actual = anterior + debe - haber");

        //Calculo de datos para cuentas de totales
        //$tamnivdet = [4 => 6, 2 => 6, 1 => 6];
        $query = "SELECT DISTINCT LENGTH(codigo) AS tamnivel FROM rptbalancesaldos WHERE tipocuenta = 1 ORDER BY 1 DESC";
        //echo $query."<br/><br/>";
        $tamniveles = $db->getQuery($query);
        foreach($tamniveles as $t){
            //echo "Tamaño del nivel = ".$t->tamnivel."<br/><br/>";
            $query = "SELECT id, idcuentac, codigo FROM rptbalancesaldos WHERE tipocuenta = 1 AND LENGTH(codigo) = ".$t->tamnivel." ORDER BY codigo";
            //echo $query."<br/><br/>";
            $niveles = $db->getQuery($query);
            foreach($niveles as $n){
                //echo "LENGTH(codigo) = ".$tamnivdet[(int)$t->tamnivel]."<br/><br/>";
                //echo "Codigo = ".$n->codigo."<br/><br/>";
                $query = "SELECT SUM(anterior) AS anterior, SUM(debe) AS debe, SUM(haber) AS haber, SUM(actual) AS actual ";
                $query.= "FROM rptbalancesaldos ";
                $query.= "WHERE tipocuenta = 0 AND LENGTH(codigo) <= 7 AND codigo LIKE '".$n->codigo."%'";
                //echo $query."<br/><br/>";
                $sumas = $db->getQuery($query)[0];
                $query = "UPDATE rptbalancesaldos SET anterior = ".$sumas->anterior.", debe = ".$sumas->debe.", haber = ".$sumas->haber.", actual = ".$sumas->actual." ";
                $query.= "WHERE tipocuenta = 1 AND id = ".$n->id." AND idcuentac = ".$n->idcuentac;
                //echo $query."<br/><br/>";
                $db->doQuery($query);
            }
        }

        $query = "SELECT id, idcuentac, codigo, nombrecta, tipocuenta, anterior, debe, haber, actual ";
        $query.= "FROM rptbalancesaldos ";
        //$query.= "WHERE tipocuenta = 0 ";
        $query.= (int)$d->solomov == 1 ? "WHERE (anterior <> 0 OR debe <> 0 OR haber <> 0 OR actual <> 0) " : "";
        $query.= "ORDER BY codigo";

        //print $db->doSelectASJson($query);
        $empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
        print json_encode(['empresa' => $empresa, 'datos'=> $db->getQuery($query)]);

    }catch(Exception $e){
        $error = "Mensaje: ".$e->getMessage()." -- Linea: ".$e->getLine()." -- Objeto: ".json_encode($d);
        $query = "SELECT 0 AS id, 0 AS idcuentac, '000000' AS codigo, '".$error."' AS nombrecta, 0 AS tipocuenta, 0 AS anterior, 0 AS debe, 0 AS haber, 0 AS actual";
        print $db->doSelectASJson($query);
    }
});

function getSelect($cual, $d, $enrango){
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
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND b.idreembolso = 0 ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.fechaingreso < '".$d->fdelstr."'" : "b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."'"), $query);
            break;
        case 3:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fecha < '".$d->fdelstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
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
                //"((b.anulado = 0 AND b.fechaliquida < '$d->fdelstr') OR (b.anulado = 1 AND b.fechaliquida < '$d->fdelstr' AND b.fechaanula >= '$d->fdelstr'))" :
				"((b.anulado = 0 AND b.fechaliquida < '$d->fdelstr') OR (b.anulado = 1 AND b.fechaliquida < '$d->fdelstr'))" :
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

$app->run();