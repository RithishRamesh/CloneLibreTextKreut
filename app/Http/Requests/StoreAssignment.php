<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignment extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => ['required',
                'max:255'],
            'available_from_date' => 'required|date',
            'due_date' => 'required|date|after:available_on_date',
            'available_from_time' => 'required|date_format:H:i:00',
            'due_time' => 'required|date_format:H:i:00',
            'type_of_submission' => Rule::in(['completed', 'correct']),
            'num_submissions_needed' => Rule::in([2, 3, 4, 5, 6, 7, 8, 9])
        ];

        //unique assignment name
        if ($this->route('assignment')) {
            array_push($rules['name'], Rule::unique('assignments', 'name')->ignore($this->route('assignment')->id));
        } else {
            array_push($rules['name'], Rule::unique('assignments', 'name'));
        }
        return $rules;
    }
    public function messages()
    {
        return [
            'available_from_time.required' => 'A time is required.',
            'due_time.required' => 'A time is required.'
        ];
    }
}
