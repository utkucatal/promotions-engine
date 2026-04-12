<?php

namespace App\Controller;

use App\Attribute\RateLimit;
use App\DTO\CreatePromotionRequest;
use App\DTO\PromotionResponse;
use App\DTO\UpdatePromotionRequest;
use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use App\Search\ElasticsearchService;
use App\Service\Serializer\DTOSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/promotions')]
#[OA\Tag(name: 'Promotions')]
class PromotionsController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepository    $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ElasticsearchService $es,
    ) {}

    #[Route('', name: 'promotions_list', methods: 'GET')]
    #[RateLimit(limit: 120, intervalSeconds: 60)]
    #[OA\Get(path: '/promotions', summary: 'List all promotions')]
    #[OA\Response(
        response: 200,
        description: 'List of promotions',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PromotionResponse::class))
        )
    )]
    public function list(): JsonResponse
    {
        $data = array_map(
            fn(Promotion $p) => PromotionResponse::fromEntity($p),
            $this->repository->findAll()
        );


        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'promotions_show', methods: 'GET')]
    #[RateLimit(limit: 120, intervalSeconds: 60)]
    #[OA\Get(path: '/promotions/{id}', summary: 'Get a promotion by ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Promotion details',
        content: new OA\JsonContent(ref: new Model(type: PromotionResponse::class))
    )]
    #[OA\Response(response: 404, description: 'Promotion not found')]
    public function show(int $id): JsonResponse
    {
        $promotion = $this->repository->find($id);

        if (!$promotion) {
            return new JsonResponse(['error' => 'Promotion not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(PromotionResponse::fromEntity($promotion));
    }

    #[Route('', name: 'promotions_create', methods: 'POST')]
    #[RateLimit(limit: 30, intervalSeconds: 60)]
    #[OA\Post(path: '/promotions', summary: 'Create a new promotion')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreatePromotionRequest::class))
    )]
    #[OA\Response(response: 201, description: 'Promotion created')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function create(Request $request, DTOSerializer $serializer): JsonResponse
    {
        $dto = $serializer->deserialize($request->getContent(), CreatePromotionRequest::class, 'json');

        $promotion = new Promotion();
        $promotion->setName($dto->name);
        $promotion->setType($dto->type);
        $promotion->setAdjustment($dto->adjustment);
        $promotion->setCriteria($dto->criteria);

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        $this->es->index('promotion', $promotion->getId(), [
            'id' => $promotion->getId(),
            'name' => $promotion->getName(),
            'type' => $promotion->getType(),
            'adjustment' => $promotion->getAdjustment(),
            'criteria' => $promotion->getCriteria(),
        ]);

        return new JsonResponse(['id' => $promotion->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'promotions_update', methods: 'PUT')]
    #[RateLimit(limit: 30, intervalSeconds: 60)]
    #[OA\Put(path: '/promotions/{id}', summary: 'Update a promotion')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: UpdatePromotionRequest::class))
    )]
    #[OA\Response(response: 200, description: 'Promotion updated')]
    #[OA\Response(response: 404, description: 'Promotion not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(int $id, Request $request, DTOSerializer $serializer): JsonResponse
    {
        $promotion = $this->repository->find($id);

        if (!$promotion) {
            return new JsonResponse(['error' => 'Promotion not found'], Response::HTTP_NOT_FOUND);
        }

        $dto = $serializer->deserialize($request->getContent(), UpdatePromotionRequest::class, 'json');

        if ($dto->name !== null) $promotion->setName($dto->name);
        if ($dto->adjustment !== null) $promotion->setAdjustment($dto->adjustment);
        if ($dto->criteria !== null) $promotion->setCriteria($dto->criteria);

        $this->entityManager->flush();

        $this->es->index('promotion', $promotion->getId(), [
            'id' => $promotion->getId(),
            'name' => $promotion->getName(),
            'type' => $promotion->getType(),
            'adjustment' => $promotion->getAdjustment(),
            'criteria' => $promotion->getCriteria(),
        ]);

        return new JsonResponse(['message' => 'Promotion updated'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'promotions_delete', methods: 'DELETE')]
    #[RateLimit(limit: 10, intervalSeconds: 60)]
    #[OA\Delete(path: '/promotions/{id}', summary: 'Delete a promotion')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Promotion deleted')]
    #[OA\Response(response: 404, description: 'Promotion not found')]
    public function delete(int $id): JsonResponse
    {
        $promotion = $this->repository->find($id);

        if (!$promotion) {
            return new JsonResponse(['error' => 'Promotion not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($promotion);
        $this->entityManager->flush();

        $this->es->delete('promotions', $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
