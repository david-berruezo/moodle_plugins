// ESM: named imports con alias para evitar colisiones de nombres
// (por ejemplo, "alert" colisiona con window.alert)
import {
    addNotification,
    alert as moodleAlert,
    saveCancelPromise,
} from 'core/notification';

export const init = async() => {
    addNotification({message: 'Test', type: 'info'});

    // Ahora no hay confusión con window.alert
    moodleAlert('Título', 'Mensaje', 'OK');

    // saveCancelPromise con async/await
    try {
        await saveCancelPromise('Guardar', '¿Guardar cambios?', 'Guardar');
        window.console.log('Usuario guardó');
    } catch (e) {
        window.console.log('Usuario canceló');
    }
};