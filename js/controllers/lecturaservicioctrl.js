(function(){

    var lecturaservicioctrl = angular.module('cpm.lecturaservicioctrl', []);

    lecturaservicioctrl.controller('lecturaServicioCtrl', ['$scope', 'servicioPropioSrvc', '$filter', '$confirm', 'toaster', 'authSrvc', '$uibModal', 'jsReportSrvc', '$window', function($scope, servicioPropioSrvc, $filter, $confirm, toaster, authSrvc, $uibModal, jsReportSrvc, $window){

        $scope.params = { idusuario: 0, mes: moment().month() + 1, anio: moment().year(), idservicio: '', idproyecto: undefined };
        $scope.usrdata = {};
        $scope.lecturas = [];
        $scope.meses = [
            {id: 1, mes: 'Enero'}, {id: 2, mes: 'Febrero'}, {id: 3, mes: 'Marzo'}, {id: 4, mes: 'Abril'}, {id: 5, mes: 'Mayo'}, {id: 6, mes: 'Junio'},
            {id: 7, mes: 'Julio'}, {id: 8, mes: 'Agosto'}, {id: 9, mes: 'Septiembre'}, {id: 10, mes: 'Octubre'}, {id: 11, mes: 'Noviembre'}, {id: 12, mes: 'Diciembre'}
        ];
        $scope.proyectos = [];

        authSrvc.getSession().then(function(usrLogged){
            $scope.usrdata = usrLogged;
            $scope.params.idusuario = +$scope.usrdata.uid;
            servicioPropioSrvc.lstProyectosUsuario($scope.params.idusuario).then(function(d){ $scope.proyectos = d; });
        });

        function setDatalectura(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].lectura = parseFloat(parseFloat(d[i].lectura).toFixed(2));
                d[i].estatus = parseInt(d[i].estatus);
                d[i].fechacorte = moment(d[i].fechacorte).isValid() ? moment(d[i].fechacorte).toDate() : undefined;
            }
            return d;
        }

        $scope.getLecturas = function(){
            //console.log($scope.params);
            servicioPropioSrvc.getLectura($scope.params.idusuario, $scope.params.mes, $scope.params.anio, $scope.params.idproyecto).then(function(d){
                $scope.lecturas = setDatalectura(d);
            });
        };

        function setFechaCorte(fecha){
            for(var i = 0; i < $scope.lecturas.length; i++){
                if(+$scope.lecturas[i].estatus == 1){
                    $scope.lecturas[i].fechacorte = fecha;
                    $scope.updLectura($scope.lecturas[i]);
                }
            }
        }

        $scope.replicaFecha = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalFechaCorte.html',
                controller: 'ModalFCorteCtrl'
            });

            modalInstance.result.then(function(fecha){
                setFechaCorte(fecha);
            }, function(){ return 0; });
        };

        $scope.updLectura = function(obj){
            //console.log(obj); return;
            obj.fechacortestr = obj.fechacorte != null && obj.fechacorte != undefined ? moment(obj.fechacorte).format('YYYY-MM-DD') : '';
            obj.lectura = obj.lectura != null && obj.lectura != undefined ? obj.lectura : '';
            servicioPropioSrvc.editRow(obj, 'ul').then(function(){
                toaster.pop('info', 'Registro de lectura', 'La lectura fue tomada exitosamente');
            });
        };

        $scope.envioFacturacion = function(){
            $confirm({text: '¿Seguro(a) de enviar a facturación? (Una vez enviado, no podrá modificar los datos)', title: 'Envío a facturación', ok: 'Sí', cancel: 'No'}).then(function() {
                $scope.lecturas.forEach(function(item){
                    if(+item.estatus == 1){
                        if($scope.params.idservicio != ''){ $scope.params.idservicio += ','; }
                        $scope.params.idservicio += item.id;
                    }
                });
                //console.log($scope.params);
                servicioPropioSrvc.editRow($scope.params, 'enviofact').then(function(){ $scope.getLecturas(); });
            });
        };

        $scope.printLectura = function(){
            var test = false;
            jsReportSrvc.getPDFReport(test ? 'SkGIDlETe' : 'rkST3lN6g', $scope.params).then(function(pdf){
                $window.open(pdf);
            });
        };

    }]);
    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    lecturaservicioctrl.controller('ModalFCorteCtrl', ['$scope', '$uibModalInstance', '$filter', 'toaster', function($scope, $uibModalInstance, $filter, toaster){
        $scope.fechacorte = moment().toDate();

        $scope.ok = function () {
            $uibModalInstance.close($scope.fechacorte);
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);

}());