# 📚 Manual Completo del Sistema de Reservas y Préstamos AIP

**Colegio Juan Tomis Stack**  
**Sistema de Reservas y Préstamos con IA - Versión 2.0**

---

## 📖 Índice

1. [Introducción](#introducción)
2. [Características Principales](#características-principales)
3. [Roles del Sistema](#roles-del-sistema)
4. [Módulos por Rol](#módulos-por-rol)
5. [Tommibot - Asistente Inteligente](#tommibot---asistente-inteligente)
6. [Guías Paso a Paso](#guías-paso-a-paso)
7. [Preguntas Frecuentes (FAQ)](#preguntas-frecuentes-faq)
8. [Reglas y Políticas](#reglas-y-políticas)
9. [Solución de Problemas](#solución-de-problemas)
10. [Configuración Técnica](#configuración-técnica)

---

## 🎯 Introducción

El Sistema de Reservas y Préstamos AIP es una plataforma web integral diseñada para gestionar de manera eficiente las reservas de aulas y el préstamo de equipos tecnológicos en el Colegio Juan Tomis Stack.

### Objetivo
Facilitar la coordinación entre profesores, administradores y encargados para optimizar el uso de recursos educativos.

### Tecnologías Utilizadas
- **Backend**: PHP 8.x con arquitectura MVC
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Base de Datos**: MySQL
- **Seguridad**: Verificación SMS con Twilio
- **IA**: Google Gemini API (chatbot inteligente)
- **Reconocimiento de Voz**: Web Speech API

---

## ✨ Características Principales

### 🔐 Seguridad
- Verificación por SMS para acciones críticas
- Códigos de 6 dígitos de un solo uso
- Expiración automática de códigos (10 minutos)
- Contraseñas robustas con validación
- Sesiones seguras con timeout automático

### 📱 Notificaciones
- SMS automático para verificación
- Notificaciones en tiempo real en el sistema
- Alertas por correo electrónico
- Campana de notificaciones en navbar

### 📊 Reportes y Análisis
- Generación de PDFs institucionales
- Reportes filtrados por fecha, profesor, aula, turno
- Estadísticas con gráficos interactivos
- Rankings de uso de aulas
- Exportación automática por email

### 🤖 Tommibot - Asistente IA
- Respuestas inteligentes con Google Gemini
- Comandos de voz ejecutables
- Contexto según rol del usuario
- Respuestas tanto del sistema como generales
- Voz juvenil personalizada

---

## 👥 Roles del Sistema

### 1. Profesor
**Permisos:**
- Hacer reservas de aulas
- Solicitar préstamos de equipos
- Ver y descargar su historial
- Cancelar reservas (mismo día)
- Cambiar contraseña (con SMS)
- Usar Tommibot

**Restricciones:**
- Mínimo 1 día de anticipación para reservas/préstamos
- Cancelación solo el mismo día
- Requiere verificación SMS

### 2. Administrador
**Permisos:**
- Todos los permisos de Profesor
- Gestionar usuarios (crear, editar, eliminar)
- Ver historial global de todos
- Generar reportes filtrados
- Ver estadísticas avanzadas
- Configurar sistema

**Panel Exclusivo:**
- Gestión de usuarios
- Reportes y filtros
- Estadísticas y gráficos
- Historial global

### 3. Encargado
**Permisos:**
- Ver préstamos pendientes
- Validar entregas de equipos
- Registrar devoluciones
- Agregar comentarios de inspección
- Ver historial de préstamos
- Controlar estado de equipos

**Panel Exclusivo:**
- Validación de préstamos
- Gestión de devoluciones
- Control de equipos

---

## 📦 Módulos por Rol

### Módulos de Profesor

#### 📅 Reservar Aula
1. Click en "Reservar Aula" en el menú
2. **SMS automático** enviado a tu teléfono
3. Ingresar código de 6 dígitos
4. Seleccionar fecha (mínimo mañana)
5. Elegir hora de inicio y fin
6. Seleccionar aula disponible
7. (Opcional) Agregar descripción
8. Confirmar reserva
9. Ver confirmación y recibir email

**Validaciones:**
- Fecha: mínimo 1 día de anticipación
- Horario: no conflictos con otras reservas
- Código SMS: válido por 10 minutos

#### 💻 Solicitar Préstamo
1. Click en "Préstamo" en el menú
2. **SMS automático** enviado a tu teléfono
3. Ingresar código de 6 dígitos
4. Seleccionar fecha de préstamo (mínimo mañana)
5. Elegir equipos necesarios:
   - Laptop
   - Proyector
   - Extensión eléctrica
   - Otros equipos
6. Seleccionar aula y horario
7. Confirmar solicitud
8. **Esperar validación del Encargado**

**Estados del préstamo:**
- **Pendiente**: Esperando validación
- **Activo**: Equipo entregado
- **Devuelto**: Equipo retornado

#### 📋 Ver Historial
1. Click en "Historial" en el menú
2. Visualizar tabla con todas tus reservas y préstamos
3. Filtrar por:
   - Fecha (desde/hasta)
   - Tipo (Reserva/Préstamo)
   - Estado
4. **Acciones disponibles:**
   - Cancelar reserva (solo mismo día)
   - Descargar PDF individual
   - Ver detalles

#### 🔑 Cambiar Contraseña
1. Click en "Cambiar Contraseña"
2. **SMS automático** (solo para Profesores)
3. Ingresar código de verificación
4. Escribir contraseña actual
5. Escribir nueva contraseña (requisitos):
   - Mínimo 8 caracteres
   - Al menos 1 mayúscula
   - Al menos 1 número
   - Al menos 1 carácter especial (@$!%*?&)
6. Confirmar nueva contraseña
7. Guardar cambios

### Módulos de Administrador

#### 👤 Gestionar Usuarios
1. Acceder al panel de Admin
2. Click en "Gestionar Usuarios"
3. **Opciones:**
   - **Crear usuario:**
     - Nombre completo
     - Correo electrónico
     - Teléfono (+51XXXXXXXXX)
     - Rol (Profesor/Admin/Encargado)
     - Contraseña inicial
   - **Editar usuario:**
     - Modificar datos
     - Cambiar rol
     - Resetear contraseña
   - **Eliminar/Desactivar usuario:**
     - Soft delete (recomendado)
     - Hard delete (permanente)

#### 📊 Reportes y Filtros
1. Click en "Reportes / Filtros"
2. Aplicar filtros:
   - Rango de fechas
   - Profesor específico
   - Aula específica
   - Turno (Mañana/Tarde)
   - Tipo (Reserva/Préstamo)
3. Ver resultados en tabla
4. **Descargar PDF:**
   - Diseño institucional
   - Logo del colegio
   - Nombre del usuario que descarga
   - Rol del usuario
   - Fecha y hora de descarga
5. PDF enviado automáticamente al correo

#### 📈 Estadísticas
1. Click en "Estadísticas"
2. Ver dashboard con:
   - **KPIs principales:**
     - Total de reservas del mes
     - Total de préstamos activos
     - Aulas más utilizadas
     - Profesores más activos
   - **Gráficos:**
     - Reservas por día (línea de tiempo)
     - Distribución por aula (pie chart)
     - Ranking de profesores (bar chart)
     - Horas pico de uso
   - **Rankings:**
     - Top 10 aulas
     - Top 10 profesores
     - Equipos más solicitados
3. Filtrar por período
4. Exportar gráficos

### Módulos de Encargado

#### ✅ Validar Préstamo
1. Acceder a "Préstamos Pendientes"
2. Ver lista de solicitudes
3. **Al momento de entregar:**
   - Verificar identidad del docente
   - Inspeccionar equipo físicamente
   - Confirmar que funciona correctamente
   - Click en "Validar Préstamo"
4. Estado cambia a "Activo"
5. Docente recibe notificación

#### 📦 Registrar Devolución
1. Docente trae el equipo
2. **Inspección física del equipo:**
   - Verificar estado
   - Detectar daños o fallas
3. Acceder a "Devoluciones"
4. Seleccionar préstamo activo
5. Marcar como "Devuelto"
6. **Agregar comentario (opcional):**
   - "Equipo en perfecto estado"
   - "Laptop con rayón en tapa"
   - "Proyector con foco débil"
7. Confirmar devolución
8. Sistema actualiza historial

---

## 🤖 Tommibot - Asistente Inteligente

### ¿Qué es Tommibot?

Tommibot es un asistente virtual potenciado con IA (Google Gemini) que puede:
- Responder preguntas sobre el sistema
- Ejecutar comandos por voz
- Guiar paso a paso en procesos
- Responder preguntas generales
- Adaptarse según tu rol

### Acceso a Tommibot

**Opción 1: Módulo Completo**
- Click en menú "Tommibot"
- Vista completa con panel de ayuda

**Opción 2: Botón Flotante**
- Click en botón flotante (robot) en esquina inferior derecha
- Panel emergente rápido

### Interacción por Texto

1. Escribir pregunta o comando
2. Presionar Enter o "Enviar"
3. Tommibot responde inteligentemente
4. Activar/desactivar lectura de voz con switch

**Ejemplos de preguntas:**
```
- ¿Cómo hago una reserva?
- No me llega el código SMS
- ¿Puedo cancelar una reserva de ayer?
- Muéstrame el historial
- ¿Qué equipos puedo pedir prestados?
- ¿Cuál es la capital de Perú? (pregunta general)
- Cuéntame un chiste
```

### Comandos de Voz Ejecutables

1. Click en botón "🎙️ Hablar"
2. Esperar "Escuchando..."
3. Decir comando claramente
4. Tommibot ejecuta acción

**Comandos disponibles:**

#### Navegación
- "Ir a reservas" → Abre módulo de Reservas
- "Abre préstamos" → Abre módulo de Préstamos
- "Muéstrame historial" → Abre Historial
- "Cambiar contraseña" → Abre cambio de contraseña
- "Gestionar usuarios" → Panel de usuarios (Admin)
- "Ver reportes" → Reportes y filtros (Admin)
- "Ver estadísticas" → Dashboard de analytics (Admin)

#### Acciones
- "Descargar PDF" → Descarga PDF del historial
- "¿Qué puedes hacer?" → Lista de comandos

#### Conversación
- Cualquier pregunta sobre el sistema
- Preguntas generales (clima, curiosidades, etc.)

### Voz de Tommibot

**Características:**
- Tono juvenil (pitch: 1.3)
- Velocidad ágil (rate: 1.05)
- Personalidad amable y profesional
- Se adapta al nombre del usuario

**Control de voz:**
- Switch "Leer respuestas" para activar/desactivar
- La voz solo saluda la primera vez
- No repite saludos innecesarios

### Adaptación por Rol

**Profesor:**
- Enfoque en reservas y préstamos
- Ayuda con verificación SMS
- Guía en historial y cancelaciones

**Administrador:**
- Información sobre gestión de usuarios
- Guía para reportes y estadísticas
- Configuración del sistema

**Encargado:**
- Ayuda con validaciones
- Registro de devoluciones
- Control de equipos

---

## 📝 Guías Paso a Paso

### 🔐 Primera vez en el sistema

1. **Recibir credenciales del Administrador**
   - Usuario (email o código)
   - Contraseña temporal

2. **Primer login:**
   - Ir a `http://localhost/Sistema_reserva_AIP`
   - Ingresar usuario y contraseña
   - Click en "Iniciar Sesión"

3. **Cambiar contraseña inicial:**
   - Ir a "Cambiar Contraseña"
   - Verificar con SMS (Profesores)
   - Crear contraseña segura
   - Confirmar cambio

4. **Actualizar perfil:**
   - Click en tu nombre (esquina superior)
   - Seleccionar "Mi Perfil"
   - Verificar teléfono (formato: +51XXXXXXXXX)
   - Actualizar correo si es necesario
   - Guardar cambios

### 📅 Hacer primera reserva

1. **Preparación:**
   - Tener teléfono a mano para SMS
   - Decidir fecha y hora deseada (mínimo mañana)
   - Elegir aula según disponibilidad

2. **Proceso:**
   - Click "Reservar Aula"
   - Esperar SMS (llega en ~10 segundos)
   - Ingresar código de 6 dígitos
   - Rellenar formulario:
     - Fecha: DD/MM/AAAA
     - Hora inicio
     - Hora fin
     - Aula
     - Descripción (opcional)
   - Click "Confirmar Reserva"

3. **Confirmación:**
   - Ver mensaje de éxito
   - Recibir email de confirmación
   - Verificar en "Historial"

### 💻 Solicitar préstamo de equipo

1. **Determinar necesidades:**
   - ¿Qué equipos necesitas?
   - ¿Para qué fecha y hora?
   - ¿En qué aula lo usarás?

2. **Solicitud:**
   - Click "Préstamo"
   - Verificación SMS
   - Ingresar código
   - Seleccionar equipos (múltiple):
     - ☑️ Laptop
     - ☑️ Proyector
     - ☑️ Extensión
   - Seleccionar fecha y horario
   - Elegir aula
   - Confirmar

3. **Recojo de equipo:**
   - Ir con Encargado en horario acordado
   - Encargado valida físicamente
   - Encargado marca "Validado" en sistema
   - Llevar equipo a tu aula

4. **Devolución:**
   - Devolver en buen estado al Encargado
   - Encargado inspecciona
   - Encargado registra devolución
   - Ver estado "Devuelto" en historial

### 🗑️ Cancelar una reserva

**⚠️ IMPORTANTE: Solo se puede cancelar el mismo día**

1. Ir a "Historial"
2. Ubicar reserva a cancelar
3. Verificar que sea del día actual
4. Click en "Cancelar"
5. Confirmar cancelación en modal
6. Reserva eliminada (queda registro)

**Si ya pasó el día:**
- No hay opción de cancelar
- Contactar con Administrador si es urgente
- Reserva queda en historial

### 📄 Descargar reporte PDF

#### Como Profesor:
1. Ir a "Historial"
2. (Opcional) Aplicar filtros
3. Click en "📥 Descargar PDF"
4. PDF se genera y descarga automáticamente
5. También se envía a tu correo

#### Como Administrador:
1. Ir a "Reportes / Filtros"
2. Aplicar filtros deseados:
   - Rango de fechas
   - Profesor específico
   - Aula
   - Turno
3. Click "Generar Reporte PDF"
4. PDF con diseño institucional:
   - Logo del colegio
   - Tu nombre y rol
   - Fecha/hora de descarga
   - Datos filtrados
5. Descarga automática + email

---

## ❓ Preguntas Frecuentes (FAQ)

### Verificación SMS

**P: No me llega el SMS, ¿qué hago?**
R: 
1. Verifica tu número en "Mi Perfil"
2. Debe estar en formato +51XXXXXXXXX
3. Click "Reenviar" (espera 60 segundos)
4. Si persiste, contacta al Admin

**P: El código SMS ya expiró**
R: 
- Los códigos valen 10 minutos
- Reinicia el proceso
- Se enviará un nuevo código

**P: Ingresé mal el código 3 veces**
R:
- Después de 3 intentos fallidos, se bloquea
- Espera 15 minutos o contacta al Admin

### Reservas y Préstamos

**P: ¿Por qué no puedo reservar para hoy?**
R:
- Política del sistema: mínimo 1 día de anticipación
- La fecha más cercana es mañana
- Esto aplica tanto para reservas como préstamos

**P: ¿Puedo modificar una reserva?**
R:
- No hay edición directa
- Debes cancelar (si es el mismo día)
- Y crear una nueva reserva

**P: ¿Puedo cancelar una reserva de ayer?**
R:
- No, solo se cancelan reservas del mismo día
- Después del día, queda registrada

**P: ¿Cuántos equipos puedo pedir prestados?**
R:
- No hay límite fijo
- Selecciona los que realmente necesites
- Sujeto a disponibilidad

### Devoluciones

**P: ¿Qué pasa si daño un equipo?**
R:
1. Informa inmediatamente al Encargado
2. Encargado registra incidente en devolución
3. Se aplican políticas del colegio
4. Posible reposición o sanción

**P: El Encargado no está para devolver**
R:
- Coordinar previamente horario de devolución
- Dejar equipo en lugar seguro solo si Encargado autoriza
- No dejar equipo sin supervisión

### Historial y Reportes

**P: No veo mi reserva en el historial**
R:
- Espera unos segundos y recarga
- Verifica que se confirmó correctamente
- Si no aparece, contacta al Admin

**P: El PDF no se descarga**
R:
- Verifica bloqueador de pop-ups
- Permitir descargas del sitio
- Revisar correo (se envía automáticamente)

### Tommibot

**P: Tommibot no responde**
R:
- Verifica conexión a internet
- Recarga la página
- Si persiste, la API de IA puede estar inactiva

**P: El reconocimiento de voz no funciona**
R:
- Solo funciona en Chrome, Edge, Safari
- Permitir acceso al micrófono
- Hablar claro y pausado

**P: ¿Tommibot guarda mi información?**
R:
- Solo usa caché temporal (1 hora)
- No almacena datos personales
- Las consultas no se registran permanentemente

---

## ⚖️ Reglas y Políticas

### Anticipación Obligatoria
- **1 día mínimo** para reservas
- **1 día mínimo** para préstamos
- Validación en frontend y backend
- No negociable

### Cancelación de Reservas
- **Solo el mismo día** de la reserva
- Después del día: no se puede cancelar
- Cancelaciones quedan registradas
- Múltiples cancelaciones pueden generar restricciones

### Verificación SMS
- **Obligatoria** para:
  - Hacer reserva
  - Solicitar préstamo
  - Cambiar contraseña (solo Profesores)
- Código válido por 10 minutos
- Un solo uso por código
- Máximo 3 intentos

### Uso de Equipos
- Uso exclusivo del solicitante
- No prestar a terceros
- Devolución en tiempo y forma
- Reportar inmediatamente cualquier daño
- Inspección obligatoria al devolver

### Responsabilidades por Rol

**Profesor:**
- Usar recursos responsablemente
- Cancelar si no usará la reserva
- Devolver equipos a tiempo
- Mantener datos actualizados

**Administrador:**
- Gestionar usuarios éticamente
- No eliminar datos sin autorización
- Generar reportes según necesidad
- Configurar sistema apropiadamente

**Encargado:**
- Validar identidad antes de entregar
- Inspeccionar minuciosamente
- Registrar estado real del equipo
- Reportar daños o pérdidas

---

## 🔧 Solución de Problemas

### Error: "Sesión expirada"
**Causa:** Inactividad prolongada
**Solución:**
1. Hacer logout
2. Login nuevamente
3. Si persiste, limpiar cookies del navegador

### Error: "Código SMS inválido"
**Causa:** Código expirado o incorrecto
**Solución:**
1. Verificar código recibido
2. Ingresar exactamente como llega
3. Si expiró, solicitar reenvío
4. Esperar 60 segundos entre reenvíos

### Error: "Fecha no válida"
**Causa:** Intentar reservar con menos de 1 día
**Solución:**
- Seleccionar fecha de mañana en adelante
- Verificar formato DD/MM/AAAA

### Error: "Aula no disponible"
**Causa:** Conflicto de horario
**Solución:**
1. Elegir otro horario
2. Elegir otra aula
3. Ver calendario de disponibilidad

### No funciona reconocimiento de voz
**Causa:** Navegador o permisos
**Solución:**
1. Usar Chrome, Edge o Safari
2. Permitir acceso al micrófono
3. Verificar que no esté silenciado
4. Recargar página

### PDF no se genera
**Causa:** Bloqueador de pop-ups
**Solución:**
1. Permitir pop-ups del sitio
2. Desactivar bloqueador temporalmente
3. Revisar carpeta de descargas
4. Verificar correo electrónico

### Tommibot sin IA (respuestas básicas)
**Causa:** API Key no configurada
**Solución:**
- Contactar al Admin
- Admin debe configurar Google Gemini API
- Ver sección de Configuración Técnica

---

## ⚙️ Configuración Técnica

### Configuración de IA (Administradores)

#### 1. Obtener API Key de Google Gemini

1. Ir a [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Iniciar sesión con cuenta Google
3. Click en "Create API Key"
4. Copiar la clave generada

#### 2. Configurar en el sistema

Editar archivo: `app/config/ai_config.php`

```php
return [
    'gemini' => [
        'api_key' => 'TU_API_KEY_AQUI', // Pegar tu API Key
        'enabled' => true, // Activar IA
    ],
];
```

#### 3. Verificar funcionamiento

- Abrir Tommibot
- Hacer pregunta general: "¿Qué hora es?"
- Si responde correctamente, IA está activa

### Límites del Tier Gratuito

**Google Gemini Free:**
- 60 peticiones por minuto
- 1,500 peticiones por día
- Sin costo

**Si se agota:**
- Tommibot funciona con KB local
- Respuestas menos naturales pero funcionales

### Configuración de SMS (Twilio)

Archivo: `app/config/twilio.php`

```php
return [
    'account_sid' => 'TU_ACCOUNT_SID',
    'auth_token' => 'TU_AUTH_TOKEN',
    'from_number' => '+51XXXXXXXXX'
];
```

### Base de Datos

Asegurar que existan estas tablas:
- `usuarios` (con campo `telefono`)
- `verification_codes`
- `reservas`
- `prestamos`
- `notificaciones`

---

## 📞 Soporte y Contacto

**Soporte Técnico:**
- Email: soporte@juantomisstack.edu.pe
- Extensión: 1234

**Administrador del Sistema:**
- Ver panel de Admin
- Contacto interno del colegio

**Emergencias:**
- Reportar equipos dañados inmediatamente
- Contactar Dirección para políticas

---

## 📅 Historial de Versiones

### Versión 2.0 (Actual)
- ✅ Integración de IA con Google Gemini
- ✅ Tommibot mejorado con NLP
- ✅ Comandos de voz ejecutables
- ✅ Detección de roles automática
- ✅ Respuestas contextualizadas
- ✅ Manual completo del sistema
- ✅ Base de conocimientos expandida

### Versión 1.5
- Verificación SMS implementada
- Anticipación de 1 día
- Reportes con diseño institucional
- Estadísticas avanzadas

### Versión 1.0
- Sistema base de reservas
- Gestión de préstamos
- Historial básico

---

**© 2025 Colegio Juan Tomis Stack - Sistema de Reservas AIP**  
**Desarrollado con ❤️ y IA por el equipo técnico**
