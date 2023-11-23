<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/mensual', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $ids_str = count($d->idempresa) > 0 ? implode(',', $d->idempresa) : "''";

    if(!isset($d->idempresa)) { $d->idempresa = 0; }
    $query = "SELECT DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del,  DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy ";
    $fechas = $db->getQuery($query)[0];

    $query = "SELECT DISTINCT
                a.id,
                CONCAT(a.serie,
                        '-',
                        IF(a.anulado = 0,
                            IF(a.serie = 'A', b.seriea, b.serieb),
                            IF(a.serie = 'A',
                                CONCAT(b.seriea, '(ANULADO)'),
                                CONCAT(b.serieb, '(ANULADO)')))) AS recibo,
                DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                IFNULL(IFNULL(c.nombre, e.nombre),
                        'Clientes Varios') AS cliente,
                (SELECT 
                        CONCAT('Q', FORMAT(SUM(b.monto), 2))
                    FROM
                        detpagorecli b
                    WHERE
                        b.idreccli = a.id AND b.idmoneda = 1) AS montoqtz,
                (SELECT 
                        CONCAT('$', FORMAT(SUM(b.monto), 2))
                    FROM
                        detpagorecli b
                    WHERE
                        b.idreccli = a.id AND b.idmoneda = 2) AS montodlr,
                NULL AS total, a.fecha AS fecharec
            FROM
                recibocli a
                    INNER JOIN
                serierecli b ON b.idrecibocli = a.id
                    LEFT JOIN
                cliente c ON a.idcliente = c.id
                    LEFT JOIN 
				detcobroventa d ON d.idrecibocli = a.id
                    LEFT JOIN
                factura e ON d.idfactura = e.id
            WHERE
                a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr' ";
    $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : '';
    $query.= "ORDER BY a.fecha ASC, a.serie ASC, b.seriea ASC, b.serieb ASC ";
    $recibos = $db->getQuery($query);

    $cntRecibos = count($recibos); 

    for ($i = 0; $i < $cntRecibos; $i++) {
        $fecharec = $recibos[$i]->fecharec;
        if ($i + 1 < $cntRecibos) {
            $fecahcomp = $recibos[$i + 1]->fecharec;
        } else {
            $fecahcomp = 0;
        }

        if($fecharec != $fecahcomp) {
            $query = "SELECT 
            (SELECT 
                    CONCAT('Q',
                                '.',
                                IFNULL(FORMAT(SUM(b.monto), 2), 0.00))
                FROM
                    recibocli a
                        INNER JOIN
                    detpagorecli b ON b.idreccli = a.id
                WHERE
                    a.fecha = '$fecharec' AND b.idmoneda = 1 "; 
            $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : '';
            $query.= ") AS montoqtz,
            (SELECT 
                    CONCAT('$',
                                '.',
                                IFNULL(FORMAT(SUM(b.monto), 2), 0.00))
                FROM
                    recibocli a
                        INNER JOIN
                    detpagorecli b ON b.idreccli = a.id
                WHERE
                    a.fecha = '$fecharec' AND b.idmoneda = 2 ";
            $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : '';
            $query.= ") AS montodlr "; 
            $total = $db->getQuery($query);

            $recibos[$i]->total = $total;
            
        } 
    }

    $query = "SELECT 
                (SELECT GROUP_CONCAT(nomempresa SEPARATOR ', ')
                    FROM
                        empresa 
                    WHERE 
                        id IN($ids_str)) AS empresa,
                (SELECT 
                        CONCAT('Q',
                                    '.',
                                    IFNULL(FORMAT(SUM(b.monto), 2), 0.00))
                    FROM
                        recibocli a
                            INNER JOIN
                        detpagorecli b ON b.idreccli = a.id
                    WHERE
                        a.fecha >= '$d->fdelstr'
                            AND a.fecha <= '$d->falstr' ";
    $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : '';
    $query.= "                 AND b.idmoneda = 1) AS montoqtz,
                (SELECT 
                        CONCAT('$',
                                    '.',
                                    IFNULL(FORMAT(SUM(b.monto), 2), 0.00))
                    FROM
                        recibocli a
                            INNER JOIN
                        detpagorecli b ON b.idreccli = a.id
                    WHERE
                        a.fecha >= '$d->fdelstr'
                            AND a.fecha <= '$d->falstr' ";
    $query.= count($d->idempresa) > 0 ? "AND a.idempresa IN($ids_str) " : '';
    $query.= "                 AND b.idmoneda = 2) AS montodlr ";
    $totgen = $db->getQuery($query)[0];

    print json_encode(['fechas' => $fechas, 'recibos' => $recibos, 'total' => $totgen]);

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