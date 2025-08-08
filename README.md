# Hotels Task - Laravel Hotel Search API

A RESTful API for multi-supplier hotel search and aggregation, built with Laravel 12.

## Requirements
- PHP >= 8.2
- Composer
- Laravel 12.x
- Extensions: JSON, cURL, OpenSSL

## Getting Started
1. **Clone the repository:**
   ```bash
   git clone <https://github.com/YasserAlawadly/hotels-task>
   cd hotels-task
   ```
2. **Install dependencies:**
   ```bash
   composer install
   ```
3. **Set up environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Run the local server:**
   ```bash
   php artisan serve
   ```

> **Cache:** The project uses file cache by default for simplicity. For production or better performance, it is recommended to use Redis or Memcached.

> **Postman:** A ready-to-use Postman collection (`Hotels_Search_API.json`) is included for easy API testing and exploration.

## API Endpoints

### Hotel Search
- `GET /api/hotels/search` : Search hotels from multiple suppliers

#### Required Parameters
- `location` (string): Hotel location (e.g., "cairo", "dubai", "london")
- `check_in` (date): Check-in date (YYYY-MM-DD, must be today or future)
- `check_out` (date): Check-out date (YYYY-MM-DD, must be after check_in)

#### Optional Parameters
- `guests` (integer): Number of guests (1-20)
- `min_price` (float): Minimum price per night
- `max_price` (float): Maximum price per night
- `sort_by` (string): Sort criteria ("price" or "rating")

#### Example: Hotel Search Request
```bash
curl "http://localhost:8000/api/hotels/search?location=cairo&check_in=2025-08-10&check_out=2025-08-12&guests=2&min_price=100&max_price=300&sort_by=price"
```

#### Example Response
```json
{
  "success": true,
  "data": {
    "hotels": [
      {
        "name": "Grand Nile Hotel",
        "location": "Cairo, Egypt",
        "price_per_night": 110.00,
        "available_rooms": 10,
        "rating": 4.5,
        "source": "supplier_b"
      }
    ],
    "total_count": 1,
    "search_params": {
      "location": "cairo",
      "check_in": "2025-08-10",
      "check_out": "2025-08-12",
      "guests": 2,
      "min_price": 100,
      "max_price": 300,
      "sort_by": "price"
    }
  },
  "message": "Hotels retrieved successfully"
}
```

## Features
- **Multi-Supplier Integration**: Connects to 4 different hotel suppliers simultaneously
- **Parallel Processing**: Executes supplier requests concurrently for optimal performance
- **Smart Deduplication**: Automatically removes duplicate hotels, keeping the best price
- **Advanced Filtering**: Filter by price range, guest count, and location
- **Flexible Sorting**: Sort results by price or rating
- **Intelligent Caching**: 10-minute cache for improved response times

## Architecture
- **Repository Pattern**: Each supplier implemented as separate repository class
- **Service Layer**: `HotelSearchService` orchestrates the search process
- **DTOs**: `HotelDTO` provides unified data structure
- **Caching Strategy**: MD5 hash-based cache keys with 10-minute duration

## Available Test Locations
The API includes mock data for the following locations:
- **Cairo**: Grand Nile Hotel, Pyramids View Resort, Cairo Palace Hotel
- **Dubai**: Burj Al Arab, Marina Bay Hotel, Desert Oasis Resort
- **London**: The Ritz London, Thames View Hotel, Covent Garden Inn
- **Paris**: Le Grand Hotel Paris, Eiffel Tower View Hotel
- **New York**: The Plaza New York, Times Square Hotel
- **Tokyo**: Park Hyatt Tokyo, Shibuya Sky Hotel

## Testing
- To run all tests:
  ```bash
  php artisan test
  ```
- Coverage includes:
  - Unit tests for repository classes and DTOs
  - Feature tests for API integration and validation
  - Edge cases for error handling and empty results
- To run specific test suites:
  ```bash
  php artisan test --testsuite=Unit
  php artisan test --testsuite=Feature
  ```

## Notes
- The API uses mock data from 4 different suppliers (A, B, C, D)
- Hotels are deduplicated based on name + location combination
- Failed supplier requests don't break the entire search process
- Comprehensive logging available in `storage/logs/laravel.log`

> **Performance:** The project uses parallel processing simulation for supplier requests. For production, implement actual HTTP clients with proper timeout handling and circuit breakers.
