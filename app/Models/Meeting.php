<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{

    protected $fillable = [
        'user_id',
        'client_name',
        'client_email',
        'meeting_date',
        'subject',
        'meeting_status',
        'details',
        'url',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
        'miniutes' => 'array',
    ];
    
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}