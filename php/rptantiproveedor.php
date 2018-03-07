<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptantiprov', function(){

    $d = json_decode(file_get_contents('php://input'));

    //$d = $d->data;

    //var_dump($d);

    try{
        $db = new dbcpm();

        $sqlfields = '';
        $sqlgrp = '';
        $sqlord = '';
        $sqlwhr = '';

        $qrmoneda = "select distinct a.idmoneda, b.nommoneda,b.simbolo from sayet.compra a inner join sayet.moneda b on a.idmoneda=b.id";

        $mnd = $db->getQuery($qrmoneda);
        $idarraymnd = 0;
        $detmon = array();

        foreach($mnd as $dmon) {

            $idarraymnd++;
            $msumvigente = 0.00;
            $msuma15 = 0.00;
            $msuma30 = 0.00;
            $msuma60 = 0.00;
            $msuma90 = 0.00;
            $msumatotal = 0.00;

            $sqlfields = 'b.compra,b.factura,b.serie,b.fecha,';
            $sqlgrp = ',b.compra';
            $sqlord = ',b.fecha,b.serie,b.factura';

            if (!empty($d->provstr)) {
                $sqlwhr = "where a.id = " . $d->provstr;
            }

            $query = "SELECT a.nit,a.nombre," . $sqlfields . "
                round(sum(if(b.dias < 15, b.monto,0)),2) as vigente,
                round(sum(if(b.dias between 15 and 29, b.monto,0)),2) as a15,
                round(sum(if(b.dias between 30 and 59, b.monto,0)),2) as a30,
                round(sum(if(b.dias between 60 and 89, b.monto,0)),2) as a60,
                round(sum(if(b.dias >= 90, b.monto,0)),2) as a90,
                round(sum(ifnull(b.monto,0)),2) as total
            from sayet.proveedor a
            inner join (

                select a.orden,a.proveedor,a.compra,a.fecha,a.factura,a.serie,
                    a.concepto,(a.monto-ifnull(sum(b.monto),0)) as monto,a.codigo,a.tc_cambio,a.fecpago,a.dias
                from (
                    SELECT 1 as orden,c.idproveedor as proveedor,c.id as compra,c.fechafactura as fecha,c.documento as factura,c.serie,c.conceptomayor as concepto,
                        ((c.totfact-c.isr)) as monto,e.simbolo as codigo,c.tipocambio as tc_cambio,
                        if(c.fechapago is not null, c.fechapago,c.fechafactura) as fecpago,datediff('" . $d->falstr . "',if(c.fechapago is not null, c.fechapago,c.fechafactura)) as dias
                    from sayet.compra c
                        inner join sayet.moneda e on c.idmoneda=e.id
                        where c.fechafactura<='" . $d->falstr . "'
                        and c.idmoneda = " . $dmon->idmoneda . "
                    order by c.fechafactura
                ) as a
                left join(
                    select c.idproveedor as proveedor,sum(round(b.monto,2)) as monto,c.id as compra
                    from sayet.tranban a
                    inner join sayet.detpagocompra b on a.id = b.idtranban and esrecprov=0
                    inner join sayet.compra c on b.idcompra=c.id and c.idmoneda = " . $dmon->idmoneda . "
                    where a.fecha <= '" . $d->falstr . "'
                    group by 1,3
                    union all
                    select d.idproveedor as proveedor,sum(round(c.monto,2)) as monto,d.id as compra
                    from sayet.tranban a
                    inner join  sayet.reciboprov b on a.id=b.idtranban
                    inner join sayet.detpagocompra c on b.id = c.idtranban and esrecprov=1
                    inner join sayet.compra d on c.idcompra=d.id
                    where a.fecha <= '" . $d->falstr . "'
                    group by 1,3
                ) as b on a.compra=b.compra
                group by a.compra having monto<>0 order by a.compra
            ) as b on a.id=b.proveedor " . $sqlwhr . "
            group by a.id " . $sqlgrp . " order by a.nombre" . $sqlord;

            $ancl = $db->getQuery($query);
            //$cnt = count($ancl);
            $detrepo = array();
            $det = array();
            $ultnom = '';
            $idarray = 0;
            $sumvigente = 0.00;
            $suma15 = 0.00;
            $suma30 = 0.00;
            $suma60 = 0.00;
            $suma90 = 0.00;
            $sumatotal = 0.00;

            foreach ($ancl as $hac) {

                if ($hac->nombre != $ultnom) {
                    $idarray++;
                    $ultnom = $hac->nombre;

                    $det = array();

                    $sumvigente = 0.00;
                    $suma15 = 0.00;
                    $suma30 = 0.00;
                    $suma60 = 0.00;
                    $suma90 = 0.00;
                    $sumatotal = 0.00;
                }

                $sumvigente += $hac->vigente;
                $suma15 += $hac->a15;
                $suma30 += $hac->a30;
                $suma60 += $hac->a60;
                $suma90 += $hac->a90;
                $sumatotal += $hac->total;

                $msumvigente += $hac->vigente;
                $msuma15 += $hac->a15;
                $msuma30 += $hac->a30;
                $msuma60 += $hac->a60;
                $msuma90 += $hac->a90;
                $msumatotal += $hac->total;


                array_push($det,
                    array(
                        'factura' => $hac->factura,
                        'serie' => $hac->serie,
                        'fecha' => $hac->fecha,
                        'vigente' => $hac->vigente,
                        'a15' => $hac->a15,
                        'a30' => $hac->a30,
                        'a60' => $hac->a60,
                        'a90' => $hac->a90
                    )
                );

                if ($idarray > 0) {
                    $detrepo[$idarray] = [
                        'nit' => $hac->nit,
                        'nombre' => $hac->nombre,
                        'vigente' => round($sumvigente, 2),
                        'a15' => round($suma15, 2),
                        'a30' => round($suma30, 2),
                        'a60' => round($suma60, 2),
                        'a90' => round($suma90, 2),
                        'total' => round($sumatotal, 2),
                        'dac' => $det
                    ];
                }
            }

            if ($idarraymnd > 0) {
                $detmon[$idarraymnd] = [
                    'idmoneda' => $dmon->idmoneda,
                    'moneda' => $dmon->nommoneda,
                    'simbolo' => $dmon->simbolo,
                    'vigente' => round($msumvigente, 2),
                    'a15' => round($msuma15, 2),
                    'a30' => round($msuma30, 2),
                    'a60' => round($msuma60, 2),
                    'a90' => round($msuma90, 2),
                    'total' => round($msumatotal, 2),
                    'dmnd' => $detrepo
                ];
            }
        }

        $strjson = array();
        foreach ($detmon as $rdet) {
            array_push($strjson, $rdet);
        }

        print json_encode($strjson);

    }catch(Exception $e){
        $error = "Mensaje: ".$e->getMessage()." -- Linea: ".$e->getLine()." -- Objeto: ".json_encode($d);
        $query = "SELECT '' AS nit, '".$error."' AS nombre, 0 AS vigente, 0 AS a15, 0 AS a30, 0 AS a60, 0 AS a90, 0 AS total";
        print $db->doSelectASJson($query);
    }
});

$app->run();