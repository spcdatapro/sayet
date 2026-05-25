(function(){

    var periodocontctrl = angular.module('cpm.periodocontctrl', ['cpm.pcontsrvc']);

    periodocontctrl.controller('periodoContableCtrl', ['$scope', 'periodoContableSrvc', 'toaster', '$confirm', function($scope, periodoContableSrvc, toaster, $confirm){

        $scope.params = {anio: moment().year(), vercerrados: 0};

        $scope.elPeriodo = {
            del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), abierto: 0,
            mes: (moment().month() + 1).toString(), anio: moment().year()
        };
        $scope.losPeriodos = [];
        $scope.meses = [{ id: '1', nombre: 'Enero' }, { id: '2', nombre: 'Febrero' }, { id: '3', nombre: 'Marzo' }, { id: '4', nombre: 'Abril' },
                        { id: '5', nombre: 'Mayo' }, { id: '6', nombre: 'Junio' }, { id: '7', nombre: 'Julio' }, { id: '8', nombre: 'Agosto' },
                        { id: '9', nombre: 'Setiembre' }, { id: '10', nombre: 'Octubre' }, { id: '11', nombre: 'Noviembre' }, { id: '12', nombre: 'Diciembre' }];

        function procData(data){
            for(var i = 0; i < data.length; i++){
                data[i].mes = (moment(data[i].del).month() + 1).toString();
                data[i].anio = moment(data[i].del).year();
                data[i].del = moment(data[i].del).toDate();
                data[i].al = moment(data[i].al).toDate();
                data[i].abierto = parseInt(data[i].abierto);
            }
            return data;
        }

        $scope.resetPeriodo = function(){
            $scope.elPeriodo = { id: 0, del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), abierto: 0, mes: (moment().month() + 1).toString(), anio: moment().year() };
        };

        $scope.getLstPeriodos = function(){
            $scope.params.vercerrados = $scope.params.vercerrados !== null && $scope.params.vercerrados !== undefined ? $scope.params.vercerrados : 0;
            $scope.params.anio = $scope.params.anio !== null && $scope.params.anio !== undefined ? $scope.params.anio : null;
            periodoContableSrvc.lstPeriodosCont($scope.params.vercerrados, $scope.params.anio).then(function(d){
                $scope.losPeriodos = procData(d);
            });
        };

        $scope.getPeriodo = function(idperiodo){
            periodoContableSrvc.getPeriodoCont(+idperiodo).then(function(d){
                $scope.elPeriodo = procData(d)[0];
                goTop();
            });
        };

        function setData(obj){
            obj.del = moment().year(obj.anio).month(parseInt(obj.mes) - 1).startOf('month').toDate();
            obj.al = moment().year(obj.anio).month(parseInt(obj.mes) - 1).endOf('month').toDate();
            obj.delstr = moment(obj.del).format('YYYY-MM-DD');
            obj.alstr = moment(obj.al).format('YYYY-MM-DD');
            obj.abierto = obj.abierto != null && obj.abierto !== undefined ? obj.abierto : 0;
            return obj;
        }

        $scope.addPeriodo = function(obj){
            obj = setData(obj);
            if (!existePeriodo(obj)) {
                if(moment(obj.del).isBefore(obj.al)){
                    periodoContableSrvc.editRow(obj, 'c').then(function(){
                        $scope.getLstPeriodos();
                        $scope.resetPeriodo();
                    });
                } else {
                toaster.pop({ type: 'error', title: 'Error en las fechas.', body: 'La fecha inicial no puede ser mayor a la fecha final.', timeout: 7000 });
                $scope.elPeriodo.al = moment(obj.del).endOf('month').toDate();
                }
            } else {
                toaster.pop({ type: 'error', title: 'Error al crear período contable.', body: 'El período ya existe.', timeout: 7000 });
            }
        };

        $scope.updPeriodo = function(data){
            data = setData(data);
            periodoContableSrvc.editRow(data, 'u').then(function(){
                $scope.getLstPeriodos();
                $scope.getPeriodo(data.id);
            });
        };

        $scope.delPeriodo = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar el período del ' + obj.delstr + ' al ' + obj.alstr + '?', title: 'Eliminar período contable', ok: 'Sí', cancel: 'No'}).then(function() {
                periodoContableSrvc.editRow({ id:obj.id }, 'd').then(function(){
                    $scope.getLstPeriodos();
                    $scope.resetPeriodo();
                });
            });
        };

        function existePeriodo(obj) {
            let existe = false;

            for (let i = 0; i < $scope.losPeriodos.length; i++) {
                if ($scope.losPeriodos[i].mes === obj.mes && $scope.losPeriodos[i].anio === obj.anio) {
                    existe = true;
                    break;
                }
            }
            return existe;
        }

        $scope.getLstPeriodos();
    }]);

}());
