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
        // Vérifier que l'outil existe
        $tool = $this->toolRepository->findById($request->toolId);
        if ($tool === null) {
            throw new ToolNotFoundException("Tool with ID {$request->toolId} not found.");
        }

        // Vérifier la disponibilité pour la période demandée
        $isAvailable = $this->toolRepository->isAvailableForPeriod(
            $request->toolId,
            $request->startDate,
            $request->endDate,
            $request->quantity
        );

        if (!$isAvailable) {
            throw new \Exception("Tool is not available for the requested period.");
        }

        // Récupérer ou créer le panier courant
        $cart = $this->cartRepository->findCurrentCartByUserId($request->userId);
        if ($cart === null) {
            $cart = $this->cartRepository->createCart($request->userId);
        }

        // Créer l'item du panier
        $cartItem = new CartItem(
            null,
            $cart->getId(),
            $request->toolId,
            $request->getStartDateAsDateTime(),
            $request->getEndDateAsDateTime(),
            $request->quantity,
            $tool
        );

        // Ajouter l'item au panier
        $this->cartRepository->addItem($cart->getId(), $cartItem);

        // Retourner le panier mis à jour
        return $this->getCurrentCart($request->userId);
    }

    public function getCurrentCart(string $userId): CartDTO
    {
        $cart = $this->cartRepository->findCurrentCartByUserId($userId);
        
        if ($cart === null) {
            // Retourner un panier vide
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

    public function clearCart(string $userId): bool
    {
        $cart = $this->cartRepository->findCurrentCartByUserId($userId);
        if ($cart === null) {
            return true; // Panier déjà vide
        }

        return $this->cartRepository->clearCart($cart->getId());
    }
}
