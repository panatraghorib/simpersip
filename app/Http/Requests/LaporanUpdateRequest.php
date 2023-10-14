<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LaporanUpdateRequest extends FormRequest
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
            'id' => 'required|exists:App\Models\Laporan,id',
            'title' => 'required',
            'prologue' => 'required',
            'periode' => 'required',
            'category_id' => 'required|string',
            'fpath' => 'required|mimes:pdf|max:15000',
        ];

    }
}
