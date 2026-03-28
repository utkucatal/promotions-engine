<?php

namespace App\Tests\unit;

use App\DTO\LowestPriceEnquiry;
use App\Entity\Promotion;
use App\Filter\Modifier\DateRangeMultiplier;
use App\Filter\Modifier\EvenItemsMultiplier;
use App\Filter\Modifier\FixedPriceVoucher;
use App\Tests\ServiceTestCase;
use PHPUnit\Framework\Attributes\Test;

class PriceModifiersTest extends ServiceTestCase
{
    #[Test]
    public function DateRangeMultiplierReturnsACorrectlyModifiedPrice():void
    {
        //Given
        $enquiry = new LowestPriceEnquiry();
        $enquiry->setQuantity(5);
        $enquiry->setRequestDate('2026-02-15');

        $promotion = new Promotion();
        $promotion->setName('Black Friday half price sale');
        $promotion->setAdjustment(0.5);
        $promotion->setCriteria(["from" => "2026-02-10", "to" => "2026-04-20"]);
        $promotion->setType('date_range_multiplier');

        $dateRangeModifier = new DateRangeMultiplier();

        //When
        $modifiedPrice = $dateRangeModifier->modify(100, 5, $promotion, $enquiry);


        //Then
        $this->assertEquals(250, $modifiedPrice);
    }


    #[Test]
    public function FixedPriceVoucherReturnsACorrectlyModifiedPrice():void
    {
        //Given
        $fixedPriceVoucher = new FixedPriceVoucher();

        $promotion = new Promotion();
        $promotion->setName('Voucher OU812');
        $promotion->setAdjustment(100);
        $promotion->setCriteria(["code" => "OU812"]);
        $promotion->setType('fixed_price_voucher');

        $enquiry = new LowestPriceEnquiry();
        $enquiry->setQuantity(2);
        $enquiry->setVoucherCode('OU812');
        //When
        $modifiedPrice = $fixedPriceVoucher->modify(150, 5, $promotion, $enquiry);

        //Then
        $this->assertEquals(500, $modifiedPrice);

    }

    #[Test]
    public function EvenItemsMultiplierReturnsACorrectlyModifiedPrice(): void
    {
        //Given
        $enquiry = new LowestPriceEnquiry();
        $enquiry->setQuantity(5);

        $promotion = new Promotion();
        $promotion->setName('Buy one get one free');
        $promotion->setAdjustment(0.5);
        $promotion->setCriteria(["minimum_quantity" => 2]);
        $promotion->setType('even_items_multiplier');

        $evenItemsMultiplier = new EvenItemsMultiplier();
        //When
        $modifiedPrice = $evenItemsMultiplier->modify(100, 5, $promotion, $enquiry);
        //Then
        $this->assertSame(300, $modifiedPrice);
    }

    #[Test]
    public function EvenItemsMultiplierCorrectlyCalculatesAlternatives(): void
    {
        //Given
        $enquiry = new LowestPriceEnquiry();
        $enquiry->setQuantity(5);

        $promotion = new Promotion();
        $promotion->setName('Buy one get one half price');
        $promotion->setAdjustment(0.75);
        $promotion->setCriteria(["minimum_quantity" => 2]);
        $promotion->setType('even_items_multiplier');

        $evenItemsMultiplier = new EvenItemsMultiplier();
        //When
        $modifiedPrice = $evenItemsMultiplier->modify(100, 5, $promotion, $enquiry);
        //Then
        $this->assertSame(400, $modifiedPrice);
    }

}