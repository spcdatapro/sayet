<?php

/**
* Clase para  realizar búsquedas
*/
class Nomina extends Principal
{
	
	function __construct()
	{
		parent::__construct();
	}

	public function terminar_planilla($args=[])
	{
		$condiciones = [
			'fecha'     => $args['fecha'],
			'terminada' => 0
		];

		if (elemento($args, 'empresa')) {
			$condiciones['idempresa'] = $args['empresa'];
		}

		if ($this->db->update("plnnomina", ['terminada' => 1], ['AND' => $condiciones])) {
			$this->finalizar_prestamos($condiciones);
			return TRUE;
		} else {
			if ($this->db->error()[0] == 0) {
				$this->set_mensaje('Nada que actualizar.');
			} else {
				$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
			}
		}
	}

	public function finalizar_prestamos($args=[])
	{
		$condiciones = [
			'fecha'     => $args['fecha'],
			'terminada' => 1
		];

		if (elemento($args, 'empresa')) {
			$condiciones['empresa'] = $args['empresa'];
		}

		$nominas = $this->db->select(
			"plnnomina", 
			['id', 'descprestamo'], 
			['AND' => $condiciones]
		);

		if (count($nominas) > 0) {
			foreach ($nominas as $row) {
				$descprestamo = $this->db->select(
					"plnpresnom", 
					['id', 'idplnprestamo', 'monto'],
					['idplnnomina' => $row['id']]
				);

				if (count($descprestamo) > 0) {
					foreach ($descprestamo as $key => $value) {
						$pre = new Prestamo($value['idplnprestamo']);
						$pre->finalizar();
					}
				}
			}
		}
	}

	public function actualizar_saldo_prestamos($args=[])
	{
		$nomina = (object)$this->db->get(
			"plnnomina", 
			['id', 'descprestamo'], 
			['id' => $args['idplnnomina']]
		);

		$descprestamo = $this->db->select(
			"plnpresnom", 
			['id', 'idplnprestamo', 'monto'],
			['idplnnomina' => $args['idplnnomina']]
		);

		if (count($descprestamo) > 0) {
			$monto = $nomina->descprestamo;
			$descuento = totalCampo($descprestamo, 'monto');

			if ($monto != $descuento) {
				foreach ($descprestamo as $key => $value) {
					$pr = new Prestamo($value['idplnprestamo']);
					$saldo = $pr->get_saldo(['sin_idplnnomina' => $nomina->id]);
					$cuota = $saldo < $pr->pre->cuotamensual ? $saldo : $pr->pre->cuotamensual;
					
					if ($cuota <= $monto) {
						$nuevo = $cuota;
						$monto -= $cuota;
					} else {
						$nuevo = $monto;
						$monto = 0;
					}

					$this->db->update(
						'plnpresnom', 
						['monto' => $nuevo], 
						["id" => $value['id']]
					);

					$pr->guardar(['saldo' => $pr->get_saldo()]);
				}
			}
		}
	}

	public function buscar($args=[])
	{
		$fecha = $args['fecha'];
		$dia   = date('d', strtotime($fecha));

		$this->limpiar_nomina($args);

		$condicion = ['activo' => 1];

		if (elemento($args, 'empresa')) {
			$condicion["idempresadebito"] = $args['empresa'];
		}

		$tmp = $this->db->select(
			'plnempleado', 
			['*'],
			['AND' => $condicion]
		);

		foreach ($tmp as $row) {
			$where = [
				'idplnempleado' => $row['id'], 
				'idempresa'     => $row['idempresadebito'], 
				'fecha'         => $fecha
			];

			$ex = (object)$this->db->get(
				'plnnomina', 
				['*'], 
				['AND' => $where]
			);

			if (isset($ex->scalar)) {
				$datos = [
					'idplnempleado' => $row['id'], 
					'idempresa'     => $row['idempresadebito'], 
					'idproyecto'    => $row['idproyecto'],
					'fecha'         => $fecha
				];

				$this->db->insert('plnnomina', $datos);
			}
		}

		$sql = "SELECT 
				    a.*, 
				    CONCAT(ifnull(b.nombre,''), ' ', ifnull(b.apellidos,'')) AS nempleado, 
				    b.formapago, 
				    b.bonificacionley, 
				    b.porcentajeigss
				FROM
				    plnnomina a
				        JOIN
				    plnempleado b ON b.id = a.idplnempleado
				WHERE
				    a.fecha = '{$fecha}' AND b.activo = 1 ";

		if (elemento($args, 'empresa')) {
			$sql .= "AND a.idempresa = {$args['empresa']} ";
		}

		$sql .= "order by b.nombre ASC";

		return $this->db->query($sql)->fetchAll();
	}

