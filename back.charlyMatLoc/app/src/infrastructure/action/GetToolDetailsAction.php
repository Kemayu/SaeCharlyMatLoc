<?php
namespace App\Infrastructure\Action;

use App\ApplicationCore\Application\UseCases\GetToolDetails;
use App\Domain\Exception\ToolNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetToolDetailsAction
{
    private GetToolDetails $getToolDetails;

    public function __construct(GetToolDetails $getToolDetails)
    {
        $this->getToolDetails = $getToolDetails;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $id = (int) $request->get('id');
            $tool = $this->getToolDetails->execute($id);

            $pricingTiersHtml = '';
            foreach ($tool->getPricingTiers() as $tier) {
                $pricingTiersHtml .= sprintf(
                    '<li>%d-%s jours : %.2f €/jour</li>',
                    $tier['min_duration_days'],
                    $tier['max_duration_days'] ?? '∞',
                    $tier['price_per_day']
                );
            }

            return new Response(
                '<html><body>' .
                '<h1>' . htmlspecialchars($tool->getName()) . '</h1>' .
                '<img src="' . htmlspecialchars($tool->getImageUrl()) . '" alt="Image de l\'outil">' .
                '<p>' . htmlspecialchars($tool->getDescription()) . '</p>' .
                '<p>Catégorie : ' . htmlspecialchars($tool->getCategory()) . '</p>' .
                '<ul>' . $pricingTiersHtml . '</ul>' .
                '</body></html>'
            );
        } catch (ToolNotFoundException $e) {
            return new Response('Outil non trouvé : ' . $e->getMessage(), 404);
        } catch (\Exception $e) {
            return new Response('Une erreur est survenue : ' . $e->getMessage(), 500);
        }
    }
}
