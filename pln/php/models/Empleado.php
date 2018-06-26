<?php

/**
* 
*/
class Empleado extends Principal
{
	public $emp;
	protected $tabla;
	protected $sueldo      = 0;
	protected $horasimple  = 1.5;
	protected $horadoble   = 2;
	protected $dtrabajados = 0;
	protected $nfecha;
	protected $ndia;
	protected $nmes;
	protected $nanio;
	protected $mesesCalculo = 0;
	protected $bonocatorce = 0;
	protected $bonocatorcedias = 0;
	
	function __construct($id = '')
	{
		parent::__construct();

		$this->tabla = 'plnempleado';

		if (!empty($id)) {
			$this->cargar_empleado($id);
		}
	}

	public function cargar_empleado($id)
	{
		$this->emp = (object)$this->db->get(
			$this->tabla, 
			['*'], 
			['id[=]' => $id]
		);
	}

	public function get_proyecto()
	{
		return (object)$this->db->get(
			'proyecto', 
			['*'], 
			['id[=]' => $this->emp->idproyecto]
		);
	}

	public function guardar($args = [])
	{
		if (is_array($args) && !empty($args)) {
			if (elemento($args, 'nombre', FALSE)) {
				$this->set_dato('nombre', $args['nombre']);
			}

			if (isset($args['apellidos'])) {
				$this->set_dato('apellidos',  elemento($args, 'apellidos'));
			}

			if (isset($args['direccion'])) {
				$this->set_dato('direccion',  elemento($args, 'direccion'));
			}

			if (isset($args['telefono'])) {
				$this->set_dato('telefono',  elemento($args, 'telefono'));
			}

			if (isset($args['correo'])) {
				$this->set_dato('correo',  elemento($args, 'correo'));
			}
			
			if (isset($args['sexo'])) {
				$this->set_dato('sexo',  elemento($args, 'sexo'));
			}
			
			if (isset($args['estadocivil'])) {
				$this->set_dato('estadocivil',  elemento($args, 'estadocivil'));
			}
			
			if (isset($args['fechanacimiento'])) {
				$this->set_dato('fechanacimiento',  elemento($args, 'fechanacimiento'));
			}
			
			if (isset($args['dpi'])) {
				$this->set_dato('dpi',  elemento($args, 'dpi'));
			}
			
			if (isset($args['extendido'])) {
				$this->set_dato('extendido',  elemento($args, 'extendido'));
			}
			
			if (isset($args['nit'])) {
				$this->set_dato('nit',  elemento($args, 'nit'));
			}
			
			if (isset($args['igss'])) {
				$this->set_dato('igss',  elemento($args, 'igss'));
			}

			if (isset($args['activo'])) {
				$this->set_dato('activo', $args['activo']);
			}

			if (isset($args['ingreso'])) {
				$this->set_dato('ingreso', elemento($args, 'ingreso', NULL));
			}

			if (isset($args['reingreso'])) {
				$this->set_dato('reingreso', elemento($args, 'reingreso', NULL));
			}

			if (isset($args['baja'])) {
				$this->set_dato('baja', elemento($args, 'baja', NULL));
			}
			
			if (elemento($args, 'idplnpuesto')) {
				$this->set_dato('idplnpuesto', $args['idplnpuesto']);
			}
			
			if (isset($args['cuentapersonal'])) {
				$this->set_dato('cuentapersonal', elemento($args, 'cuentapersonal'));
			}
			
			if (isset($args['descuentoisr'])) {
				$this->set_dato('descuentoisr', elemento($args, 'descuentoisr'));
			}
			
			if (elemento($args, 'idempresaactual')) {
				$this->set_dato('idempresaactual', $args['idempresaactual']);
			}
			
			if (isset($args['bonificacionley'])) {
				$this->set_dato('bonificacionley', elemento($args, 'bonificacionley', 0));
			}
			
			if (isset($args['sueldo'])) {
				$this->set_dato('sueldo', elemento($args, 'sueldo'));
			}
			
			if (isset($args['porcentajeigss'])) {
				$this->set_dato('porcentajeigss', elemento($args, 'porcentajeigss', 0));
			}
			
			if (elemento($args, 'formapago')) {
				$this->set_dato('formapago', $args['formapago']);
			}
			
			if (elemento($args, 'mediopago')) {
				$this->set_dato('mediopago', $args['mediopago']);
			}
			
			if (elemento($args, 'idempresadebito')) {
				$this->set_dato('idempresadebito', $args['idempresadebito']);
			}
			
			if (isset($args['cuentabanco'])) {
				$this->set_dato('cuentabanco', elemento($args, 'cuentabanco'));
			}
			
			if (elemento($args, 'idproyecto')) {
				$this->set_dato('idproyecto', $args['idproyecto']);
			}

		}

		if (!empty($this->datos)) {
			$dbita = [];

			if (elemento($args, 'movfecha')) {
				$dbita['movfecha'] = $args['movfecha'];
			}

			if (elemento($args, 'movdescripcion')) {
				$dbita['movdescripcion'] = $args['movdescripcion'];
			}

			if (elemento($args, 'movobservaciones')) {
				$dbita['movobservaciones'] = $args['movobservaciones'];
			}



			if ($this->emp) {
				$dbita['antes'] = json_encode($this->emp);

				if ($this->db->update($this->tabla, $this->datos, ["id [=]" => $this->emp->id])) {
					$this->cargar_empleado($this->emp->id);
					
					$dbita['despues'] = json_encode($this->emp);
					$this->guardar_bitacora($dbita);

					return TRUE;
				} else {
					if ($this->db->error()[0] == 0) {
						$this->set_mensaje('Nada que actualizar.');
					} else {
						$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
					}
				}
			} else {
				$lid = $this->db->insert($this->tabla, $this->datos);

				if ($lid) {
					$this->cargar_empleado($lid);

					$dbita['despues'] = json_encode($this->emp);
					$this->guardar_bitacora($dbita);

					return TRUE;
				} else {
					$this->set_mensaje('Error en la base de datos al guardar: ' . $this->db->error()[2]);
				}
			}
		} else {
			$this->set_mensaje('No hay datos que guardar o actualizar.');
		}

		return FALSE;
	}

