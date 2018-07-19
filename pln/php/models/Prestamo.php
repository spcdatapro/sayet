<?php

/**
* 
*/
class Prestamo extends Principal
{
	public $pre;
	protected $tabla;
	
	function __construct($id = '')
	{
		parent::__construct();

		$this->tabla = 'plnprestamo';

		if (!empty($id)) {
			$this->cargar_prestamo($id);
		}
	}

	public function cargar_prestamo($id)
	{
		$this->pre = (object)$this->db->get(
			$this->tabla, 
			['*'], 
			['id' => $id]
		);
    }

    public function get_empleado()
    {
    	return (object)$this->db->get(
			'plnempleado', 
			['id', 'nombre', 'apellidos', 'idempresadebito'], 
			['id' => $this->pre->idplnempleado]
		);
    }

    public function guardar($args = [])
	{
		if (is_array($args) && !empty($args)) {
			if (elemento($args, 'idplnempleado')) {
				$this->set_dato('idplnempleado', $args['idplnempleado']);
			}

			if (elemento($args, 'monto', FALSE)) {
				$this->set_dato('monto', $args['monto']);
			}

			if (elemento($args, 'cuotamensual', FALSE)) {
				$this->set_dato('cuotamensual', $args['cuotamensual']);
			}

			if (elemento($args, 'iniciopago', FALSE)) {
				$this->set_dato('iniciopago', $args['iniciopago']);
			}

			if (elemento($args, 'liquidacion', FALSE)) {
				$this->set_dato('liquidacion', $args['liquidacion']);
				$this->set_dato('finalizado', TRUE);
			} else {
				$this->set_dato('liquidacion', NULL);
				$this->set_dato('finalizado', FALSE);
			}

			if (isset($args['concepto'])) {
				$this->set_dato('concepto', $args['concepto']);
			}

			if (isset($args['saldo'])) {
				$this->set_dato('saldo', $args['saldo']);
			}
		}

		if (!empty($this->datos)) {
			if ($this->pre) {
				if ($this->pre->finalizado == 0) {
					if ($this->db->update($this->tabla, $this->datos, ["id" => $this->pre->id])) {
						$this->cargar_prestamo($this->pre->id);

						return TRUE;
					} else {
						if ($this->db->error()[0] == 0) {
							$this->set_mensaje('Nada que actualizar.');
						} else {
							$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
						}
					}
				} else {
					$this->set_mensaje("Préstamo finalizado, no puedo continuar.");
				}
			} else {
				$this->set_dato('saldo', elemento($args, 'monto', 0));

				$lid = $this->db->insert($this->tabla, $this->datos);

				if ($lid) {
					$this->cargar_prestamo($lid);

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

	public function guardar_omision($args = [])
	{
		if (elemento($args, 'fecha', FALSE)) {
			$datos = [
				'fecha' => $args['fecha'], 
				'idusuario' => $_SESSION['uid'], 
				'idplnprestamo' => $this->pre->id
			];

			$lid = $this->db->insert('plnpresnodesc', $datos);

			if ($lid) {
				return TRUE;
			} else {
				$this->set_mensaje('Error en la base de datos al guardar: ' . $this->db->error()[2]);
			}
		} else {
			$this->set_mensaje('Por favor ingrese una fecha.');
		}

		return FALSE;
	}

	public function get_omisiones()
	{
		return $this->db->select("plnpresnodesc", [
				'[><]usuario(b)' => ['plnpresnodesc.idusuario' => 'id']
			], 
			[
				"plnpresnodesc.id",
				"plnpresnodesc.fecha",
				"plnpresnodesc.registro",
				"b.nombre"
			],
			[
				'idplnprestamo' => $this->pre->id
			]
		);
	}

	public function guardar_abono($args = [])
	{
		if ($this->get_saldo() >= $args['monto']) {
			$datos = [
				'fecha' => $args['fecha'],
				'monto' => $args['monto'],
				'concepto' => $args['concepto'],
				'idusuario' => $_SESSION['uid'],
				'idplnprestamo' => $this->pre->id
			];

			$lid = $this->db->insert('plnpresabono', $datos);

			if ($lid) {
				$this->guardar(['saldo' => $this->get_saldo()]);
				return TRUE;
			} else {
				$this->set_mensaje('Error al guardar: ' . $this->db->error()[2]);
			}
		} else {
			$this->set_mensaje('El saldo es inferior al monto ingresado. Por favor verifique e intente nuevamente.');
		}

		return FALSE;
	}

	public function get_abonos()
	{
		return $this->db->select("plnpresabono", [
				'[><]usuario(b)' => ['plnpresabono.idusuario' => 'id']
			], 
			[
				"plnpresabono.id",
				"plnpresabono.fecha",
				"plnpresabono.monto",
				"plnpresabono.concepto",
				"plnpresabono.registro",
				"b.nombre"
			],
			[
				'idplnprestamo' => $this->pre->id
			]
		);
	}

	public function actualizar_saldo($args=[])
	{
		# code...
	}

	public function get_saldo($args = [])
	{
		return ($this->pre->monto - $this->get_total_descuentos($args));
	}

	public function get_total_descuentos($args = [])
	{
		return ($this->get_descuentos_planilla($args) + $this->get_otro_abonos($args));
	}

	public function get_descuentos_planilla($args = [])
	{
		$abonos = 0;
		$condiciones = ['plnpresnom.idplnprestamo' => $this->pre->id];

		if (elemento($args, 'fecha')) {
			$condiciones['b.fecha[=]'] = $args['fecha'];
		}

		if (elemento($args, 'actual')) {
			$condiciones['b.fecha[<=]'] = $args['actual'];
		}

		if (isset($args['terminada'])) {
			$condiciones['b.terminada[=]'] = $args['terminada'];
		}

		if (isset($args['sin_idplnnomina'])) {
			$condiciones['plnpresnom.idplnnomina[!]'] = $args['sin_idplnnomina'];
		}

		$tmp = $this->db->select("plnpresnom", [
				'[><]plnnomina(b)' => ['plnpresnom.idplnnomina' => 'id']
			],
			["plnpresnom.monto"],
			['AND' => $condiciones]
		);

		if (count($tmp) > 0) {
			$abonos = totalCampo($tmp, 'monto');
		}

		return $abonos;
	}

	public function get_otro_abonos($args = [])
	{
		$abonos = 0;
		$condiciones = ['idplnprestamo' => $this->pre->id];

		if (elemento($args, 'fecha')) {
			$condiciones['fecha[=]'] = $args['fecha'];
		}

		if (elemento($args, 'actual')) {
			$condiciones['fecha[<=]'] = $args['actual'];
		}

		$tmpdir = $this->db->select(
			'plnpresabono', 
			['monto'],
			['AND' => $condiciones]
		);

		if (count($tmpdir) > 0) {
			$abonos = totalCampo($tmpdir, 'monto');
		}

		return $abonos;
	}

	public function get_saldo_anterior($args=[])
	{
		$abonos = 0;

		$tmp = $this->db->select("plnpresnom", [
				'[><]plnnomina(b)' => ['plnpresnom.idplnnomina' => 'id']
			], 
			[
				"plnpresnom.monto"
			],
			[
				'AND' => [
					'plnpresnom.idplnprestamo' => $this->pre->id,
					'b.fecha[<]' => $args['fecha']
				]
			]
		);

		if ($tmp) {
			$abonos += totalCampo($tmp, 'monto');
		}

		$tmpdir = $this->db->select(
			'plnpresabono', 
			['monto'],
			[
				'AND' => [
					'idplnprestamo' => $this->pre->id,
					'fecha[<]' => $args['fecha']
				]
			]
		);

		if ($tmpdir) {
			$abonos += totalCampo($tmpdir, 'monto');
		}

		return ($this->pre->monto - $abonos);
	}

	public function get_vencimiento()
	{
		$meses = ceil($this->pre->monto/$this->pre->cuotamensual);
		$sql   = "select DATE_ADD('{$this->pre->iniciopago}', INTERVAL {$meses} MONTH) as fecha";
		$res   = $this->db->query($sql)->fetchAll();
		
		return $res[0]['fecha'];
	}

	public function get_datos_impresion($args = [])
	{
		$gen = new General();
		$ltr = new NumberToLetterConverter();
		
		$empleado = $this->get_empleado();
		$empresa  = $gen->get_empresa([
			'id'  => $empleado->idempresadebito, 
			'uno' => TRUE
		]);

		return [
			't_empresa'         => 'EMPRESA: ',
			'v_empresa'         => $empresa['nomempresa'],
			'ln_empresa'        => str_repeat('_', 90),
			'titulo'            => 'ANTICIPOS DE SUELDOS',
			'numero'            => "No. {$this->pre->id}",
			't_vale'            => 'VALE POR:',
			'v_cantidad_letras' => $ltr->to_word($this->pre->monto, 'GTQ'),
			'v_en_numero'       => 'Q. ' . number_format($this->pre->monto, 2),
			'ln_principal'      => str_repeat('_', 96),
			't_cantidad_letras' => '(Cantidad en letras)',
			't_en_numero'       => '(En números)',
			't_texto'           => 'Valor de anticipo de salario recibido, para cancelar de la siguiente forma:',
			't_moneda'          => 'Q.',
			'v_mensual'         => number_format($this->pre->cuotamensual, 2),
			'ln_mensual'        => str_repeat('_', 13),
			't_mensual'         => '(Mensuales)',
			't_apartirde'       => 'A partir de: ',
			'v_iniciopago'      => formatoFecha($this->pre->iniciopago, 1),
			'ln_apartirde'      => str_repeat('_', 15),
			't_vence'           => 'Con vencimiento el: ',
			'v_liquidacion'     => formatoFecha($this->get_vencimiento(), 1),
			'ln_vence'          => str_repeat('_', 15),
			't_conforme'        => 'Recibí conforme: ',
			'v_empleado'        => $empleado->nombre.' '.$empleado->apellidos,
			'ln_conforme'       => str_repeat('_', 85),
			't_anticipo'        => 'ANTICIPO A SUELDOS.',
			'ln_autorizado'     => str_repeat('_', 27),
			't_autorizado'      => 'Autorizado', 
			't_nota'            => 'NOTA: Me comprometo a no solicitar otro préstamo hasta cancelar mi saldo.'
		];
	}

	/**
	 * Es necesario asegurarse que el archivo ayuda.php haya sido cargado 
	 * desde donde se esté llamando la función.
	 * Valida si no existen planillas pendientes de cierre relacionadas a este préstamo
	 * En caso de no haber, verifica que todos los descuentos realizados y abonos directos
	 * ingresados sumen el total del préstamo para dejarlo finalizado.
	 * @return [void]
	 */
	public function finalizar()
	{
		if ($this->pre->finalizado == 0) {
			$tmp = $this->db->select("plnpresnom", [
					'[><]plnnomina(b)' => ['plnpresnom.idplnnomina' => 'id']
				], 
				["plnpresnom.monto"],
				[
					'AND' => [
						'plnpresnom.idplnprestamo' => $this->pre->id,
						'b.terminada' => 0
					]
				]
			);

			if (count($tmp) == 0) {
				$descuentos = $this->get_descuentos_planilla();
				$abonos     = $this->get_otro_abonos();
				$total      = ($descuentos+$abonos);

				if ($total == $this->pre->monto) {
					$this->guardar([
						'liquidacion' => date('Y-m-d'),
						'saldo'       => 0
					]);
				}
			}
		}
	}

	public function get_proyeccion($args = [])
	{
		$saldo  = $this->get_saldo_anterior(['fecha' => $args['fdel']]);
		$cuotas = ceil(($saldo/$this->pre->cuotamensual));
		$fdel   = new DateTime($args['fdel']);
		$inicio = new DateTime($this->pre->iniciopago);
		$fecha  = $fdel > $inicio ? $fdel : $inicio;
		$datos  = [];

		for ($i=1; $i<=$cuotas ; $i++) { 
			$pago  = $saldo > $this->pre->cuotamensual ? $this->pre->cuotamensual : $saldo;
			$saldo -= $pago;
			$fecha->add(new DateInterval("P1M"));
			
			$datos[] = [
				'v_nombre'			  => "Pago # {$i}",
				'v_fecha'             => $fecha->format('d/m/Y'),
				'v_descuento_mensual' => $pago,
				'v_saldo_anterior'    => $saldo
			];
		}

		return $datos;
	}
}
