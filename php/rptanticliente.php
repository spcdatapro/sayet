<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptanticli', function(){
    //echo file_get_contents('php://input');

    $d = json_decode(file_get_contents('php://input'));

    //$d = $d->data;

    try{
        $db = new dbcpm();

        $sqlfields = '';
        $sqlgrp = '';
        $sqlord = '';
        $sqlwhr = '';
        $sqlemp = "";

        $qrmoneda = "select distinct a.idmoneda, b.nommoneda,b.simbolo from sayet.factura a inner join sayet.moneda b on a.idmoneda=b.id";

        //echo $qrmoneda;

        $mnd = $db->getQuery($qrmoneda);
        $idarraymnd = 0;
        $detmon = array();

        foreach($mnd as $dmon) {

            $idarraymnd++;
            $msuma30 = 0.00;
            $msuma60 = 0.00;
            $msuma90 = 0.00;
			$msumamas = 0.00;
            $msumatotal = 0.00;

            if (!empty($d->clistr)) {
                $sqlwhr = "where a.id = " . $d->clistr;
            }

            if($d->idempresa != 0){
                $sqlemp = " and c.idempresa=".$d->idempresa;
            }

            //if($d->detalle == 1){
            $sqlfields = 'b.venta,b.factura,b.serie,b.fecha,';
            $sqlgrp = ',b.venta';
            $sqlord = ',b.fecha,b.serie,b.factura';
            //}

            // (a.monto-ifnull(sum(b.monto),0))

            $querydet1 = "SELECT a.nombre," . $sqlfields . "
                        round(sum(if(b.dias < 31, b.monto-b.retisr-b.retiva,0)),2) as a30,
                        round(sum(if(b.dias between 31 and 60, b.monto-b.retisr-b.retiva,0)),2) as a60,
                        round(sum(if(b.dias between 61 and 90, b.monto-b.retisr-b.retiva,0)),2) as a90,
                        round(sum(if(b.dias > 90, b.monto-b.retisr-b.retiva,0)),2) as amas,
                        round(sum(ifnull(b.monto-b.retisr-b.retiva,0)),2) as total,b.empresa,b.idempresa, b.contrato, b.proyecto, b.nomproyecto
                    from sayet.cliente a
                    inner join (

                        select a.orden,a.cliente,a.venta,a.fecha,a.factura,a.serie,
                            a.concepto,if(isnull(c.idpago) and a.pagada=1,0000000000.00,(a.monto-(ifnull(sum(b.monto),0)))) as monto,a.codigo,a.tc_cambio,a.fecpago,a.dias,a.empresa,a.idempresa,a.contrato,a.proyecto,a.nomproyecto,a.retisr,a.retiva
                        from (
                            SELECT 1 as orden,c.idcliente as cliente,c.id as venta,c.fecha,c.numero as factura,c.serie,c.conceptomayor as concepto,
                                round(c.subtotal,2) as monto,e.simbolo as codigo,c.tipocambio as tc_cambio,
                                if(c.fechapago is not null, c.fechapago,c.fecha) as fecpago,datediff('" . $d->falstr . "',if(c.fechapago is not null, c.fechapago,c.fecha)) as dias,
                                c.retisr, a.id as contrato, b.id as proyecto, b.nomproyecto, d.nomempresa as empresa,c.idempresa,c.retiva,c.pagada,round(c.total,2) as total
                            from sayet.factura c
                                inner join sayet.empresa d on c.idempresa=d.id
                                inner join sayet.moneda e on c.idmoneda=e.id
                                left join sayet.contrato a on c.idcontrato=a.id
                                left join sayet.proyecto b on b.id=a.idproyecto
                                where c.anulada=0
                                    and c.fecha<='" . $d->falstr . "'
                                    and c.idmoneda = " . $dmon->idmoneda . $sqlemp ;

            $querydet2 = " order by c.fecha
                        ) as a
                        left join(
                            select orden,cliente,venta,fecha,documento,tipo,monto,codigo,tc_cambio,idpago from (

                                SELECT 2 as orden,a.idcliente as cliente,a.id as venta,c.fecha,d.numero as documento,'R' as tipo, (b.monto) as monto,
                                    'Q' as codigo,a.tipocambio as tc_cambio, b.id as idpago
                                from sayet.factura a
                                    inner join sayet.detcobroventa b on a.id=b.idfactura
                                    inner join sayet.recibocli c on b.idrecibocli=c.id
                                    left join sayet.tranban d on c.idtranban=d.id
                                where c.anulado=0
                                    and c.fecha<='" . $d->falstr . "'
                                    and a.idmoneda = " . $dmon->idmoneda ;

            $querydet3 = ") as b
                        ) as b on a.venta=b.venta
                        left join(
                            select orden,cliente,venta,fecha,documento,tipo,monto,codigo,tc_cambio,if(idfox is null,idpago,null) as idpago, if(idfox is not null, idpago,null) as idpagohist from (

                                SELECT 2 as orden,a.idcliente as cliente,a.id as venta,c.fecha,d.numero as documento,'R' as tipo, (b.monto) as monto,
                                    'Q' as codigo,a.tipocambio as tc_cambio, b.id as idpago, b.idfox
                                from sayet.factura a
                                    inner join sayet.detcobroventa b on a.id=b.idfactura
                                    inner join sayet.recibocli c on b.idrecibocli=c.id
                                    left join sayet.tranban d on c.idtranban=d.id
                                where c.anulado=0
                                    and a.idmoneda = " . $dmon->idmoneda .") as c
                        ) as c on a.venta=c.venta
                        group by a.venta
                        having monto <> 0
                        order by a.fecha,a.serie,a.factura
                    ) as b on a.id=b.cliente " . $sqlwhr . "
                    group by a.id" . $sqlgrp . " order by a.nombre " . $sqlord;


            $query = "Select distinct idempresa, empresa from (".$querydet1.$querydet2.$querydet3.") as a order by empresa;";

            //echo $query;

            $empcli = $db->getQuery($query);

            $detemp = array();

            $idemparray = 0;

            foreach ($empcli as $ecli){

                $idemparray++;
                $queryemp1 = " and c.idempresa = ".$ecli->idempresa." ";
                $queryemp2 = " and a.idempresa = ".$ecli->idempresa." ";

                $query = "Select distinct proyecto, nomproyecto from (".$querydet1.$queryemp1.$querydet2.$queryemp2.$querydet3.") as a where not isnull(a.proyecto) order by nomproyecto;";

                //echo $query;

                $prycli = $db->getQuery($query);

                $detpry = array();

                $idpryarray = 0;

                $esumsaldo = 0.00;
                $esuma30 = 0.00;
                $esuma60 = 0.00;
                $esuma90 = 0.00;
				$esumamas = 0.00;
                $esumatotal = 0.00;

                foreach ($prycli as $pyc) {

                    $idpryarray++;

                    $queryproy = " and b.id = ".$pyc->proyecto;

                    $query = $querydet1.$queryproy.$queryemp1.$querydet2.$queryemp2.$querydet3;

                    //echo $query;

                    $ancl = $db->getQuery($query);

                    $cnt = count($ancl);
                    $detrepo = array();
                    $det = array();
                    $ultnom = '';
                    $idarray = 0;
                    $suma30 = 0.00;
                    $suma60 = 0.00;
                    $suma90 = 0.00;
					$sumamas = 0.00;
                    $sumatotal = 0.00;

                    foreach ($ancl as $hac) {
						
						if(round($hac->total,2) > 0){
                        
							if ($hac->nombre != $ultnom) {
								$idarray++;
								$ultnom = $hac->nombre;

								/*$detrepo[$idarray] = [
									'nombre' => $hac->nombre,
									'vigente' => 0.00,
									'a15' => 0.00,
									'a30' => 0.00,
									'a60' => 0.00,
									'a90' => 0.00,
									'total' => 0.00
								];*/

								$det = array();

								$suma30 = 0.00;
								$suma60 = 0.00;
								$suma90 = 0.00;
								$sumamas = 0.00;
								$sumatotal = 0.00;
							}

							$suma30 += $hac->a30;
							$suma60 += $hac->a60;
							$suma90 += $hac->a90;
							$sumamas += $hac->amas;
							$sumatotal += $hac->total;

							$esuma30 += $hac->a30;
							$esuma60 += $hac->a60;
							$esuma90 += $hac->a90;
							$esumamas += $hac->amas;
							$esumatotal += $hac->total;

							$msuma30 += $hac->a30;
							$msuma60 += $hac->a60;
							$msuma90 += $hac->a90;
							$msumamas += $hac->amas;
							$msumatotal += $hac->total;
							
							if((round($hac->a30,2) + round($hac->a60,2) + round($hac->a90,2) + round($hac->amas,2)) > 0){
								array_push($det,
									array(
										'factura' => $hac->factura,
										'serie' => $hac->serie,
										'fecha' => $hac->fecha,
										'a30' => round($hac->a30,2),
										'a60' => round($hac->a60,2),
										'a90' => round($hac->a90,2),
										'amas' => round($hac->amas,2)
									)
								);
							}

							if ($idarray > 0) {
								$detrepo[$idarray] = [
									'nombre' => $hac->nombre,
									'a30' => round($suma30,2),
									'a60' => round($suma60,2),
									'a90' => round($suma90,2),
									'amas' => round($sumamas,2),
									'total' => round($sumatotal,2),
									'dac' => $det
								];
							}
						}
                    }
                    if ($idpryarray > 0) {
                        array_push($detpry,
                            array(
                                'idproyecto' => $pyc->proyecto,
                                'nomproyecto' => $pyc->nomproyecto,
                                'dproy' => $detrepo
                            )
                        );
                    }

                }

                if ($idemparray > 0) {
                    array_push($detemp,
                        array(
                            'idempresa' => $ecli->idempresa,
                            'empresa' => $ecli->empresa,
                            'saldo' => round($esumsaldo, 2),
                            'a30' => round($esuma30, 2),
                            'a60' => round($esuma60, 2),
                            'a90' => round($esuma90, 2),
							'amas' => round($esumamas, 2),
                            'total' => round($esumatotal, 2),
                            'demp' => $detpry
                        )
                    );
                }
            }

            if ($idarraymnd > 0) {
                $detmon[$idarraymnd] = [
                    'idmoneda' => $dmon->idmoneda,
                    'moneda' => $dmon->nommoneda,
                    'simbolo' => $dmon->simbolo,
                    'a30' => round($msuma30, 2),
                    'a60' => round($msuma60, 2),
                    'a90' => round($msuma90, 2),
					'amas' => round($msumamas, 2),
                    'total' => round($msumatotal, 2),
                    'dmnd' => $detemp
                ];
            }
        }

        $strjson = array();
        foreach ($detmon as $rdet) {
            array_push($strjson, $rdet);
        }
        //$strjson = array();
        //foreach($detrepo as $rdet){
        //    array_push($strjson,$rdet);
            //$strjson .= json_encode($rdet);
        //}

        print json_encode($strjson);

        //print '['.json_encode($detrepo[]).']';
        //print $detrepo;

        //}else{
        //    print $db->doSelectASJson($query);
        //}

    }catch(Exception $e){
        $error = "Mensaje: ".$e->getMessage()." -- Linea: ".$e->getLine()." -- Objeto: ".json_encode($d);
        $query = "SELECT '".$error."' AS nombre, 0 AS vigente, 0 AS a15, 0 AS a30, 0 AS a60, 0 AS a90, 0 AS total";
        print $db->doSelectASJson($query);
    }
});

$app->run();
