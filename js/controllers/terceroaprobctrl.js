(function () {

    var aprobctrl = angular.module('cpm.terceroaprob', []);

    aprobctrl.controller('terceroAprob', ['$scope', 'jsReportSrvc', 'servicioBasicoSrvc', '$uibModal', 'compraSrvc', 'toaster',
        'authSrvc', function ($scope, jsReportSrvc, servicioBasicoSrvc, $uibModal, compraSrvc, toaster, authSrvc) {

            $scope.compras = [];
            $scope.servicios = [];
            $scope.params = {};
            $scope.revisando = true;
            $scope.enviar = { id: [] };
            $scope.todos = 0;
            var ids = [];
            var usr = {};
            $scope.totales = { cuantas: 0 };

            // traer datos necesarios
            servicioBasicoSrvc.getContadores(0).then(function (d) { $scope.servicios = d; });
            authSrvc.getSession().then(function (d) { usr = d; });

            $scope.getCompras = function (tipo) {
                // modificar variable global para saber el tipo de proceso
                $scope.revisando = tipo == 1 ? true : false;
                var params = $scope.params;

                // convertir fecahs en str
                params.fdel = $scope.params.fdel != undefined ? moment($scope.params.fdel).format('YYYY-MM-DD') : undefined;
                params.fal = $scope.params.fal != undefined ? moment($scope.params.fal).format('YYYY-MM-DD') : undefined;

                // traer compras 
                servicioBasicoSrvc.getPendientes(params.idservicio, params.fdel, params.fal, tipo).then(function (d) {
                    $scope.compras = d;
                });

                $scope.enviar.fecha = undefined;

                if (!$scope.revisando) {
                    // asignar fecha de autorizacion
                    $scope.enviar.fecha = moment().toDate();

                    // recordar verificar fecha
                    toaster.pop('info', 'Fecha de autorización',
                        `Favor verificar que la fecha de autorización sea la correcta y no este en blanco.`, 'timeout:1500');
                }
            }

            // funcion para seleccionar/deseleccionar todos
            $scope.selTodos = function (agregar) {
                $scope.compras.forEach(pendiente => {
                    pendiente.revisar = $scope.todos;
                    // si se esta seleccionando empujar todos los ids
                    if (agregar == 1) {
                        ids.push(pendiente.id);
                    }
                });
                // si se esta deseleccionando todo eliminar el array completo de ids
                if (agregar == 0) {
                    ids = [];
                }
                $scope.totales.cuantas = ids.length;
            }

            // funcion para seleccionar/deseleccionar uno
            $scope.agregar = function (id, agregar) {
                // si se agrega empujar id al array de ids
                if (agregar == 1) {
                    ids.push(id);
                } else {
                    // si se deselecciona (agregar == 0) obtener indice del que se deselcciona y eliminar
                    var idc = getIndice(id);
                    ids.splice(idc, 1);
                }
                $scope.totales.cuantas = ids.length;
            }

            // funcion para obtener indice de array ids
            function getIndice(id) {
                for (var i = 0; i < ids.length; i++) {
                    // si id seleccionado es igual al id del array devuelve indice
                    if (id == ids[i]) {
                        return i;
                    }
                }
            }

            $scope.actualizar = function () {
                $scope.enviar.ids = ids;
                $scope.enviar.idusuario = usr.uid;
                $scope.enviar.fechastr = $scope.enviar.fecha != undefined ? moment($scope.enviar.fecha).format('YYYY-MM-DD') : undefined;
                if (!$scope.revisando && $scope.enviar.fechastr == undefined) {
                    toaster.pop('error', 'Fecha de autorización',
                        `Favor verificar que la fecha de autorización sea la correcta y no este en blanco.`, 'timeout:1500');
                } else {
                    servicioBasicoSrvc.editRow($scope.enviar, 'upr').then(function (d) { 
                        $scope.getCompras(d);
                        ids = [];
                        $scope.enviar = {};
                    });
                }
            }

        }]);
}());