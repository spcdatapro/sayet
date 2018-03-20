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
    $ids = "5101, 5102, 5103, 5104, 5105, 5106, 5107, 5108, 5109, 5110, 5111, 5112, 5113, 5114, 5115, 5116, 5117, 5118, 5119, 5120, 5121, 5122, 5123, 5124, 5125, 5126, 5127, 5128, 5129, 5130, 5131, 5132, 5133, 5134, 5135, 5136, 5137, 5138, 5139, 5140, 5141, 5142, 5143, 5144, 5145, 5146, 5147, 5148, 5149, 5150, 5151, 5152, 5153, 5154, 5155, 5156, 5157, 5158, 5159, 5160, 5161, 5162, 5163, 5164, 5165, 5166, 5167, 5168, 5169, 5170, 5171, 5172, 5173, 5174, 5175, 5176, 5177, 5178, 5179, 5180, 5181, 5182, 5183, 5184, 5185, 5186, 5187, 5188, 5189, 5190, 5191, 5192, 5193, 5194, 5195, 5196, 5197, 5198, 5199, 5200, 5201, 5202, 5203, 5204, 5205, 5206, 5207, 5208, 5209, 5210, 5211, 5212, 5213, 5214, 5215, 5216, 5217, 5218, 5219, 5220, 5221, 5222, 5223, 5224, 5225, 5226, 5227, 5228, 5229, 5230, 5231, 5232, 5233, 5234, 5235, 5236, 5237, 5238, 5239, 5240, 5241, 5242, 5243, 5244, 5245, 5246, 5247, 5248, 5249, 5250, 5251, 5252, 5253, 5254, 5255, 5256, 5257, 5258, 5260, 5261, 5262, 5263, 5264, 5265, 5266, 5268, 5269, 5270, 5271, 5272, 5273";
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
            $query = "SELECT TRIM(idcuentac) FROM contrato WHERE id = $factura->idcontrato";
            $codctacliente = $db->getOneField($query);
            //$codctacliente = '1120199';
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