document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('form-recuperar').addEventListener('submit', enviar);
});

async function enviar(evento) {
    evento.preventDefault();
    const boton = evento.target.querySelector('button[type="submit"]');
    boton.disabled = true;
    const respuesta = await API.request('/auth/recuperar', 'POST', {
        correo: document.getElementById('correo').value.trim(),
        ci: document.getElementById('ci').value.trim(),
        password: document.getElementById('password').value
    });
    boton.disabled = false;
    const aviso = document.getElementById('mensaje');
    aviso.hidden = false;
    aviso.textContent = respuesta.message || 'No se pudo actualizar la contraseña';
    aviso.className = 'aviso ' + (respuesta.status === 'ok' ? 'aviso-ok' : 'aviso-error');
}
