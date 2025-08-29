<?php
/*
 Template Name: Semana Brasil 2025
*/

get_header();
?>

<main class="home-hero"
    x-data="{ isMuted: true }" x-intersect:enter="$refs.video.play()" x-intersect:leave="$refs.video.pause()">
    <div class="video-background" style="background-image:url('<?=the_field('background_hero')?>')">
        <img src="<?=THEMEROOT_DEV?>img/faixa-bf_top.png" class="faixa top">
        <img src="<?=THEMEROOT_DEV?>img/faixa-bf_bottom.png" class="faixa bottom">

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

    <div class="overlay">
        <div class="content">
            <h1 class="logo">
                <img src="<?=THEMEROOT_DIST?>svg/logo-fozplaza_white.svg" alt="Foz Plaza Hotel"
                    onload="SVGInject(this)">
            </h1>
            <h2 class="logo-black">
                <img src="<?=THEMEROOT_DIST?>svg/logo_black-friday_foz-plaza.svg" alt="Semana Brasil 2025 - 15% OFF"
                    onload="SVGInject(this)">
            </h2>
            <div class="stamps">
                <img src="<?=THEMEROOT_DIST?>svg/periodo-vendas.svg" alt="Vendas de 25/11 a 01/12"
                    onload="SVGInject(this)">
                <img src="<?=THEMEROOT_DIST?>svg/periodo-hospedagem.svg" alt="Vendas de 25/11 a 01/12"
                    onload="SVGInject(this)">
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
        <h3 class="maior-oferta">Para a maior oferta do ano!</h3>
    </div><!-- .container-screen -->
    <a name="cadastrar" class="anchor-link" data-scroll-offset="70"></a>
</section><!-- .countdown -->

<dialog id="modal-success" class="modal-success">
    <div class="lottie-success" id="lottie-success"></div>
    <h3 class="tit">Uhuuu <span class="name"></span>,</h3>
    <h4 class="sub">agora você é VIP na Black do Foz Plaza 😎</h4>
    <h5 class="apoio">Seu cadastro foi realizado com sucesso!</h5>
    <p class="txt">
        Enviaremos no seu email e whatsapp todas as informações sobre a <strong>Black Friday Foz Plaza</strong>,
        inclusive o código
        secreto
        para ganhar super descontos, fica de olho 👀, combinado?!
    </p>
    <div class="footer">
        <button class="btn-main close-modal"><span class="label">Entendido!</label></button>
    </div>
</dialog>

<article class="participe">
    <div class="container-screen padding-x">
        <h6 class="overline">🚨 Serão apenas 350 reservas</h6>
        <h2 class="tit">Participe Agora</h2>
        <h3 class="sub"><strong>Cadastre-se para participar</strong> e receber o código secreto<br>para a <strong>maior
                promoção
                da história!</strong>
        </h3>

        <div class="form-cadastro">
            <div class="input-box nome">
                <input class="input icon-nome" required autocomplete="name" type="text" name="nome" id="nome">
                <label class="label" for="nome">Seu Nome</label>
                <span class="error-msg">Informe seu nome</span>
                <span class="i-error"></span>
                <span class="i-ok"></span>
            </div>
            <div class="input-box email">
                <input class="input icon-email" required autocomplete="email" type="text" name="email" id="email">
                <label class="label" for="email">Email</label>
                <span class="error-msg">Email inválido</span>
                <span class="i-error"></span>
                <span class="i-ok"></span>
            </div>
            <div class="input-box ddd">
                <select class="input" name="ddd" id="ddd" required>
                    <option value=""></option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="13">13</option>
                    <option value="14">14</option>
                    <option value="15">15</option>
                    <option value="16">16</option>
                    <option value="17">17</option>
                    <option value="18">18</option>
                    <option value="19">19</option>
                    <option value="21">21</option>
                    <option value="22">22</option>
                    <option value="24">24</option>
                    <option value="27">27</option>
                    <option value="28">28</option>
                    <option value="31">31</option>
                    <option value="32">32</option>
                    <option value="33">33</option>
                    <option value="34">34</option>
                    <option value="35">35</option>
                    <option value="37">37</option>
                    <option value="38">38</option>
                    <option value="41">41</option>
                    <option value="42">42</option>
                    <option value="43">43</option>
                    <option value="44">44</option>
                    <option value="45">45</option>
                    <option value="46">46</option>
                    <option value="47">47</option>
                    <option value="48">48</option>
                    <option value="49">49</option>
                    <option value="51">51</option>
                    <option value="53">53</option>
                    <option value="54">54</option>
                    <option value="55">55</option>
                    <option value="61">61</option>
                    <option value="62">62</option>
                    <option value="63">63</option>
                    <option value="64">64</option>
                    <option value="65">65</option>
                    <option value="66">66</option>
                    <option value="67">67</option>
                    <option value="68">68</option>
                    <option value="69">69</option>
                    <option value="71">71</option>
                    <option value="73">73</option>
                    <option value="74">74</option>
                    <option value="75">75</option>
                    <option value="77">77</option>
                    <option value="79">79</option>
                    <option value="81">81</option>
                    <option value="82">82</option>
                    <option value="83">83</option>
                    <option value="84">84</option>
                    <option value="85">85</option>
                    <option value="86">86</option>
                    <option value="87">87</option>
                    <option value="88">88</option>
                    <option value="89">89</option>
                    <option value="91">91</option>
                    <option value="92">92</option>
                    <option value="93">93</option>
                    <option value="94">94</option>
                    <option value="95">95</option>
                    <option value="96">96</option>
                    <option value="97">97</option>
                    <option value="98">98</option>
                    <option value="99">99</option>
                    <!-- Continue adicionando outras opções conforme necessário -->
                </select>
                <label class="label" for="ddd">DDD</label>
                <span class="error-msg">Selecione o DDD</span>
            </div>
            <div class="input-box phone">
                <input class="input icon-phone" required autocomplete="tel" type="tel" name="phone" id="phone"
                    inputmode="numeric">
                <label class="label" for="phone">Número Whatsapp</label>
                <span class="error-msg">Digite o número Whatsapp</span>
                <span class="i-error"></span>
                <span class="i-ok"></span>
            </div>
            <div class="accepts">
                <div class="accept">
                    <input type="checkbox" name="accept-politicas" id="accept-politicas" required checked>
                    <span class="error-msg">É necessário aceitar nossas políticas de privacidade para participar.</span>
                    <label for="accept">
                        Aceito receber comunicação por e-mail, SMS e/ou Whatsapp sobre produtos e
                        serviços do Foz Plaza Hotel, conforme <a href="https://fozplaza.com.br/politicas-de-privacidade"
                            target="_blank" class="external-link">políticas de
                            privacidade</a>
                    </label>
                </div>
                <div class="accept">
                    <input type="checkbox" name="accept-condicoes" id="accept-condicoes" required checked>
                    <span class="error-msg">É necessário aceitar as condições da promoção para participar.</span>
                    <label for="accept">
                        Li e concordo com as <a href="#condicoes" class="scroll-link">condições da promoção Foz Plaza
                            Black Friday 2024</a>
                    </label>
                </div>
            </div>
            <div class="input-box submit">
                <button id="btn-submit" class="btn-main" type="submit">
                    <span class="label">Cadastrar</span>
                    <div class="loader"></div>
                </button>
            </div>
        </div><!-- .form-cadastro -->
    </div><!-- .container-screen -->