	public function guardar_bitacora($args=[])
	{
		$args['usuario']       = $_SESSION['uid'];
		$args['idplnempleado'] = $this->emp->id;

		$this->db->insert("plnbitacora", $args);
	}

	public function agregar_archivo($args = [], $fl = [])
	{
		$this->set_dato('idplnempleado', $this->emp->id);

		if (elemento($args, 'idplnarchivotipo')) {
			$this->set_dato('idplnarchivotipo', $args['idplnarchivotipo']);
		}

		if (elemento($args, 'vence')) {
			$this->set_dato('vence', $args['vence']);
		}
		
		if (isset($fl['archivo'])) {
			$base = "archivos/emp/{$this->emp->id}/" . date('Y-m-d');
			$ruta = dirname(dirname(__DIR__)) . "/{$base}";
			$nom  = $fl['archivo']['name'];

			if (!file_exists($ruta)) {
				mkdir($ruta, 0700, true);
			}

			$ruta .= "/{$nom}";

			move_uploaded_file($fl['archivo']['tmp_name'], $ruta);

			$link = "/sayet/pln/{$base}/{$nom}";

			$this->set_dato('ruta', $link);
			$this->set_dato('nombre', $nom);
		}

		$lid = $this->db->insert('plnarchivo', $this->datos);

		if ($lid) {
			return TRUE;
		} else {
			$this->set_mensaje('Error en la base de datos al agregar archivo: ' . $this->db->error()[2]);
		}
		return FALSE;
	}

	public function get_archivos()
	{
		return $this->db->select(
			'plnarchivo', 
			['*'], 
			['idplnempleado[=]' => $this->emp->id]
		);
	}

