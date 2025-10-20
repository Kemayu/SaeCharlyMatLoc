import { templateManager } from './templates.js';

class App {
    constructor() {
        this.tools = [];
        this.tool_details = null;
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

            // A remplacer par lefetch de l'api dnas un autre fichier !
            const response = await fetch('../data-example/tools.json');
            if (!response.ok) {
                throw new Error(`Failed to load tools: ${response.status}`);
            }
            this.tools = await response.json();
        } catch (error) {
            console.error('Error loading tools:', error);
            this.tools = [];
        }
    }
    async loadToolDetails() {
        try {

            // A remplacer par lefetch de l'api dnas un autre fichier !
            const response = await fetch('../data-example/tool-details.json');
            if (!response.ok) {
                throw new Error(`Failed to load tool details: ${response.status}`);
            }
            this.tool_details = await response.json();
        } catch (error) {
            console.error('Error loading tool details:', error);
            this.tool_details = null;
        }
    }

    // A TERME, faire une méthod egénérale pour éviterla duplication de code ! (loaders)
    // ===========
    // ===========

    
    setupNavigation() {
        // Délégation d'événements pour gérer les liens dynamiques
        document.addEventListener('click', (e) => {
            const pageLink = e.target.closest('[data-page]');
            if (pageLink) {
                e.preventDefault();
                this.showPage(pageLink.getAttribute('data-page'));
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
                if (toolId) {
                    const tool = this.findToolById(toolId);
                    data = { tool: tool };
                } else {
                    // A SUPPRIMER !
                    await this.loadToolDetails();
                    data = { tool: this.tool_details[0] };
                    console.log(data);
                }
                break;
            case 'tool-detail':
                data = { card: this.card };
                break;
            case 'home':
            default:
                data = {};
        }

        await templateManager.renderPage(pageName, data);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const app = new App();
    app.init();
});
