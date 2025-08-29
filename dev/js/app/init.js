// Verifica se o AlpineJS está carregado
if (typeof Alpine === 'undefined') {
    console.error('AlpineJS não foi carregado corretamente!');
} else {
    console.log('AlpineJS carregado com sucesso!');
    window.alpineInitialized = true;
}