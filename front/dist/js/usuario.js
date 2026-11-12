let prestamos = [];

document.addEventListener('DOMContentLoaded', async () => {
    const yo = await API.request('/auth/me');
    if (yo.status !== 'ok') {
        window.location.href = '/front/login.html';
        return;
    }
    document.getElementById('verificando').hidden = true;
    document.getElementById('app-usuario').hidden = false;
    document.getElementById('btn-salir').addEventListener('click', () => Sesion.cerrar());
    document.getElementById('btn-menu-lateral').addEventListener('click', () => {
        document.getElementById('app-usuario').classList.toggle('menu-abierto');
    });
    await cargarPrestamos();
});

function alCambiarIdioma() {
    pintarPrestamos();
}

async function cargarPrestamos() {
    const respuesta = await API.request('/prestamos/mios');
    prestamos = respuesta.status === 'ok' ? respuesta.data : [];
    pintarPrestamos();
}

function pintarPrestamos() {
    const lista = document.getElementById('lista-prestamos');
    lista.textContent = '';
    if (prestamos.length === 0) {
        const vacio = document.createElement('p');
        vacio.textContent = t('sin_prestamos');
        lista.appendChild(vacio);
        return;
    }
    prestamos.forEach((prestamo) => lista.appendChild(tarjeta(prestamo)));
}

function tarjeta(prestamo) {
    const caja = document.createElement('article');
    caja.className = 'prestamo-card';

    if (prestamo.imagen) {
        const imagen = document.createElement('img');
        imagen.src = prestamo.imagen;
        imagen.alt = prestamo.titulo;
        caja.appendChild(imagen);
    } else {
        const hueco = document.createElement('div');
        hueco.className = 'sin-portada';
        hueco.textContent = prestamo.titulo;
        caja.appendChild(hueco);
    }

    const datos = document.createElement('div');
    const titulo = document.createElement('h3');
    titulo.textContent = prestamo.titulo;
    datos.appendChild(titulo);

    const meta = document.createElement('p');
    meta.textContent = prestamo.autor + ' · ' + prestamo.categoria;
    datos.appendChild(meta);

    const info = document.createElement('p');
    info.textContent = prestamo.descripcion;
    datos.appendChild(info);

    const alta = document.createElement('p');
    alta.textContent = t('fecha_prestamo') + ': ' + prestamo.fecha_prestamo;
    datos.appendChild(alta);

    const baja = document.createElement('p');
    baja.textContent = t('fecha_devolucion') + ': ' + prestamo.fecha_devolucion;
    datos.appendChild(baja);

    const acciones = document.createElement('div');
    acciones.className = 'acciones';
    if (Number(prestamo.esperando_autorizacion) === 1) {
        const espera = document.createElement('p');
        espera.className = 'leyenda-espera';
        espera.textContent = t('esperando');
        acciones.appendChild(espera);
    } else {
        acciones.appendChild(boton(t('renovar'), () => renovar(prestamo.id_prestamo)));
        acciones.appendChild(boton(t('devolver'), () => devolver(prestamo.id_prestamo), 'btn-peligro'));
    }
    datos.appendChild(acciones);

    caja.appendChild(datos);
    return caja;
}

function boton(texto, accion, clase) {
    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = clase || 'btn-admin';
    boton.textContent = texto;
    boton.addEventListener('click', accion);
    return boton;
}

async function renovar(id) {
    const respuesta = await API.request('/prestamos/' + id + '/renovar', 'PUT');
    if (respuesta.status !== 'ok') return;
    await cargarPrestamos();
}

async function devolver(id) {
    const respuesta = await API.request('/prestamos/' + id + '/devolver', 'PUT');
    if (respuesta.status !== 'ok') return;
    await cargarPrestamos();
}
