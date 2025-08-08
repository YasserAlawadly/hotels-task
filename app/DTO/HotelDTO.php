<?php

namespace App\DTO;

readonly class HotelDTO
{
    public function __construct(
        public string $name,
        public string $location,
        public float  $pricePerNight,
        public int    $availableRooms,
        public float  $rating,
        public string $source
    ) {}

    /**
     * Create HotelDTO from array data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            location: $data['location'],
            pricePerNight: (float) $data['price_per_night'],
            availableRooms: (int) $data['available_rooms'],
            rating: (float) $data['rating'],
            source: $data['source']
        );
    }

    /**
     * Convert HotelDTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'location' => $this->location,
            'price_per_night' => $this->pricePerNight,
            'available_rooms' => $this->availableRooms,
            'rating' => $this->rating,
            'source' => $this->source,
        ];
    }

    /**
     * Get unique identifier for deduplication (name + location)
     *
     * @return string
     */
    public function getUniqueKey(): string
    {
        return strtolower(trim($this->name . '|' . $this->location));
    }

    /**
     * Check if this hotel matches the given filters
     *
     * @param int|null $guests
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return bool
     */
    public function matchesFilters(?int $guests = null, ?float $minPrice = null, ?float $maxPrice = null): bool
    {
        if ($guests !== null && $this->availableRooms < 1) {
            return false;
        }

        if ($minPrice !== null && $this->pricePerNight < $minPrice) {
            return false;
        }

        if ($maxPrice !== null && $this->pricePerNight > $maxPrice) {
            return false;
        }

        return true;
    }
}
