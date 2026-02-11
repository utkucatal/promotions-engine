<?php

namespace App\Controller;


use App\DTO\LowestPriceEnquiry;
use App\Filter\PromotionsFilterInterface;
use App\Service\Serializer\DTOSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProductsController extends AbstractController
{
    /**
     * @throws ExceptionInterface
     */
    #[Route('/products/{id}/lowest-price', name: 'lowest-price', methods: 'POST')]
    public function lowestPrice(Request $request, int $id, DTOSerializer $serializer, PromotionsFilterInterface $promotionsFilter): Response
    {
        if ($request->headers->has('fail')) {
            return new JsonResponse(['error' => 'Promotions Engine failure message'], $request->headers->get('fail'));
        }

        $lowestPriceEnquiry = $serializer->deserialize(
            $request->getContent(), LowestPriceEnquiry::class, 'json'
        );

        //1. Deserialize json data into a EnquiryDTO
        //2. Pass the Enquiry into a promotions filter
        $modifiedEnquiry = $promotionsFilter->apply($lowestPriceEnquiry);

        // the appropriate promotion will be applied
        //3. Return the modified Enquiry
        $responseContent = $serializer->serialize($modifiedEnquiry, 'json');

        return new Response($responseContent, Response::HTTP_OK);
//        return new JsonResponse($lowestPriceEnquiry, Response::HTTP_OK);
    }

    #[Route(path: '/products/{id}/promotions', name: 'promotions', methods: 'GET')]
    public function promotions()
    {
        return true;
    }
}
