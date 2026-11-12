// El mensajero. El HTML no habla con PHP: toda la información pasa por acá.

const API = {
    urlBase: '/api',

    async request(endpoint, method = 'GET', data = null) {
        const opciones = {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            cache: 'no-store'
        };

        const token = sessionStorage.getItem('token');
        if (token) {
            opciones.headers.Authorization = 'Bearer ' + token;
        }

        if (data) {
            opciones.body = JSON.stringify(data);
        }

        try {
            const respuesta = await fetch(this.urlBase + endpoint, opciones);
            return await respuesta.json();
        } catch (error) {
            console.error('Error de conexión con la API:', error);
            return { status: 'error', message: 'No se pudo conectar con la API' };
        }
    },

    async subirImagen(archivo) {
        const cuerpo = new FormData();
        cuerpo.append('imagen', archivo);
        const headers = {};
        const token = sessionStorage.getItem('token');
        if (token) {
            headers.Authorization = 'Bearer ' + token;
        }

        try {
            const respuesta = await fetch(this.urlBase + '/articulos/imagen', {
                method: 'POST',
                headers: headers,
                body: cuerpo,
                cache: 'no-store'
            });
            return await respuesta.json();
        } catch (error) {
            console.error('Error al subir la imagen:', error);
            return { status: 'error', message: 'No se pudo subir la imagen' };
        }
    }
};
