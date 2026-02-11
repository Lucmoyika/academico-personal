<?php

namespace App\Models;

use App\Models\Concerns\HasFallbackTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrollmentStatusType extends Model
{
    use HasFactory, HasFallbackTranslations;

    public array $translatable = ['name'];

    public $timestamps = false;

    public function styling()
    {
        return match ($this->id) {
            1 => 'warning',
            2 => 'info',
            default => 'danger',
        };
    }
}
