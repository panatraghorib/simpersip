<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotulensiAddRequest extends FormRequest
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

    public function prepareForValidation()
    {
        $this->merge([
            'meeting_participants' => json_encode($this->meeting_participants),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => 'required|string',
            'meeting_leader' => 'required|string',
            'meeting_participants' => 'required|json',
            'meeting_date' => 'required|string',
            'meeting_agenda' => 'required|string',
            'notulis' => 'required|string',
            'file_path' => 'required|mimes:pdf|max:15000',
            'category_id' => 'required|string',
            'additional_info' => 'nullable',
        ];
    }
}
