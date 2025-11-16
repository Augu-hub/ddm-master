<?php

// app/Http/Resources/ModuleResource.php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource {
  public function toArray($request){
    return [
      'id'=>$this->id,'code'=>$this->code,'name'=>$this->name,'icon'=>$this->icon,
      'sort'=>$this->sort,'route_prefix'=>$this->route_prefix,
      'entry_route'=>$this->entry_route_name,'is_active'=>(bool)$this->is_active,
      'service'=>new ServiceResource($this->whenLoaded('service')),
    ];
  }
}
