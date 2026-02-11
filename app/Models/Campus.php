<?php

namespace App\Models;

use App\Models\Concerns\HasFallbackTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * NOTE: In the current configuration, the campus with the ID of 1 represent the school itself
 * the campus model with the ID of 2 represents all external courses
 */
class Campus extends Model
{
    use HasFactory, HasFallbackTranslations;
    use SoftDeletes;

    public array $translatable = ['name'];

    public $timestamps = false;

    protected $fillable = ['name'];
}
