<?php

namespace App\Repositories;

use App\DTO\HotelDTO;
use App\Repositories\Interfaces\HotelSupplierInterface;
use Illuminate\Support\Facades\Log;

class SupplierDRepository implements HotelSupplierInterface
{
    private array $mockHotels = [
        'cairo' => [
            [
                'name' => 'Pyramids View Resort', // Duplicate with SupplierA but different price
                'location' => 'Cairo, Egypt',
                'price_per_night' => 90.00, // Lower price than SupplierA
                'available_rooms' => 12,
                'rating' => 4.2,
            ],
            [
                'name' => 'Nile Boutique Hotel', // Duplicate with SupplierB but different price
                'location' => 'Cairo, Egypt',
                'price_per_night' => 70.00, // Lower price than SupplierB
                'available_rooms' => 8,
                'rating' => 3.8,
            ],
        ],
        'dubai' => [
            [
                'name' => 'JBR Beach Resort', // Duplicate with SupplierB but different price
                'location' => 'Dubai, UAE',
                'price_per_night' => 155.00, // Lower price than SupplierB
                'available_rooms' => 16,
                'rating' => 4.1,
            ],
            [
                'name' => 'Dubai Marina Hotel',
                'location' => 'Dubai, UAE',
                'price_per_night' => 130.00,
                'available_rooms' => 22,
                'rating' => 3.8,
            ],
        ],
        'london' => [
            [
                'name' => 'The Ritz London', // Duplicate with SupplierA but different price
                'location' => 'London, UK',
                'price_per_night' => 370.00, // Lower price than SupplierA
                'available_rooms' => 7,
                'rating' => 4.8,
            ],
            [
                'name' => 'Hyde Park Hotel', // Duplicate with SupplierB but different price
                'location' => 'London, UK',
                'price_per_night' => 175.00, // Lower price than SupplierB
                'available_rooms' => 10,
                'rating' => 4.2,
            ],
            [
                'name' => 'Westminster Palace Hotel',
                'location' => 'London, UK',
                'price_per_night' => 200.00,
                'available_rooms' => 6,
                'rating' => 4.4,
            ],
        ],
        'paris' => [
            [
                'name' => 'Eiffel Tower View Hotel', // Duplicate with SupplierA but different price
                'location' => 'Paris, France',
                'price_per_night' => 195.00, // Lower price than SupplierA
                'available_rooms' => 16,
                'rating' => 4.4,
            ],
            [
                'name' => 'Louvre Palace Hotel',
                'location' => 'Paris, France',
                'price_per_night' => 240.00,
                'available_rooms' => 9,
                'rating' => 4.5,
            ],
        ],
        'new york' => [
            [
                'name' => 'Times Square Hotel', // Duplicate with SupplierB but different price
                'location' => 'New York, USA',
                'price_per_night' => 210.00, // Lower price than SupplierB
                'available_rooms' => 30,
                'rating' => 4.2,
            ],
            [
                'name' => 'Central Park Hotel',
                'location' => 'New York, USA',
                'price_per_night' => 280.00,
                'available_rooms' => 12,
                'rating' => 4.6,
            ],
        ],
        'barcelona' => [
            [
                'name' => 'Hotel Arts Barcelona',
                'location' => 'Barcelona, Spain',
                'price_per_night' => 220.00,
                'available_rooms' => 8,
                'rating' => 4.5,
            ],
            [
                'name' => 'Gothic Quarter Hotel',
                'location' => 'Barcelona, Spain',
                'price_per_night' => 120.00,
                'available_rooms' => 18,
                'rating' => 4.0,
            ],
            [
                'name' => 'Sagrada Familia Hotel',
                'location' => 'Barcelona, Spain',
                'price_per_night' => 95.00,
                'available_rooms' => 24,
                'rating' => 3.7,
            ],
        ],
        'sydney' => [
            [
                'name' => 'Sydney Harbour Hotel',
                'location' => 'Sydney, Australia',
                'price_per_night' => 190.00,
                'available_rooms' => 14,
                'rating' => 4.3,
            ],
            [
                'name' => 'Opera House View Hotel',
                'location' => 'Sydney, Australia',
                'price_per_night' => 250.00,
                'available_rooms' => 6,
                'rating' => 4.6,
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

            Log::info("SupplierD returned " . count($hotelDTOs) . " hotels for location: {$location}");

            return $hotelDTOs;
        } catch (\Exception $e) {
            Log::error("SupplierD search failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSupplierName(): string
    {
        return 'supplier_d';
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
        $baseUrl = 'https://api.supplier-d.com/hotels/search';

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

        Log::info("SupplierD mapped " . count($hotelDTOs) . " hotels from API response");

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
        $dayOfMonth = (int) $checkInDate->format('j'); // 1-31

        // Early month discount (1st-10th)
        if ($dayOfMonth <= 10) {
            return round($basePrice * 0.95, 2); // 5% discount for early month
        }

        // End of month premium (26th-31st)
        if ($dayOfMonth >= 26) {
            return round($basePrice * 1.08, 2); // 8% premium for end of month
        }

        return $basePrice;
    }
}
