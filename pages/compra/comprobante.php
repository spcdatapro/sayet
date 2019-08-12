<!DOCTYPE html>
<html>
<head>
	<title>Comprobante Compra</title>
	<style type="text/css">
		
			body {
				width: 650px;
				margin: 0 auto 0 auto;
			}

			table {
				width: 100% !important;
				font-size: 11px;
				font-family: 'Arial', sans-serif;
				border-collapse: collapse;
				margin-bottom: 20px !important;
			}

			table tbody {
				margin-bottom: 30px !important;
			}

			table tbody td, th {
				border:1px solid #000;
				padding:0.5em;
			}

			table tfoot td, th {
				border:1px solid #000;
				padding:0.5em;
			}

			.derecha {
				text-align: right;
			}

			.izquierda {
				text-align: left;
			}
		
	</style>
</head>
<body>
	<table>
		<tbody>
			<tr>
				<th>Empresa:</th>
				<td colspan="3"><?php echo $compra->nomempresa ?></td>
			</tr>
			<tr>
				<th>Proyecto:</th>
				<td colspan="3"><?php echo $compra->nomproyecto ?></td>
			</tr>
			<tr>
				<th>Proveedor:</th>
				<td colspan="3"><?php echo $compra->nomproveedor ?></td>
			</tr>
			<tr>
				<th>Fecha:</th>
				<td><?php echo formatoFecha($compra->fechafactura, 1) ?></td>
				<th>Tipo:</th>
				<td><?php echo $compra->siglas ?></td>
			</tr>
			<tr>
				<th>Documento:</th>
				<td><?php echo $compra->documento ?></td>
				<th>Serie:</th>
				<td><?php echo $compra->serie ?></td>
			</tr>
			<tr>
				<th>Ingreso:</th>
				<td><?php echo formatoFecha($compra->fechaingreso, 1) ?></td>
				<th>Mes IVA:</th>
				<td><?php echo $compra->mesiva ?></td>
			</tr>
			<tr>
				<th>Compra:</th>
				<td><?php echo $compra->desctipocompra ?></td>
				<th>Moneda:</th>
				<td><?php echo $compra->moneda ?></td>
			</tr>
			<tr>
				<th>Pago:</th>
				<td><?php echo formatoFecha($compra->fechapago, 1) ?></td>
				<th>Tipo de Cambio:</th>
				<td class="derecha"><?php echo number_format($compra->tipocambio, 2) ?></td>
			</tr>
			<tr>
				<th>Crédito Fiscal:</th>
				<td><?php echo ($compra->creditofiscal == 1 ? 'SI':'NO') ?></td>
				<th>Extraordinario:</th>
				<td><?php echo ($compra->extraordinario == 1 ? 'SI':'NO') ?></td>
			</tr>
			<tr>
				<th>I.D.P.:</th>
				<td class="derecha"><?php echo number_format($compra->idp, 2) ?></td>
				<th>Subtotal:</th>
				<td class="derecha"><?php echo number_format($compra->subtotal, 2) ?></td>
			</tr>
			<tr>
				<th>No Afecto:</th>
				<td class="derecha"><?php echo number_format($compra->noafecto, 2) ?></td>
				<th>IVA:</th>
				<td class="derecha"><?php echo number_format($compra->iva, 2) ?></td>
			</tr>
			<tr>
				<th>I.S.R.:</th>
				<td class="derecha"><?php echo number_format($compra->isr, 2) ?></td>
				<th>Total:</th>
				<td class="derecha"><?php echo number_format($compra->totfact, 2) ?></td>
			</tr>
			<tr>
				<td colspan="4">
					<small><b>Concepto:</b><br><?php echo $compra->conceptomayor ?></small>
				</td>
			</tr>
		</tbody>
	</table>
	<hr>
	<table>
		<thead>
			<tr>
				<th class="izquierda">Cuenta</th>
				<th class="derecha">Debe</th>
				<th class="derecha">Haber</th>
				<th class="izquierda">Concepto Mayor</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($compra->detalle as $row): ?>
				<tr>
					<td class="izquierda"><?php echo $row->desccuentacont ?></td>
					<td class="derecha"><?php echo number_format($row->debe, 2) ?></td>
					<td class="derecha"><?php echo number_format($row->haber, 2) ?></td>
					<td class="izquierda"><small><?php echo $row->conceptomayor ?></small></td>
				</tr>
			<?php endforeach ?>
		</tbody>
		<tfoot>
			<tr>
				<td class="izquierda">TOTAL</td>
				<td class="derecha"><?php echo number_format(suma_field($compra->detalle, 'debe'), 2) ?></td>
				<td class="derecha"><?php echo number_format(suma_field($compra->detalle, 'haber'), 2) ?></td>
				<td></td>
			</tr>
		</tfoot>
	</table>

	<script type="text/javascript">
		window.print()
	</script>
</body>
</html>
