document.addEventListener("DOMContentLoaded", function() {
    const select = document.getElementById("categoria_id");
    const header = document.getElementById("headerCategoria");
    const submit = document.getElementById("submitColor");

    function actualizarColor() {
        const color = select.options[select.selectedIndex].dataset.color;
        header.style.backgroundColor = color;
        submit.style.backgroundColor = color;
    }

    select.addEventListener("change", actualizarColor);

    actualizarColor();
})