<?php

declare(strict_types=1);

namespace charlymatloc\core\application\ports\spi\repositoryInterfaces;

use charlymatloc\core\domain\entities\reservation\Reservation;

interface ReservationRepositoryInterface
{
    /**
     * Crée une nouvelle réservation
     */
    public function create(Reservation $reservation): Reservation;

    /**
     * Trouve une réservation par son ID
     */
    public function findById(string|int $id): ?Reservation;

    /**
     * Trouve toutes les réservations d'un utilisateur
     * @return Reservation[]
     */
    public function findByUserId(string $userId): array;

    /**
     * Met à jour le statut d'une réservation
     */
    public function updateStatus(string|int $reservationId, string $status): bool;

    /**
     * Supprime une réservation
     */
    public function delete(string|int $reservationId): bool;

    /**
     * Compte le nombre de réservations d'un utilisateur
     */
    public function countByUserId(string $userId): int;
}
