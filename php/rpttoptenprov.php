<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rpttopten', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    // Top diez proveedores 
    $query = "SELECT 
                a.id,
                SUBSTRING(a.nombre, 1, 19) AS nombre,
                a.nit,
                (SELECT 
                        FORMAT(IF(b.idtipocompra = 3,
                                    IF(b.idtipofactura != 7,
                                        ROUND(SUM(b.subtotal * b.tipocambio), 2),
                                        0.00),
                                    0.00) + IF(b.idtipocompra IN (1 , 4),
                                    IF(b.idtipofactura != 7,
                                        ROUND(SUM(b.subtotal * b.tipocambio), 2),
                                        0.00),
                                    0.00),
                                2)
                    FROM
                        compra b
                    WHERE
                        b.idproveedor = a.id AND b.mesiva = $d->mes
                            AND YEAR(b.fechafactura) = $d->anio
                            AND b.idtipofactura != 5
                            AND b.idempresa = $d->idempresa
                            AND b.idreembolso = 0) AS bien,
                (SELECT 
                        IF(b.idtipocompra = 2,
                                IF(b.idtipofactura != 7,
                                    ROUND(SUM(b.subtotal * b.tipocambio), 2),
                                    0.00),
                                0.00)
                    FROM
                        compra b
                    WHERE
                        b.idproveedor = a.id AND b.mesiva = $d->mes
                            AND YEAR(b.fechafactura) = $d->anio
                            AND b.idtipofactura != 5
                            AND b.idempresa = $d->idempresa
                            AND b.idreembolso = 0) AS servicio,
                (SELECT 
                        FORMAT(SUM((b.subtotal + IF(b.idtipocompra = 3, 0.00, b.noafecto)) * b.tipocambio),
                                2)
                    FROM
                        compra b
                    WHERE
                        b.idproveedor = a.id AND b.mesiva = $d->mes
                            AND YEAR(b.fechafactura) = $d->anio
                            AND b.idtipofactura != 5
                            AND b.idempresa = $d->idempresa
                            AND b.idreembolso = 0) AS totbase,
                (SELECT 
                        FORMAT(SUM(b.iva), 2)
                    FROM
                        compra b
                    WHERE
                        b.idproveedor = a.id AND b.mesiva = $d->mes
                            AND YEAR(b.fechafactura) = $d->anio
                            AND b.idtipofactura != 5
                            AND b.idempresa = $d->idempresa
                            AND b.idreembolso = 0) AS totiva,
                (SELECT 
                        ROUND(SUM(b.iva), 2)
                    FROM
                        compra b
                    WHERE
                        b.idproveedor = a.id AND b.mesiva = $d->mes
                            AND YEAR(b.fechafactura) = $d->anio
                            AND b.idtipofactura != 5
                            AND b.idempresa = $d->idempresa
                            AND b.idreembolso = 0) AS sumiva,
                (SELECT 
                        ROUND(SUM((b.subtotal + IF(b.idtipocompra = 3, 0.00, b.noafecto)) * b.tipocambio),
                                    2)
                    FROM
                        compra b
                    WHERE
                        b.idproveedor = a.id AND b.mesiva = $d->mes
                            AND YEAR(b.fechafactura) = $d->anio
                            AND b.idtipofactura != 5
                            AND b.idempresa = $d->idempresa
                            AND b.idreembolso = 0) AS total,
                (SELECT 
                        COUNT(b.id)
                    FROM
                        compra b
                    WHERE
                        b.idproveedor = a.id AND b.mesiva = 01
                            AND YEAR(b.fechafactura) = 2022
                            AND b.idtipofactura != 5
                            AND b.idempresa = 4
                            AND b.idreembolso = 0) AS cuantas
            FROM
                proveedor a
            WHERE
                a.pequeniocont = 0
            ORDER BY total DESC
            LIMIT 10 ";
    $proveedores = $db->getQuery($query);

    for ($i = 0; $i < 10; $i++) {

        // obtener proveedor
        $proveedor = $proveedores[$i]; 

        $query = "SELECT 
                    fechafactura,
                    CONCAT(serie, '-', documento) AS factura,
                    FORMAT(IF(idtipocompra = 3,
                            IF(idtipofactura != 7,
                                ROUND(subtotal * tipocambio, 2),
                                0.00),
                            0.00) + IF(idtipocompra IN (1 , 4),
                            IF(idtipofactura != 7,
                                ROUND(subtotal * tipocambio, 2),
                                0.00),
                            0.00),
                        2) AS bien,
                    IF(idtipocompra = 2,
                        IF(idtipofactura != 7,
                            FORMAT(subtotal * tipocambio, 2),
                            0.00),
                        0.00) AS servicio,
                    FORMAT((subtotal + IF(idtipocompra = 3, 0.00, noafecto)) * tipocambio,
                        2) AS totfact,
                    FORMAT(iva * tipocambio, 2) AS iva
                FROM
                    compra
                WHERE
                    idproveedor = $proveedor->id AND mesiva = $d->mes
                        AND YEAR(fechafactura) = $d->anio
                        AND idtipofactura != 5
                        AND idempresa = $d->idempresa
                        AND idreembolso = 0 "; 
        $proveedor->facturas = $db->getQuery($query);

    }

    $query = "SELECT 
                    a.id,
                    (SELECT 
                            ROUND(SUM((b.subtotal + IF(b.idtipocompra = 3, 0.00, b.noafecto)) * b.tipocambio),
                                        2)
                        FROM
                            compra b
                        WHERE
                            b.idproveedor = a.id AND b.mesiva = 01
                                AND YEAR(b.fechafactura) = 2022
                                AND b.idtipofactura != 5
                                AND b.idempresa = 4
                                AND b.idreembolso = 0) AS total
                FROM
                    proveedor a
                WHERE
                    a.pequeniocont = 0
                ORDER BY total DESC
                LIMIT 10 ";
    $idproveedor = $db->getQueryAsArray($query);

    $id = $idproveedor[0][0];

    for ($i = 1; $i < 10; $i++) {
        $id .= ',';
        $id .= $idproveedor[$i][0];
    }

    $query = "SELECT 
                    FORMAT(SUM(iva), 2) AS iva
                FROM
                    compra
                WHERE
                    idproveedor IN($id) AND mesiva = $d->mes
                        AND YEAR(fechafactura) = $d->anio
                        AND idtipofactura != 5
                        AND idempresa = $d->idempresa
                        AND idreembolso = 0 ";
    $sumas = $db->getQuery($query)[0];

    print json_encode(['proveedores' => $proveedores, 'sumas' => $sumas]);
});

$app->run();