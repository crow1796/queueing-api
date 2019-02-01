<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Repositories\Contracts\TableContract;

class UserRepository extends Repository implements TableContract{
	public function model(){
		return '\App\User';
	}
	
	public function forTable(\Illuminate\Http\Request $request){
		$query = $this->model->whereIs($request->role)
					->with('department');
		if($request->department_id){
			$query = $query->where('department_id', $request->department_id);
		}
		dd($query->get());
		return [
			'result' => $query->get(),
		];
	}

	public function unverifiedUser($request){
		return $this->model
                    ->whereNull('verified_at')
					->where('uuid', '=', $request->uuid)
                    ->first();
	}
	
}