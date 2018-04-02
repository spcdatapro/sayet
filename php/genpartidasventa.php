<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'text/html');
$app->response->headers->set('Cache-Control', 'no-cache');

function idc($db, $origen, $idorigen, $idcuenta, $debe, $haber, $conceptomayor, $anulado) {
    $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado) VALUES(";
    $query.= "$origen, $idorigen, $idcuenta, $debe, $haber, '$conceptomayor', 1, $anulado";
    $query.= ")";
    echo "$query<br/>";
    $db->doQuery($query);
}

$app->get('/generar', function(){
    $db = new dbcpm();
    $origen = 3;
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body><small>";

    $query = "SELECT a.id, a.idempresa, a.idcliente, a.idcontrato, TRIM(a.serie) AS serie, TRIM(a.numero) AS numero, TRIM(a.conceptomayor) AS conceptomayor, ";
    //$query.= "TRUNCATE(a.total + IF(a.esinsertada = 0, (a.totdescuento * 1.12 * -1), 0), 2) AS pagoneto, ";
    $query.= "TRUNCATE(a.total, 2) AS pagoneto, ";
    $query.= "TRUNCATE(a.retisr, 2) AS retisr, ";
    $query.= "TRUNCATE(a.retiva, 2) AS retiva, ";
    $query.= "TRUNCATE(a.iva, 2) AS iva, ";
    $query.= "a.anulada, a.esinsertada ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.fecha >= '2018-01-01' ";
    $query.= "AND a.idcontrato > 0 ";
    //$query.= "AND a.idcontrato = 0 ";
    $query.= "ORDER BY a.idempresa";
    $facturas = $db->getQuery($query);
    $cntFact = count($facturas);
    for($i = 0; $i < $cntFact; $i++){
        $factura = $facturas[$i];

        $yaesta = ((int)$db->getOneField("SELECT COUNT(id) FROM detallecontable WHERE origen = 3 AND idorigen = $factura->id")) > 0;

        if(!$yaesta){
            echo "<strong>Insertando detalle contable de factura $factura->serie $factura->numero</strong><br/>";

            //Cuenta del cliente
            //$query = "SELECT TRIM(idcuentac) FROM contrato WHERE id = $factura->idcontrato";
            //$codctacliente = $db->getOneField($query);
            $codctacliente = '1120199';
            echo "Codigo cuenta cliente = $codctacliente de empresa $factura->idempresa<br/>";
            $query = "SELECT id FROM cuentac WHERE TRIM(codigo) = '$codctacliente' AND idempresa = $factura->idempresa";
            $ctacliente = (int)$db->getOneField($query);
            if($ctacliente > 0){
                echo "<span style='text-decoration: underline;'>Cuenta del cliente</span><br/>";
                idc($db, $origen, $factura->id, $ctacliente, $factura->pagoneto, 0.00, $factura->conceptomayor, (int)$factura->anulada);
            }

            //Cuenta retención ISR
            if((float)$factura->retisr > 0){
                $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 13";
                $ctaretisr = (int)$db->getOneField($query);
                if($ctaretisr > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de retención de ISR</span><br/>";
                    idc($db, $origen, $factura->id, $ctaretisr, $factura->retisr, 0.00, $factura->conceptomayor, (int)$factura->anulada);
                }
            }

            //Cuenta retención IVA
            if((float)$factura->retiva > 0){
                $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 14";
                $ctaretiva = (int)$db->getOneField($query);
                if($ctaretiva > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de retención de IVA</span><br/>";
                    idc($db, $origen, $factura->id, $ctaretiva, $factura->retiva, 0.00, $factura->conceptomayor, (int)$factura->anulada);
                }
            }

            //Cuentas de detalle de factura
            //$query = "SELECT a.id, a.idtiposervicio, a.descripcion, TRUNCATE((a.preciotot / IF($factura->esinsertada = 0, 1.12, 1)) - (a.descuento / IF($factura->esinsertada = 0, 1.12, 1)), 2) AS monto ";
            //$query = "SELECT a.id, a.idtiposervicio, a.descripcion, TRUNCATE((a.preciotot / IF($factura->esinsertada = 0, 1.12, 1)), 2) AS monto ";
			$query = "SELECT a.id, a.idtiposervicio, a.descripcion, ROUND(a.preciotot / 1.12, 2) AS monto ";
            $query.= "FROM detfact a ";
            $query.= "WHERE idfactura = $factura->id";
            $detfact = $db->getQuery($query);
            $cntDetFact = count($detfact);
            for($j = 0; $j < $cntDetFact; $j++){
                $df = $detfact[$j];
                //Cuenta del servicio del detalle de factura
                $query = "SELECT b.id FROM tiposervicioventa a INNER JOIN cuentac b ON TRIM(b.codigo) = TRIM(a.cuentac) WHERE a.id = $df->idtiposervicio AND b.idempresa = $factura->idempresa";
                $ctadetalle = (int)$db->getOneField($query);
                if($ctadetalle > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de detalle de factura ".($j + 1)."</span><br/>";
                    idc($db, $origen, $factura->id, $ctadetalle, 0.00, $df->monto, $df->descripcion, (int)$factura->anulada);
                }
            }

            //Cuenta del IVA débito
            $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 1";
            $ctaivadebito = (int)$db->getOneField($query);
            if($ctaivadebito > 0){
                echo "<span style='text-decoration: underline;'>Cuenta de IVA débito</span><br/>";
                idc($db, $origen, $factura->id, $ctaivadebito, 0.00, $factura->iva, $factura->conceptomayor, (int)$factura->anulada);
            }
        }
    }
    echo "<p><strong>Terminamos...</strong></p></small></body></html>";
});

