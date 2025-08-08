<?php

namespace App\Repositories;

use App\DTO\HotelDTO;
use App\Repositories\Interfaces\HotelSupplierInterface;
use Illuminate\Support\Facades\Log;

class SupplierBRepository implements HotelSupplierInterface
{
    private array $mockHotels = [
        'cairo' => [
            [
                'name' => 'Grand Nile Hotel', // Duplicate with SupplierA but different price
                'location' => 'Cairo, Egypt',
                'price_per_night' => 110.00, // Lower price than SupplierA
                'available_rooms' => 10,
                'rating' => 4.5,
            ],
            [
                'name' => 'Nile Boutique Hotel',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 75.00,
                'available_rooms' => 6,
                'rating' => 3.8,
            ],
            [
                'name' => 'Cairo Downtown Hotel',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 65.00,
                'available_rooms' => 20,
                'rating' => 3.5,
            ],
        ],
        'dubai' => [
            [
                'name' => 'Atlantis The Palm',
                'location' => 'Dubai, UAE',
                'price_per_night' => 320.00,
                'available_rooms' => 8,
                'rating' => 4.7,
            ],
            [
                'name' => 'Marina Bay Hotel', // Duplicate with SupplierA but higher price
                'location' => 'Dubai, UAE',
                'price_per_night' => 195.00, // Higher price than SupplierA
                'available_rooms' => 15,
                'rating' => 4.3,
            ],
            [
                'name' => 'JBR Beach Resort',
                'location' => 'Dubai, UAE',
                'price_per_night' => 160.00,
                'available_rooms' => 12,
                'rating' => 4.1,
            ],
        ],
        'london' => [
            [
                'name' => 'Savoy Hotel London',
                'location' => 'London, UK',
                'price_per_night' => 420.00,
                'available_rooms' => 4,
                'rating' => 4.9,
            ],
            [
                'name' => 'Thames View Hotel', // Duplicate with SupplierA but different price
                'location' => 'London, UK',
                'price_per_night' => 140.00, // Lower price than SupplierA
                'available_rooms' => 22,
                'rating' => 4.1,
            ],
            [
                'name' => 'Hyde Park Hotel',
                'location' => 'London, UK',
                'price_per_night' => 180.00,
                'available_rooms' => 8,
                'rating' => 4.2,
            ],
        ],
        'paris' => [
            [
                'name' => 'Hotel Plaza Athenee',
                'location' => 'Paris, France',
                'price_per_night' => 350.00,
                'available_rooms' => 3,
                'rating' => 4.8,
            ],
            [
                'name' => 'Montmartre Boutique Hotel',
                'location' => 'Paris, France',
                'price_per_night' => 130.00,
                'available_rooms' => 16,
                'rating' => 4.0,
            ],
            [
                'name' => 'Seine River Hotel',
                'location' => 'Paris, France',
                'price_per_night' => 165.00,
                'available_rooms' => 11,
                'rating' => 4.3,
            ],
        ],
        'new york' => [
            [
                'name' => 'The Plaza New York',
                'location' => 'New York, USA',
                'price_per_night' => 480.00,
                'available_rooms' => 2,
                'rating' => 4.9,
            ],
            [
                'name' => 'Times Square Hotel',
                'location' => 'New York, USA',
                'price_per_night' => 220.00,
                'available_rooms' => 25,
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

                // Add some price variation based on dates (different logic than SupplierA)
                $hotel['price_per_night'] = $this->adjustPriceForDates($hotel['price_per_night'], $checkIn, $checkOut);

                $hotelDTO = HotelDTO::fromArray($hotel);

                // Apply filters
                if ($hotelDTO->matchesFilters($guests, $minPrice, $maxPrice)) {
                    $hotelDTOs[] = $hotelDTO;
                }
            }

            Log::info("SupplierB returned " . count($hotelDTOs) . " hotels for location: {$location}");

            return $hotelDTOs;
        } catch (\Exception $e) {
            Log::error("SupplierB search failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSupplierName(): string
    {
        return 'supplier_b';
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
        $baseUrl = 'https://api.supplier-b.com/hotels/search';

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

        Log::info("SupplierB mapped " . count($hotelDTOs) . " hotels from API response");

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
     * Simulate dynamic pricing based on dates (different logic than SupplierA)
     */
    private function adjustPriceForDates(float $basePrice, string $checkIn, string $checkOut): float
    {
        $checkInDate = new \DateTime($checkIn);
        $month = (int) $checkInDate->format('n'); // 1-12

        // Summer season pricing (June, July, August)
        if (in_array($month, [6, 7, 8])) {
            return round($basePrice * 1.15, 2); // 15% increase for summer
        }

        // Winter holiday pricing (December, January)
        if (in_array($month, [12, 1])) {
            return round($basePrice * 1.25, 2); // 25% increase for holidays
        }

        return $basePrice;
    }
}
