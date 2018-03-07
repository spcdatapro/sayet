<?php
set_time_limit(0);
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptestres', function(){
    $d = json_decode(file_get_contents('php://input'));
    //$d = new forTest(); $d->fdelstr = ''; $d->falstr = '2016-12-31'; $d->idempresa = 1;
    $db = new dbcpm();
    $db->doQuery("DELETE FROM rptestadoresultados");
    $db->doQuery("ALTER TABLE rptestadoresultados AUTO_INCREMENT = 1");
    $inggas = [1, 0]; //1 = ingresos; 0 = gastos
    //$origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'contrato' => 6, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
    $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
    foreach($inggas AS $ig){
        $ctasing = $db->getQuery("SELECT b.descripcion AS tipo, a.empiezancon, b.ingresos FROM confrptcont a INNER JOIN tiporptconfcont b ON b.id = a.idtiporptconfcont WHERE b.estres = 1 AND b.ingresos = ".$ig);
        foreach($ctasing as $ing){
            $inicianCon = preg_split('/\D/', $ing->empiezancon, NULL, PREG_SPLIT_NO_EMPTY);
            foreach($inicianCon as $ini){
                $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos) ";
                $query.= "SELECT id, codigo, nombrecta, tipocuenta, ".$ig." FROM cuentac WHERE idempresa = ".$d->idempresa." AND codigo LIKE '".$ini."%' ORDER BY codigo";
                $db->doQuery($query);
                foreach($origenes as $k => $v){
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$d->acumulado == 1), $ini).") b ON a.idcuenta = b.idcuenta SET a.saldo = a.saldo + b.anterior";
                    $db->doQuery($query);
                }
                $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos, saldo, parasuma) ";
                $query.= "SELECT 0, '', 'Subtotal de cuentas de ".($ig == 1 ? "ingreso" : "gasto")." que inician con ".$ini." --->', 1, ".$ig.", SUM(saldo), 1 ";
                $query.= "FROM rptestadoresultados WHERE ingresos = ".$ig." AND tipocuenta = 0 AND LENGTH(codigo) <= 7 AND codigo LIKE '$ini%'";
                $db->doQuery($query);
            }
        }
        $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos, saldo, estotal) ";
        $query.= "SELECT 0, '99999', 'Total de ".($ig == 1 ? "ingresos" : "gastos")."', 1, ".$ig.", SUM(saldo), 1 ";
        $query.= "FROM rptestadoresultados WHERE ingresos = ".$ig." AND parasuma = 1";
        $db->doQuery($query);
    }

    $query = "SELECT ABS(a.ingresos) - ABS(b.gastos) AS estado ";
    $query.= "FROM (SELECT idcuenta, saldo AS ingresos FROM rptestadoresultados WHERE estotal = 1 AND ingresos = 1) a ";
    $query.= "INNER JOIN (SELECT idcuenta, saldo AS gastos FROM rptestadoresultados WHERE estotal = 1 AND ingresos = 0) b ON a.idcuenta = b.idcuenta";
    $estado = round((float)$db->getOneField($query), 2);
    $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, saldo, tipocuenta) VALUES(";
    $query.= "0, '99999', '".($estado >= 0 ? "Ganancia" : "Perdida")." del ejercicio', ".$estado.", 1";
    $query.= ")";
    $db->doQuery($query);

    //Calculo de datos para cuentas de totales
    //$tamnivdet = [4 => 6, 2 => 6, 1 => 6];
    $query = "SELECT DISTINCT LENGTH(codigo) AS tamnivel FROM rptestadoresultados WHERE tipocuenta = 1 AND LENGTH(codigo) > 0 ORDER BY 1 DESC";
    $tamniveles = $db->getQuery($query);
    foreach($tamniveles as $t){
        $query = "SELECT id, idcuenta, codigo FROM rptestadoresultados WHERE tipocuenta = 1 AND LENGTH(codigo) = ".$t->tamnivel." ORDER BY codigo";
        $niveles = $db->getQuery($query);
        foreach($niveles as $n){
            $query = "SELECT SUM(saldo) AS saldo ";
            $query.= "FROM rptestadoresultados ";
            $query.= "WHERE tipocuenta = 0 AND LENGTH(codigo) <= 7 AND codigo LIKE '".$n->codigo."%'";
            $sumas = $db->getQuery($query)[0];
            $query = "UPDATE rptestadoresultados SET saldo = ".$sumas->saldo." WHERE tipocuenta = 1 AND id = ".$n->id." AND idcuenta = ".$n->idcuenta;
            $db->doQuery($query);
        }
    }

    $query = "SELECT id, idcuenta, codigo, nombrecta, tipocuenta, ingresos, parasuma, estotal, saldo ";
    $query.= "FROM rptestadoresultados ";
    $query.= "WHERE saldo <> 0.00 AND nombrecta NOT LIKE 'Subtotal de cuentas de%' AND LENGTH(codigo) <= $d->nivel";

    $empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
    print json_encode(['empresa' => $empresa, 'datos'=> $db->getQuery($query)]);
});

