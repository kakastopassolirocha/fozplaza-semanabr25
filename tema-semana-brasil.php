<?php
/*
 Template Name: Semana Brasil 2025
*/

get_header();
?>

<main class="home-hero"
    x-data="{ isMuted: true }" x-intersect:enter="$refs.video.play()" x-intersect:leave="$refs.video.pause()">
    <div class="video-background" style="background-image:url('<?=the_field('background_hero')?>')">
        <!-- <img src="<?=THEMEROOT_DEV?>img/faixa-bf_top.png" class="faixa top">
        <img src="<?=THEMEROOT_DEV?>img/faixa-bf_bottom.png" class="faixa bottom"> -->

        <?php
        // Verifica se existe o vídeo "video_hero"
        $video_hero = get_field('video_hero');
        $video_hero_web = get_field('video_hero_webm');

        if ($video_hero || $video_hero_web): 
        ?>
        <video x-ref="video" autoplay muted loop playsinline class="bg-video video-scroll">
            <?php if ($video_hero_web): ?>
            <source src="<?= $video_hero_web ?>" type="video/webm">
            <?php endif; ?>
            
            <?php if ($video_hero): ?>
            <source src="<?= $video_hero ?>" type="video/mp4">
            <?php endif; ?>
        </video>
        <?php endif; ?>
    </div><!-- .video-background -->

    <div class="overlay size-full">
        <div class="content !size-full !flex !flex-col align-items-center justify-center">
            <!-- <h1 class="logo mx-auto flex justify-center">
                <img src="<?=THEMEROOT_DIST?>svg/logo-fozplaza_white.svg" alt="Foz Plaza Hotel"
                    onload="SVGInject(this)">
            </h1> -->
            <h2 class="flex justify-center relative">
                <img class="logo-img w-[300px] sm:w-[420px] lg:w-[480px]"
                    src="<?=THEMEROOT_DIST?>svg/semana-brasil_sem-arara.svg" alt="Semana Brasil 2025 - 15% OFF" />
                <img class="absolute transform w-[100px] translate-x-32 translate-y-12 sm:w-auto sm:translate-x-48 lg:translate-x-56 lg:translate-y-14 arara-flying"
                    src="<?=THEMEROOT_DIST?>img/arara.png" alt="Arara - Cataratas do Iguaçu - Foz do Iguaçu" />
            </h2>
            <div class="flex justify-center !mt-16">
                <img class="w-[240px] lg:w-[320px]"
                    src="<?=THEMEROOT_DIST?>img/periodo-de-vendas.png" alt="Vendas de 05 a 11 de Setembro" />
            </div>
        </div><!-- .content -->
    </div><!-- .overlay -->
</main><!-- .home-hero -->

<section class="countdown">
    <div class="container-screen">
        <h4 class="tit">⏱ Contagem Regressiva</h4>
        <div id="countdown-timer" class="timer" data-countdown="2025/09/05 00:00:01">
            <div class="box dias">
                <h5>10</h5>
                <h6>Dias</h6>
            </div>
            <div class="box horas">
                <h5>10</h5>
                <h6>Horas</h6>
            </div>
            <div class="box minutos">
                <h5>10</h5>
                <h6>Minutos</h6>
            </div>
            <div class="box segundos">
                <h5>10</h5>
                <h6>Segundos</h6>
            </div>
        </div><!-- .timer -->
        <h3 class="maior-oferta">Para você poder aproveitar!</h3>
    </div><!-- .container-screen -->
    <a name="cadastrar" class="anchor-link" data-scroll-offset="70"></a>
</section><!-- .countdown -->

<article class="participe bg-brazul">
    <div class="container-screen padding-x">
        <h6 class="overline-item !px-4 !text-lg">🚨 Serão apenas 350 reservas</h6>
        <h2 class="tit">Fique de olho, não perca!</h2>
        <h3 class="sub"><strong>Consulte tarifas e faça reservas</strong> aqui mesmo nessa página durante o período de vendas, anote aí:<br>
            <strong>De 05 a 15 de Setembro de 2025</strong>
        </h3>
    </div><!-- .container-screen -->
</article><!-- participe -->

<article class="vantagens !pb-32">
    <a name="motivos" class="anchor-link" data-scroll-offset="120"></a>
    <div class="container-screen padding-x">
        <h6 class="overline-section">Porque você não pode perder?</h6>
        <h2 class="tit-section">Motivos pra participar 🚀</h2>
        <section class="vantagens-boxes">
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_desconto.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item"><strong class="bg-bramarelo !p-1 !text-gray-900 rounded-sm">15% OFF</strong> ATÉ DEZEMBRO!</h3>
                <p>Serão <strong>15% OFF em tarifas</strong> para hospedagens válidas até Dezembro de 2025.</p>
            </div>
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_desconto.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item">RESERVAS <strong>LIMITADAS</strong></h3>
                <p>Liberamos <strong>apenas 350 reservas para essa promoção</strong>, não vai querer ficar de fora né!?
                </p>
            </div>
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_calendario.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item">COMPRE AGORA E <strong>USE DEPOIS</strong></h3>
                <p>Você poderá reservar no período de vendas para <strong>hospedagens até Dezembro de 2025.</strong></p>
            </div>
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_crianca.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item">
                    CRIANÇA <strong>FREE</strong>
                    <a href="#condicoes" class="scroll-link">*</a>
                </h3>
                <p>Uma <strong>criança de até 07 anos é totalmente FREE</strong>, e por aqui os pequenos se divertem pra
                    valer</p>
            </div>
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_desconto.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item">
                    REMARCAÇÃO <strong>FLEXÍVEL</strong>
                    <a href="#condicoes" class="scroll-link">*</a>
                </h3>
                <p><strong>Você aproveita o precinho</strong>, mas se precisar mudar depois, é só ajustar
                    de acordo com o
                    novo período e tarifa escolhida!</p>
            </div>
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_desconto.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item">
                    EM ATÉ <strong>10x sem juros</strong>
                    <a href="#condicoes" class="scroll-link">*</a>
                </h3>
                <p>Além do descontão, você ainda pode parcelar em <strong>até 10x sem juros</strong>, parcelinhas que
                    cabem no seu bolso!
                </p>
            </div>
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_tripadvisor.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item">TRAVELLER'S <strong>CHOICE</strong></h3>
                <p>Oportunidade para você curtir um dos hotéis de Foz do Iguaçu mais bem avaliados
                        no
                        TripAdvisor. <strong>Eleito entre os TOP 10% melhores hotéis do mundo!</strong></p>
            </div>
        </section><!-- . vantagens-box -->

        <!-- <a href="#cadastrar" class="btn-main scroll-link">Cadastre-se e participe!</a> -->
    </div><!-- .container-screen -->
