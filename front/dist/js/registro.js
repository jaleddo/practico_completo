document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('form-registro').addEventListener('submit', enviar);
});

async function enviar(evento) {
    evento.preventDefault();
    const password = document.getElementById('password').value;
    const repetir = document.getElementById('repetir').value;
    if (password !== repetir) {
        mostrar('Las contraseñas no coinciden');
        return;
    }
    const boton = evento.target.querySelector('button[type="submit"]');
    boton.disabled = true;
    const respuesta = await API.request('/auth/registro', 'POST', {
        nombre: document.getElementById('nombre').value.trim(),
        apellido: document.getElementById('apellido').value.trim(),
        telefono: document.getElementById('telefono').value.trim(),
        ci: document.getElementById('ci').value.trim(),
        correo: document.getElementById('correo').value.trim(),
        password: password
    });
    boton.disabled = false;
    if (respuesta.status !== 'ok') {
        mostrar(respuesta.message || 'No se pudo crear la cuenta');
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
