(function(){

    var tranlecturasaguactrl = angular.module('cpm.tranlecturasaguactrl', []);

    tranlecturasaguactrl.controller('tranLecturasAguaCtrl', ['$scope', 'facturacionAguaSrvc', '$confirm', 'empresaSrvc', 'authSrvc', 'servicioPropioSrvc', 'toaster', ($scope, facturacionAguaSrvc, $confirm, empresaSrvc, authSrvc, servicioPropioSrvc, toaster) => {
		$scope.params = {
			idempresa: undefined, fechavence: moment().endOf('month').toDate()
		};
		$scope.empresas = [];
		$scope.usrdata = {};
		$scope.lecturas = [];

		authSrvc.getSession().then((usrLogged) => {
            empresaSrvc.lstEmpresas().then((d) => {
                $scope.empresas = d;
                $scope.params.idempresa = usrLogged.workingon.toString();
            });
            $scope.usrdata = usrLogged;
		});

		prepareData = (d) => {
			for(let i = 0; i < d.length; i++){
				d[i].lectura = parseFloat(d[i].lectura);
				d[i].descuento = parseFloat(d[i].descuento);
			}
			return d;
		};
		
		$scope.loadPendientes = () => {
			$scope.params.fvencestr = moment($scope.params.fechavence).format('YYYY-MM-DD');
			facturacionAguaSrvc.lstCargosPendientesFEL($scope.params).then((d) => {
				$scope.lecturas = prepareData(d);
			});
		};

		$scope.recalcular = (lectura) => {
			const obj = {
				id: lectura.id,
				lectura: lectura.lectura,
				fechacortestr: lectura.fechacorte,
				descuento: lectura.descuento,
				conceptoadicional: lectura.conceptoadicional
			};
			//console.log(obj); return;
			servicioPropioSrvc.editRow(obj, 'ul').then(() => {
				$scope.loadPendientes();
				toaster.pop('success', 'Lectura contadores', 'Los datos fueron actualizados...');
			});
		};

    }]);

}());
