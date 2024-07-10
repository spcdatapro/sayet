<?php
set_time_limit(0);
ini_set('memory_limit', '1536M');
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->notFound(function () use ($app) {
    print json_encode([]);
});

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
                                if(c.fechapago is not null, c.fechapago,c.fecha) as fecpago,datediff('" . $d->falstr . "', c.fecha) as dias,
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

$app->get('/listaclientes/:qstra+', function($qstra){
    $db = new dbcpm();
    $qstr = $qstra[0];

    $query = "
        SELECT nombre FROM (
            SELECT TRIM(nombre) AS nombre FROM cliente            
            UNION
            SELECT TRIM(nombre) AS nombre FROM factura
        ) a
        WHERE a.nombre IS NOT NULL AND a.nombre LIKE '%$qstr%'
        ORDER BY a.nombre";

    print json_encode(['results' => $db->getQuery($query)]);
});

function queryFacturas($d){
    $cliente = '';
    if(trim($d->cliente) != ''){ $cliente = str_replace(' ', '%', trim($d->cliente)); }

    $qFacts = "SELECT a.idempresa, b.nomempresa AS empresa, b.abreviatura AS abreviaempresa, a.idproyecto, IF(a.idproyecto = 0, TRIM(d.nomproyecto), TRIM(e.nomproyecto)) AS proyecto, a.idcliente, IF(a.idcliente = 0, TRIM(a.nombre), 
        TRIM(f.nombre)) AS cliente,
        a.id AS idfactura, a.serie, a.numero, a.fecha, DATEDIFF('$d->falstr', a.fecha) AS dias,
        ROUND(a.subtotal, 2) AS subtotal,
        ROUND(a.retisr, 2) AS retisr,
        ROUND(a.retiva, 2) AS retiva,
        ROUND(a.total, 2) AS monto,
        IFNULL(g.montopagado, 0.00) AS montopagado,
        ROUND(a.total, 2) - IFNULL(g.montopagado, 0.00) AS saldo,
        b.ordensumario, a.serieadmin, a.numeroadmin,
        IF(a.idcliente = 0, '', TRIM(f.nombrecorto)) AS nombrecorto
        FROM factura a
        INNER JOIN empresa b ON b.id = a.idempresa
        LEFT JOIN contrato c ON c.id = a.idcontrato
        LEFT JOIN proyecto d ON d.id = c.idproyecto
        LEFT JOIN proyecto e ON e.id = a.idproyecto
        LEFT JOIN cliente f ON f.id = a.idcliente
        LEFT JOIN (
            SELECT x.idfactura, SUM(x.montopagado) AS montopagado
            FROM (
                SELECT z.idfactura, SUM(z.monto) AS montopagado
                FROM detcobroventa z
                INNER JOIN recibocli y ON y.id = z.idrecibocli    
                WHERE y.fecha <= '$d->falstr'
                GROUP BY z.idfactura
                UNION    
                SELECT z.idfacturaafecta AS idfactura, SUM(z.total) AS montopagado
                FROM factura z 
                WHERE z.idtipofactura IN (9, 13) AND z.fecha <= '$d->falstr' AND (z.anulada = 0 OR (z.anulada = 1 AND z.fechaanula > '$d->falstr'))
                GROUP BY z.idfacturaafecta
            ) x
            GROUP BY x.idfactura
        ) g ON a.id = g.idfactura
        WHERE a.idtipofactura NOT IN (9, 13) AND a.fecha <= '$d->falstr' AND a.idfox IS NULL AND (a.anulada = 0 OR (a.anulada = 1 AND a.fechaanula > '$d->falstr')) AND ROUND(a.total, 2) - IFNULL(g.montopagado, 0.00) <> 0 AND 
        IF(ISNULL(g.idfactura) AND a.pagada = 1 AND a.forzada = 1, 1 = 0, 1 = 1) ";
    $qFacts.= (int)$d->abreviado === 0 ? '' : "AND DATEDIFF('$d->falstr', a.fecha) > 60 ";
    $qFacts.= (int)$d->vernegativos === 1 ? '' : ('AND (ROUND(a.total, 2) - IFNULL(g.montopagado, 0.00)) '.((int)$d->pagoextra == 0 ? '>= 0 ' : '< 0 '));
    $qFacts.= (int)$d->idempresa > 0 ? "AND a.idempresa IN($d->idempresa) " : '';
    $qFacts.= (int)$d->idproyecto > 0 ? "AND IF(a.idproyecto = 0, d.id = $d->idproyecto, a.idproyecto = $d->idproyecto) " : '';
    $qFacts.= $cliente != '' ? "AND IF(a.idcliente = 0, a.nombre LIKE '%$cliente%', f.nombre LIKE '%$cliente%') " : '';
    return $qFacts;
}

