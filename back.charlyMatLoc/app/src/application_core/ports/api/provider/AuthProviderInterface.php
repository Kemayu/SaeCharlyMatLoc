<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\provider;

use charlymatloc\core\ports\api\dto\CredentialsDTO;
use charlymatloc\core\ports\api\dto\AuthDTO;
use charlymatloc\core\ports\api\dto\ProfileDTO;

/**
 * Interface AuthProviderInterface
 * Définit les méthodes pour l'authentification JWT
 */
interface AuthProviderInterface
{
    /**
     * Enregistre un nouvel utilisateur
     * @param CredentialsDTO $credentials Email et mot de passe
     * @param int $role Le rôle de l'utilisateur (1=patient, 10=praticien, 100=admin)
     * @return ProfileDTO Le profil créé
     */
    public function register(CredentialsDTO $credentials, int $role): ProfileDTO;

    /**
     * Authentifie un utilisateur avec ses credentials
     * @param CredentialsDTO $credentials Email et mot de passe
     * @return AuthDTO Le profil + les tokens JWT
     * @throws AuthProviderInvalidCredentialsException Si credentials invalides
     */
    public function signin(CredentialsDTO $credentials): AuthDTO;

    /**
     * Récupère le profil utilisateur depuis un access token
     * @param string $accessToken Le token JWT
     * @return ProfileDTO Le profil de l'utilisateur authentifié
     * @throws AuthProviderExpiredAccessTokenException Si le token a expiré
     * @throws AuthProviderInvalidAccessTokenException Si le token est invalide
     */
    public function getSignedInUser(string $accessToken): ProfileDTO;
}
