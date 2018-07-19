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
	public $sueldoPromedio = 0;
	
	protected $finiquitoAguinaldo     = null;
	protected $finiquitoBono          = null;
	protected $finiquitoIndenmizacion = null;
	protected $finiquitoVacaciones    = null;
	protected $finiquitoSueldo        = null;
	
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

	public function get_puesto()
	{
		return (object)$this->db->get(
			'plnpuesto', 
			['*'], 
			['id[=]' => $this->emp->idplnpuesto]
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

			if (elemento($args, 'movgasolina')) {
				$dbita['movgasolina'] = $args['movgasolina'];
			}

			if (elemento($args, 'movdepvehiculo')) {
				$dbita['movdepvehiculo'] = $args['movdepvehiculo'];
			}

			if (elemento($args, 'movotros')) {
				$dbita['movotros'] = $args['movotros'];
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
					year(fecha) as anio,
					month(fecha) as mes,
					diastrabajados,
					(sueldoordinario+sueldoextra) as total 
				FROM plnnomina
				WHERE idplnempleado = {$this->emp->id} 
				AND day(fecha) <> 15
				AND esbonocatorce = 0 
				ORDER BY fecha DESC
				LIMIT {$this->mesesCalculo}";
		
		$tmp = $this->db->query($sql)->fetchAll();

		if (isset($args['detallado'])) {
			return $tmp;
		} else {
			$promedio = 0;

			foreach ($tmp as $row) {
				$promedio += $row['sueldoordinario'];
			}

			#return ($promedio/$this->mesesCalculo);
			return ($promedio/count($tmp));
		}
	}

	public function set_sueldo_promedio()
	{
		$this->sueldoPromedio = $this->get_sueldo_promedio();
	}

	public function set_finiquito_indemnizacion()
	{
		$ingreso  = new DateTime($this->emp->ingreso);
		$baja     = new DateTime($this->emp->baja);
		$interval = $ingreso->diff($baja);
		$dias     = ($interval->format('%a')+1);
		$monto    = ($dias*($this->sueldoPromedio/365));
		
		#return ($interval->format('%a')+1);
		$this->finiquitoIndenmizacion = (object)[
			'dias'   => $dias,
			'inicio' => $this->emp->ingreso,
			'monto'  => $monto
		];
	}

	public function set_finiquito_vacaciones($args=[])
	{
		$inicio   = new DateTime($args['vacas_del']);
		$fin      = new DateTime($args['vacas_al']);
		$interval = $inicio->diff($fin);
		$dias     = (($interval->format('%a')+1)/(365/15));
		$monto    = ($dias*($this->sueldoPromedio/30));
		
		#return ($interval->format('%a')+1);
		$this->finiquitoVacaciones = (object)[
			'dias'   => $dias,
			'inicio' => $this->emp->ingreso,
			'monto'  => $monto
		];
	}

	public function set_finiquito_aguinaldo()
	{
		$sql = "SELECT DATE_FORMAT(fecha,'%Y-%m-01') as ultimo
				FROM plnnomina
				WHERE idplnempleado = {$this->emp->id} 
				AND aguinaldo > 0
				ORDER BY fecha DESC
				LIMIT 1";
		
		$tmp      = $this->db->query($sql)->fetchAll();
		$fecha    = count($tmp)>0?$tmp[0]['ultimo']:$this->emp->ingreso;
		$inicio   = new DateTime($fecha);
		$fin      = new DateTime($this->emp->baja);
		$interval = $inicio->diff($fin);
		$dias     = ($interval->format('%a')+1);
		$monto    = ($dias*($this->sueldoPromedio/365));
		
		#return ($interval->format('%a')+1);
		$this->finiquitoAguinaldo = (object)[
			'dias'   => $dias,
			'inicio' => $fecha,
			'monto'  => $monto
		];
	}

	public function set_finiquito_bono14()
	{
		$sql = "SELECT DATE_FORMAT(fecha,'%Y-%m-01') as ultimo
				FROM plnnomina
				WHERE idplnempleado = {$this->emp->id} 
				AND bonocatorce > 0
				ORDER BY fecha DESC
				LIMIT 1";
		
		$tmp      = $this->db->query($sql)->fetchAll();
		$fecha    = count($tmp)>0?$tmp[0]['ultimo']:$this->emp->ingreso;
		$inicio   = new DateTime($fecha);
		$fin      = new DateTime($this->emp->baja);
		$interval = $inicio->diff($fin);
		$dias     = ($interval->format('%a')+1);
		$monto    = ($dias*($this->sueldoPromedio/365));
		
		#return ($interval->format('%a')+1);
		# Arreglo de datos para finiquito bono 14
		$this->finiquitoBono = (object)[
			'dias'   => $dias,
			'inicio' => $fecha,
			'monto'  => $monto
		];
	}

	public function set_finiquito_sueldo($args = [])
	{
		$res = [
			'sdiario' => ($this->emp->sueldo/365),
			'bdiario' => ($this->emp->bonificacionley/365)
		];

		$dias = elemento($args, 'dias_sueldo_pagar', 0);
		
		/*$sql = "SELECT fecha, date_add(fecha, interval 1 day) as inicio
				FROM plnnomina
				WHERE idplnempleado = {$this->emp->id} 
				AND day(fecha) <> 15
				ORDER BY fecha DESC
				LIMIT 1";

		$tmp    = $this->db->query($sql)->fetchAll();
		$fecha  = new DateTime($tmp[0]['fecha']);
		$inicio = new DateTime($tmp[0]['inicio']);
		$fin    = new DateTime($this->emp->baja);*/

		if ($dias > 0) {
			$res['dias']   = $dias;
			$res['sueldo'] = ($dias*$res['sdiario']);
			$res['bono']   = ($dias*$res['bdiario']);
		} else {
			$res['dias']   = 0;
			$res['sueldo'] = 0;
			$res['bono']   = 0;
		}

		$this->finiquitoSueldo = (object)$res;
	}

	public function get_anticipos_post_baja()
	{
		$sql = "SELECT 
				    IFNULL(SUM(IFNULL(a.anticipo, 0)), 0) AS anticipos
				FROM
				    plnnomina a
				        INNER JOIN
				    plnempleado b ON b.id = a.idplnempleado AND a.fecha > b.baja
				WHERE
				    a.idplnempleado = {$this->emp->id} AND DAY(a.fecha) = 15";

		$tmp = $this->db->query($sql)->fetchAll();

		return $tmp[0]['anticipos'];
	}

	/**
	 * Antes de llamar a esta función, por favor ejecute estas otras funciones internas en el orden a continuación
	 * $this->set_meses_calculo(<meses_calculo>);
	 * $this->set_sueldo_promedio();
	 * $this->set_finiquito_indemnizacion();
	 * $this->set_finiquito_vacaciones();
	 * $this->set_finiquito_aguinaldo();
	 * $this->set_finiquito_bono14();
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_datos_finiquito($args=[])
	{
		$lugarFecha = "Guatemala, ".formatoFecha($args['fecha_egreso'],2)." de ".get_meses(formatoFecha($args['fecha_egreso'], 3))." de ".formatoFecha($args['fecha_egreso'],4);
		$empresa    = $this->get_empresa_debito();

		$texto_motivo = <<<EOT
Desde la presente fecha se dan por terminadas las relaciones de trabajo entre el señor(a) {$this->emp->nombre} {$this->emp->apellidos} y {$empresa->nomempresa}.\n
Por motivo: {$args['motivo']}.\n
Recibe en esta misma fecha todas las prestaciones a que tiene derecho según el CÓDIGO DE TRABAJO VIGENTE, como se detalla a continuación:
EOT;

		$tmp = [
			'titulo'                   => 'Finiquito Laboral',
			'lugar_fecha'              => $lugarFecha,
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
			'sueldo_promedio'          => number_format($this->sueldoPromedio, 2),
			'linea_dos_resumen'        => str_repeat("_", 90),
			'texto_prestaciones'       => 'Prestaciones',
			'texto_no_dias'            => 'No. Días',
			'texto_monto'              => 'Monto Q.',
			'indem_texto'              => '1) Indemnización por el tiempo comprendido del:',
			'indem_fechas'             => formatoFecha($this->emp->ingreso,1).' al '.formatoFecha($this->emp->baja,1),
			'indem_dias'               => $this->finiquitoIndenmizacion->dias,
			'indem_monto'              => number_format($this->finiquitoIndenmizacion->monto,2),
			'vacas_texto'              => '2) Vacaciones por el tiempo comprendido del:',
			'vacas_fechas'             => formatoFecha($args['vacas_del'],1).' al '.formatoFecha($args['vacas_al'],1),
			'vacas_dias'               => number_format($this->finiquitoVacaciones->dias,2),
			'vacas_monto'              => number_format($this->finiquitoVacaciones->monto,2),
			'aguin_texto'              => '3) Aguinaldo por el tiempo comprendido del:',
			'aguin_fechas'             => formatoFecha($this->finiquitoAguinaldo->inicio,1).' al '.formatoFecha($this->emp->baja,1),
			'aguin_dias'               => $this->finiquitoAguinaldo->dias,
			'aguin_monto'              => number_format($this->finiquitoAguinaldo->monto,2),
			'bonoc_texto'              => '4) Bono 14 por el tiempo comprendido del:',
			'bonoc_fechas'             => formatoFecha($this->finiquitoBono->inicio,1).' al '.formatoFecha($this->emp->baja,1),
			'bonoc_dias'               => $this->finiquitoBono->dias,
			'bonoc_monto'              => number_format($this->finiquitoBono->monto,2),
			'sabon_texto'              => '5) Salario y bonificación de:',
			'sabon_sdiario'            => "{$this->finiquitoSueldo->dias} días a razón de Q. ****".number_format($this->finiquitoSueldo->sdiario,2)." diarios:",
			'sabon_sueldo'             => number_format($this->finiquitoSueldo->sueldo,2),
			'sabon_bdiario'            => "{$this->finiquitoSueldo->dias} días a razón de Q. ****".number_format($this->finiquitoSueldo->bdiario,2)." diarios:",
			'sabon_bono'               => number_format($this->finiquitoSueldo->bono,2),
			'otros_texto'              => '6) Otros:',
			'otros_monto'              => number_format(0,2),
			'presta_linea'			   => str_repeat('_', 13),
			'presta_texto'			   => 'Total de Prestaciones:'
		];

		$totalPrestaciones = (
			$this->finiquitoIndenmizacion->monto+
			$this->finiquitoVacaciones->monto+
			$this->finiquitoAguinaldo->monto+
			$this->finiquitoBono->monto+
			$this->finiquitoSueldo->sueldo+
			$this->finiquitoSueldo->bono
		);

		$saldoPrestamos    = $this->get_saldo_prestamo();
		$anticiposPostBaja = $this->get_anticipos_post_baja();
		$liquidoRecibir    = ($totalPrestaciones-($saldoPrestamos+$anticiposPostBaja));

		$tmp['presta_monto']    = number_format($totalPrestaciones, 2);
		$tmp['menos_texto']     = "Menos:";
		$tmp['menos_ptexto']    = "Préstamos internos:";
		$tmp['menos_prestamos'] = number_format($saldoPrestamos, 2);
		$tmp['menos_atexto']    = "Anticipos a sueldos:";
		$tmp['menos_anticipos'] = number_format($anticiposPostBaja,2);
		$tmp['liquido_texto']   = "Líquido a recibir:";
		$tmp["liquido_linea"]   = str_repeat("_", 13);
		$tmp['liquido_monto']   = number_format($liquidoRecibir, 2);

		$ltr = new NumberToLetterConverter();
		$tmp['pie_linea']  = str_repeat('_', 90);
		$tmp['pie_texto']  = "Por lo tanto el señor(a) {$this->emp->nombre} {$this->emp->apellidos}, da por recibida a su entera satisfacción la cantidad de ".$ltr->to_word(round($liquidoRecibir,2), 'GTQ').". ( Q. ".number_format($liquidoRecibir,2)." ), y extiende a {$empresa->nomempresa}, su más amplio FINIQUITO LABORAL, por no tener ningún reclamo pendiente.";
		$tmp['pie_codigo'] = "Código: {$this->emp->id}";
		$tmp['pie_firma']  = "(f.)".str_repeat("_", 40);


		return $tmp;
	}

	public function get_empresa_debito()
	{
		$gen = new General();

		return (object)$gen->get_empresa([
			'id'  => $this->emp->idempresadebito, 
			'uno' => TRUE
		]);
	}

	public function get_datos_impresion()
	{
		$tmp = (array)$this->emp;
		$tmp['nombre'] = $this->emp->nombre . ' ' . $this->emp->apellidos;
		
		$debito = $this->get_empresa_debito();
		$tmp['empresa_debito'] = isset($debito->scalar) ? 'SIN EMPRESA' : $debito->nomempresa;

		$puesto = $this->get_puesto();
		$tmp['puesto'] = isset($puesto->scalar) ? 'S/C' : $puesto->descripcion;

		$bit = $this->get_bitacora(['uno' => true]);
		if ($bit) {
			$tmp['nota'] = $bit->movobservaciones;
		}
		
		$tmp['fecha_nacimiento'] = formatoFecha($this->emp->fechanacimiento, 1);
		$tmp['sueldo_total']     = ($this->emp->sueldo+$this->emp->bonificacionley);
		$tmp['ingreso']          = formatoFecha($this->emp->ingreso, 1);
		$tmp['baja']             = empty($this->emp->baja) ? '' : formatoFecha($this->emp->baja, 1);

		if ($this->emp->formapago == 1) {
			$tmp['formapago'] = 'QUINCENAL';
		} elseif ($this->emp->formapago == 2) {
			$tmp['formapago'] = 'MENSUAL';
		} else {
			$tmp['formapago'] = 'S/C';
		}
		

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
		$uno    = new DateTime($inicio);

		if (empty($this->emp->reingreso)) {
			$ingreso = new DateTime($this->emp->ingreso);
		} else {
			$ingreso = new DateTime($this->emp->reingreso);
		}

		if ($ingreso <= $uno) {
			$this->bonocatorcedias = 365;
			$this->bonocatorce     = $this->emp->sueldo;
		} else {
			$actual = new DateTime($fecha);
			$interval = $ingreso->diff($actual);
			$this->bonocatorcedias = ($interval->format('%a')+1);
			$this->bonocatorce     = (($this->emp->sueldo/365)*$this->bonocatorcedias);
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
		$where = ['plnbitacora.idplnempleado' => $this->emp->id];

		if (elemento($args, 'id')) {
			$where['plnbitacora.id'] = $args['id'];
		}

		$condiciones = ['AND' => $where];

		if (elemento($args, 'uno')) {
			$condiciones['LIMIT'] = 1;
		}

		$condiciones['ORDER'] = "plnbitacora.fecha DESC";

		$tmp = $this->db->select("plnbitacora", [
				'[><]usuario(b)' => ['plnbitacora.usuario' => 'id']
			], 
			[
				"plnbitacora.*",
				"b.nombre"
			],
			$condiciones
		);

		if (count($tmp) > 0) {
			if (elemento($args, 'uno')) {
				return (object)$tmp[0];
			} else {
				return $tmp;
			}
		} else {
			return FALSE;
		}
	}

	public function get_datos_movimiento($args=[])
	{
		$bit = $this->get_bitacora(['id' => $args['id'], 'uno' => true]);
		$emp = $this->get_empresa_debito();
		$ant = json_decode($bit->antes);
		$des = json_decode($bit->despues);

		return [
			'fecha'            => 'Guatemala, ' . date('d/m/Y H:i:s'),
			'movfecha' 		   => formatoFecha($bit->movfecha, 1),
			'empleado'         => $this->emp->nombre.' '.$this->emp->apellidos,
			'empresa'          => $emp->nomempresa,
			'movdescripcion'   => $bit->movdescripcion,
			'ant_sueldo'       => number_format($ant->sueldo, 2),
			'ant_bonificacion' => number_format($ant->bonificacionley, 2), 
			'ant_total'        => number_format(($ant->sueldo+$ant->bonificacionley), 2), 
			'des_sueldo'       => number_format($des->sueldo, 2), 
			'des_bonificacion' => number_format($des->bonificacionley, 2), 
			'des_total'        => number_format(($des->sueldo+$des->bonificacionley), 2), 
			'movgasolina'      => number_format($bit->movgasolina, 2), 
			'movdepvehiculo'   => number_format($bit->movdepvehiculo, 2), 
			'movotros'         => number_format($bit->movotros, 2), 
			'movobservaciones' => $bit->movobservaciones,
			'numero'           => $bit->id
		];
	}
}
