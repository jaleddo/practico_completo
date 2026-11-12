document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-boletin');
    if (!form) return;
    form.addEventListener('submit', (evento) => {
        evento.preventDefault();
        document.getElementById('aviso-boletin').textContent = t('suscripto');
    });
});
