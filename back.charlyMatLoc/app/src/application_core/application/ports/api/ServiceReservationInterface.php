<?php

declare(strict_types=1);

namespace charlymatloc\core\application\ports\api;

use charlymatloc\core\dto\ReservationDTO;

interface ServiceReservationInterface
{
    /**
     * Crée une réservation à partir du panier de l'utilisateur
     * @throws \Exception si le panier est vide
     */
    public function createFromCart(string $userId): ReservationDTO;

    /**
     * Récupère toutes les réservations d'un utilisateur
     * @return ReservationDTO[]
     */
    public function getReservationsByUserId(string $userId): array;

    /**
     * Récupère une réservation par son ID
     * @throws \Exception si la réservation n'existe pas
     */
    public function getReservationById(string|int $id): ReservationDTO;
}
