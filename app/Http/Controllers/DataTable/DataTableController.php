<?php

namespace App\Http\Controllers\DataTable;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Database\QueryException;

abstract class DataTableController extends Controller
{
    protected $allowCreation = true;

    protected $allowDeletion = true;

    protected $builder;

    public function __construct()
    {
        $builder = $this->builder();

        if(!$builder instanceof Builder)
        {
            throw new Exception('Entity builder not instance of Builder');
        }

        $this->builder = $builder;
    }

    abstract public function builder();

    public function index()
    {
        return response()->json([
            'data' => [
                'table' => $this->builder->getModel()->getTable(),
                'displayable' => array_values($this->getDisplayableColumns()),
                'updatable' => array_values($this->getUpdatableColumns()),
                'custom_columns' => $this->getCustomColumnNames(),
                'records' => $this->getRecords(),
                'allow' => [
                    'create' => $this->allowCreation,
                    'delete' => $this->allowDeletion,
                ],

            ]
        ]);
    }

    public function update($id,Request $request)
    {
        $this->builder->find($id)->update($request->only($this->getUpdatableColumns()));
        return $request->all();
    }

    public function store(Request $request)
    {
        if(!$this->allowCreation) return;

        return $this->builder->create($request->only($this->getUpdatableColumns()));
    }

    public function destroy($ids,Request $request)
    {
        if(!$this->allowDeletion) return;
        $this->builder->whereIn('id',explode(',',$ids))->delete();
    }

    public function getDisplayableColumns()
    {
        return array_diff($this->getDatabaseColumnNames(),$this->builder->getModel()->getHidden());
    }

    public function getUpdatableColumns()
    {
        return $this->getDisplayableColumns();
    }

    public function getCustomColumnNames()
    {
        return [];
    }

    protected function getDatabaseColumnNames()
    {
        return Schema::getColumnListing($this->builder->getModel()->getTable());
    }

    protected function getRecords()
    {
        $builder = $this->builder;

        if($this->hasSearchQuery())  
        {
            $builder = $this->buildSearch($builder);
        }   

        try {
            return $builder->limit(request()->limit)->orderBy('id','asc')->get($this->getDisplayableColumns());
        } 
        catch(QueryException $exception)
        {
            return [];
        }
    }

    protected function hasSearchQuery()
    {
        return count(array_filter(request()->only(['value','operator','column']))) === 3;
    }

    protected function buildSearch(Builder $builder)
    {
        $queryParts = $this->resolveQueryParts(request()->operator,request()->value);
        
        return $builder->where(request()->column,$queryParts['operator'],$queryParts['value']);
    }

    protected function resolveQueryParts($operator,$value)
    {
       
        return Arr::get([
            'equals' => [
                'operator' => '=',
                'value' => $value
            ],
            'contains' => [
                'operator' => 'LIKE',
                'value' => "%{$value}%"
            ],
            'starts_with' => [
                'operator' => 'LIKE',
                'value' => "{$value}%"
            ],
            'ends_with' => [
                'operator' => 'LIKE',
                'value' => "%{$value}"
            ],
            'greater_than' => [
                'operator' => '>',
                'value' => $value
            ],
            'less_than' => [
                'operator' => '<',
                'value' => $value
            ],
        ],$operator);
    }
}
