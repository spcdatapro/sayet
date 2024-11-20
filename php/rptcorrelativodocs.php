<?php
require 'vendor/autoload.php';
require_once 'db.php';
require 'Reportes.php';

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

    $primero = true;
    $idempresa;
    $idbanco;
    $sumas_empresa = new StdClass;
    $sumas_banco = new StdClass;
    $sumas_general = new StdClass;
    $tipo = 'transacciones';
    $data_ordenada = [];


    $montos = ['total'];

    $mesdel = date("m", strtotime($d->fdelstr));
    $mesal = date("m", strtotime($d->falstr));
    $aniodel = ' '.date("Y", strtotime($d->fdelstr));
    $anioal = ' '.date("Y", strtotime($d->falstr));
    $diadel = date("d", strtotime($d->fdelstr));
    $diaal = date("d", strtotime($d->falstr));

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    if ($aniodel == $anioal) {
        $aniodel = '';
    }

    $letra = new stdClass();

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $letra->porproyecto = $d->porproyecto ? true : null;

    $letra->del = $mesal != $mesdel ? 'Del '.$diadel.' de '.$meses[$mesdel-1].$aniodel : 'Del '.$diadel.$aniodel;

    $letra->al = 'al '.$diaal.' de '.$meses[$mesal-1].$anioal;

    $query = "SELECT 
                a.id,
                c.id AS idempresa,
                b.id AS idbanco,
                IFNULL(IFNULL(f.id, i.id), '9999') AS idproyecto,
                c.nomempresa AS empresa,
                CONCAT(b.nombre, ' (', b.nocuenta, ') ', j.simbolo) AS banco,
                IFNULL(IFNULL(f.nomproyecto, i.nomproyecto),
                        'SIN PROYECTO') AS proyecto,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                a.tipotrans,
                a.numero,
                a.beneficiario,
                a.monto AS total,
                a.concepto,
                j.simbolo AS moneda,
                IF(a.tipocambio > 1, a.tipocambio, null) AS tc,
                null AS abreviatura
            FROM
                tranban a
                    INNER JOIN
                banco b ON a.idbanco = b.id
                    INNER JOIN
                empresa c ON b.idempresa = c.id
                    LEFT JOIN
                (SELECT 
                    a.idtranban, b.idproyecto
                FROM
                    detpagocompra a
                INNER JOIN compra b ON a.idcompra = b.id
                GROUP BY a.idtranban) d ON d.idtranban = a.id
                    LEFT JOIN
                proyecto f ON d.idproyecto = f.id
                    LEFT JOIN
                dettranreem g ON g.idtranban = a.id
                    LEFT JOIN
                reembolso h ON g.idreembolso = h.id
                    LEFT JOIN
                proyecto i ON h.idproyecto = i.id
                    INNER JOIN 
                moneda j ON b.idmoneda = j.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr'
                    AND a.tipotrans IN ('C' , 'B') ";
    $query.= isset($d->idempresa) ? "AND b.idempresa = $d->idempresa " : "";
    $query.=    "ORDER BY 5 , ";
    $query.= $d->porproyecto ? "7 , 8 , 10" : "6 , 8 , 10";
    // echo $query; return;
    $data = $db->getQuery($query); 

    if ($d->porproyecto) {
        $reporte = new GeneradorReportes($data, 'transacciones', ['total'], $d->porproyecto);

        $transacciones = $reporte->getReporte();
        $montos_generales = $reporte->getTotalesGenerales();

        foreach($montos as $t) {
            $letra->$t = round(array_sum($montos_generales->$t), 2);
        }

    } else {
        $cntsTrans = count($data);
        if($cntsTrans > 0) {
            foreach($montos as $monto) {
                $sumas_general->$monto = [];
            }
    
            for ($i = 0; $i < $cntsTrans; $i++) {
                // traer dato
                $dat = $data[$i]; 

                // validar si es la primera vuelta para generar vairables o si se esta haciendo cambio de empresa y/o proyecto
                if($primero || $dat->idempresa != $idempresa) {
                    // si no es primera vuelta realizar sumas y empujar datos a array padre
                    if(!$primero) {
                        // solo se hara si el proyecto continuara siendo el mismo
                        if($dat->idbanco == $idbanco) {
                            // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
                        }

                        foreach($montos as $monto) {
                            $separador_banco->$monto = round(array_sum($sumas_banco->$monto), 2);
                        }

                        // empujar a array padre, proyecto es a empresa
                        array_push($separador_empresa->bancos, $separador_banco);

                        // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
                        foreach($montos as $monto) {
                            $separador_empresa->$monto = round(array_sum($sumas_empresa->$monto), 2);
                        }

                        // empujar a array padre, empresa es a global
                        array_push($data_ordenada, $separador_empresa);
                    }

                    // crear separador de empresa
                    $separador_empresa = new StdClass;
                    $separador_empresa->nombre = $dat->empresa;
                    $separador_empresa->numero = $dat->numero > 0 ? $dat->numero : null;
                    $separador_empresa->abreviatura = $dat->abreviatura;

                    // crear sumadores empresa 
                    foreach($montos as $monto) {
                        $sumas_empresa->total = [];
                    }

                    // si se agrupa por proyecto crear array para insertar proyecto, de lo contrario array para insertar los datos directamente

                        $separador_empresa->bancos = array();

                        // para que genere variables de proyecto
                        $primero = true;

                    // para poder hacer el reseteo de variables al cambiar de empresa
                    $idempresa = $dat->idempresa;
                }

                if(($primero || $dat->idbanco != $idbanco)) {
                    // si no es primera vuelta realizar sumas y empujar datos a array padre
                    if(!$primero) {
                        // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
                        foreach($montos as $monto) {
                            $separador_banco->$monto = round(array_sum($sumas_banco->$monto), 2);
                        }

                        // empujar a array padre, proyecto es a empresa
                        array_push($separador_empresa->bancos, $separador_banco);
                    }

                    // crear separador de proyecto
                    $separador_banco = new StdClass;
                    $separador_banco->nombre = $dat->banco;
                    $separador_banco->id = $dat->idbanco;
                    $separador_banco->moneda = $dat->moneda;
                    $separador_banco->$tipo = array();

                    // crear sumadores de proyecto
                    foreach($montos as $monto) {
                        $sumas_banco->$monto = [];
                    }

                    // terminar primera vuelta 
                    $primero = false;

                    // para poder hacer el reseteo de variables al cambiar de proyecto
                    $idbanco = $dat->idbanco;
                }

                // empujar datos una vez generada las variables y validado estar en la mimsa empresa o proyecto
                    array_push($separador_banco->$tipo, $dat);

                // empujar montos para sumas
                    // proyecto
                    foreach($montos as $monto) {
                        array_push($sumas_banco->$monto, $dat->$monto);
                    }

                // empresa
                foreach($montos as $monto) {
                    array_push($sumas_empresa->$monto, $dat->$monto);
                }

                // general
                foreach($montos as $monto) {
                    array_push($sumas_general->$monto, $dat->$monto);
                }
            }
            if (isset($d->idempresa)) {
                array_push($data_ordenada, $separador_empresa);
            }
        } else {
            return 'Sin datos';
        }

        $transacciones = $data_ordenada;
    }

    print json_encode(['fechas' => $letra, 'debitos' => $transacciones]);
});

$app->run();