# Configuración de Ax.ia (Google Gemini API)

Ax.ia es el asistente de Inteligencia Artificial integrado en SientiaMTX, potenciado por Google Gemini. Para que funcione correctamente, necesitas configurar una clave de API (API Key) desde Google AI Studio y vincularla a tu entorno.

## 1. Obtener la Clave de API de Gemini

1. Accede a [Google AI Studio](https://aistudio.google.com/) con tu cuenta de Google.
2. En el panel izquierdo, haz clic en **"Get API key"** (Obtener clave de API).
3. Haz clic en el botón **"Create API key"** (Crear clave de API).
4. Selecciona un proyecto de Google Cloud existente o crea uno nuevo para generar la clave.
5. Una vez generada, **copia la clave de API**. (Asegúrate de guardarla en un lugar seguro, no la compartas públicamente).

## 2. Configurar Ax.ia en SientiaMTX

Para activar Ax.ia, debes introducir tu clave de API en el archivo de configuración del entorno de SientiaMTX o a través de la interfaz de administración.

### Opción A: A través del Archivo `.env` (Para Administradores del Sistema)

Si administras el servidor donde se aloja SientiaMTX, puedes configurarlo directamente en el archivo de entorno:

1. Abre el archivo `.env` ubicado en la raíz de tu proyecto SientiaMTX.
2. Busca o añade la siguiente variable:
   ```env
   GEMINI_API_KEY="tu_clave_api_copiada_aqui"
   ```
3. Guarda el archivo.
4. Limpia la caché de configuración ejecutando el comando:
   ```bash
   php artisan config:clear
   ```

### Opción B: A través de la Interfaz Web (Si está habilitado)

1. Inicia sesión en SientiaMTX con una cuenta de Administrador.
2. Ve al perfil de usuario o ajustes de sistema y busca el panel de IA / Ax.ia.
3. Pega tu clave de API de Gemini en el campo indicado.
4. Guarda los cambios. El sistema validará la conexión.

## 3. Verificación

Una vez configurado, puedes probar Ax.ia intentando transcribir una nota de voz o pidiendo a la IA que desglose una tarea compleja. Si la configuración es correcta, recibirás la respuesta generada por Gemini de manera fluida.
