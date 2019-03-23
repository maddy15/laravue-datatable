<?php

namespace App\Http\Controllers\DataTable;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataTable\DataTableController;
use App\User;

class UserController extends DataTableController
{
    protected $allowCreation = false;

    protected $allowDeletion = false;

    public function builder()
    {
        return User::query();
    }

    public function getCustomColumnNames()
    {
        return [
            'name' => 'Full Name',
            'email' => 'Email Address',
        ];
    }

    public function getDisplayableColumns()
    {
        return [
            'id','name','email','created_at'
        ];
    }

    public function getUpdatableColumns()
    {
        return [
            'name','email','password'
        ];
    }

    public function store(Request $request)
    {
        
        $this->validate($request,[
            'email' => 'required|email',
            'name' => 'required',
            'password' => 'required',
        ]);

        if(!$this->allowCreation) return;
        
        // $this->builder->create($request->only($this->getUpdatableColumns()));

        return $this->builder->getModel();
        // $this->builder()->create([
        //     'name' => 'madz',
        //     'email' => 'madz@yahoo.com',
        //     'password' => '123456',
        //     'email_verified_at' => '2019-03-19 13:45:47'
        // ]);
    }
    
    public function update($id,Request $request)
    {

        return $this->builder->getModel();
        $this->validate($request,[
            'email' => 'required|email',
            'name' => 'required',
        ]);
        
        $this->builder->find($id)->update($request->only($this->getUpdatableColumns()));
        return $request->all();
    }
}
