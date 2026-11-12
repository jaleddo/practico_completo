const TEXTOS = {
    es: {
        slogan: 'tu biblioteca virtual',
        ir_catalogo: 'Ir al catálogo',
        ir_espacio: 'Ir a mi espacio',
        contacto: 'Contacto',
        traducir: 'Traducir al inglés',
        ingresar: 'Ingresar',
        inicio: 'Inicio',
        loguin: 'loguin',
        salir: 'Cerrar sesión',
        panel: 'Panel',
        mi_espacio: 'mi espacio',
        mis_libros: 'Mis libros',
        catalogo: 'Catálogo',
        catalogo_bajada: 'Libros, revistas, audiolibros y best sellers',
        buscar: 'Buscá por título, autor o categoría',
        un_articulo: '1 artículo',
        articulos: 'artículos',
        vacio: 'No hay artículos con ese criterio.',
        reservar: 'RESERVAR',
        en_biblioteca: 'en tu biblioteca',
        sin_stock: 'sin stock',
        hero: 'Tu biblioteca, abierta a toda hora.',
        hero_texto: 'Leé, escuchá y descubrí libros, revistas y audiolibros desde cualquier dispositivo. Sin filas y sin esperas.',
        nosotros: 'Quiénes somos',
        nosotros_texto: 'Una biblioteca pensada para leer sin barreras. Nacimos para acercar la lectura a todos los estudiantes y curiosos. Curamos un catálogo accesible, gratuito y disponible las 24 horas, en cualquier dispositivo.',
        lectores: '+12k Lectores activos',
        acceso: '24/7 Acceso abierto',
        gratis: '100% Gratuito',
        categorias: 'Categorías',
        categorias_bajada: 'Nuestras categorías son libros, revistas, audiolibros y best sellers.',
        cat_libros: 'Libros',
        cat_revistas: 'Revistas',
        cat_audio: 'Audiolibros',
        cat_best: 'Best sellers',
        stat_libros: '1.240 Libros',
        stat_revistas: '320 Revistas',
        stat_audio: '480 Audiolibros',
        footer_texto: 'Biblioteca virtual abierta para estudiantes y lectores curiosos.',
        tu_email: 'Tu email',
        suscribir: 'Suscribirse',
        suscripto: 'Gracias. El boletín todavía no guarda correos.',
        entrar: 'Entrar',
        correo: 'Correo',
        contrasena: 'Contraseña',
        recuperar: 'Recuperar contraseña',
        registrar: 'Registrar',
        volver_landing: 'Volver a la landing page',
        frase: 'Tu biblioteca, abierta a toda hora.',
        reg_titulo: 'Crear cuenta',
        nombre: 'Nombre',
        apellido: 'Apellido',
        telefono: 'Teléfono',
        ci: 'Cédula',
        repetir: 'Repetir contraseña',
        crear_cuenta: 'Registrarme',
        rec_titulo: 'Recuperar contraseña',
        nueva: 'Nueva contraseña',
        guardar_clave: 'Guardar contraseña',
        administracion: 'administracion',
        articulos_admin: 'Artículos',
        ver_biblioteca: 'Ver biblioteca',
        agregar: 'Agregar artículo',
        guardar: 'Guardar cambios',
        fecha_prestamo: 'Fecha del préstamo',
        fecha_devolucion: 'Fecha de devolución',
        renovar: 'Renovar préstamo',
        esperando: 'esperando autorización',
        devolver: 'Devolver',
        sin_prestamos: 'Todavía no tenés libros en préstamo.',
        info: 'Información'
    },
    en: {
        slogan: 'your virtual library',
        ir_catalogo: 'Go to catalog',
        ir_espacio: 'Go to my space',
        contacto: 'Contact',
        traducir: 'Translate to Spanish',
        ingresar: 'Sign in',
        inicio: 'Home',
        loguin: 'loguin',
        salir: 'Log out',
        panel: 'Dashboard',
        mi_espacio: 'my space',
        mis_libros: 'My books',
        catalogo: 'Catalog',
        catalogo_bajada: 'Books, magazines, audiobooks and bestsellers',
        buscar: 'Search by title, author or category',
        un_articulo: '1 item',
        articulos: 'items',
        vacio: 'No items match that search.',
        reservar: 'RESERVE',
        en_biblioteca: 'in your library',
        sin_stock: 'out of stock',
        hero: 'Your library, open at any hour.',
        hero_texto: 'Read, listen and discover books, magazines and audiobooks from any device. No lines and no waiting.',
        nosotros: 'Who we are',
        nosotros_texto: 'A library made for reading without barriers. We started to bring reading to every student and curious person. We curate an accessible, free catalog, open 24 hours on any device.',
        lectores: '+12k Active readers',
        acceso: '24/7 Open access',
        gratis: '100% Free',
        categorias: 'Categories',
        categorias_bajada: 'Our categories are books, magazines, audiobooks and bestsellers.',
        cat_libros: 'Books',
        cat_revistas: 'Magazines',
        cat_audio: 'Audiobooks',
        cat_best: 'Bestsellers',
        stat_libros: '1,240 Books',
        stat_revistas: '320 Magazines',
        stat_audio: '480 Audiobooks',
        footer_texto: 'A virtual library open to students and curious readers.',
        tu_email: 'Your email',
        suscribir: 'Subscribe',
        suscripto: 'Thanks. The newsletter does not store emails yet.',
        entrar: 'Enter',
        correo: 'Email',
        contrasena: 'Password',
        recuperar: 'Reset password',
        registrar: 'Register',
        volver_landing: 'Back to the landing page',
        frase: 'Your library, open at any hour.',
        reg_titulo: 'Create account',
        nombre: 'First name',
        apellido: 'Last name',
        telefono: 'Phone',
        ci: 'ID number',
        repetir: 'Repeat password',
        crear_cuenta: 'Sign up',
        rec_titulo: 'Reset password',
        nueva: 'New password',
        guardar_clave: 'Save password',
        administracion: 'administration',
        articulos_admin: 'Items',
        ver_biblioteca: 'View library',
        agregar: 'Add item',
        guardar: 'Save changes',
        fecha_prestamo: 'Loan date',
        fecha_devolucion: 'Return date',
        renovar: 'Renew loan',
        esperando: 'waiting for authorization',
        devolver: 'Return',
        sin_prestamos: 'You do not have any books on loan yet.',
        info: 'Details'
    }
};

function idiomaActual() {
    return localStorage.getItem('idioma') === 'en' ? 'en' : 'es';
}

function t(clave) {
    const idioma = TEXTOS[idiomaActual()] || TEXTOS.es;
    return idioma[clave] || TEXTOS.es[clave] || clave;
}

function aplicarIdioma() {
    document.documentElement.lang = idiomaActual() === 'en' ? 'en' : 'es';
    document.querySelectorAll('[data-i18n]').forEach((el) => {
        el.textContent = t(el.dataset.i18n);
    });
    document.querySelectorAll('[data-i18n-ph]').forEach((el) => {
        el.placeholder = t(el.dataset.i18nPh);
    });
    if (typeof alCambiarIdioma === 'function') {
        alCambiarIdioma();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    aplicarIdioma();
    const boton = document.getElementById('btn-idioma');
    if (!boton) return;
    boton.addEventListener('click', () => {
        localStorage.setItem('idioma', idiomaActual() === 'es' ? 'en' : 'es');
        aplicarIdioma();
    });
});