function getSelect($cual, $d, $enrango, $ini){
    $query = "";
    switch($cual){
        case 1:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN cuentac d ON d.id = a.idcuenta ";
            $query.= "WHERE a.origen = 1 AND a.activada = 1 AND FILTROFECHA AND c.idempresa = ".$d->idempresa." AND d.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ?
                //"((b.anulado = 0 AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha <= '$d->falstr' AND b.fechaanula > '$d->falstr'))" :
				"((b.anulado = 0 AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha <= '$d->falstr'))" :
                //"((b.anulado = 0 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr' AND b.fechaanula > '$d->falstr'))"
				"((b.anulado = 0 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr') OR (b.anulado = 1 AND b.fecha >= '$d->fdelstr' AND b.fecha <= '$d->falstr'))"
            ), $query);
            break;
        case 2:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND b.idreembolso = 0 AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fechaingreso <= '".$d->falstr."'" : "b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."'"), $query);
            break;
        case 3:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fecha <= '".$d->falstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 4:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN directa b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 4 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fecha <= '".$d->falstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 5:
            /*
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN reembolso b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 5 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa."  AND b.estatus = 2 AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.ffin <= '".$d->falstr."'" : "b.ffin >= '".$d->fdelstr."' AND b.ffin <= '".$d->falstr."'"), $query);
            */
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta INNER JOIN reembolso d ON d.id = b.idreembolso ";
            $query.= "WHERE a.origen = 2 AND a.anulado = 0 AND b.idreembolso > 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fechaingreso <= '".$d->falstr."'" : "b.fechaingreso >= '".$d->fdelstr."' AND b.fechaingreso <= '".$d->falstr."'"), $query);
            break;
        /*
        case 6:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN contrato b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 6 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fechacontrato <= '".$d->falstr."'" : "b.fechacontrato >= '".$d->fdelstr."' AND b.fechacontrato <= '".$d->falstr."'"), $query);
            break;
        */
        case 7:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN reciboprov b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 7 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fecha <= '".$d->falstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 8:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN recibocli b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 8 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fecha <= '".$d->falstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 9:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN cuentac d ON d.id = a.idcuenta ";
            $query.= "WHERE a.origen = 9 AND a.activada = 1 AND FILTROFECHA AND c.idempresa = ".$d->idempresa." AND d.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ?
                //"((b.anulado = 0 AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida <= '$d->falstr' AND b.fechaanula > '$d->falstr'))" :
				"((b.anulado = 0 AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida <= '$d->falstr'))" :
                //"((b.anulado = 0 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr' AND b.fechaanula > '$d->falstr'))"
				"((b.anulado = 0 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr') OR (b.anulado = 1 AND b.fechaliquida >= '$d->fdelstr' AND b.fechaliquida <= '$d->falstr'))"
            ), $query);
            break;
        case 10:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN ncdcliente b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 10 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '$ini%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fecha <= '".$d->falstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
        case 11:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN ncdproveedor b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 11 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '$ini%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ($enrango ? "b.fecha <= '".$d->falstr."'" : "b.fecha >= '".$d->fdelstr."' AND b.fecha <= '".$d->falstr."'"), $query);
            break;
    }
    return $query;
}


$app->run();