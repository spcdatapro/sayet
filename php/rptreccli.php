<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
// $db = new dbcpm();

// $app->post('/recibos', function() use($db){
//     $d = json_decode(file_get_contents('php://input'));

//     $query = "SELECT DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del,  DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy";
//     $generales = $db->getQuery($query)[0];

//     $query = "SELECT DISTINCT g.idmoneda, UPPER(h.nommoneda) AS moneda, h.simbolo ";
//     $query.= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN empresa e ON e.id = a.idempresa LEFT JOIN detcobroventa c ON a.id = c.idrecibocli ";
//     $query.= "LEFT JOIN factura d ON d.id = c.idfactura ";
//     $query.= "LEFT JOIN tranban f ON f.id = a.idtranban LEFT JOIN banco g ON g.id = f.idbanco LEFT JOIN moneda h ON h.id = g.idmoneda ";
//     $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";
//     $query.= $d->idempresa != '' ? "AND a.idempresa IN($d->idempresa) " : "";
//     $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "";
//     $query.= (int)$d->numdel > 0 ? "AND a.numero >= $d->numdel " : "";
//     $query.= (int)$d->numal > 0 ? "AND a.numero <= $d->numal " : "";
//     $query.= (int)$d->idmoneda > 0 ? "AND g.idmoneda = $d->idmoneda " : "";
//     $query.= "ORDER BY h.eslocal DESC, h.simbolo";
//     //print $query;
//     $monedas = $db->getQuery($query);
//     $cntMonedas = count($monedas);

//     for($i = 0; $i < $cntMonedas; $i++){
//         $moneda = $monedas[$i];

//         $query = "SELECT DISTINCT a.idempresa, TRIM(e.nomempresa) AS empresa, TRIM(e.abreviatura) AS abreviaempresa ";
//         $query.= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN empresa e ON e.id = a.idempresa LEFT JOIN detcobroventa c ON a.id = c.idrecibocli ";
//         $query.= "LEFT JOIN factura d ON d.id = c.idfactura ";
//         $query.= "LEFT JOIN tranban f ON f.id = a.idtranban LEFT JOIN banco g ON g.id = f.idbanco LEFT JOIN moneda h ON h.id = g.idmoneda ";
//         $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND g.idmoneda = $moneda->idmoneda ";
//         $query.= $d->idempresa != '' ? "AND a.idempresa IN($d->idempresa) " : "";
//         $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "";
//         $query.= (int)$d->numdel > 0 ? "AND a.numero >= $d->numdel " : "";
//         $query.= (int)$d->numal > 0 ? "AND a.numero <= $d->numal " : "";
//         $query.= "GROUP BY a.id ";
//         $query.= "ORDER BY e.ordensumario, a.serie, a.numero, a.fecha";
//         //print $query;
//         $moneda->empresas = $db->getQuery($query);
//         $cntEmpresas = count($moneda->empresas);

