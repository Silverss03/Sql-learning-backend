<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    protected $table = 'classes';
    protected $fillable = [
        'class_code',
        'class_name',
        'teacher_id',
        'is_active',
        'semester',
        'max_students',
        'academic_year',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }    
}
