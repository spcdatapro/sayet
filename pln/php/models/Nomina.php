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

	public function buscar($args=[])
	{
		$fecha = $args['fecha'];
		$dia   = date('d', strtotime($fecha));

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
			$ex = (object)$this->db->get(
				'plnnomina', 
				['*'], 
				[
					'AND' => [
						'idplnempleado' => $row['id'], 
						'idempresa'     => $row['idempresadebito'], 
						'fecha'         => $fecha
					]
				]
			);

			if (isset($ex->scalar)) {
				$datos = [
					'idplnempleado' => $row['id'], 
					'idempresa'     => $row['idempresadebito'], 
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

	public function actualizar(Array $args)
	{
		if (elemento($args, 'id')) {
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
				$datos["horasmes"] = elemento($args, "horasmes", 0);
			}

			if (isset($args["sueldoextra"])) {
				$datos["sueldoextra"] = elemento($args, "sueldoextra", 0);
			}

			if (!empty($datos)) {
				if ($this->db->update("plnnomina", $datos, ["id" => $args['id']])) {
					return TRUE;
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
		} else {
			$this->set_mensaje("Faltan datos obligatorios.");
		}

		return FALSE;
	}

	public function generar(Array $args)
	{

		$fecha = $args['fecha'];
		$anio  = date('Y', strtotime($fecha));
		$mes   = get_meses(date('m', strtotime($fecha)));
		$dia   = date('d', strtotime($fecha));
		
		$test  = $this->buscar($args);

		if (count($test) > 0) {
			foreach ($test as $row) {
				$e = new Empleado($row['idplnempleado']);
				$e->set_fecha($fecha);
				$e->set_sueldo();
				$e->set_dias_trabajados();

				$datos = [];

				# Pago cada quincena
				if ($dia == 15) {
					if ($e->emp->formapago == 1) {
						$datos['anticipo']  = $e->get_anticipo();
						$datos['diastrabajados']  = 0;
					}
				} else {
					$datos['descanticipo']    = $e->get_descanticipo();
					$datos['bonificacion']    = $e->get_bono_ley();
					$datos['sueldoordinario'] = $e->get_sueldo();
					$datos['diastrabajados']  = $e->get_dias_trabajados();
					$datos['descisr']		  = $e->emp->descuentoisr;
					$datos['sueldoextra'] 	  = $e->get_horas_extras_simples(['horas' => $row['horasmes']]);
					$datos['descigss']        = $e->get_descingss(['sueldoextra' => $datos['sueldoextra']]);
					
					$prest = $e->get_descprestamo();
					
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
									'monto'         => $prestamo['cuotamensual']
								]
							);
						} else {
							$this->db->update(
								"plnpresnom", 
								['monto' => $prestamo['cuotamensual']],
								['id' => $tmp->id]
							);
						}
					}
				}

				$this->db->update("plnnomina", $datos, ["id" => $row['id']]);
			}
			
			return TRUE;
		} else {
			$this->set_mensaje("Nada que generar, por favor verifique que tenga empleados activos.");
		}

		return FALSE;
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
			$where .= "AND b.formapago = 1 ";
		}

		$sql = <<<EOT
SELECT 
    a.*, b.nombre, b.apellidos, b.dpi, b.idempresaactual, c.nomempresa
FROM
    plnnomina a
        JOIN
    plnempleado b ON b.id = a.idplnempleado
        JOIN
    empresa c ON c.id = b.idempresaactual
	where b.activo = 1 and a.fecha between '{$args["fdel"]}' and '{$args["fal"]}' 
	and a.devengado <> 0 
    {$where} order by c.nomempresa, b.nombre 
