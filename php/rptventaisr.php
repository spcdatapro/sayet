<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptisr', function(){
	
	$d = json_decode(file_get_contents('php://input'));
	
	$idempresa = $d->idempresa;
	$mes = $d->mes;
	$anio = $d->anio;
	$sinret = $d->sinret;
	$retenido = $d->retenido;
	$parqueo = $d->parqueo;
	$resumen = $d->resumen;
	$qrret = "";
	$qtitulo = "";
	
	if($resumen == 1 ){
		$qrret = "";
		$qtitulo = "";
	}else{
		if($sinret == 1){
			$qrret = "and (b.idtipocliente = 3 or a.retisr = 0) ";
			$qtitulo = "Facturas sin retencion";
		}else{
			//$qrret = "and b.idtipocliente <> 3 and a.retisr <> 0 ";
			$qrret = "and a.retisr <> 0 ";
			$qtitulo = "Facturas y retenciones";
		}
	}
	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$mesletra = $meses[$mes-1];
	
    $db = new dbcpm();
	/*
    $query = "SELECT a.fecha AS fechafactura, c.siglas AS tipodocumento, a.serie, a.numero AS documento, if(a.noformisr is null,'',a.noformisr) as retencion, ";
	$query.= "TRIM(a.nit) AS nit, ";
    $query.= "substr(TRIM(a.nombre),1,30) AS cliente, ";
    $query.= "IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND(((a.subtotal - a.totdescuento) + IF(a.idfox IS NULL, 0, a.retiva) - IF(a.idfox IS NULL, a.iva, 0)), 2), 0.00) AS subtotal, TRUNCATE(a.retisr, 2) AS retisr, ";
    $query.= "IF(a.anulada = 0, a.total, 0.00) AS totfact, '$retenido' as retenido, '$parqueo' as parqueo ";
    $query.= "FROM factura a LEFT JOIN contrato b ON b.id = a.idcontrato LEFT JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN cliente d ON d.id = a.idcliente ";
    $query.= "WHERE a.anulada = 0 and a.idtipoventa <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." AND a.mesiva = ".$mes." AND YEAR(a.fecha) = ".$anio." ";
	$query.= $qrret;
    $query.= "ORDER BY 3, 4";
	*/
	
	$query = "SELECT a.fecha AS fechafactura, c.siglas AS tipodocumento, a.serie, a.numero AS documento, if(a.noformisr is null,'',a.noformisr) as retencion, ";
	$query.= "TRIM(a.nit) AS nit, ";
    $query.= "substr(TRIM(a.nombre),1,30) AS cliente, ";
    $query.= "
    IF(a.fecha < '2017-12-01',
    IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND(((a.subtotal - a.totdescuento) + IF(a.idfox IS NULL, 0, a.retiva) - IF(a.idfox IS NULL, a.iva, 0)), 2), 0.00),
    IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND(a.subtotal - a.iva, 2), 0.00)
    )
    AS subtotal,
    ";
    $query.= "TRUNCATE(a.retisr, 2) AS retisr, ";
    $query.= "IF(a.anulada = 0, a.total, 0.00) AS totfact, '$retenido' as retenido, '$parqueo' as parqueo ";
    $query.= "FROM factura a LEFT JOIN contrato b ON b.id = a.idcontrato LEFT JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN cliente d ON d.id = a.idcliente ";
    $query.= "WHERE a.anulada = 0 and a.idtipoventa <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." ";
	$query.= $d->fdelstr == '' || $d->falstr == '' ? "AND a.mesiva = $mes AND YEAR(a.fecha) = $anio " : '';
	$query.= $d->fdelstr != '' && $d->falstr != '' ? "AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' " : '';
	$query.= $qrret;
	$query.= $d->cliente != '' ? "AND a.nombre = '$d->cliente' " : '';
    $query.= "ORDER BY 3, 4";
	
	$detisr = $db->getQuery($query);
	
	$libisr = array();
	$idarray = 0;
	$isrcalc=0.00;
			
	foreach ($detisr as $dlbi) {
		$idarray++;
		$isrcalc=0.00;
		$isrcalculado = round($db->calculaISR($dlbi->subtotal,1),2);
		
		if($dlbi->retisr <> 0){
			$isrcalc = $dlbi->retisr;
		}else{
			$isrcalc = number_format(($isrcalculado == 0? round(($dlbi->subtotal * 5 / 100),2) : $isrcalculado), 2, '.', '');
		}
		
		array_push($libisr,
			array(
				'nolinea' => $idarray,
				'documento' => $dlbi->documento,
				'fechafactura' => $dlbi->fechafactura,
				'isr' => $isrcalc,
				'nit' => $dlbi->nit,
				'cliente' => $dlbi->cliente,
				'serie' => $dlbi->serie,
				'subtotal' => $dlbi->subtotal,
				'tipodocumento' => $dlbi->tipodocumento,
				'totfact' => $dlbi->totfact,
				'retencion' => $dlbi->retencion,
				'retenido' => $dlbi->retenido,
				'parqueo' => $dlbi->parqueo
			)
		);	
	}
	
	$empresa = $db->getQuery("SELECT nomempresa, abreviatura,direccion,nit,'$mesletra' as mesrep, '$anio' as aniorep, '$qtitulo' as titulo FROM empresa WHERE id = $idempresa")[0];
    //print json_encode(['empresa' => $empresa, 'datos'=> $db->getQuery($query)]);
	
	$libro = new stdclass();
	$libro->empresa = $empresa;
	$libro->lbisr = $libisr;

	
	print json_encode($libro);
    //print $db->doSelectASJson($query);
});

$app->run();