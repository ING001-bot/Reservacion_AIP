# 📚 DOCUMENTACIÓN SISTEMA DE RESERVACIÓN AIP
## Colegio Monseñor Juan Tomis Stack

**Versión:** 2.0  
**Fecha:** Noviembre 2025  
**Desarrollador:** Sistema optimizado y profesional

---

## 📋 ÍNDICE
1. [Descripción General](#descripción-general)
2. [Roles y Permisos](#roles-y-permisos)
3. [Módulos del Sistema](#módulos-del-sistema)
4. [Funcionalidades por Rol](#funcionalidades-por-rol)
5. [Flujos de Trabajo](#flujos-de-trabajo)
6. [Sistema de Notificaciones](#sistema-de-notificaciones)
7. [Sistema de Seguridad](#sistema-de-seguridad)
8. [Mantenimiento del Sistema](#mantenimiento-del-sistema)
9. [Características Técnicas](#características-técnicas)

---

## 🎯 DESCRIPCIÓN GENERAL

Sistema web para la gestión de **Aulas de Innovación Pedagógica (AIP)** que permite:
- Reservar aulas AIP y regulares
- Gestionar préstamos de equipos tecnológicos
- Controlar inventario de equipos
- Registrar devoluciones con inspección
- Generar historiales y reportes
- Sistema de notificaciones en tiempo real
- Asistente virtual (Tommibot) con IA

### Propósito
Optimizar la gestión de recursos tecnológicos del colegio, permitiendo a los profesores reservar espacios y equipos de manera eficiente, mientras el personal administrativo mantiene control total del inventario y uso.

---

## 👥 ROLES Y PERMISOS

### 1. **PROFESOR**
**Descripción:** Docente que utiliza los recursos del AIP para sus clases.

**Permisos:**
- ✅ Reservar aulas AIP (mínimo 1 día de anticipación)
- ✅ Solicitar préstamos de equipos con aulas regulares
- ✅ Ver su historial personal de reservas y préstamos
- ✅ Cancelar sus propias reservas (mismo día)
- ✅ Cambiar contraseña con verificación SMS
- ✅ Configurar perfil (foto, biografía, teléfono)
- ✅ Recibir notificaciones de confirmaciones y devoluciones
- ✅ Consultar Tommibot (asistente IA)

**Restricciones:**
- ❌ NO puede gestionar otros usuarios
- ❌ NO puede ver historiales de otros profesores
- ❌ NO puede registrar devoluciones
- ❌ Requiere verificación SMS para: reservas, préstamos, cambio de contraseña

### 2. **ENCARGADO**
**Descripción:** Personal responsable del AIP, encargado de validar préstamos y devoluciones.

**Permisos:**
- ✅ Ver historial global de todos los usuarios
- ✅ Registrar devoluciones de equipos con inspección física
- ✅ Validar estado de equipos (OK, Dañado, Falta accesorio)
- ✅ Agregar comentarios en devoluciones
- ✅ Buscar y filtrar préstamos por estado, fecha, profesor, equipo o aula
- ✅ Configurar perfil personal
- ✅ Cambiar contraseña (sin SMS)
- ✅ Recibir notificaciones de préstamos vencidos

**Restricciones:**
- ❌ NO puede crear usuarios
- ❌ NO puede gestionar equipos o aulas
- ❌ NO puede generar reportes filtrados
- ❌ NO requiere verificación SMS

### 3. **ADMINISTRADOR**
**Descripción:** Personal con acceso completo al sistema.

**Permisos:**
- ✅ **Gestión de Usuarios:** Crear, editar, eliminar (Profesores, Encargados, Administradores)
- ✅ **Gestión de Aulas:** Crear, editar aulas AIP y regulares
- ✅ **Gestión de Equipos:** Crear, editar, controlar stock y stock máximo
- ✅ **Gestión de Tipos de Equipo:** Crear categorías de equipos
- ✅ **Historial Global:** Ver todos los movimientos del sistema
- ✅ **Reportes Filtrados:** Generar reportes personalizados por fecha, profesor, tipo, estado
- ✅ **Exportar PDF:** Historiales semanales y reportes filtrados
- ✅ **Estadísticas:** Gráficos de uso de aulas y equipos
- ✅ **Mantenimiento del Sistema:** Ejecutar mantenimiento mensual (optimización BD, backups, limpieza)
- ✅ **Backups:** Crear y restaurar copias de seguridad
- ✅ **Configuración del Sistema:** Ajustes generales

**Restricciones:**
- ❌ NO requiere verificación SMS (acceso directo)

---

## 🧩 MÓDULOS DEL SISTEMA

### 1. **MÓDULO DE AUTENTICACIÓN**

#### Login y Registro
- **Login estándar:** Email + contraseña
- **Magic Login:** Enlace temporal enviado por correo (válido 10 minutos)
- **Registro:** Solo administradores pueden crear usuarios
- **Verificación de correo:** Token enviado al registrarse
- **Recuperación de contraseña:** Envío de token por correo (válido 1 hora)

#### Seguridad
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Tokens únicos para cada acción (verificación, reset, magic login)
- Prevención de caché en navegadores
- Redirección automática si ya hay sesión activa

---

### 2. **MÓDULO DE RESERVAS**

#### Características
- **Tipo de aulas:** Solo aulas AIP
- **Anticipación mínima:** 1 día (no se permite reservar el mismo día)
- **Verificación SMS:** Obligatoria para profesores antes de reservar
- **Calendario visual:** Muestra disponibilidad por horas (6:00-18:00)
- **Turnos:** Mañana (6:00-12:45), Tarde (13:00-18:00)
- **Reservas bloqueadas:** Se marcan visualmente las horas ocupadas

#### Proceso de Reserva (Profesor)
1. Accede a "Reservar Aula"
2. Sistema envía automáticamente código SMS de 6 dígitos
3. Profesor ingresa código (válido 10 minutos)
4. Selecciona fecha (mínimo mañana)
5. Selecciona aula AIP disponible
6. Selecciona rango de horas
7. Confirma reserva
8. Recibe notificación de confirmación

#### Cancelación
- **Requisito:** Solo el mismo día de la reserva
- **Proceso:** Cancelar desde "Historial" → Ingresar motivo → Confirmar
- **Registro:** Se guarda en `reservas_canceladas` con motivo y fecha

---

### 3. **MÓDULO DE PRÉSTAMOS**

#### Características
- **Tipo de aulas:** Solo aulas REGULARES (no AIP)
- **Equipos:** Laptops, Proyectores, Parlantes, etc.
- **Anticipación mínima:** 1 día
- **Verificación SMS:** Obligatoria para profesores
- **Agrupación:** Si un profesor solicita varios equipos para la misma aula/hora, se agrupan como "pack"
- **Control de stock:** El stock disminuye al prestar, aumenta al devolver

#### Proceso de Préstamo (Profesor)
1. Accede a "Préstamo de Equipos"
2. Sistema envía automáticamente código SMS
3. Profesor ingresa código
4. Selecciona fecha (mínimo mañana)
5. Selecciona aula REGULAR
6. Selecciona equipo(s) disponible(s)
7. Define hora de inicio y fin
8. Confirma préstamo
9. Recibe notificación de confirmación

#### Devolución (Encargado)
1. Accede a "Registrar Devolución"
2. Usa buscador para encontrar préstamos activos
3. Selecciona préstamo o pack a devolver
4. Inspecciona físicamente el/los equipo(s)
5. Selecciona estado:
   - **OK:** Todo en buen estado
   - **Dañado:** Equipo con daños
   - **Falta accesorio:** Falta cable, mouse, etc.
6. Si está dañado/falta accesorio: agrega comentario obligatorio
7. Confirma devolución
8. Sistema actualiza stock y envía notificación al profesor y administradores

---

### 4. **MÓDULO DE HISTORIAL**

#### Historial Personal (Profesor)
- **Pestañas:** Historial/Reserva, Historial/Equipos
- **Vista semanal:** Lunes a sábado
- **Filtros:** Navegación por semanas
- **Información:** Aula, fecha, horas, estado
- **Exportar PDF:** Descarga de historial semanal

#### Historial Global (Encargado y Administrador)
- **Vista completa:** Todos los usuarios
- **Pestañas:** Historial/Reserva, Historial/Equipos
- **Calendarios:** AIP 1, AIP 2 (mañana y tarde)
- **Exportar PDF:** Semana completa con filtro de turno

#### Reportes Filtrados (Solo Administrador)
- **Filtros disponibles:**
  - Rango de fechas
  - Profesor específico
  - Tipo (Reserva/Préstamo)
  - Estado (Activo/Cancelado/Devuelto)
- **Exportar PDF:** Reporte personalizado con filtros aplicados

---

### 5. **MÓDULO DE GESTIÓN (Administrador)**

#### Gestión de Usuarios
- **Crear:** Nombre, correo, contraseña, rol, teléfono
- **Editar:** Modificar datos (excepto correo)
- **Cambiar rol:** Profesor ↔ Encargado ↔ Administrador
- **Activar/Desactivar:** Sin eliminar del sistema
- **Eliminar:** Borra permanentemente (cascada en BD)

#### Gestión de Aulas
- **Crear:** Nombre, capacidad, tipo (AIP/REGULAR)
- **Editar:** Modificar datos
- **Activar/Desactivar:** Control de disponibilidad

#### Gestión de Equipos
- **Crear:** Nombre, tipo, stock, stock máximo
- **Editar:** Modificar datos
- **Control de stock:** Stock actual / Stock máximo
- **Activar/Desactivar:** Control de disponibilidad

#### Gestión de Tipos de Equipo
- **Crear:** Nuevas categorías (Laptop, Proyector, etc.)
- **Listar:** Ver todos los tipos existentes

---

### 6. **MÓDULO DE ESTADÍSTICAS (Administrador)**

#### Gráficos Disponibles
- **Uso de Aulas:** Barras por aula (reservas totales)
- **Uso de Equipos:** Barras por equipo (préstamos totales)
- **Periodo:** Últimos 30 días
- **Actualización:** Datos en tiempo real desde la BD

---

### 7. **MÓDULO DE NOTIFICACIONES**

#### Sistema de Notificaciones In-App
- **Ubicación:** Campana en navbar (contador de no leídas)
- **Tipos de notificaciones:**
  - ✅ Reserva confirmada
  - ✅ Préstamo confirmado (pack o individual)
  - 🔄 Devolución registrada
  - ⚠️ Préstamo vencido (no devuelto a tiempo)

#### Estructura de Notificación
- **Título:** Tipo de acción
- **Mensaje:** Detalle de la acción
- **URL:** Link directo a la sección relacionada
- **Metadata (JSON):** Datos adicionales (equipos, aula, fecha, etc.)
- **Estado:** Leída / No leída
- **Fecha:** Timestamp de creación

#### Agrupación Inteligente
- Si un profesor solicita 3 laptops para la misma aula/hora → 1 notificación de "Pack de 3 equipos"
- Al devolver → 1 notificación grupal con todos los equipos devueltos

---

### 8. **MÓDULO DE CONFIGURACIÓN**

#### Configuración Personal (Todos los roles)
- **Datos personales:** Nombre, correo, teléfono
- **Foto de perfil:** Subir imagen (JPG, PNG, máx 2MB)
- **Biografía:** Texto libre (opcional)
- **Cambiar contraseña:** Verificación con SMS para profesores

#### Configuración del Sistema (Solo Administrador)
- **Mantenimiento mensual:**
  - Optimización de base de datos (OPTIMIZE TABLE)
  - Limpieza de notificaciones antiguas (>3 meses)
  - Backup automático
  - Limpieza de sesiones expiradas
  - Recalcular estadísticas
- **Limitación:** Solo se puede ejecutar cada 30 días
- **Registro:** Guarda quién ejecutó y cuándo

---

### 9. **MÓDULO TOMMIBOT (Asistente IA)**

#### Características
- **Tecnología:** Claude AI (Anthropic)
- **Acceso:** Todos los roles
- **Contexto:** Conoce el rol del usuario y adapta las respuestas
- **Funcionalidades:**
  - Responde preguntas sobre el sistema
  - Explica cómo hacer reservas/préstamos
  - Ayuda con problemas comunes
  - Explica diferencias entre roles
  - Guía paso a paso para cada proceso

#### Información por Rol
- **Profesor:** Explica verificación SMS, anticipación de 1 día, diferencia entre AIP y REGULAR
- **Encargado:** Explica proceso de devolución, estados de equipos, búsqueda de préstamos
- **Administrador:** Explica gestión completa, reportes, mantenimiento, backups

---

## 🔄 FLUJOS DE TRABAJO

### Flujo 1: Profesor Reserva un Aula AIP
```
1. Login → Dashboard Profesor
2. Click "Ir a Reservas"
3. Sistema envía SMS automático
4. Ingresa código de 6 dígitos
5. Selecciona fecha (mínimo mañana)
6. Elige aula AIP disponible
7. Selecciona rango de horas
8. Click "Reservar"
9. Recibe notificación de confirmación
10. Puede ver en "Historial"
```

### Flujo 2: Profesor Solicita Préstamo de Equipos
```
1. Login → Dashboard Profesor
2. Click "Ir a Préstamos"
3. Sistema envía SMS automático
4. Ingresa código de 6 dígitos
5. Selecciona fecha (mínimo mañana)
6. Elige aula REGULAR
7. Selecciona equipo(s) con stock disponible
8. Define hora inicio/fin
9. Click "Solicitar Préstamo"
10. Recibe notificación de confirmación
11. Stock disminuye automáticamente
```

### Flujo 3: Encargado Registra Devolución
```
1. Login → Dashboard Encargado
2. Click "Registrar Devolución"
3. Usa buscador: busca por profesor, equipo o aula
4. Filtra por estado "Prestado"
5. Inspecciona físicamente los equipos
6. Click "Confirmar" en el préstamo
7. Selecciona estado (OK/Dañado/Falta accesorio)
8. Si no es OK: agrega comentario obligatorio
9. Click "Devolver"
10. Sistema actualiza stock
11. Envía notificación al profesor y administradores
```

### Flujo 4: Administrador Ejecuta Mantenimiento Mensual
```
1. Login → Dashboard Admin
2. Click "Configuración"
3. Scroll a sección "Mantenimiento del Sistema"
4. Verifica que hayan pasado 30+ días
5. Click "Ejecutar Mantenimiento"
6. Confirma en SweetAlert
7. Sistema ejecuta:
   - OPTIMIZE TABLE en 12 tablas
   - DELETE notificaciones >3 meses
   - Backup automático en backups/database/
   - Limpieza de sesiones /tmp/
   - Cache clear
8. Muestra mensaje de éxito
9. Registra en tabla mantenimiento_sistema
```

---

## 🔔 SISTEMA DE NOTIFICACIONES

### Tipos de Notificaciones

#### 1. Reserva Confirmada
- **Título:** "Reserva confirmada"
- **Mensaje:** "Tu reserva del aula [nombre] para el [fecha] de [hora_inicio] a [hora_fin] ha sido confirmada"
- **URL:** `/Historial.php`
- **Destinatarios:** Profesor que reservó

#### 2. Préstamo Confirmado
- **Título:** "Préstamo confirmado"
- **Mensaje:** "Tu préstamo de [X equipos] para el [fecha] ha sido confirmado"
- **Metadata:** `{equipos: ['Laptop', 'Proyector'], aula: 'Regular 1', ...}`
- **URL:** `/Historial.php?view=equipos`
- **Destinatarios:** Profesor que solicitó

#### 3. Devolución Registrada
- **Título:** "Devolución registrada"
- **Mensaje:** "Se ha registrado la devolución de [X equipos]. Estado: [OK/Dañado]. Encargado: [nombre]"
- **Metadata:** `{equipos: [...], estado: 'ok', comentario: '...', ...}`
- **URL:** `/Historial.php?view=equipos`
- **Destinatarios:** 
  - Profesor que prestó
  - Todos los administradores

#### 4. Préstamo Vencido
- **Título:** "Préstamo vencido"
- **Mensaje:** "El préstamo de [equipo] debía devolverse el [fecha] y aún no se ha devuelto"
- **URL:** `/Devolucion.php`
- **Destinatarios:** Todos los encargados

### Limpieza Automática
- **Frecuencia:** Cada mantenimiento mensual
- **Criterio:** Notificaciones con más de 3 meses
- **Query:** `DELETE FROM notificaciones WHERE creada_en < DATE_SUB(NOW(), INTERVAL 3 MONTH)`

---

## 🔐 SISTEMA DE SEGURIDAD

### Verificación SMS (Solo Profesores)

#### Configuración
- **Proveedor:** Twilio
- **Formato de número:** +51XXXXXXXXX (Perú)
- **Código:** 6 dígitos numéricos
- **Validez:** 10 minutos
- **Almacenamiento:** Tabla `verification_codes`

#### Acciones que Requieren SMS
1. **Reservar aula**
2. **Solicitar préstamo**
3. **Cambiar contraseña**

#### Proceso
1. Usuario accede a módulo (reserva/préstamo/cambiar contraseña)
2. Sistema detecta que no está verificado para esa acción
3. Envía SMS automáticamente
4. Usuario ingresa código
5. Sistema valida:
   - Código correcto
   - No expirado (< 10 min)
   - No usado previamente
6. Si es válido: marca sesión como verificada (`$_SESSION['verified_reserva'] = true`)
7. Usuario puede continuar con la acción
8. Verificación expira al cerrar sesión

#### Tabla verification_codes
```sql
- user_id: ID del usuario
- code: Código de 6 dígitos
- action_type: 'reserva' | 'prestamo' | 'cambio_clave'
- expires_at: Timestamp de expiración
- used: 0 (no usado) | 1 (ya usado)
- created_at: Timestamp de creación
```

### Tokens de Autenticación

#### Verificación de Correo
- **Token:** 32 caracteres aleatorios
- **Validez:** Indefinida hasta usar
- **Envío:** Email con link `/verificar.php?token=...`
- **Uso:** Marca `verificado = 1` en usuarios

#### Reset de Contraseña
- **Token:** 32 caracteres aleatorios
- **Validez:** 1 hora
- **Envío:** Email con link `/recuperar_contraseña.php?token=...`
- **Uso:** Permite cambiar contraseña sin la anterior

#### Magic Login
- **Token:** 32 caracteres aleatorios
- **Validez:** 10 minutos
- **Envío:** Email con link `/verify.php?token=...`
- **Uso:** Login directo sin contraseña

### Prevención de Caché
Todas las páginas autenticadas tienen headers:
```php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
```

---

## 🛠️ MANTENIMIENTO DEL SISTEMA

### Mantenimiento Mensual Automatizado

#### Tareas Ejecutadas
1. **Optimización de Base de Datos**
   - `OPTIMIZE TABLE` en las 12 tablas activas
   - Libera espacio fragmentado
   - Mejora rendimiento de consultas

2. **Limpieza de Notificaciones**
   - Elimina notificaciones > 3 meses
   - Query: `DELETE FROM notificaciones WHERE creada_en < DATE_SUB(NOW(), INTERVAL 3 MONTH)`
   - Reduce tamaño de tabla

3. **Backup Automático**
   - Genera archivo SQL en `backups/database/`
   - Formato: `backup_YYYYMMDD_HHMMSS.sql`
   - Incluye estructura y datos

4. **Limpieza de Sesiones**
   - Elimina archivos de sesión expirados en `/tmp/`
   - Solo si existen

5. **Recalcular Estadísticas**
   - Limpia caché de estadísticas (si existe)
   - Fuerza recálculo en siguiente vista

#### Restricciones
- **Frecuencia:** Máximo 1 vez cada 30 días
- **Validación:** Verifica última ejecución en tabla `mantenimiento_sistema`
- **Registro:** Guarda fecha y usuario que ejecutó
- **UI:** Botón deshabilitado si no han pasado 30 días

### Backups

#### Tipos de Backup
1. **Manual:** Desde Configuración Admin → "Crear Backup Manual"
2. **Automático:** Cada mantenimiento mensual
3. **Recomendado:** Backups semanales programados (cron)

#### Ubicación
```
backups/
  database/
    backup_20251126_143022.sql
    backup_20251120_100015.sql
    ...
```

#### Restauración
- Importar SQL desde phpMyAdmin o línea de comandos
- `mysql -u root aula_innovacion < backup.sql`

---

## ⚙️ CARACTERÍSTICAS TÉCNICAS

### Arquitectura
- **Patrón:** MVC (Model-View-Controller)
- **Backend:** PHP 7.4+ con PDO
- **Base de Datos:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:** Bootstrap 5.3.3, JavaScript ES6+
- **Iconos:** Font Awesome 6.5.0
- **Gráficos:** Chart.js
- **Alertas:** SweetAlert2
- **PDF:** DomPDF

### Estructura de Directorios
```
Reservacion_AIP/
├── app/
│   ├── api/                    # Endpoints AJAX
│   ├── bd/
│   │   └── script.sql          # Base de datos optimizada (12 tablas)
│   ├── config/                 # Configuración (DB, mail, Twilio, AI)
│   ├── controllers/            # Lógica de negocio (15 controladores)
│   ├── lib/                    # Servicios (AI, Mail, SMS, Backup, etc.)
│   ├── middleware/             # Verificación SMS
│   ├── models/                 # Acceso a datos (8 modelos)
│   └── view/                   # Interfaces (25 vistas)
│       └── partials/
│           └── navbar.php      # Navbar unificada con botón Atrás
├── backups/
│   └── database/               # Backups SQL
├── Public/
│   ├── index.php               # Login
│   ├── css/                    # Estilos personalizados
│   ├── js/                     # Scripts JS
│   ├── img/                    # Imágenes
│   └── uploads/
│       └── perfiles/           # Fotos de perfil
└── vendor/                     # Dependencias Composer
```

### Base de Datos (12 Tablas)

#### Tablas Principales
1. **usuarios** - Usuarios del sistema (campos: telefono para SMS, tokens de verificación)
2. **aulas** - Aulas AIP y REGULARES
3. **tipos_equipo** - Categorías de equipos
4. **equipos** - Inventario con stock/stock_maximo
5. **reservas** - Reservas de aulas
6. **prestamos** - Préstamos de equipos (con estado, comentario_devolucion)
7. **reservas_canceladas** - Historial de cancelaciones

#### Tablas de Sistema
8. **notificaciones** - Notificaciones in-app (con metadata JSON)
9. **verification_codes** - Códigos SMS para profesores
10. **configuracion_usuario** - Perfiles (foto, bio)
11. **mantenimiento_sistema** - Registro de mantenimientos
12. **app_config** - Configuración general

### Dependencias (Composer)
```json
{
  "require": {
    "phpmailer/phpmailer": "^6.8",
    "twilio/sdk": "^7.0",
    "dompdf/dompdf": "^2.0"
  }
}
```

### Navegación con Botón "Atrás"

#### Ubicación
- **Navbar:** Entre hamburguesa y logo
- **Visible:** Desktop y móvil
- **Estilo:** `btn-back` (blanco con icono Font Awesome)

#### Lógica Inteligente
```php
// NO muestra en páginas principales
$main_pages = ['Profesor.php', 'Encargado.php', 'Admin.php', 'Dashboard.php'];

// SÍ muestra en todas las demás vistas
$show_back = !$is_main_page && ($tipo === 'Profesor' || $tipo === 'Encargado' || $tipo === 'Administrador');

// Redirige según rol
- Profesor → Profesor.php
- Encargado → Encargado.php
- Administrador → Admin.php
```

### Buscador en Devoluciones

#### Características
- **Búsqueda en:** Nombre de profesor, equipo, aula
- **Case-insensitive:** Usa `LOWER()` en SQL
- **Filtros combinables:**
  - Estado (Prestado/Devuelto)
  - Rango de fechas (Desde - Hasta)
  - Búsqueda por texto
- **Badges de filtros activos:** Muestra qué filtros están aplicados
- **Contador de resultados:** "X registro(s) encontrado(s)"
- **Sin resultados:** Mensaje informativo diferenciado

#### Implementación SQL
```sql
SELECT ... FROM prestamos p
LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
LEFT JOIN aulas a ON p.id_aula = a.id_aula
WHERE 1=1
  AND (LOWER(u.nombre) LIKE ? OR LOWER(e.nombre_equipo) LIKE ? OR LOWER(a.nombre_aula) LIKE ?)
  AND p.estado = ?
  AND p.fecha_prestamo >= ? AND p.fecha_prestamo <= ?
ORDER BY p.fecha_prestamo DESC
```

---

## 📱 INTERFAZ DE USUARIO

### Diseño Responsivo
- **Desktop:** Sidebar (Admin), navbar completa
- **Móvil:** Hamburguesa con offcanvas, navbar compacta
- **Breakpoint:** 992px (lg)

### Paleta de Colores (Brand)
- **Primary:** `#1E6BD6` (Azul institucional)
- **Hover:** `#0F3E91` (Azul oscuro)
- **Light:** `#EAF2FF` (Azul claro)
- **Success:** `#198754` (Verde)
- **Warning:** `#ffc107` (Amarillo)
- **Danger:** `#dc3545` (Rojo)

### Componentes Reutilizables
- **Navbar:** `partials/navbar.php` (única para todos los roles)
- **Cards:** Dashboard principal con iconos y hover
- **Modals:** SweetAlert2 para confirmaciones
- **Forms:** Bootstrap con validación HTML5
- **Tables:** Bootstrap con hover y badges de estado

---

## 🚀 INSTALACIÓN Y CONFIGURACIÓN

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx con mod_rewrite
- Composer
- Cuenta Twilio (para SMS)
- API Key de Claude AI (para Tommibot)

### Pasos de Instalación
1. Clonar repositorio
2. `composer install`
3. Crear BD: `mysql -u root -p < app/bd/script.sql`
4. Configurar `app/config/conexion.php`
5. Configurar `app/config/twilio.php` (credenciales Twilio)
6. Configurar `app/config/ai_config.php` (API Key Claude)
7. Configurar `app/config/mail.php` (SMTP)
8. Crear primer admin: `Public/index.php` → "Crear Administrador"

### Configuración de Backups Automáticos (Opcional)
```bash
# Crontab semanal (domingos 3am)
0 3 * * 0 php /ruta/BackupController.php
```

---

## 📞 SOPORTE Y CONTACTO

**Sistema desarrollado para:**  
Colegio Monseñor Juan Tomis Stack  
Aulas de Innovación Pedagógica

**Versión:** 2.0 (Optimizada)  
**Última actualización:** Noviembre 2025

---

**FIN DE LA DOCUMENTACIÓN**
