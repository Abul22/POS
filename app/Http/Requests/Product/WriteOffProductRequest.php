<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Request;

class WriteOffProductRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amountToWriteOff' => 'required|integer|min:1'
        ];
    }
}
