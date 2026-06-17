document.addEventListener("DOMContentLoaded", () => {

    const map = L.map('mapa').setView([20, 0], 2);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marcador = null;

    map.on('click', async (e) => {

        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (marcador) {
            marcador.setLatLng(e.latlng);
        } else {
            marcador = L.marker(e.latlng).addTo(map);
        }

        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;
            const response = await fetch(url);
            const data = await response.json();

            if (!data.address) return;

            const pais = data.address.country || 'No detectado';
            const ciudad = data.address.city ||
                data.address.town ||
                data.address.village ||
                data.address.county ||
                'No detectada';

            document.getElementById('pais').value = pais;
            document.getElementById('ciudad').value = ciudad;

            marcador.bindPopup(`${ciudad}, ${pais}`).openPopup();

        } catch (error) {
            console.error(error);
        }
    });

});