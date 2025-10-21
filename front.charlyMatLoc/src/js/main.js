import { templateManager } from './templates.js';

const API_BASE_URL = 'http://localhost:6080';

class App {
    constructor() {
        this.tools = [];
        this.card = [];
    }

    async init() {
        this.setupNavigation();
        await this.loadTools();
        await templateManager.initializeTemplates();
        await this.showPage('home');
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
        } catch (error) {
            console.error('Erreur lors du chargement des outils:', error);
            this.tools = []; // Garde une valeur sûre en cas d'erreur
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
                this.showPage(pageName, toolId);
            }
        });
    }

    async showPage(pageName, toolId = null) {
        // Mettre à jour la navigation active
        document.querySelectorAll('[data-page]').forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-page') === pageName);
        });

        // Préparer les données selon la page
        let data = {};
        switch (pageName) {
            case 'catalog':
                data = { tools: this.tools };
                break;
            case 'tool-detail':
                if (!toolId) {
                    console.error("Aucun ID d'outil fourni pour la page de détail.");
                    await this.showPage('catalog'); // Redirige vers le catalogue
                    return;
                }
                try {
                    const response = await fetch(`${API_BASE_URL}/tools/${toolId}`, {
                        credentials: 'include'
                    });
                    if (!response.ok) throw new Error(`Erreur HTTP: ${response.status}`);
                    const tool = await response.json();
                    data = { tool };
                } catch (error) {
                    console.error(`Erreur lors du chargement du détail de l'outil ${toolId}:`, error);
                    data = { error: "L'outil n'a pas pu être chargé." };
                }
                break;
            case 'card':
                data = { card: this.card };
                break;
            case 'home':
            default:
                // Rediriger 'home' vers le catalogue par défaut
                await this.showPage('catalog');
                return; // Arrêter l'exécution pour éviter un double rendu
        }

        await templateManager.renderPage(pageName, data);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const app = new App();
    app.init();
});
