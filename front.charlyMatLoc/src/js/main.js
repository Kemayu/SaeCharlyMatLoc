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
            
            // Stocker les informations d'authentification
            this.token = data.access_token;
            this.refreshToken = data.refresh_token;
            this.user = data.profile;

            localStorage.setItem('access_token', this.token);
            localStorage.setItem('refresh_token', this.refreshToken);
            localStorage.setItem('user', JSON.stringify(this.user));

            return { success: true, user: this.user };
        } catch (error) {
            console.error('Erreur lors de la connexion:', error);
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

// Fonction de test simple
window.testAddToCartBob = async function(toolId) {
    const testDate = document.getElementById('test-date').value;
    
    try {
        const response = await fetch(`${API_BASE_URL}/cart/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tool_id: 1,
                start_date: testDate,
                end_date: testDate,
                quantity: 1,
                user_id: "guest"
            }),
            credentials:'include'
        });
        
        if (response.ok) {
            alert('OK - Ajouté au panier!');
            // Rediriger vers le panier après ajout
            window.app.showPage('card');
        } else {
            const error = await response.text();
            alert('Erreur: ' + error);
        }
    } catch (error) {
        alert('Erreur fetch: ' + error.message);
    }
};

// Fonction pour afficher le panier de Bob
window.showCartBob = async function() {
    window.app.showPage('card');
};


class App {
    constructor() {
        this.authManager = authManager;
        this.tools = [];
        this.filteredTools = []; // Outils actuellement affichés (après filtrage)
        this.card = [];
        this.currentPage = 1;
        this.itemsPerPage = 12; // Nombre d'outils par page
        this.selectedCategoryId = 'all'; // Pour mémoriser le filtre
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
    }

    async loadTools() {
        try {
            const response = await fetch(`${API_BASE_URL}/tools`, {
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`Erreur HTTP lors du chargement des outils: ${response.status}`);
            }
            const data = await response.json();
            // On extrait le tableau 'tools' de la réponse de l'API
            this.tools = data.tools || [];
            this.filteredTools = this.tools; // Au début, tous les outils sont affichés
            console.log('Loaded tools (check tool_id):', this.tools); // DEBUG: Vérifier la présence de tool_id
        } catch (error) {
            console.error('Erreur lors du chargement des outils:', error);
            this.tools = []; // Garde une valeur sûre en cas d'erreur
        }
    }

    async loadCart() {
        if (!this.authManager.isAuthenticated()) {
            console.log('User not authenticated, returning empty cart');
            return { cart: { items: [], total: 0 } };
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
            return data;
        } catch (error) {
            console.error('Erreur lors du chargement du panier:', error);
            return { cart: { items: [], total: 0 } };
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

            // Gérer le bouton de déconnexion
            if (e.target.closest('#logout-btn')) {
                e.preventDefault();
                this.handleLogout();
            }
        });

        // Délégation pour le filtre de catégorie
        document.addEventListener('change', (e) => {
            if (e.target.id === 'category-filter') {
                this.filterToolsByCategory(e.target.value);
            }
        });

        // Délégation pour le formulaire de connexion
        document.addEventListener('submit', async (e) => {
            if (e.target.classList.contains('login-form')) {
                e.preventDefault();
                await this.handleLogin(e.target);
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
            this.showPage('catalog');
        } else {
            alert(`Erreur de connexion: ${result.error}`);
        }
    }

    handleLogout() {
        if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
            this.authManager.logout();
            this.updateNavigationUI(); // Mettre à jour le menu
            alert('Vous êtes déconnecté');
            this.showPage('catalog');
        }
    }

    async showPage(pageName, toolId = null) {
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
                    isAllCategoriesSelected: this.selectedCategoryId === 'all'
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
                    data = { tool: responseData.tool };
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
                const cartData = await this.loadCart();
                data = { 
                    articles: cartData.cart?.items || [],
                    total: cartData.cart?.total || 0,
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
                data = ""
                break;
            default: // Gérer les pages inconnues ou non gérées
                console.error(`Page inconnue ou non gérée: ${effectivePageName}`);
                effectivePageName = 'catalog'; // Revenir au catalogue par défaut
                data = { tools: this.tools };
                break;
        }

        await templateManager.renderPage(effectivePageName, data);
    }

    filterToolsByCategory(categoryId) {
        this.selectedCategoryId = categoryId;

        if (categoryId === 'all') {
            this.filteredTools = this.tools;
        } else {
            // Le DTO backend renvoie 'category_id', nous filtrons sur cette clé.
            this.filteredTools = this.tools.filter(tool => tool.category_id == categoryId);
        }

        // Réinitialiser à la première page après un filtre et ré-afficher
        this.currentPage = 1;
        this.showPage('catalog');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
    window.app.init();
});

// Fonction pour supprimer un article du panier
window.removeFromCart = async function(itemId) {
    if (!confirm('Voulez-vous vraiment supprimer cet article ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/cart/remove/${itemId}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        if (response.ok) {
            alert('Article supprimé du panier');
            window.app.showPage('card');
        } else {
            const error = await response.text();
            alert('Erreur: ' + error);
        }
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
};

// Fonction pour valider le panier
window.validateCart = async function() {
    if (!confirm('Voulez-vous valider votre commande ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/cart/validate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: 'bob'
            }),
            credentials: 'include'
        });
        
        if (response.ok) {
            alert('Commande validée avec succès !');
            window.app.showPage('catalog');
        } else {
            const error = await response.text();
            alert('Erreur: ' + error);
        }
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
};
