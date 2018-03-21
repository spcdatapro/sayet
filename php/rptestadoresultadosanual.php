<?php
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
    $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'recprov' => 7, 'reccli' => 8/*'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11*/];
    foreach($inggas AS $ig){
        $ctasing = $db->getQuery("SELECT b.descripcion AS tipo, a.empiezancon, b.ingresos FROM confrptcont a INNER JOIN tiporptconfcont b ON b.id = a.idtiporptconfcont WHERE b.estres = 1 AND b.ingresos = ".$ig);
        foreach($ctasing as $ing){
            $inicianCon = preg_split('/\D/', $ing->empiezancon, NULL, PREG_SPLIT_NO_EMPTY);
            foreach($inicianCon as $ini){
                $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos) ";
                $query.= "SELECT id, codigo, nombrecta, tipocuenta, ".$ig." FROM cuentac WHERE idempresa = ".$d->idempresa." AND codigo LIKE '".$ini."%' ORDER BY codigo";
                $db->doQuery($query);
                foreach($origenes as $k => $v){
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 1),  $ini).") b ON a.idcuenta = b.idcuenta SET a.s_ene = a.s_ene + b.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 2),  $ini).") c ON a.idcuenta = c.idcuenta SET a.s_feb = a.s_feb + c.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 3),  $ini).") d ON a.idcuenta = d.idcuenta SET a.s_mar = a.s_mar + d.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 4),  $ini).") e ON a.idcuenta = e.idcuenta SET a.s_abr = a.s_abr + e.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 5),  $ini).") f ON a.idcuenta = f.idcuenta SET a.s_may = a.s_may + f.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 6),  $ini).") g ON a.idcuenta = g.idcuenta SET a.s_jun = a.s_jun + g.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 7),  $ini).") h ON a.idcuenta = h.idcuenta SET a.s_jul = a.s_jul + h.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 8),  $ini).") i ON a.idcuenta = i.idcuenta SET a.s_ago = a.s_ago + i.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 9),  $ini).") j ON a.idcuenta = j.idcuenta SET a.s_sep = a.s_sep + j.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 10), $ini).") k ON a.idcuenta = k.idcuenta SET a.s_oct = a.s_oct + k.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 11), $ini).") l ON a.idcuenta = l.idcuenta SET a.s_nov = a.s_nov + l.anterior ";  $db->doQuery($query);
                    $query = "UPDATE rptestadoresultados a INNER JOIN (".getSelect($v, $d, ((int)$m = 12), $ini).") m ON a.idcuenta = m.idcuenta SET a.s_dic = a.s_dic + m.anterior ";  $db->doQuery($query);
                }
                // Para suma del período
                //$query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos, saldo, parasuma) ";
                //$query.= "SELECT 0, '', 'Subtotal de cuentas de ".($ig == 1 ? "ingreso" : "gasto")." que inician con ".$ini." --->', 1, ".$ig.", SUM(saldo), 1 ";
                //$query.= "FROM rptestadoresultados WHERE ingresos = ".$ig." AND tipocuenta = 0 AND LENGTH(codigo) <= 7";
                // Para suma mensual
                $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos, saldo, parasuma, s_ene, s_feb,s_mar,s_abr,s_may,s_jun,s_jul,s_ago,s_sep,s_oct,s_nov,s_dic) ";
                $query.= "SELECT 0, '', 'Subtotal de cuentas de ".($ig == 1 ? "ingreso" : "gasto")." que inician con ".$ini." --->', 1, ".$ig.", SUM(saldo), 1 , ";
                $query.= "SUM(s_ene), SUM(s_feb),SUM(s_mar),SUM(s_abr),SUM(s_may),SUM(s_jun),SUM(s_jul),SUM(s_ago),SUM(s_sep),SUM(s_oct),SUM(s_nov),SUM(s_dic) ";
                $query.= "FROM rptestadoresultados WHERE ingresos = ".$ig." AND tipocuenta = 0 AND LENGTH(codigo) <= 7";
                $db->doQuery($query);
            }
        }
         //Total del período
        /*$query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos, saldo, estotal) ";
        $query.= "SELECT 0, '99999', 'Total de ".($ig == 1 ? "ingresos" : "gastos")."', 1, ".$ig.", SUM(saldo), 1 ";
        $query.= "FROM rptestadoresultados WHERE ingresos = ".$ig." AND parasuma = 1";*/
         //Totales por meses
        $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, tipocuenta, ingresos, saldo, estotal,s_ene,s_feb,s_mar,s_abr,s_may,s_jun,s_jul,s_ago,s_sep,s_oct,s_nov,s_dic) ";
        $query.= "SELECT 0, '99999', 'Total de ".($ig == 1 ? "ingresos" : "gastos")."', 1, ".$ig.", SUM(saldo), 1,SUM(s_ene),SUM(s_feb),SUM(s_mar),SUM(s_abr),SUM(s_may),SUM(s_jun),SUM(s_jul),SUM(s_ago),SUM(s_sep),SUM(s_oct),SUM(s_nov),SUM(s_dic) ";
        $query.= "FROM rptestadoresultados WHERE ingresos = ".$ig." AND parasuma = 1 ";
        $db->doQuery($query);
    }
    //trae el total
    /*
    $query = "SELECT ABS(a.ingresos) - ABS(b.gastos) AS estado ";
    $query.= "FROM (SELECT idcuenta, saldo AS ingresos FROM rptestadoresultados WHERE estotal = 1 AND ingresos = 1) a ";
    $query.= "INNER JOIN (SELECT idcuenta, saldo AS gastos FROM rptestadoresultados WHERE estotal = 1 AND ingresos = 0) b ON a.idcuenta = b.idcuenta";
    $estado = round((float)$db->getOneField($query), 2);
    $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, saldo, tipocuenta) VALUES(";
    $query.= "0, '99999', '".($estado >= 0 ? "Ganancia" : "Perdida")." del ejercicio', ".$estado.", 1";
    $query.= ")";*/
    // Resultado del período mensual
    $query = "INSERT INTO rptestadoresultados(idcuenta, codigo, nombrecta, s_ene,s_feb,s_mar,s_abr,s_may,s_jun,s_jul,s_ago,s_sep,s_oct,s_nov,s_dic, tipocuenta) ";
    $query.= "SELECT 0, '99999', 'Resultado del ejercicio', ABS(a.s_ene) - ABS(b.s_ene) AS s_ene, ABS(a.s_feb) - ABS(b.s_feb) AS s_feb, ABS(a.s_mar) - ABS(b.s_mar) AS s_mar ,ABS(a.s_abr) - ABS(b.s_abr) AS s_abr ,ABS(a.s_may) - ABS(b.s_may) AS s_may ,ABS(a.s_jun) - ABS(b.s_jun) AS s_jun , ";
    $query.= "ABS(a.s_jul) - ABS(b.s_jul) AS s_jul ,ABS(a.s_ago) - ABS(b.s_ago) AS s_ago ,ABS(a.s_sep) - ABS(b.s_sep) AS s_sep ,ABS(a.s_oct) - ABS(b.s_oct) AS s_oct ,ABS(a.s_nov) - ABS(b.s_nov) AS s_nov ,ABS(a.s_dic) - ABS(b.s_dic) AS s_dic ,1  ";
    $query.= "FROM (SELECT idcuenta, s_ene,s_feb,s_mar,s_abr,s_may,s_jun,s_jul,s_ago,s_sep,s_oct,s_nov,s_dic  FROM rptestadoresultados WHERE estotal = 1 AND ingresos = 1) a ";
    $query.= "INNER JOIN (SELECT idcuenta, s_ene,s_feb,s_mar,s_abr,s_may,s_jun,s_jul,s_ago,s_sep,s_oct,s_nov,s_dic  FROM rptestadoresultados WHERE estotal = 1 AND ingresos = 0) b ON a.idcuenta = b.idcuenta ";
    $db->doQuery($query);

    //Calculo de datos para cuentas de totales
    //$tamnivdet = [4 => 6, 2 => 6, 1 => 6];

    $query = "SELECT DISTINCT LENGTH(codigo) AS tamnivel FROM rptestadoresultados WHERE tipocuenta = 1 AND LENGTH(codigo) > 0 ORDER BY 1 DESC";
    $tamniveles = $db->getQuery($query);
    foreach($tamniveles as $t){
        $query = "SELECT id, idcuenta, codigo FROM rptestadoresultados WHERE tipocuenta = 1 AND LENGTH(codigo) = ".$t->tamnivel." ORDER BY codigo";
        $niveles = $db->getQuery($query);
        foreach($niveles as $n){
            $query = "SELECT SUM(s_ene) AS s_ene,SUM(s_feb) AS s_feb,SUM(s_mar) AS s_mar,SUM(s_abr) AS s_abr, ";
            $query.= "SUM(s_may) AS s_may,SUM(s_jun) AS s_jun,SUM(s_jul) AS s_jul,SUM(s_ago) AS s_ago, ";
            $query.= "SUM(s_sep) AS s_sep,SUM(s_oct) AS s_oct,SUM(s_nov) AS s_nov,SUM(s_dic) AS s_dic   ";
            $query.= "FROM rptestadoresultados  ";
            $query.= "WHERE tipocuenta = 0 AND LENGTH(codigo) <= 7 AND codigo LIKE '".$n->codigo."%'";
            $sumas = $db->getQuery($query)[0];
            $query = "UPDATE rptestadoresultados SET s_ene = ".$sumas->s_ene."  
                                                   , s_feb = ".$sumas->s_feb." 
                                                   , s_mar = ".$sumas->s_mar." 
                                                   , s_abr = ".$sumas->s_abr." 
                                                   , s_may = ".$sumas->s_may." 
                                                   , s_jun = ".$sumas->s_jun." 
                                                   , s_jul = ".$sumas->s_jul." 
                                                   , s_ago = ".$sumas->s_ago." 
                                                   , s_sep = ".$sumas->s_sep." 
                                                   , s_oct = ".$sumas->s_oct." 
                                                   , s_nov = ".$sumas->s_nov." 
                                                   , s_dic = ".$sumas->s_dic."  WHERE tipocuenta = 1 AND id = ".$n->id." AND idcuenta = ".$n->idcuenta; $db->doQuery($query);

        }
    }
    //trae todo el cuerpo de el pdf
    $query = "SELECT id, idcuenta, codigo, nombrecta, tipocuenta, ingresos, parasuma, estotal, saldo, s_ene, s_feb, s_mar, s_abr, s_may, s_jun, s_jul, s_ago, s_sep, s_oct, s_nov, s_dic ";
    $query.= "FROM rptestadoresultados ";
    $query.= "WHERE nombrecta NOT LIKE '%Subtotal de cuentas de%' AND (s_ene <> 0.00 OR s_feb <> 0.00 OR s_mar <> 0.00 OR s_abr <> 0.00 OR s_may <> 0.00 OR s_jun <> 0.00 OR s_jul <> 0.00 OR s_ago <> 0.00 OR s_sep <> 0.00 OR s_oct <> 0.00 OR s_nov <> 0.00 OR s_dic <> 0.00)  ";


    $empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
    print json_encode(['empresa' => $empresa, 'datos'=> $db->getQuery($query)]);
}); 


