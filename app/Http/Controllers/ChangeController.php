<?php

namespace App\Http\Controllers;

use App\Models\Change;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Http\Request;
use Ufee\Amo\Models\Lead;

class ChangeController extends Controller
{
    public function hook(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        if($request->toArray()['update'][0]['pipeline_id'] == 4582795) {


            $lead_id = $request->toArray()['update'][0]['lead_id'];

            $custom_fields = $request->toArray()['update'][0]['custom_fields'];

            if(count($custom_fields) > 0) {

                $lead = Change::where('lead_id', $lead_id)
                    ->where('status', 'OK')
                    ->first();

                if(!$lead) {

                    foreach ($custom_fields as $custom_field) {

                        if($custom_field['id'] == 760347 && $custom_field['id']['values']['value'] == 'Да') {

                            Lead::create([
                                'lead_id' => $lead_id,
                                'value' => $custom_field['id']['values']['value'],
                            ]);
                        }
                    }
                } else
                    Log::warning('Дубль отработанного хука : '. $lead->lead_id);
            } else
                Log::warning('Нет изменений в полях : '. $lead_id, $custom_fields);
        } else
            Log::warning('Не нужная воронка : '. $request->toArray()['update'][0]['pipeline_id']);
    }

    public function cron()
    {

    }
}
