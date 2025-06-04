<?php

namespace Modules\Wpbox\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\CompanyScope;

class Template extends Model
{
    
    protected $table = 'wa_templates';
    public $guarded = [];
    protected static function booted(){
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model){
           $company_id=session('company_id',null);
            if($company_id){
                $model->company_id=$company_id;
            }
        });
    }
}
