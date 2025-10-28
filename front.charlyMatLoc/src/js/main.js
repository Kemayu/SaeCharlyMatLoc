import { templateManager } from './templates.js';

const API_BASE_URL = 'http://localhost:48211';

// ============================================
// GESTION DE L'AUTHENTIFICATION
// ============================================

class AuthManager {
    constructor() {
        this.token = localStorage.getItem('access_token');
        this.refreshToken = localStorage.getItem('refresh_token');
        this.user = JSON.parse(localStorage.getItem('user') || 'null');
    }

    isAuthenticated() {
        return !!this.token && !!this.user;
    }

    getUser() {
        return this.user;
    }

    getToken() {
        return this.token;
    }

    storeSession(authPayload) {
        const auth = authPayload?.auth ? authPayload.auth : authPayload;
        if (!auth || !auth.access_token || !auth.profile) {
            throw new Error('Réponse d\'authentification invalide.');
        }

        this.token = auth.access_token;
        this.refreshToken = auth.refresh_token ?? null;
        this.user = auth.profile;

        localStorage.setItem('access_token', this.token);
        if (this.refreshToken) {
            localStorage.setItem('refresh_token', this.refreshToken);
        } else {
            localStorage.removeItem('refresh_token');
        }
        localStorage.setItem('user', JSON.stringify(this.user));
    }

    async login(email, password) {
        try {
            const response = await fetch(`${API_BASE_URL}/auth/signin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Erreur de connexion');
            }

            const data = await response.json();
            this.storeSession(data);

            return { success: true, user: this.user };
        } catch (error) {
            console.error('Erreur lors de la connexion:', error);
            return { success: false, error: error.message };
        }
    }

    async register(email, password, passwordConfirmation) {
        try {
            const response = await fetch(`${API_BASE_URL}/auth/signup`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email,
                    password,
                    password_confirmation: passwordConfirmation
                })
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.error || error.message || 'Inscription impossible');
            }

            const data = await response.json();
            this.storeSession(data.auth ?? data);

            return { success: true, user: this.user };
        } catch (error) {
            console.error('Erreur lors de l\'inscription:', error);
            return { success: false, error: error.message };
        }
    }

    logout() {
        this.token = null;
        this.refreshToken = null;
        this.user = null;
        
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('user');
    }

    getAuthHeaders() {
        if (!this.token) {
            return {
                'Content-Type': 'application/json'
            };
        }
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.token}`
        };
    }
}

// Instance globale de l'authentification
const authManager = new AuthManager();

class App {
    constructor() {
        this.authManager = authManager;
        this.tools = [];
        this.filteredTools = []; // Outils actuellement affichés (après filtrage)
        this.cart = { items: [], items_count: 0, total: 0 };
        this.currentPage = 1;
        this.itemsPerPage = 12; // Nombre d'outils par page
        this.selectedCategoryId = 'all'; // Pour mémoriser le filtre
        this.filterStartDate = '';
        this.filterEndDate = '';
        this.hasAvailabilityFilter = false;
        this.reservations = [];
        this.feedback = null;
        this.feedbackTimeout = null;
        // Mapping des catégories pour le filtre, correspondant à la BDD
        this.categories = [
            { id: 1, name: 'Petit outillage' },
            { id: 2, name: 'Menuiserie' },
            { id: 3, name: 'Peinture' },
            { id: 4, name: 'Nettoyage' },
            { id: 5, name: 'Jardinage' }
        ];
    }

    async init() {
        this.setupNavigation();
        await templateManager.initializeTemplates();
        await this.loadTools();
        if (this.authManager.isAuthenticated()) {
            await this.loadCart();
            await this.loadReservations();
        }
        this.updateNavigationUI(); // Mettre à jour l'interface selon l'état de connexion
        // Démarrer directement sur le catalogue, car 'home' redirige vers 'catalog'
        await this.showPage('catalog');
    }

    updateNavigationUI() {
        const navLinks = document.querySelector('.nav-links');
        const isAuthenticated = this.authManager.isAuthenticated();
        const user = this.authManager.getUser();

        // Trouver ou créer la section d'authentification
        let authSection = navLinks.querySelector('.auth-section');
        if (!authSection) {
            authSection = document.createElement('li');
            authSection.className = 'auth-section';
            navLinks.appendChild(authSection);
        }

        if (isAuthenticated) {
            authSection.innerHTML = `
                <span class="user-info" style="color: #333; margin-right: 10px;">👤 ${user.email}</span>
                <a href="#" id="logout-btn" class="nav-link btn-logout">Déconnexion</a>
            `;
            // Masquer les liens de connexion/inscription
            navLinks.querySelectorAll('[data-page="login"], [data-page="register"]').forEach(link => {
                link.parentElement.style.display = 'none';
            });
        } else {
            authSection.innerHTML = '';
            // Afficher les liens de connexion/inscription
            navLinks.querySelectorAll('[data-page="login"], [data-page="register"]').forEach(link => {
                link.parentElement.style.display = '';
            });
        }

        const reservationsLink = navLinks.querySelector('[data-page="reservations"]');
        if (reservationsLink) {
            reservationsLink.parentElement.style.display = isAuthenticated ? '' : 'none';
        }

        this.refreshCartIndicator();
    }