</article><!-- participe -->

<article class="vantagens">
    <a name="motivos" class="anchor-link" data-scroll-offset="120"></a>
    <div class="container-screen padding-x">
        <h6 class="overline-section">Porque você não pode perder?</h6>
        <h2 class="tit-section">Motivos pra participar 🚀</h2>
        <section class="vantagens-boxes">
            <div class="box">
                <img class="icon-item" src="<?=THEMEROOT_DIST?>svg/i_desconto.svg" alt="Vantagem"
                    onload="SVGInject(this)">
                <h3 class="tit-item"><strong>O MAIOR DESCONTO</strong> DA HISTÓRIA</h3>
                <p>Serão <strong>até 40% OFF em tarifas</strong> para hospedagens válidas até Dezembro de 2025.</p>
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
                <p><strong>Você aproveita o precinho da Black agora</strong>, mas se precisar mudar depois, é só ajustar
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
                <p>É a oportunidade perfeita para você curtir um dos hotéis de Foz do Iguaçu <strong>mais bem avaliados
                        no
                        TripAdvisor!</strong></p>
            </div>
        </section><!-- . vantagens-box -->

        <a href="#cadastrar" class="btn-main scroll-link">Cadastre-se e participe!</a>
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
        <button id="show-more" class="btn-more"><strong>+</strong> fotos</button>
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
                        É simples! Basta se cadastrar com seu e-mail e WhatsApp no formulário desta página e ficar
                        atento às nossas mensagens. Enviaremos seu cupom de desconto na data de abertura das
                        vendas! (25/Nov)
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>Quando será as vendas?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        As vendas ocorrerão de 25 de novembro a 1º de dezembro de 2024. Enviaremos o link da página de
                        vendas, junto com o cupom de desconto, para que você possa fazer sua reserva!
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
                    <h2>Onde está meu cupom de desconto?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        Após se cadastrar no formulário desta página, você receberá uma confirmação do seu cadastro. No
                        período de vendas da campanha, enviaremos o código secreto do desconto para o seu e-mail e
                        WhatsApp, para que você possa utilizá-lo.
                    </p>
                </div>
            </div>
            <div class="item">
                <header onClick="toggleItem(this)">
                    <h2>Por que não recebi 40% de desconto?</h2>
                    <span class="material-symbols-outlined"><i class="fas fa-chevron-down"></i></span>
                </header>
                <div>
                    <p>
                        Os descontos variam de acordo com as datas escolhidas. Fique atento, pois algumas datas possuem
                        descontos diferentes. Nossa equipe pode auxiliar a encontrar a melhor data e preço, entre em
                        contato conosco.
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
                        Sim! Nossa equipe de recreação está disponível às sextas, sábados e feriados, garantindo
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