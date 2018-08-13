<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
$db = new dbcpm();

$app->get('/pagoboleto/:anio(/:idempresa)', function($anio, $idempresa = 0) use($db){
    $query = "INSERT INTO plnpagoboletoornato(periodo, idplnempleado) ";
    $query.= "SELECT $anio, a.id FROM plnempleado a WHERE a.baja IS NULL AND a.id NOT IN(SELECT idplnempleado FROM plnpagoboletoornato WHERE periodo = $anio)";
    $db->doQuery($query);

    $query = "SELECT a.id, c.nomempresa AS empresadebito, d.nombre AS empresaactual, TRIM(CONCAT(IFNULL(TRIM(b.nombre), ''), ' ',IFNULL(TRIM(b.apellidos), ''))) AS nombre, a.pagado ";
    $query.= "FROM plnpagoboletoornato a INNER JOIN plnempleado b ON b.id = a.idplnempleado INNER JOIN empresa c ON c.id = b.idempresadebito INNER JOIN plnempresa d ON d.id = b.idempresaactual ";
    $query.= "WHERE a.periodo = $anio ";
    $query.= (int)$idempresa > 0 ? "AND b.idempresaactual = $idempresa " : "";
    $query.= "ORDER BY 3, 4";

    print $db->doSelectASJson($query);
});

$app->post('/u', function() use($db){
    $d = json_decode(file_get_contents('php://input'));

    if(!isset($d->pagado)){ $d->pagado = 0; }
    $query = "UPDATE plnpagoboletoornato SET pagado = $d->pagado WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/rptpago', function() use($db){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, $d->anio AS periodo";
    $generales = $db->getQuery($query)[0];

    $qGen = "SELECT LPAD(a.id, 3, '0') AS codigoempleado, a.idempresadebito, c.nomempresa AS empresadebito, a.idempresaactual, d.nombre AS empresaactual, ";
    $qGen.= "TRIM(CONCAT(IFNULL(TRIM(a.nombre), ''), ' ',IFNULL(TRIM(a.apellidos), ''))) AS nombre, a.sueldo, a.bonificacionley, (a.sueldo + a.bonificacionley) AS total, ";
    $qGen.= "(SELECT monto FROM boletoornato WHERE (a.sueldo + a.bonificacionley) >= rangode AND (a.sueldo + a.bonificacionley) <= rangoa) AS boleto, IF(b.pagado = 1, 'P', 'No P') AS pagado ";
    $qGen.= "FROM plnempleado a LEFT JOIN plnpagoboletoornato b ON a.id = b.idplnempleado LEFT JOIN empresa c ON c.id = a.idempresadebito LEFT JOIN plnempresa d ON d.id = a.idempresaactual ";
    $qGen.= "WHERE a.id IN(SELECT idplnempleado FROM plnnomina) AND (b.periodo = $d->anio OR b.periodo IS NULL) ";
    $qGen.= (int)$d->idempresa > 0 ? "AND a.idempresaactual = $d->idempresa " : "";
    $qGen.= "ORDER BY 5, 6";

    $query = "SELECT DISTINCT z.idempresaactual, z.empresaactual FROM($qGen) z ORDER BY z.empresaactual";
    $empresas = $db->getQuery($query);
    $cntEmpresas = count($empresas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $empresas[$i];
        $query = "SELECT z.codigoempleado, z.nombre, FORMAT(z.sueldo, 2) AS sueldo, FORMAT(z.bonificacionley, 2) AS bonificacion, FORMAT(z.total, 2) AS total, FORMAT(z.boleto, 2) AS boleto, z.pagado ";
        $query.= "FROM ($qGen) z ";
        $query.= "WHERE z.idempresaactual = $empresa->idempresaactual ";
        $query.= "ORDER BY z.nombre";
        //print $query;
        $empresa->boletos = $db->getQuery($query);
        if(count($empresa->boletos) > 0){
            $query = "SELECT FORMAT(SUM(z.sueldo), 2) AS sueldo, FORMAT(SUM(z.bonificacionley), 2) AS bonificacion, FORMAT(SUM(z.total), 2) AS total, FORMAT(SUM(z.boleto), 2) AS boleto ";
            $query.= "FROM ($qGen) z WHERE z.idempresaactual = $empresa->idempresaactual";
            $sumas = $db->getQuery($query)[0];
            $empresa->boletos[] = [
                'codigoempleado' => '', 'nombre' => 'Total empresa:', 'sueldo' => $sumas->sueldo, 'bonificacion' => $sumas->bonificacion,
                'total' => $sumas->total, 'boleto' => $sumas->boleto, 'pagado' => ''
            ];
        }
    }

    print json_encode(['generales' => $generales, 'empresas' => $empresas]);

});


$app->run();