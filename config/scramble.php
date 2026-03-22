<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * The path prefix to match API routes. Routes starting with this prefix
     * are included in the generated documentation.
     */
    'api_path' => 'api',

    /*
     * The API domain. Null means any domain.
     */
    'api_domain' => null,

    /*
     * The path where the generated OpenAPI spec can be exported.
     * Set to null to disable file export.
     */
    'export_path' => null,

    /*
     * API metadata used in the OpenAPI info object.
     */
    'info' => [
        'version' => env('APP_VERSION', '1.0.0'),
        'description' => 'REST API for Notifyr — manage recurring notifications and record actions.',
    ],

    /*
     * Servers list. When null, Scramble auto-detects based on the current request.
     */
    'servers' => null,

    /*
     * Middleware applied to the documentation routes (/docs/api and /docs/api.json).
     * RestrictedDocsAccess restricts access to non-production environments by default.
     */
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    /*
     * Extensions allow customising the generated OpenAPI document.
     */
    'extensions' => [],
];
