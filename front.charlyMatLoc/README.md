# CharlyMatLoc Frontend

## Description
Application frontend pour CharlyMatLoc - Location d'outils de bricolage et jardinage.
Frontend vanilla JavaScript utilisant Handlebars pour le templating et SCSS pour le styling.

## Architecture

### Structure des fichiers
```
src/
├── index.html              # Point d'entrée de l'application
├── js/
│   ├── main.js            # Application principale et navigation
│   └── templates.js       # Gestionnaire de templates Handlebars
├── templates/
│   ├── home.hbs           # Page d'accueil
│   ├── catalog.hbs        # Catalogue des outils
│   ├── tool-detail.hbs    # Détail d'un outil
│   └── card.hbs           # Panier
├── scss/                  # Styles SCSS modulaires
├── css/
│   └── main.css          # CSS compilé
└── data-example/         # Données de test (JSON)
```

### Fonctionnement

#### Navigation SPA
- **SPA (Single Page Application)** : Une seule page HTML avec navigation simulée
- **Système de routing** : Via attributs `data-page` sur les liens
- **Délégation d'événements** : Gestion centralisée des clics de navigation

#### Templates Handlebars
- **TemplateManager** : Charge et compile les templates `.hbs`
- **Rendu dynamique** : Injection de données dans les templates
- **Templates disponibles** :
  - `home` : Page d'accueil
  - `catalog` : Liste des outils avec grid responsive
  - `tool-detail` : Affichage détaillé d'un outil
  - `card` : Panier (vide pour l'instant)

#### Gestion des données
- **Chargement JSON** : Lecture des fichiers de test dans `data-example/`
- **État de l'application** : Stockage des outils et détails dans la classe `App`
- **Future API** : Architecture prête pour l'intégration avec le backend PHP

## Installation et lancement

### Prérequis
- Node.js (version 16+)
- npm ou yarn

### Installation
```bash
cd front.charlyMatLoc
npm install
```

### Développement
```bash
# Lancer le serveur de développement
npm run dev

# Compiler SCSS en mode watch
npm run sass
```

### Production
```bash
npm run build
npm start
```