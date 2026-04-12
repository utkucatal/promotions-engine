<?php

namespace App\MessageHandler;

use App\Message\PromotionChangedEvent;
use App\Search\ElasticsearchService;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PromotionChangedHandler
{
    public function __construct(
        private readonly ElasticsearchService $es,
        private readonly LoggerInterface $logger,
    ){}

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public function __invoke(PromotionChangedEvent $event): void
    {
        if ($event->action === 'deleted'){
            $this->es->delete('promotions', $event->promotionId);
        } else {
            $this->es->index('promotions', $event->promotionId, $event->data);
        }

        $this->logger->info('Promotion synced to ES', [
            'action' => $event->action,
            'promotion_id' => $event->promotionId,
        ]);
    }
}
