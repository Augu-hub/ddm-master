<?php
// config/diaddem.icons.php

return [

    // Icônes par SERVICE (clé = code du service)
    'services' => [
        'admin'   => 'ti ti-settings',
        'govrisk' => 'ti ti-alert-triangle',
        'org'     => 'ti ti-sitemap',
        'audit'   => 'ti ti-checklist',
    ],

    // Icônes par MODULE (clé = code du module)
    'modules' => [
        'param.projects' => 'ti ti-adjustments',
        'process.core'   => 'ti ti-flow-branch',
        'risk.core'      => 'ti ti-alert-triangle',
        'audit.core'     => 'ti ti-clipboard-check',
    ],

    // Icônes par MENU (clé = key du menu)
    'menus' => [
        'seed-root'                  => 'ti ti-database-cog',
        'dashboard'                  => 'ti ti-layout-dashboard',

        // PARAM
        'param'                      => 'ti ti-adjustments',
        'param-core'                 => 'ti ti-settings-cog',
        'param-home'                 => 'ti ti-home',
        'param-registry'             => 'ti ti-folders',
        'param-models'               => 'ti ti-template',
        'param-reports'              => 'ti ti-report',
        'param-settings'             => 'ti ti-settings',
        'project'                    => 'ti ti-folders',
        'entity'                     => 'ti ti-building-community',
        'process'                    => 'ti ti-sitemap',
        'function'                   => 'ti ti-components',
        'charts'                     => 'ti ti-chart-infographic',
        'entity_chart'               => 'ti ti-building-skyscraper',
        'function_chart'             => 'ti ti-hierarchy',
        'distribution'               => 'ti ti-arrows-shuffle',
        'charts_entity'              => 'ti ti-chart-infographic',

        // PROCESS
        'process-module'             => 'ti ti-flow-branch',
        'process-home'               => 'ti ti-home',

        'process-file'               => 'ti ti-folder',
        'process-file-new'           => 'ti ti-file-plus',
        'process-file-open'          => 'ti ti-folder-open',
        'process-file-save'          => 'ti ti-device-floppy',
        'process-file-saveas'        => 'ti ti-files',
        'process-file-print'         => 'ti ti-printer',
        'process-file-close'         => 'ti ti-square-x',

        'process-edit'               => 'ti ti-edit',
        'process-identity'           => 'ti ti-id',
        'process-activities'         => 'ti ti-list-details',

        'process-contracts'          => 'ti ti-arrows-left-right',
        'process-modeling'           => 'ti ti-chart-flow',

        'process-evaluation'         => 'ti ti-clipboard-heart',
        'process-eval-criticite'     => 'ti ti-activity-heartbeat',
        'process-eval-amdec'         => 'ti ti-alert-triangle',
        'process-eval-raci'          => 'ti ti-users-group',
        'process-eval-idea'          => 'ti ti-user-cog',

        'process-reports'            => 'ti ti-report-analytics',
        'process-reports-list'       => 'ti ti-table',
        'process-reports-criticality'=> 'ti ti-flame',
        'process-reports-maturity'   => 'ti ti-chart-bar',
        'process-reports-motricity'  => 'ti ti-topology-star',
        'process-reports-raci'       => 'ti ti-layout-grid',
        'process-reports-idea'       => 'ti ti-grid-dots',

        // RISK
        'risk-module'                => 'ti ti-alert-triangle',
        'risk-home'                  => 'ti ti-home',
        'risk-registry'              => 'ti ti-clipboard-list',
        'risk-models'                => 'ti ti-layout-grid',
        'risk-reports'               => 'ti ti-report-analytics',
        'risk-settings'              => 'ti ti-adjustments',

        // AUDIT
        'audit-module'               => 'ti ti-checklist',
        'audit-home'                 => 'ti ti-home',
        'audit-registry'             => 'ti ti-notebook',
        'audit-models'               => 'ti ti-template',
        'audit-reports'              => 'ti ti-report-search',
        'audit-settings'             => 'ti ti-settings',
    ],

    // Fallback global si rien trouvé
    'fallback' => 'ti ti-apps',
];
