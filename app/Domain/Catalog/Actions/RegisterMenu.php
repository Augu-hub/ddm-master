<?php
namespace App\Domain\Catalog\Actions;

use App\Models\Master\Menu;
use App\Domain\Catalog\DTOs\MenuDTO;
use App\Support\DtoValue;

class RegisterMenu
{
    public function execute(MenuDTO $dto): Menu
    {
        $key       = DtoValue::get($dto, ['key']);
        $label     = DtoValue::get($dto, ['label','name']);
        $type      = DtoValue::get($dto, ['type'], 'item');
        $icon      = DtoValue::get($dto, ['icon']);
        $url       = DtoValue::get($dto, ['url']);
        $routeName = DtoValue::get($dto, ['route_name','routeName']);
        $target    = DtoValue::get($dto, ['target']);
        $parentId  = DtoValue::get($dto, ['parent_id','parentId']);
        $sort      = (int) DtoValue::get($dto, ['sort'], 0);
        $serviceId = DtoValue::get($dto, ['service_id','serviceId']);
        $moduleId  = DtoValue::get($dto, ['module_id','moduleId']);
        $visible   = (bool) DtoValue::get($dto, ['visible'], true);
        $badge     = DtoValue::get($dto, ['badge_json','badge']);
        $tooltip   = DtoValue::get($dto, ['tooltip_json','tooltip']);
        $meta      = DtoValue::get($dto, ['meta_json','meta']);

        return Menu::updateOrCreate(
            ['key' => $key],
            [
                'label'        => $label,
                'type'         => $type,
                'icon'         => $icon,
                'url'          => $url,
                'route_name'   => $routeName,
                'target'       => $target,
                'parent_id'    => $parentId,
                'sort'         => $sort,
                'service_id'   => $serviceId,
                'module_id'    => $moduleId,
                'visible'      => $visible,
                'badge_json'   => $badge,
                'tooltip_json' => $tooltip,
                'meta_json'    => $meta,
            ]
        );
    }
}
