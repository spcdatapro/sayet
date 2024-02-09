<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/mensual', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    // convertir array a str separado por comas si existe mas de una empresa
    $ids_str = count($d->idempresa) > 0 ? implode(',', $d->idempresa) : "''";
    // variables iniciales
    $primero = true;
    $separador = new StdClass;
    $totales = new StdClass;
    $recibos = array();
    $montos_dia_gtq = array();
    $montos_dia_dlr = array();
    $mesdel = date("m", strtotime($d->fdelstr));
    $mesal = date("m", strtotime($d->falstr));
    $aniodel = ' '.date("Y", strtotime($d->fdelstr));
    $anioal = ' '.date("Y", strtotime($d->falstr));
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    // validar si solo estan obteniendo un anio
    if ($aniodel == $anioal) {
        $aniodel = '';
    }

    // clase para fechas
    $letra = new stdClass();

    $letra->del = $meses[$mesdel-1].$aniodel;

    // validar si solo estan obteniendo un mes
    if ($mesal != $mesdel) {
        $letra->al = 'a '.$meses[$mesal-1].$anioal;
    } else {
        $letra->al = $anioal;
    }

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y');

    $letra->empresas = $db->getOneField("SELECT GROUP_CONCAT(nomempresa SEPARATOR ', ') FROM empresa WHERE id IN($ids_str)");

    $query = "SELECT 
                a.id,
                IF(a.anulado = 0,
                    CONCAT(a.serie, '-', IFNULL(b.seriea, b.serieb)),
                    'ANULADO') AS recibo,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                IFNULL(IFNULL(c.nombre, d.nombre),
                        'Clientes Varios') AS cliente,
                IF(e.idmoneda = 1 AND a.anulado = 0,
                    ROUND(SUM(e.monto), 2),
                    NULL) AS montogtq,
                IF(e.idmoneda = 2 AND a.anulado = 0,
                    ROUND(SUM(e.monto), 2),
                    NULL) AS montodlr,
                f.simbolo AS moneda,
                a.fecha AS fecharec,
                g.idproyecto,
                IFNULL(h.nomproyecto, 'SIN PROYECTO') AS proyecto,
                MONTH(a.fecha) AS idmes,
                DAY(a.fecha) AS dia
            FROM
                recibocli a
                    INNER JOIN
                serierecli b ON b.idrecibocli = a.id
                    LEFT JOIN
                cliente c ON a.idcliente = c.id
                    LEFT JOIN
                (SELECT 
                    d.nit, d.nombre
                FROM
                    factura d
                LIMIT 1) d ON d.nit = a.nit
                    LEFT JOIN
                detpagorecli e ON e.idreccli = a.id
                    LEFT JOIN
                moneda f ON e.idmoneda = f.id
                    LEFT JOIN
                (SELECT 
                    e.idrecibocli AS idrecibo,
                        IF(g.idproyecto = 0, d.idproyecto, g.idproyecto) AS idproyecto
                FROM
                    detcobroventa e
                INNER JOIN factura g ON e.idfactura = g.id
                INNER JOIN contrato d ON g.idcontrato = d.id LIMIT 1) g ON g.idrecibo = a.id
                    LEFT JOIN
                proyecto h ON g.idproyecto = h.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' ";
    $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : '';
    $query.= "GROUP BY a.id ";
    $query.= $d->tipo == 1 ? "ORDER BY a.fecha ASC, " : "ORDER BY h.nomproyecto ASC, ";
    $query.= "a.serie ASC, b.seriea ASC, b.serieb ASC";
    $data = $db->getQuery($query);

    foreach($data AS $rec) {
        $rec->nombre = $rec->dia.' de '.$meses[$rec->idmes-1];
    }

    $cntsRecibos = count($data);

    for ($i = 1; $i < $cntsRecibos; $i++) {
        // traer valor actual y anterior
        $actual = $data[$i];
        $anterior = $data[$i-1];
        // separar por
        $por_ant = $d->tipo == 1 ? $anterior->nombre : $anterior->proyecto;
        $por_act = $d->tipo == 1 ? $actual->nombre : $actual->proyecto;
        // si es el primero insertar nombre del separador y crear array de recibos
        if ($primero) {
            $separador->nombre = $por_ant;
            $separador->recibos = array();
            $primero = false;
        }
        // si tienen el mismo proyecto empujar montos anteriores
        array_push($montos_dia_gtq, $anterior->montogtq);
        array_push($montos_dia_dlr, $anterior->montodlr);
        array_push($separador->recibos, $anterior);
        // si no tienen el mismo proyecto 
        if ($por_ant != $por_act) {
            // generar variable de totales
            $totales->quetzales = round(array_sum($montos_dia_gtq), 2);
            $totales->dolares = round(array_sum($montos_dia_dlr), 2);
            $separador->totales = $totales;
            // empujar a array global de recibo los recibos separados
            array_push($recibos, $separador);
            // limpiar variables 
            $totales = new StdClass;
            $montos_dia_gtq = array();
            $montos_dia_dlr = array();
            $separador = new StdClass;
            $separador->nombre = $por_act;
            $separador->recibos = array();
        }
        // para empujar el ultimo dato
        if ($i+1 == $cntsRecibos) {
            array_push($montos_dia_gtq, $actual->montogtq);
            array_push($montos_dia_dlr, $actual->montodlr);
            array_push($separador->recibos, $actual);
            $totales->quetzales = round(array_sum($montos_dia_gtq), 2);
            $totales->dolares = round(array_sum($montos_dia_dlr), 2);
            $separador->totales = $totales;
            array_push($recibos, $separador);
        }
    }

    print json_encode(['fechas' => $letra, 'recibos' => $recibos]);

});

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
                    LEFT JOIN 
				detcobroventa c ON a.id = c.idrecibocli
					LEFT JOIN 
				factura d ON d.id = c.idfactura
                    LEFT JOIN
                contrato e ON d.idcontrato = e.id
                    LEFT JOIN
                proyecto f ON e.idproyecto = f.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' AND a.tipo = 1 ";
    $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : '';
    $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';               
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
    $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : '';
    $query.= $d->idproyecto != 0 ? "AND f.id = $d->idproyecto ": '';
    $query.="ORDER BY b.id ASC ";
    $empresas = $db->getQuery($query);

    // conteo de empresas
    $cuantasEmpresas = count($empresas);

    for($i = 0; $i < $cuantasEmpresas; $i++)
    {

        // id de empresa 
        $empresa = $empresas[$i];

        // proyecto por empresa
        $query = "SELECT DISTINCT
                    e.id AS idproyecto, e.nomproyecto AS proyecto
                FROM
                    recibocli a
                        INNER JOIN
                    detcobroventa b ON b.idrecibocli = a.id
                        INNER JOIN
                    factura c ON b.idfactura = c.id
                        INNER JOIN
                    contrato d ON c.idcontrato = d.id
                        INNER JOIN
                    proyecto e ON d.idproyecto = e.id
                WHERE
                    a.fecha >= '$d->fdelstr'
                        AND a.fecha <= '$d->falstr' 
                        AND a.idempresa = $empresa->idempresa AND a.tipo = 1 ";
        $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';               
        $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
        $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : '';
        $query.= $d->idproyecto != 0 ? "AND e.id = $d->idproyecto ": '';
        $query.= "ORDER BY e.id ASC ";
        $empresa->proyectos = $db->getQuery($query);

        // conteo proyectos
        $cuantosProyectos = count($empresa->proyectos);

        for($j = 0; $j < $cuantosProyectos; $j++)
        {
            
            // id de proyecto
            $proyecto = $empresa->proyectos[$j];
            
            // recibos por proyecto
            $query = "SELECT DISTINCT
                        CONCAT(a.serie,
                                '-',
                                IF(a.anulado = 0,
                                    IF(a.serie = 'A', b.seriea, b.serieb),
                                    IF(a.serie = 'A',
                                        CONCAT(b.seriea, ' (ANULADO)'),
                                        CONCAT(b.serieb, ' (ANULADO)')))) AS norecibo,
                        c.nomempresa AS empresa,
                        DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                        IFNULL(IFNULL(d.nombre, e.nombre),
                                'Clientes Varios') AS cliente,
                        (SELECT 
                                CONCAT('Q.', FORMAT(SUM(b.monto), 2))
                            FROM
                                detcobroventa b
                                    INNER JOIN
                                detpagorecli c ON b.idrecibocli = c.idreccli
                                    INNER JOIN
                                factura d ON b.idfactura = d.id
                                    INNER JOIN
                                contrato e ON d.idcontrato = e.id
                            WHERE
                                b.idrecibocli = a.id AND c.idmoneda = 1
                                    AND e.idproyecto = $proyecto->idproyecto
                                    AND a.idempresa = $empresa->idempresa) AS totreciboqtz,
                        (SELECT 
                                CONCAT('$.', FORMAT(c.monto, 2))
                            FROM
                                detcobroventa b
                                    INNER JOIN
                                detpagorecli c ON c.idreccli = b.idrecibocli
                                    INNER JOIN
                                factura d ON b.idfactura = d.id
                                    INNER JOIN
                                contrato e ON d.idcontrato = e.id
                            WHERE
                                b.idrecibocli = a.id AND c.idmoneda = 2
                                    AND e.idproyecto = $proyecto->idproyecto
                                    AND a.idempresa = $empresa->idempresa LIMIT 1) AS totrecibodlr,
                        i.nomproyecto AS proyecto
                    FROM
                        recibocli a
                            INNER JOIN
                        serierecli b ON b.idrecibocli = a.id
                            INNER JOIN
                        empresa c ON a.idempresa = c.id
                            LEFT JOIN
                        cliente d ON a.idcliente = d.id
                            LEFT JOIN
                        factura e ON e.nit = a.nit AND a.nit != 'CF'
                            INNER JOIN
                        detcobroventa f ON f.idrecibocli = a.id
                            INNER JOIN
                        factura g ON f.idfactura = g.id
                            INNER JOIN
                        contrato h ON g.idcontrato = h.id
                            INNER JOIN
                        proyecto i ON h.idproyecto = i.id
                    WHERE
                        a.tipo = 1 AND a.fecha >= '$d->fdelstr'
                            AND a.fecha <= '$d->falstr'
                            AND h.idproyecto = $proyecto->idproyecto 
                            AND a.idempresa = $empresa->idempresa ";
            $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';               
            $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
            $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : '';
            $query.= $d->idproyecto != 0 ? "AND h.idproyecto = $d->idproyecto ": '';
            $query.= "ORDER BY a.serie ASC , IFNULL(IF(a.serie = 'A', b.seriea, b.serieb), 
            a.id) ASC ";   
            $proyecto->recibos = $db->getQuery($query);

            // id de recibos por proyecto
            $query = "SELECT DISTINCT
                        GROUP_CONCAT(a.id) AS recibos
                    FROM
                        recibocli a
                            INNER JOIN
                        detcobroventa b ON b.idrecibocli = a.id
                            INNER JOIN
                        factura c ON c.id = b.idfactura
                            INNER JOIN
                        contrato d ON c.idcontrato = d.id
                    WHERE
                        a.tipo = 1 AND a.fecha >= '$d->fdelstr'
                            AND a.fecha <= '$d->falstr'
                            AND d.idproyecto = $proyecto->idproyecto ";
            $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : ''; 
            $query.= $d->idproyecto != 0 ? "AND d.idproyecto = $d->idproyecto ": '';
            $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
            $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : '';
            $idrecpry = $db->getQuery($query)[0];

            // suma de monto recibos por proyecto
            $query = "SELECT 
                        (SELECT 
                                CONCAT('Q.', IFNULL(FORMAT(SUM(a.monto), 2), 0.00))
                            FROM
                                detcobroventa a
                                    INNER JOIN
                                detpagorecli b ON a.idrecibocli = b.idreccli
                                    INNER JOIN
                                factura c ON a.idfactura = c.id
                                    INNER JOIN
                                contrato d ON c.idcontrato = d.id
                            WHERE
                                a.idrecibocli IN($idrecpry->recibos)
                                    AND b.idmoneda = 1
                                    AND d.idproyecto = $proyecto->idproyecto
                                    AND d.idempresa = $empresa->idempresa) AS montopryqtz,
                        (SELECT 
                                IFNULL(CONCAT('$.', FORMAT(SUM(a.monto), 2)),
                                            CONCAT('$', 0.00))
                            FROM
                                detpagorecli a
                                    INNER JOIN 
                                recibocli b ON b.id = a.idreccli
                            WHERE
                                a.idreccli IN($idrecpry->recibos)
                                    AND a.idmoneda = 2
                                    AND b.idempresa = $empresa->idempresa) AS montoprydls ";
            $proyecto->totalproy = $db->getQuery($query);
        }

        // id de recibos por empresa
        $query = "SELECT DISTINCT
                    GROUP_CONCAT(a.id) AS recibos
                FROM
                    recibocli a
                WHERE
                    a.tipo = 1 AND a.fecha >= '$d->fdelstr'
                        AND a.fecha <= '$d->falstr'
                        AND a.idempresa = $empresa->idempresa ";
        $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : ''; 
        $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : '';
        $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : '';
        $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
        $idrecemp = $db->getQuery($query)[0];
        
        if ($idrecemp->recibos == NULL) {
            $idrecemp->recibos = 0;
        }

        // suma de monto recibos por empresa
        $query = "SELECT 
        (SELECT 
                IFNULL(CONCAT('Q', FORMAT(SUM(a.monto), 2)),
                            CONCAT('Q', 0.00))
            FROM
                detcobroventa a
                    INNER JOIN
                detpagorecli b ON b.idreccli = a.idrecibocli
            WHERE
                a.idrecibocli IN ($idrecemp->recibos) AND b.idmoneda = 1) AS montoempqtz,
        (SELECT 
                IFNULL(CONCAT('$', FORMAT(SUM(a.monto), 2)),
                            CONCAT('$', 0.00))
            FROM
                detpagorecli a
            WHERE
                a.idreccli IN ($idrecemp->recibos) AND a.idmoneda = 2) AS montoempdls ";
        $empresa->totalemp = $db->getQuery($query);
    }

    // idempresas
    $query = "SELECT DISTINCT
                GROUP_CONCAT(a.idempresa) AS idempresa            
            FROM
                recibocli a
                    INNER JOIN
                empresa b ON b.id = a.idempresa
                    LEFT JOIN 
                detcobroventa c ON a.id = c.idrecibocli
                    LEFT JOIN 
                factura d ON d.id = c.idfactura
                    LEFT JOIN
                contrato e ON d.idcontrato = e.id
                    LEFT JOIN
                proyecto f ON e.idproyecto = f.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' ";
            $query.= $d->idempresa != 0 ? "AND a.idempresa = $d->idempresa " : '';
            $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';               
            $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
            $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : '';
            $query.= $d->idproyecto != 0 ? "AND f.id = $d->idproyecto ": '';
            $query.="ORDER BY b.id ASC ";
            $grparray = $db->getQuery($query)[0];


    // idrecibo de todas las empresas
    $query = "SELECT DISTINCT
                GROUP_CONCAT(a.id) AS recibos
            FROM
                recibocli a
                    LEFT JOIN
                serierecli b ON a.id = b.idrecibocli
                    INNER JOIN
                empresa c ON c.id = a.idempresa
                    LEFT JOIN
                detcobroventa f ON f.idrecibocli = a.id
                    LEFT JOIN
                factura g ON g.id = f.idfactura
                    LEFT JOIN
                contrato h ON g.idcontrato = h.id
                    LEFT JOIN
                proyecto i ON h.idproyecto = i.id
            WHERE
                a.tipo = 1 AND a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr'
                    AND a.idempresa IN($grparray->idempresa)
                    AND a.anulado = 0
            ORDER BY a.id DESC ";
    $idrectod = $db->getQuery($query)[0];

    // suma de monto de todas las empresas
    $query = "SELECT 
    (SELECT 
            IFNULL(CONCAT('Q', FORMAT(SUM(a.monto), 2)),
                        CONCAT('Q', 0.00))
        FROM
            detcobroventa a
                INNER JOIN
            detpagorecli b ON b.idreccli = a.idrecibocli
        WHERE
            a.idrecibocli IN ($idrectod->recibos)
                AND b.idmoneda = 1) AS totgenqtz,
    (SELECT 
            IFNULL(CONCAT(b.simbolo, FORMAT(SUM(a.monto), 2)),
                        CONCAT('$', 0.00))
        FROM
            detpagorecli a
                INNER JOIN
            moneda b ON b.id = a.idmoneda
        WHERE
            a.idreccli IN ($idrectod->recibos)
                AND a.idmoneda = 2) AS totgendlr ";
    $totalgen = $db->getQuery($query)[0];

    print json_encode(['generales' => $generales, 'empresas' => $empresas, 'totalesgen' => $totalgen]);
});

$app->run();