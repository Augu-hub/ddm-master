<?php
// app/Http/Resources/ServiceResource.php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource {
  public function toArray($request){
    return [
      'id'=>$this->id,'code'=>$this->code,'name'=>$this->name,'icon'=>$this->icon,
      'description'=>$this->description,'is_active'=>(bool)$this->is_active,
      'modules'=>ModuleResource::collection($this->whenLoaded('modules')),
    ];
  }
}
