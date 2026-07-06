
google.charts.load('current', { 'packages': ['corechart'] });
google.charts.setOnLoadCallback(inicializarGraficas);

function inicializarGraficas() {
    renderizarGraficaPie('pais_div', 'usuarios_por_pais_data', 'pais', '');
    renderizarGraficaPie('edad_div', 'usuarios_por_edad_data', 'rango_edad', '');
    renderizarGraficaPie('genero_div', 'usuarios_por_sexo_data', 'sexo', '');
    renderizarGraficaEscalera('usuarios_tiempo_div', 'usuarios_tiempo_data', 'periodo', '');
    renderizarGraficaEscalera('preguntas_tiempo_div', 'preguntas_tiempo_data', 'periodo', 'Preguntas Creadas');
    renderizarGraficaEscalera('partidas_tiempo_div', 'partidas_tiempo_data', 'periodo', 'Partidas Jugadas');
    renderizarGraficaDona('acierto_dona_div', 'acierto_dona_data', 'tipo', 'porcentaje');
    configurarPreparacionPDF();
}

function renderizarGraficaPie(elementId, datasetId, columnaTexto, chartTitle) {
    var contenedorData = document.getElementById(datasetId);
    var chartDiv = document.getElementById(elementId);

    if (!contenedorData || !chartDiv) return;

    //convierte el json en un array
    var rawData = JSON.parse(contenedorData.getAttribute('data-json'));

    // Si JSON viene vacio, mostramos un mensaje en el div
    if (rawData.length === 0) {
        chartDiv.innerHTML = "<p class='text-muted pt-5'>No hay datos suficientes para graficar</p>";
        chartDiv.setAttribute('data-base64', 'vacio');
        window.dispatchEvent(new Event('graficaLista'));
        return;
    }

    var arrayChart = [['Concepto', 'Cantidad']];

    rawData.forEach(function(item) {
        // Accedemos directamente a las propiedades del objeto de la base de datos
        var etiqueta = item[columnaTexto] || 'No especificado';
        var cantidad = parseInt(item.cantidad) || 0;
        arrayChart.push([etiqueta, cantidad]);
    });

    //transforma la matriz arrayChart a DataTable para que lo pueda graficar
    var dataTable = google.visualization.arrayToDataTable(arrayChart);

    var options = {
        title: chartTitle,
        is3D: true,
        chartArea: { width: '100%', height: '80%' },
        legend: { position: 'bottom' },
        // Asegura que no rompa la estética de Bootstrap
        backgroundColor: 'transparent',
        forceIFrame: true,


    };

    var chart = new google.visualization.PieChart(chartDiv);

    google.visualization.events.addListener(chart, 'ready', function () {
        //aca se codifica el grafico en base64 para que pueda ser escrito cuando se descargue en pdf
        chartDiv.setAttribute('data-base64', chart.getImageURI());
        window.dispatchEvent(new Event('graficaLista'));
    });

    chart.draw(dataTable, options);
}

function renderizarGraficaEscalera(elementId, datasetId, columnaTexto, chartTitle) {
    var contenedorData = document.getElementById(datasetId);
    var chartDiv = document.getElementById(elementId);

    if (!contenedorData || !chartDiv) return;

    var rawData = JSON.parse(contenedorData.getAttribute('data-json'));

    if (rawData.length === 0) {
        chartDiv.innerHTML = "<p class='text-muted pt-5'>No hay datos suficientes para graficar</p>";
        chartDiv.setAttribute('data-base64', 'vacio');
        window.dispatchEvent(new Event('graficaLista'));
        return;
    }

    var arrayChart = [['Periodo', 'Usuarios']];
    rawData.forEach(function(item) {
        var etiqueta = item[columnaTexto] || 'No especificado';
        var cantidad = parseInt(item.cantidad) || 0;
        arrayChart.push([etiqueta, cantidad]);
    });

    var dataTable = google.visualization.arrayToDataTable(arrayChart);

    var options = {
        title: chartTitle,
        chartArea: { width: '85%', height: '70%' },
        legend: { position: 'none' }, // Quitamos la leyenda para mejorar espacio
        backgroundColor: 'transparent',
        forceIFrame: true,
        colors: ['#0d6efd'], // Azul primario de Bootstrap para las barras
        vAxis: { minValue: 0, ticks: { stepSize: 1 } },
        hAxis: { textStyle: { fontSize: 10 } }
    };

    // Usamos ColumnChart en lugar de PieChart para lograr el efecto visual de escalera
    var chart = new google.visualization.ColumnChart(chartDiv);

    google.visualization.events.addListener(chart, 'ready', function () {
        chartDiv.setAttribute('data-base64', chart.getImageURI());
        window.dispatchEvent(new Event('graficaLista')); // Avisa que esta gráfica también terminó
    });

    chart.draw(dataTable, options);
}

function renderizarGraficaDona(elementId, datasetId, columnaTexto, columnaNum) {
    var contenedorData = document.getElementById(datasetId);
    var chartDiv = document.getElementById(elementId);

    if (!contenedorData || !chartDiv) return;

    var rawData = JSON.parse(contenedorData.getAttribute('data-json'));

    if (rawData.length === 0) {
        chartDiv.innerHTML = "<p class='text-muted pt-5'>No hay datos suficientes para graficar</p>";
        chartDiv.setAttribute('data-base64', 'vacio');
        window.dispatchEvent(new Event('graficaLista'));
        return;
    }

    var arrayChart = [['Concepto', 'Porcentaje']];
    rawData.forEach(function(item) {
        arrayChart.push([item[columnaTexto], parseInt(item[columnaNum])]);
    });

    var dataTable = google.visualization.arrayToDataTable(arrayChart);

    var options = {
        pieHole: 0.3, // Esto transforma el PieChart en un donut
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
    window.addEventListener('graficaLista', function() {
        var paisImg = document.getElementById('pais_div').getAttribute('data-base64');
        var edadImg = document.getElementById('edad_div').getAttribute('data-base64');
        var generoImg = document.getElementById('genero_div').getAttribute('data-base64');
        var tiempoImg = document.getElementById('usuarios_tiempo_div').getAttribute('data-base64');
        var preguntasImg = document.getElementById('preguntas_tiempo_div').getAttribute('data-base64');
        var partidasImg  = document.getElementById('partidas_tiempo_div').getAttribute('data-base64');
        var aciertosImg  = document.getElementById('acierto_dona_div').getAttribute('data-base64');

        if (paisImg && edadImg && generoImg && tiempoImg) {
            document.getElementById('input_img_pais').value = paisImg;
            document.getElementById('input_img_edad').value = edadImg;
            document.getElementById('input_img_genero').value = generoImg;
            document.getElementById('input_img_usuarios_tiempo').value = tiempoImg;
            document.getElementById('input_img_preguntas_tiempo').value = preguntasImg;
            document.getElementById('input_img_partidas_tiempo').value = partidasImg;
            document.getElementById('input_img_acierto_dona').value = aciertosImg;

            var btn = document.getElementById('btn-exportar-pdf');
            if(btn) {
                btn.removeAttribute('disabled');
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-danger');
            }
        }
    });
}