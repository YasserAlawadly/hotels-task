<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HotelSearchRequest;
use App\Services\HotelSearchService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HotelSearchController extends Controller
{
    use ApiResponseTrait;

    private HotelSearchService $hotelSearchService;

    public function __construct(HotelSearchService $hotelSearchService)
    {
        $this->hotelSearchService = $hotelSearchService;
    }

    /**
     * Search for hotels
     *
     * @param HotelSearchRequest $request
     * @return JsonResponse
     */
    public function search(HotelSearchRequest $request): JsonResponse
    {
        try {
            // Get validated data from Form Request
            $validated = $request->validated();

            Log::info('Hotel search request received', [
                'location' => $validated['location'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'ip' => $request->ip()
            ]);

            // Search hotels using the service
            $hotels = $this->hotelSearchService->searchHotels(
                location: $validated['location'],
                checkIn: $validated['check_in'],
                checkOut: $validated['check_out'],
                guests: $validated['guests'] ?? null,
                minPrice: $validated['min_price'] ?? null,
                maxPrice: $validated['max_price'] ?? null,
                sortBy: $validated['sort_by'] ?? null
            );

            return $this->successResponse([
                'hotels' => $hotels,
                'total_count' => count($hotels),
                'search_params' => [
                    'location' => $validated['location'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'guests' => $validated['guests'] ?? null,
                    'min_price' => $validated['min_price'] ?? null,
                    'max_price' => $validated['max_price'] ?? null,
                    'sort_by' => $validated['sort_by'] ?? null,
                ]
            ], 'Hotels fetched successfully');

        } catch (\Exception $e) {
            Log::error('Hotel search failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return $this->serverErrorResponse('An error occurred while searching for hotels. Please try again later.');
        }
    }
}
