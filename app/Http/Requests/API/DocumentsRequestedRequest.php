<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class DocumentsRequestedRequest extends FormRequest
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
        return [
            'transport' => 'required|array',
            'transport.number' => 'required|string|max:255',
            'transport.transport_id' => 'required|string|max:255',
            'transport.due_days' => 'required|in:7,30,45,60',
            'transport.loading_date' => 'required|string|max:255',
            'transport.loading' => 'required|string|max:255',
            'transport.unloading' => 'required|string|max:255',
            'transport.driver_price' => 'nullable|numeric|between:-999999.99,999999.99',
            'transport.driver_plate_number' => 'nullable|string|max:255',
            'transport.timocom_id' => 'nullable|string|max:255',
            'transport.raal_id' => 'nullable|string|max:255',
            'transport.weight' => 'required|numeric|between:0,999999.99',
            'transport.ldm' => 'required|string|max:255','driver_price',
            'transport.bill_file' => 'nullable|string|max:255',
            'transport.docs_file' => 'nullable|string|max:255',

            'driver' => 'required|array',
            'driver.driver_id' => 'required|integer',
            'driver.name' => 'required|string|max:255',
            'driver.email' => 'required|string|email|max:255',
            'driver.country' => 'required|string|max:255',
        ];
    }
}
