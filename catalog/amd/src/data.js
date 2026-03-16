// =============================================================================
// DATA — mock de cursos
// En producción esto vendrá de la API de Moodle / catalog_manager.php
// =============================================================================

/** @type {Course[]} */
export const COURSES_TRENDING = [
    { title: 'React JS de cero a experto',
        instructor: 'Fernando Herrera',
        rating: 4.8, reviews: '3.241',
        price: 'Incluido', badge: 'Más vendido',
        color: '#61DAFB', initials: 'R'   },

];

/** @type {Course[]} */
export const COURSES_DATA_AI = [
    { title: 'ChatGPT y LLMs: Domina la IA generativa',
        instructor: 'Andrés Jiménez',
        rating: 4.9, reviews: '2.112', price: 'Incluido',
        badge: 'Nuevo',
        color: '#10A37F', initials: 'AI'  },
];
