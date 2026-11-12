const Sesion = {
    guardar(token) {
        sessionStorage.setItem('token', token);
    },

    async cerrar() {
        await API.request('/auth/logout', 'POST');
        sessionStorage.removeItem('token');
        window.location.href = '/front/login.html';
    },

    destino(persona) {
        if (Number(persona.administrador) === 1) {
            return '/front/admin/index.html';
        }
        return '/front/usuario/index.html';
    }
};
