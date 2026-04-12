<?php


namespace App\MessageHandler;

use App\Message\PriceCalculatedEvent;
use App\Search\ElasticsearchService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PriceCalculatedHandler
{
    public function __construct(
        private readonly ElasticsearchService $es,
        private readonly LoggerInterface $logger,
    ){}

    public function __invoke(PriceCalculatedEvent $event): void
    {
        $this->es->index('price_calculations', 0, [
            'product_id' => $event->productId,
            'product_name' => $event->productName,
            'original_price' => $event->originalPrice,
            'discounted_price' => $event->discountedPrice,
            'promotion_id' => $event->promotionId,
            'promotion_name' => $event->promotionName,
            'quantity' => $event->quantity,
            'request_date' => $event->requestDate,
            'calculated_at' => $event->calculatedAt,
        ]);

        $this->logger->info('Price calculation indexed to ES', [
            'product_id' => $event->productId,
        ]);
    }
}
