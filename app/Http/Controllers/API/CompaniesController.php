<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    /**
     * Upsert fakturačných firiem z Damara (zrkadlo tabuľky companies).
     * Payload: ['companies' => [['id' => 1, 'name' => '...', 'ico' => '...',
     *           'dic' => '...', 'icdph' => '...', 'country' => 'SK', ...], ...]]
     * Krajina sa očakáva ako 2-písmenový kód (Damaro resolvne country_id -> kód).
     */
    public function sync(Request $request)
    {
        $companies = $request->input('companies', []);

        if (empty($companies)) {
            return response()->json(['upserted' => 0]);
        }

        $fields = ['name', 'address', 'city', 'postal_code', 'country', 'ico', 'dic', 'icdph', 'bank_name', 'swift', 'iban'];
        $upserted = 0;

        foreach ($companies as $item) {
            $id = $item['id'] ?? null;
            if (!$id) continue;

            $data = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $item)) {
                    $data[$field] = $item[$field];
                }
            }

            if (isset($data['country']) && $data['country'] !== null) {
                $data['country'] = strtoupper(substr((string) $data['country'], 0, 2));
            }

            Company::updateOrCreate(['id' => (int) $id], $data);
            $upserted++;
        }

        return response()->json(['upserted' => $upserted]);
    }
}
