<?php

declare(strict_types=1);

namespace charlymatloc\infra\repositories;

use charlymatloc\core\domain\entities\reservation\Reservation;
use charlymatloc\core\domain\entities\reservation\ReservationItem;
use charlymatloc\core\domain\entities\tool\Tool;
use charlymatloc\core\application\ports\spi\repositoryInterfaces\ReservationRepositoryInterface;
use \charlymatloc\core\domain\entities\tool\Category;
use PDO;

final class PDOReservationRepository implements ReservationRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Reservation $reservation): Reservation
    {
        try {
            $this->pdo->beginTransaction();

            // Insérer la réservation
            $stmt = $this->pdo->prepare(
                "INSERT INTO reservations (reservation_user_id, status_code, start_date, end_date, total_price) 
                 VALUES (:user_id, :status_code, :start_date, :end_date, :total_price) 
                 RETURNING reservation_id"
            );

            $statusCode = $this->mapStatusToCode($reservation->getStatus());
            
            // Calculer les dates min/max des items
            $startDate = null;
            $endDate = null;
            foreach ($reservation->getItems() as $item) {
                if ($startDate === null || $item->getStartDate() < $startDate) {
                    $startDate = $item->getStartDate();
                }
                if ($endDate === null || $item->getEndDate() > $endDate) {
                    $endDate = $item->getEndDate();
                }
            }

            $stmt->execute([
                ':user_id' => $reservation->getUserId(),
                ':status_code' => $statusCode,
                ':start_date' => $startDate ? $startDate->format('Y-m-d') : date('Y-m-d'),
                ':end_date' => $endDate ? $endDate->format('Y-m-d') : date('Y-m-d'),
                ':total_price' => $reservation->getTotalAmount(),
            ]);

            $reservationId = $stmt->fetchColumn();

            // Insérer les items
            $itemStmt = $this->pdo->prepare(
                "INSERT INTO reservation_items (reservation_id, tool_id, quantity, price_per_day) 
                 VALUES (:reservation_id, :tool_id, :quantity, :price_per_day)"
            );

            foreach ($reservation->getItems() as $item) {
                $itemStmt->execute([
                    ':reservation_id' => $reservationId,
                    ':tool_id' => $item->getToolId(),
                    ':quantity' => $item->getQuantity(),
                    ':price_per_day' => $item->getUnitPrice(),
                ]);
            }

            $this->pdo->commit();

            // Recharger la réservation créée (reservationId est un UUID string)
            $createdReservation = $this->findById($reservationId);
            
            if ($createdReservation === null) {
                throw new \Exception("Failed to retrieve created reservation");
            }
            
            return $createdReservation;

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \Exception("Failed to create reservation: " . $e->getMessage());
        }
    }

    public function findById(string|int $id): ?Reservation
    {
        $stmt = $this->pdo->prepare(
            "SELECT reservation_id::text, reservation_user_id, status_code, start_date, end_date, total_price 
             FROM reservations 
             WHERE reservation_id = :id"
        );

        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $reservation = $this->hydrateReservation($data);
        
        // Charger les items
        $items = $this->getReservationItemsByUUID($data['reservation_id']);
        $reservation->setItems($items);

        return $reservation;
    }

    public function findByUserId(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT reservation_id::text, reservation_user_id, status_code, start_date, end_date, total_price 
             FROM reservations 
             WHERE reservation_user_id = :user_id 
             ORDER BY start_date DESC"
        );

        $stmt->execute([':user_id' => $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reservations = [];
        foreach ($results as $data) {
            $reservation = $this->hydrateReservation($data);
            
            // Charger les items pour chaque réservation
            // reservation_id est maintenant un string UUID grâce au ::text
            $items = $this->getReservationItemsByUUID($data['reservation_id']);
            $reservation->setItems($items);
            
            $reservations[] = $reservation;
        }

        return $reservations;
    }

    private function getReservationItemsByUUID(string $reservationUuid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ri.reservation_item_id, ri.reservation_id::text, ri.tool_id, ri.quantity, ri.price_per_day,
                    t.tool_id, t.tool_category_id, t.name, t.description, t.image_url, t.stock,
                    c.category_id, c.name AS category_name,
                    r.start_date, r.end_date
             FROM reservation_items ri
             INNER JOIN tools t ON ri.tool_id = t.tool_id
             INNER JOIN categories c ON t.tool_category_id = c.category_id
             INNER JOIN reservations r ON ri.reservation_id = r.reservation_id
             WHERE ri.reservation_id = :reservation_id::uuid"
        );

        $stmt->execute([':reservation_id' => $reservationUuid]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($results as $row) {
            $category = new Category(
                (int)$row['category_id'],
                $row['category_name']
            );

            $tool = new Tool(
                (int)$row['tool_id'],
                $category,
                $row['name'],
                $row['description'] ?? '',
                $row['image_url'] ?? '',
                (int)$row['stock']
            );

            $startDate = new \DateTime($row['start_date']);
            $endDate = new \DateTime($row['end_date']);
            $durationDays = max(1, $startDate->diff($endDate)->days);
            $totalPrice = (float)$row['price_per_day'] * (int)$row['quantity'] * $durationDays;

            $item = new ReservationItem(
                (int)$row['reservation_item_id'],
                0, // reservationId n'est pas utilisé ici car c'est un UUID
                (int)$row['tool_id'],
                $startDate,
                $endDate,
                (int)$row['quantity'],
                (float)$row['price_per_day'],
                $totalPrice,
                $tool
            );

            $items[] = $item;
        }

        return $items;
    }

    public function updateStatus(string|int $reservationId, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE reservations 
             SET status_code = :status_code 
             WHERE reservation_id = :id"
        );

        $statusCode = $this->mapStatusToCode($status);

        return $stmt->execute([
            ':status_code' => $statusCode,
            ':id' => $reservationId,
        ]);
    }

    public function delete(string|int $reservationId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM reservations WHERE reservation_id = :id");
        return $stmt->execute([':id' => $reservationId]);
    }

    public function countByUserId(string $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    private function getReservationItems(int $reservationId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ri.reservation_item_id, ri.reservation_id, ri.tool_id, ri.quantity, ri.price_per_day,
                    t.tool_id, t.tool_category_id, t.name, t.description, t.image_url, t.stock,
                    c.category_id, c.name AS category_name,
                    r.start_date, r.end_date
             FROM reservation_items ri
             INNER JOIN tools t ON ri.tool_id = t.tool_id
             INNER JOIN categories c ON t.tool_category_id = c.category_id
             INNER JOIN reservations r ON ri.reservation_id = r.reservation_id
             WHERE ri.reservation_id = :reservation_id"
        );

        $stmt->execute([':reservation_id' => $reservationId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($results as $row) {
            $category = new Category(
                (int)$row['category_id'],
                $row['category_name']
            );

            $tool = new Tool(
                (int)$row['tool_id'],
                $category,
                $row['name'],
                $row['description'] ?? '',
                $row['image_url'] ?? '',
                (int)$row['stock']
            );

            $startDate = new \DateTime($row['start_date']);
            $endDate = new \DateTime($row['end_date']);
            $durationDays = max(1, $startDate->diff($endDate)->days);
            $totalPrice = (float)$row['price_per_day'] * (int)$row['quantity'] * $durationDays;

            $item = new ReservationItem(
                (int)$row['reservation_item_id'],
                $reservationId,
                (int)$row['tool_id'],
                $startDate,
                $endDate,
                (int)$row['quantity'],
                (float)$row['price_per_day'],
                $totalPrice,
                $tool
            );

            $items[] = $item;
        }

        return $items;
    }

    private function hydrateReservation(array $data): Reservation
    {
        $status = $this->mapCodeToStatus((int)$data['status_code']);

        return new Reservation(
            $data['reservation_id'] ?? null,
            $data['reservation_user_id'],
            new \DateTime($data['start_date']),
            $status,
            (float)$data['total_price']
        );
    }

    private function mapStatusToCode(string $status): int
    {
        return match ($status) {
            'pending' => 0,
            'confirmed' => 1,
            'returned' => 2,
            'cancelled' => 3,
            default => 0,
        };
    }

    private function mapCodeToStatus(int $code): string
    {
        return match ($code) {
            0 => 'pending',
            1 => 'confirmed',
            2 => 'returned',
            3 => 'cancelled',
            default => 'pending',
        };
    }
}
