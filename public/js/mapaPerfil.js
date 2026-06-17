document.addEventListener("DOMContentLoaded", () => {

    const mapaDiv = document.getElementById("mapa");

    if (!mapaDiv) return;

    const ciudad = mapaDiv.dataset.ciudad;
    const pais = mapaDiv.dataset.pais;

    if (!ciudad || !pais) return;

    const query = `${ciudad}, ${pais}`;

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {

            if (!data.length) return;

            const lat = data[0].lat;
            const lon = data[0].lon;

            const map = L.map('mapa').setView([lat, lon], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            L.marker([lat, lon])
                .addTo(map)
                .bindPopup(query)
                .openPopup();
        })
        .catch(err => console.error("Error cargando mapa:", err));

});