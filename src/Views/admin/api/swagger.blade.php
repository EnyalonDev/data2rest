@extends('layouts.main')

@section('title', 'API Documentation')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui.css">
    <style>
        /* Swagger UI Integration Tweaks */
        #swagger-ui {
            background: #fff;
            border-radius: 0.75rem; /* rounded-xl */
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Dark mode overrides if needed, but Swagger default is light. 
           Keeping it contained in a white box is safest for readability. */
    </style>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <a href="{{ $baseUrl }}admin/api" class="text-p-muted hover:text-white flex items-center gap-2 mb-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to API Dashboard
            </a>
            <h1 class="text-3xl font-black text-p-title italic tracking-tighter">
                API Reference
            </h1>
        </div>
        <div>
            <!-- Actions like Download Spec could go here -->
        </div>
    </div>

    <div id="swagger-ui"></div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-bundle.js"> </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-standalone-preset.js"> </script>
    <script>
        window.onload = function () {
            const ui = SwaggerUIBundle({
                url: "{{ $baseUrl }}admin/api/swagger/spec?db_id={{ $db_id }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout", // Changed from Standalone to Base to avoid full-screen takeover
                requestInterceptor: (req) => {
                    const apiKey = localStorage.getItem('d2r_api_key');
                    if (apiKey) {
                        req.headers['X-API-KEY'] = apiKey;
                    }
                    return req;
                }
            });
            window.ui = ui;
        };
    </script>
@endsection