	public function actualizar_prosueldo(Array $args)
	{
		$datos = [
			"enero"      => elemento($args, "enero", 0), 
			"febrero"    => elemento($args, "febrero", 0), 
			"marzo"      => elemento($args, "marzo", 0), 
			"abril"      => elemento($args, "abril", 0), 
			"mayo"       => elemento($args, "mayo", 0), 
			"junio"      => elemento($args, "junio", 0), 
			"julio"      => elemento($args, "julio", 0), 
			"agosto"     => elemento($args, "agosto", 0), 
			"septiembre" => elemento($args, "septiembre", 0), 
			"octubre"    => elemento($args, "octubre", 0), 
			"noviembre"  => elemento($args, "noviembre", 0), 
			"diciembre"  => elemento($args, "diciembre", 0)
		];

		if ($this->db->update('plnprosueldo', $datos, ["AND" => ["id" => $args["id"], "idplnempleado" => $this->emp->id]])) {
			return TRUE;
		} else {
			if ($this->db->error()[0] == 0) {
				$this->set_mensaje('Nada que actualizar.');
			} else {
				$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
			}
		}

		return FALSE;
	}

	public function set_fecha($fecha)
	{
		$nstr = strtotime($fecha);
		
		$this->nfecha = $fecha;
		$this->ndia   = date('d', $nstr);
		$this->nmes   = date('m', $nstr);
		$this->nanio  = date('Y', $nstr);
	}

	public function set_sueldo()
	{
		$pro = $this->db->get(
			'plnprosueldo', 
			[get_meses($this->nmes)], 
			[
				'AND' => [
					'idplnempleado' => $this->emp->id, 
					'anio'          => $this->nanio
				]
			]
		);

		if (isset($pro['scalar'])) {
			$this->sueldo = $this->emp->sueldo;
		} else {
			$this->sueldo = ($pro[get_meses($this->nmes)]>0)?$pro[get_meses($this->nmes)]:$this->emp->sueldo;
		}
	}

	public function get_sueldo()
	{
		return $this->sueldo;
	}

	public function get_gana_dia()
	{
		return $this->sueldo/30;
	}

	public function get_bono_dia()
	{
		return $this->emp->bonificacionley/30;
	}

	public function get_gana_hora()
	{
		return $this->get_gana_dia()/8;
	}

	public function get_horas_extras_simples($args = [])
	{
		if (isset($args['horas'])) {
			return ($args['horas']*$this->get_gana_hora())*$this->horasimple;
		}
		return 0;
	}

	public function get_horas_extras_dobles()
	{
		return $this->emp->cantidad_horas_dobles*$this->get_gana_hora()*$this->horadoble;
	}

	public function get_total_horas_extras()
	{
		return $this->get_horas_extras_simples() + $this->get_horas_extras_dobles();
	}

	public function set_dias_trabajados()
	{
		$istr  = strtotime($this->emp->ingreso);
		$idia  = date('d', $istr);
		$imes  = date('m', $istr);
		$ianio = date('Y', $istr);

		if ($ianio == $this->nanio && $imes == $this->nmes) {
			if ($this->ndia > $idia) {
				$this->dtrabajados = ($this->ndia-$idia);
			}
		} else {
			if ($this->nanio >= $ianio) {
				$this->dtrabajados = $this->ndia == 15 ? 15 : 30;
			}
		}
	}

	public function get_dias_trabajados() 
	{
		return $this->dtrabajados;
	}

	public function get_sueldo_ordinario()
	{
		if ($this->dtrabajados > 0) {
			return $this->get_gana_dia()*$this->dtrabajados;
		}

		return 0;
	}

	public function get_bono_ley()
	{
		if ($this->dtrabajados > 0) {

			if ($this->ndia != 15 && $this->dtrabajados == $this->ndia) {
				return $this->emp->bonificacionley;
			}

			return $this->get_bono_dia()*$this->dtrabajados;
		}

		return 0;
	}

	/**
	 * Devuelve la primera quincena pagada si el empleado está marcado como pago quincenal
	 * @return [float]
	 */
	public function get_anticipo()
	{

		if ($this->emp->formapago == 1 && $this->ndia == 15) {

			#return round( ($this->dtrabajados * ($this->get_gana_dia() + $this->get_bono_dia())), 2);
			return (($this->sueldo+$this->emp->bonificacionley)/2);
		}

		return 0;
	}