    setFeedback(message, type = 'info', options = {}) {
        if (this.feedbackTimeout) {
            clearTimeout(this.feedbackTimeout);
            this.feedbackTimeout = null;
        }

        if (!message) {
            this.clearFeedback();
            return;
        }

        const normalizedType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
        const shouldAutoHide = options.autoHide !== undefined ? options.autoHide : normalizedType !== 'error';
        const duration = options.duration ?? 6000;

        this.feedback = {
            message,
            type: normalizedType
        };

        this.renderFeedback();

        if (shouldAutoHide) {
            this.feedbackTimeout = setTimeout(() => {
                this.clearFeedback();
            }, duration);
        }
    }

    clearFeedback() {
        if (this.feedbackTimeout) {
            clearTimeout(this.feedbackTimeout);
            this.feedbackTimeout = null;
        }

        this.feedback = null;
        this.renderFeedback();
    }

    renderFeedback() {
        if (typeof document === 'undefined') {
            return;
        }

        const container = document.getElementById('global-feedback');
        if (!container) {
            return;
        }

        container.classList.remove('is-visible');
        container.innerHTML = '';

        if (!this.feedback || !this.feedback.message) {
            return;
        }

        const { message, type } = this.feedback;
        const iconMap = {
            success: '✔',
            error: '✖',
            warning: '!',
            info: 'ℹ'
        };

        const wrapper = document.createElement('div');
        wrapper.className = `feedback-message feedback-${type}`;

        const icon = document.createElement('span');
        icon.className = 'feedback-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = iconMap[type] ?? iconMap.info;
        wrapper.appendChild(icon);

        const text = document.createElement('span');
        text.className = 'feedback-text';
        text.textContent = message;
        wrapper.appendChild(text);

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'feedback-close';
        closeBtn.setAttribute('aria-label', 'Fermer la notification');
        closeBtn.innerHTML = '&times;';
        wrapper.appendChild(closeBtn);

        container.appendChild(wrapper);
        container.classList.add('is-visible');
    }

    refreshCartIndicator() {
        const navLinks = document.querySelector('.nav-links');
        if (!navLinks) {
            return;
        }

        const cartLink = navLinks.querySelector('[data-page="card"]');
        if (!cartLink) {
            return;
        }

        const count = this.cart?.items_count ?? 0;
        cartLink.textContent = count > 0 ? `Panier (${count})` : 'Panier';
    }

    updateCartState(cartPayload) {
        const items = Array.isArray(cartPayload?.items) ? cartPayload.items : [];
        const total = Number(cartPayload?.total ?? 0);
        const itemsCount = typeof cartPayload?.items_count === 'number'
            ? cartPayload.items_count
            : items.length;

        this.cart = {
            items,
            total,
            items_count: itemsCount
        };

        this.refreshCartIndicator();
        return this.cart;
    }

    async loadTools() {
        try {
            const params = new URLSearchParams();
            if (this.selectedCategoryId !== 'all') {
                params.append('category_id', String(this.selectedCategoryId));
            }

            if (this.filterStartDate) {
                params.append('start_date', this.filterStartDate);
                if (this.filterEndDate) {
                    params.append('end_date', this.filterEndDate);
                }
            }

            const queryString = params.toString();
            const url = queryString ? `${API_BASE_URL}/tools?${queryString}` : `${API_BASE_URL}/tools`;

            const response = await fetch(url, {
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`Erreur HTTP lors du chargement des outils: ${response.status}`);
            }
            const data = await response.json();
            // On extrait le tableau 'tools' de la réponse de l'API
            this.tools = data.tools || [];
            this.filteredTools = this.tools;
            this.hasAvailabilityFilter = Boolean(this.filterStartDate);
            if (this.feedback?.message === 'Impossible de charger le catalogue pour le moment. Veuillez réessayer plus tard.') {
                this.clearFeedback();
            }
            return true;
        } catch (error) {
            console.error('Erreur lors du chargement des outils:', error);
            this.tools = []; // Garde une valeur sûre en cas d'erreur
            this.filteredTools = [];
            this.hasAvailabilityFilter = false;
            const message = 'Impossible de charger le catalogue pour le moment. Veuillez réessayer plus tard.';
            if (!this.feedback || this.feedback.message !== message) {
                this.setFeedback(message, 'error', { autoHide: false });
            }
            return false;
        }
    }

