'use strict'

/**
 * Classe ScrollAnimations
 * Controla as animações de elementos ao rolar a página e a pausa automática de vídeos quando saem do viewport.
 */
class ScrollAnimations {

    /**
     * Construtor da classe ScrollAnimations
     * @param {String} className - Classe CSS opcional que pode ser atribuída aos elementos para animar. Por padrão, será usada a classe 'animated'.
     */
    constructor(className = '') {
        // Adiciona a classe 'scrollAnimations' ao body para indicar que o módulo de animação está ativo
        document.body.classList.add('scrollAnimations');

        // Define a classe a ser adicionada aos elementos que serão animados. Se não for especificada, será 'animated'.
        this.className = className || 'animated';

        // Inicializa o array que conterá todos os elementos que serão monitorados pelo IntersectionObserver
        this.targets = [];
    }

    /**
     * Adiciona um efeito de animação aos elementos especificados
     * @param {string|array} target - Seletor CSS ou lista de seletores para os elementos que serão animados.
     * @param {string} margin - Margem de interseção que define quando o elemento deve começar a animar em relação ao viewport (ex: '0px', '50px', etc.).
     * @param {boolean} oneTime - Define se o elemento será animado apenas uma vez (true) ou sempre que entrar/sair do viewport (false).
     */
    add(target, margin, oneTime = true) {
        // Opções do IntersectionObserver, como a margem que define o início da animação
        var observerOptions = { rootMargin: margin };

        // Seleciona os elementos com base no seletor fornecido (pode ser um array de seletores)
        let targets = document.querySelectorAll(target);

        // Armazena cada elemento no array de targets da classe
        targets.forEach(elmt => {
            this.targets.push(elmt);
        });

        // Cria o IntersectionObserver para monitorar os elementos e verificar quando entram no viewport
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {

                // Se o elemento entrou no viewport, adiciona a classe de animação
                if (entry.isIntersecting) {
                    entry.target.classList.add(this.className);
                }

                // Se o elemento sair do viewport e oneTime for falso, remove a classe de animação
                else if (!oneTime) {
                    entry.target.classList.remove(this.className);
                }

            });
        }, observerOptions);
    }

    /**
     * Pausa vídeos automaticamente quando saem do viewport
     * @param {string|array} target - Seletor CSS ou lista de seletores para os vídeos.
     * @param {string} margin - Margem de interseção que define quando o vídeo deve ser pausado em relação ao viewport.
     */
    videoScrollPause(target, margin) {

        // Opções do IntersectionObserver, como a margem que define o início da pausa
        var observerOptions = { rootMargin: margin },
            targets = document.querySelectorAll(target);

        // Armazena os vídeos no array de targets da classe
        targets.forEach(elmt => {
            this.targets.push(elmt);
        });

        // Cria o IntersectionObserver para monitorar quando os vídeos saem do viewport
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {

                // Se o vídeo saiu do viewport, ele é pausado
                if (!entry.isIntersecting) {
                    entry.target.pause();
                }

                // Se o vídeo entrou novamente no viewport, ele é reproduzido
                if (entry.isIntersecting) {
                    entry.target.play();
                }

            });
        }, observerOptions);
    }

    /**
     * Inicia a observação dos elementos adicionados para animação
     * Este método precisa ser chamado após adicionar os elementos com a função add()
     */
    run() {
        // Para cada elemento no array de targets, inicia o monitoramento do IntersectionObserver
        this.targets.forEach(element => {
            this.observer.observe(element);
        });
    }
}
