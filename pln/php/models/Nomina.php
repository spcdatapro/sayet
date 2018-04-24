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
			$sql .= "AND a.idempresa = {$args['empresa']}";
		}

		return $this->db->query($sql)->fetchAll();
	}

	public function actualizar(Array $args)
	{
		if (elemento($args, 'id')) {
			$datos = [];

			if (elemento($args, "viaticos")) {
				$datos["viaticos"] = $args["viaticos"];
			}

			if (elemento($args, "aguinaldo")) {
				$datos["aguinaldo"] = $args["aguinaldo"];
			}

			if (elemento($args, "indemnizacion")) {
				$datos["indemnizacion"] = $args["indemnizacion"];
			}

			if (elemento($args, "bonocatorce")) {
				$datos["bonocatorce"] = $args["bonocatorce"];
			}

			if (elemento($args, "vacaciones")) {
				$datos["vacaciones"] = $args["vacaciones"];
			}

			if (elemento($args, "otrosingresos")) {
				$datos["otrosingresos"] = $args["otrosingresos"];
			}

			if (elemento($args, "descisr")) {
				$datos["descisr"] = $args["descisr"];
			}

			if (elemento($args, "descotros")) {
				$datos["descotros"] = $args["descotros"];
			}

			if (elemento($args, "sueldoordinario")) {
				$datos["sueldoordinario"] = $args["sueldoordinario"];
			}

			if (elemento($args, "anticipo")) {
				$datos["anticipo"] = $args["anticipo"];
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
					$datos['descigss']        = $e->get_descingss();
					
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
					'valor' => 0
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
				]
			];
		}

		return $datos;
	}

	public function get_cabecera($args = [])
	{
		$subtitulo = $args['dia'] == 15 ? 'Anticipo Quincena # ' . (int)$args['mes'] : 'Planilla General';
		$nmes = ucwords(get_meses($args['mes']));
		
		return [
			[
				'campo' => 'titulon', 
				'valor' => 'Módulo de Planillas'
			], 
			[
				'campo' => 'subtitulo', 
				'valor' => $subtitulo
			], 
			[
				'campo' => 'mes', 
				'valor' => "del mes de {$nmes} de {$args['anio']}"
			], 
			[
				'campo' => 'tcodigot', 
				'valor' => "Código"
			], 
			[
				'campo' => 'tnombre', 
				'valor' => "Nombre"
			], 
			[
				'campo' => 'tdiastrabajadost', 
				'valor' => "DíasTrab"
			], 
			[
				'campo' => 'tsueldoot', 
				'valor' => "Sueldo O."
			],
			[
				'campo' => 'tsueldoextrat', 
				'valor' => "Sueldo E."
			],
			[
				'campo' => 'tsueldototalt', 
				'valor' => "Sueldo T."
			],
			[
				'campo' => 'tbonificaciont', 
				'valor' => "Bonifica"
			],
			[
				'campo' => 'tanticipot', 
				'valor' => "Anticipos"
			],
			[
				'campo' => 'tvacacionest', 
				'valor' => "Vacaciones"
			],
			[
				'campo' => 'tbono14t', 
				'valor' => "Bono14"
			],
			[
				'campo' => 'taguinaldot', 
				'valor' => "Aguinaldo"
			],
			[
				'campo' => 'tdevengadot', 
				'valor' => "Devengado"
			],
			[
				'campo' => 'tigsst', 
				'valor' => "IGSS"
			],
			[
				'campo' => 'tisrt', 
				'valor' => "ISR"
			],
			[
				'campo' => 'tdescprestamot', 
				'valor' => "Préstamos"
			],
			[
				'campo' => 'tdescotrost', 
				'valor' => "Otros"
			],
			[
				'campo' => 'tdescanticipot', 
				'valor' => 'Anticipos:'
			],
			[
				'campo' => 'tdeducidot', 
				'valor' => "Deducido"
			],
			[
				'campo' => 'tliquidot', 
				'valor' => "Líquido"
			],
			[
				'campo' => 'tlineat', 
				'valor' => str_repeat("_", 250)
			],
			[
				'campo' => 'tlineapiet', 
				'valor' => str_repeat("_", 250)
			], 
			[
				'campo' => 'tnopaginat', 
				'valor' => "Página No. "
			], 
			[
				'campo' => 'linea_devengados', 
				'valor' => str_repeat("_", 40) . 'Devengados' . str_repeat('_', 40)
			], 
			[
				'campo' => 'linea_devengados_total',
				'valor' => 'Total'
			],
			[
				'campo' => 'linea_deducidos',
				'valor' => str_repeat("_", 22) . 'Deducido' . str_repeat('_', 22)
			],
			[
				'campo' => 'linea_deducidos_total',
				'valor' => 'Total'
			]
		];
	}

	public function get_cabecera_igss($args = [])
	{
		$nmes = strtoupper(get_meses($args['mes']));

		return [
			[
				'campo' => 'titulo', 
				'valor' => 'INSTITUTO GUATEMALTECO DE SEGURIDAD SOCIAL'
			], 
			[
				'campo' => 'subtitulo', 
				'valor' => 'PLANILLA DE SEGURIDAD SOCIAL'
			], 
			[
				'campo' => 'mes',
				'valor' => "CORRESPONDIENTE AL MES DE {$nmes} DE {$args['anio']}"
			], 
			[
				'campo' => 'periodo', 
				'valor' => 'POR EL PERIODO DEL 01/'.$args['mes'].'/'.$args['anio'].' AL '.$args['dia'].'/'.$args['mes'].'/'.$args['anio']
			], 
			[
				'campo' => 't_razon_social',
				'valor' => 'Nombre o Razón Social:'
			],
			[
				'campo' => 't_direccion_patrono',
				'valor' => 'Dirección del Patrono:'
			],
			[
				'campo' => 't_numero_patronal',
				'valor' => 'Número Patronal:'
			],
			[
				'campo' => 't_afiliacion', 
				'valor' => "No. Afiliación"
			], 
			[
				'campo' => 't_nombre_empleado',
				'valor' => 'Nombre del Empleado'
			],
			[
				'campo' => 't_fecha_baja',
				'valor' => 'Fecha de Baja'
			],
			[
				'campo' => 't_sueldo_ordinario',
				'valor' => 'Sueldo Ordinario'
			],
			[
				'campo' => 't_sueldo_extraordinario',
				'valor' => 'Sueldo Extraordinario'
			],
			[
				'campo' => 't_sueldo_total',
				'valor' => 'Sueldo Total'
			],
			[
				'campo' => 't_igss',
				'valor' => 'IGSS'
			]
		];
	}

	public function get_firmas()
	{
		return [
			[
				'campo' => 'elaborado', 
				'valor' => 'Elaborado por: ' . str_repeat('_', 25)
			], 
			[
				'campo' => 'revisado', 
				'valor' => 'Revisado VoBo: ' . str_repeat('_', 25)
			], 
			[
				'campo' => 'autorizado', 
				'valor' => 'Autorizado VoBo: ' . str_repeat('_', 25)
			]
		];
	}
}