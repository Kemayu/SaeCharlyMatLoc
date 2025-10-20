class TemplateManager {
    constructor() {
        this.templates = {};
        this.contentContainer = '#main-content';
        this.templatesPath = '../templates/';
    }

    async initializeTemplates() {
        // Charger et compiler les templates depuis le répertoire templates
        await this.loadTemplateFromFile('home');
        await this.loadTemplateFromFile('catalog');
        await this.loadTemplateFromFile('tool-detail');
        await this.loadTemplateFromFile('card');
    }

    async loadTemplateFromFile(name) {
        try {
            const response = await fetch(`${this.templatesPath}${name}.hbs`);
            if (!response.ok) {
                throw new Error(`Failed to load template ${name}: ${response.status}`);
            }
            const templateString = await response.text();
            this.templates[name] = Handlebars.compile(templateString);
        } catch (error) {
            console.error(`Error loading template ${name}:`, error);
        }
    }

    async loadTemplate(name, templateString) {
        this.templates[name] = Handlebars.compile(templateString);
    }

    async renderPage(pageName, data = {}) {
        const template = this.templates[pageName];
        if (!template) {
            console.error(`Template ${pageName} not found`);
            return;
        }

        console.log(`Data for ${pageName}:`, data);

        const html = template(data);
        const container = document.querySelector(this.contentContainer);
        if (container) {
            container.innerHTML = html;
        }
    }
}

export const templateManager = new TemplateManager();
