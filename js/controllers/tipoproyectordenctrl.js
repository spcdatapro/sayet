(function(){
	angular.module('cpm.tpo', [])
			.factory('servicioTPO', ['comunFact', function(comunFact){
				var urlBase = 'php/tipoproyectoorden.php';

				return {
					lista: function(){
						return comunFact.doGETJ(urlBase + '/lista');
					},
					guardar: function(datos){
						return comunFact.doPOST(urlBase + '/guardar', datos);
					},
					eliminar: function(datos){
						return comunFact.doPOST(urlBase + '/d', datos);
					}
				};
			}])
			.controller('MntTipoProyectoOrden', ['$scope', 'tipoProyectoSrvc', 'tipoLocalSrvc', 'servicioTPO',
				function($scope, tipoProyectoSrvc, tipoLocalSrvc, servicioTPO){
					$scope.listaTipoProyecto = [];
					$scope.listaTipoLocal = [];
					$scope.ordenes = [];

					$scope.guardarOrden = function() {
						servicioTPO.guardar($scope.data).then(function(res){
							$scope.data = {};

							if (res.update == 0) {
								$scope.cargarOrdenes();
							}

							alert(res.mensaje);
						});
					};

					$scope.eliminarOrden = function(o) {
						if (confirm("¿Desea continuar?")) {
							servicioTPO.eliminar(o).then(function(res){
								$scope.cargarOrdenes();
								alert(res.mensaje);
							});
						}
					};

					$scope.cargarOrdenes = function(){
						servicioTPO.lista().then(function(res){
							$scope.ordenes = res;
						});
					};

					$scope.editarOrden = function(o){
						$scope.data = o;
						$scope.data.orden = parseInt(o.orden);
					};

					tipoProyectoSrvc.lstTipoProyecto().then(function(res){
						$scope.listaTipoProyecto = res;
					});

					tipoLocalSrvc.lstTiposLocal().then(function(res){
						$scope.listaTipoLocal = res;
					});

					$scope.cargarOrdenes();
				}
			]);
}());