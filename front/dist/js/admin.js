let articulos = [];
let miId = 0;

const etiquetaEstado = {
    disponible: 'Disponible',
    prestado: 'Prestado'
};

document.addEventListener('DOMContentLoaded', async () => {
    const permitido = await exigirAdmin();
    if (!permitido) return;

    await cargarApoyos();
    await cargarArticulos();
    document.getElementById('form-articulo').addEventListener('submit', guardar);
    document.getElementById('btn-cancelar').addEventListener('click', () => {
        resetFormulario();
        mostrarAviso('Edición cancelada.', 'ok');
    });
    document.getElementById('btn-salir').addEventListener('click', () => Sesion.cerrar());
    document.querySelectorAll('[data-panel]').forEach((enlace) => {
        enlace.addEventListener('click', (evento) => {
            evento.preventDefault();
            mostrarPanel(enlace.dataset.panel);
        });
    });
    document.getElementById('form-usuario').addEventListener('submit', guardarUsuario);
    document.getElementById('btn-baja-usuario').addEventListener('click', eliminarPorCi);
    document.getElementById('form-admin').addEventListener('submit', guardarAdmin);
    document.getElementById('btn-baja-admin').addEventListener('click', eliminarAdminPorCi);
    document.getElementById('btn-menu-lateral').addEventListener('click', () => {
        document.getElementById('app-admin').classList.toggle('menu-abierto');
    });
    document.getElementById('lista-renovaciones').addEventListener('click', alClickRenovacion);
    document.getElementById('cuerpo-tabla').addEventListener('click', alClickTabla);
    document.getElementById('imagen-existente').addEventListener('change', (evento) => {
        mostrarVista(evento.target.value);
    });
    document.getElementById('imagen-archivo').addEventListener('change', (evento) => {
        const archivo = evento.target.files[0];
        if (!archivo) return;
        mostrarVista(URL.createObjectURL(archivo));
    });
});

async function exigirAdmin() {
    const respuesta = await API.request('/auth/me');
    if (respuesta.status !== 'ok') {
        window.location.href = '/front/login.html?motivo=admin';
        return false;
    }
    if (Number(respuesta.data.administrador) !== 1) {
        window.location.href = '/front/usuario/index.html';
        return false;
    }
    document.getElementById('verificando').hidden = true;
    document.getElementById('app-admin').hidden = false;
    miId = Number(respuesta.data.id_rol);
    document.getElementById('saludo').textContent = 'Hola administrador';
    return true;
}

async function cargarApoyos() {
    const [categorias, autores, editoriales, imagenes] = await Promise.all([
        API.request('/categorias'),
        API.request('/autores'),
        API.request('/editoriales'),
        API.request('/imagenes')
    ]);
    llenarSelect('id_categoria', categorias.data || [], 'id_categoria', 'nombre');
    llenarSelect('id_autor', autores.data || [], 'id_autor', 'nombre');
    llenarSelect('id_editorial', editoriales.data || [], 'id_editorial', (item) => {
        return item.pais ? item.nombre + ' (' + item.pais + ')' : item.nombre;
    });
    const selectImagen = document.getElementById('imagen-existente');
    const actual = selectImagen.value;
    selectImagen.textContent = '';
    const sinImagen = document.createElement('option');
    sinImagen.value = '';
    sinImagen.textContent = 'Sin imagen de portada';
    selectImagen.appendChild(sinImagen);
    (imagenes.data || []).forEach((ruta) => {
        const opcion = document.createElement('option');
        opcion.value = ruta;
        opcion.textContent = ruta.split('/').pop();
        selectImagen.appendChild(opcion);
    });
    if (actual) selectImagen.value = actual;
    if (!selectImagen.value && selectImagen.options.length) {
        mostrarVista(selectImagen.value);
    }
}

function llenarSelect(id, items, campoId, campoTexto) {
    const select = document.getElementById(id);
    const actual = select.value;
    select.textContent = '';
    items.forEach((item) => {
        const opcion = document.createElement('option');
        opcion.value = item[campoId];
        opcion.textContent = typeof campoTexto === 'function' ? campoTexto(item) : item[campoTexto];
        select.appendChild(opcion);
    });
    if (actual) select.value = actual;
}

