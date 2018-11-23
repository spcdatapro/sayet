<?php

/**
* 
*/
class Vacaciones extends Empleado
{
    public function get_vacaciones($args=[])
	{
		$where = ["idplnempleado" => $this->emp->id];

		if (isset($args["anio"])) {
			$where["anio"] = $args["anio"];
        }
        
        if (elemento($args, 'fdel')) {
			$where['fechainicio[>=]'] = $args['fdel'];
		}

		if (isset($args["anulado"])) {
			$where["anulado"] = $args["anulado"];
		}

		if (isset($args["pagadas"])) {
			$where["pagadas"] = $args["pagadas"];
        }

		if (count($where) > 1) {
			$condicion = ['AND' => $where];
		} else {
			$condicion = $where;
        }
        
        if (isset($args["reciente"])) {
            $condicion["ORDER"] = "fechainicio DESC";
        }

		return $this->db->select(
			'plnvacaciones', 
			['*'], 
			$condicion
		);
    }
    
    public function getDiasPagados($args=[])
    {
        $tmp = $this->get_vacaciones([
            "anio" => $args["anio"],
            "pagadas" => 1,
            "anulado" => 0
        ]);

        if (count($tmp) > 0) {
            return $tmp[0];
        } else {
            return false;
        }
    }

    public function setDiasVacaciones($args=[])
    {
        
        if (empty($this->emp->reingreso)) {
            $ingreso = new DateTime($this->emp->ingreso);
        } else {
            $ingreso = new DateTime($this->emp->reingreso);
        }

        $dusados = ["pagadas" => 0, "anulado" => 0];

        if ($ingreso->format('Y') == $args["anio"]) {
            $ingreso  = new DateTime($ingreso);
            $finAnio  = new DateTime($args["anio"]."-12-31");
            $interval = $ingreso->diff($finAnio);
            $dias     = ($interval->format('%a')+1);

            if ($dias > 366) {
                $diasLaborados = 366;
            } else {
                $diasLaborados = $dias;
            }

            $vacasdias = (($diasLaborados*21)/366);
            
            $dusados["anio"] = $args["anio"];
        } else {
            $vacasdias = 21;

            if (isset($args["vacasultimas"])) {
                $dusados["fdel"] = $args["vacasultimas"];
            } else {
                $dusados["anio"] = $args["anio"];
            }
        }

        $vacasusados = 0;

        $usados = $this->get_vacaciones($dusados);

        foreach ($usados as $key => $value) {
            $vacasusados += $value["dias"];
        }

        $datos = [
            "vacasusados" => $vacasusados,
            "vacasdias" => $vacasdias-$vacasusados,
            "vacasgozar" => $args["vacasgozar"]
        ];

        if (isset($args["vacasultimas"])) {
            $datos["vacasultimas"] = $args["vacasultimas"];
        }

        $this->guardar($datos);
    }

    /*public function diasPagar($args=[])
    {
        if (empty($this->emp->reingreso)) {
            $ingreso = new DateTime($this->emp->ingreso);
        } else {
            $ingreso = new DateTime($this->emp->reingreso);
        }

        $finAnio = new DateTime($args["anio"]."-12-31");

        $dias = 0;

        $usados = $this->get_vacaciones([
            "anio" => $args["anio"],
            "pagadas" => 0,
            "anulado" => 0
        ]);

        foreach ($usados as $key => $value) {
            $dias += $value["dias"];
        }

        if ($ingreso->format("Y") == $args["anio"]) {
            # code...
        } else {
            # code...
        }
        
    }*/
}