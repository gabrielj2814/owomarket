
const dateUtils = {

    procesarFechaCompleto(fechaUsuario: Date) {
        const ahora = new Date();

        // Si la fecha no tiene hora específica, agregar hora actual
        if (fechaUsuario.getHours() === 0 &&
            fechaUsuario.getMinutes() === 0 &&
            fechaUsuario.getSeconds() === 0) {

            fechaUsuario.setHours(
            ahora.getHours(),
            ahora.getMinutes(),
            ahora.getSeconds(),
            ahora.getMilliseconds()
            );
        }

        // Convertir a UTC para BD
        const fechaUTC = fechaUsuario.toISOString();

        // Para mostrar al usuario
        const mostrarLocal = fechaUsuario.toLocaleString('es-ES', {
            dateStyle: 'medium',
            timeStyle: 'medium',
            hour12: false // Cambia a true si quieres AM/PM
        });

        return {
            paraBD: fechaUTC, // "2024-01-15T14:30:00.000Z"
            paraMostrar: mostrarLocal, // "15 ene 2024, 15:30:00"
            horaMilitar: `${fechaUsuario.getHours().toString().padStart(2, '0')}:${fechaUsuario.getMinutes().toString().padStart(2, '0')}`,
            hora12h: fechaUsuario.toLocaleTimeString('es-ES', { hour12: true })
        };
    }

}

export default dateUtils

