<?php

namespace App\Repositories\Interfaces;

use App\DTO\HotelDTO;

interface HotelSupplierInterface
{
    /**
     * Search for hotels based on the given criteria
     *
     * @param string $location
     * @param string $checkIn
     * @param string $checkOut
     * @param int|null $guests
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return array<HotelDTO>
     */
    public function searchHotels(
        string $location,
        string $checkIn,
        string $checkOut,
        ?int $guests = null,
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): array;

    /**
     * Get the supplier name/identifier
     *
     * @return string
     */
    public function getSupplierName(): string;

    /**
     * Build the API endpoint URL for this supplier
     *
     * @param array $filters
     * @return string
     */
    public function endpoint(array $filters): string;

    /**
     * Map the JSON response from the supplier API to HotelDTO array
     *
     * @param array $json
     * @return array<HotelDTO>
     */
    public function mapResponse(array $json): array;

    /**
     * Get mock data for a specific location (fallback when HTTP fails)
     *
     * @param string $location
     * @param string $checkIn
     * @param string $checkOut
     * @return array
     */
    public function getMockDataForLocation(string $location, string $checkIn, string $checkOut): array;
}