	public function get_descanticipo()
	{
		if ($this->ndia != 15 && $this->emp->formapago == 1) {
			$ant = $this->db->get(
				'plnnomina', 
				['anticipo'], 
				[
					'AND' => [
						'idplnempleado' => $this->emp->id, 
						'fecha'         => "{$this->nanio}-{$this->nmes}-15"
					]
				]
			);

			if (!isset($ant['scalar'])) {
				return $ant['anticipo'];
			}
		}

		return 0;
	}

	public function get_descprestamo($args=[])
	{
		$prest = ['prestamo' => [], 'total' => 0];

		if ($this->ndia != 15) {
			$prestamos = $this->db->select(
				"plnprestamo", 
				['id', 'cuotamensual'], 
				[
					'AND' => [
						'idplnempleado[=]' => $this->emp->id,
						"finalizado[=]" => 0,
						"iniciopago[<=]" => $this->nfecha
					]
				]
			);

			if (count($prestamos) > 0) {
				foreach ($prestamos as $row) {
					$ant = $this->db->get(
						"plnpresnodesc",
						['*'],
						[
							'AND' => [
								"fecha" => $this->nfecha,
								"idplnprestamo" => $row['id']
							]
						]
					);

					if ($ant && count($ant) > 0 && !isset($ant['scalar'])) {
						continue;
					} else {
						$pr = new Prestamo($row['id']);
						$saldo = $pr->get_saldo($args);
						$cuota = (($pr->pre->cuotamensual < $saldo)?$pr->pre->cuotamensual:$saldo);
						
						$prest['prestamo'][] = [
							'id'    => $pr->pre->id,
							'cuota' => $cuota
						];

						#$prest['prestamo'][] =  $row;
						$prest['total']     += $cuota;
						#$prest['total']     += (($row['cuotamensual'] <= $saldo)?$row['cuotamensual']:$saldo);
					}
				}
			}
		}

		return $prest;
	}

	public function get_descingss($args = [])
	{
		return round(($this->emp->porcentajeigss/100) * ($this->sueldo+$args['sueldoextra']), 2);
	}

	public function get_saldo_prestamo($args = [])
	{
		$saldo = 0;

		$tmp = $this->db->select(
			'plnprestamo', 
			['id'],
			[
				'AND' => [
					'idplnempleado' => $this->emp->id, 
					'finalizado'    => 0
				]
			]
		);

		if ($tmp) {
			foreach ($tmp as $row) {
				$pre    = new Prestamo($row['id']);
				$saldo += $pre->get_saldo($args);
			}
		}

		return $saldo;
	}

	public function set_meses_calculo($meses)
	{
		$this->mesesCalculo = $meses;
	}

	public function get_sueldo_promedio($args = [])
	{
		$sql = "SELECT 
					sueldoordinario,
					sueldoextra,
					fecha,
					(sueldoordinario+sueldoextra) as total 
				FROM plnnomina
				WHERE idplnempleado = {$this->emp->id} 
				AND day(fecha) <> 15
				AND terminada = 1
				ORDER BY fecha DESC
				LIMIT {$this->mesesCalculo}";
		
		$tmp = $this->db->query($sql)->fetchAll();

		if (isset($args['detallado'])) {
			return $tmp;
		} else {
			$promedio = 0;

			foreach ($tmp as $row) {
				$promedio += $row['total'];
			}

			return ($promedio/$this->mesesCalculo);
		}
	}

	private function get_finiquito_indemnizacion_dias()
	{
		$ingreso  = new DateTime($this->emp->ingreso);
		$baja     = new DateTime($this->emp->baja);
		$interval = $ingreso->diff($baja);
		
		return ($interval->format('a')+1);
	}

	private function get_finiquito_indemnizacion_monto()
	{
		$dias = $this->get_finiquito_indemnizacion_dias();
		$diasPromedio = ($this->get_sueldo_promedio()/30);

		return ($dias*$diasPromedio);
	}

	public function get_finiquito_vacaciones_dias($args=[])
	{
		$inicio  = new DateTime($args['vacas_del']);
		$fin = new DateTime($args['vacas_al']);
		$interval = $inicio->diff($fin);
		
		return ($interval->format('a')+1);
	}

