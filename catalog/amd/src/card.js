// =============================================================================
// CARD — renderizado de tarjetas de curso
// =============================================================================
import { buildStars } from './utils';

/**
 * @typedef {import('./data.js').Course} Course
 */

/**
 * Genera el HTML completo de una cv-card.
 * @param {Course} course
 * @returns {string}
 */
export function buildCardHTML(course) {
    const badge = course.badge
        ? `<div class="cv-card__badge">${course.badge}</div>`
        : '';

    return `
        <a href="#" class="cv-card" tabindex="0">
            <div class="cv-card__img" style="background:${course.color}22">
                <span class="cv-card__img-initials" style="color:${course.color}">
                    ${course.initials}
                </span>
            </div>
            <div class="cv-card__body">
                ${badge}
                <div class="cv-card__title">${course.title}</div>
                <div class="cv-card__instructor">${course.instructor}</div>
                <div class="cv-card__rating">
                    <span class="cv-card__score">${course.rating}</span>
                    <span class="cv-card__stars" aria-label="Rating: ${course.rating} de 5">
                        ${buildStars(course.rating)}
                    </span>
                    <span class="cv-card__count">(${course.reviews})</span>
                </div>
                <div class="cv-card__meta">
                    <div class="cv-card__price"><strong>${course.price}</strong></div>
                </div>
            </div>
        </a>`;
}

/**
 * Renderiza una lista de cursos dentro del track de un carousel.
 * @param {string}   trackId   id del elemento contenedor
 * @param {Course[]} courses
 */
export function populateCarousel(trackId, courses) {
    const track = document.getElementById(trackId);
    if (!track) {
        window.console.warn(`populateCarousel: track #${trackId} not found`);
        return;
    }

    const fragment = document.createDocumentFragment();

    courses.forEach((course) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'cv-carousel__item';
        wrapper.innerHTML = buildCardHTML(course);
        fragment.appendChild(wrapper);
    });

    track.appendChild(fragment);
}
