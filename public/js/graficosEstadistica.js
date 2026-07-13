
google.charts.load('current', { 'packages': ['corechart'] });
google.charts.setOnLoadCallback(inicializarGraficas);

function inicializarGraficas() {
    renderizarGraficaPie('pais_div', 'usuarios_por_pais_data', 'pais', '');
    renderizarGraficaPie('edad_div', 'usuarios_por_edad_data', 'rango_edad', '');
    renderizarGraficaPie('genero_div', 'usuarios_por_sexo_data', 'sexo', '');

    renderizarGraficaEscalera('usuarios_tiempo_div', 'usuarios_tiempo_data', 'periodo', ' ');
    renderizarGraficaEscalera('preguntas_tiempo_div', 'preguntas_tiempo_data', 'periodo', ' ');
    renderizarGraficaEscalera('partidas_tiempo_div', 'partidas_tiempo_data', 'periodo', ' ');
    renderizarGraficaEscalera('preguntas_usuarios_tiempo_div', 'preguntas_usuarios_tiempo_data', 'periodo', ' ');

    renderizarGraficaDona('acierto_dona_div', 'acierto_dona_data', 'tipo', 'porcentaje');

    configurarPreparacionPDF();
}

function renderizarGraficaPie(elementId, datasetId, columnaTexto, chartTitle) {
    var resultado = obtenerYValidarDatos(datasetId, elementId);
    if (!resultado) return;

    var rawData = resultado.data;
    var chartDiv = resultado.div;

    // el objeto DataTable necesita encabezados
    var arrayChart = [['Concepto', 'Cantidad']];

    // se extrae el filtro(dia,mes,semana) y el int cantidad
    rawData.forEach(function(item) {
        var etiqueta = item[columnaTexto] || 'No especificado';
        var cantidad = parseInt(item.cantidad) || 0;
        arrayChart.push([etiqueta, cantidad]);
    });

    //convierte el array en un DataTable para que googleCharts lo pueda usar
    var dataTable = google.visualization.arrayToDataTable(arrayChart);

    var options = {
        title: chartTitle,
        is3D: true,
        chartArea: { width: '100%', height: '80%' },
        legend: { position: 'bottom' },
        backgroundColor: 'transparent',
        forceIFrame: true
    };

    // se crea una instancia dentro del div en donde va a estar el grafico
    var chart = new google.visualization.PieChart(chartDiv);

    //el listener es para activar el boton imprimir cuando se cargue completamente y..
    google.visualization.events.addListener(chart, 'ready', function () {
        // aca el grafico se convierte en una imagen estatica codificada en base64 para ser imprimible
        chartDiv.setAttribute('data-base64', chart.getImageURI());
        window.dispatchEvent(new Event('graficaLista'));
    });
    // se hace hace el dibujo
    chart.draw(dataTable, options);
}

function renderizarGraficaEscalera(elementId, datasetId, columnaTexto, chartTitle) {
    var resultado = obtenerYValidarDatos(datasetId, elementId);
    if (!resultado) return;

    var rawData = resultado.data;
    var chartDiv = resultado.div;

    var columnaMetrica = chartTitle || 'Cantidad';
    var arrayChart = [['Periodo', columnaMetrica]];

    rawData.forEach(function(item) {
        var etiqueta = item[columnaTexto] || 'No especificado';
        var cantidad = parseInt(item.cantidad) || 0;
        arrayChart.push([etiqueta, cantidad]);
    });

    var dataTable = google.visualization.arrayToDataTable(arrayChart);

    var options = {
        title: chartTitle,
        chartArea: { width: '85%', height: '70%' },
        legend: { position: 'none' },
        backgroundColor: 'transparent',
        forceIFrame: true,
        colors: ['#0d6efd'],
        vAxis: { minValue: 0, ticks: { stepSize: 1 } },
        hAxis: { textStyle: { fontSize: 10 } }
    };

    var chart = new google.visualization.ColumnChart(chartDiv);

    google.visualization.events.addListener(chart, 'ready', function () {
        chartDiv.setAttribute('data-base64', chart.getImageURI());
        window.dispatchEvent(new Event('graficaLista'));
    });

    chart.draw(dataTable, options);
}

function renderizarGraficaDona(elementId, datasetId, columnaTexto, columnaNum) {
    var resultado = obtenerYValidarDatos(datasetId, elementId);
    if (!resultado) return;

    var rawData = resultado.data;
    var chartDiv = resultado.div;

    var arrayChart = [['Concepto', 'Porcentaje']];
    rawData.forEach(function(item) {
        arrayChart.push([item[columnaTexto], parseInt(item[columnaNum])]);
    });

    var dataTable = google.visualization.arrayToDataTable(arrayChart);

    var options = {
        pieHole: 0.3,
        chartArea: { width: '100%', height: '75%' },
        legend: { position: 'bottom' },
        backgroundColor: 'transparent',
        forceIFrame: true,
        colors: ['#198754', '#da0202'],
        pieSliceText: 'percentage',
        pieSliceTextStyle: { color: '#ffffff', fontSize: 12 }
    };

    var chart = new google.visualization.PieChart(chartDiv);

    google.visualization.events.addListener(chart, 'ready', function () {
        chartDiv.setAttribute('data-base64', chart.getImageURI());
        window.dispatchEvent(new Event('graficaLista'));
    });

    chart.draw(dataTable, options);
}

function configurarPreparacionPDF() {
    // cada vez que una grafica carga completamente, se llama esta funcion
    window.addEventListener('graficaLista', function() {
        //  Mapeo para el for
        var mapaGraficos = {
            'pais_div': 'input_img_pais',
            'edad_div': 'input_img_edad',
            'genero_div': 'input_img_genero',
            'usuarios_tiempo_div': 'input_img_usuarios_tiempo',
            'preguntas_tiempo_div': 'input_img_preguntas_tiempo',
            'partidas_tiempo_div': 'input_img_partidas_tiempo',
            'acierto_dona_div': 'input_img_acierto_dona',
            'preguntas_usuarios_tiempo_div': 'input_img_preguntas_usuarios_tiempo'
        };

        var todasListas = true;

        // se verifica si las graficas estan listas
        for (var divId in mapaGraficos) {
            var imgData = document.getElementById(divId).getAttribute('data-base64') || '';
            if (!imgData) {
                todasListas = false;
                break;
            }
        }

        // inyectamos los valores en los inputs y activamos el botón
        if (todasListas) {
            for (var divId in mapaGraficos) {
                var inputId = mapaGraficos[divId];
                document.getElementById(inputId).value = document.getElementById(divId).getAttribute('data-base64');
            }

            var btn = document.getElementById('btn-exportar-pdf');
            if (btn) {
                btn.removeAttribute('disabled');
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-danger');
            }
        }
    });
}


//dataset es el json y el elementId es el ID en donde va a estar la grafica
function obtenerYValidarDatos(datasetId, elementId) {
    var contenedorData = document.getElementById(datasetId);
    var chartDiv = document.getElementById(elementId);

    if (!contenedorData || !chartDiv) return null;

    // transforma el json en un objeto
    var rawData = JSON.parse(contenedorData.getAttribute('data-json'));

    // si no llego nada se prepara para dar el aviso
    if (!rawData || rawData.length === 0) {
        chartDiv.innerHTML = "<p class='text-muted pt-5'>No hay datos suficientes para graficar</p>";
        chartDiv.setAttribute('data-base64', 'vacio');
        // el evento es para desbloquear el boton de imprimir
        window.dispatchEvent(new Event('graficaLista'));
        return null;
    }


    return { data: rawData, div: chartDiv };
}