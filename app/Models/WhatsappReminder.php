<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappReminder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'attempts' => 'integer',
        'attempted_at' => 'datetime',
        'sent_at' => 'datetime',
        'next_attempt_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
