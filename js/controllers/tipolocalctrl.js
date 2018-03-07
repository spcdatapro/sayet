(function(){

    var tipolocalctrl = angular.module('cpm.tipolocalctrl', []);

    tipolocalctrl.controller('tipoLocalCtrl', ['$scope', 'tipoLocalSrvc', '$confirm', function($scope, tipoLocalSrvc, $confirm){

        $scope.tipo = {};
        $scope.tipos = [];
        $scope.grpBtnTipo = {i: false, p:false, e: false, u: false, c: false, d: false, a: true};
        $scope.sl = {tipo: true};

        $scope.confGrpBtn = function(grp, i, u, d, a, e, c, p){
            var instruccion = "$scope." + grp + ".i = i; $scope." + grp + ".u = u; $scope." + grp + ".d = d; $scope." + grp + ".a = a; $scope." + grp + ".e = e; $scope." + grp + ".c = c; $scope." + grp + ".p = p;";
            eval(instruccion);
        };

        $scope.getLstTipos = function(){
            tipoLocalSrvc.lstTiposLocal().then(function(d){
                for(var i = 0; i < d.length; i++){
                    d[i].id = parseInt(d[i].id);
                    d[i].esrentable = parseInt(d[i].esrentable);
                }
                $scope.tipos = d;
            });
        };

        $scope.getTipo = function(idtipo){
            tipoLocalSrvc.getTipoLocal(idtipo).then(function(d){
                for(var i = 0; i < d.length; i++){
                    d[i].id = parseInt(d[i].id);
                    d[i].esrentable = parseInt(d[i].esrentable);
                    d[i].orden = parseInt(d[i].orden);
                }
                $scope.tipo = d[0];
                $scope.confGrpBtn('grpBtnTipo', false, false, true, true, true, false, false);
                $scope.sl.tipo = true;
            });
        };

        $scope.resetTipo = function(){
            $scope.tipo = { descripcion: '', esrentable: 1, orden: 0 };
            $scope.confGrpBtn('grpBtnTipo', false, false, false, true, false, false, false);
            $scope.sl.tipo = true;
        };

        $scope.cancelEditTipo = function(){
            if($scope.tipo.id > 0){
                $scope.getTipo($scope.tipo.id);
            }else{
                $scope.resetTipo();
            }
            $scope.confGrpBtn('grpBtnTipo', false, false, false, true, false, false, false);
            $scope.sl.tipo = true;
        };

        $scope.startEditTipo = function(){
            $scope.sl.tipo = false;
            $scope.confGrpBtn('grpBtnTipo', false, true, true, false, false, true, false);
            goTop();
        };

        $scope.imprimirTipo = function(){ console.log('Función pendiente...') };

        $scope.nuevoTipo = function(){
            $scope.resetTipo();
            $scope.sl.tipo = false;
            $scope.confGrpBtn('grpBtnTipo', true, false, false, false, false, true, false);
        };

        $scope.addTipo = function(obj){
            obj.esrentable = obj.esrentable != null && obj.esrentable != undefined ? obj.esrentable : 0;
            tipoLocalSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstTipos();
                $scope.getTipo(+d.lastid);
            });
        };

        $scope.updTipo = function(obj){
            obj.esrentable = obj.esrentable != null && obj.esrentable != undefined ? obj.esrentable : 0;
            tipoLocalSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstTipos();
                $scope.getTipo(obj.id);
            });
        };

        $scope.delTipo = function(idtipo){
            $confirm({ text: "¿Esta seguro(a) de eliminar este tipo de local?", title: 'Eliminar tipo de local', ok: 'Sí', cancel: 'No'}).then(function() {
                tipoLocalSrvc.editRow({id: idtipo}, 'd').then(function(){ $scope.getLstTipos(); $scope.resetTipo(); });
            });
        };

        $scope.getLstTipos();

    }]);

}());
