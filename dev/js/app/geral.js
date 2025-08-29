// const scrollAnimations = new ScrollAnimations();
// scrollAnimations.videoScrollPause('.video-scroll', '-200px');
// scrollAnimations.run();

//Manipulando os SVG
SVGInject.setOptions({
    afterInject: function (img, svg) {
        // console.log(svg);
        let svgWidth = svg.getAttribute('width');
        let svgHeight = svg.getAttribute('height');
        // console.log(svgWidth, svgHeight);

        if (svgWidth > 0) {
            // Remover atributos 'width' e 'height' para respeitar o CSS
            // svg.removeAttribute('width');
            // svg.removeAttribute('height');

            // Certificar-se de que o 'viewBox' está presente
            if (!svg.hasAttribute('viewBox')) {
                svg.setAttribute('viewBox', `0 0 ${svgWidth} ${svgHeight}`);
            }
        }
    }
});


// Aguarda o DOM estar carregado para garantir que os elementos estão disponíveis
document.addEventListener("DOMContentLoaded", function () {

    // Seleciona o body para adicionar e remover classes
    var body = document.body;

    // * INICIA LOTTIE DO WHATSAPP
    var lottieSuccess = bodymovin.loadAnimation({
        container: document.getElementById('whatsapp-fixed'),
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: backvars.dist + 'lottie/whatsapp2.json',
        rendererSettings: {
            scaleMode: 'fit',
            clearCanvas: true,
            progressiveLoad: true
        }
    });

    //Coloca a rolagem no ponto zero e adiciona a classe "topScroll" ao body
    window.scrollTo(0, 0);
    body.classList.add("topScroll");

    // Adiciona um listener para o evento de scroll da página
    window.addEventListener("scroll", function () {
        // Verifica a quantidade de scroll vertical da página
        if (window.scrollY > 50) {
            // Se rolou mais de 50px, adiciona a classe 'inScroll' e remove 'topScroll'
            body.classList.add("inScroll");
            body.classList.remove("topScroll");
        } else {
            // Se o scroll está no topo ou menor que 50px, remove 'inScroll' e adiciona 'topScroll'
            body.classList.remove("inScroll");
            body.classList.add("topScroll");
        }
    });

    // Adiciona evento de clique para rolar suavemente até a âncora com -80px de diferença
    document.querySelectorAll('.scroll-link').forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();

            const targetId = this.getAttribute('href').substring(1);

            if (targetId === 'cadastrar') {
                setTimeout(() => {
                    document.getElementById('nome').focus();
                }, 500);
            }

            // const targetElement = document.getElementById(targetId);
            const targetElement = document.querySelector(`.anchor-link[name="${targetId}"]`);
            if (targetElement) {
                let scrollOffset = targetElement.getAttribute('data-scroll-offset') ? parseInt(targetElement.getAttribute('data-scroll-offset')) : 0;
                // console.log(scrollOffset);
                const offsetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - scrollOffset;
                // console.log(offsetPosition);
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // * LOAD MORE GALERIA
    var showMoreBtn = document.getElementById('show-more');
    showMoreBtn.addEventListener('click', function () {
        var hiddenItems = document.querySelectorAll('#gallery .foto.hidden');
        hiddenItems.forEach(function (item) {
            item.classList.remove('hidden');
        });
        showMoreBtn.style.display = 'none';
    });

    if (body.classList.contains('page-template-tema-semana-brasil')) {
        // * COUNTDOWN
        function updateCountdown() {
            const countdownElement = document.getElementById('countdown-timer');
            if (!countdownElement) return; // Verifica se o elemento existe

            const countdownDate = new Date(countdownElement.getAttribute('data-countdown')).getTime();
            const now = new Date().getTime();
            const distance = countdownDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            countdownElement.querySelector('.dias >:first-child').innerText = days;
            countdownElement.querySelector('.horas >:first-child').innerText = hours;
            countdownElement.querySelector('.minutos >:first-child').innerText = minutes;
            countdownElement.querySelector('.segundos >:first-child').innerText = seconds;

            if (distance < 0) {
                clearInterval(countdownInterval);
                countdownElement.innerHTML = "A contagem regressiva terminou!";
            }
        }

        const countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown();
    }

    // Remove a classe 'active' de todos os itens ao carregar o site
    document.querySelectorAll(".accordions .item").forEach(item => {
        item.querySelector("header").classList.remove("active");
        item.querySelector("div").style.height = "0px";
    });

    // Garantir que o vídeo seja reproduzido automaticamente em dispositivos móveis
    const video = document.querySelector('.bg-video');
    if (video) {
        video.play().catch(error => {
            console.error('Erro ao reproduzir o vídeo:', error);
        });
    }
});

// * ACCORDION
const toggleItem = (element) => {
    const headers = document.querySelectorAll(".accordions header");

    if (element.classList.contains("active")) {
        element.classList.remove("active");
        element.nextElementSibling.style.height = "0px";
        return;
    }

    for (let header of headers) {
        header.classList.remove("active");
        header.nextElementSibling.style.height = "0px";
    }

    element.classList.add("active");

    const content = element.nextElementSibling;

    const text = content.querySelector("p");

    content.style.height = `${text.clientHeight}px`;
}

(function ($) {
    $(document).ready(function () {
        // * GALERIA DE IMAGENS
        new jBox('Image', {
            imageCounter: true,
            imageCounterSeparator: ' de '
        });
        // * GALERIA DE IMAGENS
    });
})(jQuery);