function getSelect($cual, $d, $mes, $ini){
    $query = "";
    switch($cual){
        case 1:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN cuentac d ON d.id = a.idcuenta ";
            $query.= "WHERE a.origen = 1 AND a.activada = 1 AND FILTROFECHA AND c.idempresa = ".$d->idempresa." AND d.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("((b.anulado = 0 AND YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes' ) OR (b.anulado = 1 AND YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes'))"), $query);
            break;
        case 2:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 2 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND b.idreembolso = 0 AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (" YEAR(b.fechaingreso) = '$d->resAn' AND MONTh(b.fechaingreso) = '$mes'"), $query);
            break;
        case 3:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN factura b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 3 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes'"), $query);
            break;
        case 4:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN directa b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 4 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes'"), $query);
            break;
        case 5:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta INNER JOIN reembolso d ON d.id = b.idreembolso ";
            $query.= "WHERE a.origen = 2 AND a.anulado = 0 AND b.idreembolso > 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("YEAR(b.fechaingreso) = '$d->resAn' AND MONTh(b.fechaingreso) = '$mes'"), $query);
            break;
        case 7:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN reciboprov b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 7 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes'"), $query);
            break;
        case 8:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN recibocli b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 8 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes'"), $query);
            break;
        /*
        case 9:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN tranban b ON b.id = a.idorigen INNER JOIN banco c ON c.id = b.idbanco INNER JOIN cuentac d ON d.id = a.idcuenta ";
            $query.= "WHERE a.origen = 9 AND a.activada = 1 AND FILTROFECHA AND c.idempresa = ".$d->idempresa." AND d.codigo LIKE '".$ini."%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("((b.anulado = 0 AND YEAR(b.fechaliquida) = '$d->resAn' AND MONTh(b.fechaliquida) = '$mes' )) OR (b.anulado = 1 AND YEAR(b.fechaliquida) = '$d->resAn' AND MONTh(b.fechaliquida) = '$mes'))"), $query);
            break;
        case 10:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN ncdcliente b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 10 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '$ini%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes'"), $query);
            break;
        case 11:
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN ncdproveedor b ON b.id = a.idorigen INNER JOIN cuentac c ON c.id = a.idcuenta ";
            $query.= "WHERE a.origen = 11 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." AND c.codigo LIKE '$ini%' ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", ("YEAR(b.fecha) = '$d->resAn' AND MONTh(b.fecha) = '$mes'"), $query);
            break;
        */
    }
    return $query;
}


$app->run();