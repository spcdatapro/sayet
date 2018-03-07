<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptsaldoprov', function(){
    $d = json_decode(file_get_contents('php://input'));

    //$d = $d->data;

    try{
        $db = new dbcpm();

        $qrmoneda = "select distinct a.idmoneda, b.nommoneda,b.simbolo from sayet.compra a inner join sayet.moneda b on a.idmoneda=b.id";

        $mnd = $db->getQuery($qrmoneda);
        $idarraymnd = 0;
        $detmon = array();

        foreach($mnd as $dmon) {
            $idarraymnd++;
            $sumanterior = 0.00;
            $sumcargos = 0.00;
            $sumabonos = 0.00;
            $sumsaldo = 0.00;

            $query = "select c.nit,c.nombre,round(a.saldo,2) as anterior, round(b.cargos,2) as cargos,round(b.abonos,2) as abonos,round((a.saldo+b.cargos)-b.abonos,2) as saldo,c.id as proveedor from
            sayet.proveedor c
            left join (
                SELECT a.id as proveedor
                    ,ifnull(b.cargos,0) as cargos
                    ,ifnull(d.abonos,0) as abonos
                    ,ifnull(b.cargos,0) -ifnull(d.abonos,0) as saldo
                    from sayet.proveedor a
                    left join
                    (
                        select idproveedor,sum(totfact-isr) as cargos from sayet.compra where fechafactura < '" . $d->fdelstr . "' and idmoneda=" . $dmon->idmoneda . " group by idproveedor
                    ) as b on a.id = b.idproveedor
                    left join
                    (
                        select idproveedor,sum(abonos) as abonos from (
                            select c.idproveedor as idproveedor,round(b.monto,2) as abonos
                            from sayet.tranban a
                            inner join sayet.detpagocompra b on a.id = b.idtranban and esrecprov=0
                            inner join sayet.compra c on b.idcompra=c.id and c.idmoneda= " . $dmon->idmoneda . "
                            where a.fecha < '" . $d->fdelstr . "'
                            union all
                            select d.idproveedor as idproveedor,round(c.monto,2) as abonos
                            from sayet.tranban a
                            inner join  sayet.reciboprov b on a.id=b.idtranban
                            inner join sayet.detpagocompra c on b.id = c.idtranban and esrecprov=1
                            inner join sayet.compra d on c.idcompra=d.id  and d.idmoneda= " . $dmon->idmoneda . "
                            where a.fecha < '" . $d->fdelstr . "'
                        ) as a group by idproveedor
                    ) as d on a.id = d.idproveedor
            ) as a on a.proveedor = c.id

            left join (
                SELECT a.id as proveedor
                    ,ifnull(b.cargos,0) as cargos
                    ,ifnull(d.abonos,0) as abonos
                    ,ifnull(b.cargos,0) -ifnull(d.abonos,0) as saldo
                    from sayet.proveedor a
                    left join
                    (
                        select idproveedor,sum(totfact-isr) as cargos from sayet.compra where fechafactura between '" . $d->fdelstr . "' and '" . $d->falstr . "'  and idmoneda= " . $dmon->idmoneda . " group by idproveedor
                    )  as b on a.id = b.idproveedor
                    left join
                    (
                        select idproveedor,sum(abonos) as abonos from (
                            select c.idproveedor as idproveedor,round(b.monto,2) as abonos
                            from sayet.tranban a
                            inner join sayet.detpagocompra b on a.id = b.idtranban and esrecprov=0
                            inner join sayet.compra c on b.idcompra=c.id  and c.idmoneda= " . $dmon->idmoneda . "
                            where a.fecha between '" . $d->fdelstr . "' and '" . $d->falstr . "'
                            union all
                            select d.idproveedor as idproveedor,round(c.monto,2) as abonos
                            from sayet.tranban a
                            inner join  sayet.reciboprov b on a.id=b.idtranban
                            inner join sayet.detpagocompra c on b.id = c.idtranban and esrecprov=1
                            inner join sayet.compra d on c.idcompra=d.id and d.idmoneda= " . $dmon->idmoneda . "
                            where a.fecha between '" . $d->fdelstr . "' and '" . $d->falstr . "'
                        ) as a group by idproveedor
                    )  as d on a.id = d.idproveedor
            ) as b on a.proveedor=b.proveedor
            group by c.id
            having saldo+cargos+abonos<>0
            order by c.nombre";

            $saldomoneda = $db->getQuery($query);

            $det = array();

            foreach($saldomoneda as $sld){
                $sumanterior += $sld->anterior;
                $sumcargos += $sld->cargos;
                $sumabonos += $sld->abonos;
                $sumsaldo += $sld->saldo;

                array_push($det,
                    array(
                        'nit' => $sld->nit,
                        'nombre' => $sld->nombre,
                        'anterior' => $sld->anterior,
                        'cargos' => $sld->cargos,
                        'abonos' => $sld->abonos,
                        'saldo' => $sld->saldo,
                        'proveedor' => $sld->proveedor,
                    )
                );

            }

            if ($idarraymnd > 0) {
                $detmon[$idarraymnd] = [
                    'idmoneda' => $dmon->idmoneda,
                    'moneda' => $dmon->nommoneda,
                    'simbolo' => $dmon->simbolo,
                    'anterior' => round($sumanterior, 2),
                    'cargos' => round($sumcargos, 2),
                    'abonos' => round($sumabonos, 2),
                    'saldo' => round($sumsaldo, 2),
                    'det' => $det
                ];
            }
        }

        $strjson = array();
        foreach ($detmon as $rdet) {
            array_push($strjson, $rdet);
        }

        print json_encode($strjson);

        //echo $query;
        //print $db->doSelectASJson($query);
    }catch(Exception $e){
        $error = "Mensaje: ".$e->getMessage()." -- Linea: ".$e->getLine()." -- Objeto: ".json_encode($d);
        $query = "SELECT '' AS nit, '".$error."' AS nombre, 0 AS anterior, 0 AS cargos, 0 AS abonos, 0 AS saldo, 0 AS proveedor";
        print $db->doSelectASJson($query);
    }
});

$app->run();
