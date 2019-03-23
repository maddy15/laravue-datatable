<?php

namespace App\Http\Controllers\DataTable;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataTable\DataTableController;
use App\Plan;

class PlanController extends DataTableController
{
    protected $allowCreation = true;
    public function builder()
    {
        return Plan::query();
    }

    public function getDisplayableColumns()
    {
        return [
            'id','braintree_id','price','active'
        ];
    }

    public function getUpdatableColumns()
    {
        return [
            'braintree_id','price','active'
        ];
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'braintree_id' => 'required',
            'price' => 'required|numeric',
            'active' => 'required|boolean',
        ]);

        if(!$this->allowCreation) return;

        return $this->builder->create($request->only($this->getUpdatableColumns()));
    }

    public function update($id,Request $request)
    {
       
        $this->validate($request,[
            'braintree_id' => 'required',
            'price' => 'required|numeric',
            'active' => 'required',
        ]);

        $this->builder->find($id)->update($request->only($this->getUpdatableColumns()));
        return $request->all();
    }
}
