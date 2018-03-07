<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptecuentaprov', function(){

    $d = json_decode(file_get_contents('php://input'));

    //$d = $d->data;

    try{
        $db = new dbcpm();

        //$sqlh = "having saldo<>0";
		$sqlh = "";
        $sqlwhr = "";
        if(!empty($d->provstr)){
            $sqlwhr = "where a.id = ".$d->provstr;
        }

        if($d->detalle == 1){
            $sqlh = "";
        }

        $qrmoneda = "select distinct a.idmoneda, b.nommoneda,b.simbolo from sayet.compra a inner join sayet.moneda b on a.idmoneda=b.id";

        $mnd = $db->getQuery($qrmoneda);
        $idarraymnd = 0;
        $detmon = array();

        foreach($mnd as $dmon) {
            $idarraymnd++;
            $msumsaldo = 0.00;


            $query = "SELECT a.nit,a.nombre,b.compra,b.factura,b.serie,b.fecha,
                round(b.monto,2) as saldo,round(b.totalfac,2) as totalfac
            from sayet.proveedor a
            inner join (

                select a.orden,a.proveedor,a.compra,a.fecha,a.factura,a.serie,
                    a.concepto,(a.monto-ifnull(sum(b.monto),0)) as monto,a.codigo,a.tc_cambio,a.fecpago,a.dias,a.monto as totalfac
                from (
                    SELECT 1 as orden,c.idproveedor as proveedor,c.id as compra,c.fechafactura as fecha,c.documento as factura,c.serie,c.conceptomayor as concepto,
                        (c.totfact-c.isr) as monto,e.simbolo as codigo,c.tipocambio as tc_cambio,
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
                    select d.idproveedor as proveedor,sum(round(c.monto,2)) as abonos,d.id as compra
                    from sayet.reciboprov b
                    inner join sayet.detpagocompra c on b.id = c.idtranban and esrecprov=1
                    inner join sayet.compra d on c.idcompra=d.id and d.idmoneda = " . $dmon->idmoneda . "
                    where b.fecha <= '" . $d->falstr . "'
                    group by 1,3
                ) as b on a.compra=b.compra
                group by a.compra " . $sqlh . " order by a.compra
            ) as b on a.id=b.proveedor " . $sqlwhr . "
            group by a.id ,b.compra order by a.nombre,b.fecha,b.serie,b.factura";

            //echo $query;

            $ancl = $db->getQuery($query);

            $detrepo = array();
            $det = array();
            $detfac = array();
            $ultnom = '';
            $idarray = 0;
            $sumasaldo = 0.00;

            foreach ($ancl as $hac) {

                if ($hac->nombre != $ultnom) {
                    $idarray++;
                    $ultnom = $hac->nombre;

                    $det = array();

                    $sumasaldo = 0.00;
                }

                $sumasaldo += $hac->saldo;
                $msumsaldo += $hac->saldo;

                if ($d->detalle == 1) {
                    $detfac = array();

                    $qdetpago = "select c.idproveedor as proveedor,round(b.monto,2) as monto,c.id as compra,a.tipotrans,concat(a.numero,' ',d.siglas,' ',e.simbolo) as documento,a.fecha
                    from sayet.tranban a
                    inner join sayet.detpagocompra b on a.id = b.idtranban and esrecprov=0
                    inner join sayet.compra c on b.idcompra=c.id and c.idmoneda = " . $dmon->idmoneda . "
                    left join sayet.banco d on a.idbanco=d.id
                    left join sayet.moneda e on d.idmoneda=e.id
                    where a.fecha <= '" . $d->falstr . "' and c.id=" . $hac->compra . "
                    union all
                    select d.idproveedor as proveedor,round(c.monto,2) as abonos,d.id as compra,'P' as tipotrans,concat(b.id,' ',f.simbolo) as documento,b.fecha
                    from sayet.reciboprov b
                    inner join sayet.detpagocompra c on b.id = c.idtranban and esrecprov=1
                    inner join sayet.compra d on c.idcompra=d.id and d.idmoneda = " . $dmon->idmoneda . "
                    left join sayet.moneda f on d.idmoneda=f.id
                    where b.fecha <= '" . $d->falstr . "' and d.id=" . $hac->compra . "
                    group by 1,3,5";

                    $qdfac = $db->getQuery($qdetpago);
                    //echo $qdetpago;
                    foreach ($qdfac as $row) {

                        //var_dump($row);

                        array_push($detfac,
                            array(
                                'monto' => $row->monto,
                                'compra' => $row->compra,
                                'tipotrans' => $row->tipotrans,
                                'documento' => $row->documento,
                                'fecha' => $row->fecha
                            )
                        );
                    }
                    //$detfac = $db->doSelectASJson($qdetpago);
                }

                array_push($det,
                    array(
                        'factura' => $hac->factura,
                        'serie' => $hac->serie,
                        'fecha' => $hac->fecha,
                        'saldo' => $hac->saldo,
                        'totalfac' => $hac->totalfac,
                        'dfac' => $detfac
                    )
                );
                //
                if ($idarray > 0) {
                    $detrepo[$idarray] = [
                        'nit' => $hac->nit,
                        'nombre' => $hac->nombre,
                        'tsaldo' => $sumasaldo,
                        'dec' => $det
                    ];
                }
            }

            if ($idarraymnd > 0) {
                $detmon[$idarraymnd] = [
                    'idmoneda' => $dmon->idmoneda,
                    'moneda' => $dmon->nommoneda,
                    'simbolo' => $dmon->simbolo,
                    'saldo' => round($msumsaldo, 2),
                    'dmon' => $detrepo
                ];
            }
        }

        $strjson = array();
        foreach($detmon as $rdet){
            array_push($strjson,$rdet);
            //$strjson .= json_encode($rdet);
        }

        /*
        $strjson = array();
        foreach($detrepo as $rdet){
            array_push($strjson,$rdet);
            //$strjson .= json_encode($rdet);
        }
        */
        print json_encode($strjson);

        //print '['.json_encode($detrepo[]).']';
        //print $detrepo;

    }catch(Exception $e){
        $error = "Mensaje: ".$e->getMessage()." -- Linea: ".$e->getLine()." -- Objeto: ".json_encode($d);
        $query = "SELECT '' AS nit, '".$error."' AS nombre, 0 AS vigente, 0 AS a15, 0 AS a30, 0 AS a60, 0 AS a90, 0 AS total";
        print $db->doSelectASJson($query);
    }
});

$app->run();