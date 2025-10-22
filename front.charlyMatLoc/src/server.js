import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';

// Configure les chemins pour fonctionner avec les modules ES
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const port = 3000;

// Définir le chemin racine du projet (un niveau au-dessus de 'src')
const projectRoot = path.join(__dirname, '..');

// C'est la ligne clé : elle indique à Express de servir les fichiers statiques (images, css)
// depuis le dossier 'public' et 'src' à la racine du projet frontend.
app.use(express.static(path.join(projectRoot, 'public')));
app.use(express.static(path.join(projectRoot, 'src')));

app.listen(port, '0.0.0.0', () => {
  console.log(`Frontend server running at http://localhost:${port}`);
});