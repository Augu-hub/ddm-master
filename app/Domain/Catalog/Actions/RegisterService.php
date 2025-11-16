<?php
namespace App\Domain\Catalog\Actions;

use App\Models\Master\Service;
use App\Domain\Catalog\DTOs\ServiceDTO;
use App\Support\DtoValue;

class RegisterService
{
    public function execute(ServiceDTO $dto): Service
    {
        $code        = DtoValue::get($dto, ['code']);
        $name        = DtoValue::get($dto, ['name','label']);
        $icon        = DtoValue::get($dto, ['icon']);
        $description = DtoValue::get($dto, ['description','desc']);
        $basePath    = DtoValue::get($dto, ['base_path','basePath']);
        $isActive    = (bool) DtoValue::get($dto, ['is_active','active','enabled'], true);

        return Service::updateOrCreate(
            ['code' => $code],
            [
                'name'        => $name,
                'icon'        => $icon,
                'description' => $description,
                'base_path'   => $basePath,
                'is_active'   => $isActive,
            ]
        );
    }
}
