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
            console.log('Loaded tools (check tool_id):', this.tools); // DEBUG: Vérifier la présence de tool_id
        } catch (error) {
            console.error('Erreur lors du chargement des outils:', error);
            this.tools = []; // Garde une valeur sûre en cas d'erreur
            this.filteredTools = [];
            this.hasAvailabilityFilter = false;
        }
    }

    async loadCart() {
        if (!this.authManager.isAuthenticated()) {
            console.log('User not authenticated, returning empty cart');
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
            console.log('Loaded cart:', data);
            const cart = data.cart ?? { items: [], total: 0, items_count: 0 };
            return this.updateCartState(cart);
        } catch (error) {
            console.error('Erreur lors du chargement du panier:', error);
            this.updateCartState({ items: [], total: 0, items_count: 0 });
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
            return this.reservations;
        } catch (error) {
            console.error('Erreur lors du chargement des réservations:', error);
            this.reservations = [];
            return this.reservations;
        }
    }
    
    setupNavigation() {
        // Délégation d'événements pour gérer les liens dynamiques
        document.addEventListener('click', (e) => {
            const pageLink = e.target.closest('[data-page]');
            if (pageLink) {
                e.preventDefault();
                const pageName = pageLink.getAttribute('data-page');
                const toolId = pageLink.dataset.id; // Récupère l'ID de l'outil si présent
                console.log('Navigation click:', { pageName, toolId, target: e.target }); // DEBUG: Voir ce qui est cliqué
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
            alert(`Bienvenue ${result.user.email} !`);
            this.updateNavigationUI(); // Mettre à jour le menu
            await this.loadCart();
            await this.loadReservations();
            await this.showPage('catalog');
        } else {
            alert(`Erreur de connexion: ${result.error}`);
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
            alert('Merci de renseigner un email et un mot de passe.');
            return;
        }

        if (password.length < 8) {
            alert('Le mot de passe doit contenir au moins 8 caractères.');
            return;
        }

        if (confirmation && password !== confirmation) {
            alert('Les mots de passe ne correspondent pas.');
            return;
        }

        const result = await this.authManager.register(email, password, confirmation);

        if (result.success) {
            form.reset();
            alert('Votre compte a été créé avec succès. Bienvenue !');
            await this.loadCart();
            await this.loadReservations();
            this.updateNavigationUI();
            await this.showPage('catalog');
        } else {
            alert(`Erreur lors de l'inscription : ${result.error}`);
        }
    }

    handleLogout() {
        if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
            this.authManager.logout();
            this.updateCartState({ items: [], total: 0, items_count: 0 });
            this.reservations = [];
            this.updateNavigationUI(); // Mettre à jour le menu
            alert('Vous êtes déconnecté');
            this.showPage('catalog');
        }
    }

    async handleAddToCart(form) {
        if (!this.authManager.isAuthenticated()) {
            if (confirm('Vous devez être connecté pour ajouter un article au panier. Aller à la connexion ?')) {
                await this.showPage('login');
            }
            return;
        }

        const toolId = parseInt(form.dataset.toolId, 10);
        const formData = new FormData(form);
        const startDate = formData.get('start_date');
        const endDate = formData.get('end_date') || startDate;
        const quantity = parseInt(formData.get('quantity'), 10) || 1;

        if (!toolId || !startDate) {
            alert('Merci de sélectionner une date de début valide.');
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
            alert('Article ajouté au panier avec succès.');
            await this.showPage('card', null, { skipLoad: true });
        } catch (error) {
            console.error('Erreur lors de l\'ajout au panier:', error);
            alert(`Erreur lors de l'ajout au panier : ${error.message}`);
        }
    }

    async handleRemoveFromCart(itemId) {
        if (!this.authManager.isAuthenticated()) {
            alert('Veuillez vous connecter pour gérer votre panier.');
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
            alert('Article supprimé du panier.');
            await this.showPage('card', null, { skipLoad: true });
        } catch (error) {
            console.error('Erreur lors de la suppression du panier:', error);
            alert(`Erreur lors de la suppression : ${error.message}`);
        }
    }

    async handleCartQuantityChange(itemId, newQuantity, inputElement) {
        if (!this.authManager.isAuthenticated()) {
            alert('Veuillez vous connecter pour modifier votre panier.');
            await this.showPage('login');
            return;
        }

        if (newQuantity < 1) {
            inputElement.value = inputElement.dataset.previousValue || 1;
            alert('La quantité minimale est 1.');
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
            await this.showPage('card', null, { skipLoad: true });
        } catch (error) {
            console.error('Erreur lors de la mise à jour de la quantité:', error);
            alert(`Erreur lors de la mise à jour de la quantité : ${error.message}`);
            inputElement.value = previousValue;
        } finally {
            inputElement.disabled = false;
            inputElement.dataset.previousValue = inputElement.value;
        }
    }

    async handleCheckout() {
        if (!this.authManager.isAuthenticated()) {
            alert('Veuillez vous connecter pour valider votre panier.');
            await this.showPage('login');
            return;
        }

        if (!this.cart || this.cart.items_count === 0) {
            alert('Votre panier est vide.');
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
                alert(`Réservation enregistrée, mais le paiement a échoué : ${paymentError.message}`);
                await this.showPage('catalog');
                return;
            }

            this.updateCartState({ items: [], total: 0, items_count: 0 });
            const confirmationMessage = paymentReference
                ? `Paiement simulé réussi ! Référence: ${paymentReference}`
                : 'Paiement simulé réussi !';
            alert(`Commande validée. ${confirmationMessage}`);
            await this.loadReservations();
            await this.showPage('catalog');
        } catch (error) {
            console.error('Erreur lors de la validation du panier:', error);
            alert(`Erreur lors de la validation : ${error.message}`);
        }
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
                    data = { error: "L'outil n'a pas pu être chargé." };
                }
                break;
            case 'card':
                if (!this.authManager.isAuthenticated()) {
                    alert('Vous devez être connecté pour voir votre panier');
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

                data = { 
                    cart: cartState,
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
                    alert('Vous devez être connecté pour consulter vos réservations');
                    await this.showPage('login');
                    return;
                }
                const reservations = await this.loadReservations();
                data = {
                    user: this.authManager.getUser(),
                    reservations,
                    hasReservations: reservations.length > 0
                };
                break;
            default: // Gérer les pages inconnues ou non gérées
                console.error(`Page inconnue ou non gérée: ${effectivePageName}`);
                effectivePageName = 'catalog'; // Revenir au catalogue par défaut
                data = { tools: this.tools };
                break;
        }

        await templateManager.renderPage(effectivePageName, data);
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
            alert('La date de fin doit être postérieure ou égale à la date de début.');
            return;
        }

        this.filterStartDate = startValue;
        this.filterEndDate = endValue;
        this.currentPage = 1;

        try {
            await this.loadTools();
        } catch (error) {
            console.error('Erreur lors de l\'application du filtre de disponibilité:', error);
            alert('Impossible d\'appliquer le filtre de disponibilité.');
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

        try {
            await this.loadTools();
        } catch (error) {
            console.error('Erreur lors de la réinitialisation du filtre de disponibilité:', error);
            alert('Impossible de réinitialiser le filtre de disponibilité.');
        }

        await this.showPage('catalog');
    }

    async filterToolsByCategory(categoryId) {
        this.selectedCategoryId = categoryId;
        this.currentPage = 1;

        try {
            await this.loadTools();
        } catch (error) {
            console.error('Erreur lors du filtrage par catégorie:', error);
            alert('Impossible d\'appliquer le filtre de catégorie.');
        }

        await this.showPage('catalog');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
    window.app.init();
});
