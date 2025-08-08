<?php

namespace App\Repositories;

use App\DTO\HotelDTO;
use App\Repositories\Interfaces\HotelSupplierInterface;
use Illuminate\Support\Facades\Log;

class SupplierARepository implements HotelSupplierInterface
{
    private array $mockHotels = [
        'cairo' => [
            [
                'name' => 'Grand Nile Hotel',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 120.00,
                'available_rooms' => 15,
                'rating' => 4.5,
            ],
            [
                'name' => 'Pyramids View Resort',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 95.00,
                'available_rooms' => 8,
                'rating' => 4.2,
            ],
            [
                'name' => 'Cairo Palace Hotel',
                'location' => 'Cairo, Egypt',
                'price_per_night' => 85.00,
                'available_rooms' => 12,
                'rating' => 4.0,
            ],
        ],
        'dubai' => [
            [
                'name' => 'Burj Al Arab',
                'location' => 'Dubai, UAE',
                'price_per_night' => 450.00,
                'available_rooms' => 3,
                'rating' => 5.0,
            ],
            [
                'name' => 'Marina Bay Hotel',
                'location' => 'Dubai, UAE',
                'price_per_night' => 180.00,
                'available_rooms' => 20,
                'rating' => 4.3,
            ],
            [
                'name' => 'Desert Oasis Resort',
                'location' => 'Dubai, UAE',
                'price_per_night' => 220.00,
                'available_rooms' => 7,
                'rating' => 4.6,
            ],
        ],
        'london' => [
            [
                'name' => 'The Ritz London',
                'location' => 'London, UK',
                'price_per_night' => 380.00,
                'available_rooms' => 5,
                'rating' => 4.8,
            ],
            [
                'name' => 'Thames View Hotel',
                'location' => 'London, UK',
                'price_per_night' => 150.00,
                'available_rooms' => 18,
                'rating' => 4.1,
            ],
            [
                'name' => 'Covent Garden Inn',
                'location' => 'London, UK',
                'price_per_night' => 125.00,
                'available_rooms' => 10,
                'rating' => 3.9,
            ],
        ],
        'paris' => [
            [
                'name' => 'Le Grand Hotel Paris',
                'location' => 'Paris, France',
                'price_per_night' => 280.00,
                'available_rooms' => 6,
                'rating' => 4.7,
            ],
            [
                'name' => 'Eiffel Tower View Hotel',
                'location' => 'Paris, France',
                'price_per_night' => 200.00,
                'available_rooms' => 14,
                'rating' => 4.4,
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

                // Add some price variation based on dates (simulate dynamic pricing)
                $hotel['price_per_night'] = $this->adjustPriceForDates($hotel['price_per_night'], $checkIn, $checkOut);

                $hotelDTO = HotelDTO::fromArray($hotel);

                // Apply filters
                if ($hotelDTO->matchesFilters($guests, $minPrice, $maxPrice)) {
                    $hotelDTOs[] = $hotelDTO;
                }
            }

            Log::info("SupplierA returned " . count($hotelDTOs) . " hotels for location: {$location}");

            return $hotelDTOs;
        } catch (\Exception $e) {
            Log::error("SupplierA search failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSupplierName(): string
    {
        return 'supplier_a';
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
        $baseUrl = 'https://api.supplier-a.com/hotels/search';

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

        Log::info("SupplierA mapped " . count($hotelDTOs) . " hotels from API response");

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
     * Simulate dynamic pricing based on dates
     */
    private function adjustPriceForDates(float $basePrice, string $checkIn, string $checkOut): float
    {
        $checkInDate = new \DateTime($checkIn);
        $dayOfWeek = (int) $checkInDate->format('N'); // 1 (Monday) to 7 (Sunday)

        // Weekend pricing (Friday, Saturday, Sunday)
        if (in_array($dayOfWeek, [5, 6, 7])) {
            return round($basePrice * 1.2, 2); // 20% increase for weekends
        }

        return $basePrice;
    }
}
