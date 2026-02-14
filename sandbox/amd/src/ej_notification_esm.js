// ESM: importas SOLO las funciones que necesitas (named imports)
import {addNotification, alert, confirm} from 'core/notification';

export const init = () => {
    // Llamas directamente a la función, sin prefijo
    addNotification({
        message: 'Hola desde ESM named imports',
        type: 'success'
    });

    alert('Título', 'Mensaje', 'OK');

    confirm('Confirmar', '¿Seguro?', 'Sí', 'No',
        () => window.console.log('Sí'),
        () => window.console.log('No')
    );
};