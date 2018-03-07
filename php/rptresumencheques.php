<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptrescheques', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $datos = new stdclass();
    $query = "SELECT DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS fdel, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS fal, ";
	$query.= "CONCAT(nommoneda, ' (', simbolo, ')') AS simbolo, '0.00' AS total FROM moneda WHERE id = $d->idmoneda";
    $datos->generales = $db->getQuery($query)[0];

    $query = "SELECT DISTINCT b.idempresa, c.nomempresa AS empresa, c.abreviatura AS abreviaempre ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "INNER JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "WHERE a.tipotrans = 'C' AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND b.idmoneda = $d->idmoneda ";
    $query.= "ORDER BY ".((int)$d->orden == 1 ? "c.espersonal, b.ordensumario" : "c.nomempresa");
    $datos->empresas = $db->getQuery($query);
    $cntDatos = count($datos->empresas);

    for($i = 0; $i < $cntDatos; $i++){
        $data = $datos->empresas[$i];
        $query = "SELECT c.nomempresa AS empresa, c.abreviatura AS abreviaempre, a.numero, a.beneficiario, FORMAT(a.monto, 2) AS monto ";
        $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN empresa c ON c.id = b.idempresa ";
        $query.= "WHERE a.tipotrans = 'C' AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND b.idempresa = $data->idempresa AND b.idmoneda = $d->idmoneda ";
        $query.= "ORDER BY a.numero";
        $data->cheques = $db->getQuery($query);
        if(count($data->cheques) > 0){
            $query = "SELECT FORMAT(SUM(a.monto), 2) AS summonto ";
            $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN empresa c ON c.id = b.idempresa ";
            $query.= "WHERE a.tipotrans = 'C' AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND b.idempresa = $data->idempresa AND b.idmoneda = $d->idmoneda ";
            $data->cheques[] = ['empresa' => '', 'abreviaempre' => '', 'numero' => '', 'beneficiario' => 'SUBTOTAL', 'monto' => $db->getOneField($query)];
        }
    }

    $query = "SELECT FORMAT(SUM(a.monto), 2) ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "INNER JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "WHERE a.tipotrans = 'C' AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND b.idmoneda = $d->idmoneda ";
    $query.= "ORDER BY c.nomempresa";
    $datos->generales->total = $db->getOneField($query);

    print json_encode($datos);
});

$app->run();