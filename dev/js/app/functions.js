//Crie uma função para verificar se é um dispositivo móvel
function isMobile() {
    return window.innerWidth <= 768;
}

//Verificador de página
function isHome() {
    const path = window.location.pathname;
    return path === "/" || path === "";
}
function pageIncludePath(page) {
    return window.location.href.includes(page);
}
function pageEndWith(page) {
    return window.location.pathname.endsWith(page) || window.location.pathname.endsWith(page + "/");
}

function validMail(mail) {
    const filter =
        /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return filter.test(mail.trim());
}