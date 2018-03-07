<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para pagos
$app->get('/lstpagos/:idempresa/:flimite/:idmoneda', function($idempresa, $flimite, $idmoneda){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, a.idproveedor, b.nombre AS proveedor, a.serie, a.documento, a.fechapago, a.conceptomayor, a.subtotal, a.totfact, ";
    $query.= "IFNULL(c.montopagado, 0.00) AS montopagado, 0 AS retenisr, 1 AS pagatodo, (a.totfact - (a.isr + IFNULL(c.montopagado, 0.00))) AS montoapagar, ";
    $query.= "(a.totfact - (a.isr + IFNULL(c.montopagado, 0.00))) AS saldo, 0 AS pagar, d.simbolo AS moneda, a.tipocambio, a.idmoneda, a.isr, b.chequesa ";
    $query.= "FROM compra a LEFT JOIN proveedor b ON b.id = a.idproveedor LEFT JOIN (";
    $query.= "SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) c ON a.id = c.idcompra ";
    $query.= "LEFT JOIN moneda d ON d.id = a.idmoneda ";
    $query.= "WHERE (a.totfact - (a.isr + IFNULL(c.montopagado, 0.00))) > 0.00 AND a.idempresa = $idempresa ";
    $query.= $flimite !== "" ? ("AND a.fechapago <= '$flimite' ") : "";
    $query.= (int)$idmoneda > 0 ? ("AND a.idmoneda = $idmoneda ") : "";
    $query.= "ORDER BY b.nombre, a.fechapago";
    print $db->doSelectASJson($query);
});

$app->post('/g', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    //$conn = $db->getConn();
    $objBanco = array_shift($d); //Es un objeto, no un array con un objeto
    //Ordeno el array por id del proveedor
    usort($d, function($a, $b){ $idpa = (int)$a->idproveedor; $idpb = (int)$b->idproveedor; return $idpa == $idpb ? 0 : ($idpa < $idpb ? -1 : 1); });

    $cantPagos = count($d);
    $idprovs = [];
    //Extraigo los diferentes ids de proveedores del array de compras
    for($x = 0; $x < $cantPagos; $x++){
        if(!in_array((int)$d[$x]->idproveedor, $idprovs)){
            $idprovs[] = (int)$d[$x]->idproveedor;
        };
    };
    //print_r($idprovs);
    $generados = '';
    $cantProvs = count($idprovs);
    for($y = 0; $y < $cantProvs; $y++){
        $totAPagar = 0.0;
        $qFacturas = '';
        $nombreProveedor = '';
        $idempresa = 0;
        $losPagos = [];
        for($z = 0; $z < $cantPagos; $z++){
            $quetzalizar = false;
            $tc = ($quetzalizar ? (float)$d[$z]->tipocambio : 1.00);
            if((int)$d[$z]->idproveedor == $idprovs[$y]){
                $totAPagar += (float)$d[$z]->montoapagar * $tc;
                if($idempresa == 0) {$idempresa = $d[$z]->idempresa; };
                if($nombreProveedor == ''){ $nombreProveedor = $d[$z]->chequesa; };
                if($qFacturas !== ''){ $qFacturas.= ', '; };
                $qFacturas.= $d[$z]->serie.'-'.$d[$z]->documento;
                $losPagos[] = ['idcompra' => $d[$z]->id, 'monto' => ($d[$z]->montoapagar * $tc)];
            };
        };

        //Inserto la transaccion bancaria
        $getCorrela = "SELECT correlativo FROM banco WHERE id = ".$objBanco->idbanco;
        $query = "INSERT INTO tranban(idbanco, tipotrans, fecha, monto, beneficiario, concepto, numero, origenbene, idbeneficiario) ";
        $query.= "VALUES($objBanco->idbanco, 'C', '$objBanco->fechatranstr', $totAPagar, '$nombreProveedor', ";
        $query.= "'Pago de factura(s) $qFacturas', ($getCorrela), 1, $idprovs[$y])";
        //echo $query.'<br/><br/>';
        $db->doQuery($query);
        $lastid = $db->getLastId();
        if($generados !== ''){ $generados.= ', '; }
        $generados.= $lastid;
        $db->doQuery("UPDATE banco SET correlativo = correlativo + 1 WHERE id = ".$objBanco->idbanco);
        $origen = 1;

        //Inserto el detalle contable
        $ctaproveedores = (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = ".$idempresa." AND idtipoconfig = 3");
        $ctabanco = (int)$db->getOneField("SELECT idcuentac FROM banco WHERE id = ".$objBanco->idbanco);

        if($ctaproveedores > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= $origen.", ".$lastid.", ".$ctaproveedores.", ".($totAPagar * (float)$objBanco->tipocambio).", 0.00, 'Pago de factura(s) ".$qFacturas."')";
            $db->doQuery($query);
        };

        if($ctabanco > 0){
            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
            $query.= $origen.", ".$lastid.", ".$ctabanco.", 0.00, ".($totAPagar * (float)$objBanco->tipocambio).", 'Pago de factura(s) ".$qFacturas."')";
            $db->doQuery($query);
        };

        //Inserto el detalle de pagos
        if((int)$lastid > 0){
            $cantCompras = count($losPagos);
            for($i = 0; $i < $cantCompras; $i++){
                $query = "INSERT INTO detpagocompra(idcompra, idtranban, monto)";
                $query.= "VALUES(".$losPagos[$i]['idcompra'].", ".$lastid.", ".$losPagos[$i]['monto'].")";
                $db->doQuery($query);
            };
        }
    };

    $chequesGenerados = '';
    if($generados !== ''){
        $chequesGenerados = $db->getOneField("SELECT GROUP_CONCAT(numero SEPARATOR ', ') AS cheques FROM tranban WHERE id IN(".$generados.") GROUP BY idbanco");
    }

    print json_encode(
        ['mensaje' => ($chequesGenerados !== '' ?
        'Se generaron los cheques '.$chequesGenerados.' del banco '.$objBanco->nombanco :
        'No se generó ningún cheque...')]
    );
});

