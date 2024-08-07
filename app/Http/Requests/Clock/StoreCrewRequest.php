<?php

namespace App\Http\Requests\Clock;

use Illuminate\Foundation\Http\FormRequest;

class StoreCrewRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // 'crew_name' => 'required|max:255',
            'crew_type_id' => 'required',
            'superintendentId' => 'required',
            'crew_members' => 'required'
        ];
    }
}
