<?php

namespace App\Command;

use App\Search\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:elasticsearch:reindex', description: 'Elasticsearch indexer')]
class ElasticsearchReindexCommand extends Command
{
    private const BATCH_SIZE = 20000;

    public function __construct(
        private readonly ElasticsearchService $es,
        private readonly EntityManagerInterface $em,
    )
    {
        parent::__construct();
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->es->createIndices();
        $this->reindexEntity('App\Entity\Product', 'products', $output, function($product){
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
            ];
        });

        $this->reindexEntity('App\Entity\Promotion', 'promotions', $output, function($promo){
            return [
                'id' => $promo->getId(),
                'name' => $promo->getName(),
                'type' => $promo->getType(),
                'adjustment' => $promo->getAdjustment(),
                'criteria' => $promo->getCriteria(),
            ];
        });

        return Command::SUCCESS;
    }

    private function reindexEntity(string $entity, string $indexName, OutputInterface $output, callable $mapper): void
    {
        $query = $this->em->createQuery("Select e From {$entity} e");
        $docs = [];
        $total = 0;

        foreach ($query->toIterable() as $item) {
            $docs[] = $mapper($item);
            $total++;

            if (count($docs) >= self::BATCH_SIZE) {
                $this->es->bulkIndex($indexName, $docs);
                $docs = [];
                $this->em->clear();
            }
        }

        if (!empty($docs)) {
            $this->es->bulkIndex($indexName, $docs);
        }
        $output->writeln(sprintf('Indexed %d %s', $total, $indexName));
    }
}