$app->post('/rptfactprov', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $fSoloPendientes = "AND (a.totfact - (a.isr + IFNULL(c.montopagado, 0.00))) > 0.00 ";
    $fProveedor = "AND a.idproveedor = ".$d->idprov." ";
    $fDel = "AND a.fechaingreso >= '".$d->fdelstr."' ";
    $fAl = "AND a.fechaingreso <= '".$d->falstr."' ";

    $query = "SELECT b.nit, b.nombre AS proveedor, a.serie, a.documento, a.fechaingreso, a.fechapago, a.conceptomayor, ";
    $query.= "a.totfact, (a.totfact - IFNULL(c.montopagado, 0.00)) AS saldo, b.id AS idproveedor ";
    $query.= "FROM compra a LEFT JOIN proveedor b ON b.id = a.idproveedor ";
    $query.= "LEFT JOIN (SELECT idcompra, SUM(monto) AS montopagado FROM detpagocompra GROUP BY idcompra) c ON a.id = c.idcompra ";
    $query.= "WHERE a.idempresa = ".$d->idempresa." ";
    $query.= (int)$d->pendientes === 1 ? $fSoloPendientes : "";
    $query.= (int)$d->idprov > 0 ? $fProveedor : "";
    $query.= $d->fdelstr !== "" ? $fDel : "";
    $query.= $d->falstr !== "" ? $fAl: "";
    $query.= "ORDER BY b.nombre, a.fechapago";
    print $db->doSelectASJson($query);
});

$app->post('/rpthistpagos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $fProveedor = "AND b.idproveedor = ".$d->idprov." ";
    $fDel = "AND d.fecha >= '".$d->fdelstr."' ";
    $fAl = "AND d.fecha <= '".$d->falstr."' ";

    $query = "SELECT c.nit, c.nombre AS proveedor, CONCAT(b.serie,'-',b.documento) AS documento, f.descripcion AS tipotranban, d.numero, e.nombre AS banco, ";
    $query.= "d.fecha, d.beneficiario,  a.monto, c.id AS idprov, b.id AS idcompra, b.totfact ";
    $query.= "FROM detpagocompra a INNER JOIN compra b ON b.id = a.idcompra INNER JOIN proveedor c ON c.id = b.idproveedor ";
    $query.= "INNER JOIN tranban d ON d.id = a.idtranban INNER JOIN banco e ON e.id = d.idbanco ";
    $query.= "INNER JOIN tipomovtranban f ON f.abreviatura = d.tipotrans INNER JOIN empresa g ON g.id = e.idempresa ";
    $query.= "WHERE a.esrecprov = 0 AND g.id = ".$d->idempresa." ";
    $query.= (int)$d->idprov > 0 ? $fProveedor : "";
    $query.= $d->fdelstr !== "" ? $fDel : "";
    $query.= $d->falstr !== "" ? $fAl: "";
    $query.= "UNION ALL ";
    $query.= "SELECT c.nit, c.nombre AS proveedor, CONCAT(b.serie,'-',b.documento) AS documento, f.descripcion AS tipotranban, d.numero, e.nombre AS banco, ";
    $query.= "d.fecha, d.beneficiario,  a.monto, c.id AS idprov, b.id AS idcompra, b.totfact ";
    $query.= "FROM detpagocompra a INNER JOIN compra b ON b.id = a.idcompra INNER JOIN proveedor c ON c.id = b.idproveedor INNER JOIN detrecprov h ON b.id = h.idorigen ";
    $query.= "INNER JOIN reciboprov i ON i.id = h.idrecprov LEFT JOIN tranban d ON d.id = i.idtranban LEFT JOIN banco e ON e.id = d.idbanco ";
    $query.= "LEFT JOIN tipomovtranban f ON f.abreviatura = d.tipotrans LEFT JOIN empresa g ON g.id = e.idempresa ";
    $query.= "WHERE a.esrecprov = 1 AND h.origen = 2 AND i.idempresa = $d->idempresa ";
    $query.= (int)$d->idprov > 0 ? $fProveedor : "";
    $fDel = "AND i.fecha >= '".$d->fdelstr."' ";
    $fAl = "AND i.fecha <= '".$d->falstr."' ";
    $query.= $d->fdelstr !== "" ? $fDel : "";
    $query.= $d->falstr !== "" ? $fAl: "";
    $query.= "ORDER BY 2, 10, 6, 5";
    print $db->doSelectASJson($query);
});

$app->run();