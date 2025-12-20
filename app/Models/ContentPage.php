<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContentPage extends Model
{
    use HasUuids;
    protected $fillable = ['slug', 'title', 'content', 'is_visible'];
}