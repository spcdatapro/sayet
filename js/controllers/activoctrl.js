(function(){

    var activoctrl = angular.module('cpm.activoctrl', ['cpm.activosrvc']);

    activoctrl.controller('activoCtrl', ['$scope', '$rootScope', 'activoSrvc', 'empresaSrvc','tipoactivoSrvc','activoAdjuntoSrvc','tipoAdjuntoSrvc','Upload','DTOptionsBuilder','municipioSrvc', 'authSrvc', '$confirm', '$route', '$location', 'localStorageSrvc', '$filter', function($scope, $rootScope, activoSrvc, empresaSrvc, tipoactivoSrvc,activoAdjuntoSrvc,tipoAdjuntoSrvc,Upload,DTOptionsBuilder,municipioSrvc, authSrvc, $confirm, $route, $location, localStorageSrvc, $filter){

        $scope.elActivo = {nomclienteajeno: ''};
        $scope.lasEmpresas = [];
        $scope.losActivos = [];
        $scope.losTipoActivo = [];
        $scope.elActivoAdjunto = {};
        $scope.losActivosAdjuntos = [];
        $scope.losTipoAdjunto = [];
        $scope.losMunicipios = [];
        $scope.usrdata = {};
        $scope.lasBitacoras = [];
        $scope.laBitacora = {};
        $scope.progressPercentage = 0;
        $scope.activostr = '';
        $scope.proyectos = [];
        $scope.objini = {};
        $scope.grpBtnActivo = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.slactivo = true;
        $scope.showForm = {activo: false, adjuntos: false, bitacora: false};

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap().withOption('paging', false);

        authSrvc.getSession().then(function(usrLogged){ $scope.usrdata = usrLogged; });

        $scope.resetElActivo = function(){
            $scope.elActivo = {
                id : 0, idempresa : 0, departamento : 0, finca : '', folio : '', libro : '', horizontal : 0, direccion_cat : '', direccion_mun : '', iusi : parseFloat(0.0), por_iusi : parseFloat(0.0), valor_registro : parseFloat(0.0),
                metros_registro : parseFloat(0.0), valor_dicabi : parseFloat(0.0), metros_dicabi : parseFloat(0.0), valor_muni : parseFloat(0.0), metros_muni : parseFloat(0.0), observaciones : '', tipo_activo : 0, nombre_corto : '',
                nombre_largo : '', zona : 0, nomclienteajeno: '', debaja: 0, fechabaja: undefined
            };
            $scope.lasBitacoras = [];
            $scope.laBitacora = {};
            $scope.proyectos = [];
            $scope.losActivosAdjuntos = [];
            $scope.elActivoAdjunto = {};
        };

        $scope.resetElActivo();

        $scope.getlstEmpresas = function(){
            empresaSrvc.lstEmpresas().then(function(d){
                for(var x = 0; x < d.length; x++){
                    d[x].id = parseInt(d[x].id);
                    d[x].propia = parseInt(d[x].propia);
                }
                $scope.lasEmpresas = d;
            });
        };
        $scope.getlstTipoActivo = function() {
            tipoactivoSrvc.lstTipoActivo().then(function (d) {
                $scope.losTipoActivo = d;
            });
        };
        $scope.getlstTipoAdjunto = function() {
            tipoAdjuntoSrvc.lstTipoAdjunto().then(function (d) {
                $scope.losTipoAdjunto = d;
            });
        };
        $scope.getlstMunicipios = function(){
            municipioSrvc.lstMunicipios().then(function(d){
                $scope.losMunicipios = d;
            });
        };

        $scope.getLstActivosAdjuntos = function() {
            activoAdjuntoSrvc.getActivoAdjunto($scope.elActivo.id).then(function(d){
                $scope.losActivosAdjuntos = d;
            });
        };

        function procDataActivos(d){
            for(var i = 0; i < d.length; i++){
                d[i].horizontal = parseInt(d[i].horizontal);
            }
            return d;
        }

        $scope.getLstActivos = function(){
            activoSrvc.lstActivo().then(function(d){
                $scope.losActivos = procDataActivos(d);
                $scope.objini = localStorageSrvc.get('idactivo');
                //console.log($scope.objini);
                if($scope.objini != null && $scope.objini != undefined){
                    localStorageSrvc.clear('idactivo');
                    $scope.getActivo(parseInt($scope.objini));
                }
            });
        };

        $scope.$watch('elActivo.file', function () {
            //$scope.upload([$scope.file]);
            //console.log($scope.nuevo);
        });

        $scope.getActivo = function(id){
            $scope.resetElActivo();
            activoSrvc.getActivo(id).then(function(d){
                d.fhcreacion = d.fhcreacion != null && d.fhcreacion !== undefined ? moment(d.fhcreacion).toDate() : d.fhcreacion;
                d.actualiza_info = d.actualiza_info != null && d.actualiza_info !== undefined ? moment(d.actualiza_info).toDate() : d.actualiza_info;
                d.multilotes = parseInt(d.multilotes);
                d.debaja = +d.debaja;
                d.fechacompra = d.fechacompra != null && d.fechacompra !== undefined ? moment(d.fechacompra).toDate() : undefined;
                d.fechabaja = d.fechabaja != null && d.fechabaja !== undefined ? moment(d.fechabaja).toDate() : undefined;
                $scope.elActivo = d;
                $scope.activostr = ' del activo ' + d.finca + '-' + d.folio + '-'+ d.libro;
                empresaSrvc.getEmpresa(d.idempresa).then(function(resEmpresa){
                    $scope.elActivo.objEmpresa = resEmpresa[0];
                });

                tipoactivoSrvc.getTipoActivo(d.tipo_activo).then(function(resTipoActivo){
                    $scope.elActivo.objTipoActivo = resTipoActivo[0];
                });

                municipioSrvc.getMunicipio(d.departamento).then(function(resMunicipio){
                    $scope.elActivo.objDepartamento = resMunicipio[0];
                });

                activoSrvc.lstBitacora(id).then(function(d){
                    for(var i = 0; i < d.length; i++){ d[i].fhbitacora = moment(d[i].fhbitacora).toDate(); };
                    $scope.lasBitacoras = d;
                });

                activoSrvc.lstProyectosActivo(parseInt(id)).then(function(d){ $scope.proyectos = d; });

                //$scope.getlstEmpresas();
                //$scope.getlstTipoActivo();
                //$scope.getlstMunicipios();
                $scope.getLstActivosAdjuntos();

                $scope.confGrpBtn('grpBtnActivo', false, false, true, true, true, false);
                $scope.slactivo = true;
                $scope.showForm.activo = true;
                goTop();
            });
        };

        $scope.setClienteRequerido = function(espropia){
            document.getElementById('txtNomAjeno').required = (espropia == 0);
            $scope.elActivo.nomclienteajeno = '';
        };

        $scope.confGrpBtn = function(grp, i, u, d, a, e, c){
            var instruccion = "$scope." + grp + ".i = i; $scope." + grp + ".u = u; $scope." + grp + ".d = d; $scope." + grp + ".a = a; $scope." + grp + ".e = e; $scope." + grp + ".c = c;";
            eval(instruccion);
        };

        $scope.btnAA = function(){
            $scope.slactivo = false;
            $scope.resetElActivo();
            $scope.confGrpBtn('grpBtnActivo', true, false, false, false, false, true);
            goTop();
        };

        $scope.btnCA = function(){
            if($scope.elActivo.id > 0){
                $scope.getActivo($scope.elActivo.id);
            }else{
                $scope.resetElActivo();
            }
            $scope.confGrpBtn('grpBtnActivo', false, false, false, true, false, false);
            $scope.slactivo = true;
            goTop();
        };

        //$scope.btnCP2 = function(){ if($scope.elProyecto.id > 0){ $scope.confGrpBtn('grpBtnProyecto', false, false, true, true, true, false); $scope.slproyecto = true; } };
        /*
        $scope.btnCA2 = function(){
            if(+$scope.elActivo.id > 0){
                $scope.confGrpBtn('grpBtnActivo', false, false, true, true, true, false);
                $scope.slactivo = true;
                console.log($scope.slactivo);
            }
        };
        */

        $scope.btnEA = function(){
            $scope.slactivo = false;
            $scope.confGrpBtn('grpBtnActivo', false, true, true, false, false, true);
            goTop();
        };

        $scope.addActivo = function(obj){
            obj.idempresa = $scope.elActivo.objEmpresa.id;
            obj.tipo_activo = $scope.elActivo.objTipoActivo.id;
            obj.departamento = $scope.elActivo.objDepartamento.id;
            obj.usuario = $scope.usrdata.usuario;
            obj.nomclienteajeno = obj.nomclienteajeno !== null && obj.nomclienteajeno !== undefined ? obj.nomclienteajeno : '';
            obj.multilotes = obj.multilotes != null && obj.multilotes !== undefined ? obj.multilotes : 0;
            obj.direcciondos = obj.direcciondos != null && obj.direcciondos !== undefined ? obj.direcciondos : '';
            obj.fechacomprastr = obj.fechacompra != null && obj.fechacompra !== undefined ? moment(obj.fechacompra).format('YYYY-MM-DD') : '';
            obj.debaja = obj.debaja != null && obj.debaja !== undefined ? obj.debaja : 0;
            obj.fechabajastr = obj.fechabaja != null && obj.fechabaja !== undefined ? moment(obj.fechabaja).format('YYYY-MM-DD') : '';
            activoSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstActivos();
                $scope.resetElActivo();
                $scope.getActivo(parseInt(d.lastid));
            });
        };

        $scope.updActivo = function(data, id){
            data.id = id;
            data.idempresa = $scope.elActivo.objEmpresa.id;
            data.tipo_activo = $scope.elActivo.objTipoActivo.id;
            data.departamento = $scope.elActivo.objDepartamento.id;
            data.usuario = $scope.usrdata.usuario;
            data.multilotes = data.multilotes != null && data.multilotes !== undefined ? data.multilotes : 0;
            data.direcciondos = data.direcciondos != null && data.direcciondos !== undefined ? data.direcciondos : '';
            data.fechacomprastr = data.fechacompra != null && data.fechacompra !== undefined ? moment(data.fechacompra).format('YYYY-MM-DD') : '';
            data.debaja = data.debaja != null && data.debaja !== undefined ? data.debaja : 0;
            data.fechabajastr = data.fechabaja != null && data.fechabaja !== undefined ? moment(data.fechabaja).format('YYYY-MM-DD') : '';
            //console.log(data); //return;
            activoSrvc.editRow(data, 'u').then(function(d){
                $scope.getLstActivos();
                $scope.getActivo(parseInt(d.lastid));
                //$scope.btnCA2();
            });
        };

        $scope.delActivo = function(id){
            activoSrvc.editRow({id:id}, 'd').then(function(){
                $scope.getLstActivos();
                $scope.resetElActivo();
                $scope.btnCA();
            });
        };

        $scope.addBitacora = function(obj){
            obj.idactivo = parseInt($scope.elActivo.id);
            obj.usuario = $scope.usrdata.usuario;
            activoSrvc.editRow(obj, 'cb').then(function(){
                activoSrvc.lstBitacora(parseInt($scope.elActivo.id)).then(function(d){
                    for(var i = 0; i < d.length; i++){ d[i].fhbitacora = moment(d[i].fhbitacora).toDate(); };
                    $scope.lasBitacoras = d;
                    $scope.laBitacora = {};
                });
            });
        };

        $scope.delBitacora = function(idbitacora){
            $confirm({text: '¿Seguro(a) de eliminar esta bitácora?', title: 'Eliminar bitácora', ok: 'Sí', cancel: 'No'}).then(function() {
                activoSrvc.editRow({id:idbitacora}, 'db').then(function(){
                    activoSrvc.lstBitacora(parseInt($scope.elActivo.id)).then(function(d){
                        for(var i = 0; i < d.length; i++){ d[i].fhbitacora = moment(d[i].fhbitacora).toDate(); };
                        $scope.lasBitacoras = d;
                    });
                });
            });
        };

        // upload later on form submit or something similar
        $scope.submit = function() {
            if ($scope.file) {
                $scope.upload($scope.file);
            }
        };

        // upload on file select or drop
        $scope.upload = function (file) {
            Upload.upload({
                url: 'php/upload.php',
                method: 'POST',
                file: file,
                sendFieldsAs: 'form',
                fields: {
                    directorio: '../activo_adjunto/',
                    prefijo: 'ACT_'+$scope.elActivo.id+'_'
                }
            }).then(function (resp) {
                //console.log(resp);
                //console.log('Success ' + resp.config.data.file.name + 'uploaded. Response: ' + resp.data);
                $scope.file = null;
                $scope.progressPercentage = 0;
            }, function (resp) {
                //console.log(resp);
                //console.log('Error status: ' + resp.status);
            }, function (evt) {
                //console.log(evt);
                $scope.progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
                //console.log('progress: ' + progressPercentage + '% ' + evt.config.data.file.name);
            });
        };

        $scope.addActivoAdjunto = function(obj){
            $scope.submit();
            obj.idactivo = $scope.elActivo.id;
            obj.tipo_adjunto = $scope.elActivoAdjunto.objTipoAdjunto.id;
            obj.ubicacion = "activo_adjunto/"+'ACT_'+$scope.elActivo.id+'_'+ $filter('textCleaner')($scope.file.name);
            activoAdjuntoSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstActivosAdjuntos();
                $scope.elActivoAdjunto = {};
            });

        };
        $scope.updActivoAdjunto = function(data, id){
            data.id = id;
            activoAdjuntoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstActivosAdjuntos();
            });
        };

        $scope.delActivoAdjunto = function(id){
            $confirm({text: '¿Seguro(a) de eliminar este adjunto? (Esto también eliminará físicamente el documento)',
                title: 'Eliminar adjunto de activo', ok: 'Sí', cancel: 'No'}).then(function() {
                activoAdjuntoSrvc.editRow({id:id}, 'd').then(function(){ $scope.getLstActivosAdjuntos(); });
            });
        };
        $scope.getScope = function () {
            console.log($scope);
        };

        $scope.printVersion = function(){
            var nomact = $scope.elActivo.finca + '-' + $scope.elActivo.folio + '-' + $scope.elActivo.libro;
            PrintElem('#divFrmActivos', 'Ficha del activo ' + nomact);
        };

        $scope.getLstActivos();
        $scope.getlstEmpresas();
        $scope.getlstTipoActivo();
        $scope.getlstTipoAdjunto();
        $scope.getlstMunicipios();

        $scope.loadDataActivo = function(idactivo){
            localStorageSrvc.set('idactivo', idactivo);
            $location.path('mntactivo');
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Catálogo de activos');
        };

    }]);
}());
