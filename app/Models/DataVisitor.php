<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataVisitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'page_visited',
        'visit_date',
        'visit_time',
        'user_agent',
        'referrer',
        'session_duration',
        'device_type',
        'browser_type',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'session_duration' => 'integer',
    ];
}
