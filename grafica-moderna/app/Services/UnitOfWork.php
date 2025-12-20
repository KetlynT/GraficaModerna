<?php

namespace App\Services;

// Apenas um wrapper simples para simular o UoW do C#
// No Laravel, o Eloquent já é um UoW, mas mantemos a estrutura para portabilidade.
class UnitOfWork
{
    // Propriedade mágica para acessar models como propriedades
    public function __get($name)
    {
        // Mapeia _uow.EmailTemplates para App\Models\EmailTemplate
        if ($name === 'EmailTemplates') {
            return new \App\Repositories\EmailTemplateRepository(); // Ou retornar o Model direto se preferir
        }
        
        return null;
    }
    
    // Métodos de transação (Opcionais se usar DB::transaction direto nos services)
    public function beginTransaction() { \Illuminate\Support\Facades\DB::beginTransaction(); }
    public function commit() { \Illuminate\Support\Facades\DB::commit(); }
    public function rollback() { \Illuminate\Support\Facades\DB::rollBack(); }
}

// Pequena classe auxiliar interna ou arquivo separado
namespace App\Repositories;

class EmailTemplateRepository
{
    public function getAllAsync() { return \App\Models\EmailTemplate::all(); }
    public function getByIdAsync($id) { return \App\Models\EmailTemplate::find($id); }
    public function update($model) { $model->save(); }
}