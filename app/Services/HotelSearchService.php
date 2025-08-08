<?php

namespace App\Services;

use App\DTO\HotelDTO;
use App\Repositories\SupplierARepository;
use App\Repositories\SupplierBRepository;
use App\Repositories\SupplierCRepository;
use App\Repositories\SupplierDRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HotelSearchService
{
    private array $suppliers;

    public function __construct()
    {
        $this->suppliers = [
            new SupplierARepository(),
            new SupplierBRepository(),
            new SupplierCRepository(),
            new SupplierDRepository(),
        ];
    }

    /**
     * Search hotels from all suppliers with caching
     *
     * @param string $location
     * @param string $checkIn
     * @param string $checkOut
     * @param int|null $guests
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @param string|null $sortBy
     * @return array
     */
    public function searchHotels(
        string $location,
        string $checkIn,
        string $checkOut,
        ?int $guests = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?string $sortBy = null
    ): array {
        // Generate cache key based on all parameters
        $cacheKey = $this->generateCacheKey($location, $checkIn, $checkOut, $guests, $minPrice, $maxPrice, $sortBy);

        // Try to get from cache first
        $cachedResults = Cache::get($cacheKey);
        if ($cachedResults !== null) {
            Log::info("Returning cached results for search: {$location}");
            return $cachedResults;
        }

        Log::info("Starting hotel search for location: {$location}, check-in: {$checkIn}, check-out: {$checkOut}");

        $allHotels = $this->executeParallelRequests($location, $checkIn, $checkOut, $guests, $minPrice, $maxPrice);

        $deduplicatedHotels = $this->deduplicateHotels($allHotels);

        $sortedHotels = $this->sortHotels($deduplicatedHotels, $sortBy);

        $results = array_map(fn(HotelDTO $hotel) => $hotel->toArray(), $sortedHotels);

        Cache::put($cacheKey, $results, now()->addMinutes(10));

        Log::info("Hotel search completed. Found " . count($results) . " unique hotels");

        return $results;
    }

    /**
     * Execute parallel requests to all suppliers using Http::pool()
     *
     * @param string $location
     * @param string $checkIn
     * @param string $checkOut
     * @param int|null $guests
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return array<HotelDTO>
     */
    private function executeParallelRequests(
        string $location,
        string $checkIn,
        string $checkOut,
        ?int $guests,
        ?float $minPrice,
        ?float $maxPrice
    ): array {
        $startTime = microtime(true);
        $filters = compact('location', 'checkIn', 'checkOut', 'guests', 'minPrice', 'maxPrice');

        $responses = Http::pool(function ($pool) use ($filters) {
            $calls = [];
            foreach ($this->suppliers as $supplier) {
                $url = $supplier->endpoint($filters);
                $queryParams = $this->transformFilters($filters);
                $calls[$supplier->getSupplierName()] = $pool
                    ->withHeaders(['Accept' => 'application/json'])
                    ->timeout(3)
                    ->connectTimeout(2)
                    ->get($url, $queryParams);
            }
            return $calls;
        });

        $allHotels = [];
        $supplierResults = [];

        foreach ($this->suppliers as $supplier) {
            $supplierName = $supplier->getSupplierName();
            $response = $responses[$supplierName] ?? null;

            try {
                if ($response && $response->successful()) {
                    $data = $response->json();
                } else {
                    $statusCode = $response ? $response->status() : 'no response';
                    Log::info("Supplier {$supplierName} HTTP failed (status: {$statusCode}), falling back to mock data");
                    $data = $supplier->getMockDataForLocation(
                        $filters['location'],
                        $filters['checkIn'],
                        $filters['checkOut']
                    );
                }

                $hotels = $supplier->mapResponse($data);

                $filteredHotels = array_filter($hotels, function($hotel) use ($guests, $minPrice, $maxPrice) {
                    return $hotel->matchesFilters($guests, $minPrice, $maxPrice);
                });

                $supplierResults[$supplierName] = $filteredHotels;
                array_push($allHotels, ...$filteredHotels);

                Log::info("Supplier {$supplierName} returned " . count($filteredHotels) . " hotels");
            } catch (\Exception $e) {
                Log::error("Supplier {$supplierName} processing failed: " . $e->getMessage());
                $supplierResults[$supplierName] = [];
            }
        }

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        Log::info("Parallel supplier requests completed in {$executionTime}ms", [
            'supplier_results' => array_map('count', $supplierResults),
            'total_hotels' => count($allHotels)
        ]);

        return $allHotels;
    }

    /**
     * Transform filters to the format expected by supplier APIs
     *
     * @param array $filters
     * @return array
     */
    private function transformFilters(array $filters): array
    {
        $q = [
            'location'  => $filters['location'] ?? null,
            'check_in'  => $filters['checkIn'] ?? null,
            'check_out' => $filters['checkOut'] ?? null,
            'guests'    => $filters['guests'] ?? null,
            'min_price' => $filters['minPrice'] ?? null,
            'max_price' => $filters['maxPrice'] ?? null,
        ];
        return array_filter($q, fn($v) => !is_null($v));
    }

    /**
     * Remove duplicate hotels, keeping the one with the lowest price
     *
     * @param array<HotelDTO> $hotels
     * @return array<HotelDTO>
     */
    private function deduplicateHotels(array $hotels): array
    {
        $uniqueHotels = [];
        $duplicatesFound = 0;

        foreach ($hotels as $hotel) {
            $uniqueKey = $hotel->getUniqueKey();

            if (!isset($uniqueHotels[$uniqueKey])) {
                $uniqueHotels[$uniqueKey] = $hotel;
            } else {
                if ($hotel->pricePerNight < $uniqueHotels[$uniqueKey]->pricePerNight) {
                    Log::info("Replacing duplicate hotel '{$hotel->name}' with better price: {$hotel->pricePerNight} < {$uniqueHotels[$uniqueKey]->pricePerNight}");
                    $uniqueHotels[$uniqueKey] = $hotel;
                } else {
                    Log::info("Keeping existing hotel '{$hotel->name}' with better price: {$uniqueHotels[$uniqueKey]->pricePerNight} <= {$hotel->pricePerNight}");
                }
                $duplicatesFound++;
            }
        }

        Log::info("Deduplication completed. Removed {$duplicatesFound} duplicates. Unique hotels: " . count($uniqueHotels));

        return array_values($uniqueHotels);
    }

    /**
     * Sort hotels based on the specified criteria
     *
     * @param array<HotelDTO> $hotels
     * @param string|null $sortBy
     * @return array<HotelDTO>
     */
    private function sortHotels(array $hotels, ?string $sortBy): array
    {
        if (!$sortBy) {
            return $hotels;
        }

        switch (strtolower($sortBy)) {
            case 'price':
                usort($hotels, fn($a, $b) => $a->pricePerNight <=> $b->pricePerNight);
                Log::info("Hotels sorted by price (ascending)");
                break;
            case 'rating':
                usort($hotels, fn($a, $b) => $b->rating <=> $a->rating);
                Log::info("Hotels sorted by rating (descending)");
                break;
            default:
                Log::warning("Invalid sort criteria: {$sortBy}. No sorting applied.");
        }

        return $hotels; // Still DTOs
    }

    /**
     * Generate cache key based on search parameters
     *
     * @param string $location
     * @param string $checkIn
     * @param string $checkOut
     * @param int|null $guests
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @param string|null $sortBy
     * @return string
     */
    private function generateCacheKey(
        string $location,
        string $checkIn,
        string $checkOut,
        ?int $guests,
        ?float $minPrice,
        ?float $maxPrice,
        ?string $sortBy
    ): string {
        $params = [
            'location' => strtolower($location),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $guests,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort_by' => $sortBy,
        ];

        return 'hotel_search_' . md5(serialize($params));
    }

    /**
     * Clear cache for hotel searches (useful for testing)
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::tags(['hotel_search'])->flush();

        Log::info("Hotel search cache cleared");
    }
}