async function cargarArticulos() {
    const respuesta = await API.request('/articulos/gestion');
    if (respuesta.status !== 'ok') {
        mostrarAviso(respuesta.message || 'No se pudieron leer los artículos', 'error');
        return;
    }
    articulos = respuesta.data;
    pintarTabla();
}

function pintarTabla() {
    const cuerpo = document.getElementById('cuerpo-tabla');
    cuerpo.textContent = '';
    if (articulos.length === 0) {
        const fila = document.createElement('tr');
        const celda = document.createElement('td');
        celda.colSpan = 5;
        celda.textContent = 'Todavía no hay artículos.';
        fila.appendChild(celda);
        cuerpo.appendChild(fila);
        return;
    }

    articulos.forEach((articulo) => {
        const fila = document.createElement('tr');

        const tapa = document.createElement('td');
        if (articulo.imagen) {
            const img = document.createElement('img');
            img.src = articulo.imagen;
            img.alt = '';
            img.className = 'mini';
            tapa.appendChild(img);
        } else {
            tapa.textContent = 'Sin tapa';
        }
        fila.appendChild(tapa);

        const datos = document.createElement('td');
        const titulo = document.createElement('strong');
        titulo.textContent = articulo.titulo;
        datos.appendChild(titulo);
        const meta = document.createElement('p');
        meta.className = 'meta';
        const editorial = articulo.pais_editorial
            ? articulo.editorial + ' (' + articulo.pais_editorial + ')'
            : articulo.editorial;
        meta.textContent = articulo.autor + ' · ' + editorial;
        datos.appendChild(meta);
        fila.appendChild(datos);

        const categoria = document.createElement('td');
        categoria.textContent = articulo.categoria + (articulo.destacado ? ' · Best seller' : '');
        fila.appendChild(categoria);

        const estado = document.createElement('td');
        estado.textContent = (etiquetaEstado[articulo.estado] || articulo.estado) + ' · ' + articulo.cantidad_disponible;
        fila.appendChild(estado);

        const acciones = document.createElement('td');
        acciones.className = 'acciones-fila';
        acciones.appendChild(botonFila('Editar', 'editar', articulo.id_articulo, 'btn-linea'));
        acciones.appendChild(botonFila('Eliminar', 'eliminar', articulo.id_articulo, 'btn-peligro'));
        fila.appendChild(acciones);

        cuerpo.appendChild(fila);
    });
}

function botonFila(texto, accion, id, clase) {
    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = clase;
    boton.dataset.accion = accion;
    boton.dataset.id = String(id);
    boton.textContent = texto;
    return boton;
}

function alClickTabla(evento) {
    const boton = evento.target.closest('button');
    if (!boton) return;
    const articulo = articulos.find((item) => item.id_articulo === Number(boton.dataset.id));
    if (!articulo) return;
    if (boton.dataset.accion === 'editar') cargarEnFormulario(articulo);
    if (boton.dataset.accion === 'eliminar') eliminarArticulo(articulo);
}

function cargarEnFormulario(articulo) {
    document.getElementById('id_articulo').value = articulo.id_articulo;
    document.getElementById('titulo').value = articulo.titulo;
    document.getElementById('descripcion').value = articulo.descripcion;
    document.getElementById('id_categoria').value = articulo.id_categoria;
    document.getElementById('id_autor').value = articulo.id_autor;
    document.getElementById('id_editorial').value = articulo.id_editorial;
    document.getElementById('anio').value = articulo.anio ?? '';
    document.getElementById('cantidad').value = articulo.cantidad_disponible ?? 0;
    document.getElementById('estado').value = articulo.estado === 'prestado' ? 'prestado' : 'disponible';
    document.getElementById('destacado').checked = articulo.destacado === 1;
    document.getElementById('nueva_categoria').value = '';
    document.getElementById('nuevo_autor').value = '';
    document.getElementById('nueva_editorial').value = '';
    document.getElementById('imagen-archivo').value = '';
    asegurarOpcionImagen(articulo.imagen);
    document.getElementById('imagen-existente').value = articulo.imagen;
    mostrarVista(articulo.imagen);
    document.getElementById('btn-guardar').textContent = 'Guardar cambios';
    document.getElementById('btn-cancelar').hidden = false;
    document.getElementById('form-articulo').scrollIntoView({ behavior: 'smooth' });
}

