<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller via policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
        ];
    }

    /**
     * Customize the validation error messages.
     */
    public function messages()
    {
        return [
            'category_id.exists' => 'Selected category does not exist.',
            'title.max' => 'Title cannot exceed 255 characters.',
        ];
    }
}
