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
	protected $horasdoble  = 2;
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
	private $proyeccion = false;

	public $aguinaldoDias  = 0;
	public $aguinaldoMonto = 0;
	
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
			'*', 
			['id[=]' => $id]
		);
	}

	public function set_proyeccion($value)
	{
		$this->proyeccion = $value;
	}

	public function get_proyecto()
	{
		return (object)$this->db->get(
			'proyecto', 
			'*', 
			['id[=]' => $this->emp->idproyecto]
		);
	}

	public function get_puesto()
	{
		return (object)$this->db->get(
			'plnpuesto', 
			'*', 
			['id[=]' => $this->emp->idplnpuesto]
		);
	}

	/**
	 * Ejecutar antes de hacer la actualización
	 * para revisar diferencias entre datos
	 */
	private function revisarMostrarBitacora($args=[])
	{
		if ($this->emp->sueldo != elemento($args, "sueldo")) {
			return 1;
		}

		if ($this->emp->bonificacionley != elemento($args, 'bonificacionley')) {
			return 1;
		}

		if ($this->emp->idempresadebito != elemento($args, 'idempresadebito')) {
			return 1;
		}

		if ($this->emp->idempresaactual != elemento($args, 'idempresaactual')) {
			return 1;
		}

		if ($this->emp->ingreso != elemento($args, 'ingreso')) {
			return 1;
		}

		if ($this->emp->reingreso != elemento($args, 'reingreso')) {
			return 1;
		}

		return 0;
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

			if (isset($args['emenombre'])) {
				$this->set_dato('emenombre', $args['emenombre']);
			}

			if (isset($args['emetelefono'])) {
				$this->set_dato('emetelefono', $args['emetelefono']);
			}

			if (isset($args['emedireccion'])) {
				$this->set_dato('emedireccion', $args['emedireccion']);
			}

			if (isset($args['vacasultimas'])) {
				$this->set_dato('vacasultimas', $args['vacasultimas']);
			}

			if (isset($args['vacasgozar'])) {
				$this->set_dato('vacasgozar', $args['vacasgozar']);
			}

			if (isset($args['vacasdias'])) {
				$this->set_dato('vacasdias', $args['vacasdias']);
			}

			if (isset($args['vacasusados'])) {
				$this->set_dato('vacasusados', $args['vacasusados']);
			}

			if (isset($args['idunidad'])) {
				$this->set_dato('idunidad', $args['idunidad']);
			}
		}

		$dbita = [];

		if (elemento($args, 'movfecha')) {
			$dbita['movfecha'] = $args['movfecha'];
			$dbita['mostrar']  = 1;
		}

		if (elemento($args, 'movdescripcion')) {
			$dbita['movdescripcion'] = $args['movdescripcion'];
		}

		if (elemento($args, 'idplnmovimiento')) {
			$bus = new General();
			$mov = $bus->tipoMovimiento([
				"id" => $args["idplnmovimiento"],
				"_uno" => true
			]);
	
			$dbita["movdescripcion"] = $mov->descripcion;
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

		if (elemento($args, 'idplnmovimiento')) {
			$dbita['idplnmovimiento'] = $args['idplnmovimiento'];
		}

		if (!empty($this->datos)) {
			if ($this->emp) {
				$dbita['antes']   = json_encode($this->emp);
				# $dbita['mostrar'] = $this->revisarMostrarBitacora($this->datos);

				if ($this->db->update($this->tabla, $this->datos, ["id [=]" => $this->emp->id])) {
					$this->cargar_empleado($this->emp->id);
					
					$dbita['despues'] = json_encode($this->emp);

					$this->guardar_bitacora($dbita);
					
					return TRUE;
				} else {
					if ($this->db->error()[0] == 0) {
						if (empty($dbita)) {
							$this->set_mensaje('Nada que actualizar.');
						} else {
							$dbita['despues'] = $dbita['antes'];
							$this->guardar_bitacora($dbita);
							return TRUE;
						}
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
			if (!empty($dbita)) {
				$dbita['antes']   = json_encode($this->emp);
				$dbita['despues'] = json_encode($this->emp);

				$this->guardar_bitacora($dbita);

				$this->set_mensaje("Movimiento de personal grabado.");
			} else {
				$this->set_mensaje('No hay datos que guardar o actualizar.');
			}
		}

		return FALSE;
	}

	public function guardar_bitacora($args=[])
	{
		$tabla = "plnbitacora";

		if (elemento($args, "id")) {
			$this->db->update($tabla, $args, ["AND" => [
				"id" => $args["id"],
				"idplnempleado" => $this->emp->id
			]]);
		} else {
			$args['usuario']       = $_SESSION['uid'];
			$args['idplnempleado'] = $this->emp->id;

			$lid = $this->db->insert($tabla, $args);
		}
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
			$ruta = BASEPATH . "/pln/{$base}";
			$nom  = $fl['archivo']['name'];

			if (!file_exists($ruta)) {
				mkdir($ruta, 0700, true);
			}

			$ruta .= "/{$nom}";

			move_uploaded_file($fl['archivo']['tmp_name'], $ruta);

			$dir = basename(BASEPATH);

			$link = "/{$dir}/pln/{$base}/{$nom}";

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
			'*', 
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
		if ($this->dtrabajados == 30) {
			$this->sueldo = $this->emp->sueldo;
		} else if ($this->dtrabajados == 15) {
			$this->sueldo = round($this->emp->sueldo / 2, 2);
		} else {
			$this->sueldo = $this->get_gana_dia() * $this->dtrabajados;
		}
	}

	public function get_sueldo()
	{
		return $this->sueldo;
	}

	public function get_gana_dia()
	{
		return $this->emp->sueldo/30;
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

	public function get_horas_extras_dobles($args=[])
	{
		if (isset($args['horas'])) {

			return ($args['horas']*$this->get_gana_hora())*$this->horasdoble;
		}
		return 0;
	}

	public function set_dias_trabajados()
	{
		$pago = new DateTime($this->nfecha);
		$ingreso = new DateTime($this->getFechaIngreso());
		$ipago = new DateTime($pago->format('Y-m-01'));

		if ($ipago >= $ingreso) {
			if (empty($this->emp->baja)) {
				$this->dtrabajados = $pago->format('d') == 15 ? 15 : 30;
			} else {
				$baja = new DateTime($this->emp->baja);

				if ($baja >= $pago) {
					$this->dtrabajados = $pago->format('d') == 15 ? 15 : 30;
				} else  {
					if ($baja < $ipago) {
						$this->dtrabajados = 0;
					} else {
						$interval = $baja->diff($ipago);
						$this->dtrabajados = ($interval->days + 1);
					}
				}
			}
		} else {
			if ($ingreso > $pago) {
				$this->dtrabajados = 0;
			} else {
				if (empty($this->emp->baja)) {
					$interval = $pago->diff($ingreso);
					$this->dtrabajados = ($interval->days + 1);
				} else {
					$baja = new DateTime($this->emp->baja);
					$fin = $baja < $pago ? $baja : $pago;
					
					$interval = $fin->diff($ingreso);
					$this->dtrabajados = ($interval->days + 1);
				}
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

	public function get_descuento_isr()
	{
		if (empty($this->emp->descuentoisr)) {
			return 0;
		} else {
			// if ($this->dtrabajados == 30) {
				return $this->emp->descuentoisr;
			// } else {
				// return round(($this->emp->descuentoisr/30)*$this->dtrabajados, 2);
			// }
		}
	}

	public function get_bono_ley()
	{
		if ($this->dtrabajados > 0) {
			if ($this->dtrabajados == 30) {
				return $this->emp->bonificacionley;
			} else {
				return round($this->get_bono_dia()*$this->dtrabajados, 2);
			}
		}

		return 0;
	}

	/**
	 * Devuelve la primera quincena pagada si el empleado está marcado como pago quincenal
	 * @return [float]
	 */
	public function get_anticipo()
	{
		$anticipo = 0;

		if ($this->emp->formapago == 1 && $this->ndia == 15) {

			if ($this->dtrabajados > 0) {
				if ($this->dtrabajados == 15) {
					$sueldo = round($this->emp->sueldo/2, 2);
					$bono = round($this->emp->bonificacionley/2, 2);
				} else {
					$sueldo = round($this->get_gana_dia()*$this->dtrabajados, 2);
					$bono = round($this->get_bono_dia()*$this->dtrabajados, 2);
				}

				if (empty($this->emp->baja)) {
					$anticipo = round($sueldo + $bono, 2);
				} else {
					$isr = $this->get_descuento_isr();
					$igss = ($sueldo * ($this->emp->porcentajeigss/100));

					$anticipo = round(($sueldo-$igss-$isr)+$bono, 2);
				}
			}
		}

		return $anticipo;
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
			
			if ($ant !== false) {
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
						"iniciopago[<=]" => $this->nfecha,
						'anulado[=]' => 0
					]
				]
			);

			if (count($prestamos) > 0) {
				foreach ($prestamos as $row) {
					$ant = $this->db->get(
						"plnpresnodesc",
						'*',
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

						if ($saldo > 0) {
							$cuota = (($pr->pre->cuotamensual < $saldo)?$pr->pre->cuotamensual:$saldo);
							
							$prest['prestamo'][] = [
								'id'    => $pr->pre->id,
								'cuota' => $cuota
							];

							$prest['total'] += $cuota;
						}
					}
				}
			}
		}

		return $prest;
	}

	public function get_descingss($args = [])
	{
		return round(
			($this->emp->porcentajeigss/100) * (
				$this->sueldo
				+elemento($args,'sueldoextra',0)
				+elemento($args,'vacaciones',0)
			), 
		2);
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
		if ($this->mesesCalculo == 'ficha') {
			return isset($args['detallado']) ? false : $this->emp->sueldo;
		} else {
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

				if (count($tmp) > 0) {
					return ($promedio/count($tmp));
				} else {
					return 0;
				}
			}
		}
	}

	public function set_sueldo_promedio()
	{
		$this->sueldoPromedio = $this->get_sueldo_promedio();
	}

	public function getFechaIngreso()
	{
		if (empty($this->emp->reingreso)) {
			return $this->emp->ingreso;
		} else {
			return $this->emp->reingreso;
		}
	}

	public function set_finiquito_indemnizacion($args = [])
	{
		$ingreso = new DateTime($this->getFechaIngreso());

		if (isset($args["sin_indemnizacion"])) {
			$dias = 0;
			$monto = 0;
		} else {
			$baja     = new DateTime($this->emp->baja);
			$interval = $ingreso->diff($baja);
			$dias     = ($interval->format('%a')+1);
			$monto    = ($dias*((($this->sueldoPromedio/12)*14)/365));
		}

		$this->finiquitoIndenmizacion = (object)[
			'dias'   => $dias,
			'inicio' => $this->getFechaIngreso(),
			'monto'  => $monto
		];
	}

	public function set_finiquito_vacaciones($args=[])
	{
		if (isset($args["sin_vacaciones"])) {
			$dias  = 0;
			$monto = 0;
		} else {
			$inicio   = new DateTime($args['vacas_del']);
			$fin      = new DateTime($args['vacas_al']);
			$interval = $inicio->diff($fin);

			$dias  = (($interval->format('%a')+1)/(365/15));
			$monto = ($dias*($this->sueldoPromedio/30));
		}	
		
		$this->finiquitoVacaciones = (object)[
			'dias'   => $dias,
			'inicio' => $this->getFechaIngreso(),
			'monto'  => $monto
		];
	}

	public function set_finiquito_aguinaldo($args = [])
	{
		if (isset($args["sin_aguinaldo"])) {
			$dias  = 0;
			$monto = 0;
			$fecha = $this->getFechaIngreso();
		} else {
			$egreso = $args["fecha_egreso"];

			$sql = "SELECT IF(
						ifnull(b.reingreso, b.ingreso) > DATE_FORMAT(a.fecha,'%Y-%m-01'), 
						ifnull(b.reingreso, b.ingreso), 
						DATE_FORMAT(a.fecha,'%Y-%m-01')
					) as ultimo
					FROM plnnomina a 
					INNER JOIN plnempleado b on b.id = a.idplnempleado
					WHERE a.idplnempleado = {$this->emp->id} 
					AND a.aguinaldo > 0
					AND a.fecha < '{$egreso}'
					ORDER BY a.fecha DESC
					LIMIT 1";
			
			$tmp      = $this->db->query($sql)->fetchAll();
			$fecha    = count($tmp)>0?$tmp[0]['ultimo']:$this->getFechaIngreso();
			$inicio   = new DateTime($fecha);
			$fin      = new DateTime($this->emp->baja);
			$interval = $inicio->diff($fin);
			$dias     = ($interval->format('%a')+1);
			$monto    = ($dias*($this->sueldoPromedio/365));
		}
		
		$this->finiquitoAguinaldo = (object)[
			'dias'   => $dias,
			'inicio' => $fecha,
			'monto'  => $monto
		];
	}

	public function set_finiquito_bono14($args = [])
	{
		if (isset($args["sin_bono14"])) {
			$dias  = 0;
			$monto = 0;
			$fecha = $this->getFechaIngreso();
		} else {
			$egreso = $args["fecha_egreso"];

			$sql = "SELECT IF(
						ifnull(b.reingreso, b.ingreso) > DATE_FORMAT(a.fecha,'%Y-%m-01'), 
						ifnull(b.reingreso, b.ingreso), 
						DATE_FORMAT(a.fecha,'%Y-%m-01')
					) as ultimo
					FROM plnnomina a 
					INNER JOIN plnempleado b on b.id = a.idplnempleado
					WHERE a.idplnempleado = {$this->emp->id} 
					AND a.bonocatorce > 0
					AND a.fecha < '{$egreso}'
					ORDER BY a.fecha DESC
					LIMIT 1";
			
			$tmp      = $this->db->query($sql)->fetchAll();
			$fecha    = count($tmp)>0?$tmp[0]['ultimo']:$this->getFechaIngreso();
			$inicio   = new DateTime($fecha);
			$fin      = new DateTime($this->emp->baja);
			$interval = $inicio->diff($fin);
			$dias     = ($interval->format('%a')+1);
			$monto    = ($dias*($this->sueldoPromedio/365));
		}
		
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
			'sdiario' => ($this->emp->sueldo/30),
			'bdiario' => ($this->emp->bonificacionley/30)
		];

		$dias = elemento($args, 'dias_sueldo_pagar', 0);

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
		$puesto = $this->get_puesto();
		$proyecto = $this->get_proyecto();

		$texto_motivo = <<<EOT
Desde la presente fecha se dan por terminadas las relaciones de trabajo entre el señor(a) {$this->emp->nombre} {$this->emp->apellidos} y {$empresa->nomempresa}.\n
Por motivo: {$args['motivo']}.  \n 
Puesto: $puesto->descripcion. \n
Ubicación: $proyecto->nomproyecto. \n
Recibe en esta misma fecha todas las prestaciones a que tiene derecho según el CÓDIGO DE TRABAJO VIGENTE, como se detalla a continuación:
EOT;

		$fechaIngreso = formatoFecha($this->getFechaIngreso(),1);

		$tmp = [
			'titulo'                   => 'Finiquito Laboral',
			'lugar_fecha'              => $lugarFecha,
			'texto_motivo'             => $texto_motivo,
			'linea_uno_resumen'        => str_repeat("_", 90),
			'fecha_ingreso_etiqueta'   => 'Fecha de Ingreso:',
			'fecha_ingreso'            => $fechaIngreso,
			'fecha_egreso_etiqueta'    => 'Fecha de Egreso:',
			'fecha_egreso'             => formatoFecha($args['fecha_egreso'],1),
			'sueldo_etiqueta'          => 'Sueldo Mensual:',
			'sueldo'                   => number_format($this->emp->sueldo, 2),
			'bonificacion_etiqueta'    => 'Bonificación:',
			'bonificacion'             => number_format($this->emp->bonificacionley, 2),
			'total_etiqueta'           => 'Total:',
			'total_linea'              => str_repeat('_', 10),
			'total'                    => number_format($this->emp->sueldo + $this->emp->bonificacionley, 2),
			'sueldo_promedio'          => number_format($this->sueldoPromedio, 2),
			'linea_dos_resumen'        => str_repeat("_", 90),
			'texto_prestaciones'       => 'Prestaciones',
			'texto_no_dias'            => 'No. Días',
			'texto_monto'              => 'Monto Q.',
			'indem_texto'              => '1) Indemnización por el tiempo comprendido del:',
			'indem_fechas'             => $fechaIngreso.' al '.formatoFecha($this->emp->baja,1),
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
			'otros_texto'              => '6) Otros: ' . $args['otros_razon'],
			'otros_monto'              => number_format(elemento($args, 'otros_monto', 0),2),
			'presta_linea'             => str_repeat('_', 13),
			'presta_texto'             => 'Total de Prestaciones:',
			'tempresa'                 => 'Empresa:',
			'vempresa'                 => $empresa->nomempresa,
			'templeado'                => 'Nombre:',
			'vempleado'                => "{$this->emp->nombre} {$this->emp->apellidos}",
			'tcodigo'                  => 'Código:',
			'vcodigo'                  => $this->emp->id,
			'tdpi'                     => 'DPI:',
			'vdpi'                     => $this->emp->dpi,
			'tdevengados'              => 'DEVENGADOS',
			'tdeducidos'               => 'DEDUCIDOS', 
			'division'                 => 'linea',
			'tsueldopromedio'          => 'Sueldo Promedio:',
			'tbonificacion'            => 'Bonificación:',
			'tdiastrabajados'          => 'Días trabajados:',
			'tviaticos'                => 'Viáticos:',
			'totrosingresos'           => 'Otros:',
			'tanticipo'                => 'Anticipos:',
			'tvacaciones'              => 'Vacaciones:',
			'taguinaldo'               => 'Aguinaldo:',
			'tbonocatorce'			   => 'Bono 14',
			'tindemnizacion'           => 'Indemnizacion:',
			'tanticiposueldos'         => 'Anticipo a Sueldos:',
			'tdevengado'               => 'Total Devengado:',
			'tdeducido'                => 'Total Deducido:',
			'tliquido'                 => 'Líquido a Recibir:',
			'lrecibi'                  => str_repeat("_", 35) ,
			'trecibi'                  => 'Recibí Conforme',
			'otrosdesc_razon'          => $args['otrosdesc_razon'],
			'otrosdesc_monto'          => number_format(elemento($args, 'otrosdesc_monto', 0),2)
		];

		if ($args['meses_calculo'] == 'ficha') {
			$tmp["sueldo_promedio_etiqueta"] = "Sueldo Base:";
		} else {
			$tmp["sueldo_promedio_etiqueta"] = "Sueldo Promedio:\nsobre {$args['meses_calculo']} meses";
		}
		

		$totalPrestaciones = (
			$this->finiquitoIndenmizacion->monto+
			$this->finiquitoVacaciones->monto+
			$this->finiquitoAguinaldo->monto+
			$this->finiquitoBono->monto+
			$this->finiquitoSueldo->sueldo+
			$this->finiquitoSueldo->bono+
			elemento($args, 'otros_monto', 0)
		);

		$saldoPrestamos    = $this->get_saldo_prestamo();
		$anticiposPostBaja = 0; /* $this->get_anticipos_post_baja() */
		$valorDeducido     = ($saldoPrestamos+$anticiposPostBaja+elemento($args, 'otrosdesc_monto', 0));
		$liquidoRecibir    = ($totalPrestaciones-$valorDeducido);

		$tmp['presta_monto']    = number_format($totalPrestaciones, 2);
		$tmp['menos_texto']     = "Menos:";
		$tmp['menos_ptexto']    = "Préstamos internos:";
		$tmp['menos_prestamos'] = number_format($saldoPrestamos, 2);
		$tmp['menos_atexto']    = "Anticipos a sueldos:";
		$tmp['menos_anticipos'] = number_format($anticiposPostBaja,2);
		$tmp['liquido_texto']   = "Líquido a recibir:";
		$tmp["liquido_linea"]   = str_repeat("_", 13);
		$tmp['liquido_monto']   = number_format($liquidoRecibir, 2);
		$tmp['vdeducido']       = number_format($valorDeducido,2);

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

		$bit = $this->get_bitacora(['_uno' => true]);
		if ($bit) {
			$tmp['nota'] = $bit->movobservaciones;
		}
		
		$tmp['fecha_nacimiento'] = formatoFecha($this->emp->fechanacimiento, 1);
		$tmp['sueldo_total']     = ($this->emp->sueldo+$this->emp->bonificacionley);
		$tmp['estadocivil']      = estadoCivil($this->emp->estadocivil, $this->emp->sexo);
		$tmp['ingreso']          = formatoFecha($this->getFechaIngreso(), 1);
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

	public function set_bonocatorce($args = [])
	{
		if ($this->proyeccion || ($this->nmes == 7 && $this->ndia == 15)) {
			$this->set_meses_calculo(6);

			if ($this->proyeccion) {
				$inicio = $args["fdel"];
				$fecha = $this->nfecha;
			} else {
				if ($this->ndia == 15) {
					$fecha = date('Y-m-t', strtotime('-1 months', strtotime($this->nfecha))); 
				} else {
					$fecha = $this->nfecha;
				}

				$pasado  = date('Y-m-t', strtotime('-1 year', strtotime($fecha)));
				$inicio  = date('Y-m-d', strtotime('+1 days', strtotime($pasado)));
			}
				
			$uno     = new DateTime($inicio);
			$ingreso = new DateTime($this->getFechaIngreso());
			$actual  = new DateTime($fecha);
			$dif_general = $uno->diff($actual);

			if ($ingreso <= $uno) {
				$interval = $uno->diff($actual);
				$this->bonocatorcedias = ($interval->format('%a')+1);
			} else if ($ingreso <= $actual) {
				$interval = $ingreso->diff($actual);
				$this->bonocatorcedias = ($interval->format('%a')+1);
			}

			if (($dif_general->format('%a')+1) > 365) {
				$this->bonocatorcedias = $this->bonocatorcedias - 1;
			}

			if ($this->bonocatorcedias > 0) {
				$this->bonocatorce = $this->bonocatorcedias == 365 
				? round($this->emp->sueldo, 2)
				: round((($this->emp->sueldo/365)*($this->bonocatorcedias - 1)), 2);
			}
		}
	}

	public function set_aguinaldo($args = [])
	{
		if ($this->proyeccion || ($this->nmes == 12 && $this->ndia == 15)) {
			$this->set_meses_calculo(6);

			if ($this->proyeccion) {
				$inicio = $args["fdel"];
				$fecha = $this->nfecha;
			} else {
				if ($this->ndia == 15) {
					$fecha = date('Y-m-t', strtotime('-1 months', strtotime($this->nfecha))); 
				} else {
					$fecha = $this->nfecha;
				}

				$pasado  = date('Y-m-t', strtotime('-1 year', strtotime($fecha)));
				$inicio  = date('Y-m-d', strtotime('+1 days', strtotime($pasado)));
			}

			$uno     = new DateTime($inicio);
			$ingreso = new DateTime($this->getFechaIngreso());
			$actual  = new DateTime($fecha);

			if ($ingreso <= $uno) {
				$interval = $uno->diff($actual);
				$this->aguinaldoDias  = ($interval->format('%a')+1);
			} else if ($ingreso <= $actual) {
				$interval = $ingreso->diff($actual);
				$this->aguinaldoDias = ($interval->format('%a')+1);
			}

			if ($this->aguinaldoDias > 0) {
				$this->aguinaldoMonto = $this->aguinaldoDias == 365 
				? round($this->emp->sueldo, 2) 
				: round((($this->emp->sueldo/365)*$this->aguinaldoDias), 2);
			}
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
		$bus = new General();

		$args['idplnempleado'] = $this->emp->id;

		return $bus->getBitacora($args);
	}

	public function get_datos_movimiento($args=[])
	{
		$bit = $this->get_bitacora(['id' => $args['id'], '_uno' => true]);
		$emp = $this->get_empresa_debito();

		$tmp = [
			'fecha'            => 'Guatemala, ' . date('d/m/Y H:i:s'),
			'movfecha' 		   => (empty($bit->movfecha) ? "" : formatoFecha($bit->movfecha, 1)),
			'empleado'         => $this->emp->nombre.' '.$this->emp->apellidos,
			'empresa'          => $emp->nomempresa,
			'movdescripcion'   => $bit->movdescripcion,
			'movgasolina'      => number_format($bit->movgasolina, 2), 
			'movdepvehiculo'   => number_format($bit->movdepvehiculo, 2), 
			'movotros'         => number_format($bit->movotros, 2), 
			'movobservaciones' => $bit->movobservaciones,
			'numero'           => $bit->id
		];

		if (!empty($bit->antes)) {
			$ant = json_decode($bit->antes);
			$tmp['ant_sueldo']       = number_format($ant->sueldo, 2);
			$tmp['ant_bonificacion'] = number_format($ant->bonificacionley, 2);
			$tmp['ant_total']        = number_format(($ant->sueldo+$ant->bonificacionley), 2);
		} else {
			$tmp['ant_sueldo']       = 0;
			$tmp['ant_bonificacion'] = 0;
			$tmp['ant_total']        = 0;
		}

		if (!empty($bit->despues)) {
			$des = json_decode($bit->despues);
			$tmp['des_sueldo']       = number_format($des->sueldo, 2);
			$tmp['des_bonificacion'] = number_format($des->bonificacionley, 2);
			$tmp['des_total']        = number_format(($des->sueldo+$des->bonificacionley), 2);
		} else {
			$tmp['des_sueldo']       = 0;
			$tmp['des_bonificacion'] = 0;
			$tmp['des_total']        = 0;
		}

		return $tmp;
	}

	public function get_datos_libro_salarios($args=[])
	{
		$where = "";

		if (elemento($args, 'empleado')) {
			$where .= "AND a.idplnempleado in ({$args['empleado']}) ";
		}

		if (elemento($args, 'empresa')) {
			$where .= "AND a.idempresa in ({$args['empresa']}) ";
		}

		$sql = <<<EOT
SELECT 
	concat(month(fecha),'/',year(fecha)) as mes,
	a.idempresa,
    b.nombre AS nomempresa, 
    sum(ifnull(a.id,0)) as id,
    sum(ifnull(a.idplnempleado,0)) as idplnempleado,
    sum(ifnull(a.sueldoordinario,0)) as sueldoordinario,
    sum(ifnull(a.sueldoextra,0)) as sueldoextra,
    sum(ifnull(a.bonificacion,0)) as bonificacion,
    sum(ifnull(a.otrosingresos,0)) as otrosingresos,
    sum(ifnull(a.aguinaldo,0)) as aguinaldo,
    sum(ifnull(a.vacaciones,0)) as vacaciones,
    sum(ifnull(a.indemnizacion,0)) as indemnizacion,
    sum(ifnull(a.bonocatorce,0)) as bonocatorce,
    sum(ifnull(a.viaticos,0)) as viaticos,
    sum(ifnull(a.descigss,0)) as descigss,
    sum(ifnull(a.descanticipo,0)) as descanticipo,
    sum(ifnull(a.descisr,0)) as descisr,
    sum(ifnull(a.descprestamo,0)) as descprestamo,
    sum(ifnull(a.descotros,0)) as descotros,
    sum(if(day(a.fecha) > 15, a.devengado, 0)) + if(a.fecha_baja > 0, a.devengado, 0) as devengado,
    sum(ifnull(a.deducido,0)) as deducido,
    sum(ifnull(a.liquido,0)) as liquido,
    sum(ifnull(a.horasmes,0)) as horasmes,
    sum(ifnull(a.horasmesmonto,0)) as horasmesmonto,
    sum(ifnull(a.horasdesc,0)) as horasdesc,
    sum(ifnull(a.anticipo,0)) as anticipo,
    sum(ifnull(a.diastrabajados,0)) as diastrabajados,
    sum(ifnull(a.bonocatorcedias,0)) as bonocatorcedias,
    sum(ifnull(a.hedcantidad,0)) as hedcantidad,
    sum(ifnull(a.hedmonto,0)) as hedmonto,
    sum(ifnull(a.sueldoordinarioreporte,0)) as sueldoordinarioreporte
    from plnnomina a 
    LEFT JOIN
    plnempresa b ON b.id = a.idempresa
where a.idplnempleado = {$this->emp->id} 
and a.fecha between '{$args["fdel"]}' and '{$args["fal"]}' 
    {$where} group by month(fecha)
EOT;

		$res   = $this->db->query($sql)->fetchAll();
		$datos = [];

		foreach ($res as $row) {
			$row = (object)$row;

			$datos[] = [
				'vidempresa'       => $row->idempresa, 
				'vempresa'         => $row->nomempresa, 
				'tempresa'         => 'Empresa:',
				'templeado'        => 'Fecha:',
				'vempleado'        => $row->mes,
				'tcodigo'          => 'Código:',
				'vcodigo'          => $row->idplnempleado,
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
				#'vaguinaldodias'   => $row->aguinaldodias,
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
				'vsaldoprestamo'   => $this->get_saldo_prestamo(['actual' => $args['fal']]),
				'vdiastrabajados'  => $row->diastrabajados,
				'lrecibi'          => str_repeat("_", 35) ,
			];
		}

		return $datos;
	}

	/** El arreglo <args> es lo que se guarda en plnextradetalle */
	public function guardar_extra($args=[])
	{
		if (elemento($args, "id", FALSE)) {
			$idDetalle = $args["id"];
		} else {
			$anio = $args["anio"];

			$tmp = $this->db->get(
				'plnextra', 
				'*', 
				['anio' => $anio]
			);

			if ($tmp === false) {
				$idExtra = $this->db->insert("plnextra", [
					'anio' => $anio,
					'idusuario' => $_SESSION['uid']
				]);
			} else {
				$idExtra = $tmp['id'];
			}

			$test = $this->db->get(
				'plnextradetalle', 
				'*', 
				[
					"AND" => [
						'idplnextra' => $idExtra,
						'idplnempleado' => $this->emp->id
					]
				]
			);

			if ($test === false) {
				$args["datos"]['idplnextra'] = $idExtra;
				$args["datos"]['idplnempleado'] = $this->emp->id;

				$this->db->insert("plnextradetalle", $args["datos"]);
				
				return TRUE;
			} else {
				$idDetalle = $test['id'];
			}
		}

		if (isset($idDetalle)) {
			$this->db->update("plnextradetalle", $args["datos"], ["id" => $idDetalle]);
			
			return TRUE;
		}
	}

	public function buscar($args=[])
	{
		$filtro = null;

		if (count($args) > 0) {
			$tmp = [];

			if (isset($args["activo"])) {
				$tmp["activo"] = $args["activo"];
			}

			if (isset($args["con_sueldo"])) {
				$tmp["sueldo[>]"] = 0;
			}

			if (isset($args["sin_baja"])) {
				$tmp["baja"] = null;
			}

			if (elemento($args, "empleado") !== null) {
				$tmp["id"] = $args["empleado"];
			}

			if (elemento($args, "empresa") !== null) {
				$tmp["idempresadebito"] = $args["empresa"];
			}

			if (count($tmp) > 0) {
				$filtro = (count($tmp) > 1 ? ["AND" => $tmp] : $tmp);
			}
		}

		return $this->db->select(
			"plnempleado", 
			[
				'[>]empresa(b)' => ['plnempleado.idempresadebito' => 'id']
			],
			[
				"plnempleado.*",
				"b.nomempresa"
			],
			$filtro
		);
	}
}
