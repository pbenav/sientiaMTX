<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'locale' => ['required', 'string', 'in:en,es'],
            'timezone' => ['required', 'string', 'timezone'],
            'show_welcome_messages' => ['nullable', 'boolean'],
            'work_start_time' => ['nullable', 'string', 'regex:/^[0-9]{2}:[0-9]{2}$/'],
            'work_end_time' => ['nullable', 'string', 'regex:/^[0-9]{2}:[0-9]{2}$/'],
            'work_start_time_1' => ['nullable', 'string', 'regex:/^[0-9]{2}:[0-9]{2}$/'],
            'work_end_time_1' => ['nullable', 'string', 'regex:/^[0-9]{2}:[0-9]{2}$/'],
            'work_start_time_2' => ['nullable', 'string', 'regex:/^[0-9]{2}:[0-9]{2}$/'],
            'work_end_time_2' => ['nullable', 'string', 'regex:/^[0-9]{2}:[0-9]{2}$/'],
            'work_days_1' => ['nullable', 'array'],
            'work_days_1.*' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'work_days_2' => ['nullable', 'array'],
            'work_days_2.*' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'cth_api_url' => ['nullable', 'string', 'url', 'max:255'],
            'cth_api_token' => ['nullable', 'string'],
            'cth_user_code' => ['nullable', 'string', 'max:255'],
            'cth_work_center_code' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->input('tab') === 'general') {
            $this->merge([
                'work_days_1' => $this->input('work_days_1', []),
                'work_days_2' => $this->input('work_days_2', []),
            ]);
            
            // Si limpian la contraseña y ya había una guardada, no la sobreescribimos
            if (empty($this->input('cth_api_token'))) {
                $this->request->remove('cth_api_token');
            }
        }
    }
}