$app->post('/antiguedad', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->falstr)) { $d->falstr = date('Y-m-d'); }
    $d->idempresa = count($d->idempresa) > 0 ? implode(',', $d->idempresa) : '0';
    if(!isset($d->idproyecto)) { $d->idproyecto = 0; }
    if(!isset($d->detallada)){ $d->detallada = 0; }
    if(!isset($d->orderalfa)){ $d->orderalfa = 1; }
    if(!isset($d->cliente)){ $d->cliente = ''; }
    if(!isset($d->pagoextra)){ $d->pagoextra = 0; }
    if(!isset($d->vernegativos)){ $d->vernegativos = 0; }
    if(!isset($d->abreviado)){ $d->abreviado = 0; }

    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, 'Antigüedad de Saldos de Cliente' AS titulo, NULL AS esdetallado";
    $generales = $db->getQuery($query)[0];

    if((int)$d->detallada == 1){
        $generales->titulo.= " Detallada";
        $generales->esdetallado = 1;
    }

    $qFacts = queryFacturas($d);

    $query = "SELECT DISTINCT j.idempresa, j.empresa, j.abreviaempresa, 0.00 AS r030, 0.00 AS r3160, 0.00 AS r6190, 0.00 AS r90, 0.00 AS saldo FROM ($qFacts) j ";
    $query.= "ORDER BY IF( $d->orderalfa = 1, j.empresa, j.ordensumario)";
    $antiguedades = $db->getQuery($query);
    $cntAntiguedades = count($antiguedades);
    for($i = 0; $i < $cntAntiguedades; $i++){
        $antiguedad = $antiguedades[$i];
        $query = "SELECT DISTINCT j.proyecto FROM ($qFacts) j WHERE j.idempresa = $antiguedad->idempresa ORDER BY j.proyecto";
        $antiguedad->proyectos = $db->getQuery($query);
        $cntProyectos = count($antiguedad->proyectos);
        for($j = 0; $j < $cntProyectos; $j++){
            $proyecto = $antiguedad->proyectos[$j];
            $andProyecto = "AND j.proyecto = ".(!is_null($proyecto->proyecto) ? "'".$proyecto->proyecto."'" : 'NULL');
            $query = "SELECT DISTINCT j.cliente, 0.00 AS r030, 0.00 AS r3160, 0.00 AS r6190, 0.00 AS r90, j.nombrecorto FROM ($qFacts) j WHERE j.idempresa = $antiguedad->idempresa $andProyecto ";
            $query.= (int)$d->abreviado == 0 ? '' : "AND j.dias > 60 "; 
            $query.="ORDER BY j.cliente";
            $proyecto->clientes = $db->getQuery($query);
            $cntClientes = count($proyecto->clientes);
            for($k = 0; $k < $cntClientes; $k++){
                $cliente = $proyecto->clientes[$k];
                $andCliente = "AND j.cliente = ".(!is_null($cliente) ? "'".$cliente->cliente."'" : 'NULL');
                //Comprobar si el saldo del cliente es diferente de cero
                $query = "SELECT SUM(j.saldo) FROM ($qFacts) j WHERE j.idempresa = $antiguedad->idempresa $andProyecto $andCliente";
                $cliente->saldo = (float)$db->getOneField($query);
                $tieneSaldo = $cliente->saldo != 0;
                // if($tieneSaldo){
                    if($cliente->saldo >= 0){
                        $cliente->saldo = number_format($cliente->saldo, 2);
                    } else {
                        $cliente->saldo = '('.number_format(abs($cliente->saldo), 2).')';
                    }
                    if((int)$d->detallada == 1){
                        $query = "SELECT j.serie, j.numero, DATE_FORMAT(j.fecha, '%d/%m/%Y') AS fecha, j.serieadmin, j.numeroadmin, ";
                        $query.= "IF(j.dias < 31, IF(j.saldo >= 0, FORMAT(j.saldo, 2), CONCAT('(', FORMAT(ABS(j.saldo), 2),')')), 0.00) AS r030, ";
                        $query.= "IF(j.dias BETWEEN 31 AND 60, IF(j.saldo >= 0, FORMAT(j.saldo, 2), CONCAT('(', FORMAT(ABS(j.saldo), 2),')')), 0.00) AS r3160, ";
                        $query.= "IF(j.dias BETWEEN 61 AND 90, IF(j.saldo >= 0, FORMAT(j.saldo, 2), CONCAT('(', FORMAT(ABS(j.saldo), 2),')')), 0.00) AS r6190, ";
                        $query.= "IF(j.dias > 90, IF(j.saldo >= 0, FORMAT(j.saldo, 2), CONCAT('(', FORMAT(ABS(j.saldo), 2),')')), 0.00) AS r90 ";
                        $query.= "FROM ($qFacts) j ";
                        $query.= "WHERE j.idempresa = $antiguedad->idempresa $andProyecto $andCliente ";
                        $query.= (int)$d->abreviado == 0 ? '' : "AND j.dias > 60 "; 
                        $query.= "ORDER BY j.fecha, j.numero";
                        $cliente->facturas = $db->getQuery($query);
                    } else {
                        $cliente->facturas = [];
                    }

                    $query = "SELECT '' AS serie, '' AS numero, 'Totales:' AS fecha, '' AS serieadmin, '' AS numeroadmin, ";
                    $query.= "IF(SUM(IF(j.dias < 31, j.saldo, 0.00)) >= 0, FORMAT(SUM(IF(j.dias < 31, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias < 31, j.saldo, 0.00))), 2), ')')) AS r030, ";
                    $query.= "IF(SUM(IF(j.dias BETWEEN 31 AND 60, j.saldo, 0.00)) >= 0, FORMAT(SUM(IF(j.dias BETWEEN 31 AND 60, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias BETWEEN 31 AND 60, j.saldo, 0.00))), 2), ')')) AS r3160, ";
                    $query.= "IF(SUM(IF(j.dias BETWEEN 61 AND 90, j.saldo, 0.00)) >=0, FORMAT(SUM(IF(j.dias BETWEEN 61 AND 90, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias BETWEEN 61 AND 90, j.saldo, 0.00))), 2), ')')) AS r6190, ";
                    $query.= "IF(SUM(IF(j.dias > 90, j.saldo, 0.00)) >= 0, FORMAT(SUM(IF(j.dias > 90, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias > 90, j.saldo, 0.00))), 2), ')')) AS r90 ";
                    $query.= "FROM ($qFacts) j ";
                    $query.= "WHERE j.idempresa = $antiguedad->idempresa $andProyecto $andCliente ";
                    $query.= (int)$d->abreviado == 0 ? '' : "AND j.dias > 60 "; 
                    $sumasCliente = $db->getQuery($query)[0];

                    $cliente->r030 = $sumasCliente->r030;
                    $cliente->r3160 = $sumasCliente->r3160;
                    $cliente->r6190 = $sumasCliente->r6190;
                    $cliente->r90 = $sumasCliente->r90;
                // } else {
                    // $cliente->saldo = null;
                // }
            }
        }

        if($cntAntiguedades > 0){
            $query = "SELECT IF(SUM(IF(j.dias < 31, j.saldo, 0.00)) >= 0, FORMAT(SUM(IF(j.dias < 31, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias < 31, j.saldo, 0.00))), 2), ')')) AS r030, ";
            $query.= "IF(SUM(IF(j.dias BETWEEN 31 AND 60, j.saldo, 0.00)) >= 0, FORMAT(SUM(IF(j.dias BETWEEN 31 AND 60, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias BETWEEN 31 AND 60, j.saldo, 0.00))), 2), ')')) AS r3160, ";
            $query.= "IF(SUM(IF(j.dias BETWEEN 61 AND 90, j.saldo, 0.00)) >= 0, FORMAT(SUM(IF(j.dias BETWEEN 61 AND 90, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias BETWEEN 61 AND 90, j.saldo, 0.00))), 2), ')')) AS r6190, ";
            $query.= "IF(SUM(IF(j.dias > 90, j.saldo, 0.00)) >= 0, FORMAT(SUM(IF(j.dias > 90, j.saldo, 0.00)), 2), CONCAT('(', FORMAT(ABS(SUM(IF(j.dias > 90, j.saldo, 0.00))), 2), ')')) AS r90, ";
            $query.= "IF(SUM(j.saldo) >= 0, FORMAT(SUM(j.saldo), 2), CONCAT('(', FORMAT(ABS(SUM(j.saldo)), 2), ')') ) AS saldo ";
            $query.= "FROM ($qFacts) j ";
            $query.= "WHERE j.idempresa = $antiguedad->idempresa ";
            $query.= (int)$d->abreviado == 0 ? '' : "AND j.dias > 60 "; 
            $sumas = $db->getQuery($query)[0];

            $antiguedad->r030 = $sumas->r030;
            $antiguedad->r3160 = $sumas->r3160;
            $antiguedad->r6190 = $sumas->r6190;
            $antiguedad->r90 = $sumas->r90;
            $antiguedad->saldo = $sumas->saldo;
        }
    }

    print json_encode(['generales' => $generales, 'antiguedad' => $antiguedades]);
});

