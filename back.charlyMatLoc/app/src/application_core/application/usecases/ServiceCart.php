<?php

declare(strict_types=1);

namespace charlymatloc\core\application\usecases;

use charlymatloc\core\ports\spi\ServiceCartInterface;
use charlymatloc\core\ports\spi\CartRepositoryInterface;
use charlymatloc\core\ports\spi\ToolRepositoryInterface;
use charlymatloc\core\dto\AddToCartRequestDTO;
use charlymatloc\core\dto\CartDTO;
use charlymatloc\core\dto\CartItemDTO;
use charlymatloc\core\dto\ToolInCartDTO;
use charlymatloc\core\domain\entities\tool\CartItem;
use charlymatloc\core\domain\exception\ToolNotFoundException;
use charlymatloc\core\domain\exception\ToolNotAvailableException;

final class ServiceCart implements ServiceCartInterface
{
    private CartRepositoryInterface $cartRepository;
    private ToolRepositoryInterface $toolRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ToolRepositoryInterface $toolRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->toolRepository = $toolRepository;
    }

    public function addToCart(AddToCartRequestDTO $request): CartDTO
    {
        $tool = $this->toolRepository->findById($request->toolId);
        if ($tool === null) {
            throw new ToolNotFoundException("Tool with ID {$request->toolId} not found.");
        }

        $startDateTime = new \DateTime($request->startDate);
        $endDateTime = new \DateTime($request->endDate);
        $today = new \DateTime('today'); // Début de la journée (00:00:00)

        // Vérifier que la date de début n'est pas dans le passé
        if ($startDateTime < $today) {
            throw new \Exception("Start date cannot be in the past. Must be today or later.");
        }

        // Vérifier que la date de fin n'est pas avant la date de début
        if ($endDateTime < $startDateTime) {
            throw new \Exception("End date cannot be before start date.");
        }

        // Vérifier la disponibilité pour la période demandée
        $isAvailable = $this->toolRepository->isAvailableForPeriod(
            $request->toolId,
            $request->startDate,
            $request->endDate,
            $request->quantity
        );

        if (!$isAvailable) {
            throw new ToolNotAvailableException(
                "Tool with ID {$request->toolId} is not available in the requested quantity ({$request->quantity}) " .
                "for the period from {$request->startDate} to {$request->endDate}. " .
                "Please check availability or choose different dates."
            );
        }

        $cart = $this->cartRepository->findCurrentCartByUserId($request->userId);
        if ($cart === null) {
            $cart = $this->cartRepository->createCart($request->userId);
        }
        // Vérifier qu'il n'y a pas déjà le même outil avec des dates qui se chevauchent
        foreach ($cart->getItems() as $existingItem) {
            if ($existingItem->getToolId() === $request->toolId) {
                $existingStart = $existingItem->getStartDate();
                $existingEnd = $existingItem->getEndDate();

                // Vérifier si les dates sont exactement les mêmes
                if ($existingStart->format('Y-m-d') === $startDateTime->format('Y-m-d') &&
                    $existingEnd->format('Y-m-d') === $endDateTime->format('Y-m-d')) {
                    throw new \Exception("This tool is already in your cart for the same dates. Please update the quantity instead.");
                }

                // Vérifier s'il y a un chevauchement de dates
                // Chevauchement si : (start1 <= end2) ET (end1 >= start2)
                if ($startDateTime <= $existingEnd && $endDateTime >= $existingStart) {
                    throw new \Exception(
                        "This tool is already in your cart for overlapping dates " .
                        "({$existingStart->format('Y-m-d')} to {$existingEnd->format('Y-m-d')}). " .
                        "Please choose different dates or remove the existing item first."
                    );
                }
            }
        }

        $cartItem = new CartItem(
            null,
            $cart->getId(),
            $request->toolId,
            $startDateTime,
            $endDateTime,
            $request->quantity,
            $tool
        );

        $this->cartRepository->addItem($cart->getId(), $cartItem);

        return $this->getCurrentCart($request->userId);
    }

    public function getCurrentCart(string $userId): CartDTO
    {
        $cart = $this->cartRepository->findCurrentCartByUserId($userId);
        
        if ($cart === null) {
            return new CartDTO([], 0.0);
        }

        $itemDTOs = [];
        foreach ($cart->getItems() as $item) {
            $toolDTO = ToolInCartDTO::fromEntity($item->getTool());
            $itemDTOs[] = new CartItemDTO($toolDTO, $item->getQuantity());
        }

        return new CartDTO($itemDTOs, $cart->calculateTotal());
    }

    public function removeFromCart(string $userId, int $toolId, string $startDate): CartDTO
    {
        $cart = $this->cartRepository->findCurrentCartByUserId($userId);
        if ($cart === null) {
            throw new \Exception("No current cart found for user.");
        }

        $startDateTime = new \DateTime($startDate);
        $this->cartRepository->removeItem($cart->getId(), $toolId, $startDateTime);

        return $this->getCurrentCart($userId);
    }

     public function updateItemQuantity(string $userId, int $itemId, int $newQuantity): CartDTO
    {
        // Valider que la quantité est positive
        if ($newQuantity < 1) {
            throw new \Exception("Quantity must be at least 1");
        }

        // Vérifier que l'item existe et appartient bien à l'utilisateur
        $cart = $this->cartRepository->findCurrentCartByUserId($userId);
        if ($cart === null) {
            throw new \Exception("No current cart found for user.");
        }

        // Vérifier que l'item appartient au panier de l'utilisateur
        $itemBelongsToUser = false;
        $toolId = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getId() === $itemId) {
                $itemBelongsToUser = true;
                $toolId = $item->getToolId();
                break;
            }
        }

        if (!$itemBelongsToUser) {
            throw new \Exception("Item not found in user's cart");
        }

        // Vérifier le stock disponible
        $tool = $this->toolRepository->findById($toolId);
        if ($tool === null) {
            throw new \Exception("Tool not found");
        }

        if ($tool->getStock() < $newQuantity) {
            throw new \Exception("Insufficient stock. Available: {$tool->getStock()}");
        }

        // Mettre à jour la quantité
        $this->cartRepository->updateItemQuantity($itemId, $newQuantity);

        // Retourner le panier mis à jour
        return $this->getCurrentCart($userId);
    }

    public function clearCart(string $userId): bool
    {
        $cart = $this->cartRepository->findCurrentCartByUserId($userId);
        if ($cart === null) {
            return true; 
        }

        return $this->cartRepository->clearCart($cart->getId());
    }
}
