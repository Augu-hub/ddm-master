<?php

namespace App\Domain\Catalog\DTOs;

use App\Models\Master\Module;

class ModuleDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $entry_route,
        public ?string $service_name,
    ) {}

    public static function fromModel(Module $m): self
    {
        return new self(
            code: $m->code,
            name: $m->name,
            entry_route: $m->entry_route_name,
            service_name: optional($m->service)->name,
        );
    }

    public function toArray(): array
    {
        return [
            'code'         => $this->code,
            'name'         => $this->name,
            'entry_route'  => $this->entry_route,
            'service'      => ['name' => $this->service_name],
        ];
    }
}
