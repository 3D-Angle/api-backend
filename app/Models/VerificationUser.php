<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationUser extends Model
{
    use HasFactory;

    public function getUpdatedAtColumn() {
        return null;
    }


    protected $guarded = ['id', 'created_at'];

    /**
     * Get the user that owns the VerificationUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
