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

        $diasAnio = 366;
        $dusados = ["pagadas" => 0, "anulado" => 0];

        if ($ingreso->format('Y') == $args["anio"]) {
            $finAnio  = new DateTime($args["anio"]."-12-31");
            $interval = $ingreso->diff($finAnio);
            $dias     = ($interval->format('%a')+1);

            if ($dias > $diasAnio) {
                $diasLaborados = $diasAnio;
            } else {
                $diasLaborados = $dias;
            }

            $vacasdias = (($diasLaborados*21)/$diasAnio);
            
            $dusados["anio"] = $args["anio"];
        } else {
            $vacasdias = 21;

            if (elemento($args, "vacasultimas")) {
                $dusados["fdel"] = $args["vacasultimas"];
            } else {
                $dusados["anio"] = $args["anio"];
            }
        }

        $sueldoDia = ($this->emp->sueldo/21);
        $vacasTotal = ($sueldoDia * $vacasdias);
        $vacasDescuento = ($sueldoDia * $args["vacasusados"]);

        $this->guardar_extra($args["anio"], [
            "vacasingreso" => $ingreso->format('Y-m-d'),
            "vacasultimas" => elemento($args, "vacasultimas"),
            "vacasusados" => $args["vacasusados"],
            "vacasgozar" => $args["vacasgozar"],
            "vacasdias" => $vacasdias,
            "vacastotal" => $vacasTotal,
            "vacasdescuento" => $vacasDescuento,
            "vacasliquido" => ($vacasTotal-$vacasDescuento)
        ]);
    }

    public function getDatosVacas($anio)
    {
        $tmp = new General();
        
        return $tmp->getDatosVacas([
            "uno" => true,
            "idplnempleado" => $this->emp->id
        ]);
    }
}