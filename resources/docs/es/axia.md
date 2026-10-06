# 🤖 Asistente Ax.ia (Google Gemini API) — SientiaMTX (v1.2.0)

**Ax.ia** es el copiloto de Inteligencia Artificial integrado transversalmente en todo el ecosistema de SientiaMTX. Potenciado por los modelos fundacionales de Google Gemini, Ax.ia asiste a los equipos en la redacción, transcripción de audio, toma de decisiones y transferencia directa de contenidos a las tareas y documentos de trabajo.

---

## 🔑 1. Obtener la Clave de API de Gemini

Para habilitar Ax.ia, necesitas una API Key de Google Gemini:

1. Accede a la consola de [Google AI Studio](https://aistudio.google.com/) con tu cuenta de Google.
2. En el menú de navegación, haz clic en **"Get API key"** (Obtener clave de API).
3. Pulsa sobre el botón **"Create API key"** (Crear clave de API).
4. Elige un proyecto existente de Google Cloud o crea uno nuevo para asociar la clave.
5. Copia la clave generada y mantenla a buen recaudo.

---

## ⚙️ 2. Jerarquía de Configuración de Claves

SientiaMTX ofrece tres niveles de asignación de claves API para maximizar la flexibilidad:

### Nivel 1: Clave Global de Servidor (`.env`)
Recomendado para organizaciones que sufragan centralizadamente el uso de la IA para todos sus usuarios:
1. Edita el archivo `.env` en la raíz de la instalación de SientiaMTX:
   ```env
   GEMINI_API_KEY="tu_clave_api_de_google_ai_studio"
   ```
2. Refresca la configuración en Laravel:
   ```bash
   php artisan config:clear
   ```

### Nivel 2: Clave por Equipo de Trabajo
Los equipos pueden aislar su cuota y costes introduciendo una clave de equipo desde **Ajustes de Equipo ➔ Inteligencia Artificial**. La clave se almacena cifrada en base de datos.

### Nivel 3: Clave Personal de Usuario
Cualquier miembro puede configurar su propia clave personal desde **Perfil ➔ Integraciones de IA**, lo que le permite disponer de Ax.ia con su cuota individual independientemente de las opciones del servidor.

---

## 🧠 3. Modelos Compatibles y Cascada Fallback

SientiaMTX integra una cadena inteligente de selección de modelos para garantizar respuestas rápidas y alta tolerancia a límites de cuota:

| Modelo | Propósito Principal | Rendimiento |
|---|---|---|
| **Gemini 2.0 Flash** / **1.5 Flash** | Modelo por defecto para chat general, desgloses rápidos y respuestas en tiempo real. | Ultrarrápido, baja latencia |
| **Gemini 1.5 Pro** | Tareas complejas de razonamiento analítico, análisis documental extenso y síntesis de actas. | Alta capacidad contextual |

Si la API de Google reporta una saturación temporal o error 429 en un modelo, el servicio activa automáticamente la cadena de *fallback* para mantener la continuidad operativa sin interrumpir al usuario.

---

## 🚀 4. Transferencia Inteligente de Contenidos (AI Content Transfer)

A diferencia de un chatbot convencional aislado, Ax.ia está profundamente conectado con el modelo de datos de SientiaMTX:

```
[Conversación con Ax.ia]
         │
         ├──▶ Transferir como Descripción de Actividad
         ├──▶ Inyectar como Nota de Equipo (Desarrollo interno)
         ├──▶ Añadir como Nuevo Capítulo en Documento (OnlyOffice/Markdown)
         └──▶ Crear Hilo en el Foro de Discusión
```

- **Inyección en Documentos**: Al generar textos extensos, puedes inyectarlos con un clic como un nuevo capítulo estructurado dentro de cualquier actividad de tipo Documento.
- **Auto-creación de Actividades**: Ax.ia es capaz de extraer automáticamente títulos, descripciones, niveles de urgencia y fechas límite para crear tareas listas para ejecutar.
- **Transcripción de Voz Instantánea**: Sube o graba notas de voz; el modelo procesa el audio y genera el texto estructurado en el portapapeles o en una nota rápida.

---

## 🛠️ 5. Verificación y Diagnóstico

Para comprobar el correcto funcionamiento de Ax.ia:
1. Abre el panel lateral de Ax.ia desde el icono flotante de la esquina inferior.
2. Envía un mensaje de prueba (por ejemplo: *"Genera 3 subtareas para organizar un taller de innovación"*).
3. Pulsa sobre el botón de transferencia para verificar que el contenido se traslada correctamente al elemento seleccionado.