	public function get_saldo_prestamos($args=[])
	{
		$saldo = 0;

		$descprestamo = $this->db->select(
			"plnpresnom", 
			['*'],
			['idplnnomina' => $args['idplnnomina']]
		);

		if (count($descprestamo) > 0) {
			foreach ($descprestamo as $key => $value) {
				$pre = new Prestamo($value['idplnprestamo']);
				$saldo += $pre->get_saldo(['sin_idplnnomina' => $args['idplnnomina']]);
			}
		}

		return $saldo;
	}

	public function get_registro($id)
	{
		$sql = "SELECT 
				    a.*, 
				    CONCAT(ifnull(b.nombre,''), ' ', ifnull(b.apellidos,'')) AS nempleado, 
				    b.formapago, 
				    b.bonificacionley, 
				    b.porcentajeigss
				FROM
				    plnnomina a
				        JOIN
				    plnempleado b ON b.id = a.idplnempleado
				WHERE a.id = {$id}";

		return $this->db->query($sql)->fetchAll()[0];
	}

	public function actualizar(Array $args)
	{
		if (elemento($args, 'id')) {
			$nom = (object)$this->get_registro($args['id']);

			if ($this->verificar_planilla_cerrada(['fecha' => $nom->fecha])) {
				$this->set_mensaje('Esta planilla se encuentra cerrada, no puedo editar datos. Por favor verifique que tenga el período abierto.');
				return false;
			} else {
				$mpld = new Empleado($nom->idplnempleado);
				$mpld->set_sueldo();

				$datos = [];

				if (isset($args['viaticos'])) {
					$datos["viaticos"] = elemento($args, "viaticos", 0);
				}

				if (isset($args["aguinaldo"])) {
					$datos["aguinaldo"] = elemento($args, "aguinaldo", 0);
				}

				if (isset($args["indemnizacion"])) {
					$datos["indemnizacion"] = elemento($args, "indemnizacion", 0);
				}

				if (isset($args["bonocatorce"])) {
					$datos["bonocatorce"] = elemento($args, "bonocatorce", 0);
				}

				if (isset($args["vacaciones"])) {
					$datos["vacaciones"] = elemento($args, "vacaciones", 0);
				}

				if (isset($args["otrosingresos"])) {
					$datos["otrosingresos"] = elemento($args, "otrosingresos", 0);
				}

				if (isset($args["descisr"])) {
					$datos["descisr"] = elemento($args, "descisr", 0);
				}

				if (isset($args["descigss"])) {
					$datos["descigss"] = elemento($args, "descigss", 0);
				}

				if (isset($args["descotros"])) {
					$datos["descotros"] = elemento($args, "descotros", 0);
				}

				if (isset($args["sueldoordinario"])) {
					$datos["sueldoordinario"] = elemento($args, "sueldoordinario", 0);
				}

				if (isset($args["anticipo"])) {
					$datos["anticipo"] = elemento($args, "anticipo", 0);
				}

				if (isset($args["horasmes"])) {
					$datos["horasmes"]      = elemento($args, "horasmes", 0);
					$datos["horasmesmonto"] = $mpld->get_horas_extras_simples(['horas' => $datos['horasmes']]);
				}

				if (isset($args["hedcantidad"])) {
					$datos["hedcantidad"] = elemento($args, "hedcantidad", 0);
					$datos["hedmonto"]    = $mpld->get_horas_extras_dobles(['horas' => $datos['hedcantidad']]);
				}

				/*if (isset($args["sueldoextra"])) {
					$datos["sueldoextra"] = elemento($args, "sueldoextra", 0);
				}*/

				if (isset($args["descprestamo"])) {
					$saldoPrestamos = $this->get_saldo_prestamos(['idplnnomina' => $args['id']]);
					$datos["descprestamo"] = ((elemento($args, "descprestamo", 0) > $saldoPrestamos)?$saldoPrestamos:$args['descprestamo']);
				}

				if (!empty($datos)) {
					if ($this->db->update("plnnomina", $datos, ["id" => $args['id']])) {
						$this->actualizar_saldo_prestamos(['idplnnomina' => $args['id']]);
						return $this->get_registro($args['id']);
					} else {
						if ($this->db->error()[0] == 0) {
							$this->set_mensaje('Nada que actualizar.');
						} else {
							$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
						}
					}
				} else {
					$this->set_mensaje("Nada que actualizar");
				}
			}
		} else {
			$this->set_mensaje("Faltan datos obligatorios.");
		}

		return FALSE;
	}