//         for($j = 0; $j < $cntEmpresas; $j++){
//             $empresa = $moneda->empresas[$j];
//             $query = "SELECT a.id AS idrecibo, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.serie, IF(a.numero IS NULL, a.id, a.numero) AS numero, TRIM(b.nombre) AS cliente, ";
//             $query.= "GROUP_CONCAT(DISTINCT TRIM(CONCAT(TRIM(d.serie), ' ', TRIM(d.numero), IF(d.serieadmin IS NOT NULL, CONCAT(' (', d.serieadmin, '-', d.numeroadmin, ')') , ''))) ORDER BY d.serie, d.numero SEPARATOR ', ') AS facturas, FORMAT(SUM(c.monto), 2) AS totrecibo, ";
//             $query.= "CONCAT(f.tipotrans, f.numero) AS tranban, h.simbolo AS monedadep, g.siglas AS banco, FORMAT(f.monto, 2) AS montotran ";
//             $query.= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN empresa e ON e.id = a.idempresa LEFT JOIN detcobroventa c ON a.id = c.idrecibocli ";
//             $query.= "LEFT JOIN factura d ON d.id = c.idfactura ";
//             $query.= "LEFT JOIN tranban f ON f.id = a.idtranban LEFT JOIN banco g ON g.id = f.idbanco LEFT JOIN moneda h ON h.id = g.idmoneda ";
//             $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND g.idmoneda = $moneda->idmoneda AND a.idempresa = '$empresa->idempresa' ";
//             $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "";
//             $query.= (int)$d->numdel > 0 ? "AND a.numero >= $d->numdel " : "";
//             $query.= (int)$d->numal > 0 ? "AND a.numero <= $d->numal " : "";
//             $query.= "GROUP BY a.id ";
//             $query.= "ORDER BY e.ordensumario, a.serie, a.numero, a.fecha";
//             //print $query;
//             $empresa->recibos = $db->getQuery($query);
//             if(count($empresa->recibos) > 0){
//                 $query = "SELECT FORMAT(SUM(c.monto), 2) ";
//                 $query.= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN empresa e ON e.id = a.idempresa LEFT JOIN detcobroventa c ON a.id = c.idrecibocli ";
//                 $query.= "LEFT JOIN factura d ON d.id = c.idfactura ";
//                 $query.= "LEFT JOIN tranban f ON f.id = a.idtranban LEFT JOIN banco g ON g.id = f.idbanco LEFT JOIN moneda h ON h.id = g.idmoneda ";
//                 $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND g.idmoneda = $moneda->idmoneda AND a.idempresa = '$empresa->idempresa' ";
//                 $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "";
//                 $query.= (int)$d->numdel > 0 ? "AND a.numero >= $d->numdel " : "";
//                 $query.= (int)$d->numal > 0 ? "AND a.numero <= $d->numal " : "";
//                 $totempresa = $db->getOneField($query);

//                 $query = "SELECT FORMAT(SUM(f.monto), 2) ";
//                 $query.= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN empresa e ON e.id = a.idempresa ";
//                 $query.= "LEFT JOIN tranban f ON f.id = a.idtranban LEFT JOIN banco g ON g.id = f.idbanco LEFT JOIN moneda h ON h.id = g.idmoneda ";
//                 $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND g.idmoneda = $moneda->idmoneda AND a.idempresa = '$empresa->idempresa' ";
//                 $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "";
//                 $query.= (int)$d->numdel > 0 ? "AND a.numero >= $d->numdel " : "";
//                 $query.= (int)$d->numal > 0 ? "AND a.numero <= $d->numal " : "";
//                 $tottran = $db->getOneField($query);
//                 $empresa->recibos[] = [
//                     'idrecibo' => '', 'fecha' => '', 'serie' => '', 'numero' => '', 'cliente' => '', 'facturas' => 'Total de empresa', 'totrecibo' => $totempresa,
//                     'tranban' => '', 'monedadep' => '', 'banco' => '', 'montotran' => $tottran
//                 ];
//             }
//         }
//     }
//     print json_encode([ 'generales' => $generales, 'recibos' => $monedas]);
// });

