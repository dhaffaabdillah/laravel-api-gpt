<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Conversation extends Model
{
    use HasFactory;
    protected $table = 'conversations';
    protected $primaryKey = 'recid_conversations';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'email',
        'context',
    ];
}
