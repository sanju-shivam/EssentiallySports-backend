<?php
// app/Models/ComplianceRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'validator_class',
        'parameters',
        'description',
        'is_active',
        'priority'
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
    ];
}