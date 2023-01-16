(function () {

    const gfacectrl = angular.module('cpm.gfacectrl', []);

    gfacectrl.controller('gfaceCtrl', ['$scope', 'empresaSrvc', 'jsReportSrvc', 'authSrvc', '$filter', '$confirm', 'facturacionSrvc', '$window', 'toaster', function ($scope, empresaSrvc, jsReportSrvc, authSrvc, $filter, $confirm, facturacionSrvc, $window, toaster) {

        $scope.params = { idempresa: undefined, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate() };
        $scope.empresas = [];
        $scope.pendientes = [];

        authSrvc.getSession().then(function (usrLogged) {
            empresaSrvc.lstEmpresas().then(function (d) {
                $scope.empresas = d;
                $scope.params.idempresa = usrLogged.workingon.toString();
            });
        });

        $scope.getPend = () => {
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            facturacionSrvc.factsPendFEL($scope.params).then((d) => {
                //console.log(d);
                d.map((i) => i.descargar = +i.descargar);
                $scope.pendientes = d;
            });
        };

        let checkListado = () => {
            let aDescargar = [];
            $scope.pendientes.forEach((i) => {
                if (+i.descargar === 1) {
                    aDescargar.push(i.idfactura);
                }
            });
            return aDescargar.join(',');
        };

        const test = false;
        $scope.getGFACE = function () {
            $scope.params.fdelstr = moment($scope.params.fdel).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fal).format('YYYY-MM-DD');
            $scope.params.listafact = checkListado();
            let abreviatura = $filter('getById')($scope.empresas, $scope.params.idempresa).abreviatura, nombre = '';
            abreviatura = abreviatura != null && abreviatura != undefined ? abreviatura : '';
            nombre = abreviatura + '-GFACE' + moment().format('DDMMYYYYhhmmss');
            const qstr = $scope.params.idempresa + '/' + $scope.params.fdelstr + '/' + $scope.params.falstr + '/' + nombre + '/' + $scope.params.listafact;
            $window.open('php/facturacion.php/gettxt/' + qstr);
        };

        openWindowWithPostRequest = (url, params) => {
            var winName = 'MyWindow';
            var winURL = url;
            var windowoption = 'resizable=yes,height=600,width=800,location=0,menubar=0,scrollbars=1';
            var form = document.createElement("form");
            form.setAttribute("method", "post");
            form.setAttribute("action", winURL);
            form.setAttribute("target", winName);
            for (var i in params) {
                if (params.hasOwnProperty(i)) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = i;
                    input.value = params[i];
                    form.appendChild(input);
                }
            }
            document.body.appendChild(form);
            const win = window.open('', winName, windowoption);
            form.target = winName;
            form.submit();
            document.body.removeChild(form);
            setTimeout(() => win.close(), 3000);
        }

        $scope.getArchivoFEL = () => {
            let seleccionadas = '';
            $scope.pendientes.forEach(f => {
                if (+f.descargar === 1) {
                    if (seleccionadas !== '') {
                        seleccionadas += ', ';
                    }
                    seleccionadas += f.id;
                }
            });
            if (seleccionadas !== '') {
                $scope.params.listafact = seleccionadas;
                $scope.params.regenerar = 1;
                facturacionSrvc.factsPendFEL($scope.params).then(d => {
                    let facturas = '';
                    let abreviatura = $filter('getById')($scope.empresas, $scope.params.idempresa).abreviatura;
                    abreviatura = !!abreviatura ? abreviatura : '';
                    d.forEach(f => {
                        facturas += `${f.tiporegistro}|${f.fechadocumento}|${f.tipodocumento}|${f.nitcomprador}|${f.codigomoneda}|${f.tasacambio}|${f.ordenexterno}|${f.tipoventa}|${f.destinoventa}|${f.enviarcorreo}|${f.nombrecomprador}|${f.direccion}|${f.nombrecorto}|$ ${f.montodol}|${f.tipocambio}|$ ${f.pagonetodol}|${f.monedafact} ${f.pagoneto}|${f.monedafact} ${f.retiva}|${f.monedafact} ${f.retisr}|${f.monedafact} ${f.monto}|${f.numeroacceso}|${f.serieadmin}|${f.numeroadmin}|${f.tipoidreceptor}\n`;
                        f.detalle.forEach(d => {
                            facturas += `${d.tiporegistro}|${d.cantidad}|${d.unidadmedida}|${d.precio}|${d.porcentajedescuento}|${d.importedescuento}|${d.importebruto}|${d.importeexento}|${d.importeneto}|${d.importeiva}|${d.importeotros}|${d.importetotal}|${d.producto}|${d.descripcion}|${d.tipoventa}\n`;
                        });
                        f.docasoc.forEach(da => {
                            facturas += `${da.tiporegistro}|${da.tipodocumento}|${da.serie}|${da.numero}|${da.fechadocumento}\n`;
                        });
                        const t = f.totales;
                        facturas += `${t.tiporegistro}|${t.importebruto}|${t.importedescuento}|${t.importeexento}|${t.importeneto}|${t.importeiva}|${t.importeotros}|${t.importetotal}|${t.porcentajeisr}|${t.importeisr}|${f.detalle.length}|${t.documentosasociados}\n`;
                    });
                    //console.log(facturas);
                    const nombre = abreviatura + '-FEL-' + moment().format('YYYYMMDDhhmmss');
                    openWindowWithPostRequest('php/facturacion.php/convencod', { de: 'UTF-8', a: 'Windows-1252', texto: facturas, nombre: nombre });
                    $scope.pendientes = [];
                    $scope.params.listafact = '';
                    $scope.params.regenerar = 0;
                })
            }
        };

        $scope.resetParams = function () { $scope.params = { idempresa: $scope.params.idempresa, fdel: moment().startOf('month').toDate(), fal: moment().endOf('month').toDate() }; };

        $scope.showContent = function ($fileContent) {
            $scope.content = $fileContent;
            $confirm({ text: '¿Desea actualizar las facturas con estos datos?', title: 'Archivo de respuesta', ok: 'Sí', cancel: 'No' }).then(function () {
                $scope.procesaArchivoFEL($scope.content);
            });
        };

        $scope.procesaArchivoFEL = (archivo) => {
            var cadena = archivo.split('\n');
            // console.log(cadena); return;
            var linea, facturas = [];
            cadena.forEach(function (cad) {
                linea = cad.replace('\r', '').replace('\n', '').split('|');
                // console.log(linea);
                const qTipo = linea[0] || '';
                if ((qTipo.trim().toUpperCase().indexOf('FACT') > -1) || (qTipo.trim().toUpperCase().indexOf('NCRE') > -1)) {
                    facturas.push({ id: linea[8], firma: linea[7], serie: linea[2], numero: linea[3], nit: linea[4], nombre: linea[9], respuesta: cad, tipo: linea[0] });
                }
            });            
            // console.log(facturas); //return;
            if (facturas.length > 0) {
                facturacionSrvc.respuestaGFACE(facturas).then(function (d) {
                    //console.log(d.estatus);
                    toaster.pop({ type: 'success', title: 'Proceso terminado', body: 'Las facturas fueron actualizadas con su firma.', timeout: 9000 });
                    $scope.content = '';
                    $('#txtFile').val(undefined);
                })
            } else {
                toaster.pop({ type: 'error', title: 'Archivo de respuesta', body: 'Hubo un error al cargar el archivo, por favor revise que la información es correcta.', timeout: 9000 });
            }
        };

        $scope.procesaArchivo = function (archivo) {
            //var cadena = archivo.split('\r\n');
            var cadena = archivo.split('\n');
            //console.log(cadena); return;
            var linea, facturas = [];
            cadena.forEach(function (cad) {
                linea = cad.replace('\r', '').replace('\n', '').split('|');
                //console.log(linea);
                //facturas.push({ id: +linea[9], firma: linea[8], serie: linea[1], numero: linea[2], nit: linea[3], nombre: linea[4], respuesta: cad });
                facturas.push({ id: +linea[8], firma: linea[7], serie: linea[2], numero: linea[3], nit: linea[4], nombre: linea[9], respuesta: cad });
            });
            //console.log(facturas); return;
            if (facturas.length > 0) {
                facturacionSrvc.respuestaGFACE(facturas).then(function (d) {
                    //console.log(d.estatus);
                    toaster.pop({ type: 'success', title: 'Proceso terminado', body: 'Las facturas fueron actualizadas con su firma.', timeout: 9000 });
                    $scope.content = '';
                    $('#txtFile').val(undefined);
                })
            }
        };

        $scope.getRptPendientes = function () {
            jsReportSrvc.getPDFReport(test ? 'S1wR9_Mif' : 'HyJCJizjf', $scope.params).then(function (pdf) { $scope.content = pdf; });
        }

    }]);
}());

