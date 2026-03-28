<?php

namespace App\Controller;


use App\Cache\PromotionCache;
use App\DTO\LowestPriceEnquiry;
use App\DTO\PromotionResponse;
use App\Entity\Promotion;
use App\Filter\PromotionsFilterInterface;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Service\Serializer\DTOSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;


class ProductsController extends AbstractController
{
    public function __construct(
        private ProductRepository $repository,
        private PromotionRepository $promotionRepository,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/products/{id}/lowest-price', name: 'lowest-price', methods: 'POST')]
    public function lowestPrice(
        Request $request,
        int $id,
        DTOSerializer $serializer,
        PromotionsFilterInterface $promotionsFilter,
        PromotionCache $promotionCache
    ): Response
    {
        $this->logger->info('Lowest price request received', [
            'product_id' => $id,
        ]);

        if ($request->headers->has('fail')) {
            return new JsonResponse(['error' => 'Promotions Engine failure message'], $request->headers->get('fail'));
        }

        $lowestPriceEnquiry = $serializer->deserialize(
            $request->getContent(), LowestPriceEnquiry::class, 'json'
        );

        $product = $this->repository->findOrFail($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $lowestPriceEnquiry->setProduct($product);

        $promotions = $promotionCache->findValidForProduct($product, $lowestPriceEnquiry->getRequestDate());

        $modifiedEnquiry = $promotionsFilter->apply($lowestPriceEnquiry, ...$promotions);

        $responseContent = $serializer->serialize($modifiedEnquiry, 'json');

        $this->logger->info('Lowest price calculated', [
            'product_id'      => $id,
            'discounted_price' => $modifiedEnquiry->getDiscountedPrice(),
            'promotion_name'  => $modifiedEnquiry->getPromotionName(),
        ]);

        return new Response($responseContent, Response::HTTP_OK, ['content-type' => 'application/json']);
    }

    #[Route(path: '/products/{id}/promotions', name: 'promotions', methods: 'GET')]
    public function promotions(int $id): JsonResponse
    {
        $product = $this->repository->findOrFail($id);

        $promotions = $this->promotionRepository->findValidForProduct($product, new \DateTimeImmutable());

        $data = array_map(
            fn(Promotion $promotion) => PromotionResponse::fromEntity($promotion),
            $promotions
        );

        return new JsonResponse($data, Response::HTTP_OK);
    }
}