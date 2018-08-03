<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptecuentacli', function(){

	//echo "entro a la app";

    $d = json_decode(file_get_contents('php://input'));

    //$d = $d->data;

	//var_dump($d);
	
    try{
		//echo 'entro al try';
		
        $db = new dbcpm();

		if ($d->detalle == 1) {
			$sqlh = "";
		}else {
			$sqlh = "having saldo<>0";
		}
        $sqlwhr = "";
		$sqlemp = "  and c.idempresa>0 ";
		
        if(!empty($d->clistr)){
            $sqlwhr = " where a.id = ".$d->clistr;
        }
		//$sqlemp = "";
		if(intval($d->idempresa) > 0){
			$sqlemp = " and c.idempresa=".$d->idempresa." ";
		}


        /*if($d->detalle == 1){
            $sqlh = "";
        }*/

        $qrmoneda = "select distinct a.idmoneda, b.nommoneda,b.simbolo from sayet.compra a inner join sayet.moneda b on a.idmoneda=b.id";

        $mnd = $db->getQuery($qrmoneda);
        $idarraymnd = 0;
        $detmon = array();

        foreach($mnd as $dmon) {
            $idarraymnd++;
            $msumsaldo = 0.00;

            $querydet1 = "SELECT a.nombre,b.venta,b.factura,b.serie,b.fecha,
                    round(b.monto,2) as saldo,round(b.totalfac,2) as totalfac, round(b.retisr,2) as retisr, substr(b.concepto,1,31) as concepto, b.contrato, b.proyecto, b.nomproyecto, round(b.apagar,2) as apagar,b.empresa,b.idempresa,round(b.retiva,2) as retiva
                from sayet.cliente a
                inner join (

                    select a.orden,a.cliente,a.venta,a.fecha,a.factura,a.serie,
                        a.concepto,if(isnull(c.idpago) and a.pagada=1,0000000000.00,(a.total-(ifnull(sum(b.monto),0)))) as monto,a.codigo,a.tc_cambio,a.fecpago,a.dias,a.monto as totalfac,a.retisr,a.contrato,a.proyecto,a.nomproyecto,
						(a.total) as apagar,a.empresa,a.idempresa,a.retiva,a.pagada,b.idpago,c.idpago as allpago
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
                            and c.idmoneda = " . $dmon->idmoneda ;
            
			$querydet2 = " order by c.fecha
                    ) as a
                    left join(
                        select orden,cliente,venta,fecha,documento,tipo,monto,codigo,tc_cambio,idpago from (

                            SELECT 2 as orden,a.idcliente as cliente,a.id as venta,c.fecha,d.numero as documento,'R' as tipo, (b.monto) as monto,
                                'Q' as codigo,a.tipocambio as tc_cambio, b.id as idpago,b.idfox
                            from sayet.factura a
                                inner join sayet.detcobroventa b on a.id=b.idfactura
                                inner join sayet.recibocli c on b.idrecibocli=c.id
                                left join sayet.tranban d on c.idtranban=d.id
                            where c.anulado=0 
                                and c.fecha<='" . $d->falstr . "'
                                and a.idmoneda = " . $dmon->idmoneda;

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
                                and a.idmoneda = " . $dmon->idmoneda ."
						) as b
						group by venta
					) as c on a.venta=c.venta
                    group by a.venta order by a.venta
                ) as b on a.id=b.cliente " . $sqlwhr . "
                group by a.id,b.venta " . $sqlh . " order by a.nombre,b.fecha,b.serie,b.factura";

			
			$query = "Select distinct idempresa, empresa from (".$querydet1.$sqlemp.$querydet2.$querydet3.") as a order by empresa;";
			
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
				
				foreach ($prycli as $pyc) {
					
					$idpryarray++;
				
					$queryproy = " and b.id = ".$pyc->proyecto;
					
					$query = $querydet1.$queryproy.$queryemp1.$querydet2.$queryemp2.$querydet3;
					
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
						$esumsaldo += $hac->saldo;
						$msumsaldo += $hac->saldo;

						if ($d->detalle == 1) {
							$detfac = array();

							$qdetpago = "SELECT a.idcliente as cliente,c.id as venta,c.fecha,if(d.numero is null,c.id,d.numero) as documento,if(d.tipotrans is null,'P',d.tipotrans) as tipotrans, round((b.monto*if(a.idmoneda=1,1,a.tipocambio)),2) as monto,concat(c.serie,c.numero) as recibo
									from sayet.factura a
										inner join sayet.detcobroventa b on a.id=b.idfactura
										inner join sayet.recibocli c on b.idrecibocli=c.id
										left join sayet.tranban d on c.idtranban=d.id
									where c.anulado=0 
										and c.fecha<='" . $d->falstr . "' and a.id=" . $hac->venta . " and a.idmoneda = " . $dmon->idmoneda . "";

							$qdfac = $db->getQuery($qdetpago);
							//echo $qdetpago;
							foreach ($qdfac as $row) {

								//var_dump($row);

								array_push($detfac,
									array(
										'monto' => $row->monto,
										'venta' => $row->venta,
										'tipotrans' => $row->tipotrans,
										'documento' => $row->documento,
										'fecha' => $row->fecha,
										'recibo' => $row->recibo
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
								'dfac' => $detfac,
								'isr' => $hac->retisr,
								'concepto' => $hac->concepto,
								'apagar' => $hac->apagar,
								'empresa' => $hac->empresa,
								'retiva' => $hac->retiva
							)
						);
						//
						if ($idarray > 0) {
							$detrepo[$idarray] = [
								'nombre' => $hac->nombre,
								'tsaldo' => $sumasaldo,
								'dec' => $det
							];
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
						/*$detpry[] = [
							'idproyecto' => $pyc->proyecto,
							'nomproyecto' => $pyc->nomproyecto,
							'dproy' => $detrepo
						];*/
					}
					
				}
				
				if ($idemparray > 0) {
					array_push($detemp,
						array(
							'idempresa' => $ecli->idempresa,
						'empresa' => $ecli->empresa,
						'saldo' => round($esumsaldo, 2),
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
                    'saldo' => round($msumsaldo, 2),
                    'dmon' => $detemp
                ];
            }
        }

        $strjson = array();
        foreach($detmon as $rdet){
            array_push($strjson,$rdet);
            //$strjson .= json_encode($rdet);
        }

        print json_encode($strjson);
        //print '['.json_encode($detrepo[]).']';
        //print $detrepo;

    }catch(Exception $e){
        $error = "Mensaje: ".$e->getMessage()." -- Linea: ".$e->getLine()." -- Objeto: ".json_encode($d);
        $query = "SELECT '".$error."' AS nombre, 0 AS vigente, 0 AS a15, 0 AS a30, 0 AS a60, 0 AS a90, 0 AS total";
        print $db->doSelectASJson($query);
    }
});

$app->run();