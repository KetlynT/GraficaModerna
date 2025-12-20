<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmailTemplate extends Model
{
    use HasUuids;

    protected $fillable = ['event_type', 'subject', 'html_content', 'description'];
}