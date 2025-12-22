<?php

namespace App\Services;

class UnitOfWork
{
    public function __get($name)
    {
        if ($name === 'EmailTemplates') {
            return new \App\Repositories\EmailTemplateRepository();
        }
        
        return null;
    }
    
    public function beginTransaction() { \Illuminate\Support\Facades\DB::beginTransaction(); }
    public function commit() { \Illuminate\Support\Facades\DB::commit(); }
    public function rollback() { \Illuminate\Support\Facades\DB::rollBack(); }
}

namespace App\Repositories;

class EmailTemplateRepository
{
    public function getAllAsync() { return \App\Models\EmailTemplate::all(); }
    public function getByIdAsync($id) { return \App\Models\EmailTemplate::find($id); }
    public function update($model) { $model->save(); }
}