</article><!-- .vantagens -->

<article class="o-hotel">
    <a name="o-hotel" class="anchor-link" data-scroll-offset="120"></a>
    <div class="container-screen padding-x">
        <h6 class="overline-section">Um oásis de conforto e lazer</h6>
        <h2 class="tit-section">Foz Plaza Hotel</h2>
        <p class="desc-section">
            Confira nossa galeria e tenha um gostinho do que te espera!
        </p>

        <div id="gallery" style="color:#fff;">
            <?php
            $gallery = get_field('galeria_fotos');
            if ($gallery):
                foreach ($gallery as $index => $image): ?>

            <div class="foto<?php if ($index >= 12) echo ' hidden'; ?>">
                <a href="<?=$image['url']?>" data-jbox-image="gallery1">
                    <img src="<?=$image['sizes']['medium']; ?>" alt="<?= esc_attr($image['alt']); ?>" loading="lazy">
                </a>
            </div>

            <?php
                endforeach;
            endif;
            ?>
        </div><!-- #gallery -->
        <button id="show-more" class="btn-more"><strong class="!rounded-full">+</strong> FOTOS</button>
    </div><!-- .container-screen -->
</article><!-- o-hotel -->

<article class="duvidas">
    <a name="duvidas-frequentes" class="anchor-link"></a>
    <div class="container-screen padding-x">
        <h6 class="overline-section">Ainda em dúvida? 🤔</h6>
        <h2 class="tit-section">Dúvidas Frequentes</h2>
        <div class="accordions">
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>Como posso participar da promoção?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        É simples! Do dia 05 ao dia 15 de Setembro basta entrar nessa página para fazer suas consultas de tarifas e suas reservas. Todas as tarifas até Dezembro de 2025 estarão com o desconto de 15% OFF aplicado. Mas fique ligado, pois algumas datas podem ter disponibilidade limitada, e serã apenas 350 reservas com esse desconto especial.
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>Quando serão as vendas?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        As vendas ocorrerão de 05 de Setembro a 15 de Setembro de 2025, aqui mesmo nessa página.
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>Posso fazer mais de uma reserva?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        Sim! Você pode fazer quantas reservas desejar durante o período de vendas da campanha. No
                        entanto, limitamos a promoção a 350 reservas. Então, não perca tempo e garanta a sua nos
                        primeiros dias de venda!
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>Preciso de cupom de desconto?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        Não, durante o período de vendas da campanha, todas as tarifas disponíveis para reservas
                        até Dezembro de 2025 já estarão com o desconto de 15% OFF aplicado automaticamente.
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>É o melhor preço que vou conseguir?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        Sim! É o melhor preço de nossas tarifas que vai conseguir para reservas até Dezembro de 2025!
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>O hotel oferece equipe de recreação?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        Sim! Nossa equipe de recreação está disponível às sextas, sábados e feriados, e durante o mês de Dezembro inteiro, garantindo
                        diversão para toda a família.
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>O hotel possui piscina aquecida?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        Nossas piscinas não são aquecidas, mas em nosso estrutura oferecemos elegantes jacuzzis com água
                        aquecida, perfeitas para o seu relaxamento.
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>O café da manhã está incluído na promoção?</h2>
                    <span class="material-symbols-outlined">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                </header>
                <div>
                    <p>
                        Sim! O café da manhã está incluído em todas as reservas feitas durante a promoção, oferecendo
                        uma variedade de opções deliciosas para começar bem o seu dia.
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>Quais são as formas de pagamento?</h2>
                    <span class="material-symbols-outlined">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                </header>
                <div>
                    <p>
                        Aceitamos cartões de crédito, débito e Pix. As reservas podem ser parceladas em até 10 vezes sem
                        juros no cartão de crédito, com parcelas mínimas de R$ 100,00.
                    </p>
                </div>
            </div>
        </div><!-- .accordions -->
    </div><!-- .container-screen -->
</article><!-- .duvidas -->

<?php
$condicoes = get_field('condicoes');
if($condicoes):
?>
<article class="condicoes">
    <a name="condicoes" class="anchor-link" data-scroll-offset="0"></a>
    <div class="container-screen padding-x">
        <?php
        echo $condicoes;
        ?>
    </div> <!-- .container-screen -->
</article><!-- .condicoes -->
<?php endif; ?>

<?php
get_footer();
?>