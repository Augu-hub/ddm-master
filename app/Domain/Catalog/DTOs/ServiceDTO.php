<?php
// app/Domain/Catalog/DTOs/ServiceDTO.php
namespace App\Domain\Catalog\DTOs;
class ServiceDTO {
  public function __construct(
    public string $code,
    public string $name,
    public ?string $icon=null,
    public ?string $description=null,
    public ?string $base_path=null,
    public bool $is_active=true,
  ){}
}
