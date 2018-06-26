<?php
set_time_limit(0);
ini_set('memory_limit', '2048M');
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
$db = new dbcpm();

$app->post('/getfacturas', function() use($app, $db){
    $d = json_decode(file_get_contents('php://input'));

    $app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    $app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $app->response->headers->set('Access-Control-Allow-Origin', '*');

    $respuesta = $db->CallJSReportAPI('GET', $d->url);
    print $respuesta;
});

function parseFecha($fecha){
    $partes = explode("-", $fecha);
    return $partes[2].'-'.$partes[1].'-'.$partes[0];
}

function idc($db, $origen, $idorigen, $idcuenta, $debe, $haber, $conceptomayor, $anulado) {
    $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado) VALUES(";
    $query.= "$origen, $idorigen, $idcuenta, $debe, $haber, '$conceptomayor', 1, $anulado";
    $query.= ")";
    $db->doQuery($query);
}

function insertaPartidaDirecta($d, $insertadas, $montoClientesVarios, $montoIVA, $montoParqueo){
    $db = new dbcpm();

    $query = "SELECT GROUP_CONCAT(concepto SEPARATOR ', ') FROM (";
    $query.= "SELECT CONCAT('Serie ', serie, ' de No. ', MIN(numero), ' a No. ', MAX(numero)) AS concepto FROM factura ";
    $query.= "WHERE id IN($insertadas) GROUP BY serie) a";
    $concepto = $db->getOneField($query);

    $query = "INSERT INTO directa (idempresa, fecha, concepto, tipocierre) VALUES($d->idempresa, NOW(), 'Facturas de parqueo: $concepto', 0)";
    $db->doQuery($query);
    $lastid = $db->getLastId();

    if((int)$lastid > 0){
        $codctacliente = '1120199';
        $query = "SELECT id FROM cuentac WHERE TRIM(codigo) = '$codctacliente' AND idempresa = $d->idempresa";
        $ctacliente = (int)$db->getOneField($query);
        if($ctacliente > 0){
            idc($db, 4, $lastid, $ctacliente, $montoClientesVarios, 0.00, "Facturas de parqueo: $concepto", 0);
        }

        $query = "SELECT b.id FROM tiposervicioventa a INNER JOIN cuentac b ON TRIM(b.codigo) = TRIM(a.cuentac) WHERE a.id = 7 AND b.idempresa = $d->idempresa";
        $ctadetalle = (int)$db->getOneField($query);
        if($ctadetalle > 0){
            idc($db, 4, $lastid, $ctadetalle, 0.00, $montoParqueo, "Facturas de parqueo: $concepto", 0);
        }

        $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $d->idempresa AND idtipoconfig = 1";
        $ctaivadebito = (int)$db->getOneField($query);
        if($ctaivadebito > 0){
            idc($db, 4, $lastid, $ctaivadebito, 0.00, $montoIVA, "Facturas de parqueo: $concepto", 0);
        }
    }
}

$app->post('/insertafacts', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $n2l = new NumberToLetterConverter();

    $conteo = 0;
    $montoClientesVarios = 0.00;
    $montoIVA = 0.00;
    $montoParqueo = 0.00;
    $insertadas = '';
    foreach($d->facturas as $p){

        $query = "SELECT COUNT(id) FROM factura WHERE idempresa = $d->idempresa AND idproyecto = $d->idproyecto AND TRIM(serie) = '$p->serie' AND TRIM(numero) = '$p->numero' AND esparqueo = 1";
        $noexiste = (int)$db->getOneField($query) <= 0;

        if($noexiste){
            $fechaFact = parseFecha($p->fecha);
            $totapagar = round((float)$p->total, 2);
            $totsiniva = round($totapagar / 1.12, 2);
            $iva = $totapagar - $totsiniva;
            $montodol = round($totsiniva / (float)$d->tc, 2);
            $nit = $p->nit === 'C-F' ? 'C/F' : $p->nit;
            $query = "INSERT INTO factura(";
            $query.= "idempresa, idtipofactura, idcontrato, idcliente, serie, numero, ";
            $query.= "fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, iva, ";
            $query.= "total, noafecto, subtotal, totalletras, idmoneda, tipocambio, ";
            $query.= "retisr, retiva, totdescuento, nit, nombre, direccion, montocargoiva, montocargoflat, ";
            $query.= "idproyecto, esparqueo";
            $query.= ") VALUES (";
            $query.= "$d->idempresa, 8, 0, 0, '$p->serie', '$p->numero', ";
            $query.= "'$fechaFact', MONTH('$fechaFact'), '$fechaFact', 2, 'PARQUEO', $iva, ";
            $query.= "$totapagar, 0.00, $totapagar, '".$n2l->to_word($totapagar, 'GTQ')."', 1, $d->tc, ";
            $query.= "0.00, 0.00, 0.00, '$nit', '$p->nombre', NULL, $totapagar, $montodol, ";
            $query.= "$d->idproyecto, 1";
            $query.= ")";
            //print $query;
            $db->doQuery($query);
            $lastid = $db->getLastId();

            if((int)$lastid > 0){
                $conteo++;
                $montoClientesVarios += $totapagar;
                $montoIVA += $iva;
                $montoParqueo += $totsiniva;
                if($insertadas != ''){ $insertadas .= ','; }
                $insertadas.= $lastid;
                $query = "INSERT INTO detfact(idfactura, cantidad, descripcion, preciounitario, preciotot, idtiposervicio, mes, anio, descuento, montoconiva, montoflatconiva) VALUES(";
                $query.= "$lastid, 1, 'PARQUEO', $totapagar, $totapagar, 7, MONTH('$fechaFact'), YEAR('$fechaFact'), 0.00, $totapagar, $totapagar";
                $query.= ")";
                //print $query;
                $db->doQuery($query);
                /*
                $url = 'http://localhost/sayet/php/genpartidasventa.php/genpost';
                $data = ['ids' => $lastid, 'idcontrato' => 0];
                $db->CallJSReportAPI('POST', $url, json_encode($data));
                */
            }
        }
    }

    if($montoClientesVarios > 0){
        insertaPartidaDirecta($d, $insertadas, $montoClientesVarios, $montoIVA, $montoParqueo);
    }

    print json_encode(['Recibidas' => count($d->facturas), 'Insertadas' => $conteo]);
});

$app->run();