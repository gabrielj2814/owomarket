import dayjs from "dayjs";

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
    },

    /**
     * Obtiene la primera y última fecha del mes actual
     * @returns Un objeto con la primera y última fecha del mes actual
     */

    getFirstAndLastDayOfCurrentMonth: (): {
        firstDay: dayjs.Dayjs;
        lastDay: dayjs.Dayjs;
    } => {
        const now = dayjs();

        // Primera fecha del mes (día 1)
        const firstDay = now.startOf('month');

        // Última fecha del mes (último día)
        const lastDay = now.endOf('month');

        return {
            firstDay,
            lastDay
        };
    },


    /**
     * Obtiene la primera y última fecha del mes actual en formato de cadena
     * @param format Formato opcional para las fechas (por defecto: 'YYYY-MM-DD')
     * @returns Un objeto con la primera y última fecha del mes actual en formato de cadena
     */
    getFirstAndLastDayOfCurrentMonthFormatted: (
        format: string = 'YYYY-MM-DD'
    ): {
        firstDay: string;
        lastDay: string;
    } =>  {
        const { firstDay, lastDay } = dateUtils.getFirstAndLastDayOfCurrentMonth();

        return {
            firstDay: firstDay.format(format),
            lastDay: lastDay.format(format)
        };
    },

    /**
     * Obtiene la primera y última fecha de un mes específico
     * @param date Fecha de referencia (dayjs, Date o string)
     * @returns Un objeto con la primera y última fecha del mes de la fecha proporcionada
     */
    getFirstAndLastDayOfMonth: (
        date: dayjs.Dayjs | Date | string
    ): {
        firstDay: dayjs.Dayjs;
        lastDay: dayjs.Dayjs;
    } => {
        const dayjsDate = dayjs(date);

        return {
            firstDay: dayjsDate.startOf('month'),
            lastDay: dayjsDate.endOf('month')
        };
    }


}

export default dateUtils

