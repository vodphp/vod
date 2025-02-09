<?php

return [
    'output_json_schema' => true,
    'output_stubs' => true,
    'auto_discover_types' => [
        app_path(),
    ],
    'output_json_schema_location' => resource_path('json-schemas'),
    'output_stubs_location' => base_path('stubs/Vod'),
];
