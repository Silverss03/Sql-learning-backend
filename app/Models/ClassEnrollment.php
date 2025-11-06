<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassEnrollment extends Model
{
    protected $fillable = ['class_id', 'student_id', 'enrolled_at'];

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
