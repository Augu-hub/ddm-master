<?php
namespace App\Domain\Catalog\Actions;

use App\Models\Master\Module;
use App\Domain\Catalog\DTOs\ModuleDTO;
use App\Support\DtoValue;

class RegisterModule
{
    public function execute(ModuleDTO $dto): Module
    {
        $code           = DtoValue::get($dto, ['code']);
        $serviceId      = (int) DtoValue::get($dto, ['service_id','serviceId']);
        $name           = DtoValue::get($dto, ['name','label']);
        $icon           = DtoValue::get($dto, ['icon']);
        $routePrefix    = DtoValue::get($dto, ['route_prefix','routePrefix'], 'm');
        $entryRouteName = DtoValue::get($dto, ['entry_route_name','entryRouteName']);
        $routeWebFile   = DtoValue::get($dto, ['route_web_file','routeWebFile']);
        $routeApiFile   = DtoValue::get($dto, ['route_api_file','routeApiFile']);
        $sort           = (int) DtoValue::get($dto, ['sort'], 0);
        $isActive       = (bool) DtoValue::get($dto, ['is_active','active','enabled'], true);

        return Module::updateOrCreate(
            ['code' => $code],
            [
                'service_id'       => $serviceId,
                'name'             => $name,
                'icon'             => $icon,
                'route_prefix'     => $routePrefix,
                'entry_route_name' => $entryRouteName,
                'route_web_file'   => $routeWebFile,
                'route_api_file'   => $routeApiFile,
                'sort'             => $sort,
                'is_active'        => $isActive,
            ]
        );
    }
}
