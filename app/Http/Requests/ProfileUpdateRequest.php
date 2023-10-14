<?php

namespace App\Http\Requests;

use App\Rules\AlphabetAndUnderLineCharactersOnly;
use App\Rules\BirthDayShouldBe;
use App\Rules\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
            'date_of_birth' => Carbon::createFromFormat('d/m/Y', $this->date_of_birth)->format('Y-m-d'),
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
            'id' => 'required|exists:App\Models\UserProfile,id',
            'user_id' => 'required|exists:App\Models\UserProfile,user_id',
            'email' => 'required|email|unique:App\Models\User,email',
            'username' => ['required', 'string', 'max:25', 'unique:App\Models\UserProfile,username,' . $this->id, new AlphabetAndUnderLineCharactersOnly],
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'mobile' => [new PhoneNumber],
            'avatar' => ['string'],
            'address' => 'required',
            'gender' => 'required',
            'url_website' => 'url',
            'url_facebook' => 'url',
            'url_instagram' => 'url',
            'date_of_birth' => ['required', new BirthDayShouldBe],
            'bio' => 'max:225',
        ];
    }
}
