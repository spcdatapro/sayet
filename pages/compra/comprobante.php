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
				<td colspan="3"><?php echo $compra[0]->nomempresa ?></td>
			</tr>
			<tr>
				<th>Proyecto:</th>
				<td colspan="3"><?php echo $compra[0]->nomproyecto ?></td>
			</tr>
			<tr>
				<th>Proveedor:</th>
				<td colspan="3"><?php echo $compra[0]->nomproveedor ?></td>
			</tr>
			<tr>
				<th>Fecha:</th>
				<td><?php echo formatoFecha($compra[0]->fechafactura, 1) ?></td>
				<th>Tipo:</th>
				<td><?php echo $compra[0]->siglas ?></td>
			</tr>
			<tr>
				<th>Documento:</th>
				<td><?php echo $compra[0]->documento ?></td>
				<th>Serie:</th>
				<td><?php echo $compra[0]->serie ?></td>
			</tr>
			<tr>
				<th>Ingreso:</th>
				<td><?php echo formatoFecha($compra[0]->fechaingreso, 1) ?></td>
				<th>Mes IVA:</th>
				<td><?php echo $compra[0]->mesiva ?></td>
			</tr>
			<tr>
				<th>Compra:</th>
				<td><?php echo $compra[0]->desctipocompra ?></td>
				<th>Moneda:</th>
				<td><?php echo $compra[0]->moneda ?></td>
			</tr>
			<tr>
				<th>Pago:</th>
				<td><?php echo formatoFecha($compra[0]->fechapago, 1) ?></td>
				<th>Tipo de Cambio:</th>
				<td class="derecha"><?php echo number_format($compra[0]->tipocambio, 2) ?></td>
			</tr>
			<tr>
				<th>Crédito Fiscal:</th>
				<td><?php echo ($compra[0]->creditofiscal == 1 ? 'SI':'NO') ?></td>
				<th>Extraordinario:</th>
				<td><?php echo ($compra[0]->extraordinario == 1 ? 'SI':'NO') ?></td>
			</tr>
			<tr>
				<th>I.D.P.:</th>
				<td class="derecha"><?php echo number_format($compra[0]->idp, 2) ?></td>
				<th>Subtotal:</th>
				<td class="derecha"><?php echo number_format($compra[0]->subtotal, 2) ?></td>
			</tr>
			<tr>
				<th>No Afecto:</th>
				<td class="derecha"><?php echo number_format($compra[0]->noafecto, 2) ?></td>
				<th>IVA:</th>
				<td class="derecha"><?php echo number_format($compra[0]->iva, 2) ?></td>
			</tr>
			<tr>
				<th>I.S.R.:</th>
				<td class="derecha"><?php echo number_format($compra[0]->isr, 2) ?></td>
				<th>Total:</th>
				<td class="derecha"><?php echo number_format($compra[0]->totfact, 2) ?></td>
			</tr>
			<tr>
				<td colspan="4">
					<small><b>Concepto:</b><br><?php echo $compra[0]->conceptomayor ?></small>
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
			<?php foreach ($detalle as $row): ?>
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
				<td class="derecha"><?php echo number_format(suma_field($detalle, 'debe'), 2) ?></td>
				<td class="derecha"><?php echo number_format(suma_field($detalle, 'haber'), 2) ?></td>
				<td></td>
			</tr>
		</tfoot>
	</table>

	<script type="text/javascript">
		window.print()
	</script>
</body>
</html>