function asegurarOpcionImagen(ruta) {
    const select = document.getElementById('imagen-existente');
    const existe = Array.from(select.options).some((opcion) => opcion.value === ruta);
    if (existe) return;
    const opcion = document.createElement('option');
    opcion.value = ruta;
    opcion.textContent = ruta.split('/').pop();
    select.appendChild(opcion);
}

async function guardar(evento) {
    evento.preventDefault();
    const boton = document.getElementById('btn-guardar');
    boton.disabled = true;

    let imagen = document.getElementById('imagen-existente').value;
    const archivo = document.getElementById('imagen-archivo').files[0];
    if (archivo) {
        const subida = await API.subirImagen(archivo);
        if (subida.status !== 'ok') {
            mostrarAviso(subida.message || 'No se pudo subir la imagen', 'error');
            boton.disabled = false;
            return;
        }
        imagen = subida.data.imagen;
    }
    const id = document.getElementById('id_articulo').value;
    const payload = {
        titulo: document.getElementById('titulo').value.trim(),
        descripcion: document.getElementById('descripcion').value.trim(),
        id_categoria: Number(document.getElementById('id_categoria').value),
        nueva_categoria: document.getElementById('nueva_categoria').value.trim(),
        id_autor: Number(document.getElementById('id_autor').value),
        nuevo_autor: document.getElementById('nuevo_autor').value.trim(),
        id_editorial: Number(document.getElementById('id_editorial').value),
        nueva_editorial: document.getElementById('nueva_editorial').value.trim(),
        anio: document.getElementById('anio').value,
        cantidad_disponible: document.getElementById('cantidad').value,
        estado: document.getElementById('estado').value,
        destacado: document.getElementById('destacado').checked ? 1 : 0,
        imagen: imagen
    };

    const respuesta = id
        ? await API.request('/articulos/' + id, 'PUT', payload)
        : await API.request('/articulos', 'POST', payload);

    boton.disabled = false;
    if (respuesta.status !== 'ok') {
        mostrarAviso(respuesta.message || 'No se pudo guardar', 'error');
        return;
    }

    mostrarAviso(respuesta.message, 'ok');
    resetFormulario();
    await cargarApoyos();
    await cargarArticulos();
}

async function eliminarArticulo(articulo) {
    const confirmar = window.confirm('¿Eliminar «' + articulo.titulo + '»? También va a desaparecer del catálogo.');
    if (!confirmar) return;
    const respuesta = await API.request('/articulos/' + articulo.id_articulo, 'DELETE');
    if (respuesta.status !== 'ok') {
        mostrarAviso(respuesta.message || 'No se pudo eliminar', 'error');
        return;
    }
    if (document.getElementById('id_articulo').value === String(articulo.id_articulo)) {
        resetFormulario();
    }
    mostrarAviso('Artículo eliminado. El catálogo público ya no lo muestra.', 'ok');
    await cargarArticulos();
}

function resetFormulario() {
    document.getElementById('form-articulo').reset();
    document.getElementById('id_articulo').value = '';
    document.getElementById('btn-guardar').textContent = 'Agregar artículo';
    document.getElementById('btn-cancelar').hidden = true;
    const select = document.getElementById('imagen-existente');
    mostrarVista(select.value);
}

function mostrarVista(src) {
    const img = document.getElementById('vista-imagen');
    if (!src) {
        img.hidden = true;
        return;
    }
    img.src = src;
    img.hidden = false;
}

function mostrarPanel(id) {
    ['articulos', 'renovaciones', 'usuarios', 'administradores'].forEach((panel) => {
        document.getElementById(panel).hidden = panel !== id;
    });
    document.querySelectorAll('[data-panel]').forEach((enlace) => {
        enlace.classList.toggle('activo', enlace.dataset.panel === id);
    });
    if (id === 'renovaciones') cargarRenovaciones();
    if (id === 'usuarios') cargarUsuarios();
    if (id === 'administradores') cargarAdministradores();
}

