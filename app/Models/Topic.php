<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;
    
    protected $fillable = ['topic_name', 'slug', 'description', 'is_active', 'order_index', 'created_by'];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}
