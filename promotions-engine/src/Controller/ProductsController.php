<?php

namespace App\Controller;


use App\DTO\LowestPriceEnquiry;
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
    #[Route('products/{id}/lowest-price', name: 'lowest-price', methods: 'POST')]
    public function lowestPrice(Request $request, int $id, DTOSerializer $serializer): Response
    {
        if ($request->headers->has('fail')) {
            return new JsonResponse(['error' => 'Promotions Engine failure message'], $request->headers->get('fail'));
        }

        $lowestPriceEnquiry = $serializer->deserialize(
            $request->getContent(), LowestPriceEnquiry::class, 'json'
        );

        //1. Deserialize json data into a EnquiryDTO
        //2. Pass the Enquiry into a promotions filter
        // the appropriate promotion will be applied
        //3. Return the modified Enquiry

        $lowestPriceEnquiry->setDiscountedPrice(50);
        $lowestPriceEnquiry->setPrice(100);
        $lowestPriceEnquiry->setPromotionId(3);
        $lowestPriceEnquiry->setPromotionName(3);

        $responseContent = $serializer->serialize($lowestPriceEnquiry, 'json');

        return new Response($responseContent, Response::HTTP_OK);
//        return new JsonResponse($lowestPriceEnquiry, Response::HTTP_OK);




        echo '<pre>';
        var_dump($lowestPriceEnquiry);
        echo '</pre>';


//        return new JsonResponse([
//            "quantity" => 5,
//            "request_location" => "UK",
//            "voucher_code" => "OU812",
//            "request_date" => "2026-02-10",
//            "product_id" => $id,
//            "price" => 100,
//            "discounted_price" => 50,
//            "promotion_id" => 3,
//            "promotion_name" => 'Black Friday',
//        ], 200);
    }





    #[Route(path: '/products/{id}/promotions', name: 'promotions', methods: 'GET')]
    public function promotions()
    {

    }
}
