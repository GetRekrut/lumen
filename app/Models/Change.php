<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    protected $fillable = [
        'lead_id',
        'value',
    ];


}
