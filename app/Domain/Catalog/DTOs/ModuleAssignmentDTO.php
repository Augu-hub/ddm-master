<?php
// app/Domain/Catalog/DTOs/ModuleAssignmentDTO.php
namespace App\Domain\Catalog\DTOs;
class ModuleAssignmentDTO { public function __construct(public int $module_id, public int $user_id){} }