$app->post('/pagoextra', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->falstr)) { $d->falstr = date('Y-m-d'); }
    if(!isset($d->idempresa)) { $d->idempresa = 0; }
    if(!isset($d->idproyecto)) { $d->idproyecto = 0; }
    if(!isset($d->detallada)){ $d->detallada = 0; }
    if(!isset($d->orderalfa)){ $d->orderalfa = 1; }
    if(!isset($d->cliente)){ $d->cliente = ''; }
    if(!isset($d->pagoextra)){ $d->pagoextra = 0; }

    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy";
    $generales = $db->getQuery($query)[0];

    $qFacts = queryFacturas($d);

    $query = "SELECT DISTINCT j.idempresa, j.empresa, j.abreviaempresa FROM ($qFacts) j ";
    $query.= "ORDER BY IF( $d->orderalfa = 1, j.empresa, j.ordensumario)";
    $antiguedades = $db->getQuery($query);
    $cntAntiguedades = count($antiguedades);
    for($i = 0; $i < $cntAntiguedades; $i++){
        $antiguedad = $antiguedades[$i];
        $query = "SELECT DISTINCT j.proyecto FROM ($qFacts) j WHERE j.idempresa = $antiguedad->idempresa ORDER BY j.proyecto";
        $antiguedad->proyectos = $db->getQuery($query);
        $cntProyectos = count($antiguedad->proyectos);
        for($j = 0; $j < $cntProyectos; $j++){
            $proyecto = $antiguedad->proyectos[$j];
            $andProyecto = "AND j.proyecto = ".(!is_null($proyecto->proyecto) ? "'".$proyecto->proyecto."'" : 'NULL');
            $query = "SELECT DISTINCT j.cliente FROM ($qFacts) j WHERE j.idempresa = $antiguedad->idempresa $andProyecto ORDER BY j.cliente";
            $proyecto->clientes = $db->getQuery($query);
            $cntClientes = count($proyecto->clientes);
            for($k = 0; $k < $cntClientes; $k++){
                $cliente = $proyecto->clientes[$k];
                $andCliente = "AND j.cliente = ".(!is_null($cliente) ? "'".$cliente->cliente."'" : 'NULL');
                $query = "SELECT j.serie, j.numero, DATE_FORMAT(j.fecha, '%d/%m/%Y') AS fecha, ";
                $query.= "CONCAT('(', FORMAT(ABS(j.saldo), 2),')') AS saldo, j.serieadmin, j.numeroadmin ";
                $query.= "FROM ($qFacts) j ";
                $query.= "WHERE j.idempresa = $antiguedad->idempresa $andProyecto $andCliente ";
                $query.= "ORDER BY j.fecha, j.numero";
                //print $query;
                $cliente->facturas = $db->getQuery($query);
                if(count($cliente->facturas) > 0){
                    $query = "SELECT '' AS serie, '' AS numero, 'Total:' AS fecha, ";
                    $query.= "CONCAT('(', FORMAT(ABS(SUM(j.saldo)), 2), ')') AS saldo, '' AS serieadmin, '' AS numeroadmin ";
                    $query.= "FROM ($qFacts) j ";
                    $query.= "WHERE j.idempresa = $antiguedad->idempresa $andProyecto $andCliente ";
                    $cliente->facturas[] = $db->getQuery($query)[0];
                }
            }
        }        
    }
    print json_encode(['generales' => $generales, 'facturas' => $antiguedades]);
});

