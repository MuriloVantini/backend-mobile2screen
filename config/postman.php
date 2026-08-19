<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Basic Configuration
    |--------------------------------------------------------------------------
    |
    | Core settings for the API documentation
    |
    */
    'name' => env('APP_NAME', 'Laravel API'),
    'description' => env('API_DESCRIPTION', 'API do Mobile2Screen para gerenciamento de alertas, dispositivos e kiosks em tempo real.'),
    'base_url' => env('APP_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | Route Filtering Configuration
    |--------------------------------------------------------------------------
    |
    | Define which routes should be included/excluded from documentation
    |
    */
    'routes' => [
        'prefix' => '',

        // Routes to explicitly include
        'include' => [
            // MUDE ISSO: Adicione os padrões que você quer capturar
            'patterns' => [
                'api/*',
            ],

            // Only routes with these middleware
            'middleware' => [],

            // Only routes from these controllers
            'controllers' => [],
        ],

        // Routes to explicitly exclude
        'exclude' => [
            // É bom excluir rotas internas do Laravel/Ignition para não poluir
            'patterns' => [
                '_ignition/*',
                'telescope/*'
            ],

            'middleware' => [],

            'controllers' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Structure
    |--------------------------------------------------------------------------
    |
    | How the documentation should be organized in Postman
    |
    */
    'structure' => [
        'folders' => [
            // Grouping strategy: 'prefix', 'nested_path', 'controller'
            'strategy' => 'controller',
            'max_depth' => 10, //  when strategy is nested_path

            // Custom name mapping for folders
            'mapping' => [
                // Example: 'admin' => 'Administration'
            ],
        ],

        /**
         * Postman request naming format.
         * Placeholders: {method}, {uri}, {controller}, {action}
         * Example: '[POST] /users' or 'UserController@store'
         */
        'naming_format' => '[{action}] {uri}',

        /**
         * Request body settings:
         * - default_body_type: 'raw' or 'formdata'
         * - default_values: preset values applied to generated request fields
         */
        'requests' => [
            'default_body_type' => 'raw',
            'default_values' => [
                'name' => fake()->name(),
                'title' => fake()->sentence(3),
                'message' => fake()->paragraph(),
                'email' => fake()->safeEmail(),
                'password' => fake()->password(8, 16),
                'company' => fake()->company(),
                'phone' => fake()->phoneNumber(),
                'notification_email' => fake()->safeEmail(),
                'notification_phone' => fake()->phoneNumber(),
                'location' => fake()->address(),
                'ip_address' => fake()->ipv4(),
                'mac_address' => strtoupper(fake()->bothify('??:??:??:??:??:??')),
                'metadata' => [
                    'source' => 'postman-doc',
                    'firmware' => fake()->numerify('1.#.#'),
                ],
                'type' => 'info',
                'status' => 'active',
                'role' => 'user',
                'color' => fake()->safeColorName(),
                'timezone' => fake()->timezone(),
                'language' => 'pt-BR',
                'theme' => fake()->randomElement(['light', 'dark', 'auto']),
                'priority' => fake()->numberBetween(0, 10),
                'duration_seconds' => fake()->numberBetween(10, 120),
                'last_active' => fake()->dateTimeBetween('-7 days', 'now')->format('Y-m-d H:i:s'),
                'plan_id' => 1,
                'user_id' => 1,
                'device_id' => 1,
                'tag_id' => 1,
                'alert_id' => 1,
                // Parâmetros usados diretamente nas URLs das rotas.
                'user' => 1,
                'device' => 1,
                'tag' => 1,
                'alert' => 1,
                'delivery' => 1,
                'plan' => 1,
                'api_key' => 1,
                // Autorização do canal privado do Laravel Reverb.
                'socket_id' => '123456.789012',
                'channel_name' => 'private-device.1',
                'simulator' => true,
                'screen' => 'kiosk',
                'token' => fake()->sha1(),
                'tags' => [1],
                'tags.*' => 1,
                'is_active' => true,
                'expires_at' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d H:i:s'),
                'from_date' => fake()->dateTimeBetween('-30 days', '-10 days')->format('Y-m-d'),
                'to_date' => fake()->dateTimeBetween('-9 days', 'now')->format('Y-m-d'),
                'days' => fake()->numberBetween(1, 30),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for API authentication documentation and examples
    |
    | Determines how authentication is handled in the generated documentation,
    | including auth type detection, protected route identification, and
    | example values for documentation purposes.
    |
    */
    'auth' => [
        // Enable authentication documentation
        'enabled' => true,

        // Supported: 'bearer', 'basic', 'api_key'
        'type' => 'bearer',

        // Where to send the auth: 'header' or 'query'
        'location' => 'header',

        // Default values (use env vars for real values)
        'default' => [
            'token' => 'your-access-token',       // For bearer auth
            'username' => 'user@example.com',      // For basic auth
            'password' => 'password',              // For basic auth
            'key_name' => 'X-API-KEY',             // For api_key auth
            'key_value' => 'your-api-key-here',    // For api_key auth
        ],

        // Middleware that indicate protected routes
        'protected_middleware' => ['auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Headers
    |--------------------------------------------------------------------------
    |
    | Headers to include with every request
    |
    */
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket / Laravel Reverb Documentation
    |--------------------------------------------------------------------------
    |
    | Postman Collection v2.1 não exporta coleções multiprotocolo. A conexão
    | WebSocket é documentada em um arquivo AsyncAPI separado, importável no
    | Postman, enquanto esta configuração continua gerando as rotas HTTP.
    |
    */
    'websocket' => [
        'specification' => storage_path('postman/reverb_websocket.asyncapi.json'),
        'host' => env('REVERB_HOST', 'localhost'),
        'port' => (int) env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http') === 'https' ? 'wss' : 'ws',
        'app_key' => env('REVERB_APP_KEY', 'your-reverb-app-key'),
        'protocol' => 7,
        'channel' => 'private-device.{deviceId}',
        'event' => 'alert.available',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    |
    | Where and how to save the generated documentation
    |
    */
    'output' => [
        'driver' => env('POSTMAN_STORAGE_DISK', 'local'),

        // Storage path for generated files
        'path' => env('POSTMAN_STORAGE_DIR', storage_path('postman')),

        // File naming pattern (date will be appended)
        'filename' => env('POSTMAN_STORAGE_FILE', 'api_collection'),
    ],
];
