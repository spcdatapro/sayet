<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptsaldocli', function(){
    $d = json_decode(file_get_contents('php://input'));

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

            $query = "select c.nombre,round(a.saldo,2) as anterior, round(b.cargos,2) as cargos,round(b.abonos,2) as abonos,round((a.saldo+b.cargos)-b.abonos,2) as saldo,c.id as cliente from
                    sayet.cliente c
                    left join (
                        SELECT a.id as cliente,
                            ifnull(b.cargos,0) as cargos,
                            ifnull(d.abonos,0) as abonos,
                            ifnull(b.cargos,0) -ifnull(d.abonos,0) as saldo
                            from sayet.cliente a
                            left join
                            (
                                select idcliente,sum(total) as cargos 
								from sayet.factura 
								where anulada=0 and pagada=0 
								and fecha < '".$d->fdelstr."' 
								and idmoneda= " . $dmon->idmoneda . " 
								and idempresa=".$d->idempresa."  
								group by idcliente
                            ) as b on a.id = b.idcliente
                            left join
                            (
                                select a.idcliente,sum(b.monto) as abonos
                                from sayet.recibocli a
                                inner join sayet.detcobroventa b on a.id = b.idrecibocli
                                inner join sayet.factura c on b.idfactura=c.id and c.idmoneda= " . $dmon->idmoneda . " and anulada=0 and pagada=0 
                                where a.fecha < '".$d->fdelstr."' and a.idempresa=".$d->idempresa." group by a.idcliente
                            ) as d on a.id = d.idcliente
                    ) as a on a.cliente = c.id

                    left join (
                        SELECT a.id as cliente,
                            ifnull(b.cargos,0) as cargos,
                            ifnull(d.abonos,0) as abonos,
                            ifnull(b.cargos,0) -ifnull(d.abonos,0) as saldo
                            from sayet.cliente a
                            left join
                            (
                                select idcliente,sum(total) as cargos 
								from sayet.factura 
								where anulada=0 
								and  fecha between '".$d->fdelstr."' and '".$d->falstr."' 
								and idmoneda= " . $dmon->idmoneda . " 
								and idempresa=".$d->idempresa." 
								group by idcliente
                            )  as b on a.id = b.idcliente
                            left join
                            (
                                select a.idcliente,sum(b.monto) as abonos
                                from sayet.recibocli a
                                inner join sayet.detcobroventa b on a.id = b.idrecibocli
                                inner join sayet.factura c on b.idfactura=c.id and c.idmoneda= " . $dmon->idmoneda . " and anulada=0 
                                where a.fecha between '".$d->fdelstr."' and '".$d->falstr."' and a.idempresa=".$d->idempresa." group by a.idcliente
                            )  as d on a.id = d.idcliente
                    ) as b on a.cliente=b.cliente
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
                        'nombre' => $sld->nombre,
                        'anterior' => $sld->anterior,
                        'cargos' => $sld->cargos,
                        'abonos' => $sld->abonos,
                        'saldo' => $sld->saldo,
                        'cliente' => $sld->cliente,
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
        //print $db->doSelectASJson($query);
    }catch(Exception $e){
        $error = "Mensaje: ".$e->getMessage()." -- Linea: ".$e->getLine()." -- Objeto: ".json_encode($d);
        $query = "SELECT '".$error."' AS nombre, 0 AS anterior, 0 AS cargos, 0 AS abonos, 0 AS saldo, 0 AS cliente";
        print $db->doSelectASJson($query);
    }
});

$app->run();
