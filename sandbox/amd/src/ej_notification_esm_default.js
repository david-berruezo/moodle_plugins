// ESM: importas el default export → mismo objeto que en AMD
import Notification from 'core/notification';

export const init = () => {
    // Accedes igual que en AMD
    Notification.addNotification({
        message: 'Hola desde ESM default',
        type: 'success'
    });

    Notification.alert('Título', 'Mensaje', 'OK');
};