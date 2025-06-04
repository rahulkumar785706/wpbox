<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messagesalesforce extends Model
{
    use HasFactory;
    protected $fillable = [
        'phone',
        'message',
        'sent_at',
        'media_url',
        'message_id',
        'status',
        'customer_name',
        'country_code',
    ];
}
