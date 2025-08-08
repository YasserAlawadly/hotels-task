<?php

namespace App\Repositories;

use App\DTO\HotelDTO;
use App\Repositories\Interfaces\HotelSupplierInterface;
use Illuminate\Support\Facades\Log;

class SupplierCRepository implements HotelSupplierInterface
{
    private array $mockHotels = [
        'cairo' => [
            [
                'name' => 'Four Seasons Cairo',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 250.00,
                'available_rooms' => 4,
                'rating' => 4.8,
            ],
            [
                'name' => 'Cairo Palace Hotel', // Duplicate with SupplierA but different price
                'location' => 'Cairo, Egypt',
                'price_per_night' => 80.00, // Lower price than SupplierA
                'available_rooms' => 18,
                'rating' => 4.0,
            ],
        ],
        'dubai' => [
            [
                'name' => 'Burj Al Arab', // Duplicate with SupplierA but different price
                'location' => 'Dubai, UAE',
                'price_per_night' => 420.00, // Lower price than SupplierA
                'available_rooms' => 5,
                'rating' => 5.0,
            ],
            [
                'name' => 'Emirates Palace Hotel',
                'location' => 'Dubai, UAE',
                'price_per_night' => 380.00,
                'available_rooms' => 6,
                'rating' => 4.9,
            ],
            [
                'name' => 'Downtown Dubai Hotel',
                'location' => 'Dubai, UAE',
                'price_per_night' => 140.00,
                'available_rooms' => 30,
                'rating' => 3.9,
            ],
        ],
        'london' => [
            [
                'name' => 'The Shard Hotel',
                'location' => 'London, UK',
                'price_per_night' => 320.00,
                'available_rooms' => 7,
                'rating' => 4.6,
            ],
            [
                'name' => 'Covent Garden Inn', // Duplicate with SupplierA but different price
                'location' => 'London, UK',
                'price_per_night' => 115.00, // Lower price than SupplierA
                'available_rooms' => 14,
                'rating' => 3.9,
            ],
            [
                'name' => 'London Bridge Hotel',
                'location' => 'London, UK',
                'price_per_night' => 95.00,
                'available_rooms' => 25,
                'rating' => 3.7,
            ],
        ],
        'paris' => [
            [
                'name' => 'Le Grand Hotel Paris', // Duplicate with SupplierA but different price
                'location' => 'Paris, France',
                'price_per_night' => 270.00, // Lower price than SupplierA
                'available_rooms' => 8,
                'rating' => 4.7,
            ],
            [
                'name' => 'Champs Elysees Hotel',
                'location' => 'Paris, France',
                'price_per_night' => 190.00,
                'available_rooms' => 12,
                'rating' => 4.2,
            ],
        ],
        'tokyo' => [
            [
                'name' => 'Park Hyatt Tokyo',
                'location' => 'Tokyo, Japan',
                'price_per_night' => 400.00,
                'available_rooms' => 3,
                'rating' => 4.9,
            ],
            [
                'name' => 'Shibuya Sky Hotel',
                'location' => 'Tokyo, Japan',
                'price_per_night' => 180.00,
                'available_rooms' => 20,
                'rating' => 4.3,
            ],
            [
                'name' => 'Tokyo Bay Resort',
                'location' => 'Tokyo, Japan',
                'price_per_night' => 150.00,
                'available_rooms' => 15,
                'rating' => 4.1,
            ],
        ],
        'rome' => [
            [
                'name' => 'Hotel de Russie Rome',
                'location' => 'Rome, Italy',
                'price_per_night' => 290.00,
                'available_rooms' => 5,
                'rating' => 4.7,
            ],
            [
                'name' => 'Colosseum View Hotel',
                'location' => 'Rome, Italy',
                'price_per_night' => 160.00,
                'available_rooms' => 12,
                'rating' => 4.2,
            ],
        ],
    ];

    public function searchHotels(
        string $location,
        string $checkIn,
        string $checkOut,
        ?int $guests = null,
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): array {
        try {
            $locationKey = strtolower($location);
            $hotels = $this->mockHotels[$locationKey] ?? [];

            $hotelDTOs = [];
            foreach ($hotels as $hotel) {
                $hotel['source'] = $this->getSupplierName();

                // Add some price variation based on dates (different logic)
                $hotel['price_per_night'] = $this->adjustPriceForDates($hotel['price_per_night'], $checkIn, $checkOut);

                $hotelDTO = HotelDTO::fromArray($hotel);

                // Apply filters
                if ($hotelDTO->matchesFilters($guests, $minPrice, $maxPrice)) {
                    $hotelDTOs[] = $hotelDTO;
                }
            }

            Log::info("SupplierC returned " . count($hotelDTOs) . " hotels for location: {$location}");

            return $hotelDTOs;
        } catch (\Exception $e) {
            Log::error("SupplierC search failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSupplierName(): string
    {
        return 'supplier_c';
    }

    /**
     * Build the API endpoint URL for this supplier
     *
     * @param array $filters
     * @return string
     */
    public function endpoint(array $filters): string
    {
        // For demonstration, we'll create a mock endpoint
        // In real implementation, this would be the actual supplier API URL
        $baseUrl = 'https://api.supplier-c.com/hotels/search';

        return $baseUrl;
    }

    /**
     * Map the JSON response from the supplier API to HotelDTO array
     *
     * @param array $json
     * @return array<HotelDTO>
     */
    public function mapResponse(array $json): array
    {
        $hotelDTOs = [];

        // Simulate the response structure that would come from the API
        $hotels = $json['hotels'] ?? $json;

        foreach ($hotels as $hotel) {
            $hotel['source'] = $this->getSupplierName();

            // Add some price variation based on dates if available
            if (isset($json['check_in'], $json['check_out'])) {
                $hotel['price_per_night'] = $this->adjustPriceForDates(
                    $hotel['price_per_night'],
                    $json['check_in'],
                    $json['check_out']
                );
            }

            $hotelDTO = HotelDTO::fromArray($hotel);
            $hotelDTOs[] = $hotelDTO;
        }

        Log::info("SupplierC mapped " . count($hotelDTOs) . " hotels from API response");

        return $hotelDTOs;
    }

    /**
     * Get mock data for a location (used for simulation)
     *
     * @param string $location
     * @param string $checkIn
     * @param string $checkOut
     * @return array
     */
    public function getMockDataForLocation(string $location, string $checkIn, string $checkOut): array
    {
        $locationKey = strtolower($location);
        $hotels = $this->mockHotels[$locationKey] ?? [];

        // Add check-in/check-out dates to the response for price adjustment
        return [
            'hotels' => $hotels,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'location' => $location
        ];
    }

    /**
     * Simulate dynamic pricing based on dates (different logic)
     */
    private function adjustPriceForDates(float $basePrice, string $checkIn, string $checkOut): float
    {
        $checkInDate = new \DateTime($checkIn);
        $checkOutDate = new \DateTime($checkOut);
        $nights = $checkInDate->diff($checkOutDate)->days;

        // Long stay discount (7+ nights)
        if ($nights >= 7) {
            return round($basePrice * 0.9, 2); // 10% discount for long stays
        }

        // Short stay premium (1 night)
        if ($nights === 1) {
            return round($basePrice * 1.1, 2); // 10% premium for single night
        }

        return $basePrice;
    }
}
