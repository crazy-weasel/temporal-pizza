import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['count', 'cartItems', 'coinsDisplay', 'checkoutBtn', 'form', 'emailInput', 'error'];

    cart = {};
    coins = 0;

    connect() {
        this.#render();
    }

    async submit(event) {
        event.preventDefault();
        this.errorTarget.textContent = '';
        this.checkoutBtnTarget.disabled = true;

        try {
            const items = Object.entries(this.cart).map(([pizzaId, { quantity }]) => ({ pizzaId: parseInt(pizzaId), quantity }));
            const response = await fetch(this.formTarget.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: this.emailInputTarget.value, coins: this.coins, items }),
            });

            if (!response.ok) {
                this.errorTarget.textContent = 'Something went wrong, please try again.';
                return;
            }

            const { redirectUrl } = await response.json();
            window.Turbo.visit(redirectUrl);
        } catch {
            this.errorTarget.textContent = 'Network error, please try again.';
        } finally {
            this.checkoutBtnTarget.disabled = false;
        }
    }

    add({ params: { id, name } }) {
        if (!this.cart[id]) {
            this.cart[id] = { name, quantity: 0 };
        }
        if (this.cart[id].quantity >= 10) return;
        this.cart[id].quantity++;
        this.coins = Math.floor(Math.random() * 10) + 1;

        this.#render();
    }

    remove({ params: { id } }) {
        if (!this.cart[id]) return;
        this.cart[id].quantity--;
        if (this.cart[id].quantity <= 0) delete this.cart[id];

        if (Object.keys(this.cart).length === 0) {
            this.coins = 0;
        }

        this.#render();
    }

    #coins() {
        return Array.from({ length: this.coins }, (_, i) =>
            `<span style="margin-left: ${i === 0 ? 0 : -10}px; z-index: ${this.coins - i}; position: relative; display: inline-block;">🪙</span>`
        ).join('');
    }

    #render() {
        const entries = Object.entries(this.cart);
        const totalItems = entries.reduce((sum, [, { quantity }]) => sum + quantity, 0);

        this.countTarget.textContent = totalItems;
        this.checkoutBtnTarget.disabled = totalItems === 0;

        if (this.coins > 0) {
            this.coinsDisplayTarget.innerHTML = this.#coins();
        } else {
            this.coinsDisplayTarget.innerHTML = '<span class="text-tp-muted">—</span>';
        }

        if (entries.length === 0) {
            this.cartItemsTarget.innerHTML = '<p class="text-tp-muted text-sm text-center mt-6">Your cart is empty</p>';
            return;
        }

        this.cartItemsTarget.innerHTML = entries.map(([id, { name, quantity }]) => `
            <div class="flex items-center justify-between py-2 border-b border-tp-border last:border-0">
                <span class="text-sm font-medium truncate flex-1">${name}</span>
                <div class="flex items-center gap-2 ml-2 shrink-0">
                    <button data-action="cart#remove"
                            data-cart-id-param="${id}"
                            class="w-6 h-6 rounded-full bg-tp-surface-raised hover:bg-tp-border text-sm font-bold flex items-center justify-center transition-colors">−</button>
                    <span class="text-sm font-bold w-4 text-center">${quantity}</span>
                    <button data-action="cart#add"
                            data-cart-id-param="${id}"
                            data-cart-name-param="${name}"
                            class="w-6 h-6 rounded-full bg-tp-surface-raised hover:bg-tp-border text-sm font-bold flex items-center justify-center transition-colors">+</button>
                </div>
            </div>
        `).join('');
    }
}
