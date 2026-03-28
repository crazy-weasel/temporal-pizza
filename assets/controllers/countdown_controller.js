import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #interval;

    connect() {
        let seconds = parseInt(this.element.textContent, 10);

        const format = (s) => {
            if (s <= 0) return '0s';
            const m = Math.floor(s / 60);
            const rem = s % 60;
            return m > 0 ? `${m}m ${rem}s` : `${rem}s`;
        };

        this.element.textContent = format(seconds);

        this.#interval = setInterval(() => {
            seconds--;
            this.element.textContent = format(seconds);
            if (seconds <= 0) clearInterval(this.#interval);
        }, 1000);
    }

    disconnect() {
        clearInterval(this.#interval);
    }
}
