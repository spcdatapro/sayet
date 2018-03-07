<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para adjuntos de proyectos
$app->get('/lstproyectoadjunto', function(){
    $db = new dbcpm();
    $query = "SELECT idadjunto, headerid, nomadjunto, tipo_adjunto, ubicacion, idtipodocproy, numero, fvence ";
    $query.= "FROM proyecto_adjunto ";
    $query.= "ORDER BY nomadjunto";
    print $db->doSelectASJson($query);
});

$app->get('/getproyectoadjunto/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $query = "SELECT a.idadjunto, a.headerid, a.nomadjunto, a.tipo_adjunto, a.ubicacion, SUBSTRING_INDEX(a.ubicacion, '.', -1) as extension, ";
    $query.= "b.nombre as nomtipo_adjunto, a.numero, a.fvence, c.descripcion AS tipodocproy ";
    $query.= "FROM proyecto_adjunto a ";
    $query.= "LEFT JOIN tipo_adjunto b ON a.tipo_adjunto = b.id ";
    $query.= "LEFT JOIN tipodocproy c ON c.id = a.idtipodocproy ";
    $query.= "WHERE headerid = ".$idproyecto." ORDER BY c.descripcion, a.nomadjunto";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $fvence = is_null($d->fvencestr) ? 'NULL' : "'".$d->fvencestr."'";
    $query = "INSERT INTO proyecto_adjunto(headerid, nomadjunto, tipo_adjunto, ubicacion, idtipodocproy, numero, fvence) ";
    $query.= "VALUES(".$d->idproyecto.",'".$d->nomadjunto."', ".$d->tipo_adjunto.", '".$d->ubicacion."', ".$d->idtipodocproy.", ";
    $query.= "'".$d->numero."', ".$fvence.")";
    $db->doQuery($query);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE proyecto_adjunto SET nomadjunto = '".$d->nomadjunto."', tipo_adjunto = ".$d->tipo_adjunto." WHERE id = ".$d->idadjunto;
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $ubicacion = $db->getOneField("SELECT ubicacion FROM proyecto_adjunto WHERE idadjunto = ".$d->id);
    if(file_exists('../'.$ubicacion)){ unlink('../'.$ubicacion); }
    $query = "DELETE FROM proyecto_adjunto WHERE idadjunto = ".$d->id;
    $db->doQuery($query);
});

$app->get('/chkadjuntos', function(){
    $db = new dbcpm();

    $unfound = [];
    $query = "SELECT b.id AS idproyecto, b.nomproyecto, a.nomadjunto, a.ubicacion ";
    $query.= "FROM proyecto_adjunto a INNER JOIN proyecto b ON b.id = a.headerid ";
    $query.= "ORDER BY b.nomproyecto, a.nomadjunto";
    $adjuntos = $db->getQuery($query);
    $cntAdjuntos = count($adjuntos);
    for($i = 0; $i < $cntAdjuntos; $i ++){
        $adjunto = $adjuntos[$i];
        if(!file_exists("../$adjunto->ubicacion")){
            $unfound[] = [
                'idproyecto' => $adjunto->idproyecto,
                'proyecto' => $adjunto->nomproyecto,
                'adjunto' => $adjunto->nomadjunto,
                'ubicacion' => $adjunto->ubicacion
            ];
        }
    }

    print json_encode($unfound);

});

$app->run();