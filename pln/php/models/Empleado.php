<?php

/**
* 
*/
class Empleado extends Principal
{
	public $emp;
	public $lab;
	public $per;
	public $emg;
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
		if ($this->emp->idpersonal > 0) {
			$this->per = (object)$this->db->get (
				'plnpersonal', 
				'*',
				['id[=]' => $this->emp->idpersonal]
			);
		}
		if ($this->emp->idlaboral > 0) {
			$this->lab = (object)$this->db->get (
				'plnlaboral', 
				'*',
				['id[=]' => $this->emp->idlaboral]
			);
		}
		if ($this->emp->idemergencia > 0) {
			$this->emg = (object)$this->db->get (
				'plnemergencia', 
				'*',
				['id[=]' => $this->emp->idemergencia]
			);
		}
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
			['id[=]' => $this->lab->idproyecto]
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

			if (isset($args['observaciones'])) {
				$this->set_dato('observaciones', $args['observaciones']);
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

		if (elemento($args, 'inicio')) {
			$this->set_dato('inicio', $args['inicio']);
		}

		if (elemento($args, 'idempresa')) {
			$this->set_dato('idempresa', $args['idempresa']);
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
			$this->sueldo = $this->lab->sueldo;
		} else if ($this->dtrabajados == 15) {
			$this->sueldo = round($this->lab->sueldo / 2, 2);
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
		return $this->lab->sueldo/30;
	}

	public function get_bono_dia()
	{
		return $this->lab->bonificacionley/30;
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
			if (empty($this->lab->baja)) {
				$this->dtrabajados = $pago->format('d') == 15 ? 15 : 30;
			} else {
				$baja = new DateTime($this->lab->baja);

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
				if (empty($this->lab->baja)) {
					$interval = $pago->diff($ingreso);
					$this->dtrabajados = ($interval->days + 1);
				} else {
					$baja = new DateTime($this->lab->baja);
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
		if (empty($this->lab->descuentoisr)) {
			return 0;
		} else {
			// if ($this->dtrabajados == 30) {
				return $this->lab->descuentoisr;
			// } else {
				// return round(($this->emp->descuentoisr/30)*$this->dtrabajados, 2);
			// }
		}
	}

	public function get_bono_ley()
	{
		if ($this->dtrabajados > 0) {
			if ($this->dtrabajados == 30) {
				return $this->lab->bonificacionley;
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

		if ($this->lab->frecuencia == 'quincenal' && $this->ndia == 15) {

			if ($this->dtrabajados > 0) {
				if ($this->dtrabajados == 15) {
					$sueldo = round($this->lab->sueldo/2, 2);
					$bono = round($this->lab->bonificacionley/2, 2);
				} else {
					$sueldo = round($this->get_gana_dia()*$this->dtrabajados, 2);
					$bono = round($this->get_bono_dia()*$this->dtrabajados, 2);
				}

				if (empty($this->lab->baja)) {
					$anticipo = round($sueldo + $bono, 2);
				} else {
					$isr = $this->get_descuento_isr();
					$igss = ($sueldo * ($this->lab->porcentajeigss/100));

					$anticipo = round(($sueldo-$igss-$isr)+$bono, 2);
				}
			}
		}

		return $anticipo;
	}

	public function get_descanticipo()
	{
		if ($this->ndia != 15 && $this->lab->frecuencia == 'quincenal') {
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
						'anulado[=]' => 0,
						'esembargo[=]' => 0
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

	public function get_descembargo($args=[])
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
						'anulado[=]' => 0,
						'esembargo[=]' => 1
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
			($this->lab->porcentajeigss/100) * (
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
					'finalizado'    => 0,
					'esembargo'     => 0,
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
			return isset($args['detallado']) ? false : $this->lab->sueldo;
		} else {
			$sql = "SELECT 
						sueldoordinarioreporte as sueldoordinario,
						sueldoextra,
						fecha,
						year(fecha) as anio,
						month(fecha) as mes,
						diastrabajados,
						(sueldoordinario+sueldoextra) as total 
					FROM plnnomina
					WHERE idplnempleado = {$this->emp->id} 
					AND day(fecha) <> 15
					AND esextraordinaria = 0
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
		if (empty($this->lab->reingreso)) {
			return $this->lab->ingreso;
		} else {
			return $this->lab->reingreso;
		}
		// if (empty($this->emp->reingreso)) {
		// 	return $this->emp->ingreso;
		// } else {
		// 	return $this->emp->reingreso;
		// }
	}

	public function set_finiquito_indemnizacion($args = [])
	{
		$ingreso = new DateTime($this->getFechaIngreso());

		if (isset($args["sin_indemnizacion"])) {
			$dias = 0;
			$monto = 0;
		} else {
			$baja     = new DateTime($this->lab->baja);
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

			// para anios biciestos
			$intervalo = ($interval->format('%a')+1) > 365 ? ($interval->format('%a')) : ($interval->format('%a')+1);
			$dias  = ($intervalo/(365/15));
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
						ifnull(c.reingreso, c.ingreso) > DATE_FORMAT(a.fecha,'%Y-%m-01'), 
						ifnull(c.reingreso, c.ingreso), 
						DATE_FORMAT(a.fecha,'%Y-%m-01')
					) as ultimo
					FROM plnnomina a 
					INNER JOIN plnempleado b on b.id = a.idplnempleado
					INNER JOIN plnlaboral c ON b.idlaboral = c.id
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
						ifnull(c.reingreso, c.ingreso) > DATE_FORMAT(a.fecha,'%Y-%m-01'), 
						ifnull(c.reingreso, c.ingreso), 
						DATE_FORMAT(a.fecha,'%Y-%m-01')
					) as ultimo
					FROM plnnomina a 
					INNER JOIN plnempleado b on b.id = a.idplnempleado
					INNER JOIN plnlaboral c ON b.idlaboral = c.id
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
			'sdiario' => ($this->lab->sueldo/30),
			'bdiario' => ($this->lab->bonificacionley/30)
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
			'sueldo'                   => number_format($this->lab->sueldo, 2),
			'bonificacion_etiqueta'    => 'Bonificación:',
			'bonificacion'             => number_format($this->lab->bonificacionley, 2),
			'total_etiqueta'           => 'Total:',
			'total_linea'              => str_repeat('_', 10),
			'total'                    => number_format($this->lab->sueldo + $this->lab->bonificacionley, 2),
			'sueldo_promedio'          => number_format($this->sueldoPromedio, 2),
			'linea_dos_resumen'        => str_repeat("_", 90),
			'texto_prestaciones'       => 'Prestaciones',
			'texto_no_dias'            => 'No. Días',
			'texto_monto'              => 'Monto Q.',
			'indem_texto'              => '1) Indemnización por el tiempo comprendido del:',
			'indem_fechas'             => $fechaIngreso.' al '.formatoFecha($this->lab->baja,1),
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
			'vdpi'                     => $this->per->documento,
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

		if ($args['guardar']) {
			$fini['idplnempleado'] = $this->emp->id;
			$fini['fecha'] = $args['fecha_egreso'];
			$fini['finiquito'] = round($this->finiquitoIndenmizacion->monto, 2);
			$fini['vacaciones'] = round($this->finiquitoVacaciones->monto, 2);
			$fini['aguinaldo'] = round($this->finiquitoAguinaldo->monto, 2);
			$fini['bono'] =	round($this->finiquitoBono->monto, 2);
			$fini['ordinario'] = round($this->finiquitoSueldo->sdiario * $this->finiquitoSueldo->dias, 2);
			$fini['extra'] = round($this->finiquitoSueldo->bdiario * $this->finiquitoSueldo->dias, 2);
			$fini['otrosbono'] = round(elemento($args, 'otros_monto', 0), 2);
			$fini['prestamos'] = round($saldoPrestamos, 2);
			$fini['anticipos'] = round($anticiposPostBaja, 2);
			$fini['otrosdesc'] = round(elemento($args, 'otrosdesc_monto', 0), 2);
			$fini['idempresa'] = $this->lab->idempresadebito;
			$fini['idproyecto'] = $this->lab->idproyecto;
			$fini['idprestamos'] = $args['idprestamos'];
			$fini['concepto'] = $args['concepto'];

			$lid = $this->db->insert('plnfiniquito', $fini);
		}

		return $tmp;
	}

	public function get_empresa_debito()
	{
		$gen = new General();

		return (object)$gen->get_empresa([
			'id'  => $this->lab->idempresadebito, 
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
				? round($this->lab->sueldo, 2)
				: round((($this->lab->sueldo/365)*$this->bonocatorcedias), 2);
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
			$dif_general = $uno->diff($actual);

			if ($ingreso <= $uno) {
				$interval = $uno->diff($actual);
				$this->aguinaldoDias  = ($interval->format('%a')+1);
			} else if ($ingreso <= $actual) {
				$interval = $ingreso->diff($actual);
				$this->aguinaldoDias = ($interval->format('%a')+1);
			}

			if ($this->aguinaldoDias > 365) {
				$this->aguinaldoDias = $this->aguinaldoDias - 1;
			}

			if ($this->aguinaldoDias > 0) {
				$this->aguinaldoMonto = $this->aguinaldoDias == 365 
				? round($this->lab->sueldo, 2) 
				: round((($this->lab->sueldo/365)*$this->aguinaldoDias), 2);
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
		$resultado = $this->db->select("plnbitacora", [
			"movgasolina",
			"movdepvehiculo"
		], [
			"AND" => [
				"idplnempleado" => $bit->idplnempleado,
				"mostrar" => 1,
				"OR" => [
					"movdepvehiculo[>]" => 0.00,
					"movgasolina[>]" => 0.00
				]
			]
		]);

		// $sueldo_ant = $this->db->select("plnbitacora", [
		// 	"sueldo",
		// 	"bonificacionley"
		// ], [
		// 	"AND" => [
		// 		"idplnempleado" => $bit->idplnempleado,
		// 		"sueldo[>]" => 0.00,
		// 		"bonificacionley[>]" => 0.00
		// 	]
		// ]);
		// if (!empty($sueldo_ant) && $bit->idplnmovimiento != 10) {
		// 	$sueldo_ant = $sueldo_ant[0];
		// } else {
		// 	$sueldo_ant = [];
		// }

		$siempre = !empty($resultado) ? $resultado[0] : null;		

		$antes = json_decode($bit->antes);

		if ($bit->idempresadebito > 0 && $bit->idplnmovimiento != 6) {
			$nomempresa = $this->db->select("empresa","nomempresa",["id [=]" => $bit->idempresadebito])[0];
		} else if ($antes !== null) {
			$antes = get_object_vars($antes);
			// print_r($antes); return;
			foreach ($antes as $a => $valor) {
				if ($a == 'idempresadebito') {
					$idempresa = $valor;
				}
			}

			$nomempresa = $this->db->select("empresa","nomempresa",["id [=]" => $idempresa])[0];
		} else { 
			$nomempresa = $emp->nomempresa;
		}

		$tmp = [
			'fecha'            => 'Guatemala, ' . date('d/m/Y H:i:s'),
			'movfecha' 		   => (empty($bit->movfecha) ? "" : formatoFecha($bit->movfecha, 1)),
			'empleado'         => $this->emp->nombre.' '.$this->emp->apellidos,
			'empresa'          => $nomempresa,
			'movdescripcion'   => $bit->movdescripcion,
			'movgasolina'      => isset($siempre['movgasolina']) ? number_format($siempre['movgasolina'], 2) : number_format($bit->movgasolina, 2), 
			'movdepvehiculo'   => isset($siempre['movdepvehiculo']) ? number_format($siempre['movdepvehiculo'], 2) : number_format($bit->movdepvehiculo, 2), 
			'movotros'         => number_format($bit->movotros, 2), 
			'movobservaciones' => $bit->movobservaciones,
			'numero'           => $bit->id
		];

		if (!empty($bit->antes) && $bit->idplnmovimiento != 10) {
			$ant = json_decode($bit->antes);
			$tmp['ant_sueldo']       = number_format($ant->sueldo, 2);
			$tmp['ant_bonificacion'] = number_format($ant->bonificacionley, 2);
			$tmp['ant_total']        = number_format(($ant->sueldo+$ant->bonificacionley), 2);
		} else {
			$ant = new stdClass();
			$ant->sueldo = 0;
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

		if (isset($des)) {
			if ($ant->sueldo === $des->sueldo) {
				$antes = $this->db->select("plnbitacora", ["antes"], 
					[
						"AND" => [
							"idplnempleado" => $bit->idplnempleado,
							"idplnmovimiento[=]" => 7
						],
						"ORDER" => ["fecha DESC"],
						"LIMIT" => 1
					],
					1
				);
				if (isset($antes[0])) {
					$antes = json_decode($antes[0]['antes']);
					$tmp['ant_sueldo']       = number_format($antes->sueldo, 2);
					$tmp['ant_bonificacion'] = number_format($antes->bonificacionley, 2);
					$tmp['ant_total']        = number_format(($antes->sueldo+$antes->bonificacionley), 2);
				} else {
					$tmp['ant_sueldo']       = 0.00;
					$tmp['ant_bonificacion'] = 0.00;
					$tmp['ant_total']        = 0.00;
				}
			}
		}

		// if ($bit->sueldo > 0) {
		// 	$tmp['ant_sueldo'] = number_format($bit->sueldo, 2);
		// 	if ($bit->bonificacionley > 0) {
		// 		$tmp['ant_bonificacion'] = number_format($bit->bonificacionley, 2);
		// 		$tmp['ant_total']  = number_format(($bit->sueldo+$bit->bonificacionley), 2);
		// 	} else {
		// 		$tmp['ant_bonificacion'] = 0;
		// 	}
		// } else if (!empty($sueldo_ant)) {
		// 	$tmp['ant_sueldo'] = number_format($sueldo_ant['sueldo'], 2);
		// 	if ($sueldo_ant['bonificacionley'] > 0) {
		// 		$tmp['ant_bonificacion'] = number_format($sueldo_ant['bonificacionley'], 2);
		// 		$tmp['ant_total']  = number_format(($sueldo_ant['sueldo']+$sueldo_ant['bonificacionley']), 2);
		// 	} else {
		// 		$tmp['ant_bonificacion'] = 0;
		// 	}
		// }

		// $tmp['des_sueldo']       = number_format($this->lab->sueldo, 2);
		// $tmp['des_bonificacion'] = number_format($this->lab->bonificacionley, 2);
		// $tmp['des_total']        = number_format($this->lab->sueldo + $this->lab->bonificacionley, 2);

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

	public function guardarDatosPersonales($data = []) : object {
		// validar si existen datos
		if (count($data) > 0) {
			// formatear datos antes de hacer insert
			if (!isset($data['primernombre']) || !isset($data['primerapellido'])) {
				$respuesta = new StdClass;
				$respuesta->tipo = 'error';
				$respuesta->mensaje = 'Error, no se recibio primer nombre o primer apellido favor ingresarlos nuevamente.';
				return $respuesta;
			}
			trim($data['primernombre']);
			$data['segundonombre'] = isset($data['segundonombre']) ? trim($data['segundonombre']) : null;
			$data['tercernombre'] = isset($data['tercernombre']) ? trim($data['tercernombre']) : null;
			trim($data['primerapellido']);
			$data['segundoapellido'] = isset($data['segundoapellido']) ? trim($data['segundoapellido']) : null;
			$data['apellidocasada'] = isset($data['apellidocasada']) ? trim($data['apellidocasada']) : null;
			$data['direccion'] = isset($data['direccion']) ? trim($data['direccion']) : null;
			$data['telefono'] = isset($data['telefono']) ? trim($data['telefono']) : null;
			$data['documento'] = isset($data['documento']) ? trim($data['documento']) : null;
			$data['nit'] = isset($data['nit']) ? trim($data['nit']) : null;
			$data['correo'] = isset($data['correo']) ? trim($data['correo']) : null;
			$data['idlengua'] = isset($data['idlengua']) ? $data['idlengua'] : null;
			$data['hijos'] = isset($data['hijos']) ? $data['hijos'] : null;
			$data['profesion'] = isset($data['profesion']) ? $data['profesion'] : null;
			$idempleado = isset($data['idplnempleado']) ? $data['idplnempleado'] : null;
			$idpersonal = isset($data['id']) ? $data['id'] : null;

			if (isset($data['nacimiento'])) { 
				$fecha = new DateTime($data['nacimiento']);
				$data['nacimiento'] = $fecha->format('Y-m-d');
			} else { 
				unset($data['nacimiento']); 
			}

			unset($data['idplnempleado']);
			unset($data['id']);

			foreach($data as $d => $campo) {
				$this->set_dato($d,  $campo);
			}

			if (!isset($idpersonal)) {
				$idpersonal = $this->db->insert('plnpersonal', $this->datos);

				if ($idpersonal > 0 && !isset($idempleado)) {
					$this->datos = [];
					$this->set_dato("nombre", $data['primernombre'].' '.$data['segundonombre'].' '.$data['tercernombre']);
					$this->set_dato("apellidos", $data['primerapellido'].' '.$data['segundoapellido'].' '.$data['apellidocasada']);
					$this->set_dato("idpersonal", $idpersonal);
					$idempleado = $this->db->insert('plnempleado', $this->datos);
				} else if ($idpersonal > 0 && isset($idempleado)) {
					$this->datos = [];
					$this->set_dato("idpersonal", $idpersonal);
					$this->db->update('plnempleado', $this->datos, ["id [=]" => $idempleado]);
				}

				if ($idempleado > 0 && $idpersonal > 0) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'success';
					$respuesta->mensaje = 'Datos personales insertados con exito.';
					$respuesta->id = $idempleado;
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'Error en la base de datos favor comunicarse con IT.';
				}
			} else {
				$upd = $this->db->update('plnpersonal', $this->datos, ["id [=]" => $idpersonal]);

				if ($upd) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'success';
					$respuesta->mensaje = 'Datos personales actualizados con exito.';
					$respuesta->id = $idempleado;
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'warning';
					$respuesta->mensaje = 'Error en la base de datos al actualizar, favor comunicarse con IT.';
				}
			}
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'No se recibieron datos, volver a intentar.';
		}

		return $respuesta;
	}

	public function guardarDatosLaborales($data = []) : object {
		if (isset($data['idplnempleado']) && !isset($data['id'])) {
			$data['id'] = $this->db->select("plnempleado","idlaboral",["id [=]" => $data['idplnempleado']])[0];
		}

		$lab = $this->db->get("plnlaboral", "*", ["id [=]" => $data['id']]);

		// validar si existen datos
		if (count($data) > 0) {
			// formatear datos antes de hacer insert
			if (isset($data['ingreso'])) {
				$fecha = new DateTime($data['ingreso']);
				$data['ingreso'] = $fecha->format('Y-m-d');
			}
			if (isset($data['reingreso'])) { 
				$fecha = new DateTime($data['reingreso']);
				$data['reingreso'] = $fecha->format('Y-m-d');
			} else { 
				unset($data['reingreso']); 
			}
			if (isset($data['baja'])) { 
				$fecha = new DateTime($data['baja']);
				$data['baja'] = $fecha->format('Y-m-d');
			} else { 
				$data['baja'] = null;
			}
			$idempleado = isset($data['idplnempleado']) ? $data['idplnempleado'] : null;
			$idlaboral = isset($data['id']) ? $data['id'] : null;

			unset($data['idplnempleado']);
			unset($data['id']);

			foreach($data as $d => $campo) {
				$this->set_dato($d,  $campo);
			}

			if (!isset($idlaboral) || $idlaboral == null) {
				$idlaboral = $this->db->insert('plnlaboral', $this->datos);

				if (!isset($idempleado)) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'No existe el empelado, favor ingresar datos del empleado antes de los datos laborales.';
					return $respuesta;
				} else if ($idlaboral > 0 && isset($idempleado)) {
					$this->datos = [];
					$this->set_dato("idlaboral", $idlaboral);
					$this->db->update('plnempleado', $this->datos, ["id [=]" => $idempleado]);
				}

				if ($idempleado > 0 && $idlaboral > 0) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'success';
					$respuesta->mensaje = 'Datos laborales insertados con exito.';
					$respuesta->id = $idempleado;
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'Error en la base de datos favor comunicarse con IT.';
				}
			} else {
				$antes = (object)$this->db->get('plnlaboral', '*',['id[=]' => $idlaboral]);
				$upd = $this->db->update('plnlaboral', $this->datos, ["id [=]" => $idlaboral]);
				$despues = (object)$this->db->get('plnlaboral', '*',['id[=]' => $idlaboral]);

				if ($upd) {
					if ($lab['sueldo'] != $data['sueldo'] || $lab['bonificacionley'] != $data['bonificacionley'] || $lab['descuentoisr'] != $data['descuentoisr']) {
						$objeto = json_decode(json_encode($lab));
						$objeto->movobservaciones = 'Aumento de sueldo';
						$this->generarBitacora($idempleado, 7, $objeto, 'Aumento de sueldo', 0, null, $antes, $despues);

						$fecha_nomina = date('d') <= 15 ? date('Y-m-15') : date('Y-m-15');

						$existe_nomina = $this->db->get(
						"plnnomina",
						'id',
						[
							'AND' => [
								"fecha" => $fecha_nomina,
								"idplnempleado" => $idempleado
							]
						]
							) > 0;
						if ($existe_nomina) {
							$respuesta = new StdClass;
							$respuesta->tipo = 'warning';
							$respuesta->mensaje = 'Datos laborales actualizados con exito. Favor actualizar la nómina del empleado para que los cambios tengan efecto.';
							$respuesta->id = $idempleado;
						} else {
							$respuesta = new StdClass;
							$respuesta->tipo = 'success';
							$respuesta->mensaje = 'Datos laborales actualizados con exito.';
							$respuesta->id = $idempleado;
						}
					} else {
						$respuesta = new StdClass;
						$respuesta->tipo = 'success';
						$respuesta->mensaje = 'Datos laborales actualizados con exito.';
						$respuesta->id = $idempleado;
					}
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'warning';
					$respuesta->mensaje = 'Error en la base de datos al actualizar, favor comunicarse con IT.';
				}
			}
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'No se recibieron datos, volver a intentar.';
		}

		return $respuesta;
	}

	public function guardarDatosEmergencia($data = []) : object {
		// validar si existen datos
		if (count($data) > 0) {
			// formatear datos antes de hacer insert
			$data['nombre'] = isset($data['nombre']) ? trim($data['nombre']) : null;
			$data['telefono'] = isset($data['telefono']) ? trim($data['telefono']) : null;
			$data['direccion'] = isset($data['direccion']) ? trim($data['direccion']) : null;

			$idempleado = isset($data['idplnempleado']) ? $data['idplnempleado'] : null;
			$idemergencia = isset($data['id']) ? $data['id'] : null;

			unset($data['idplnempleado']);
			unset($data['id']);

			foreach($data as $d => $campo) {
				$this->set_dato($d,  $campo);
			}

			if (!isset($idemergencia)) {
				$idemergencia = $this->db->insert('plnemergencia', $this->datos);

				if (!isset($idempleado)) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'No existe el empelado, favor ingresar datos personales del empleado antes de los datos de emergencia.';
					return $respuesta;
				} else if ($idemergencia > 0 && isset($idempleado)) {
					$this->datos = [];
					$this->set_dato("idemergencia", $idemergencia);
					$this->db->update('plnempleado', $this->datos, ["id [=]" => $idempleado]);
				}

				if ($idempleado > 0 && $idemergencia > 0) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'success';
					$respuesta->mensaje = 'Datos de emergencia insertados con exito.';
					$respuesta->id = $idempleado;
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'Error en la base de datos favor comunicarse con IT.';
				}
			} else {
				$upd = $this->db->update('plnemergencia', $this->datos, ["id [=]" => $idemergencia]);

				if ($upd) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'success';
					$respuesta->mensaje = 'Datos de emergencia actualizados con exito.';
					$respuesta->id = $idempleado;
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'warning';
					$respuesta->mensaje = 'Error en la base de datos al actualizar, favor comunicarse con IT.';
				}
			}
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'No se recibieron datos, volver a intentar.';
		}

		return $respuesta;
	}

	public function eliminar($id) : object {
		$idemr = $this->db->select("plnempleado","idemergencia",["id [=]" => $id])[0];
		$idper = $this->db->select("plnempleado","idpersonal",["id [=]" => $id])[0];
		$idlab = $this->db->select("plnempleado","idlaboral",["id [=]" => $id])[0];

		if ($idemr) {
			$elm = $this->db->delete('plnemergencia', ["id [=]" => $idemr]);
		}
		if ($idper) {
			$elm = $this->db->delete('plnpersonal', ["id [=]" => $idper]);
		}
		if ($idlab) {
			$elm = $this->db->delete('plnlaboral', ["id [=]" => $idlab]);
		}

		$elm = $this->db->delete('plnempleado', ["id [=]" => $id]);
		if ($elm) {
			$respuesta = new StdClass;
			$respuesta->tipo = 'success';
			$respuesta->mensaje = 'Empleado eliminado con exito.';
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'Error al eliminar empleado, favor comunicarse con IT.';
		}
		return $respuesta;
	}

	public function crear ($data) : object {
		// control de datos
		$fecha = new DateTime($data->fecha);
		$data->ingreso = $fecha->format('Y-m-d');
		$data->segundonombre = isset($data->segundonombre) ? $data->segundonombre : '';
		$data->tercernombre = isset($data->tercernombre) ? $data->tercernombre : '';
		$data->segundoapellido = isset($data->segundoapellido) ? $data->segundoapellido : '';
		$data->apellidocasada = isset($data->apellidocasada) ? $data->apellidocasada : '';

		// crear empleado en general
		$this->datos = [];
		$this->set_dato("nombre", $data->primernombre.' '.$data->segundonombre.' '.$data->tercernombre);
		$this->set_dato("apellidos", $data->primerapellido.' '.$data->segundoapellido.' '.$data->apellidocasada);
		$this->set_dato("ingreso", $data->ingreso);

		$idempleado = $this->db->insert('plnempleado', $this->datos);

		// crear datos personales
		$this->datos = [];
		$this->set_dato("primernombre", $data->primernombre);
		$this->set_dato("segundonombre", $data->segundonombre);
		$this->set_dato("tercernombre", $data->tercernombre);
		$this->set_dato("primerapellido", $data->primerapellido);
		$this->set_dato("segundoapellido", $data->segundoapellido);
		$this->set_dato("apellidocasada", $data->apellidocasada);

		$idpersonal = $this->db->insert('plnpersonal', $this->datos);

		// crear datos laborales
		$this->datos = [];
		$this->set_dato("ingreso", $data->ingreso);
		$this->set_dato("idempresaactual", $data->idempresaactual);
		$this->set_dato("idempresadebito", $data->idempresadebito);
		$this->set_dato("sueldo", $data->sueldo);
		$this->set_dato("bonificacionley", $data->bonificacionley);
		$this->set_dato("descuentoisr", $data->descuentoisr);
		$this->set_dato("porcentajeigss", $data->porcentajeigss);

		$idlaboral = $this->db->insert('plnlaboral', $this->datos);

		// asignarle los datos personales y laborales al empleado
		$this->datos = [];
		$this->set_dato("idpersonal", $idpersonal);
		$this->set_dato("idlaboral", $idlaboral);
		$this->db->update('plnempleado', $this->datos, ["id [=]" => $idempleado]);

		$data->sueldo = 0.00;
		$data->bonificacionley = 0.00;
		$despues = (object)$this->db->get('plnlaboral', '*',['id[=]' => $idlaboral]);

		// generar bitacora de nuevo ingreso
		$idbitacora = $this->generarBitacora($idempleado, 10, $data, 'NUEVO INGRESO', 0,$data->ingreso, null, $despues);

		if ($idempleado > 0) {
			if ($idpersonal > 0) {
				if ($idlaboral > 0) {
					if ($idbitacora > 0) {
						$respuesta = new StdClass;
						$respuesta->tipo = 'success';
						$respuesta->mensaje = 'Empleado creado con exito.';
						$respuesta->id = $idempleado;
					} else {
						$respuesta = new StdClass;
						$respuesta->tipo = 'error';
						$respuesta->mensaje = 'Error al crear bitacora, favor comunicarse con IT.';
					}
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'Error al crear datos laborales, favor comunicarse con IT.';
				}
			} else {
				$respuesta = new StdClass;
				$respuesta->tipo = 'error';
				$respuesta->mensaje = 'Error al crear datos personales, favor comunicarse con IT.';
			}
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'Error al crear empleado, favor volver a intentar.';
		}

		return $respuesta;
	}

	public function alta ($data) : object {
		$fecha = new DateTime($data->reingreso);
		$data->reingreso = $fecha->format('Y-m-d');

		$antes = (object)$this->db->get('plnlaboral', '*',['id[=]' => $data->id]);

		$data_anterior = $this->db->select("plnlaboral","*",["id" => $data->id])[0];
		$anterior = new StdClass;
		$anterior->movobservaciones = $data->movobservaciones;
		$anterior->idempresadebito = $data_anterior['idempresadebito'];
		$anterior->idempresaactual = $data_anterior['idempresaactual'];
		$anterior->sueldo = $data_anterior['sueldo'];
		$anterior->bonificacionley = $data_anterior['bonificacionley'];
		$anterior->descuentoisr = $data_anterior['descuentoisr'];
		$anterior->porcentajeigss = $data_anterior['porcentajeigss'];
		$anterior->idcuenta = $data_anterior['idcuenta'];
		$anterior->idproyecto = $data_anterior['idproyecto'];
		$anterior->baja = $data_anterior['baja'];
		$anterior->reingreso = $data_anterior['reingreso'];

		$this->datos = [];
		$this->set_dato("reingreso", $data->reingreso);
		$this->set_dato("baja", null);
		$this->set_dato("idempresaactual", $data->idempresaactual);
		$this->set_dato("idempresadebito", $data->idempresadebito);
		$this->set_dato("sueldo", $data->sueldo);
		$this->set_dato("bonificacionley", $data->bonificacionley);
		$this->set_dato("descuentoisr", $data->descuentoisr);
		$this->set_dato("porcentajeigss", $data->porcentajeigss);
		if ($data->idcuenta > 0) {
			$this->set_dato("idcuenta", $data->idcuenta);
		}
		if ($data->idproyecto > 0) {
			$this->set_dato("idproyecto", $data->idproyecto);
		}

		$upd = $this->db->update('plnlaboral', $this->datos, ["id [=]" => $data->id]);

		if ($upd) {
				$this->datos = [];
				$this->set_dato("baja", null); 
				$upd = $this->db->update('plnempleado', $this->datos, ["id [=]" => $data->idplnempleado]);
			if ($upd) {
				$despues = (object)$this->db->get('plnlaboral', '*',['id[=]' => $data->id]);
				if ($this->generarBitacora($data->idplnempleado, 6, $anterior, 'REINGRESO', 1, $data->reingreso, $antes, $despues) > 0) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'success';
					$respuesta->mensaje = 'Empleado dado de alta con exito.';
					$respuesta->id = $data->idplnempleado;
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'No se pudo generar bitacora de reingreso.';
					$respuesta->id = $data->idplnempleado;
				}
			} else {
				$respuesta = new StdClass;
				$respuesta->tipo = 'error';
				$respuesta->mensaje = 'No se pudo dar de alta al empleado.';
				$respuesta->id = $data->idplnempleado;
			}
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'No se pudo editar datos laborales.';
			$respuesta->id = $data->idplnempleado;
		}
		return $respuesta;
	} 

	public function baja ($data) : object {
		$idlaboral = $this->db->select("plnempleado","idlaboral",["id [=]" => $data->empleado])[0];

		$antes = (object)$this->db->get('plnlaboral', '*',['id[=]' => $idlaboral]);

		$data_anterior = $this->db->select("plnlaboral","*",["id" => $idlaboral])[0];
		$anterior = new StdClass;
		$anterior->movobservaciones = $data->concepto;
		$anterior->baja = $data_anterior['baja'];
		$anterior->reingreso = $data_anterior['reingreso'];

		$fecha = new DateTime($data->fecha_egreso);
		$data->baja = $fecha->format('Y-m-d');

		$upd = $this->db->update('plnlaboral', ["baja" => $data->baja], ["id [=]" => $idlaboral]);
		if ($upd) {
			$upd = $this->db->update('plnempleado', ["baja" => $data->baja], ["id [=]" => $data->empleado]);
			if ($upd) {
				$despues = (object)$this->db->get('plnlaboral', '*',['id[=]' => $idlaboral]);
				if ($this->generarBitacora($data->empleado, 3, $anterior, 'BAJA DE EMPLEADO', 1, $data->baja, $antes, $despues) > 0) {
					$respuesta = new StdClass;
					$respuesta->tipo = 'success';
					$respuesta->mensaje = 'Empleado dado de baja con exito.';
					$respuesta->id = $data->empleado;
				} else {
					$respuesta = new StdClass;
					$respuesta->tipo = 'error';
					$respuesta->mensaje = 'No se pudo generar bitacora de baja.';
					$respuesta->id = $data->empleado;
				}
			} else {
				$respuesta = new StdClass;
				$respuesta->tipo = 'error';
				$respuesta->mensaje = 'No se pudo dar de baja al empleado.';
				$respuesta->id = $data->empleado;
			}
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'No se pudo dar de baja al empleado.';
			$respuesta->id = $data->empleado;
		}
		return $respuesta;
	}

	private function generarBitacora ($idempleado, $tipo, $datos = [], $descripcion, $revertir = 0, $fecha = null, $antes = null, $despues = null) : int {
		$fecha_mov = $fecha ? $fecha : date('Y-m-d');
		$antes = $antes ? json_encode($antes) : null;
		$despues = $despues ? json_encode($despues) : null;

		$this->datos = [];
		$this->set_dato("idplnempleado", $idempleado);
		$this->set_dato("usuario", 1);
		$this->set_dato("movfecha", $fecha_mov);
		$this->set_dato("movdescripcion", $descripcion);
		$this->set_dato("movobservaciones", $datos->movobservaciones);
		$this->set_dato("mostrar", 1);
		$this->set_dato("idplnmovimiento", $tipo);
		$this->set_dato("revertir", $revertir);
		$this->set_dato("antes", $antes);
		$this->set_dato("despues", $despues);

		if (isset($datos->idempresadebito) && $datos->idempresadebito > 0) {
			$this->set_dato("idempresadebito", $datos->idempresadebito);
		}
		if (isset($datos->idempresaactual) && $datos->idempresaactual > 0) {
			$this->set_dato("idempresaactual", $datos->idempresaactual);
		}
		if (isset($datos->sueldo) && $datos->sueldo > 0) {
			$this->set_dato("sueldo", $datos->sueldo);
		}	
		if (isset($datos->bonificacionley) && $datos->bonificacionley > 0) {
			$this->set_dato("bonificacionley", $datos->bonificacionley);
		}
		if (isset($datos->descuentoisr) && $datos->descuentoisr > 0) {
			$this->set_dato("descuentoisr", $datos->descuentoisr);
		}
		if (isset($datos->porcentajeigss) && $datos->porcentajeigss > 0) {
			$this->set_dato("porcentajeigss", $datos->porcentajeigss);
		}
		if (isset($datos->idcuenta) && $datos->idcuenta > 0) {
			$this->set_dato("idcuenta", $datos->idcuenta);
		}
		if (isset($datos->idproyecto) && $datos->idproyecto > 0) {
			$this->set_dato("idproyecto", $datos->idproyecto);
		}
		if (isset($datos->baja) && $datos->baja != null) {
			$this->set_dato("baja", $datos->baja);
		}
		if (isset($datos->reingreso) && $datos->reingreso != null) {
			$this->set_dato("reingreso", $datos->reingreso);
		}

		$idbitacora = $this->db->insert('plnbitacora', $this->datos);
		return $idbitacora;
	}
}
