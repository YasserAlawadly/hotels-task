<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class HotelSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test
        Cache::flush();
    }

    public function test_hotel_search_with_valid_parameters_returns_success()
    {
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'cairo',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'hotels' => [
                        '*' => [
                            'name',
                            'location',
                            'price_per_night',
                            'available_rooms',
                            'rating',
                            'source'
                        ]
                    ],
                    'total_count',
                    'search_params'
                ],
                'message'
            ])
            ->assertJson([
                'status' => true,
                'message' => 'Hotels fetched successfully'
            ]);

        $this->assertGreaterThan(0, $response->json('data.total_count'));
    }

    public function test_hotel_search_with_all_parameters()
    {
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'dubai',
            'check_in' => '2025-08-15',
            'check_out' => '2025-08-17',
            'guests' => 2,
            'min_price' => 100,
            'max_price' => 300,
            'sort_by' => 'price'
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'search_params' => [
                        'location' => 'dubai',
                        'check_in' => '2025-08-15',
                        'check_out' => '2025-08-17',
                        'guests' => 2,
                        'min_price' => 100,
                        'max_price' => 300,
                        'sort_by' => 'price'
                    ]
                ]
            ]);

        $hotels = $response->json('data.hotels');

        // Check that all hotels are within price range
        foreach ($hotels as $hotel) {
            $this->assertGreaterThanOrEqual(100, $hotel['price_per_night']);
            $this->assertLessThanOrEqual(300, $hotel['price_per_night']);
        }

        // Check that hotels are sorted by price (ascending)
        if (count($hotels) > 1) {
            for ($i = 0; $i < count($hotels) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    $hotels[$i + 1]['price_per_night'],
                    $hotels[$i]['price_per_night']
                );
            }
        }
    }

    public function test_hotel_search_with_rating_sort()
    {
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'london',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
            'sort_by' => 'rating'
        ]));

        $response->assertStatus(200);

        $hotels = $response->json('data.hotels');

        // Check that hotels are sorted by rating (descending)
        if (count($hotels) > 1) {
            for ($i = 0; $i < count($hotels) - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    $hotels[$i + 1]['rating'],
                    $hotels[$i]['rating']
                );
            }
        }
    }

    public function test_hotel_search_deduplication_keeps_lowest_price()
    {
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'cairo',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
        ]));

        $response->assertStatus(200);

        $hotels = $response->json('data.hotels');
        $hotelNames = array_column($hotels, 'name');

        // Check that there are no duplicate hotel names
        $this->assertEquals(count($hotelNames), count(array_unique($hotelNames)));

        // Check that Grand Nile Hotel exists (it's in both SupplierA and SupplierB)
        $grandNileHotels = array_filter($hotels, fn($hotel) => $hotel['name'] === 'Grand Nile Hotel');
        $this->assertCount(1, $grandNileHotels);

        // The price should be the lower one (from SupplierB: 110.00 vs SupplierA: 120.00)
        $grandNileHotel = reset($grandNileHotels);
        $this->assertEquals('supplier_b', $grandNileHotel['source']);
    }

    public function test_hotel_search_caching_works()
    {
        $params = [
            'location' => 'paris',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
        ];

        // First request
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/hotels/search?' . http_build_query($params));
        $firstRequestTime = microtime(true) - $startTime;

        $response1->assertStatus(200);

        // Second request (should be cached)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/hotels/search?' . http_build_query($params));
        $secondRequestTime = microtime(true) - $startTime;

        $response2->assertStatus(200);

        // Results should be identical
        $this->assertEquals($response1->json('data.hotels'), $response2->json('data.hotels'));

        // Second request should be faster (cached)
        $this->assertLessThan($firstRequestTime, $secondRequestTime);
    }

    public function test_hotel_search_validation_errors()
    {
        // Missing required location
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['location']);
    }

    public function test_hotel_search_invalid_date_validation()
    {
        // Check-in date in the past
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'cairo',
            'check_in' => '2024-01-01',
            'check_out' => '2025-08-12',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in']);

        // Check-out before check-in
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'cairo',
            'check_in' => '2025-08-12',
            'check_out' => '2025-08-10',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_out']);
    }

    public function test_hotel_search_invalid_price_range_validation()
    {
        // Max price less than min price
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'cairo',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
            'min_price' => 200,
            'max_price' => 100,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_hotel_search_invalid_sort_by_validation()
    {
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'cairo',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
            'sort_by' => 'invalid_sort',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    public function test_hotel_search_for_unknown_location_returns_empty_results()
    {
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'unknown_city',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'hotels' => [],
                    'total_count' => 0
                ]
            ]);
    }

    public function test_hotel_search_with_strict_price_filter_returns_filtered_results()
    {
        $response = $this->getJson('/api/hotels/search?' . http_build_query([
            'location' => 'dubai',
            'check_in' => '2025-08-10',
            'check_out' => '2025-08-12',
            'min_price' => 400,
            'max_price' => 500,
        ]));

        $response->assertStatus(200);

        $hotels = $response->json('data.hotels');

        foreach ($hotels as $hotel) {
            $this->assertGreaterThanOrEqual(400, $hotel['price_per_night']);
            $this->assertLessThanOrEqual(500, $hotel['price_per_night']);
        }
    }
}
