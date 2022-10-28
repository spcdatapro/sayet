<?php
set_time_limit(0);
require 'vendor/autoload.php';
require_once 'db.php';
require_once  'conta.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptbalsal', function(){
    $d = json_decode(file_get_contents('php://input'));
    try{
        $db = new dbcpm();
        $tblname = $db->crearTablasReportesConta('bs');
        //$db->doQuery("DELETE FROM $tblname");
        //$db->doQuery("ALTER TABLE $tblname AUTO_INCREMENT = 1");
        $db->doQuery("INSERT INTO $tblname(idcuentac, codigo, nombrecta, tipocuenta) SELECT id, codigo, nombrecta, tipocuenta FROM cuentac WHERE idempresa = $d->idempresa ORDER BY codigo");
        //$origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'contrato' => 6, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
        $origenes = ['tranban' => 1, 'compra' => 2, 'venta' => 3, 'directa' => 4, 'reembolso' => 5, 'recprov' => 7, 'reccli' => 8, 'liquidadoc' => 9, 'ncdclientes' => 10, 'ncdproveedores' => 11];
        foreach($origenes as $k => $v){
            $query = "UPDATE $tblname a INNER JOIN (".getSelect($v, $d, false).") b ON a.idcuentac = b.idcuenta SET a.anterior = a.anterior + b.anterior";
            $db->doQuery($query);
            $query = "UPDATE $tblname a INNER JOIN (".getSelect($v, $d, true).") b ON a.idcuentac = b.idcuenta SET a.debe = a.debe + b.debe, a.haber = a.haber + b.haber";
            $db->doQuery($query);
            //if($v == 3){ exit(); }
        }
        $db->doQuery("UPDATE $tblname SET actual = anterior + debe - haber");

        //Calculo de datos para cuentas de totales
        //$tamnivdet = [4 => 6, 2 => 6, 1 => 6];
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
        $query.= "WHERE LENGTH(codigo) <= $d->nivel ";
        $query.= (int)$d->solomov == 1 ? "AND (anterior <> 0 OR debe <> 0 OR haber <> 0 OR actual <> 0) " : "";
        $query.= "ORDER BY codigo";

        //print $db->doSelectASJson($query);
        $empresa = $db->getQuery("SELECT nomempresa, abreviatura FROM empresa WHERE id = $d->idempresa")[0];
        print json_encode(['empresa' => $empresa, 'datos'=> $db->getQuery($query)]);
        $db->eliminarTablasRepConta($tblname);

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
            /*
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN reembolso b ON b.id = a.idorigen ";
            $query.= "WHERE a.origen = 5 AND a.activada = 1 AND a.anulado = 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa."  AND b.estatus = 2 ";
            $query.= "GROUP BY a.idcuenta ORDER BY a.idcuenta";
            $query = str_replace("FILTROFECHA", (!$enrango ? "b.ffin < '".$d->fdelstr."'" : "b.ffin >= '".$d->fdelstr."' AND b.ffin <= '".$d->falstr."'"), $query);
            */
            $query = "SELECT a.idcuenta, SUM(a.debe) AS debe, SUM(a.haber) AS haber, (SUM(a.debe) - SUM(a.haber)) AS anterior ";
            $query.= "FROM detallecontable a INNER JOIN compra b ON b.id = a.idorigen INNER JOIN reembolso c ON c.id = b.idreembolso ";
            $query.= "WHERE a.origen = 2 AND a.anulado = 0 AND b.idreembolso > 0 AND FILTROFECHA AND b.idempresa = ".$d->idempresa." ";
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

// version anterior

// $app->post('/balancesaldos', function(){
//     $d = json_decode(file_get_contents('php://input'));
//     if(!isset($d->vercierre)){ $d->vercierre = 0; }
//     if(!isset($d->nivel)){ $d->nivel = 10; }
//     if(!isset($d->solomov)){ $d->solomov = 1; }
//     $db = new dbcpm();

//     $query = "SELECT nomempresa, abreviatura, DATE_FORMAT('$d->fdelstr', '$db->_formatoFecha') AS del, DATE_FORMAT('$d->falstr', '$db->_formatoFecha') AS al, DATE_FORMAT(NOW(), '$db->_formatoFechaHora') AS hoy, ";
//     $query.= "0.00 AS anterior, 0.00 AS debe, 0.00 AS haber, 0.00 AS actual, ";
//     $query.= "0.00 AS anteriorstr, 0.00 AS debestr, 0.00 AS haberstr, 0.00 AS actualstr ";
//     $query.= "FROM empresa ";
//     $query.= "WHERE id = $d->idempresa";
//     $empresa = $db->getQuery($query)[0];

//     $conta = new contabilidad($d->fdelstr, $d->falstr, $d->idempresa, (int)$d->vercierre);
//     $queryRawData = $conta->getDatosEnCrudo();
//     $queryRawDataAnterior = $conta->getDatosEnCrudoAnterior();

//     $query = "SELECT j.id, j.codigo, j.nombrecta, j.tipocuenta, 
//                 IFNULL(l.anterior, 0.00) AS anterior, IFNULL(l.anteriorstr, 0.00) AS anteriorstr, IFNULL(k.debe, 0.00) AS debe, FORMAT(IFNULL(k.debe, 0.00), 2) AS debestr, IFNULL(k.haber, 0.00) AS haber, FORMAT(IFNULL(k.haber, 0.00), 2) AS haberstr, 
//                 (IFNULL(l.anterior, 0.00) + IFNULL(k.debe, 0.00) - IFNULL(k.haber, 0.00)) AS actual, FORMAT((IFNULL(l.anterior, 0.00) + IFNULL(k.debe, 0.00) - IFNULL(k.haber, 0.00)), 2) AS actualstr, 1 AS mostrar
//                 FROM cuentac j
//                 LEFT JOIN (
//                     SELECT idcuentac, SUM(debe) AS debe, SUM(haber) AS haber
//                     FROM ($queryRawData) w
//                     WHERE idcuentac IS NOT NULL
//                     GROUP BY idcuentac
//                 ) k ON j.id = k.idcuentac
//                 LEFT JOIN(
//                     SELECT idcuentac, (SUM(debe) - SUM(haber)) AS anterior, FORMAT((SUM(debe) - SUM(haber)), 2) AS anteriorstr
//                     FROM ($queryRawDataAnterior) w
//                     WHERE idcuentac IS NOT NULL 
//                     GROUP BY idcuentac
//                 ) l ON j.id = l.idcuentac
//                 WHERE j.idempresa = $d->idempresa ";
//     $query.= "ORDER BY j.codigo";

//     $cuentas = $db->getQuery($query);
//     $cntCuentas = count($cuentas);
//     for($i = 0; $i < $cntCuentas; $i++){
//         $cuenta = $cuentas[$i];
//         if((int)$cuenta->tipocuenta === 1){
//             $query = "SELECT SUM(debe) AS debe, FORMAT(SUM(debe), 2) AS debestr, SUM(haber) AS haber, FORMAT(SUM(haber), 2) AS haberstr ";
//             $query.= "FROM ($queryRawData) w ";
//             $query.= "WHERE codigo LIKE '$cuenta->codigo%'";
//             $sumas = $db->getQuery($query);
//             if(count($sumas) > 0){
//                 $cuenta->debe = $sumas[0]->debe;
//                 $cuenta->debestr = $sumas[0]->debestr;
//                 $cuenta->haber = $sumas[0]->haber;
//                 $cuenta->haberstr = $sumas[0]->haberstr;
//             }

//             $query = "SELECT (SUM(w.debe) - SUM(w.haber)) AS anterior, FORMAT(SUM(w.debe) - SUM(w.haber), 2) AS anteriorstr ";
//             $query.= "FROM ($queryRawDataAnterior) w ";
//             $query.= "WHERE w.codigo LIKE '$cuenta->codigo%'";
//             $anterior = $db->getQuery($query);
//             if(count($anterior) > 0){
//                 $cuenta->anterior = $anterior[0]->anterior;
//                 $cuenta->anteriorstr = $anterior[0]->anteriorstr;
//                 $cuenta->actual = (float)$anterior[0]->anterior + (float)$sumas[0]->debe - (float)$sumas[0]->haber;
//                 $cuenta->actualstr = number_format($cuenta->actual, 2);
//             }
//         }

//         if(((float)$cuenta->anterior === 0.00 && (float)$cuenta->debe === 0.00 && (float)$cuenta->haber === 0.00 && (float)$cuenta->actual === 0.00) || strlen(trim($cuenta->codigo)) > (int)$d->nivel){
//             $cuenta->mostrar = false;
//         }

//         if($cuenta->mostrar && (int)$cuenta->tipocuenta === 0){
//             $empresa->anterior += (float)$cuenta->anterior;
//             $empresa->debe += (float)$cuenta->debe;
//             $empresa->haber += (float)$cuenta->haber;
//             $empresa->actual += (float)$cuenta->actual;
//         }
//     }

//     $empresa->anteriorstr = number_format($empresa->anterior, 2);
//     $empresa->debestr = number_format($empresa->debe, 2);
//     $empresa->haberstr = number_format($empresa->haber, 2);
//     $empresa->actualstr = number_format($empresa->actual, 2);

//     print json_encode(['parametros' => $d, 'empresa' => $empresa, 'datos' => $cuentas, 'rawant' => $queryRawDataAnterior, 'raw' => $queryRawData]);
// });

function getSumaCuentaTotales($lista, $cuenta, $actual = true)
{
    $datos = new stdClass();
    $datos->debe = 0.0;
    $datos->haber = 0.0;
    $datos->anterior = 0.0;
    $cuenta = $cuenta && is_string($cuenta) ? trim($cuenta) : '';
    $tamanio = strlen($cuenta);
    if ($tamanio > 0 && count($lista) > 0) {
        foreach ($lista as $item) {
            $iniciaCon = substr($item->codigo, 0, $tamanio);
            if (strcasecmp($cuenta, $iniciaCon) === 0) {
                if ($actual) {
                    $datos->debe += (float)$item->debe;
                    $datos->haber += (float)$item->haber;
                } else {
                    $datos->anterior += (float)$item->anterior;
                }
            }
        }
        return $datos;
    }
    return false;
}

$app->post('/balancesaldos', function () {
    set_time_limit(0);
    ini_set('memory_limit', '-1');
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->vercierre)) {
        $d->vercierre = 0;
    }
    if (!isset($d->nivel)) {
        $d->nivel = 10;
    }
    if (!isset($d->solomov)) {
        $d->solomov = 1;
    }
    $db = new dbcpm();

    $query = "SELECT nomempresa, abreviatura, DATE_FORMAT('$d->fdelstr', '$db->_formatoFecha') AS del, DATE_FORMAT('$d->falstr', '$db->_formatoFecha') AS al, DATE_FORMAT(NOW(), '$db->_formatoFechaHora') AS hoy, ";
    $query .= "0.00 AS anterior, 0.00 AS debe, 0.00 AS haber, 0.00 AS actual, ";
    $query .= "0.00 AS anteriorstr, 0.00 AS debestr, 0.00 AS haberstr, 0.00 AS actualstr ";
    $query .= "FROM empresa ";
    $query .= "WHERE id = $d->idempresa";
    $empresa = $db->getQuery($query)[0];

    $conta = new contabilidad($d->fdelstr, $d->falstr, $d->idempresa, (int)$d->vercierre);
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
    $query .= "ORDER BY j.codigo";
    $cuentas = $db->getQuery($query);
    $cntCuentas = count($cuentas);

    $query = 'SELECT TRIM(w.codigo) AS codigo, SUM(debe) AS debe, FORMAT(SUM(debe), 2) AS debestr, SUM(haber) AS haber, FORMAT(SUM(haber), 2) AS haberstr ';
    $query .= "FROM ($queryRawData) w ";
    $query .= 'WHERE w.codigo IS NOT NULL ';
    $query .= 'GROUP BY w.codigo';
    $sumasActual = $db->getQuery($query);

    $query = "SELECT TRIM(w.codigo) AS codigo, (SUM(w.debe) - SUM(w.haber)) AS anterior, FORMAT(SUM(w.debe) - SUM(w.haber), 2) AS anteriorstr ";
    $query .= "FROM ($queryRawDataAnterior) w ";
    $query .= 'WHERE w.codigo IS NOT NULL ';
    $query .= 'GROUP BY w.codigo';
    $sumasAnterior = $db->getQuery($query);

    for ($i = 0; $i < $cntCuentas; $i++) {
        $cuenta = $cuentas[$i];
        if ((int)$cuenta->tipocuenta === 1) {            
            $sumas = getSumaCuentaTotales($sumasActual, $cuenta->codigo);
            if ($sumas) {
                $cuenta->debe = $sumas->debe;
                $cuenta->debestr = number_format($sumas->debe, 2);
                $cuenta->haber = $sumas->haber;
                $cuenta->haberstr = number_format($sumas->haber, 2);
            }
            
            $anterior = getSumaCuentaTotales($sumasAnterior, $cuenta->codigo, false);
            if ($anterior) {
                $cuenta->anterior = $anterior->anterior;
                $cuenta->anteriorstr = number_format($anterior->anterior, 2);
                $cuenta->actual = (float)$anterior->anterior + (float)$sumas->debe - (float)$sumas->haber;
                $cuenta->actualstr = number_format($cuenta->actual, 2);
            }
        }

        if (((float)$cuenta->anterior === 0.00 && (float)$cuenta->debe === 0.00 && (float)$cuenta->haber === 0.00 && (float)$cuenta->actual === 0.00) || strlen(trim($cuenta->codigo)) > (int)$d->nivel) {
            $cuenta->mostrar = false;
        }

        if ($cuenta->mostrar && (int)$cuenta->tipocuenta === 0) {
            $empresa->anterior += (float)$cuenta->anterior;
            $empresa->debe += (float)$cuenta->debe;
            $empresa->haber += (float)$cuenta->haber;
            $empresa->actual += (float)$cuenta->actual;
        }
    }

    $empresa->anteriorstr = number_format($empresa->anterior, 2);
    $empresa->debestr = number_format($empresa->debe, 2);
    $empresa->haberstr = number_format($empresa->haber, 2);
    $empresa->actualstr = number_format($empresa->actual, 2);

    print json_encode(['parametros' => $d, 'empresa' => $empresa, 'datos' => $cuentas, 'rawant' => $queryRawDataAnterior, 'raw' => $queryRawData]);
});
$app->run();