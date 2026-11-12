document.addEventListener('DOMContentLoaded', async () => {
    if (sessionStorage.getItem('token')) {
        const yo = await API.request('/auth/me');
        if (yo.status === 'ok') {
            window.location.href = Sesion.destino(yo.data);
            return;
        }
        sessionStorage.removeItem('token');
    }
    document.getElementById('form-login').addEventListener('submit', enviar);
});

async function enviar(evento) {
    evento.preventDefault();
    const boton = evento.target.querySelector('button[type="submit"]');
    boton.disabled = true;
    const respuesta = await API.request('/auth/login', 'POST', {
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value
    });
    boton.disabled = false;
    if (respuesta.status !== 'ok') {
        mostrar(respuesta.message || 'No se pudo iniciar sesión');
        return;
    }
    Sesion.guardar(respuesta.data.token);
    window.location.href = Sesion.destino(respuesta.data.usuario);
}

function mostrar(texto) {
    const aviso = document.getElementById('mensaje');
    aviso.hidden = false;
    aviso.textContent = texto;
}