	public function get_finiquito_vacaciones_monto($args = [])
	{
		$dias = $this->get_finiquito_vacaciones_dias($args);
		$diasPromedio = ($this->get_sueldo_promedio()/30);

		return ($dias*$diasPromedio);
	}

	public function get_finiquito_aguinaldo_dias()
	{
		$sql = "SELECT DATE_FORMAT(fecha,'%Y-%m-01') as ultimo
				FROM plnnomina
				WHERE idplnempleado = {$this->emp->id} 
				AND aguinaldo > 0
				AND terminada = 1
				ORDER BY fecha DESC
				LIMIT 1";
		
		$tmp = $this->db->query($sql)->fetchAll();

		$inicio  = new DateTime($tmp[0]['ultimo']);
		$fin = new DateTime($this->emp->baja);
		$interval = $inicio->diff($fin);
		
		return ($interval->format('a')+1);
	}

	public function get_finiquito_aguinaldo_monto()
	{
		$dias = $this->get_finiquito_aguinaldo_dias();
		$diasPromedio = ($this->get_sueldo_promedio()/30);

		return ($dias*$diasPromedio);
	}

	public function get_finiquito_bono14_dias()
	{
		$sql = "SELECT DATE_FORMAT(fecha,'%Y-%m-01') as ultimo
				FROM plnnomina
				WHERE idplnempleado = {$this->emp->id} 
				AND bonocatorce > 0
				AND terminada = 1
				ORDER BY fecha DESC
				LIMIT 1";
		
		$tmp = $this->db->query($sql)->fetchAll();

		$inicio  = new DateTime($tmp[0]['ultimo']);
		$fin = new DateTime($this->emp->baja);
		$interval = $inicio->diff($fin);
		
		return ($interval->format('a')+1);
	}


	public function get_finiquito_bono14_monto()
	{
		$dias = $this->get_finiquito_bono14_dias();
		$diasPromedio = ($this->get_sueldo_promedio()/30);

		return ($dias*$diasPromedio);
	}

	public function get_datos_finiquito($args=[])
	{

		$lugar_fecha = "Guatemala, ".formatoFecha($args['fecha_egreso'],2)." de ".get_meses(formatoFecha($args['fecha_egreso'], 3))." de ".formatoFecha($args['fecha_egreso'],4);

		$gen = new General();
		$empresa = $gen->get_empresa(['empresa' => $this->emp->idempresadebito, 'uno' => TRUE]);

		$texto_motivo = <<<EOT
Desde la presente fecha se dan por terminadas las relaciones de trabajo entre el señor(a) {$this->emp->nombre} {$this->emp->apellidos} y {$empresa['nomempresa']}.\n
Por motivo: {$args['motivo']}.\n
Recibe en esta misma fecha todas las prestaciones a que tiene derecho según el CÓDIGO DE TRABAJO VIGENTE, como se detalla a continuación:
EOT;

		return [
			'titulo'                   => 'Finiquito Laboral',
			'lugar_fecha'              => $lugar_fecha,
			'texto_motivo'             => $texto_motivo,
			'linea_uno_resumen'        => str_repeat("_", 90),
			'fecha_ingreso_etiqueta'   => 'Fecha de Ingreso:',
			'fecha_ingreso'            => formatoFecha($this->emp->ingreso,1),
			'fecha_egreso_etiqueta'    => 'Fecha de Egreso:',
			'fecha_egreso'             => formatoFecha($args['fecha_egreso'],1),
			'sueldo_etiqueta'          => 'Sueldo Mensual:',
			'sueldo'                   => number_format($this->emp->sueldo, 2),
			'bonificacion_etiqueta'    => 'Bonificación:',
			'bonificacion'             => number_format($this->emp->bonificacionley, 2),
			'total_etiqueta'           => 'Total:',
			'total_linea'              => str_repeat('_', 10),
			'total'                    => number_format($this->emp->sueldo + $this->emp->bonificacionley, 2),
			'sueldo_promedio_etiqueta' => "Sueldo Promedio:\nsobre {$args['meses_calculo']} meses",
			'sueldo_promedio'          => number_format($this->get_sueldo_promedio(['meses' => $args['meses_calculo']]), 2),
			'linea_dos_resumen'        => str_repeat("_", 90),
			'texto_prestaciones'       => 'Prestaciones',
			'texto_no_dias'            => 'No. Días',
			'texto_monto'              => 'Monto Q.',
			'indem_texto'              => 'Indemnización por el tiempo comprendido del:',
			'indem_fechas'             => ' al ',
			'indem_dias'               => $this->get_finiquito_indemnizacion_dias(),
			'indem_monto'              => $this->get_finiquito_indemnizacion_monto(['meses' => $args['meses_calculo']]),
			'vacas_texto'              => 'Vacaciones por el tiempo comprendido del:',
			'vacas_fechas'             => ' al ',
			'vacas_dias'               => $this->get_finiquito_vacaciones_dias($args),
			'vacas_monto'              => $this->get_finiquito_vacaciones_monto($args),
			'aguin_texto'              => 'Aguinaldo por el tiempo comprendido del:',
			'aguin_fechas'             => ' al ',
			'aguin_dias'               => $this->get_finiquito_aguinaldo_dias(),
			'aguin_monto'              => $this->get_finiquito_aguinaldo_monto(),
			'bonoc_texto'              => 'Bono 14 por el tiempo comprendido del:',
			'bonoc_fechas'             => ' al ',
			'bonoc_dias'               => $this->get_finiquito_bono14_dias(),
			'bonoc_monto'              => $this->get_finiquito_bono14_monto(),
			'sabon_texto'              => 'Salario y bonificación de:'
		];
	}

