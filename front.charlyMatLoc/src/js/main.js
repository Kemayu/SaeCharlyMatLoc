import { templateManager } from './templates.js';

const API_BASE_URL = 'http://localhost:48211';

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
        this.tools = [];
        this.card = [];
    }

    async init() {
        this.setupNavigation();
        await this.loadTools();
        await templateManager.initializeTemplates();
        // Démarrer directement sur le catalogue, car 'home' redirige vers 'catalog'
        await this.showPage('catalog');
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
            console.log('Loaded tools (check tool_id):', this.tools); // DEBUG: Vérifier la présence de tool_id
        } catch (error) {
            console.error('Erreur lors du chargement des outils:', error);
            this.tools = []; // Garde une valeur sûre en cas d'erreur
        }
    }

    async loadCart() {
        try {
            const response = await fetch(`${API_BASE_URL}/cart?user_id=bob`, {
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`Erreur HTTP lors du chargement du panier: ${response.status}`);
            }
            const data = await response.json();
            console.log('Loaded cart:', data);
            return data;
        } catch (error) {
            console.error('Erreur lors du chargement du panier:', error);
            return { items: [], total: 0 };
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
        });
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
                data = { tools: this.tools };
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
                const cartData = await this.loadCart();
                data = { 
                    articles: cartData.items || [],
                    total: cartData.total || 0,
                    user_id: 'bob'
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