$app->get('/regen', function(){
    $db = new dbcpm();
    $origen = 3;
    $ids = "5338, 5339, 5340, 5341, 5342, 5343, 5344, 5345, 5346, 5347, 5348, 5349, 5350, 5351, 5352, 5353, 5354, 5355, 5356, 5357, 5358, 5359, 5360, 5361, 5362, 5363, 5364, 5365, 5366, 5367, 5368, 5369, 5370, 5371, 5372, 5373, 5374, 5375, 5376, 5377, 5378, 5379, 5380, 5381, 5382, 5383, 5384, 5385, 5386, 5387, 5388, 5389, 5390, 5391, 5392, 5393, 5394, 5395, 5396, 5397, 5398, 5399, 5400, 5401, 5402, 5403, 5404, 5405, 5406, 5407, 5408, 5409, 5410, 5411, 5412, 5413, 5414, 5415, 5416, 5417, 5418, 5419, 5420, 5421, 5422, 5423, 5424, 5425, 5426, 5427, 5428, 5429, 5430, 5431, 5432, 5433, 5434, 5435, 5436, 5437, 5438, 5439, 5440, 5441, 5442, 5443, 5444, 5445, 5446, 5447, 5448, 5449, 5450, 5451, 5452, 5453, 5454, 5455, 5456, 5457, 5458, 5459, 5460, 5461, 5462, 5463, 5464, 5465, 5466, 5467, 5468, 5469, 5470, 5471, 5472, 5473, 5474, 5475, 5476, 5477, 5478, 5479, 5480, 5481, 5482, 5483, 5484, 5485, 5486, 5487, 5488, 5489, 5490, 5491, 5492, 5493, 5495, 5496, 5497, 5498, 5499, 5500, 5501, 5502, 5503, 5504, 5505, 5506, 5507, 5508, 5509";
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body><small><h3>Regeneración de facturas específicas</h3><h2>$ids</h2>";

    $query = "DELETE FROM detallecontable WHERE origen = $origen AND idorigen IN($ids)";
    $db->doQuery($query);

    $query = "SELECT a.id, a.idempresa, a.idcliente, a.idcontrato, TRIM(a.serie) AS serie, TRIM(a.numero) AS numero, TRIM(a.conceptomayor) AS conceptomayor, ";
    $query.= "ROUND(a.total, 2) AS pagoneto, ";
    $query.= "ROUND(a.retisr, 2) AS retisr, ";
    $query.= "ROUND(a.retiva, 2) AS retiva, ";
    $query.= "ROUND(a.iva, 2) AS iva, ";
    $query.= "a.anulada, a.esinsertada ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.id IN($ids) ";
    //$query.= "AND a.idcontrato > 0 ";
    $query.= "AND a.idcontrato = 0 ";
    $query.= "ORDER BY a.idempresa";
    $facturas = $db->getQuery($query);
    $cntFact = count($facturas);
    for($i = 0; $i < $cntFact; $i++){
        $factura = $facturas[$i];

        $yaesta = ((int)$db->getOneField("SELECT COUNT(id) FROM detallecontable WHERE origen = 3 AND idorigen = $factura->id")) > 0;

        if(!$yaesta){
            echo "<strong>Insertando detalle contable de factura $factura->serie $factura->numero</strong><br/>";

            //Cuenta del cliente
            //$query = "SELECT TRIM(idcuentac) FROM contrato WHERE id = $factura->idcontrato";
            //$codctacliente = $db->getOneField($query);
            $codctacliente = '1120199';
            echo "Codigo cuenta cliente = $codctacliente de empresa $factura->idempresa<br/>";
            $query = "SELECT id FROM cuentac WHERE TRIM(codigo) = '$codctacliente' AND idempresa = $factura->idempresa";
            $ctacliente = (int)$db->getOneField($query);
            if($ctacliente > 0){
                echo "<span style='text-decoration: underline;'>Cuenta del cliente</span><br/>";
                idc($db, $origen, $factura->id, $ctacliente, $factura->pagoneto, 0.00, $factura->conceptomayor, (int)$factura->anulada);
            }

            //Cuenta retención ISR
            if((float)$factura->retisr > 0){
                $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 13";
                $ctaretisr = (int)$db->getOneField($query);
                if($ctaretisr > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de retención de ISR</span><br/>";
                    idc($db, $origen, $factura->id, $ctaretisr, $factura->retisr, 0.00, $factura->conceptomayor, (int)$factura->anulada);
                }
            }

            //Cuenta retención IVA
            if((float)$factura->retiva > 0){
                $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 14";
                $ctaretiva = (int)$db->getOneField($query);
                if($ctaretiva > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de retención de IVA</span><br/>";
                    idc($db, $origen, $factura->id, $ctaretiva, $factura->retiva, 0.00, $factura->conceptomayor, (int)$factura->anulada);
                }
            }

            //Cuentas de detalle de factura
            $query = "SELECT a.id, a.idtiposervicio, a.descripcion, ROUND((a.preciotot - IF($factura->esinsertada = 0, 0, a.descuento)) / 1.12, 2) AS monto ";
            $query.= "FROM detfact a ";
            $query.= "WHERE idfactura = $factura->id";
            $detfact = $db->getQuery($query);
            $cntDetFact = count($detfact);
            for($j = 0; $j < $cntDetFact; $j++){
                $df = $detfact[$j];
                //Cuenta del servicio del detalle de factura
                $query = "SELECT b.id FROM tiposervicioventa a INNER JOIN cuentac b ON TRIM(b.codigo) = TRIM(a.cuentac) WHERE a.id = $df->idtiposervicio AND b.idempresa = $factura->idempresa";
                $ctadetalle = (int)$db->getOneField($query);
                if($ctadetalle > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de detalle de factura ".($j + 1)."</span><br/>";
                    idc($db, $origen, $factura->id, $ctadetalle, 0.00, $df->monto, $df->descripcion, (int)$factura->anulada);
                }
            }

            //Cuenta del IVA débito
            $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 1";
            $ctaivadebito = (int)$db->getOneField($query);
            if($ctaivadebito > 0){
                echo "<span style='text-decoration: underline;'>Cuenta de IVA débito</span><br/>";
                idc($db, $origen, $factura->id, $ctaivadebito, 0.00, $factura->iva, $factura->conceptomayor, (int)$factura->anulada);
            }
        }
    }
    echo "<p><strong>Terminamos la regeneración...</strong></p></small></body></html>";
});

