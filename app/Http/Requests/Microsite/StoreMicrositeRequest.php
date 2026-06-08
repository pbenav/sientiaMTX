<?php

namespace App\Http\Requests\Microsite;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMicrositeRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('microsites', 'slug')->whereNull('deleted_at')],
            'html_content' => 'nullable|string',
            'css_content' => 'nullable|string',
            'is_published' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
        ];
    }
    
    public function prepareForValidation()
    {
        $this->merge([
            'slug' => \Illuminate\Support\Str::slug($this->slug ?: $this->title),
        ]);
    }
}
