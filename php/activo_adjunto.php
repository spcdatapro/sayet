<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para adjuntos de activos
$app->get('/lstactivoadjunto', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.idadjunto, a.headerid, a.nomadjunto, a.tipo_adjunto, a.ubicacion, SUBSTRING_INDEX(a.ubicacion, '.', -1) as extension, b.nombre as nomtipo_adjunto ";
    $query .= "from activo_adjunto a ";
    $query .= "left join tipo_adjunto b on a.tipo_adjunto=b.id ORDER BY a.nomadjunto";

    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);

    //$data = $conn->select('activo_adjunto',['idadjunto', 'headerid', 'nomadjunto', 'tipo_adjunto', 'ubicacion','SUBSTRING_INDEX(ubicacion, '.', -1) as extencion'],['ORDER' => 'nomadjunto']);
    //print json_encode($data);

});

$app->get('/getactivoadjunto/:idactivo', function($idactivo){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.idadjunto, a.headerid, a.nomadjunto, a.tipo_adjunto, a.ubicacion, SUBSTRING_INDEX(a.ubicacion, '.', -1) as extension, ";
    $query.= "b.nombre as nomtipo_adjunto ";
    $query.= "FROM activo_adjunto a ";
    $query.= "LEFT JOIN tipo_adjunto b ON a.tipo_adjunto = b.id WHERE headerid = ".$idactivo." ORDER BY a.nomadjunto";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);

    //$data = $conn->select('activo_adjunto',['idadjunto', 'headerid', 'nomadjunto', 'tipo_adjunto', 'ubicacion'],['headerid' => $idactivo, 'ORDER' => 'nomadjunto']);
    //print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO activo_adjunto(headerid,nomadjunto, tipo_adjunto, ubicacion) ";
    $query.= "VALUES(".$d->idactivo.",'".$d->nomadjunto."', ".$d->tipo_adjunto.", '".$d->ubicacion."')";
    $ins = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $ubicacion = $conn->query("SELECT ubicacion FROM activo_adjunto WHERE idadjunto = ".$d->id)->fetchColumn(0);
    if(file_exists('../'.$ubicacion)){ unlink('../'.$ubicacion); }
    $query = "DELETE FROM activo_adjunto WHERE idadjunto = ".$d->id;
    $del = $conn->query($query);
});

$app->get('/chkadjuntos', function(){
    $db = new dbcpm();

    $unfound = [];
    $query = "SELECT b.id AS idactivo, CONCAT(b.finca, '-', b.folio, '-', b.libro) AS ffl, a.nomadjunto, a.ubicacion
              FROM activo_adjunto a
              INNER JOIN activo b ON b.id = a.headerid
              ORDER BY 2, a.nomadjunto";
    $adjuntos = $db->getQuery($query);
    $cntAdjuntos = count($adjuntos);
    for($i = 0; $i < $cntAdjuntos; $i ++){
        $adjunto = $adjuntos[$i];
        if(!file_exists("../$adjunto->ubicacion")){
            $unfound[] = [
                'idactivo' => $adjunto->idactivo,
                'ffl' => $adjunto->ffl,
                'adjunto' => $adjunto->nomadjunto,
                'ubicacion' => $adjunto->ubicacion
            ];
        }
    }

    print json_encode($unfound);

});

$app->run();