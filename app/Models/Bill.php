<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;
    protected $table = 'bills';
    protected $fillable = [
        'creator_id',
        'phone',
        'email',
        'note',
        'name',
        'status',
        'booking_id',
        'total_price',
        'type'
    ];
}