$app->post('/genpost', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $origen = 3;
    $ids = $d->ids;
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body><small><h3>Regeneración de facturas específicas</h3><h2>$ids</h2>";

    $query = "DELETE FROM detallecontable WHERE origen = $origen AND idorigen IN($ids)";
    $db->doQuery($query);

    $query = "SELECT a.id, a.idempresa, a.idcliente, a.idcontrato, TRIM(a.serie) AS serie, TRIM(a.numero) AS numero, TRIM(a.conceptomayor) AS conceptomayor, ";
    $query.= "ROUND(a.total, 2) AS pagoneto, ";
    $query.= "ROUND(a.retisr, 2) AS retisr, ";
    $query.= "ROUND(a.retiva, 2) AS retiva, ";
    $query.= "ROUND(a.iva, 2) AS iva, ";
    $query.= "a.anulada, a.esinsertada ";
    $query.= "FROM factura a ";
    $query.= "WHERE a.id IN($ids) ";
    $query.= "AND a.idcontrato ".((int)$d->idcontrato > 0 ? ">" : "=")." 0 ";
    $query.= "ORDER BY a.idempresa";
    $facturas = $db->getQuery($query);
    $cntFact = count($facturas);
    for($i = 0; $i < $cntFact; $i++){
        $factura = $facturas[$i];

        $yaesta = ((int)$db->getOneField("SELECT COUNT(id) FROM detallecontable WHERE origen = 3 AND idorigen = $factura->id")) > 0;

        if(!$yaesta){
            echo "<strong>Insertando detalle contable de factura $factura->serie $factura->numero</strong><br/>";

            //Cuenta del cliente
            $codctacliente = '';
            if((int)$d->idcontrato > 0){
                $query = "SELECT TRIM(idcuentac) FROM contrato WHERE id = $factura->idcontrato";
                $codctacliente = $db->getOneField($query);
            }else{
                $codctacliente = '1120199';
            }

            echo "Codigo cuenta cliente = $codctacliente de empresa $factura->idempresa<br/>";
            $query = "SELECT id FROM cuentac WHERE TRIM(codigo) = '$codctacliente' AND idempresa = $factura->idempresa";
            $ctacliente = (int)$db->getOneField($query);
            if($ctacliente > 0){
                echo "<span style='text-decoration: underline;'>Cuenta del cliente</span><br/>";
                idc($db, $origen, $factura->id, $ctacliente, $factura->pagoneto, 0.00, $factura->conceptomayor, (int)$factura->anulada);
            }

            //Cuenta retención ISR
            if((float)$factura->retisr > 0){
                $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 13";
                $ctaretisr = (int)$db->getOneField($query);
                if($ctaretisr > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de retención de ISR</span><br/>";
                    idc($db, $origen, $factura->id, $ctaretisr, $factura->retisr, 0.00, $factura->conceptomayor, (int)$factura->anulada);
                }
            }

            //Cuenta retención IVA
            if((float)$factura->retiva > 0){
                $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 14";
                $ctaretiva = (int)$db->getOneField($query);
                if($ctaretiva > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de retención de IVA</span><br/>";
                    idc($db, $origen, $factura->id, $ctaretiva, $factura->retiva, 0.00, $factura->conceptomayor, (int)$factura->anulada);
                }
            }

            //Cuentas de detalle de factura
            $query = "SELECT a.id, a.idtiposervicio, a.descripcion, ROUND((a.preciotot - IF($factura->esinsertada = 0, 0, a.descuento)) / 1.12, 2) AS monto ";
            $query.= "FROM detfact a ";
            $query.= "WHERE idfactura = $factura->id";
            $detfact = $db->getQuery($query);
            $cntDetFact = count($detfact);
            for($j = 0; $j < $cntDetFact; $j++){
                $df = $detfact[$j];
                //Cuenta del servicio del detalle de factura
                $query = "SELECT b.id FROM tiposervicioventa a INNER JOIN cuentac b ON TRIM(b.codigo) = TRIM(a.cuentac) WHERE a.id = $df->idtiposervicio AND b.idempresa = $factura->idempresa";
                $ctadetalle = (int)$db->getOneField($query);
                if($ctadetalle > 0){
                    echo "<span style='text-decoration: underline;'>Cuenta de detalle de factura ".($j + 1)."</span><br/>";
                    idc($db, $origen, $factura->id, $ctadetalle, 0.00, $df->monto, $df->descripcion, (int)$factura->anulada);
                }
            }

            //Cuenta del IVA débito
            $query = "SELECT idcuentac FROM detcontempresa WHERE idempresa = $factura->idempresa AND idtipoconfig = 1";
            $ctaivadebito = (int)$db->getOneField($query);
            if($ctaivadebito > 0){
                echo "<span style='text-decoration: underline;'>Cuenta de IVA débito</span><br/>";
                idc($db, $origen, $factura->id, $ctaivadebito, 0.00, $factura->iva, $factura->conceptomayor, (int)$factura->anulada);
            }
        }
    }
    echo "<p><strong>Terminamos la regeneración...</strong></p></small></body></html>";
});

$app->run();