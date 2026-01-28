Guía Maestra: Integración de Google OAuth en Proyectos Frontend
Esta guía detalla el proceso exacto para conectar un nuevo proyecto Frontend (React/Vite) con la autenticación centralizada de Data2Rest.

1. Estrategia de Client IDs (Google Cloud) ☁️
¿Puedo usar múltiples Client IDs con el mismo Backend? SÍ. Totalmente. Data2Rest está diseñado para eso.

Cada proyecto en Data2Rest (ID: 1, ID: 2, ID: 3...) tiene su propia configuración de "Client ID" y "Secret".
Esto significa que el proyecto "Veterinaria" (ID: 2) puede usar una credencial de Google totalmente diferente a la de "MiApp" (ID: 3).
Recomendación de Escalabilidad
Grupo de Proyectos (1-50): Crea una sola credencial OAuth en Google llamada "Grupo 1".
Agrega todas las URLs (https://veterinaria.com, https://miapp.cc) a esa misma credencial.
Usa el MISMO Client ID en la configuración de Data2Rest para todos esos proyectos.
Siguientes 50: Cuando llenes el cupo de URLs, crea una nueva credencial "Grupo 2".
Usa este NUEVO Client ID para los siguientes proyectos.
No afecta a los anteriores. Data2Rest sabrá cuál usar porque se lo configuras a cada proyecto individualmente.
2. Paso a Paso: Integrar Nuevo Proyecto (Ej: Veterinaria) 🛠️
A. Configuración en Servidores
Google Cloud Console:

Ve a tus Credenciales.
Orígenes JS: Agrega https://tuveterinaria.com (y localhost).
Redirect URI: Agrega https://tuveterinaria.com/auth/callback (y localhost).
Nota: Si estás reusando el Client ID de "MiApp", solo agrégalo a la lista.
Data2Rest Admin (/admin/projects):

Edita el Proyecto ID: 2 (Veterinaria).
External Auth: Enabled ✅.
Tokens: Copia el Client ID y Secret de Google.
Allowed Origins: https://tuveterinaria.com.
Redirect URI: https://tuveterinaria.com/auth/callback.
B. Código Frontend (React/Vite)
1. Variables de Entorno (.env)

VITE_API_URL=https://d2r.nestorovallos.com
VITE_PROJECT_ID=2  # <--- ID DEL PROYECTO
2. Login (src/pages/auth/Login.tsx) El botón debe redirigir al Backend forzando la URL de retorno:

const handleLogin = () => {
    // URL EXACTA registrada en Google
    const redirectUri = window.location.origin + '/auth/callback';
    
    // URL del Iniciador Backend
    const authUrl = `${import.meta.env.VITE_API_URL}/api/projects/${import.meta.env.VITE_PROJECT_ID}/auth/google?redirect_uri=${encodeURIComponent(redirectUri)}`;
    
    window.location.href = authUrl;
};
3. Callback (src/pages/auth/GoogleCallback.tsx) El verificador debe enviar code Y redirect_uri:

// ... imports ...
const GoogleCallback = () => {
    // ... hooks ...
    useEffect(() => {
        const exchange = async () => {
            const code = searchParams.get('code');
            const redirectUri = window.location.origin + '/auth/callback'; // LA MISMA
            const res = await api.post(`/api/projects/${projectId}/auth/google/callback`, {
                code,
                redirect_uri: redirectUri // <--- CRÍTICO
            });
            
            // OJO: La respuesta viene anidada en data.data
            const { token, user } = res.data.data; 
            
            setAuth(user, token);
            navigate('/dashboard');
        };
        exchange();
    }, []);
    // ... render ...
}
4. Configuración del Servidor Web (vercel.json o 
.htaccess
) Para evitar errores 404 al recargar y bloqueos de seguridad:

Para Vercel (vercel.json):

{
  "rewrites": [{ "source": "/(.*)", "destination": "/index.html" }],
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        { "key": "Content-Security-Policy", "value": "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; connect-src 'self' https:; img-src 'self' data: https:;" }
      ]
    }
  ]
}
¡Listo! Siguiendo estos pasos, puedes integrar infinitos proyectos. 🚀