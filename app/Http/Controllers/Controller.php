<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function init()
    {
        $ufee = \Ufee\Amo\Oauthapi::setInstance([
            'domain' => env('AMO_DOMAIN'),
            'client_id' => env('AMO_CLIENT_ID'),
            'client_secret' => env('AMO_CLIENT_SECRET'),
            'redirect_uri' => env('AMO_REDIRECT_URI'),
        ]);

        try {
            $ufee = \Ufee\Amo\Oauthapi::getInstance(env('AMO_CLIENT_ID'));

        } catch (\Exception $exception) {

            $ufee->fetchAccessToken(env('AMO_CODE'));
        }

        return $ufee;
    }
}
