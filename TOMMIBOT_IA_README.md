# 🤖 Tommibot con IA - Guía de Configuración Rápida

## 📋 Resumen de Mejoras

Tommibot ha sido mejorado significativamente con las siguientes capacidades:

### ✨ Nuevas Características

1. **Inteligencia Artificial con Google Gemini (Gratis)**
   - Respuestas naturales y contextualizadas
   - Comprensión de lenguaje natural (NLP)
   - Respuestas tanto del sistema como generales
   - Cache inteligente para optimizar uso de API

2. **Detección Automática de Roles**
   - Adapta respuestas según usuario (Profesor/Admin/Encargado)
   - Contexto personalizado por rol
   - Sugerencias específicas según permisos

3. **Comandos de Voz Ejecutables**
   - Navegación por voz ("Ir a reservas")
   - Descarga de PDFs por voz
   - Comandos de ayuda
   - Sin saludos repetitivos

4. **Base de Conocimientos Expandida**
   - Más intenciones reconocidas
   - FAQs integradas
   - Manual completo del sistema
   - Información por rol

5. **Respuestas Mejoradas**
   - Formato con saltos de línea
   - Emojis contextuales
   - Pasos numerados claros
   - Tono juvenil y amable

---

## ⚡ Configuración en 5 Minutos

### Paso 1: Obtener API Key de Google Gemini (GRATIS)

1. **Ir a Google AI Studio:**
   ```
   https://makersuite.google.com/app/apikey
   ```

2. **Iniciar sesión** con tu cuenta de Google

3. **Crear API Key:**
   - Click en "Create API Key"
   - Copiar la clave generada (empieza con `AIza...`)

4. **Guardar la clave** en un lugar seguro

### Paso 2: Configurar en el Sistema

1. **Abrir archivo de configuración:**
   ```
   app/config/ai_config.php
   ```

2. **Pegar tu API Key:**
   ```php
   'gemini' => [
       'api_key' => 'AIza...TU_CLAVE_AQUI', // ← Pegar aquí
       'enabled' => true,
   ],
   ```

3. **Guardar el archivo**

### Paso 3: Verificar Funcionamiento

1. **Abrir el sistema** en tu navegador
2. **Login** con tu usuario
3. **Abrir Tommibot** (botón flotante o menú)
4. **Hacer pregunta general:**
   ```
   ¿Cuál es la capital de Perú?
   ```
5. **Si responde correctamente** → ✅ IA activa

---

## 🎯 Uso de Tommibot Mejorado

### Por Texto

**Preguntas sobre el sistema:**
```
- ¿Cómo hago una reserva?
- No me llega el SMS
- ¿Puedo cancelar una reserva?
- Muéstrame el historial
```

**Preguntas generales:**
```
- ¿Qué hora es?
- ¿Cuál es la capital de Francia?
- Cuéntame un chiste
- ¿Qué clima hace hoy?
```

### Por Voz (Comandos Ejecutables)

1. **Click en botón "🎙️ Hablar"**
2. **Decir comando:**

**Navegación:**
```
- Ir a reservas
- Abre préstamos
- Muéstrame historial
- Cambiar contraseña
- Ver reportes (Admin)
- Gestionar usuarios (Admin)
```

**Acciones:**
```
- Descargar PDF
- ¿Qué puedes hacer?
```

**Conversación:**
```
- Cualquier pregunta del sistema
- Preguntas generales
```

---

## 📊 Características Técnicas

### Arquitectura

```
┌─────────────────┐
│   Frontend      │
│  (tommibot.js)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   API Endpoint  │
│ (Tommibot_chat) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Controller     │
│ (TommibotCtrl)  │
└────┬────────┬───┘
     │        │
     ▼        ▼
┌─────────┐ ┌────────────┐
│   KB    │ │ AIService  │
│  Local  │ │  (Gemini)  │
└─────────┘ └────────────┘
```

### Flujo de Respuesta

1. **Usuario envía mensaje**
2. **Detección de tipo:**
   - ¿Es pregunta del sistema?
   - ¿Es pregunta general?
3. **Si es del sistema:**
   - Buscar en KB local
   - Mejorar con IA (si está activa)
   - Agregar contexto del rol
4. **Si es general:**
   - Usar IA directamente
   - Fallback a mensaje genérico
5. **Responder al usuario**

### Cache de Respuestas

- **Duración:** 1 hora
- **Tamaño máximo:** 100 respuestas
- **Beneficio:** Reduce llamadas a API
- **Optimización:** Reutiliza respuestas idénticas

---

## 🔧 Archivos Modificados/Creados

### Nuevos Archivos

1. **`app/lib/AIService.php`**
   - Servicio de IA
   - Conexión con Google Gemini
   - Cache de respuestas
   - Detección de intenciones

2. **`app/config/ai_config.php`**
   - Configuración de API
   - Parámetros del bot
   - Configuración de cache

3. **`MANUAL_SISTEMA_TOMMIBOT.md`**
   - Manual completo del sistema
   - Guías paso a paso
   - FAQs

4. **`TOMMIBOT_IA_README.md`** (este archivo)
   - Guía rápida de configuración
   - Instrucciones de uso

### Archivos Mejorados