	/**
	 * Devuelve true si el período esté cerrado o no está creado
	 * @param  array  $args [description]
	 * @return [bool]       [description]
	 */
	public function verificar_planilla_cerrada($args=[])
	{
		$periodo = $this->db->get("plnperiodo", ['*'], ['fin' => $args['fecha']]);

		if ($periodo) {
			if (isset($periodo['scalar'])) {
				return true;
			} else if ($periodo['cerrado'] == 1) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	public function generar(Array $args)
	{
		if ($this->verificar_planilla_cerrada($args)) {
			$this->set_mensaje('Esta planilla se encuentra cerrada, no puedo editar datos. Por favor verifique que tenga el período abierto.');
			return false;
		} else {
			$fecha = $args['fecha'];
			$anio  = date('Y', strtotime($fecha));
			$mes   = date('m', strtotime($fecha));
			$dia   = date('d', strtotime($fecha));
			
			$test  = $this->buscar($args);

			if (count($test) > 0) {
				foreach ($test as $row) {
					$e = new Empleado($row['idplnempleado']);
					$e->set_fecha($fecha);
					$e->set_sueldo();
					$e->set_dias_trabajados();

					$datos = [
						'horasmes'      => 0,
						'horasmesmonto' => 0,
						'hedcantidad'   => 0,
						'hedmonto'      => 0
					];

					# Solo deja calcular bono el 15 de la primera quincena
					if (isset($args['bono14']) && $args['bono14'] == 'true' && $mes == 7 && $dia == 15) {
						$e->set_bonocatorce();
						
						$datos['bonocatorce']            = $e->get_bonocatorce();
						$datos['bonocatorcedias']        = $e->get_bonocatorce_dias();
						$datos['esbonocatorce']          = 1;
						$datos['sueldoordinarioreporte'] = $e->get_sueldo();
					} else {
						$datos['bonocatorce']            = 0;
						$datos['bonocatorcedias']        = 0;
						$datos['esbonocatorce']          = 0;
						$datos['sueldoordinarioreporte'] = 0;
					}

					# Solo deja calcular bono el 15 de la primera quincena
					if (isset($args['aguinaldo']) && $args['aguinaldo'] == 'true') {
						$e->set_aguinaldo();
						
						$datos['aguinaldo']              = $e->aguinaldoMonto;
						$datos['aguinaldodias']          = $e->aguinaldoDias;
						$datos['sueldoordinarioreporte'] = $e->get_sueldo();
					} else {
						$datos['aguinaldo']              = 0;
						$datos['aguinaldodias']          = 0;
						$datos['sueldoordinarioreporte'] = 0;
					}

					# Pago cada quincena
					if ($dia == 15) {
						if ($e->emp->formapago == 1) {
							$datos['anticipo']  = $e->get_anticipo();
						}

						$datos['diastrabajados']  = 0;
						$datos['descanticipo']    = 0;
						$datos['bonificacion']    = 0;
						$datos['sueldoordinario'] = 0;
						$datos['descisr']		  = 0;
						$datos['sueldoextra'] 	  = 0;
						$datos['descigss']        = 0;
					} else {
						$datos['descanticipo']    = $e->get_descanticipo();
						$datos['bonificacion']    = $e->get_bono_ley();
						$datos['sueldoordinario'] = $e->get_sueldo();
						$datos['diastrabajados']  = $e->get_dias_trabajados();
						$datos['descisr']		  = $e->emp->descuentoisr;
						$datos['descigss']        = $e->get_descingss();
						
						$prest = $e->get_descprestamo(['sin_idplnnomina' => $row['id']]);
						
						$datos['descprestamo'] = $prest['total'];

						foreach ($prest['prestamo'] as $prestamo) {
							$tmp = (object)$this->db->get(
								"plnpresnom", 
								['*'], 
								[
									'AND' => [
										'idplnprestamo' => $prestamo['id'], 
										'idplnnomina'   => $row['id']
									]
								]
							);

							if (isset($tmp->scalar)) {
								$this->db->insert(
									"plnpresnom", 
									[
										'idplnprestamo' => $prestamo['id'],
										'idplnnomina'   => $row['id'],
										'monto'         => $prestamo['cuota']
									]
								);
							} else {
								$this->db->update(
									"plnpresnom", 
									['monto' => $prestamo['cuota']],
									['id' => $tmp->id]
								);
							}
						}
					}

					$this->db->update("plnnomina", $datos, [
						'AND' => [
							"id"        => $row['id'], 
							'terminada' => 0
						]
					]);
				}
				
				return TRUE;
			} else {
				$this->set_mensaje("Nada que generar, por favor verifique que tenga empleados activos.");
			}

			return FALSE;
		}
	}

	public function limpiar_nomina($args=[])
	{
		if (elemento($args, 'fecha')) {
			$where = [
				'plnnomina.fecha'     => $args['fecha'],
				'plnnomina.terminada' => 0
			];

			/*if (isset($args['bono14']) && $args['bono14'] != 'false') {
				$where['plnnomina.esbonocatorce'] = 1;
			} else {
				$where['plnnomina.esbonocatorce'] = 0;
			}*/

			$tmp = $this->db->select("plnnomina", [
					'[><]plnempleado(b)' => ['plnnomina.idplnempleado' => 'id']
				], 
				[
					"plnnomina.id",
					"plnnomina.idempresa",
					"plnnomina.idplnempleado",
					"plnnomina.esbonocatorce",
					"plnnomina.fecha",
					"plnnomina.idproyecto(proyecto)",
					"b.idempresadebito",
					"b.idproyecto",
					"b.activo"
				],
				[
					'AND'   => $where,
					'ORDER' => "plnnomina.idplnempleado ASC"
				]
			);

			$anterior = null;

			foreach ($tmp as $row) {
				$eliminar = false;

				if (
					($row['activo'] == 0) || 
					($row["idempresa"] != $row["idempresadebito"]) || 
					($row["idproyecto"] != $row["proyecto"])
				) {
					$eliminar = true;
				}

				if ($anterior !== null && $eliminar === false) {
					/*if (
						$anterior["idplnempleado"] === $row["idplnempleado"] && 
						$anterior["esbonocatorce"] === $row["esbonocatorce"] 
					) {
						$eliminar = true;
					}*/
					if ($anterior["idplnempleado"] === $row["idplnempleado"]) {
						$eliminar = true;
					}
				}

				if ($eliminar) {
					$this->db->delete("plnpresnom", [
						'AND' => [
							'idplnnomina' => $row['id'],
							'id[>]'       => 0
						]
					]);

					$this->db->delete("plnnomina", ['id' => $row['id']]);
				} else {
					$anterior = $row;
				}
			}
		}
	}

	public function get_anticipos(Array $args)
	{
		$datos = [];
		$test = $this->buscar($args);

		if (count($test) > 0) {
			foreach ($test as $row) {
				$datos[] = [
					'empleado' => $row['nempleado'], 
					'anticipos' => $row['sueldoordinario'] 
				];
			}
		}
	}

	public function get_datos_recibo(Array $args)
	{
		$where = "";

		if (elemento($args, 'empleado')) {
			$where .= "AND a.idplnempleado in ({$args['empleado']}) ";
		}

		if (elemento($args, 'empresa')) {
			$where .= "AND a.idempresa in ({$args['empresa']}) ";
		}

		if ($args["fal"] == 15) {
			$where .= "AND (b.formapago = 1 or a.bonocatorce<>0 or a.aguinaldo<>0) ";
		}

		/*if (elemento($args, 'esaguinaldo', false)) {
			$where .= "AND a.aguinaldo > 0 ";
		}

		if (elemento($args, 'esbonocatorce', false)) {
			$where .= "AND a.esbonocatorce = 1 ";
		} else {
			$where .= "AND a.esbonocatorce = 0 ";
		}*/

		$sql = <<<EOT
SELECT 
    a.*, 
    b.nombre, 
    b.apellidos, 
    b.dpi, 
    b.idempresaactual, 
    b.ingreso,
    c.nombre AS nomempresa, 
    c.pigss
FROM
    plnnomina a
        JOIN
    plnempleado b ON b.id = a.idplnempleado
        LEFT JOIN
    plnempresa c ON c.id = b.idempresaactual
	where b.activo = 1 and a.fecha between '{$args["fdel"]}' and '{$args["fal"]}' 
	and a.devengado <> 0 
    {$where} order by c.nombre, b.nombre 
EOT;

		$res   = $this->db->query($sql)->fetchAll();
		$datos = [];

		foreach ($res as $row) {
			$row = (object)$row;
			$dia = date('d', strtotime($row->fecha));
			$emp = new Empleado($row->idplnempleado);

			$datos[] = [
				'vidempresa'       => $row->idempresaactual, 
				'vempresa'         => $row->nomempresa, 
				'tempresa'         => 'Empresa:',
				'titulo'           => 'RECIBO DE PAGO',
				'rango'            => 'Planilla del '.formatoFecha($args['fdel'],1).' al '.formatoFecha($args['fal'], 1),
				'templeado'        => 'Nombre:',
				'vempleado'        => "{$row->nombre} {$row->apellidos}",
				'tcodigo'          => 'Código:',
				'vcodigo'          => $row->idplnempleado,
				'tdpi'             => 'DPI:',
				'vdpi'             => $row->dpi,
				'tdevengados'      => 'DEVENGADOS',
				'tdeducidos'       => 'DEDUCIDOS', 
				'division'         => 'linea',
				'tsueldoordinario' => 'Sueldo Ordinario:',
				'vsueldoordinario' => $row->sueldoordinario,
				'thorasextras'     => 'Horas Extras:',
				'vhorasextras'     => $row->horasmes,
				'tsueldoextra'     => 'Sueldo Extra:',
				'vsueldoextra'     => $row->sueldoextra,
				'vsueldototal'     => ($row->sueldoordinario+$row->sueldoextra),
				'tbonificacion'    => 'Bonificación:',
				'vbonificacion'    => $row->bonificacion,
				'tviaticos'        => 'Viáticos:',
				'vviaticos'        => $row->viaticos,
				'totrosingresos'   => 'Otros:',
				'votrosingresos'   => $row->otrosingresos,
				'tanticipo'        => 'Anticipos:',
				'vanticipo'        => $row->anticipo,
				'tvacaciones'      => 'Vacacioness:',
				'vvacaciones'      => $row->vacaciones,
				'vbono14'          => $row->bonocatorce,
				'vbono14dias'      => $row->bonocatorcedias,
				'taguinaldo'       => 'Aguinaldo:',
				'vaguinaldo'       => $row->aguinaldo,
				'vaguinaldodias'   => $row->aguinaldodias,
				'tindemnizacion'   => 'Indemnizacion:',
				'vindemnizacion'   => $row->indemnizacion,
				'tigss'            => 'IGSS:',
				'vigss'            => $row->descigss,
				'tisr'             => 'ISR:',
				'visr'             => $row->descisr,
				'tdescanticipo'    => 'Anticipos:',
				'vdescanticipo'    => $row->descanticipo,
				'tprestamo'        => 'Préstamos:',
				'vprestamo'        => $row->descprestamo,
				'tdescotros'       => 'Otros:',
				'vdescotros'       => $row->descotros,
				'tdevengado'       => 'Total Devengado:',
				'vdevengado'       => $row->devengado,
				'tdeducido'        => 'Total Deducido:',
				'vdeducido'        => $row->deducido,
				'tliquido'         => 'Líquido a Recibir:',
				'vliquido'         => $row->liquido,
				'recprestamo'      => 'rectangulo',
				'tsaldoprestamo'   => 'Saldo de Préstamo', 
				'vsaldoprestamo'   => $emp->get_saldo_prestamo(['actual' => $args['fal']]),
				'vdiastrabajados'  => $row->diastrabajados,
				'lrecibi'          => str_repeat("_", 35) ,
				'trecibi'          => 'Recibí Conforme',
				'tbonoanual'       => 'Bonifi. anual p/trab. sector privado y público:', 
				'vbonoanual'       => 0,
				'vafiliacionigss'  => $emp->emp->igss,
				'vbaja'            => ($emp->emp->baja === NULL ? '':formatoFecha($emp->emp->baja, 1)),
				'vpigss'           => $row->pigss,
				'vfechaingreso'    => formatoFecha($row->ingreso, 1),
				'vsueldoordinarioreporte' => $row->sueldoordinarioreporte
			];
		}

		return $datos;
	}

	public function get_cabecera($args = [])
	{
		$subtitulo = $args['dia'] == 15 ? 'Anticipo Quincena # ' . (int)$args['mes'] : 'Planilla General # ' . (int)$args['mes'];
		$nmes = ucwords(get_meses($args['mes']));
		
		return [
			'titulon'                => 'Módulo de Planillas',
			'subtitulo'              => $subtitulo,
			'mes'                    => "del mes de {$nmes} de {$args['anio']}",
			'tcodigot'               => "Código",
			'tnombre'                => "Nombre",
			'tdiastrabajadost'       => "DíasTrab",
			'tsueldoot'              => "Sueldo O.",
			'tsueldoextrat'          => "Sueldo E.",
			'tsueldototalt'          => "Sueldo T.",
			'tbonificaciont'         => "Bonifica",
			'tanticipot'             => "Anticipos",
			'tvacacionest'           => "Vacaciones",
			'tbono14t'               => "Bono14",
			'taguinaldot'            => "Aguinaldo",
			'tdevengadot'            => "Devengado",
			'tigsst'                 => "IGSS",
			'tisrt'                  => "ISR",
			'tdescprestamot'         => "Préstamos",
			'tdescotrost'            => "Otros",
			'tdescanticipot'         => 'Anticipos:',
			'tdeducidot'             => "Deducido",
			'tliquidot'              => "Líquido",
			'tlineat'				 => str_repeat("_", 250),
			'tlineapiet'             => str_repeat("_", 250),
			'tnopaginat'             => "Página No. ",
			'linea_devengados'       => str_repeat("_", 40) . 'Devengados' . str_repeat('_', 40),
			'linea_devengados_total' => 'Total',
			'linea_deducidos'        => str_repeat("_", 22) . 'Deducido' . str_repeat('_', 22),
			'linea_deducidos_total'  => 'Total'
		];
	}

	public function get_cabecera_isr($args = [])
	{
		$subtitulo = $args['dia'] == 15 ? 'Anticipo Quincena # ' . (int)$args['mes'] : 'Planilla General';
		$nmes = ucwords(get_meses($args['mes']));
		
		return [
			'titulon'                => 'Módulo de Planillas',
			'subtitulo'              => "Descuento de ISR",
			'mes'                    => 'Planilla del 01/'.$args['mes'].'/'.$args['anio'].' al '.$args['dia'].'/'.$args['mes'].'/'.$args['anio'],
			'tcodigot'               => "Código",
			'tnombre'                => "Nombre",
			'tdevengadot'            => "Total Devengado",
			'tisrt'                  => "ISR",
			'tlineat'				 => str_repeat("_", 160),
			'tlineapiet'             => str_repeat("_", 160),
			'tnopaginat'             => "Página No. "
		];
	}

	public function get_cabecera_igss($args = [])
	{
		$nmes = strtoupper(get_meses($args['mes']));

		return [
			'igss_titulo'             => 'INSTITUTO GUATEMALTECO DE SEGURIDAD SOCIAL',
			'igss_subtitulo'          => 'PLANILLA DE SEGURIDAD SOCIAL',
			'igss_mes'                => "CORRESPONDIENTE AL MES DE {$nmes} DE {$args['anio']}",
			'igss_periodo'            => 'POR EL PERIODO DEL 01/'.$args['mes'].'/'.$args['anio'].' AL '.$args['dia'].'/'.$args['mes'].'/'.$args['anio'],
			't_razon_social'          => 'Nombre o Razón Social:',
			'v_razon_social'		  => $args['razon_social'],
			't_direccion_patrono'     => 'Dirección del Patrono:',
			'v_direccion_patrono'	  => $args['direccion_patrono'],
			't_numero_patronal'       => 'Número Patronal:',
			'v_numero_patronal'	      => $args['numero_patronal'],
			't_afiliacion'            => "No. Afiliación",
			't_nombre_empleado'       => 'Nombre del Empleado',
			't_fecha_baja'            => 'Fecha de Baja',
			't_sueldo_ordinario'      => 'Sueldo Ordinario',
			't_sueldo_extraordinario' => 'Sueldo Extraordinario',
			't_sueldo_total'          => 'Sueldo Total',
			't_igss'                  => 'IGSS',
			'tlineat'				  => str_repeat("_", 160)
		];
	}

	public function get_firmas()
	{
		return [
			'elaborado'  => 'Elaborado por: ' . str_repeat('_', 25),
			'revisado'   => 'Revisado VoBo: ' . str_repeat('_', 25),
			'autorizado' => 'Autorizado VoBo: ' . str_repeat('_', 25)
		];
	}

	public function get_resumen_igss($args = [])
	{
		return [
			'r_igss_concepto'           => 'Concepto',
			'r_igss_cuota_patronal'     => 'Cuota Patronal',
			'r_igss_cuota_trabajadores' => 'Cuota Trabajadores',
			'r_igss_total_pagar'        => 'Total a Pagar',
			'con_igss'                  => 'IGSS',
			'con_intecap'               => 'INTECAP',
			'con_irtra'                 => 'IRTRA',
			'con_total'                 => 'Total',
			'cp_igss'                   => number_format($args['cp_igss'], 2),
			'cp_intecap'                => number_format($args['cp_intecap'], 2),
			'cp_irtra'                  => number_format($args['cp_irtra'], 2),
			'cp_total'                  => number_format($args['cp_igss']+$args['cp_intecap']+$args['cp_irtra'], 2),
			'ct_igss'                   => number_format($args['ct_igss'], 2),
			'ct_total'                  => number_format($args['ct_igss'], 2),
			'tp_igss'                   => number_format($args['cp_igss']+$args['ct_igss'], 2),
			'tp_intecap'                => number_format($args['cp_intecap'], 2),
			'tp_irtra'                  => number_format($args['cp_irtra'], 2),
			'tp_total'                  => number_format($args['cp_igss']+$args['cp_intecap']+$args['cp_irtra']+$args['ct_igss'], 2),
			'r_igss_firma'				=> str_repeat("_", 50) . "\n(Firma del Patrono o su Representante Lega)"
		];
	}

	public function get_cabecera_bono14($args = [])
	{

		$pasado = date('Y-m-t', strtotime('-1 year', strtotime($args['fal'])));
		$fdel   = date('Y-m-d', strtotime('+1 days', strtotime($pasado)));

		return [
			'titulon'     => 'Módulo de Planillas',
			'subtitulo'   => "Listado de Bono 14",
			'mes'         => 'Período del '.formatoFecha($fdel, 1).' al '.formatoFecha($args['fal'], 1),
			'tcodigot'    => "Código",
			'tnombre'     => "Nombre del Empleado",
			'tsueldo'     => "Sueldo Mensual",
			'tingreso'    => "Fecha Ingreso",
			'tdias'		  => "Días",
			'tbono14'     => "Bono 14",
			'tlineat'     => str_repeat("_", 160),
			'tlineapiet'  => str_repeat("_", 160),
			'tnopaginat'  => "Página No. "
		];
	}

	public function get_cabecera_aguinaldo($args = [])
	{
		$anio   = formatoFecha($args['fal'], 4);
		$pasado = ($anio-1);
		$ultimo = date('t', strtotime("{$anio}-11-01"));

		return [
			'titulon'     => 'Módulo de Planillas',
			'subtitulo'   => "Lista de Aguinaldo",
			'mes'         => "Período del 01/12/{$pasado} al {$ultimo}/11/{$anio}",
			'tcodigot'    => "Código",
			'tnombre'     => "Nombre del Empleado",
			'tsueldo'     => "Sueldo Mensual",
			'tingreso'    => "Fecha Ingreso",
			'tdias'		  => "Días",
			'taguinaldot' => "Aguinaldo",
			'tlineat'     => str_repeat("_", 160),
			'tlineapiet'  => str_repeat("_", 160),
			'tnopaginat'  => "Página No. "
		];
	}
}