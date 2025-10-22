<?php

declare(strict_types=1);

namespace charlymatloc\core\application\usecases;

use charlymatloc\core\dto\ReservationDTO;
use charlymatloc\core\domain\entities\reservation\Reservation;
use charlymatloc\core\domain\entities\reservation\ReservationItem;
use charlymatloc\core\application\ports\api\ServiceReservationInterface;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\ReservationRepositoryInterface;
use charlymatloc\core\ports\spi\ServiceCartInterface;

final class ServiceReservation implements ServiceReservationInterface
{
    private ReservationRepositoryInterface $reservationRepository;
    private ServiceCartInterface $cartService;

    public function __construct(
        ReservationRepositoryInterface $reservationRepository,
        ServiceCartInterface $cartService
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->cartService = $cartService;
    }

    public function createFromCart(string $userId): ReservationDTO
    {
        $cartDTO = $this->cartService->getCurrentCart($userId);

        if (empty($cartDTO->items)) {
            throw new \Exception('Cannot create reservation from empty cart');
        }

        $reservation = new Reservation(
            null,
            $userId,
            new \DateTime(),
            'pending',
            $cartDTO->total
        );

        // Convertir les items du panier en items de réservation
        foreach ($cartDTO->items as $cartItem) {
            $tool = $cartItem->tool;
            
            // Calculer la durée et le prix
            $startDate = new \DateTime($tool->startDate ?? 'now');
            $endDate = new \DateTime($tool->endDate ?? 'now');
            $durationDays = max(1, $startDate->diff($endDate)->days);
            $unitPrice = $tool->pricePerDay ?? 0.0;
            $totalPrice = $unitPrice * $cartItem->quantity * $durationDays;

            $reservationItem = new ReservationItem(
                null,
                0, 
                $tool->id,
                $startDate,
                $endDate,
                $cartItem->quantity,
                $unitPrice,
                $totalPrice
            );

            $reservation->addItem($reservationItem);
        }

        $reservation->calculateTotal();

        $createdReservation = $this->reservationRepository->create($reservation);

        $this->cartService->clearCart($userId);

        return ReservationDTO::fromEntity($createdReservation);
    }

    public function getReservationsByUserId(string $userId): array
    {
        $reservations = $this->reservationRepository->findByUserId($userId);
        
        $dtos = [];
        foreach ($reservations as $reservation) {
            $dtos[] = ReservationDTO::fromEntity($reservation);
        }

        return $dtos;
    }

    public function getReservationById(string|int $id): ReservationDTO
    {
        $reservation = $this->reservationRepository->findById($id);

        if ($reservation === null) {
            throw new \Exception("Reservation with ID $id not found");
        }

        return ReservationDTO::fromEntity($reservation);
    }
}
