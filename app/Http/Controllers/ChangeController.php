<?php

namespace App\Http\Controllers;

use App\Models\Change;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;


class ChangeController extends Controller
{
    public function hook(Request $request)
    {
        Log::info(__METHOD__, $request::capture()->toArray());

        $input = !empty($request::capture()->toArray()['leads']['update'][0]) ?
            $request::capture()->toArray()['leads']['update'][0] :
            $request::capture()->toArray()['leads']['add'][0];

        if($input['pipeline_id'] == 4582795) {

            $input = $request::capture()->toArray();

            $lead_id = $input['id'];

            $custom_fields = $input['custom_fields'];

            if(count($custom_fields) > 0) {

                $lead = Change::where('lead_id', $lead_id)
                    ->where('status', 'OK')
                    ->where('status', 'В компании уже есть нужный тег')
                    ->first();

                if(!$lead) {

                    foreach ($custom_fields as $custom_field) {

                        if($custom_field['id'] == 760347 && $custom_field['values']['value'] == 'Да') {

                            Change::create([
                                'lead_id' => $lead_id,
                                'value' => $custom_field['values']['value'],
                            ]);
                        }
                    }
                } else
                    Log::warning('Дубль отработанного хука : '. $lead->lead_id);
            } else
                Log::warning('Нет изменений в полях : '. $lead_id, $custom_fields);
        } else
            Log::warning('Ненужная воронка : '. $input['pipeline_id']);
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

                        if(count($company->tags) > 0) {

                            foreach ($company->tags as $tag) {

                                if($tag == 'Продающее АН') {

                                    $change->status = 'В компании уже есть нужный тег';
                                    $change->company_id = $company->id;
                                    $change->save();

                                    continue 2;
                                }
                            }
                        }

                        $company->attachTag('Продающее АН');
                        $company->save();

                        $change->status = 'OK';
                        $change->company_id = $company->id;
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