1. **`app/controllers/TommibotController.php`**
   - Integración con AIService
   - Detección automática de roles
   - Manejo de preguntas generales
   - Contextualización de respuestas

2. **`Public/js/tommibot.js`**
   - Comandos de voz ejecutables
   - Eliminación de saludos repetitivos
   - Mejor manejo de errores
   - Formato de mensajes mejorado

3. **`Public/kb/tommibot_kb.json`**
   - Más intenciones
   - Comandos de voz
   - FAQs
   - Información del sistema

---

## 🎨 Personalización

### Cambiar Personalidad del Bot

Editar `app/config/ai_config.php`:

```php
'bot' => [
    'name' => 'Tommibot',
    'personality' => 'juvenil, amable, profesional', // ← Cambiar aquí
    'tone' => 'adolescente',
],
```

### Ajustar Voz

Editar `Public/js/tommibot.js`:

```javascript
u.rate = 1.05; // Velocidad (0.1 - 2.0)
u.pitch = 1.3; // Tono (0.0 - 2.0)
u.volume = 0.9; // Volumen (0.0 - 1.0)
```

### Modificar Contexto del Sistema

Editar `app/lib/AIService.php`:

```php
private function initializeSystemContext() {
    $this->systemContext = "Eres Tommibot, ..."; // ← Personalizar
}
```

---

## ⚠️ Solución de Problemas

### Problema: Tommibot responde genérico (sin IA)

**Causa:** API Key no configurada o inválida

**Solución:**
1. Verificar `app/config/ai_config.php`
2. API Key debe ser válida de Google AI Studio
3. `enabled` debe ser `true`
4. Verificar conexión a internet

### Problema: Error "API limit exceeded"

**Causa:** Límite gratuito alcanzado

**Solución:**
1. Esperar al día siguiente (se resetea diario)
2. Límites gratuitos:
   - 60 req/minuto
   - 1,500 req/día
3. Mientras tanto, funciona con KB local

### Problema: Reconocimiento de voz no funciona

**Causa:** Navegador no compatible

**Solución:**
- Usar Chrome, Edge o Safari
- Permitir acceso al micrófono
- Verificar permisos del sitio

### Problema: Respuestas muy lentas

**Causa:** Latencia de API

**Solución:**
1. Cache optimiza respuestas repetidas
2. Revisar conexión a internet
3. Considerar aumentar timeout en config

---

## 📈 Estadísticas de Uso (Opcional)

Para monitorear uso de la IA, puedes agregar logging:

```php
// En app/lib/AIService.php
error_log("Gemini API call: " . $prompt);
```

Ver logs en:
```
xampp/apache/logs/error.log
```

---

## 🔐 Seguridad de API Key

### ✅ Buenas Prácticas

1. **Nunca compartir** la API Key públicamente
2. **No subir** a repositorios Git públicos
3. **Rotar** la clave periódicamente
4. **Monitorear** uso en Google AI Studio
5. **Limitar** acceso solo a IPs conocidas (si es posible)

### Archivo .gitignore

Agregar a `.gitignore`:
```
app/config/ai_config.php
```

Para compartir estructura sin clave:
```
app/config/ai_config.example.php
```

---

## 🚀 Mejoras Futuras (Sugerencias)

### Versión 2.1 (Próxima)
- [ ] Historial de conversaciones por usuario
- [ ] Feedback de respuestas (👍 👎)
- [ ] Sugerencias predictivas
- [ ] Multilenguaje (español/inglés)

### Versión 2.2
- [ ] Integración con WhatsApp
- [ ] Notificaciones proactivas
- [ ] Análisis de sentimiento avanzado
- [ ] Resúmenes automáticos de historial

### Versión 3.0
- [ ] Fine-tuning con datos del colegio
- [ ] Modelo local (privacidad total)
- [ ] Integración con calendario Google
- [ ] Dashboard de analytics de Tommibot

---

## 📞 Soporte

**Documentación completa:**
- Ver `MANUAL_SISTEMA_TOMMIBOT.md`

**Google Gemini API:**
- [Documentación oficial](https://ai.google.dev/docs)
- [Límites y pricing](https://ai.google.dev/pricing)

**Web Speech API:**
- [MDN Documentation](https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API)

---

## ✅ Checklist de Implementación

- [ ] Obtener API Key de Google Gemini
- [ ] Configurar `app/config/ai_config.php`
- [ ] Probar con pregunta general
- [ ] Probar comandos de voz
- [ ] Verificar respuestas por rol
- [ ] Revisar formato de mensajes
- [ ] Ajustar voz si es necesario
- [ ] Capacitar usuarios en uso de Tommibot
- [ ] Distribuir manual del sistema

---

## 🎉 ¡Listo!

Tommibot ahora es un asistente inteligente completo que puede:

✅ Responder preguntas del sistema  
✅ Responder preguntas generales  
✅ Ejecutar comandos por voz  
✅ Adaptarse según el rol del usuario  
✅ Mantener conversaciones naturales  
✅ Guiar paso a paso en procesos  

**¡Disfruta de tu nuevo asistente con IA!** 🤖✨

---

**Última actualización:** Noviembre 2025  
**Versión Tommibot:** 2.0  
**Powered by:** Google Gemini API + Web Speech API
