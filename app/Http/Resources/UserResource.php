<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'pseudo'=>$this->pseudo,
            'phone'=>$this->phone,
            'location'=>$this->location,
            'activity_type'=>$this->activity_type,
            'password'=>$this->password,
            'registry_url'=>$this->registry_url,
            'identity_url'=>$this->identity_url,
            'company_name'=>$this->canSeeCompanyName($request)?$this->company_name:null

        ];
    }

    private function canSeeCompanyName($request)
    {
        $user=$request->user();

        if(!$user){
            return false;
        }
        if($user->id === $this->id){
            return true;
        }
        if($user->role === 1){
            return true;
        }
    }
}