async function cargarRenovaciones() {
    const respuesta = await API.request('/prestamos/pendientes');
    const lista = document.getElementById('lista-renovaciones');
    lista.textContent = '';
    const pendientes = respuesta.status === 'ok' ? respuesta.data : [];
    if (pendientes.length === 0) {
        lista.textContent = 'No hay renovaciones pendientes.';
        return;
    }
    pendientes.forEach((item) => {
        const caja = document.createElement('article');
        caja.className = 'prestamo-card';
        const datos = document.createElement('div');
        const titulo = document.createElement('h3');
        titulo.textContent = item.titulo;
        datos.appendChild(titulo);
        const quien = document.createElement('p');
        quien.textContent = item.nombre + ' ' + item.apellido + ' · ' + item.correo;
        datos.appendChild(quien);
        const fechas = document.createElement('p');
        fechas.textContent = 'Devolución actual: ' + item.fecha_devolucion;
        datos.appendChild(fechas);
        datos.appendChild(botonFila('Autorizar', 'autorizar', item.id_prestamo, 'btn-admin'));
        caja.appendChild(datos);
        lista.appendChild(caja);
    });
}

async function alClickRenovacion(evento) {
    const boton = evento.target.closest('button');
    if (!boton || boton.dataset.accion !== 'autorizar') return;
    const respuesta = await API.request('/prestamos/' + boton.dataset.id + '/autorizar', 'PUT');
    mostrarAviso(respuesta.message || 'No se pudo autorizar', respuesta.status === 'ok' ? 'ok' : 'error');
    await cargarRenovaciones();
}

async function cargarUsuarios() {
    const respuesta = await API.request('/roles');
    const lista = document.getElementById('lista-usuarios');
    const cantidad = document.getElementById('cantidad-usuarios');
    lista.textContent = '';
    if (respuesta.status !== 'ok') {
        cantidad.textContent = '';
        avisoUsuarios(respuesta.message || 'No se pudieron leer los usuarios', 'error');
        return;
    }
    const personas = respuesta.data.usuarios || [];
    cantidad.textContent = 'Usuarios existentes: ' + respuesta.data.cantidad;
    personas.forEach((persona) => {
        const caja = document.createElement('article');
        caja.className = 'prestamo-card';
        const datos = document.createElement('div');
        const titulo = document.createElement('h3');
        titulo.textContent = persona.nombre + ' ' + persona.apellido;
        datos.appendChild(titulo);
        const meta = document.createElement('p');
        meta.textContent = persona.correo + ' · CI ' + persona.ci + ' · ' + persona.telefono;
        datos.appendChild(meta);
        const libros = document.createElement('ul');
        libros.className = 'lista-simple';
        if (!persona.libros.length) {
            const item = document.createElement('li');
            item.textContent = 'Sin libros en préstamo';
            libros.appendChild(item);
        } else {
            persona.libros.forEach((libro) => {
                const item = document.createElement('li');
                item.textContent = libro.titulo + ' · préstamo ' + libro.fecha_prestamo + ' · devolución ' + libro.fecha_devolucion;
                libros.appendChild(item);
            });
        }
        datos.appendChild(libros);
        caja.appendChild(datos);
        lista.appendChild(caja);
    });
}

async function guardarUsuario(evento) {
    evento.preventDefault();
    const respuesta = await API.request('/roles', 'POST', {
        nombre: document.getElementById('u-nombre').value.trim(),
        apellido: document.getElementById('u-apellido').value.trim(),
        telefono: document.getElementById('u-telefono').value.trim(),
        ci: document.getElementById('u-ci').value.trim(),
        correo: document.getElementById('u-correo').value.trim()
    });
    avisoUsuarios(respuesta.message || 'No se pudo agregar', respuesta.status === 'ok' ? 'ok' : 'error');
    if (respuesta.status !== 'ok') return;
    evento.target.reset();
    await cargarUsuarios();
}

