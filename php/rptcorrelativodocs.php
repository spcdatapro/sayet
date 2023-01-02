<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/correlativo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $correlativo = new stdClass();

    //Datos del banco
    $query = "SELECT a.nombre, b.simbolo, a.nocuenta, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, c.nomempresa AS empresa ";
    $query.= "FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.id = $d->idbanco";
    $correlativo->banco = $db->getQuery($query)[0];

    //Documentos
    $query = "SELECT DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, CONCAT(a.tipotrans, a.numero) AS documento, ";
    $query.= "IF(b.suma = 1, IF(a.anulado = 0, FORMAT(a.monto, 2), NULL), NULL) AS credito, ";
    $query.= "IF(b.suma = 0, IF(a.anulado = 0, FORMAT(a.monto, 2), NULL), NULL) AS debito, ";
    $query.= "a.beneficiario, a.concepto ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";
    $query.= $d->tipo != '' ? "AND a.tipotrans = '$d->tipo' " : '';
    $query.= $d->beneficiario != '' ? "AND a.beneficiario LIKE '%$d->beneficiario%' " : '';
    $query.= "ORDER BY a.fecha, a.numero";
    $correlativo->docs = $db->getQuery($query);

    //Sumatorias
    $query = "SELECT FORMAT(SUM(IF(b.suma = 1, a.monto, 0.00)), 2) AS credito, FORMAT(SUM(IF(b.suma = 0, a.monto, 0.00)), 2) AS debito ";
    $query.= "FROM tranban a INNER JOIN tipomovtranban b ON b.abreviatura = a.tipotrans ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";
    $query.= $d->tipo != '' ? "AND a.tipotrans = '$d->tipo' " : '';
    $query.= $d->beneficiario != '' ? "AND a.beneficiario LIKE '%$d->beneficiario%' " : '';
    $correlativo->sumas = $db->getQuery($query)[0];

    print json_encode($correlativo);
});

$app->post('/correlativoger', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al";
    $general = $db->getQuery($query)[0];

    //Empresas
    $query = "SELECT DISTINCT a.id, a.nomempresa AS empresa, NULL AS bancos FROM empresa a 
    INNER JOIN banco b ON b.idempresa = a.id INNER JOIN tranban c ON c.idbanco = b.id WHERE c.fecha >= '$d->fdelstr' AND fecha <= '$d->falstr' ";
    $query.= $d->idempresa != 0 ? "AND a.id = $d->idempresa " : '';
    $query.= "ORDER BY a.nomempresa ";
    $empresas = $db->getQuery($query);

    $cntEmpresas = count($empresas);

    for ($i = 0; $i < $cntEmpresas; $i++) {
        $empresa = $empresas[$i]; 
        // Bancos
        $query = "SELECT a.id, CONCAT(a.nombre, ' (', a.nocuenta, ') ', b.simbolo) AS banco, a.idmoneda, b.simbolo, NULL AS trans, NULL AS total, NULL AS mostrar
        FROM banco a INNER JOIN moneda b ON a.idmoneda = b.id WHERE a.idempresa = $empresa->id ORDER BY a.nombre ";
        $empresa->bancos = $db->getQuery($query);

        $cntBancos = count($empresa->bancos);

        for ($j = 0; $j < $cntBancos; $j++){
            // Banco
            $banco = $empresa->bancos[$j];
            
            // Array de montos
            $sbanco = array();

            // Transacciones
            $query = "SELECT 
                        DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                        CONCAT(a.tipotrans, ' ', a.numero) AS documento,
                        a.monto,
                        SUBSTRING(a.beneficiario, 1, 25) AS beneficiario,
                        IFNULL(c.conceptomayor, a.concepto) AS concepto,
                        IFNULL(SUBSTRING(d.nomproyecto, 1, 25), 'N/A') AS proyecto,
                        NULL AS simbolo
                    FROM
                        tranban a
                            LEFT JOIN
                        detpagocompra b ON b.idtranban = a.id
                            LEFT JOIN
                        compra c ON b.idcompra = c.id
                            LEFT JOIN
                        proyecto d ON c.idproyecto = d.id
                    WHERE
                        a.idbanco = $banco->id AND a.fecha >= '$d->fdelstr'
                            AND a.fecha <= '$d->falstr'
                            AND a.tipotrans IN('C', 'B')
                    ORDER BY a.fecha, a.numero ";
            $banco->trans = $db->getQuery($query);
            
            $cntTrans = count($banco->trans);

            if ($cntTrans > 0) {
                $banco->mostrar = true;
            }

            for ($k = 0; $k < $cntTrans; $k++) {
                $tran = $banco->trans[$k];
                if ($banco->idmoneda == 1) {
                    $tran->simbolo = 'Q.';
                } else {
                    $tran->simbolo = '$.';
                }
                array_push($sbanco, $tran->monto);
            }
            $tbanco = array_sum($sbanco);
            $banco->total = number_format($tbanco, 2, '.', ',');
        }
    }

    //Sumatorias

    print json_encode(['general' => $general, 'debitos' => $empresas]);
});

$app->run();