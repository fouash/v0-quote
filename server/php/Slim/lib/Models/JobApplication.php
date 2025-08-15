<?php

namespace Models;

class JobApplication extends AppModel
{
    protected $table = 'job_applications';
    
    public function job()
    {
        return $this->belongsTo('Models\Job', 'job_id', 'id')
            ->with('user', 'category');
    }
    
    public function user()
    {
        return $this->belongsTo('Models\User', 'user_id', 'id')
            ->select('id', 'username', 'email')
            ->with('avatar');
    }
    
    public function attachments()
    {
        return $this->hasMany('Models\Attachment', 'foreign_id', 'id')
            ->where('class', 'JobApplicationAttachment');
    }
}