document.addEventListener('DOMContentLoaded', async () => {
    const salir = document.getElementById('btn-salir-nav');
    const entrar = document.getElementById('btn-ingresar');
    const espacio = document.getElementById('btn-espacio');
    if (salir) {
        salir.addEventListener('click', () => Sesion.cerrar());
    }
    if (!sessionStorage.getItem('token')) {
        if (entrar) entrar.hidden = false;
        if (espacio) espacio.hidden = true;
        return;
    }

    const yo = await API.request('/auth/me');
    if (yo.status !== 'ok') {
        sessionStorage.removeItem('token');
        if (entrar) entrar.hidden = false;
        if (espacio) espacio.hidden = true;
        return;
    }
    if (entrar) entrar.hidden = true;
    if (salir) salir.hidden = false;
    if (espacio) {
        espacio.hidden = false;
        if (Number(yo.data.administrador) === 1) {
            espacio.dataset.i18n = 'administracion';
            espacio.href = '/front/admin/index.html';
        } else {
            espacio.dataset.i18n = 'mi_espacio';
            espacio.href = '/front/usuario/index.html';
        }
    }
    aplicarIdioma();
});