async function eliminarPorCi() {
    const ci = document.getElementById('baja-ci').value.trim();
    if (!ci) {
        avisoUsuarios('Ingresá la cédula', 'error');
        return;
    }
    if (!window.confirm('¿Eliminar la cuenta con cédula ' + ci + '?')) return;
    const respuesta = await API.request('/roles/baja', 'POST', { ci: ci });
    avisoUsuarios(respuesta.message || 'No se pudo eliminar', respuesta.status === 'ok' ? 'ok' : 'error');
    if (respuesta.status !== 'ok') return;
    document.getElementById('baja-ci').value = '';
    await cargarUsuarios();
    await cargarAdministradores();
}

async function cargarAdministradores() {
    const respuesta = await API.request('/roles');
    const lista = document.getElementById('lista-admins');
    lista.textContent = '';
    if (respuesta.status !== 'ok') {
        avisoAdmins(respuesta.message || 'No se pudieron leer los administradores', 'error');
        return;
    }
    const admins = (respuesta.data.usuarios || []).filter((persona) => Number(persona.administrador) === 1);
    if (admins.length === 0) {
        lista.textContent = 'No hay administradores.';
        return;
    }
    admins.forEach((persona) => {
        const caja = document.createElement('article');
        caja.className = 'prestamo-card';
        const datos = document.createElement('div');
        const titulo = document.createElement('h3');
        titulo.textContent = persona.nombre + ' ' + persona.apellido;
        datos.appendChild(titulo);
        const meta = document.createElement('p');
        meta.textContent = persona.correo + ' · CI ' + persona.ci;
        datos.appendChild(meta);
        caja.appendChild(datos);
        lista.appendChild(caja);
    });
}

async function guardarAdmin(evento) {
    evento.preventDefault();
    const respuesta = await API.request('/roles', 'POST', {
        nombre: document.getElementById('a-nombre').value.trim(),
        apellido: document.getElementById('a-apellido').value.trim(),
        telefono: document.getElementById('a-telefono').value.trim(),
        ci: document.getElementById('a-ci').value.trim(),
        correo: document.getElementById('a-correo').value.trim(),
        administrador: 1
    });
    avisoAdmins(respuesta.message || 'No se pudo agregar', respuesta.status === 'ok' ? 'ok' : 'error');
    if (respuesta.status !== 'ok') return;
    evento.target.reset();
    await cargarAdministradores();
}

async function eliminarAdminPorCi() {
    const ci = document.getElementById('baja-admin-ci').value.trim();
    if (!ci) {
        avisoAdmins('Ingresá la cédula', 'error');
        return;
    }
    if (!window.confirm('¿Eliminar el administrador con cédula ' + ci + '?')) return;
    const respuesta = await API.request('/roles/baja', 'POST', { ci: ci, solo_administrador: 1 });
    avisoAdmins(respuesta.message || 'No se pudo eliminar', respuesta.status === 'ok' ? 'ok' : 'error');
    if (respuesta.status !== 'ok') return;
    document.getElementById('baja-admin-ci').value = '';
    await cargarAdministradores();
    await cargarUsuarios();
}

function avisoAdmins(texto, tipo) {
    const aviso = document.getElementById('aviso-admins');
    aviso.hidden = false;
    aviso.textContent = texto;
    aviso.className = 'aviso ' + (tipo === 'ok' ? 'aviso-ok' : 'aviso-error');
}

async function alClickUsuario(evento) {
    const boton = evento.target.closest('button');
    if (!boton || boton.dataset.accion !== 'eliminar-usuario') return;
    if (!window.confirm('¿Eliminar este usuario?')) return;
    const respuesta = await API.request('/roles/' + boton.dataset.id, 'DELETE');
    avisoUsuarios(respuesta.message || 'No se pudo eliminar', respuesta.status === 'ok' ? 'ok' : 'error');
    if (respuesta.status === 'ok') await cargarUsuarios();
}

function avisoUsuarios(texto, tipo) {
    const aviso = document.getElementById('aviso-usuarios');
    aviso.hidden = false;
    aviso.textContent = texto;
    aviso.className = 'aviso ' + (tipo === 'ok' ? 'aviso-ok' : 'aviso-error');
}

function mostrarAviso(texto, tipo) {
    const aviso = document.getElementById('aviso-admin');
    aviso.hidden = false;
    aviso.textContent = texto;
    aviso.className = 'aviso ' + (tipo === 'ok' ? 'aviso-ok' : 'aviso-error');
}
