(function () {

    var rptlibdiactrl = angular.module('cpm.rptlibdiactrl', []);

    rptlibdiactrl.controller('rptLibroDiarioCtrl', ['$scope', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', '$sce', function ($scope, empresaSrvc, authSrvc, jsReportSrvc, $sce) {

        $scope.params = { del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0, vercierre: 1 };
        $scope.empresa = {};
        $scope.content = `${window.location.origin}/blank.html`;
        $scope.cargando = false;

        authSrvc.getSession().then(function (usrLogged) {
            if (parseInt(usrLogged.workingon) > 0) {
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function (d) { $scope.empresa = d[0]; });
            }
        });

        var test = false;
        $scope.getLibroDiario = function () {
            $scope.cargando = true;
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? '' : 'ByXFp8o-Z', $scope.params).then(function (pdf) { $scope.content = pdf; $scope.cargando = false; });
        };


        $scope.getLibroDiarioXLSX = function () {
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            jsReportSrvc.getReport(test ? 'B1Sn40tFf' : 'rJENFRKYG', $scope.params).then(function (result) {
                //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                var file = new Blob([result.data], { type: 'application/vnd.ms-excel' });
                var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.del).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY');
                saveAs(file, 'LD_' + nombre + '.xlsx');
            });
        };

        $scope.getLibroDiarioXLSX2 = () => {
            $scope.cargando = true;
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');

            var url = '/php/rptlibdia.php/librodiario'

            try {
            $.post(url, $scope.params, function (data) {
                var tab_text = '<table>'
                tab_text = tab_text + "<tr><th colspan='5'> " + data.empresa.nomempresa + " </th></tr>";
                tab_text = tab_text + "<tr><th colspan='5'>Libro Diario</th></tr>";
                tab_text = tab_text + "<tr><th colspan='5'>Del " + data.empresa.del + " al " + data.empresa.al + "</th></tr>";
                tab_text = tab_text + "<tr><th>Fecha</th><th colspan='2'>Referencia</th><th colspan='2'>Concepto</th></tr>";
                tab_text = tab_text + "<tr><th></th><th>Código</th><th>Cuenta</th><th>Debe</th><th>Haber</th></tr>";

                data.ld.forEach(e => {
                    if (e.dld.length) {
                        tab_text = tab_text + "<tr><th>" + e.fechastr + "</th><th colspan='2'>" + e.referencia + "</th><th colspan='2'>" + e.concepto + "</th></tr>";

                        e.dld.forEach(function (d) {
                            tab_text = tab_text + "<tr><td></td><td>" + d.codigo + "</td><td>" + d.nombrecta + "</td><td>" + d.debestr + "</td><td>" + d.haberstr + "</td></tr>"
                        })

                        tab_text = tab_text + "<tr><td></td><td></td><th>Totales</th><th>" + data.empresa.debestr + "</th><th>" + data.empresa.haberstr + "</th></tr>"
                    }
                })

                tab_text = tab_text + '</table>'

                var a = document.createElement('a')
                document.body.appendChild(a)
                a.href = 'data:application/vnd.oasis.opendocument.spreadsheet,' + encodeURIComponent(tab_text)
                a.download = 'Libro_diario_' + moment($scope.params.del).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY') + '.xls'
                a.click()
                $scope.cargando = false;
            })
            } catch (error) {
                console.error(error);
                $scope.cargando = false;
            }
        }

    }]);
}());
