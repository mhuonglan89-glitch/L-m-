document.addEventListener("DOMContentLoaded", function () {
    // Load header
    fetch("partials/header.html")
        .then(response => response.text())
        .then(data => {
            document.getElementById("header").innerHTML = data;
        });

    // Load footer
    fetch("partials/footer.html")
        .then(response => response.text())
        .then(data => {
            document.getElementById("footer").innerHTML = data;
        });
});
