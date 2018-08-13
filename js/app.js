(function(){

    var app = angular.module('cpm', [
        'ngSanitize', 'ngAnimate', 'cpm.frmbtns', 'cpm.shortenstrfltr', 'ui.select',
        'ngRoute', 'cpm.pagesctrl', 'cpm.indexctrl', 'cpm.cpmidxctrl', 'cpm.menucpm',
        'cpm.usrctrl', 'cpm.showtab', 'cpm.menusrvc', 'cpm.menuctrl', 'cpm.empresasrvc',
        'cpm.empresactrl', 'cpm.monedactrl', 'cpm.monedasrvc', 'xeditable', 'cpm.editrowform',
        'cpm.cuentacctrl', 'cpm.cuentacsrvc', 'cpm.bancoctrl', 'cpm.bancosrvc', 'cpm.proveedorctrl',
        'cpm.proveedorsrvc', 'cpm.setempre', 'ui.bootstrap', 'cpm.tranbancctrl', 'cpm.tranbacsrvc',
        'datatables', 'datatables.bootstrap', 'cpm.datatablewrapper', 'cpm.activoctrl', 'cpm.activosrvc',
        'cpm.tipoactivosrvc', 'cpm.activoadjuntosrvc', 'cpm.tipoadjuntosrvc', 'ngFileUpload','cpm.proyectoctrl',
        'cpm.proyectosrvc','cpm.tipoproyectosrvc','cpm.proyectoadjuntosrvc','cpm.municipiosrvc','fcsa-number', 'angular-confirm',
        'cpm.conciliactrl', 'cpm.tipodocsoptbsrvc', 'cpm.tipomovtranbansrvc', 'cpm.pcontsrvc', 'cpm.detcontsrvc',
        'cpm.compractrl', 'cpm.comprasrvc', 'cpm.tipocomprasrvc', 'cpm.periodocontctrl','cpm.proyectoactivosrvc',
        'cpm.clientesrvc','cpm.clientectrl','cpm.getbyidfltr','cpm.tipoconfigcontasrvc', 'cpm.tranpagosctrl', 
        'cpm.tranpagossrvc', 'cpm.rptactivosctrl', 'cpm.tipoactivosrvc', 'cpm.tipoactivoctrl', 'cpm.tipoadjuntosrvc', 'cpm.tipoadjuntoctrl',
        'cpm.tipocompractrl', 'cpm.tipogastocontablesrvc', 'cpm.tipogastocontablectrl', 'cpm.tipogastosrvc',
        'cpm.tipogastoctrl', 'cpm.tipomovtranbanctrl', 'cpm.tipoproyectosrvc', 'cpm.tipoproyectoctrl',
        'cpm.rptcatbcoctrl', 'cpm.rptcatprovctrl', 'cpm.rptcorrchctrl', 'cpm.rptdetcontdocsctrl', 'cpm.rptdetcontfactctrl',
        'cpm.rptdocscirculactrl', 'cpm.rptestadoctactrl','cpm.rptfactprovctrl', 'cpm.rpthistpagosctrl', 'cpm.rptpagoiusictrl',
        'cpm.municipioctrl', 'angular-svg-round-progressbar', 'cpm.tipodocproysrvc', 'cpm.tipodocproyctrl', 'cpm.rptlstproyctrl',
        'cpm.rptdocsvencectrl', 'cpm.unidadctrl', 'cpm.unidadsrvc', 'cpm.tipolocalsrvc', 'cpm.tiposerviciosrvc',
        'cpm.dashboardctrl', 'cpm.dashboardsrvc', 'cpm.padfltr', 'cpm.getbycodctafltr', 'cpm.localstoragesrvc',
        'cpm.tiposervicioctrl', 'cpm.serviciobasicosrvc', 'cpm.serviciobasicoctrl', 'cpm.noordensrvc', 'cpm.tipolocalctrl',
        'cpm.tipoclientesrvc', 'cpm.tiposervicioventasrvc', 'cpm.periodicidadsrvc', 'cpm.trandirectactrl', 'cpm.directasrvc',
        'cpm.tipocambiosrvc', 'cpm.reembolsoctrl', 'cpm.reembolsosrvc', 'adaptv.adaptStrap', 'cpm.tiporeembolsosrvc',
        'cpm.tipofacturasrvc', 'cpm.beneficiarioctrl', 'cpm.beneficiariosrvc', 'cpm.razonanulacionsrvc', 'cpm.razonanulacionctrl',
        'cpm.rptbalsalsrvc', 'cpm.rptbalsalctrl', 'cpm.rptlibcompsrvc', 'cpm.rptlibcompctrl', 'cpm.tipocombsrvc',
        'cpm.rptlibmaysrvc', 'cpm.rptlibmayctrl', 'cpm.rptestressrvc', 'cpm.rptestresctrl', 'cpm.rptbalgensrvc',
        'cpm.rptbalgenctrl', 'cpm.tiposervicioventactrl', 'cpm.jsreportsrvc', 'cpm.facturacionsrvc', 'cpm.facturacionctrl',
        'cpm.serviciopropiosrvc', 'cpm.serviciopropioctrl', 'cpm.rptauditoriactrl', 'cpm.tipoipcsrvc', 'cpm.tipoipcctrl',
        'cpm.presupuestosrvc', 'cpm.presupuestoctrl', 'cpm.tranaprobpresupctrl', 'cpm.transegpresupctrl', 'angular.chosen',
        'cpm.rptincdecctrl', 'cpm.rptvencimientosctrl', 'cpm.rptlibventasrvc', 'cpm.rptlibventactrl', 'cpm.isrctrl',
        'cpm.rptlibdiactrl', 'cpm.reciboprovsrvc', 'cpm.reciboprovctrl', 'cpm.reciboclisrvc', 'cpm.reciboclictrl',
        'cpm.rptconciliabcosrvc', 'cpm.rptconciliabcoctrl', 'cpm.rptcatactctrl', 'cpm.rptfichaproyctrl', 'cpm.rptdetcontratoctrl',
        'cpm.rptfichaclientectrl', 'cpm.rptalquileresctrl', 'cpm.onfinishrender', 'cpm.lecturaservicioctrl', 'cpm.facturacionaguasrvc',
        'angucomplete-alt', 'cpm.facturaotrossrvc', 'cpm.gfacectrl', 'cpm.tipoimpchequesrvc', 'cpm.asignactasubtipogastoctrl',
        'ngDesktopNotification', 'cpm.onreadfile', 'treeGrid', 'cpm.setfocusoncontrol', 'cpm.rptsumarioctrl',
        'cpm.rptaguactrl', 'infinite-scroll', 'cpm.ventasrvc', 'cpm.ventactrl', 'cpm.rptserviciosctrl',
        'cpm.rptrescheqctrl', 'cpm.rptchqaprobctrl','cpm.rptsaldoclisrvc', 'cpm.rptsaldoclictrl', 'cpm.rptsaldoprovsrvc', 
        'cpm.rptsaldoprovctrl','cpm.rptanticlisrvc','cpm.rptanticlictrl','cpm.rptantiprovsrvc', 'cpm.rptantiprovctrl', 
        'cpm.rptecuentaprovsrvc', 'cpm.rptecuentaprovctrl','cpm.rptecuentaclisrvc', 'cpm.rptecuentaclictrl', 'cpm.rptctrlccctrl',
        'cpm.txtcleanerfltr', 'cpm.rptbuscafactcompctrl', 'cpm.rptingegrproysrvc', 'cpm.rptingegrproyctrl',
        'cpm.rptfactsemitidasctrl', 'cpm.rptarbolsrvcctrl', 'cpm.rptivaventactrl', 'cpm.dropdigitsfltr', 'cpm.rptrecclictrl',
        'cpm.formsretventactrl', 'cpm.rptretenedoresctrl', 'cpm.rptasistelibrosctrl', 'cpm.estatuspresupuestosrvc', 'cpm.rptdescuadresctrl',
        'cpm.rptdetcontventasctrl', 'cpm.rptintegractacontctrl', 'cpm.rptarchivobictrl', 'cpm.factsparqueosrvc', 'cpm.tpo',
        'cpm.planillasrvc', 'cpm.genpagosplnctrl', 'cpm.rptcompaguactrl', 'cpm.rptfactsparqueoctrl', 'cpm.cierreanualsrvc',
        'cpm.cierreanualctrl', 'cpm.rptplnpremiosctrl', 'cpm.empleadosrvc', 'cpm.rptplnhistosueldoctrl', 'cpm.plnpagoboletoornatosrvc',
        'cpm.plnpagoboletoornatoctrl'
    ]);

    app.config(['$routeProvider', 'desktopNotificationProvider', function ($routeProvider, desktopNotificationProvider) {
        $routeProvider.when('/', { templateUrl: 'pages/blank.html' });
        $routeProvider.when('/:name', { templateUrl: 'pages/blank.html', controller: 'PagesController' });
        
		// LM
        $routeProvider
        .when('/pln/:tipo/:pagina', { 
            templateUrl: 'pln/pages/base.html', 
            controller: 'plnRutasController' 
        });
		
		$routeProvider.otherwise({redirectTo: '/'});


        desktopNotificationProvider.config({
            autoClose:false,
            requireInteraction: true
        });

    }]);

    app.run(['$rootScope', '$window', 'authSrvc', 'localStorageSrvc', 'tipoCambioSrvc', 'desktopNotification', function($rootScope, $window, authSrvc, localStorageSrvc, tipoCambioSrvc, desktopNotification){
        localStorageSrvc.clearAll();
        $rootScope.workingon = 0;
        $rootScope.logged = false;
        desktopNotification.requestPermission().then(function(){}, function(){});
        $rootScope.$on("$routeChangeStart", function (event, next, current) {
            tipoCambioSrvc.getTC();
            authSrvc.getSession().then(function(res){
                if(parseInt(res.uid) > 0){
                    $rootScope.logged = true;
                    $rootScope.uid = parseInt(res.uid);
                    $rootScope.fullname = res.nombre;
                    $rootScope.usuario = res.usuario;
                    $rootScope.correoe = res.correoe;
                    $rootScope.workingon = parseInt(res.workingon);
                }else{
                    var nextUrl = next.$$route.originalPath;
                    if(nextUrl == "/"){
                        if(parseInt(res.uid) == 0){
                            $window.location.href = 'index.html';
                        }
                    }else{
                        $window.location.href = 'index.html';
                    }
                }
            });
        });

    }]);

    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });

}());