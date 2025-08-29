document.addEventListener("DOMContentLoaded", () => {
    if (document.body.classList.contains('page-template-tema-home-fase-1'))
    {
        //* MODAL
        const modalSuccess = document.getElementById('modal-success');
        const closeModal = modalSuccess.querySelector('.close-modal');
        closeModal.addEventListener('click', () => {
            modalSuccess.close();
        });
        const modalTit = modalSuccess.querySelector('.tit');
        const modalSub = modalSuccess.querySelector('.sub');
        const modalApoio = modalSuccess.querySelector('.apoio');
        const modalTxt = modalSuccess.querySelector('p.txt');
        const modalFooter = modalSuccess.querySelector('.footer');

        // Fecha o modal quando o usuário clica no backdrop
        // modalSuccess.addEventListener('click', (event) => {
        //     // Verifica se o clique foi fora do conteúdo do modal
        //     if (event.target === modalSuccess) {
        //         modalSuccess.close();
        //     }
        // });
        
        //Campos formulário
        const inputNome = document.querySelector('.form-cadastro input#nome');
        const inputEmail = document.querySelector('.form-cadastro input#email');
        const inputDDD = document.querySelector('.form-cadastro .input#ddd');
        const inputPhone = document.querySelector('.form-cadastro .input#phone');
        
        const acceptPoliticas = document.querySelector('.form-cadastro #accept-politicas');
        const acceptCondicoes = document.querySelector('.form-cadastro #accept-condicoes');
        
        const btnSubmit = document.getElementById('btn-submit');

        btnSubmit.addEventListener('click', function(event) {
            event.preventDefault();

            //Custom toggleClass
            const toggleClass = (element, condition) => {
                element.classList.toggle('ok', condition);
                element.classList.toggle('error', !condition);

                if (!condition)
                {
                    element.focus();
                    return false;
                }
                return true;
            };

            // toggleClass(inputNome, inputNome.value.length >= 3, true);
            if(!toggleClass(inputNome, inputNome.value.length >= 3)) return;
            if(!toggleClass(inputEmail, validMail(inputEmail.value))) return;
            if(!toggleClass(inputDDD, inputDDD.value.length >= 2)) return;
            if(!toggleClass(inputPhone, inputPhone.value.replace(/\D/g, '').length >= 8)) return;
            if(!acceptPoliticas.checked)
            {
                acceptPoliticas.parentElement.classList.add('error');
                acceptPoliticas.focus();
                return false;
            }
            else
            {
                acceptPoliticas.parentElement.classList.remove('error');
            }
            if(!acceptCondicoes.checked)
            {
                acceptCondicoes.parentElement.classList.add('error');
                acceptCondicoes.focus();
                
                return false;
            }
            else
            {
                acceptCondicoes.parentElement.classList.remove('error');
            }
            
            //Valida se email já existe
            jQuery.post({
                url: backvars.ajax_url,
                data: {
                    action: "cadastro",
                    key: "valid_user",
                    mail: inputEmail.value,
                    ddd: inputDDD.value,
                    phone: inputPhone.value
                },
                beforeSend: () => {
                    this.classList.add('loading');
                    this.disabled = true;
                }
            })
            .done(function(res) {
                if (!res.success) {
                    inputEmail.parentElement.querySelector('.error-msg').innerHTML = res.data.message_email;
                    inputPhone.parentElement.querySelector('.error-msg').innerHTML = res.data.message_phone;

                    inputEmail.classList.toggle('ok', res.data.valid_email);
                    inputEmail.classList.toggle('error', !res.data.valid_email);
                    
                    inputPhone.classList.toggle('ok', res.data.valid_phone);
                    inputPhone.classList.toggle('error', !res.data.valid_phone);

                    btnSubmit.classList.remove('loading');
                    btnSubmit.disabled = false;

                    console.log(res.data.debug);
                } else {
                    //Cadastrar
                    // console.log('Cadastrar: ' + res.data.debug);

                    inputEmail.classList.remove('error');
                    inputPhone.classList.remove('error');
                    
                    //Inicializa o Lottie
                    var lottieSuccess = bodymovin.loadAnimation({
                        container: document.getElementById('lottie-success'),
                        renderer: 'svg',
                        loop: false,
                        path: backvars.dist + 'lottie/success.json',
                        rendererSettings: {
                            scaleMode: 'fit',
                            clearCanvas: true,
                            progressiveLoad: true
                        }
                    });
                    lottieSuccess.onComplete = () => {
                        jQuery(modalTit).fadeIn();
                        jQuery(modalSub).delay(200).fadeIn();
                        jQuery(modalApoio).delay(400).fadeIn();
                        jQuery(modalTxt).delay(600).fadeIn();
                        jQuery(modalFooter).delay(1200).fadeIn();
                    };

                    jQuery.post({
                        url: backvars.ajax_url,
                        data: {
                            action: "cadastro",
                            key: "register",
                            name: inputNome.value,
                            mail: inputEmail.value,
                            ddd: inputDDD.value,
                            phone: inputPhone.value
                        }
                    })
                    .done(function(res) {
                        if (!res.success) {
                            new jBox("Notice", {
                                content: "Verifique os seus dados",
                                title: "Ops... Algo deu errado!",
                                color: 'red',
                                autoClose: 5000,
                                showCountdown: true,
                                animation: { open: "tada", close: "pulse" }
                            });
                        } else {
                            // Insere na Datalayer o dados do usuário
                            window.dataLayer.push({
                                'event': 'FP_lead',
                                'eventDetails': {
                                    'category': 'Account',
                                    'action': 'create'
                                },
                                'userDetails': {
                                    'firstName': res.data.first_name,
                                    'lastName': res.data.last_name,
                                    'email': res.data.email,
                                    'phone': '+55' + res.data.ddd + res.data.phone
                                }
                            });

                            console.table(window.dataLayer);

                            modalSuccess.querySelector('.tit > .name').innerHTML = res.data.first_name;
                            jQuery("#lottie-success").siblings().fadeOut(0);
                            lottieSuccess.setSpeed(2);
                            lottieSuccess.play();
                            modalSuccess.showModal();
                        }
                    })
                    .fail(() => {
                        new jBox("Notice", {
                                content: "Tente novamente por favor 🙁",
                            title: "Ops... Algo deu errado!",
                            color: 'red',
                            autoClose: 5000,
                            showCountdown: true,
                            animation: { open: "tada", close: "pulse" }
                        });
                    })
                    .always(() => {
                        btnSubmit.classList.remove('loading');
                        btnSubmit.disabled = false;
                    });
                }
            })
            .fail(() => console.log('Erro AJAX verifyemail'));
        });

        // Observa mudanças no atributo "open" do dialog
        const observer = new MutationObserver(() => {
            if (modalSuccess.hasAttribute('open')) {
                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
            } else {
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
            }
        });
        // Configura o MutationObserver para monitorar o atributo "open"
        observer.observe(modalSuccess, { attributes: true });
    
    } // if body.classList.contains('page-template-tema-home-fase-1')
});

(function($) {
    $(document).ready(function() {
        if (document.body.classList.contains('page-template-tema-home-fase-1'))
        {
            var $inputPhone = $('.form-cadastro .input#phone');
            // console.log($inputPhone);

            var maskBehavior = function(val) {
                    return val.replace(/\D/g, "").length === 9 ?
                        "0 0000-0000" :
                        "0000-00000";
                },
                options = {
                    onKeyPress: function(val, e, field, options) {
                        field.mask(maskBehavior.apply({}, arguments), options);
                    },
                };
            $inputPhone.mask(maskBehavior, options);
        } // if document.body.classList.contains('page-template-tema-home-fase-1')
    });
})(jQuery);