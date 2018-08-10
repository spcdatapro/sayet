<?php
/**
 * CLase para consultas para reportes
 */
class Reporte extends Principal
{
	
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Es oblitarios que en el arreglo venga el atributo  'fal'
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_antiguedad_empleado($args = [])
	{
		$condiciones = "";

		if (elemento($args, 'empleado')) {
			$condiciones .= " and a.id=".$args["empleado"];
		}

		if (elemento($args, 'empresa')) {
			$condiciones .= " and a.idempresadebito=".$args["empresa"];
		}

		$fecha = $args['fal'];


		$sql = <<<EOT
select 
	a.id as codigo, 
    concat(a.nombre, ifnull(a.apellidos,'')) as nombre,
    DATE_FORMAT(a.ingreso, '%d/%m/%Y') as ingreso,
    a.idempresadebito,
    b.nomempresa,
    TIMESTAMPDIFF(DAY, a.ingreso, '{$fecha}') as dias,
    TIMESTAMPDIFF(MONTH, a.ingreso, '{$fecha}') as meses,
    TIMESTAMPDIFF(YEAR, a.ingreso, '{$fecha}') as anios
from plnempleado a
join empresa b on b.id = a.idempresadebito
where a.ingreso is not null
{$condiciones}
order by b.nomempresa, a.nombre;
EOT;

		$tmp = $this->db->query($sql)->fetchAll();
		$res = [];

		foreach ($tmp as $key => $value) {
			if (isset($res[$value['idempresadebito']])) {
				$res[$value['idempresadebito']]['empleados'][] = $value;
			} else {
				$res[$value['idempresadebito']] = [
					'nombre' => $value['nomempresa'],
					'empleados' => [$value]
				];
			}
		}

		return $res;
	}
}