EOT;
		$res   = $this->db->query($sql)->fetchAll();
		$datos = [];

		foreach ($res as $row) {
			$row = (object)$row;
			$dia = date('d', strtotime($row->fecha));
			$emp = new Empleado($row->idplnempleado);

			$datos[] = [
				[
					'campo' => 'vidempresa', 
					'valor' => $row->idempresaactual
				], # El id de la empresa debe ir como primer arreglo, NO LO CAMBIÈS
				[
					'campo' => 'vempresa', 
					'valor' => $row->nomempresa
				], # Y el nombre de la empresa como segundo arreglo
				[
					'campo' => 'tempresa', 
					'valor' => 'Empresa:'
				],
				[
					'campo' => 'titulo', 
					'valor' => 'RECIBO DE PAGO'
				],
				[
					'campo' => 'rango', 
					'valor' => 'Planilla del '.formatoFecha($args['fdel'],1).' al '.formatoFecha($args['fal'], 1)
				],
				[
					'campo' => 'templeado', 
					'valor' => 'Nombre:'
				],
				[
					'campo' => 'vempleado', 
					'valor' => "{$row->nombre} {$row->apellidos}"
				],
				[
					'campo' => 'tcodigo', 
					'valor' => 'Código:'
				],
				[
					'campo' => 'vcodigo', 
					'valor' => $row->idplnempleado
				],
				[
					'campo' => 'tdpi', 
					'valor' => 'DPI:'
				],
				[
					'campo' => 'vdpi', 
					'valor' => $row->dpi
				],
				[
					'campo' => 'tdevengados' ,
					'valor' => 'DEVENGADOS'
				],
				[
					'campo' => 'tdeducidos', 
					'valor' => 'DEDUCIDOS'
				], 
				[
					'campo' => 'division', 
					'valor' => 'linea'
				],
				[
					'campo' => 'tsueldoordinario', 
					'valor' => 'Sueldo Ordinario:'
				],
				[
					'campo' => 'vsueldoordinario', 
					'valor' => $row->sueldoordinario
				],
				[
					'campo' => 'thorasextras', 
					'valor' => 'Horas Extras:'
				],
				[
					'campo' => 'vhorasextras', 
					'valor' => $row->horasmes
				],
				[
					'campo' => 'tsueldoextra', 
					'valor' => 'Sueldo Extra:'
				],
				[
					'campo' => 'vsueldoextra', 
					'valor' => $row->sueldoextra
				],
				[
					'campo' => 'vsueldototal', 
					'valor' => ($row->sueldoordinario+$row->sueldoextra)
				],
				[
					'campo' => 'tbonificacion', 
					'valor' => 'Bonificación:'
				],
				[
					'campo' => 'vbonificacion', 
					'valor' => $row->bonificacion
				],
				[
					'campo' => 'tviaticos', 
					'valor' => 'Viáticos:'
				],
				[
					'campo' => 'vviaticos', 
					'valor' => $row->viaticos
				],
				[
					'campo' => 'totrosingresos', 
					'valor' => 'Otros:'
				],
				[
					'campo' => 'votrosingresos', 
					'valor' => $row->otrosingresos
				],
				[
					'campo' => 'tanticipo', 
					'valor' => 'Anticipos:'
				],
				[
					'campo' => 'vanticipo', 
					'valor' => $row->anticipo
				],
				[
					'campo' => 'tvacaciones', 
					'valor' => 'Vacacioness:'
				],
				[
					'campo' => 'vvacaciones', 
					'valor' => $row->vacaciones
				],
				[
					'campo' => 'vbono14', 
					'valor' => $row->bonocatorce
				],
				[
					'campo' => 'taguinaldo', 
					'valor' => 'Aguinaldo:'
				],
				[
					'campo' => 'vaguinaldo', 
					'valor' => $row->aguinaldo
				],
				[
					'campo' => 'tindemnizacion', 
					'valor' => 'Indemnizacion:'
				],
				[
					'campo' => 'vindemnizacion', 
					'valor' => $row->indemnizacion
				],
				[
					'campo' => 'tigss', 
					'valor' => 'IGSS:'
				],
				[
					'campo' => 'vigss', 
					'valor' => $row->descigss
				],
				[
					'campo' => 'tisr', 
					'valor' => 'ISR:'
				],
				[
					'campo' => 'visr', 
					'valor' => $row->descisr
				],
				[
					'campo' => 'tdescanticipo', 
					'valor' => 'Anticipos:'
				],
				[
					'campo' => 'vdescanticipo', 
					'valor' => $row->descanticipo
				],
				[
					'campo' => 'tprestamo', 
					'valor' => 'Préstamos:'
				],
				[
					'campo' => 'vprestamo', 
					'valor' => $row->descprestamo
				],
				[
					'campo' => 'tdescotros', 
					'valor' => 'Otros:'
				],
				[
					'campo' => 'vdescotros', 
					'valor' => $row->descotros
				],
				[
					'campo' => 'tdevengado', 
					'valor' => 'Total Devengado:'
				],
				[
					'campo' => 'vdevengado', 
					'valor' => $row->devengado
				],
				[
					'campo' => 'tdeducido', 
					'valor' => 'Total Deducido:'
				],
				[
					'campo' => 'vdeducido', 
					'valor' => $row->deducido
				],
				[
					'campo' => 'tliquido', 
					'valor' => 'Líquido a Recibir:'
				],
				[
					'campo' => 'vliquido', 
					'valor' => $row->liquido
				],
				[
					'campo' => 'recprestamo', 
					'valor' => 'rectangulo'
				],
				[
					'campo' => 'tsaldoprestamo', 
					'valor' => 'Saldo de Préstamo'
				], 
				[
					'campo' => 'vsaldoprestamo', 
					'valor' => $emp->get_saldo_prestamo()
				],
				[
					'campo' => 'vdiastrabajados', 
					'valor' => $row->diastrabajados
				],
				[
					'campo' => 'lrecibi', 
					'valor' => str_repeat("_", 35) 
				],
				[
					'campo' => 'trecibi', 
					'valor' => 'Recibí Conforme'
				],
				[
					'campo' => 'tbonoanual',
					'valor' => 'Bonifi. anual p/trab. sector privado y público:'
				], 
				[
					'campo' => 'vbonoanual',
					'valor' => 0
				],
				[
					'campo' => 'vafiliacionigss',
					'valor' => $emp->emp->igss
				],
				[
					'campo' => 'vbaja',
					'valor' => ($emp->emp->baja === NULL ? '':formatoFecha($emp->emp->baja, 1))
				]
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
}