<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HotelSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'location' => 'required|string|min:2|max:100',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'nullable|integer|min:1|max:20',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'sort_by' => 'nullable|string|in:price,rating',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location.required' => 'Location is required.',
            'location.min' => 'Location must be at least 2 characters.',
            'location.max' => 'Location must not exceed 100 characters.',
            'check_in.required' => 'Check-in date is required.',
            'check_in.date' => 'Check-in must be a valid date.',
            'check_in.after_or_equal' => 'Check-in date must be today or later.',
            'check_out.required' => 'Check-out date is required.',
            'check_out.date' => 'Check-out must be a valid date.',
            'check_out.after' => 'Check-out date must be after check-in date.',
            'guests.integer' => 'Guests must be a number.',
            'guests.min' => 'Guests must be at least 1.',
            'guests.max' => 'Guests must not exceed 20.',
            'min_price.numeric' => 'Minimum price must be a number.',
            'min_price.min' => 'Minimum price must be at least 0.',
            'max_price.numeric' => 'Maximum price must be a number.',
            'max_price.min' => 'Maximum price must be at least 0.',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
            'sort_by.in' => 'Sort by must be either price or rating.',
        ];
    }
}
