<?php

define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/sayet');
require_once BASEPATH . '/php/clases/Compra.php';

class compraWS {
	public function setFactura()
	{
		$args = func_get_arg(0);

		$xml = simplexml_load_string($args);
		$encabezado = (array)$xml->encabezado;

		$cp = new Compra();

		if (isset($encabezado['compra']) && empty($encabezado['compra'])) {
			$cp->setCompra($encabezado['compra']);

			unset($encabezado['compra']);
		}

		$cp->guardarCompra($encabezado);

		return $cp->getCompra();
	}
}

try {
	$server = new SOAPServer(
		NULL,
		array(
			'uri' => 'http://localhost/server.php'
		)
	);

	$server->setClass('compraWS');
	$server->handle();
} catch (SOAPFault $f) {
	print $f->faultstring;
}
