<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{

    protected $fillable = [
        'id','payment_id'
    ];

    public function activeCourses()
    {
        return $this->hasMany('App\ActiveCourse','student_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function payments() {
        return $this->hasMany('App\Payment','student_id');
    }
}
