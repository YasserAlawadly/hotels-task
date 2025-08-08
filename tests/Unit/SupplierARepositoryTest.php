<?php

namespace Tests\Unit;

use App\Repositories\SupplierARepository;
use App\DTO\HotelDTO;
use Tests\TestCase;

class SupplierARepositoryTest extends TestCase
{
    private SupplierARepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new SupplierARepository();
    }

    public function test_supplier_name_is_correct()
    {
        $this->assertEquals('supplier_a', $this->repository->getSupplierName());
    }

    public function test_search_hotels_returns_array_of_hotel_dtos()
    {
        $hotels = $this->repository->searchHotels('cairo', '2025-08-10', '2025-08-12');

        $this->assertIsArray($hotels);
        $this->assertNotEmpty($hotels);

        foreach ($hotels as $hotel) {
            $this->assertInstanceOf(HotelDTO::class, $hotel);
            $this->assertEquals('supplier_a', $hotel->source);
        }
    }

    public function test_search_hotels_for_cairo_returns_expected_hotels()
    {
        $hotels = $this->repository->searchHotels('cairo', '2025-08-10', '2025-08-12');

        $hotelNames = array_map(fn($hotel) => $hotel->name, $hotels);

        $this->assertContains('Grand Nile Hotel', $hotelNames);
        $this->assertContains('Pyramids View Resort', $hotelNames);
        $this->assertContains('Cairo Palace Hotel', $hotelNames);
    }

    public function test_search_hotels_for_unknown_location_returns_empty_array()
    {
        $hotels = $this->repository->searchHotels('unknown_city', '2025-08-10', '2025-08-12');

        $this->assertIsArray($hotels);
        $this->assertEmpty($hotels);
    }

    public function test_search_hotels_applies_min_price_filter()
    {
        $hotels = $this->repository->searchHotels('cairo', '2025-08-10', '2025-08-12', null, 100.0);

        foreach ($hotels as $hotel) {
            $this->assertGreaterThanOrEqual(100.0, $hotel->pricePerNight);
        }
    }

    public function test_search_hotels_applies_max_price_filter()
    {
        $hotels = $this->repository->searchHotels('cairo', '2025-08-11', '2025-08-13', null, null, 100.0);

        foreach ($hotels as $hotel) {
            $this->assertLessThanOrEqual(100.0, $hotel->pricePerNight);
        }
    }

    public function test_search_hotels_applies_price_range_filter()
    {
        $hotels = $this->repository->searchHotels('cairo', '2025-08-10', '2025-08-12', null, 80.0, 120.0);

        foreach ($hotels as $hotel) {
            $this->assertGreaterThanOrEqual(80.0, $hotel->pricePerNight);
            $this->assertLessThanOrEqual(120.0, $hotel->pricePerNight);
        }
    }

    public function test_weekend_pricing_adjustment()
    {
        // Friday (weekend)
        $weekendHotels = $this->repository->searchHotels('cairo', '2025-08-15', '2025-08-16');
        // Monday (weekday)
        $weekdayHotels = $this->repository->searchHotels('cairo', '2025-08-11', '2025-08-12');

        $this->assertNotEmpty($weekendHotels);
        $this->assertNotEmpty($weekdayHotels);

        // Weekend prices should be higher (20% increase)
        $weekendPrice = $weekendHotels[0]->pricePerNight;
        $weekdayPrice = $weekdayHotels[0]->pricePerNight;

        $this->assertGreaterThan($weekdayPrice, $weekendPrice);
    }

    public function test_search_hotels_filters_by_available_rooms()
    {
        $hotels = $this->repository->searchHotels('cairo', '2025-08-10', '2025-08-12', 1);

        foreach ($hotels as $hotel) {
            $this->assertGreaterThan(0, $hotel->availableRooms);
        }
    }

    public function test_all_hotels_have_required_properties()
    {
        $hotels = $this->repository->searchHotels('dubai', '2025-08-10', '2025-08-12');

        foreach ($hotels as $hotel) {
            $this->assertNotEmpty($hotel->name);
            $this->assertNotEmpty($hotel->location);
            $this->assertGreaterThan(0, $hotel->pricePerNight);
            $this->assertGreaterThanOrEqual(0, $hotel->availableRooms);
            $this->assertGreaterThanOrEqual(0, $hotel->rating);
            $this->assertLessThanOrEqual(5, $hotel->rating);
            $this->assertEquals('supplier_a', $hotel->source);
        }
    }
}
