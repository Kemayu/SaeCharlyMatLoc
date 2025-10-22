<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\service;

use charlymatloc\core\ports\api\dto\CredentialsDTO;
use charlymatloc\core\ports\api\dto\ProfileDTO;

/**
 * Interface charlymatlocAuthnServiceInterface
 * Service d'authentification métier
 */
interface CharlymatlocAuthnServiceInterface
{
    /**
     * Vérifie les credentials et retourne le profil utilisateur
     * @param CredentialsDTO $credentials Email et mot de passe
     * @return ProfileDTO Le profil si credentials valides
     * @throws AuthenticationFailedException Si credentials invalides
     */
    public function byCredentials(CredentialsDTO $credentials): ProfileDTO;

    /**
     * Enregistre un nouvel utilisateur
     * @param CredentialsDTO $credentials Email et mot de passe
     * @param int $role Le rôle de l'utilisateur
     * @return ProfileDTO Le profil créé
     */
    public function register(CredentialsDTO $credentials, int $role): ProfileDTO;
}