// nuevo reporte de antiguedad de saldos cliente
$app->post('/anticliente', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $primero = true;
    $ids_str = count($d->idempresa) > 0 ? implode(',', $d->idempresa) : "''";
    date_default_timezone_set("America/Guatemala");

    // separadores
    $separador_empresa = new StdClass;
    $separador_proyecto = new StdClass;
    $separador_cliente = new StdClass;

    // sumadores
    // empresa
    $sumas_empresa = new StdClass;
    $sumas_empresa->a30 = array();
    $sumas_empresa->a60 = array();
    $sumas_empresa->a90 = array();
    $sumas_empresa->aMas = array();
    $sumas_empresa->saldo = array();
    // proyecto
    $sumas_proyecto = new StdClass;
    $sumas_proyecto->a30 = array();
    $sumas_proyecto->a60 = array();
    $sumas_proyecto->a90 = array();
    $sumas_proyecto->aMas = array();
    $sumas_proyecto->saldo = array();
    // clientes
    $sumas_cliente = new StdClass;
    $sumas_cliente->a30 = array();
    $sumas_cliente->a60 = array();
    $sumas_cliente->a90 = array();
    $sumas_cliente->aMas = array();
    $sumas_cliente->saldo = array();

    // array de nombre de meses
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // clase para fechas
    $letra = new stdClass();

    $letra->al = new DateTime($d->falstr);
    $letra->al = $letra->al->format('d/m/Y');

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->detallado = $d->detallada ? 'Detallado' : null;

    // array de facturas
    $facturas = array();

    $query = "SELECT 
                a.id,
                a.idempresa,
                b.idproyecto,
                a.idcliente,
                c.nomempresa AS empresa,
                d.nomproyecto AS proyecto,
                SUBSTRING(e.nombre, 1, 50) AS cliente,
                e.nombrecorto AS cliente_corto,
                a.fecha AS orden,
                CONCAT(a.serie, '-', a.numero) AS factura,
                CONCAT(a.serieadmin, '-', a.numeroadmin) AS interno,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                DATEDIFF('$d->falstr', a.fecha) AS dias,
                ROUND(a.total, 2) AS monto,
                f.monto AS resta,
                ROUND(a.total - IFNULL(f.monto, 0.00), 2) AS saldo,
                c.abreviatura
            FROM
                factura a
                    INNER JOIN
                contrato b ON a.idcontrato = b.id
                    INNER JOIN
                empresa c ON a.idempresa = c.id
                    INNER JOIN
                proyecto d ON b.idproyecto = d.id
                    INNER JOIN
                cliente e ON a.idcliente = e.id
                    LEFT JOIN
                (SELECT 
                    a.idfactura, SUM(a.monto) AS monto
                FROM
                    detcobroventa a
                        INNER JOIN 
                    recibocli b ON a.idrecibocli = b.id
                WHERE 
                    b.fecha <= '$d->falstr'
                GROUP BY idfactura) f ON f.idfactura = a.id
                    LEFT JOIN
                (SELECT 
                    idfactura, id
                FROM
                    detcobroventa
                GROUP BY idfactura) g ON g.idfactura = a.id
            WHERE
                a.anulada = 0
                    AND (ROUND(a.total - IFNULL(f.monto, 0.00), 2) != 0.00
                    OR (a.total - f.monto) IS NULL) ";
    $query.= $d->vernegativos ? "AND IF(ISNULL(g.id) OR a.idfox > 0, a.pagada = 0, ((a.total - IFNULL(f.monto, 0.00)) < -0.5 OR (a.total - IFNULL(f.monto, 0.00)) > 0.5)) " : 
    "AND IF(ISNULL(g.id) OR a.idfox > 0, a.pagada = 0, (a.total - IFNULL(f.monto, 0.00)) > 0.5) ";
    $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : "";
    $query.= isset($d->idproyecto) ? "AND a.idproyecto = $d->idproyecto " : "";
    $query.= isset($d->idcliente) > 0 ? "AND a.idcliente = $d->idcliente " : "";
    $query.= $d->abreviado ? "AND DATEDIFF('$d->falstr', a.fecha) > 60 " : "";
    $query.=       "AND a.idtipofactura NOT IN (9 , 13) 
                    AND a.fecha <= '$d->falstr'
                    AND a.id NOT IN (SELECT 
                        idfacturaafecta
                    FROM
                        factura
                    WHERE
                        idtipofactura IN (9 , 13))
            ORDER BY 5 ASC , 6 ASC , 7 ASC , 9 ASC";
    // echo $query; return;
    $data = $db->getQuery($query);

    foreach($data as $dat) {
        asignarMonto($dat);
    }

    $cntsFacturas = count($data);

    for ($i = 1; $i < $cntsFacturas; $i++)  {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];

        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            $separador_empresa->nombre = $anterior->empresa;
            $separador_empresa->abreviatura = $anterior->abreviatura;
            $separador_empresa->proyectos = array();
            // proyecto
            $separador_proyecto->nombre = $anterior->proyecto;
            $separador_proyecto->clientes = array();
            // cliente
            $separador_cliente->nombre = $anterior->cliente;
            $separador_cliente->corto = $anterior->cliente_corto;
            $separador_cliente->detallado = $d->detallada ? true : null; 
            $separador_cliente->facturas = array();
            $primero = false;
        }

        // empujar anteriores para sumas
        // cliente
        array_push($sumas_cliente->a30, $anterior->a30);
        array_push($sumas_cliente->a60, $anterior->a60);
        array_push($sumas_cliente->a90, $anterior->a90);
        array_push($sumas_cliente->aMas, $anterior->amas);
        array_push($sumas_cliente->saldo, $anterior->saldo);
        // proyecto
        array_push($sumas_proyecto->a30, $anterior->a30);
        array_push($sumas_proyecto->a60, $anterior->a60);
        array_push($sumas_proyecto->a90, $anterior->a90);
        array_push($sumas_proyecto->aMas, $anterior->amas);
        array_push($sumas_proyecto->saldo, $anterior->saldo);
        // empresa
        array_push($sumas_empresa->a30, $anterior->a30);
        array_push($sumas_empresa->a60, $anterior->a60);
        array_push($sumas_empresa->a90, $anterior->a90);
        array_push($sumas_empresa->aMas, $anterior->amas);
        array_push($sumas_empresa->saldo, $anterior->saldo);

        array_push($separador_cliente->facturas, $anterior);

        if ($anterior->idcliente !== $actual->idcliente) {
            // generar variable de totales
            $separador_cliente->total_a30 = round(array_sum($sumas_cliente->a30), 2);
            $separador_cliente->total_a60 = round(array_sum($sumas_cliente->a60), 2);
            $separador_cliente->total_a90 = round(array_sum($sumas_cliente->a90), 2);
            $separador_cliente->total_aMas = round(array_sum($sumas_cliente->aMas), 2);
            $separador_cliente->total_saldo = round(array_sum($sumas_cliente->saldo), 2);

            // total general
            // array_push($suma_ventas, $totales->total);

            // empujar a array padre
            array_push($separador_proyecto->clientes, $separador_cliente);

            // limpiar variables 
            $sumas_cliente->a30 = array();
            $sumas_cliente->a60 = array();
            $sumas_cliente->a90 = array();
            $sumas_cliente->aMas = array();
            $sumas_cliente->saldo = array();

            $separador_cliente = new StdClass;
            $separador_cliente->nombre = $actual->cliente;
            $separador_cliente->corto = $actual->cliente_corto;
            $separador_cliente->detallado = $d->detallada ? true : null; 
            $separador_cliente->facturas = array();
        }

        if ($anterior->idproyecto !== $actual->idproyecto) {

            // generar variable de totales
            $separador_proyecto->total_a30 = round(array_sum($sumas_proyecto->a30), 2);
            $separador_proyecto->total_a60 = round(array_sum($sumas_proyecto->a60), 2);
            $separador_proyecto->total_a90 = round(array_sum($sumas_proyecto->a90), 2);
            $separador_proyecto->total_aMas = round(array_sum($sumas_proyecto->aMas), 2);
            $separador_proyecto->total_saldo = round(array_sum($sumas_proyecto->saldo), 2);

            // empujar a array padre
            array_push($separador_empresa->proyectos, $separador_proyecto);

            // limpiar variables 
            // sumas
            $sumas_proyecto->a30 = array();
            $sumas_proyecto->a60 = array();
            $sumas_proyecto->a90 = array();
            $sumas_proyecto->aMas = array();
            $sumas_proyecto->saldo = array();
            // separador
            $separador_proyecto = new StdClass;
            $separador_proyecto->nombre = $actual->proyecto;
            $separador_proyecto->clientes = array();
        }

        if ($anterior->idempresa !== $actual->idempresa) {
            // generar variable de totales
            $separador_empresa->total_a30 = round(array_sum($sumas_empresa->a30), 2);
            $separador_empresa->total_a60 = round(array_sum($sumas_empresa->a60), 2);
            $separador_empresa->total_a90 = round(array_sum($sumas_empresa->a90), 2);
            $separador_empresa->total_aMas = round(array_sum($sumas_empresa->aMas), 2);
            $separador_empresa->total_saldo = round(array_sum($sumas_empresa->saldo), 2);

            // empujar a array padre
            array_push($facturas, $separador_empresa);

            // limpiar variables 
            // sumas
            $sumas_empresa->a30 = array();
            $sumas_empresa->a60 = array();
            $sumas_empresa->a90 = array();
            $sumas_empresa->aMas = array();
            $sumas_empresa->saldo = array();
            // separador
            $separador_empresa = new StdClass;
            $separador_empresa->nombre = $actual->empresa;
            $separador_empresa->abreviatura = $actual->abreviatura;
            $separador_empresa->proyectos = array();
        }
        
        // para empujar el ultimo dato
        if ($i+1 == $cntsFacturas) {
            // empujar ultima factura
            array_push($separador_cliente->facturas, $actual);

            // empujar anteriores para sumas
            // cliente
            array_push($sumas_cliente->a30, $actual->a30);
            array_push($sumas_cliente->a60, $actual->a60);
            array_push($sumas_cliente->a90, $actual->a90);
            array_push($sumas_cliente->aMas, $actual->amas);
            array_push($sumas_cliente->saldo, $actual->saldo);
            // proyecto
            array_push($sumas_proyecto->a30, $actual->a30);
            array_push($sumas_proyecto->a60, $actual->a60);
            array_push($sumas_proyecto->a90, $actual->a90);
            array_push($sumas_proyecto->aMas, $actual->amas);
            array_push($sumas_proyecto->saldo, $actual->saldo);
            // empresa
            array_push($sumas_empresa->a30, $actual->a30);
            array_push($sumas_empresa->a60, $actual->a60);
            array_push($sumas_empresa->a90, $actual->a90);
            array_push($sumas_empresa->aMas, $actual->amas);
            array_push($sumas_empresa->saldo, $actual->saldo);

            // totales
            $separador_cliente->total_a30 = round(array_sum($sumas_cliente->a30), 2);
            $separador_cliente->total_a60 = round(array_sum($sumas_cliente->a60), 2);
            $separador_cliente->total_a90 = round(array_sum($sumas_cliente->a90), 2);
            $separador_cliente->total_aMas = round(array_sum($sumas_cliente->aMas), 2);
            $separador_cliente->total_saldo = round(array_sum($sumas_cliente->saldo), 2);
        
            array_push($separador_proyecto->clientes, $separador_cliente);

            // generar variable de totales
            $separador_proyecto->total_a30 = round(array_sum($sumas_proyecto->a30), 2);
            $separador_proyecto->total_a60 = round(array_sum($sumas_proyecto->a60), 2);
            $separador_proyecto->total_a90 = round(array_sum($sumas_proyecto->a90), 2);
            $separador_proyecto->total_aMas = round(array_sum($sumas_proyecto->aMas), 2);
            $separador_proyecto->total_saldo = round(array_sum($sumas_proyecto->saldo), 2);

            // empujar a array padre
            array_push($separador_empresa->proyectos, $separador_proyecto);

            // generar variable de totales
            $separador_empresa->total_a30 = round(array_sum($sumas_empresa->a30), 2);
            $separador_empresa->total_a60 = round(array_sum($sumas_empresa->a60), 2);
            $separador_empresa->total_a90 = round(array_sum($sumas_empresa->a90), 2);
            $separador_empresa->total_aMas = round(array_sum($sumas_empresa->aMas), 2);
            $separador_empresa->total_saldo = round(array_sum($sumas_empresa->saldo), 2);
            
            // empujar a array padre
            array_push($facturas, $separador_empresa);
        }
    }   

    print json_encode([ 'encabezado' => $letra, 'empresas' => $facturas ]);
});

function asignarMonto($d) {
    if ($d->dias <= 30) {
        $d->a30 = $d->saldo;
        $d->a60 = 0.00;
        $d->a90 = 0.00;
        $d->amas = 0.00;
    } else if ($d->dias > 30 && $d->dias <= 60) {
        $d->a30 = 0.00;
        $d->a60 = $d->saldo;
        $d->a90 = 0.00;
        $d->amas = 0.00;
    } else if ($d->dias > 60 && $d->dias <= 90) {
        $d->a30 = 0.00;
        $d->a60 = 0.00;
        $d->a90 = $d->saldo;
        $d->amas = 0.00;
    } else {
        $d->a30 = 0.00;
        $d->a60 = 0.00;
        $d->a90 = 0.00;
        $d->amas = $d->saldo;
    }
}

$app->run();
