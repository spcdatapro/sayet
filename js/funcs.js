//Funciones para todo el sitio...
function rowCallback(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
    if(aData[0] == 'Total de partida'){
        var debe = parseFloat(parseFloat(aData[1].replace(',', '')).toFixed(2));
        var haber = parseFloat(parseFloat(aData[2].replace(',', '')).toFixed(2));
        var qClase = debe === haber ? 'partida-cuadrada' : 'partida-descuadrada';
        $('td:eq(0)', nRow).addClass(qClase);
        $('td:eq(1)', nRow).addClass(qClase);
        $('td:eq(2)', nRow).addClass(qClase);
        $('td:eq(3)', nRow).addClass(qClase);
    }
}

function PrintElem(elem, tituloPagina){
    setTimeout(function(){ Popup($(elem).html(), tituloPagina); }, 500);
}

function Popup(data, tituloPagina){
    var mywindow = window.open('', 'PrintVer', 'height=400,width=600');
    mywindow.document.write('<html><head><title>' + tituloPagina + '</title>');
    mywindow.document.write('<link rel="stylesheet" href="libs/bootstrap/css/bootstrap.min.css">');
    mywindow.document.write('<link rel="stylesheet" href="libs/bootstrap/css/bootstrap-theme.min.css" >');
    mywindow.document.write('<link rel="stylesheet" href="libs/node_modules/angularjs-toaster/toaster.min.css">');
    mywindow.document.write('<link rel="stylesheet" href="libs/angularxeditable/css/xeditable.css">');
    mywindow.document.write('<link rel="stylesheet" href="libs/datatables/css/jquery.dataTables.min.css">');
    mywindow.document.write('<link rel="stylesheet" href="libs/datatables/css/datatables.bootstrap.min.css">');
    mywindow.document.write('<link rel="stylesheet" href="css/styles.css">');
    mywindow.document.write('<style>div, table, tr, td, th, h1, h2, h3, h4, input, select, textarea { font-size:1em; } body{ text-align: inherit; } button, a { visibility: hidden; }</style>');
    mywindow.document.write('</head><body>');
    mywindow.document.write(data);
    mywindow.document.write('<script type="text/javascript" src="libs/jquery/jquery-1.11.3.min.js"></script>');
    mywindow.document.write('<script type="text/javascript" src="libs/angularjs/angular.min.js"></script>');
    mywindow.document.write('<script type="text/javascript" src="libs/bootstrap/js/bootstrap.min.js"></script>');
    mywindow.document.write('<script type="text/javascript" src="libs/uibootstrap/ui-bootstrap-tpls-1.1.0.min.js"></script>');
    mywindow.document.write('<script type="text/javascript" src="js/funcs.js"></script>');
    mywindow.document.write('<script type="text/javascript">hideTableInPrintIdEndsWith("_1");</script>');
    mywindow.document.write('</body></html>');

    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10

    //mywindow.print();
    setTimeout(function(){mywindow.print();}, 500);
    setTimeout(function(){mywindow.close();}, 1000);
    //mywindow.close();

    return true;
}

function hideTableInPrintIdEndsWith(val){ $("table[id$='" + val + "']").hide(); }

function goTop(){ $('html, body').animate({ scrollTop: 0 }, 'fast'); }

function pad(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}

function objectPropsToList(lstObj, nameProp, separator){
    var lista = '';
    try{
        for(var i = 0; i < lstObj.length; i++){
            if(lista != ''){ lista += separator}
            lista += eval('lstObj[i].' + nameProp);
        }
    }catch(err){
        lista = '';
    }
    return lista;
}

function moveToTab(fromTab, toTab){
    $('#' + fromTab).removeClass('active');
    $('#' + toTab).addClass('active');
    $('.nav-tabs a[href="#'+ toTab +'"]').tab('show');
}

function moveToTabById(idNavTab, fromTab, toTab){
    $('#' + fromTab).removeClass('active');
    $('#' + toTab).addClass('active');
    $('#' + idNavTab + ' a[href="#'+ toTab +'"]').tab('show');
}

function  enablePopOvers(){ $('[data-toggle="popover"]').popover(); }

function getIniciales(nombre){
    var iniciales = '';
    nombre.split(' ').forEach(function(item){
        iniciales += item.substring(0, 1);
    });
    return iniciales;
}
