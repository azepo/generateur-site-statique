// Sommaire du mémo : Bootstrap ScrollSpy met la classe "active" sur le lien de la section visible.
// On reporte cet état dans aria-current pour qu'il soit aussi annoncé par les lecteurs d'écran.
document.addEventListener('DOMContentLoaded', function () {
    var liens = document.querySelectorAll('#sommaire-memo a');

    window.addEventListener('activate.bs.scrollspy', function () {
        liens.forEach(function (lien) {
            if (lien.classList.contains('active')) {
                lien.setAttribute('aria-current', 'true');
            } else {
                lien.removeAttribute('aria-current');
            }
        });
    });
});
