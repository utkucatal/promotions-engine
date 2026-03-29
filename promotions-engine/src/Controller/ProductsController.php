<?php

namespace App\Controller;


use App\Attribute\RateLimit;
use App\Cache\PromotionCache;
use App\DTO\LowestPriceEnquiry;
use App\DTO\PromotionResponse;
use App\Entity\Promotion;
use App\Filter\PriceFilterInterface;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Service\Serializer\DTOSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[OA\Tag(name: 'Products')]
class ProductsController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository   $repository,
        private readonly PromotionRepository $promotionRepository,
        private readonly LoggerInterface     $logger
    )
    {
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/products/{id}/lowest-price', name: 'lowest-price', methods: 'POST')]
    #[OA\Post(
        path: '/products/{id}/lowest-price',
        description: 'Applies all valid promotions and returns the lowest achievable price',
        summary: 'Calculate the lowest price for a product',
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Product ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'quantity', type: 'integer', example: 5),
                new OA\Property(property: 'request_date', type: 'string', format: 'date', example: '2026-02-12'),
                new OA\Property(property: 'voucher_code', type: 'string', example: 'OU812'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Lowest price calculated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'quantity', type: 'integer', example: 5),
                new OA\Property(property: 'voucher_code', type: 'string', example: 'OU812', nullable: true),
                new OA\Property(property: 'request_date', type: 'string', format: 'date', example: '2026-02-12'),
                new OA\Property(property: 'price', type: 'integer', example: 200),
                new OA\Property(property: 'discounted_price', type: 'integer', example: 100),
                new OA\Property(property: 'promotion_id', type: 'integer', example: 1),
                new OA\Property(property: 'promotion_name', type: 'string', example: 'Black Friday half price sale'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Product not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[RateLimit(limit: 60, intervalSeconds: 60)]
    public function lowestPrice(
        Request $request,
        int $id,
        DTOSerializer $serializer,
        PriceFilterInterface $promotionsFilter,
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

    #[Route(path: '/products/{id}/promotions', name: 'products_promotions', methods: 'GET')]
    #[OA\Get(
        path: '/products/{id}/promotions',
        summary: 'Get all valid promotions for a product',
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Product ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'List of valid promotions',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PromotionResponse::class))
        )
    )]
    #[OA\Response(response: 404, description: 'Product not found')]
    #[RateLimit(limit: 120, intervalSeconds: 60)]
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