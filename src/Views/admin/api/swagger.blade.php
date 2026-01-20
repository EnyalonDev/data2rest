<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui.css">
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }

        .topbar {
            background-color: #1a202c;
            padding: 10px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            font-family: sans-serif;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>
    <div class="topbar">
        <span>Data2Rest API Documentation</span>
        <a href="{{ \App\Core\Config::get('base_url') }}/admin/api" class="btn-back">&larr; Back to Admin</a>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-bundle.js"> </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-standalone-preset.js"> </script>
    <script>
        window.onload = function () {
            const ui = SwaggerUIBundle({
                url: "{{ \App\Core\Config::get('base_url') }}/admin/api/swagger/spec?db_id={{ $db_id }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                requestInterceptor: (req) => {
                    // Try to get API key from localStorage if available
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
</body>

</html>