$app->post('/correlativo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    // fechas
    $query = "SELECT DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del,  DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy ";
    $generales = $db->getQuery($query)[0];

    // traer empresas que tengan recibos
    $query = "SELECT DISTINCT
                a.idempresa, b.nomempresa AS empresa            
            FROM
                recibocli a
                    INNER JOIN
                empresa b ON b.id = a.idempresa
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' ";
    $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : '';
    $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';               
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
    $query.="ORDER BY b.id ASC ";
    $empresas = $db->getQuery($query);

    // conteo de empresas
    $cuantasEmpresas = count($empresas);

    for($i = 0; $i < $cuantasEmpresas; $i++)
    {

        // id de empresa 
        $empresa = $empresas[$i];

        // recibos por empresa
        $query = "SELECT DISTINCT
                    CONCAT(a.serie,
                            '-',
                            IF(a.anulado = 0,
                                IFNULL(IF(a.serie = 'A', b.seriea, b.serieb),
                                        a.id),
                                'ANULADO')) AS norecibo,
                    c.nomempresa AS empresa,
                    DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                    SUBSTRING(IFNULL(IFNULL(d.nombrecorto, e.nombre),
                            'Cientes varios'), 1, 25) AS cliente,
                    (SELECT 
                            CONCAT(d.simbolo, '.', FORMAT(SUM(b.monto), 2))
                        FROM
                            detcobroventa b
                                INNER JOIN
                            factura c ON b.idfactura = c.id
                                INNER JOIN
                            moneda d ON c.idmonedafact = d.id
                        WHERE
                            b.idrecibocli = a.id) AS totrecibo
                FROM
                    recibocli a
                        LEFT JOIN
                    serierecli b ON a.id = b.idrecibocli
                        INNER JOIN
                    empresa c ON c.id = a.idempresa
                        LEFT JOIN
                    cliente d ON d.id = a.idcliente
                        LEFT JOIN
                    factura e ON e.nit = a.nit 
                WHERE
                    a.fecha >= '$d->fdelstr'
                        AND a.fecha <= '$d->falstr' 
                        AND a.idempresa = $empresa->idempresa ";
        $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';               
        $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
        $query.= "ORDER BY a.serie ASC , IFNULL(IF(a.serie = 'A', b.seriea, b.serieb), 
            a.id) ASC ";   
        $empresa->recibos = $db->getQuery($query);
        
        // suma de monto recibos por empresa
        $query = "SELECT 
        (SELECT 
                IFNULL(CONCAT(d.simbolo, '.', FORMAT(SUM(c.total), 2)), 0.00)
            FROM
                recibocli a
                    INNER JOIN
                detcobroventa b ON b.idrecibocli = a.id
                    INNER JOIN
                factura c ON c.id = b.idfactura
                    INNER JOIN
                moneda d ON d.id = c.idmonedafact
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' ";
        $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
        $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';
        $query.=" AND a.idempresa = $empresa->idempresa
                    AND c.idmonedafact = 1) AS montoempresaQ,
        (SELECT 
                IFNULL(CONCAT(d.simbolo, '.', FORMAT(SUM(c.total), 2)), 0.00)
            FROM
                recibocli a
                    INNER JOIN
                detcobroventa b ON b.idrecibocli = a.id
                    INNER JOIN
                factura c ON c.id = b.idfactura
                    INNER JOIN
                moneda d ON d.id = c.idmonedafact
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' ";
        $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
        $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';
        $query.=" AND a.idempresa = $empresa->idempresa
                    AND c.idmonedafact = 2) AS montoempresa$ ";
        $empresa->totalemp = $db->getQuery($query);
    }

    // suma de monto de todas las empresas
    $query = "SELECT 
    (SELECT 
            IFNULL(CONCAT(d.simbolo, '.', FORMAT(SUM(c.total), 2)), 0.00)
        FROM
            recibocli a
                INNER JOIN
            detcobroventa b ON b.idrecibocli = a.id
                INNER JOIN
            factura c ON c.id = b.idfactura
                INNER JOIN
            moneda d ON d.id = c.idmonedafact
        WHERE
            a.fecha >= '$d->fdelstr'
                AND a.fecha <= '$d->falstr' ";
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
    $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : '';
    $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';
    $query.=" AND c.idmonedafact = 1) AS montoQ,
    (SELECT 
            IFNULL(CONCAT(d.simbolo, '.', FORMAT(SUM(c.total), 2)), 0.00)
        FROM
            recibocli a
                INNER JOIN
            detcobroventa b ON b.idrecibocli = a.id
                INNER JOIN
            factura c ON c.id = b.idfactura
                INNER JOIN
            moneda d ON d.id = c.idmonedafact
        WHERE
            a.fecha >= '$d->fdelstr'
                AND a.fecha <= '$d->falstr' ";
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
    $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : '';
    $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';
    $query.=" AND c.idmonedafact = 2) AS monto$ ";
    $totalgen = $db->getQuery($query)[0];

    print json_encode(['generales' => $generales, 'recempre' => $empresas, 'totalesgen' => $totalgen]);
});


$app->run();