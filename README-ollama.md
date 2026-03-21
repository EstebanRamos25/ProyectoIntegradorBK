# Chatbot con Ollama (Proveedor Local Gratuito)

Este proyecto ahora usa **Ollama** como proveedor por defecto (`CHAT_PROVIDER=ollama`) para el asistente interno CERABOT.

## 1. Variables de entorno necesarias
Añade al archivo `.env` (o actualiza) lo siguiente:
```
CHAT_PROVIDER=ollama
OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3
OLLAMA_TIMEOUT=120
# (Opcional) Ajustes de rendimiento (útil en CPU/RAM limitada)
OLLAMA_NUM_CTX=1024
OLLAMA_NUM_PREDICT=60
OLLAMA_KEEP_ALIVE=10m
# (Opcional) Para volver a OpenAI:
# CHAT_PROVIDER=openai
# OPENAI_API_KEY=sk-xxx
# OPENAI_MODEL=gpt-4.1-mini
```
Luego limpia caché de config:
```
php artisan config:clear
```

## 2. Instalación de Ollama
### Linux (Debian/Ubuntu)
```bash
curl -fsSL https://ollama.com/install.sh | sh
# Verificar
ollama --version
```
Iniciar/activar el servicio (recomendado):
```bash
sudo systemctl enable --now ollama
sudo systemctl status ollama --no-pager
```
Probar que Ollama responde en el puerto por defecto:
```bash
curl -sS http://127.0.0.1:11434/api/version
```
Si necesitas desinstalar:
```
sudo systemctl stop ollama
sudo rm -rf /usr/local/bin/ollama /usr/share/ollama
```

### Windows
1. Descarga el instalador desde: https://ollama.com/download
2. Ejecuta el instalador (crea el servicio Ollama automáticamente).
3. Abre PowerShell y verifica:
```powershell
ollama --version
```

## 3. Descargar el modelo
Con cualquiera de los sistemas:
```bash
ollama pull llama3
```
Puedes listar modelos instalados:
```bash
ollama list
```

Modelos alternativos (más pequeños o distintos):
- `llama3:instruct`
- `mistral`
- `phi3:mini`

Cambiar modelo: ajusta `OLLAMA_MODEL` en `.env`.

## 4. Probar el endpoint del chatbot
```bash
curl -X POST http://127.0.0.1:8000/api/chatbot \
  -H 'Content-Type: application/json' \
  -d '{"message":"hola","module":"productos"}'
```
Respuesta esperada: texto generado por el modelo local.

Diagnóstico rápido (incluido en el proyecto):
```bash
php artisan chatbot:ollama-check
```

## 5. Comportamiento de verificación
Antes de enviar la pregunta a Ollama, el servicio comprueba si el modelo existe con `GET /api/tags`. Si falta, devuelve mensaje indicando: `ollama pull <modelo>`.

## 6. Volver a OpenAI (opcional)
1. Cambiar `CHAT_PROVIDER=openai` en `.env`.
2. Añadir `OPENAI_API_KEY` válido y (opcional) `OPENAI_MODEL`.
3. `php artisan config:clear`.

## 7. Seguridad
Actualmente `/api/chatbot` está sin CSRF para pruebas (usa `withoutMiddleware(VerifyCsrfToken)`). Cuando quieras reinstaurar seguridad:
- Quita `->withoutMiddleware(...)` en `routes/web.php`.
- Añade `<meta name="csrf-token" content="{{ csrf_token() }}">` al layout principal si no existe.
- Envía el token desde fetch (ya implementado en `platform.js`).

## 8. Posibles mejoras futuras
- Fallback automático: si OpenAI falla o no hay crédito → usar Ollama.
- Cache de respuestas frecuentes para reducir latencia.
- Rate limiting (throttle) para `/api/chatbot`.
- Sanitización adicional de HTML en la respuesta antes de mostrarla.

## 9. Errores comunes
| Mensaje | Causa | Acción |
|---------|-------|--------|
| `El modelo 'llama3' no está disponible` | No se ha hecho pull | `ollama pull llama3` |
| `No se pudo conectar a Ollama` | Servicio no iniciado | Iniciar/reinstalar Ollama |
| `Error Ollama (404)` | Endpoint incorrecto o versión antigua | Actualizar Ollama |
| `Error OpenAI (429)` | Cuota agotada | Agregar método de pago o usar Ollama |

Notas para contenedores (Docker):
- Si Laravel/PHP corre dentro de un contenedor, `OLLAMA_URL=http://127.0.0.1:11434` apunta al contenedor, no al host.
- En ese caso, configura `OLLAMA_URL` con el host accesible desde el contenedor (ej. `http://host.docker.internal:11434` o la IP del host) y ejecuta `php artisan config:clear`.

## 10. Licencia y costos
- Ollama y los modelos open-source se ejecutan localmente: sin costo por token (solo recursos de tu máquina).
- OpenAI requiere crédito o plan activo.

---
Listo: entorno preparado para usar Ollama como proveedor principal del chatbot.
