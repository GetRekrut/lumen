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
                    ->where('status', 'В компании уже есть нужный тег')
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
            Log::warning('Ненужная воронка : '. $request->toArray()['update'][0]['pipeline_id']);
    }

    public function cron()
    {
        $changes = Change::where('status', '!=', 'OK')->get();

        if($changes->count() > 0) {

            $ufee = $this->init();

            foreach ($changes as $change) {

                try {
                    $lead = $ufee->leads()->find($change->lead_id);

                    $company = $lead->company;

                    if($company) {

                        //тут получаем теги компании
                        //если есть нужный, то
                        $change->status = 'В компании уже есть нужный тег';
                        $change->company_id = $company->id;
                        $change->save();

                        //если тега нет, то добавляем его
                        $company->attachTag('Продающее АН');
                        $company->save();

                        $change->status = 'OK';
                        $change->save();

                    } else {
                        $change->status = 'У лида нет компании';
                        $change->save();
                    }
                } catch (\Exception $exception) {

                    $change->status = $exception->getMessage();
                    $change->save();
                }
            }
        }
    }
}
