<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$url = "http://localhost/jk/php/ws/server.php";

$client = new SoapClient(null, array(
	'location' => $url,
	'uri'      => $url,
	'trace'    => 1 
));

$compra = "
<document>
	<encabezado>
		<idempresa>4</idempresa>
		<idproyecto>0</idproyecto>
		<idreembolso>0</idreembolso>
		<idtipofactura>6</idtipofactura>
		<idproveedor>8</idproveedor>
		<proveedor>Alejandro</proveedor>
		<nit>725490-3</nit>
		<serie> </serie>
		<documento>6</documento>
		<fechaingreso>6</fechaingreso>
		<fechaingresobck> </fechaingresobck>
		<mesiva>9</mesiva>
		<fechafactura>7</fechafactura>
		<idtipocompra>1</idtipocompra>
		<conceptomayor>A</conceptomayor>
		<creditofiscal>0</creditofiscal>
		<extraordinario>0</extraordinario>
		<fechapago>7</fechapago>
		<ordentrabajo>0</ordentrabajo>
		<totfact>0</totfact>
		<noafecto>0</noafecto>
		<subtotal>0</subtotal>
		<iva>0</iva>
		<retenerisr>0</retenerisr>
		<isr>0</isr>
		<idtipocombustible>0</idtipocombustible>
		<galones>0</galones>
		<idp>0</idp>
		<idmoneda>1</idmoneda>
		<tipocambio>0</tipocambio>
		<noformisr> </noformisr>
		<noaccisr> </noaccisr>
		<fecpagoformisr> </fecpagoformisr>
		<mesisr> </mesisr>
		<anioisr> </anioisr>
		<revisada>0</revisada>
		<idsubtipogasto>0</idsubtipogasto>
		<cuadrada>0</cuadrada>
	</encabezado>
	<detalle>
		<cuenta>10101010</cuenta>
		<debe>150</debe>
		<haber>0</haber>
	</detalle>
</document>";

# header("Content-Type:text/xml");
$return = $client->__soapCall("setFactura",array(
	"compra" => $compra
));

echo "<pre>";
print_r($return);
echo "</pre>";
