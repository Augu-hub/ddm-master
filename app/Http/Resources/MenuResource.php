<?php
// app/Http/Resources/MenuResource.php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource {
  public function toArray($request){
    return [
      'id'=>$this->id,'key'=>$this->key,'label'=>$this->label,'type'=>$this->type,
      'icon'=>$this->icon,'url'=>$this->url,'route_name'=>$this->route_name,'target'=>$this->target,
      'sort'=>$this->sort,'visible'=>(bool)$this->visible,
      'service'=>new ServiceResource($this->whenLoaded('service')),
      'module'=>new ModuleResource($this->whenLoaded('module')),
      'children'=>MenuResource::collection($this->whenLoaded('children')),
      'badge'=>$this->badge_json,'tooltip'=>$this->tooltip_json,'meta'=>$this->meta_json,
    ];
  }
}
