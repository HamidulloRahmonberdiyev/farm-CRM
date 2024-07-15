<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phone extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'phone',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }
}
