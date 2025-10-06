<?php

// return [
//     'models' => [
//         'permission' => App\Models\Master\Permission::class,
//         'role'       => App\Models\Master\Role::class,
//     ],

//     'table_names' => [
//         'roles'                 => 'roles',
//         'permissions'           => 'permissions',
//         'model_has_permissions' => 'model_has_permissions',
//         'model_has_roles'       => 'model_has_roles',
//         'role_has_permissions'  => 'role_has_permissions',
//     ],

//     // IMPORTANT : on utilise le multi-tenant via 'tenant_id'
//     // 'teams' => true,
//     // 'team_foreign_key' => 'tenant_id',
//     'teams' => true,
//     'team_foreign_key' => 'team_id',

//     'display_permission_in_exception' => false,
//     'enable_wildcard_permission'      => false,
// ];

return [
    'enabled' => env('PERMISSIONS_ENABLED', true),
];
