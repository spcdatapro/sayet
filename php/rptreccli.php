<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

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
                    AND a.fecha <= '$d->falstr' ";
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
                        AND a.idempresa = $empresa->idempresa ";
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
                                    IFNULL(IF(a.serie = 'A', b.seriea, b.serieb),
                                            a.id),
                                    IF(a.serie = 'A',
                                        CONCAT(b.seriea, ' (ANULADO)'),
                                        CONCAT(b.serieb, ' (ANULADO)')))) AS norecibo,
                        c.nomempresa AS empresa,
                        DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha,
                        IFNULL(IFNULL(SUBSTRING(d.nombre, 1, 39),
                                        SUBSTRING(e.nombre, 1, 39)),
                                'Cientes varios') AS cliente,
                                (SELECT 
                                    CONCAT(c.simbolo, '.', FORMAT(SUM(b.monto), 2))
                                FROM
                                    detpagorecli b
                                        INNER JOIN
                                    moneda c ON c.id = b.idmoneda
                                WHERE
                                    b.idreccli = a.id AND b.idmoneda = 1) AS totreciboqtz,
                            (SELECT 
                                    CONCAT(c.simbolo, '.', FORMAT(SUM(b.monto), 2))
                                FROM
                                    detpagorecli b
                                        INNER JOIN
                                    moneda c ON c.id = b.idmoneda
                                WHERE
                                    b.idreccli = a.id AND b.idmoneda = 2) AS totrecibodlr,
                        i.nomproyecto AS proyecto,
                        IF(j.idmoneda = 2, 1, NULL) AS esdolares
                    FROM
                        recibocli a
                            LEFT JOIN
                        serierecli b ON a.id = b.idrecibocli
                            INNER JOIN
                        empresa c ON c.id = a.idempresa
                            LEFT JOIN
                        cliente d ON d.id = a.idcliente
                            LEFT JOIN
                        factura e ON e.nit = a.nit AND a.nit != 'CF'
                            LEFT JOIN
                        detcobroventa f ON f.idrecibocli = a.id
                            LEFT JOIN
                        factura g ON g.id = f.idfactura
                            LEFT JOIN
                        contrato h ON g.idcontrato = h.id
                            LEFT JOIN
                        proyecto i ON h.idproyecto = i.id
                            LEFT JOIN
                        detpagorecli j ON j.idreccli = a.id
                    WHERE
                        a.tipo = 1 AND a.fecha >= '$d->fdelstr'
                            AND a.fecha <= '$d->falstr'
                            AND i.id = $proyecto->idproyecto ";
            $query.= $d->anulados != 1 ? "AND a.anulado = 0 " : '';               
            $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : '';
            $query.= $d->idcliente != 0 ? "AND a.idcliente = $d->idcliente " : '';
            $query.= $d->idproyecto != 0 ? "AND i.id = $d->idproyecto ": '';
            $query.= "ORDER BY a.serie ASC , IFNULL(IF(a.serie = 'A', b.seriea, b.serieb), 
            a.id) ASC ";   
            $proyecto->recibos = $db->getQuery($query);

            // id de recibos por proyecto
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
                            AND i.id = $proyecto->idproyecto
                            AND a.anulado = 0
                    ORDER BY i.nomproyecto ASC , a.serie ASC , IFNULL(IF(a.serie = 'A', b.seriea, b.serieb),
                            a.id) ASC ";
            $idrecpry = $db->getQuery($query)[0];

            // suma de monto recibos por proyecto
            $query = "SELECT 
            (SELECT 
                    IFNULL(CONCAT(b.simbolo, FORMAT(SUM(a.monto), 2)),
                                CONCAT('Q', 0.00))
                FROM
                    detpagorecli a
                        INNER JOIN
                    moneda b ON b.id = a.idmoneda
                WHERE
                    a.idreccli IN ($idrecpry->recibos)
                        AND a.idmoneda = 1) AS montopryqtz,
            (SELECT 
                    IFNULL(CONCAT(b.simbolo, FORMAT(SUM(a.monto), 2)),
                                CONCAT('$', 0.00))
                FROM
                    detpagorecli a
                        INNER JOIN
                    moneda b ON b.id = a.idmoneda
                WHERE
                    a.idreccli IN ($idrecpry->recibos)
                        AND a.idmoneda = 2) AS montoprydls ";
            $proyecto->totalproy = $db->getQuery($query);
        }
         
        // id de recibos por empresa
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
                        AND a.idempresa = $empresa->idempresa
                        AND a.anulado = 0
                ORDER BY a.id DESC ";
        $idrecemp = $db->getQuery($query)[0];
        
        // suma de monto recibos por empresa
        $query = "SELECT 
        (SELECT 
                IFNULL(CONCAT(b.simbolo, FORMAT(SUM(a.monto), 2)),
                            CONCAT('Q', 0.00))
            FROM
                detpagorecli a
                    INNER JOIN
                moneda b ON b.id = a.idmoneda
            WHERE
                a.idreccli IN ($idrecemp->recibos)
                    AND a.idmoneda = 1) AS montoempqtz,
        (SELECT 
                IFNULL(CONCAT(b.simbolo, FORMAT(SUM(a.monto), 2)),
                            CONCAT('$', 0.00))
            FROM
                detpagorecli a
                    INNER JOIN
                moneda b ON b.id = a.idmoneda
            WHERE
                a.idreccli IN ($idrecemp->recibos)
                    AND a.idmoneda = 2) AS montoempdls ";
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
            IFNULL(CONCAT(b.simbolo, FORMAT(SUM(a.monto), 2)),
                        CONCAT('Q', 0.00))
        FROM
            detpagorecli a
                INNER JOIN
            moneda b ON b.id = a.idmoneda
        WHERE
            a.idreccli IN ($idrectod->recibos)
                AND a.idmoneda = 1) AS totgenqtz,
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