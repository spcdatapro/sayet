<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para tipo de cambio diario
function obj2array($obj) {
    $out = array();
    foreach ($obj as $key => $val) {
        switch(true) {
            case is_object($val):
                $out[$key] = obj2array($val);
                break;
            case is_array($val):
                $out[$key] = obj2array($val);
                break;
            default:
                $out[$key] = $val;
        }
    }
    return $out;
};
$app->get('/gettc', function(){
    try{
        $db = new dbcpm();
        $ws = "http://www.banguat.gob.gt/variables/ws/TipoCambio.asmx?wsdl";
        $client = new SoapClient($ws, array('trace' => 1));
        $resArr = obj2array($client->TipoCambioDia());
        $tc = $resArr['TipoCambioDiaResult']['CambioDolar']['VarDolar'];
        $tc['fecha'] = DateTime::createFromFormat('d/m/Y', $tc['fecha'])->format('Y-m-d');
        $query = "INSERT INTO tipocambio(fecha, tipocambio) VALUES('".$tc['fecha']."', ".$tc['referencia'].")";
        $db->doQuery($query);
        $query = "UPDATE moneda SET tipocambio = ".$tc['referencia']." WHERE id = 2";
        $db->doQuery($query);
    }catch(Exception $e){
        print 'Excepción ('.$e->getCode().'): '.$e->getMessage();
    }
});

$app->get('/getlasttc', function(){
    $db = new dbcpm();
    print json_encode(['lasttc' => $db->getOneField("SELECT tipocambio FROM tipocambio ORDER BY fecha DESC LIMIT 1")]);
});

$app->run();