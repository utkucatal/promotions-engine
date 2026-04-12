<?php

namespace App\Controller;

use App\Attribute\RateLimit;
use App\Search\ElasticsearchService;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/search', name: 'search')]
#[OA\Tag(name: 'Search')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly ElasticsearchService $es,
        private readonly CacheInterface $cache,
    ){}

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/products', name: 'search_products', methods: ['GET'])]
    #[RateLimit(limit: 10000, intervalSeconds: 60)]
    public function products(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return new JsonResponse(['error' => 'Query must be at least 2 characters'],400);
        }

        $cacheKey = 'search_products_' . md5($query);
        $results = $this->cache->get($cacheKey, function (ItemInterface $item) use ($query) {
            $item->tag(['search-products']);
            $hits = $this->es->search('products', $query);
            return array_map(fn($hit)=>[
                'score' => $hit['_score'],
                ...$hit['_source'],
            ], $hits);
        });

        return new JsonResponse($results);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    #[Route('/promotions', name: 'search_promotions', methods: ['GET'])]
    #[RateLimit(limit: 120, intervalSeconds: 60)]
    public function promotions(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type');

        if (strlen($query) < 2) {
            return new JsonResponse(['error' => 'Query must be at least 2 characters'], 400);
        }

        $filters = [];
        if ($type) {
            $filters['type'] = $type;
        }

        $hits = $this->es->search('promotions', $query, $filters);
        $result = array_map(fn($hit)=>[
            'score' => $hit['_score'],
            ...$hit['_source'],
        ], $hits);

        return new JsonResponse($result);
    }
}
