let articulos = [];

document.addEventListener('DOMContentLoaded', () => {
    if (!sessionStorage.getItem('token')) {
        window.location.href = '/front/login.html';
        return;
    }
    cargarCatalogo();
    const buscador = document.getElementById('buscador');
    buscador.addEventListener('input', () => pintarCatalogo());
    buscador.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') evento.preventDefault();
    });
});

function alCambiarIdioma() {
    if (articulos.length) pintarCatalogo();
}

async function cargarCatalogo() {
    const respuesta = await API.request('/articulos');
    const lista = document.getElementById('lista-catalogo');
    if (respuesta.status !== 'ok' || !Array.isArray(respuesta.data)) {
        document.getElementById('resultado-catalogo').textContent = t('vacio');
        lista.textContent = '';
        return;
    }
    articulos = respuesta.data;
    pintarCatalogo();
}

function pintarCatalogo() {
    const lista = document.getElementById('lista-catalogo');
    const resultado = document.getElementById('resultado-catalogo');
    const texto = document.getElementById('buscador').value.trim().toLowerCase();
    const items = articulos.filter((item) => {
        if (!texto) return true;
        return [item.titulo, item.autor, item.categoria, item.descripcion].join(' ').toLowerCase().includes(texto);
    });
    resultado.textContent = items.length === 1 ? t('un_articulo') : items.length + ' ' + t('articulos');
    lista.textContent = '';
    if (items.length === 0) {
        const vacio = document.createElement('p');
        vacio.className = 'col-12';
        vacio.textContent = t('vacio');
        lista.appendChild(vacio);
        return;
    }
    items.forEach((articulo) => lista.appendChild(tarjeta(articulo)));
}

function textoBoton(articulo) {
    if (Number(articulo.esperando_autorizacion) === 1) return t('esperando');
    if (Number(articulo.en_biblioteca) === 1) return t('en_biblioteca');
    if (Number(articulo.cantidad_disponible) < 1) return t('sin_stock');
    return t('reservar');
}

function tarjeta(articulo) {
    const columna = document.createElement('div');
    columna.className = 'col';
    const caja = document.createElement('article');
    caja.className = 'alineador1';

    if (articulo.imagen) {
        const imagen = document.createElement('img');
        imagen.src = articulo.imagen;
        imagen.alt = articulo.titulo;
        imagen.className = 'img-fluid w-100 tapa';
        caja.appendChild(imagen);
    } else {
        const hueco = document.createElement('div');
        hueco.className = 'tapa sin-portada';
        hueco.textContent = articulo.titulo;
        caja.appendChild(hueco);
    }

    const titulo = document.createElement('h3');
    titulo.className = 'titulo-item';
    titulo.textContent = articulo.titulo;
    caja.appendChild(titulo);

    const meta = document.createElement('p');
    meta.className = 'meta-item';
    meta.textContent = articulo.autor + ' · ' + articulo.categoria;
    caja.appendChild(meta);

    const descripcion = document.createElement('p');
    descripcion.className = 'desc-item';
    descripcion.textContent = articulo.descripcion;
    caja.appendChild(descripcion);

    if (Number(articulo.esperando_autorizacion) === 1) {
        const leyenda = document.createElement('p');
        leyenda.className = 'leyenda-catalogo';
        leyenda.textContent = t('esperando');
        caja.appendChild(leyenda);
    } else {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'btn-reservar w-100';
        boton.textContent = textoBoton(articulo);
        boton.disabled = Number(articulo.en_biblioteca) === 1 || Number(articulo.cantidad_disponible) < 1;
        boton.addEventListener('click', () => reservar(articulo, boton));
        caja.appendChild(boton);
    }

    columna.appendChild(caja);
    return columna;
}

async function reservar(articulo, boton) {
    if (!sessionStorage.getItem('token')) {
        window.location.href = '/front/login.html';
        return;
    }
    boton.disabled = true;
    const respuesta = await API.request('/prestamos', 'POST', { id_articulo: articulo.id_articulo });
    if (respuesta.status === 'ok') {
        articulo.en_biblioteca = 1;
        if (Number(articulo.cantidad_disponible) > 0) {
            articulo.cantidad_disponible = Number(articulo.cantidad_disponible) - 1;
        }
        boton.textContent = t('en_biblioteca');
        return;
    }
    const mensaje = (respuesta.message || '').toLowerCase();
    if (mensaje.includes('autoriz')) {
        articulo.esperando_autorizacion = 1;
        pintarCatalogo();
        return;
    }
    if (mensaje.includes('ejemplar') || mensaje.includes('stock')) {
        articulo.cantidad_disponible = 0;
        boton.textContent = t('sin_stock');
        return;
    }
    boton.disabled = false;
    boton.textContent = t('reservar');
}
