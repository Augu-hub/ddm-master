<?php

namespace App\Domain\Catalog\DTOs;

use App\Models\Master\Menu;

class MenuDTO
{
    public function __construct(
        public string $key,
        public ?string $label,
        public ?string $icon,
        public ?string $url,
        public ?string $route,
        public bool $isTitle,
        public bool $isDivider,
        public ?array $badge,
        public ?array $tooltip,
        /** @var MenuNodeDTO[] */
        public array $children = [],
    ) {}

    public static function fromModel(Menu $m): self
    {
        return new self(
            key:       $m->key,
            label:     $m->label,
            icon:      $m->icon,
            url:       $m->url,
            route:     $m->route_name,
            isTitle:   $m->type === 'title',
            isDivider: $m->type === 'divider',
            badge:     $m->badge_json,
            tooltip:   $m->tooltip_json,
            children:  [], // on hydrate récursivement dans le service
        );
    }

    public function toArray(): array
    {
        return [
            'key'       => $this->key,
            'label'     => $this->label,
            'icon'      => $this->icon,
            'url'       => $this->url,
            'route'     => $this->route,
            'isTitle'   => $this->isTitle,
            'isDivider' => $this->isDivider,
            'badge'     => $this->badge,
            'tooltip'   => $this->tooltip,
            'children'  => array_map(fn(self $n) => $n->toArray(), $this->children),
        ];
    }
}
