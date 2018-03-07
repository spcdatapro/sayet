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

	public function guardar($args = '')
	{
		if (is_array($args) && !empty($args)) {
			if (elemento($args, 'nombre')) {
				$this->set_dato('nombre', $args['nombre']);
			}

			if (elemento($args, 'apellidos')) {
				$this->set_dato('apellidos', $args['apellidos']);
			}

			if (elemento($args, 'direccion')) {
				$this->set_dato('direccion', $args['direccion']);
			}

			if (elemento($args, 'telefono')) {
				$this->set_dato('telefono', $args['telefono']);
			}

			if (elemento($args, 'correo')) {
				$this->set_dato('correo', $args['correo']);
			}
			
			if (elemento($args, 'sexo')) {
				$this->set_dato('sexo', $args['sexo']);
			}
			
			if (elemento($args, 'estadocivil')) {
				$this->set_dato('estadocivil', $args['estadocivil']);
			}
			
			if (elemento($args, 'fechanacimiento')) {
				$this->set_dato('fechanacimiento', $args['fechanacimiento']);
			}
			
			if (elemento($args, 'dpi')) {
				$this->set_dato('dpi', $args['dpi']);
			}
			
			if (elemento($args, 'extendido')) {
				$this->set_dato('extendido', $args['extendido']);
			}
			
			if (elemento($args, 'nit')) {
				$this->set_dato('nit', $args['nit']);
			}
			
			if (elemento($args, 'igss')) {
				$this->set_dato('igss', $args['igss']);
			}
			
			$this->set_dato('ingreso', elemento($args, 'ingreso', NULL));
			$this->set_dato('reingreso', elemento($args, 'reingreso', NULL));
			$this->set_dato('baja', elemento($args, 'baja', NULL));
			
			if (elemento($args, 'idplnpuesto')) {
				$this->set_dato('idplnpuesto', $args['idplnpuesto']);
			}
			
			if (elemento($args, 'cuentapersonal')) {
				$this->set_dato('cuentapersonal', $args['cuentapersonal']);
			}
			
			if (elemento($args, 'descuentoisr')) {
				$this->set_dato('descuentoisr', $args['descuentoisr']);
			}
			
			if (elemento($args, 'idempresaactual')) {
				$this->set_dato('idempresaactual', $args['idempresaactual']);
			}
			
			if (elemento($args, 'bonificacionley')) {
				$this->set_dato('bonificacionley', $args['bonificacionley']);
			}
			
			if (elemento($args, 'sueldo')) {
				$this->set_dato('sueldo', $args['sueldo']);
			}
			
			if (elemento($args, 'porcentajeigss')) {
				$this->set_dato('porcentajeigss', $args['porcentajeigss']);
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
			
			if (elemento($args, 'cuentabanco')) {
				$this->set_dato('cuentabanco', $args['cuentabanco']);
			}
			
			if (elemento($args, 'idproyecto')) {
				$this->set_dato('idproyecto', $args['idproyecto']);
			}

		}

		if (!empty($this->datos)) {
			if ($this->emp) {
				if ($this->db->update($this->tabla, $this->datos, ["id [=]" => $this->emp->id])) {
					$this->cargar_empleado($this->emp->id);

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
		return round($this->sueldo/30, 2);
	}

	public function get_bono_dia()
	{
		return round($this->emp->bonificacionley/30, 2);
	}

	public function get_gana_hora()
	{
		return round($this->get_gana_dia()/8, 2);
	}

	public function get_horas_extras_simples()
	{
		return round($this->emp->cantidad_horas_simples*$this->get_gana_hora()*$this->horasimple, 2);
	}

	public function get_horas_extras_dobles()
	{
		return round($this->emp->cantidad_horas_dobles*$this->get_gana_hora()*$this->horadoble, 2);
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
				$this->dtrabajados = $this->ndia;
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
			return round($this->get_gana_dia()*$this->dtrabajados, 2);
		}

		return 0;
	}

	public function get_bono_ley()
	{
		if ($this->dtrabajados > 0) {
			return round($this->get_bono_dia()*$this->dtrabajados, 2);
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

			return ($this->dtrabajados * $this->get_gana_dia());
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

	public function get_descingss()
	{
		return round(($this->emp->porcentajeigss/100) * $this->sueldo, 2);
	}
}