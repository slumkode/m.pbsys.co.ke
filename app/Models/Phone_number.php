<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Phone_number extends Model
    {
        protected $fillable = [
            "customer_name", 'phone_number'
        ];
    }