    async loadCart() {
        if (!this.authManager.isAuthenticated()) {
            this.updateCartState({ items: [], total: 0, items_count: 0 });
            return this.cart;
        }

        try {
            const userId = this.authManager.getUser().id;
            const response = await fetch(`${API_BASE_URL}/users/${userId}/cart`, {
                headers: this.authManager.getAuthHeaders()
            });
            if (!response.ok) {
                throw new Error(`Erreur HTTP lors du chargement du panier: ${response.status}`);
            }
            const data = await response.json();
            const cart = data.cart ?? { items: [], total: 0, items_count: 0 };
            if (this.feedback?.message === 'Impossible de charger votre panier pour le moment.') {
                this.clearFeedback();
            }
            return this.updateCartState(cart);
        } catch (error) {
            console.error('Erreur lors du chargement du panier:', error);
            this.updateCartState({ items: [], total: 0, items_count: 0 });
            const message = 'Impossible de charger votre panier pour le moment.';
            if (!this.feedback || this.feedback.message !== message) {
                this.setFeedback(message, 'error', { autoHide: false });
            }
            return this.cart;
        }
    }

    async loadReservations() {
        if (!this.authManager.isAuthenticated()) {
            this.reservations = [];
            return this.reservations;
        }

        try {
            const userId = this.authManager.getUser().id;
            const response = await fetch(`${API_BASE_URL}/users/${userId}/reservations`, {
                headers: this.authManager.getAuthHeaders()
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP lors du chargement des réservations: ${response.status}`);
            }

            const data = await response.json();
            this.reservations = data.reservations ?? [];
            if (this.feedback?.message === 'Impossible de récupérer vos réservations pour le moment.') {
                this.clearFeedback();
            }
            return this.reservations;
        } catch (error) {
            console.error('Erreur lors du chargement des réservations:', error);
            this.reservations = [];
            const message = 'Impossible de récupérer vos réservations pour le moment.';
            if (!this.feedback || this.feedback.message !== message) {
                this.setFeedback(message, 'error', { autoHide: false });
            }
            return this.reservations;
        }
    }
    
    setupNavigation() {
        // Délégation d'événements pour gérer les liens dynamiques
        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('.feedback-close');
            if (closeBtn) {
                e.preventDefault();
                this.clearFeedback();
                return;
            }

            const pageLink = e.target.closest('[data-page]');
            if (pageLink) {
                e.preventDefault();
                const pageName = pageLink.getAttribute('data-page');
                const toolId = pageLink.dataset.id; // Récupère l'ID de l'outil si présent
                this.showPage(pageName, toolId);
            }

            // Gérer les clics sur les liens de pagination
            const pageNav = e.target.closest('[data-page-nav]');
            if (pageNav) {
                e.preventDefault();
                const newPage = parseInt(pageNav.dataset.pageNav, 10);
                this.currentPage = newPage;
                window.scrollTo(0, 0); // Scroll en haut de la nouvelle page
                this.showPage('catalog'); // Re-render le catalogue à la nouvelle page
            }

            const removeBtn = e.target.closest('[data-remove-item]');
            if (removeBtn) {
                e.preventDefault();
                const itemId = parseInt(removeBtn.dataset.removeItem, 10);
                if (!Number.isNaN(itemId)) {
                    this.handleRemoveFromCart(itemId);
                }
            }

            const checkoutBtn = e.target.closest('[data-checkout]');
            if (checkoutBtn) {
                e.preventDefault();
                this.handleCheckout();
            }

            if (e.target.id === 'apply-availability-filter') {
                e.preventDefault();
                this.applyAvailabilityFilter().catch(error => {
                    console.error('Erreur filtre disponibilité:', error);
                });
            }

            if (e.target.id === 'clear-availability-filter') {
                e.preventDefault();
                this.clearAvailabilityFilter().catch(error => {
                    console.error('Erreur réinitialisation filtre:', error);
                });
            }

            // Gérer le bouton de déconnexion
            if (e.target.closest('#logout-btn')) {
                e.preventDefault();
                this.handleLogout();
            }
        });

        // Délégation pour le filtre de catégorie
        document.addEventListener('change', (e) => {
            if (e.target.id === 'category-filter') {
                this.filterToolsByCategory(e.target.value).catch(error => {
                    console.error('Erreur filtre catégorie:', error);
                });
            } else if (e.target.classList.contains('cart-quantity-input')) {
                const itemId = parseInt(e.target.dataset.itemId, 10);
                const newQuantity = parseInt(e.target.value, 10);

                if (Number.isNaN(itemId) || Number.isNaN(newQuantity)) {
                    return;
                }

                this.handleCartQuantityChange(itemId, newQuantity, e.target);
            } else if (e.target.id === 'availability-start') {
                this.filterStartDate = e.target.value;
            } else if (e.target.id === 'availability-end') {
                this.filterEndDate = e.target.value;
            }
        });

        document.addEventListener('focusin', (e) => {
            if (e.target.classList.contains('cart-quantity-input')) {
                e.target.dataset.previousValue = e.target.value;
            }
        });

        // Délégation pour le formulaire de connexion
        document.addEventListener('submit', async (e) => {
            if (e.target.classList.contains('login-form')) {
                e.preventDefault();
                await this.handleLogin(e.target);
            } else if (e.target.classList.contains('register-form')) {
                e.preventDefault();
                await this.handleRegister(e.target);
            } else if (e.target.classList.contains('add-to-cart-form')) {
                e.preventDefault();
                await this.handleAddToCart(e.target);
            }
        });
    }

    async handleLogin(form) {
        const email = form.querySelector('input[type="email"]').value;
        const password = form.querySelector('input[type="password"]').value;

        const result = await this.authManager.login(email, password);
        
        if (result.success) {
            this.setFeedback(`Bienvenue ${result.user.email} !`, 'success');
            this.updateNavigationUI(); // Mettre à jour le menu
            await this.loadCart();
            await this.loadReservations();
            await this.showPage('catalog');
        } else {
            this.setFeedback(`Erreur de connexion : ${result.error}`, 'error', { autoHide: false });
        }
    }

    async handleRegister(form) {
        const emailInput = form.querySelector('input[name="email"]') || form.querySelector('input[type="email"]');
        const passwordInput = form.querySelector('input[name="password"]') || form.querySelector('input[type="password"]');
        const confirmInput = form.querySelector('input[name="password_confirmation"]');

        const email = emailInput ? emailInput.value.trim() : '';
        const password = passwordInput ? passwordInput.value : '';
        const confirmation = confirmInput ? confirmInput.value : '';

        if (!email || !password) {
            this.setFeedback('Merci de renseigner un email et un mot de passe.', 'error', { autoHide: false });
            return;
        }

        if (password.length < 8) {
            this.setFeedback('Le mot de passe doit contenir au moins 8 caractères.', 'error', { autoHide: false });
            return;
        }

        if (confirmation && password !== confirmation) {
            this.setFeedback('Les mots de passe ne correspondent pas.', 'error', { autoHide: false });
            return;
        }

        const result = await this.authManager.register(email, password, confirmation);

        if (result.success) {
            form.reset();
            this.setFeedback('Votre compte a été créé avec succès. Bienvenue !', 'success');
            await this.loadCart();
            await this.loadReservations();
            this.updateNavigationUI();
            await this.showPage('catalog');
        } else {
            this.setFeedback(`Erreur lors de l'inscription : ${result.error}`, 'error', { autoHide: false });
        }
    }

    handleLogout() {
        this.authManager.logout();
        this.updateCartState({ items: [], total: 0, items_count: 0 });
        this.reservations = [];
        this.updateNavigationUI(); // Mettre à jour le menu
        this.setFeedback('Vous êtes déconnecté.', 'info');
        this.showPage('catalog');
    }

    async handleAddToCart(form) {
        if (!this.authManager.isAuthenticated()) {
            this.setFeedback('Connectez-vous pour ajouter des articles à votre panier.', 'warning', { autoHide: false });
            await this.showPage('login');
            return;
        }

        const toolId = parseInt(form.dataset.toolId, 10);
        const formData = new FormData(form);
        const startDate = formData.get('start_date');
        const endDate = formData.get('end_date') || startDate;
        const quantity = parseInt(formData.get('quantity'), 10) || 1;

        if (!toolId || !startDate) {
            this.setFeedback('Merci de sélectionner une date de début valide.', 'error', { autoHide: false });
            return;
        }

        if (!Array.isArray(this.cart?.items)) {
            await this.loadCart();
        }

        const duplicateItem = (this.cart.items ?? []).find(item => {
            return item.tool?.id === toolId &&
                item.start_date === startDate &&
                item.end_date === endDate;
        });

        if (duplicateItem) {
            this.setFeedback(
                'Cet outil est déjà présent dans votre panier pour ces dates. Ajustez la quantité directement depuis le panier.',
                'warning',
                { autoHide: false }
            );
            await this.showPage('card', null, { skipLoad: true });
            return;
        }

        try {
            const userId = this.authManager.getUser().id;
            const payload = {
                tool_id: toolId,
                start_date: startDate,
                end_date: endDate,
                quantity
            };

            const response = await fetch(`${API_BASE_URL}/users/${userId}/cart/items`, {
                method: 'POST',
                headers: this.authManager.getAuthHeaders(),
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Impossible d\'ajouter cet article au panier.');
            }

            const result = await response.json();
            this.updateCartState(result.cart);
            this.setFeedback('Article ajouté au panier avec succès.', 'success');
            await this.refreshToolAvailability(toolId);
            await this.showPage('card', null, { skipLoad: true });
        } catch (error) {
            console.error('Erreur lors de l\'ajout au panier:', error);
            const isStockIssue = this.isStockError(error.message);
            const message = isStockIssue
                ? this.getFriendlyStockError(error.message)
                : `Erreur lors de l'ajout au panier : ${error.message}`;
            this.setFeedback(message, 'error', { autoHide: false });

            if (isStockIssue) {
                await this.refreshToolAvailability(toolId);
            }
        }
    }

    async handleRemoveFromCart(itemId) {
        if (!this.authManager.isAuthenticated()) {
            this.setFeedback('Veuillez vous connecter pour gérer votre panier.', 'warning', { autoHide: false });
            await this.showPage('login');
            return;
        }

        if (!confirm('Voulez-vous vraiment supprimer cet article ?')) {
            return;
        }

        try {
            const userId = this.authManager.getUser().id;
            const response = await fetch(`${API_BASE_URL}/users/${userId}/cart/items/${itemId}`, {
                method: 'DELETE',
                headers: this.authManager.getAuthHeaders()
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Impossible de supprimer cet article.');
            }

            const result = await response.json();
            this.updateCartState(result.cart);
            this.setFeedback('Article supprimé du panier.', 'success');
            await this.showPage('card', null, { skipLoad: true });
        } catch (error) {
            console.error('Erreur lors de la suppression du panier:', error);
            this.setFeedback(`Erreur lors de la suppression : ${error.message}`, 'error', { autoHide: false });
        }
    }

    async handleCartQuantityChange(itemId, newQuantity, inputElement) {
        if (!this.authManager.isAuthenticated()) {
            this.setFeedback('Veuillez vous connecter pour modifier votre panier.', 'warning', { autoHide: false });
            await this.showPage('login');
            return;
        }

        if (newQuantity < 1) {
            inputElement.value = inputElement.dataset.previousValue || 1;
            this.setFeedback('La quantité minimale est 1.', 'error', { autoHide: false });
            return;
        }

        const previousValue = parseInt(inputElement.dataset.previousValue, 10) || 1;

        if (newQuantity === previousValue) {
            return;
        }

        try {
            inputElement.disabled = true;

            const userId = this.authManager.getUser().id;
            const response = await fetch(`${API_BASE_URL}/users/${userId}/cart/items/${itemId}`, {
                method: 'PATCH',
                headers: this.authManager.getAuthHeaders(),
                body: JSON.stringify({ quantity: newQuantity })
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Impossible de mettre à jour la quantité.');
            }

            const result = await response.json();
            this.updateCartState(result.cart);
            this.setFeedback(`Quantité mise à jour : ${newQuantity}.`, 'success');
            await this.showPage('card', null, { skipLoad: true });
        } catch (error) {
            console.error('Erreur lors de la mise à jour de la quantité:', error);
            const isStockIssue = this.isStockError(error.message);
            const message = isStockIssue
                ? `${this.getFriendlyStockError(error.message)} La quantité a été réinitialisée.`
                : `Erreur lors de la mise à jour de la quantité : ${error.message}`;
            this.setFeedback(message, 'error', { autoHide: false });
            inputElement.value = previousValue;

            if (isStockIssue) {
                const cartItem = (this.cart.items ?? []).find(item => item.id === itemId);
                if (cartItem?.tool?.id) {
                    await this.refreshToolAvailability(cartItem.tool.id);
                }
            }
        } finally {
            inputElement.disabled = false;
            inputElement.dataset.previousValue = inputElement.value;
        }
    }

    async handleCheckout() {
        if (!this.authManager.isAuthenticated()) {
            this.setFeedback('Veuillez vous connecter pour valider votre panier.', 'warning', { autoHide: false });
            await this.showPage('login');
            return;
        }

        if (!this.cart || this.cart.items_count === 0) {
            this.setFeedback('Votre panier est vide.', 'warning', { autoHide: false });
            return;
        }

        if (!confirm('Confirmez-vous la validation de votre panier ?')) {
            return;
        }

        try {
            const userId = this.authManager.getUser().id;
            const reservationResponse = await fetch(`${API_BASE_URL}/users/${userId}/reservations`, {
                method: 'POST',
                headers: this.authManager.getAuthHeaders()
            });

            if (!reservationResponse.ok) {
                const errorData = await reservationResponse.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Impossible de créer la réservation.');
            }

            const reservationResult = await reservationResponse.json();
            const reservation = reservationResult.reservation;

            if (!reservation || !reservation.id) {
                throw new Error('La réservation a été créée mais aucune donnée détaillée n’a été retournée.');
            }

            let paymentReference = null;
            try {
                const paymentPayload = {
                    amount: reservation.total_amount,
                    payment_method: 'card',
                    card_holder: this.authManager.getUser().email
                };

                const paymentResponse = await fetch(`${API_BASE_URL}/users/${userId}/reservations/${reservation.id}/payments`, {
                    method: 'POST',
                    headers: this.authManager.getAuthHeaders(),
                    body: JSON.stringify(paymentPayload)
                });

                if (!paymentResponse.ok) {
                    const paymentError = await paymentResponse.json().catch(() => ({}));
                    throw new Error(paymentError.error || paymentError.message || 'Paiement refusé.');
                }

                const paymentResult = await paymentResponse.json();
                paymentReference = paymentResult.payment?.provider_reference || null;
            } catch (paymentError) {
                console.error('Erreur lors du paiement simulé:', paymentError);
                this.updateCartState({ items: [], total: 0, items_count: 0 });
                this.setFeedback(`Réservation enregistrée, mais le paiement a échoué : ${paymentError.message}`, 'warning', { autoHide: false });
                await this.showPage('catalog');
                return;
            }

            this.updateCartState({ items: [], total: 0, items_count: 0 });
            const confirmationMessage = paymentReference
                ? `Paiement simulé réussi ! Référence: ${paymentReference}`
                : 'Paiement simulé réussi !';
            this.setFeedback(`Commande validée. ${confirmationMessage}`, 'success');
            await this.loadReservations();
            await this.showPage('catalog');
        } catch (error) {
            console.error('Erreur lors de la validation du panier:', error);
            const isStockIssue = this.isStockError(error.message);
            const message = isStockIssue
                ? `${this.getFriendlyStockError(error.message)} Impossible de finaliser la commande.`
                : `Erreur lors de la validation : ${error.message}`;
            this.setFeedback(message, 'error', { autoHide: false });

            if (isStockIssue) {
                await this.loadCart();
                await this.showPage('card');
            }
        }
    }

    formatDisplayDate(dateString) {
        if (!dateString || typeof dateString !== 'string') {
            return dateString ?? '';
        }

        const parts = dateString.split('-');
        if (parts.length === 3) {
            const [year, month, day] = parts;
            return `${day.padStart(2, '0')}/${month.padStart(2, '0')}/${year}`;
        }

        return dateString;
    }

    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    getDefaultDate(offsetDays = 0) {
        const date = new Date();
        date.setDate(date.getDate() + offsetDays);
        return this.formatDate(date);
    }

    getReservationStatusLabel(status) {
        if (!status) {
            return 'Statut inconnu';
        }

        const labels = {
            pending: 'En attente',
            confirmed: 'Confirmée',
            cancelled: 'Annulée',
            returned: 'Terminée'
        };

        const normalized = String(status).toLowerCase();
        return labels[normalized] ?? status;
    }

    getReservationStatusIcon(status) {
        if (!status) {
            return 'ℹ';
        }

        const icons = {
            pending: '⏳',
            confirmed: '✅',
            cancelled: '✖',
            returned: '🔁'
        };

        const normalized = String(status).toLowerCase();
        return icons[normalized] ?? 'ℹ';
    }

    isStockError(message) {
        if (!message) {
            return false;
        }
        return String(message).toLowerCase().includes('insufficient stock');
    }

    getFriendlyStockError(message) {
        if (!message) {
            return 'Stock insuffisant pour la période sélectionnée.';
        }

        const quantityMatch = /requested quantity\s*\((\d+)\)/i.exec(message);
        const periodMatch = /from\s([0-9-]+)\s+to\s+([0-9-]+)/i.exec(message);

        const quantityPart = quantityMatch ? ` pour ${quantityMatch[1]} article(s)` : '';

        let periodPart = '';
        if (periodMatch) {
            const start = this.formatDisplayDate(periodMatch[1]);
            const end = this.formatDisplayDate(periodMatch[2]);

            if (start && end) {
                periodPart = start === end
                    ? ` le ${start}`
                    : ` du ${start} au ${end}`;
            }
        }

        return `Stock insuffisant${quantityPart}${periodPart}. Réduisez la quantité ou choisissez d'autres dates.`;
    }

    updateCachedTool(tool) {
        if (!tool || !tool.tool_id) {
            return;
        }

        const replaceTool = (collection) => {
            if (!Array.isArray(collection)) {
                return;
            }
            const index = collection.findIndex(item => item.tool_id === tool.tool_id);
            if (index !== -1) {
                collection[index] = {
                    ...collection[index],
                    ...tool
                };
            }
        };

        replaceTool(this.tools);
        replaceTool(this.filteredTools);
    }

    async refreshToolAvailability(toolId) {
        if (!toolId) {
            return null;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/tools/${toolId}`, {
                credentials: 'include'
            });

            if (!response.ok) {
                return null;
            }

            const data = await response.json();
            const tool = data.tool;
            if (!tool) {
                return null;
            }

            this.updateCachedTool(tool);

            if (Array.isArray(this.cart?.items)) {
                const updatedCartItems = this.cart.items.map(item => {
                    if (item.tool?.id === tool.tool_id) {
                        return {
                            ...item,
                            tool: {
                                ...item.tool,
                                stock: tool.stock
                            }
                        };
                    }
                    return item;
                });

                this.cart = {
                    ...this.cart,
                    items: updatedCartItems
                };
            }

            if (typeof document !== 'undefined') {
                const stockSpan = document.querySelector('[data-tool-stock]');
                if (stockSpan) {
                    stockSpan.textContent = tool.stock;
                }

                const quantityInput = document.querySelector('[data-stock-input]');
                if (quantityInput) {
                    quantityInput.setAttribute('max', String(tool.stock));
                    const currentValue = parseInt(quantityInput.value, 10);
                    if (!Number.isNaN(currentValue) && currentValue > tool.stock) {
                        quantityInput.value = tool.stock > 0 ? tool.stock : 1;
                    }
                }
            }

            return tool;
        } catch (error) {
            console.error('Erreur lors de la mise à jour du stock:', error);
            return null;
        }
    }

    async showPage(pageName, toolId = null, options = {}) {
        // Déterminer le nom de page effectif pour le rendu
        let effectivePageName = pageName;
        if (pageName === 'home') {
            effectivePageName = 'catalog'; // La page 'home' affiche en fait le catalogue
        }

        // Mettre à jour la classe 'active' pour les liens de navigation principaux
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-page') === effectivePageName);
        });

        // Préparer les données selon la page
        let data = {};
        switch (effectivePageName) {
            case 'catalog':
                const totalItems = this.filteredTools.length;
                const totalPages = Math.ceil(totalItems / this.itemsPerPage);
                const startIndex = (this.currentPage - 1) * this.itemsPerPage;
                const paginatedTools = this.filteredTools.slice(startIndex, startIndex + this.itemsPerPage);

                const pages = [];
                if (totalPages > 1) {
                    for (let i = 1; i <= totalPages; i++) {
                        pages.push({ number: i, isCurrent: i === this.currentPage });
                    }
                }

                // Ajoute l'état de sélection aux catégories pour le template
                const categoriesWithSelection = this.categories.map(category => ({
                    ...category,
                    isSelected: category.id == this.selectedCategoryId
                }));

                data = { 
                    tools: paginatedTools,
                    categories: categoriesWithSelection,
                    pagination: totalPages > 1 ? {
                        totalPages: totalPages,
                        currentPage: this.currentPage,
                        pages: pages,
                        hasPrev: this.currentPage > 1,
                        prevPage: this.currentPage - 1,
                        hasNext: this.currentPage < totalPages,
                        nextPage: this.currentPage + 1
                    } : null,
                    isAllCategoriesSelected: this.selectedCategoryId === 'all',
                    selectedStartDate: this.filterStartDate,
                    selectedEndDate: this.filterEndDate,
                    hasAvailabilityFilter: this.hasAvailabilityFilter
                };
                break;
            case 'tool-detail':
                if (!toolId) {
                    console.error("Aucun ID d'outil fourni pour la page de détail.");
                    // Si pas d'ID, rediriger vers le catalogue pour éviter une boucle d'erreur ou une page blanche.
                    await this.showPage('catalog');
                    return;
                }
                try {
                    const response = await fetch(`${API_BASE_URL}/tools/${toolId}`, {
                        credentials: 'include'
                    });
                    if (!response.ok) throw new Error(`Erreur HTTP: ${response.status}`);
                    // La réponse de l'API est de la forme { "type": "resource", "tool": { ... } }
                    // On extrait directement l'objet de l'outil pour le passer au template.
                    const responseData = await response.json();
                    data = {
                        tool: responseData.tool,
                        isAuthenticated: this.authManager.isAuthenticated(),
                        defaultStartDate: this.getDefaultDate(0),
                        defaultEndDate: this.getDefaultDate(1),
                        user: this.authManager.getUser()
                    };
                } catch (error) {
                    console.error(`Erreur lors du chargement du détail de l'outil ${toolId}:`, error);
                    this.setFeedback('Impossible de charger cet outil pour le moment.', 'error', { autoHide: false });
                    data = { error: "L'outil n'a pas pu être chargé." };
                }
                break;
            case 'card':
                if (!this.authManager.isAuthenticated()) {
                    this.setFeedback('Vous devez être connecté pour voir votre panier.', 'warning', { autoHide: false });
                    await this.showPage('login');
                    return;
                }
                let cartState;
                if (options.skipLoad) {
                    if (options.cart) {
                        cartState = this.updateCartState(options.cart);
                    } else {
                        cartState = this.cart;
                    }
                } else {
                    cartState = await this.loadCart();
                }

                const decoratedCart = {
                    ...cartState,
                    items: (cartState.items ?? []).map(item => {
                        const startDisplay = this.formatDisplayDate(item.start_date);
                        const endDisplay = this.formatDisplayDate(item.end_date);
                        let periodLabel = startDisplay;
                        if (startDisplay && endDisplay) {
                            periodLabel = startDisplay === endDisplay
                                ? `Le ${startDisplay}`
                                : `${startDisplay} → ${endDisplay}`;
                        }

                        return {
                            ...item,
                            display_start_date: startDisplay,
                            display_end_date: endDisplay,
                            display_period: periodLabel
                        };
                    })
                };

                data = {
                    cart: decoratedCart,
                    user: this.authManager.getUser()
                };
                break;
            case 'login':
                if (this.authManager.isAuthenticated()) {
                    // Si déjà connecté, rediriger vers le catalogue
                    await this.showPage('catalog');
                    return;
                }
                data = {}; 
                break;
            case 'register':
                if (this.authManager.isAuthenticated()) {
                    await this.showPage('catalog');
                    return;
                }
                data = {};
                break;
            case 'reservations':
                if (!this.authManager.isAuthenticated()) {
                    this.setFeedback('Vous devez être connecté pour consulter vos réservations.', 'warning', { autoHide: false });
                    await this.showPage('login');
                    return;
                }
                const reservations = await this.loadReservations();
                const decoratedReservations = reservations.map(reservation => {
                    const items = (reservation.items ?? []).map(item => {
                        const startDisplay = this.formatDisplayDate(item.start_date);
                        const endDisplay = this.formatDisplayDate(item.end_date);
                        let periodLabel = startDisplay;
                        if (startDisplay && endDisplay) {
                            periodLabel = startDisplay === endDisplay
                                ? `Le ${startDisplay}`
                                : `${startDisplay} → ${endDisplay}`;
                        }

                        return {
                            ...item,
                            display_period: periodLabel
                        };
                    });

                    const totalQuantity = items.reduce((sum, item) => sum + (item.quantity ?? 0), 0);

                    return {
                        ...reservation,
                        items,
                        status_label: this.getReservationStatusLabel(reservation.status),
                        status_icon: this.getReservationStatusIcon(reservation.status),
                        formatted_reservation_date: this.formatDisplayDate(reservation.reservation_date),
                        total_quantity: totalQuantity
                    };
                });

                data = {
                    user: this.authManager.getUser(),
                    reservations: decoratedReservations,
                    hasReservations: decoratedReservations.length > 0
                };
                break;
            default: // Gérer les pages inconnues ou non gérées
                console.error(`Page inconnue ou non gérée: ${effectivePageName}`);
                effectivePageName = 'catalog'; // Revenir au catalogue par défaut
                data = { tools: this.tools };
                break;
        }

        await templateManager.renderPage(effectivePageName, data);
        this.renderFeedback();
    }

    async applyAvailabilityFilter() {
        const startInput = document.getElementById('availability-start');
        const endInput = document.getElementById('availability-end');

        const startValue = startInput ? startInput.value : '';
        const endValue = endInput ? endInput.value : '';

        if (!startValue) {
            await this.clearAvailabilityFilter();
            return;
        }

        if (startValue && endValue && endValue < startValue) {
            this.setFeedback('La date de fin doit être postérieure ou égale à la date de début.', 'error', { autoHide: false });
            return;
        }

        this.filterStartDate = startValue;
        this.filterEndDate = endValue;
        this.currentPage = 1;

        const loaded = await this.loadTools();
        if (!loaded) {
            console.error('Erreur lors de l\'application du filtre de disponibilité: les données n\'ont pas pu être chargées.');
            this.setFeedback('Impossible d\'appliquer le filtre de disponibilité pour le moment.', 'error', { autoHide: false });
        } else {
            const startLabel = this.formatDisplayDate(startValue);
            const endLabel = this.formatDisplayDate(endValue || startValue);
            const message = (endValue && endValue !== startValue)
                ? `Disponibilité appliquée du ${startLabel} au ${endLabel}.`
                : `Disponibilité appliquée pour le ${startLabel}.`;
            this.setFeedback(message, 'info');
        }

        await this.showPage('catalog');
    }

    async clearAvailabilityFilter() {
        if (!this.filterStartDate && !this.filterEndDate) {
            return;
        }

        this.filterStartDate = '';
        this.filterEndDate = '';
        this.currentPage = 1;

        const startInput = document.getElementById('availability-start');
        const endInput = document.getElementById('availability-end');
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';

        const loaded = await this.loadTools();
        if (!loaded) {
            console.error('Erreur lors de la réinitialisation du filtre de disponibilité: les données n\'ont pas pu être chargées.');
            this.setFeedback('Impossible de réinitialiser le filtre de disponibilité.', 'error', { autoHide: false });
        } else {
            this.setFeedback('Filtre de disponibilité réinitialisé. Tous les outils sont affichés.', 'info');
        }

        await this.showPage('catalog');
    }

    async filterToolsByCategory(categoryId) {
        this.selectedCategoryId = categoryId;
        this.currentPage = 1;

        const loaded = await this.loadTools();
        if (!loaded) {
            console.error('Erreur lors du filtrage par catégorie: les données n\'ont pas pu être chargées.');
            this.setFeedback('Impossible d\'appliquer le filtre de catégorie.', 'error', { autoHide: false });
        } else {
            const category = this.categories.find(item => String(item.id) === String(categoryId));
            const message = categoryId === 'all'
                ? 'Toutes les catégories sont affichées.'
                : `Filtre appliqué : ${category?.name ?? 'Catalogue'}.`;
            this.setFeedback(message, 'info');
        }

        await this.showPage('catalog');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
    window.app.init();
});
