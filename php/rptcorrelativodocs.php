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

    $mesdel = date("m", strtotime($d->fdelstr));
    $mesal = date("m", strtotime($d->falstr));
    $aniodel = ' '.date("Y", strtotime($d->fdelstr));
    $anioal = ' '.date("Y", strtotime($d->falstr));

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    if ($aniodel == $anioal) {
        $aniodel = '';
    }

    $letra = new stdClass();

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $letra->del = 'De ' .$meses[$mesdel-1].$aniodel;

    $mesal != $mesdel ? $letra->al = 'a '.$meses[$mesal-1].$anioal : $letra->al = $anioal;

    $ids = array();

    //Empresas
    $query = "SELECT DISTINCT a.id, a.nomempresa AS empresa FROM empresa a INNER JOIN banco b ON b.idempresa = a.id 
    INNER JOIN tranban c ON c.idbanco = b.id WHERE c.fecha >= '$d->fdelstr' AND fecha <= '$d->falstr'";
    $query.= $d->idempresa != 0 ? " AND a.id = $d->idempresa " : ' ';
    $query.= "ORDER BY a.nomempresa ";
    $empresas = $db->getQuery($query);

    $cntEmpresas = count($empresas);

    for ($i = 0; $i < $cntEmpresas; $i++) {
        $empresa = $empresas[$i]; 

        array_push($ids, $empresa->id);
    }

    $ids_str = implode(',', $ids);

    // Bancos
    $query = "SELECT a.id, CONCAT(a.nombre, ' (', a.nocuenta, ') ', b.simbolo) AS banco, a.idmoneda, b.simbolo, a.idempresa
    FROM banco a INNER JOIN moneda b ON a.idmoneda = b.id WHERE a.idempresa IN($ids_str) ORDER BY a.nombre";
    $bancos = $db->getQuery($query);

    $cntBancos = count($bancos);

    $ids = array();

    for ($i = 0; $i < $cntBancos; $i++) {
        $banco = $bancos[$i];

        array_push($ids, $banco->id);
    }

    $ids_str = implode(',', $ids);

    // Transacciones
    $query = "SELECT 
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                CONCAT(a.tipotrans, ' ', a.numero) AS documento,
                a.monto,
                SUBSTRING(a.beneficiario, 1, 25) AS beneficiario,
                IFNULL(c.conceptomayor, a.concepto) AS concepto,
                IFNULL(GROUP_CONCAT(DISTINCT ' ', d.nomproyecto), 
                            'N/A') AS proyecto,
                a.idbanco, 
                f.simbolo
            FROM
                tranban a
                    LEFT JOIN
                detpagocompra b ON b.idtranban = a.id
                    LEFT JOIN
                compra c ON b.idcompra = c.id
                    LEFT JOIN
                proyecto d ON c.idproyecto = d.id
                    INNER JOIN
                banco e ON a.idbanco = e.id
                    INNER JOIN 
                moneda f ON e.idmoneda = f.id
            WHERE
                a.idbanco IN($ids_str) AND a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr'
                    AND a.tipotrans IN('C', 'B')
            GROUP BY a.id
            ORDER BY a.fecha, a.numero";
    $trans = $db->getQuery($query);

    $cntTrans = count($trans);

    for ($i = 0; $i < $cntEmpresas; $i++) {
        $empresa = $empresas[$i];

        $banempresa = array();
        for ($j = 0; $j < $cntBancos; $j++) {
            $banco = $bancos[$j];

            $tranbanco = array();
            $totbanco = array();

            for ($k = 0; $k < $cntTrans; $k++) {
                $tran = $trans[$k];

                if ($tran->idbanco == $banco->id) {
                    array_push($tranbanco, $tran);
                    array_push($totbanco, $tran->monto);
                }
            }

            $banco->total = number_format(array_sum($totbanco), 2, ".", ",");
            $banco->trans = $tranbanco;

            if ($banco->idempresa == $empresa->id && count($banco->trans) > 0) {
                array_push($banempresa, $banco);
            }
        }
        $empresa->bancos = $banempresa;
    }

    print json_encode(['fechas' => $letra, 'debitos' => $empresas]);
});

$app->run();