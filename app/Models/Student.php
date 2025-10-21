<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'user_id', 
        'student_code',
        'avg_score'
    ] ;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrolments()
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