	public function get_empresa_debito()
	{
		$gen = new General();

		return (object)$gen->get_empresa([
			'empresa' => $this->emp->idempresadebito, 
			'uno' => TRUE
		]);
	}

	public function get_datos_impresion()
	{
		$debito = $this->get_empresa_debito();

		$tmp = (array)$this->emp;
		$tmp['nombre'] = $this->emp->nombre . ' ' . $this->emp->apellidos;
		
		if (isset($debito->scalar)) {
			$tmp['empresa_debito'] = 'SIN EMPRESA';
		} else {
			$tmp['empresa_debito'] = $debito->nomempresa;
		}
		
		$tmp['fecha_nacimiento'] = formatoFecha($this->emp->fechanacimiento, 1);
		$tmp['ingreso'] = formatoFecha($this->emp->ingreso, 1);
		$tmp['baja'] = empty($this->emp->baja) ? '' : formatoFecha($this->emp->baja, 1);

		return $tmp;
	}

	public function set_bonocatorce()
	{
		$this->set_meses_calculo(6);

		if ($this->ndia == 15) {
			$fecha = date('Y-m-t', strtotime('-1 months', strtotime($this->nfecha))); 
		} else {
			$fecha = $this->nfecha;
		}

		$pasado = date('Y-m-t', strtotime('-1 year', strtotime($fecha)));
		$inicio = date('Y-m-d', strtotime('+1 days', strtotime($pasado)));

		$uno     = new DateTime($inicio);
		$ingreso = new DateTime($this->emp->ingreso);

		if ($ingreso <= $uno) {
			$this->bonocatorcedias = 365;
			$this->bonocatorce     = $this->get_sueldo_promedio();
		} else {
			$actual = new DateTime($fecha);
			$interval = $ingreso->diff($actual);
			$this->bonocatorcedias = $interval->format('%a');
			$this->bonocatorce     = (($this->get_sueldo_promedio()/365)*$this->bonocatorcedias);
		}
	}

	public function get_bonocatorce()
	{
		return $this->bonocatorce;
	}

	public function get_bonocatorce_dias()
	{
		return $this->bonocatorcedias;
	}

	public function get_bitacora($args=[])
	{
		$where = ['idplnempleado' => $this->emp->id];

		return $this->db->select("plnbitacora", [
				'[><]usuario(b)' => ['plnbitacora.usuario' => 'id']
			], 
			[
				"plnbitacora.*",
				"b.nombre"
			],
			[
				'AND' => $where
				
			]
		);
	}
}