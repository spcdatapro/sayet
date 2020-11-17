<?php
require 'vendor/autoload.php';
// require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

/*
$app->get('/lstmodulos', function () {
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('modulo',['id', 'descmodulo']);
    print json_encode($data);
});
*/

$app->get('/info', function () {
    phpinfo();
});

function rand_float($st_num = 0, $end_num = 1, $mul = 1000000)
{
    if ($st_num > $end_num) return false;
    return mt_rand($st_num * $mul, $end_num * $mul) / $mul;
}

$app->get('/test', function () {
    $resultado = [];

    for ($i = 0; $i < 3; $i++) {
        $no = random_int(1, 10);
        $cantidad = random_int(1, 10);
        $precio_unitario = round(rand_float(0, 20), 2);
        $resultado[] = [
            'id' => $no,
            'producto' => 'Producto ' . ($no < 10 ? ('0' . $no) : $no),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'precio_total' => round($cantidad * $precio_unitario, 2)
        ];
    }

    print json_encode([
        'exito' => true,
        'mensaje' => 'Datos obtenidos con éxito.',
        'resultado' => $resultado
    ]);
});

$app->get('/vacia', function () {

    $obj = '';
    $mensaje = 'NO ESTÁ VACÍA!!!';

    if (empty($obj)) {
        $mensaje = 'Está vacía...';
    }

    print json_encode([
        'exito' => true,
        'mensaje' => $mensaje
    ]);
});

$app->get('/tstrtwms', function () {
    //die("hola");
    $xml = "<?xml version='1.0'?>
        <documento>
            <encabezado>
            <usuario>1</usuario>
            </encabezado>
            <detalle>
                <item>
                    <cantidad>1</cantidad>
                    <articulo>65</articulo>
                    <precio_unitario>290</precio_unitario>
                    <particion>2</particion>
                </item>
            </detalle>
        </documento>";
    $client = null;
    $client = new SoapClient("https://f099a7732b61.ngrok.io/resttouch/wms.php/api?wsdl");
    $res =  $client->set_egreso($xml, "5dad3173-4df5-4fc2-97bb-6ff41c53d84c-1-1");
    echo "<pre>";
    print_r($res);
    echo "</pre>";
    echo "<pre>";
    print_r($client->__getLastRequest());
    echo "</pre>";
});

$app->run();
