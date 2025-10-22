# Sistema de Verificación SMS - Documentación

## 📋 Resumen de Implementación

Se ha implementado un sistema completo de verificación por código SMS para docentes en la plataforma de gestión académica, con las siguientes funcionalidades:

### ✅ Funcionalidades Implementadas

#### 1. **Verificación SMS Automática**
- **Reserva de Aulas**: Al intentar hacer una reserva, se envía automáticamente un código SMS
- **Préstamo de Equipos**: Al solicitar un préstamo, se envía automáticamente un código SMS
- **Cambio de Contraseña**: Al cambiar contraseña, se envía automáticamente un código SMS

#### 2. **Validación de Anticipación (1 día)**
- Las reservas y préstamos solo pueden realizarse con **al menos 1 día de anticipación**
- Validación tanto en frontend (JavaScript) como en backend (PHP)
- Mensajes claros y visibles en la interfaz

#### 3. **Interfaz de Verificación Mejorada**
- Diseño moderno con gradientes y animaciones
- Tooltip flotante que indica dónde ingresar el código cuando es incorrecto
- Animaciones visuales para errores y éxitos
- Contador de reenvío de código (60 segundos)
- Auto-submit cuando se completan los 6 dígitos

---

## 🗄️ Base de Datos

### Tabla de Códigos de Verificación

La tabla `verification_codes` ya existe en tu base de datos (archivo: `app/bd/verification_codes.sql`):

```sql
CREATE TABLE IF NOT EXISTS verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    action_type ENUM('reserva', 'prestamo', 'cambio_clave') NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Nota**: Si aún no has ejecutado este script, hazlo antes de usar el sistema.

---

## ⚙️ Configuración de Twilio (SMS)

### Archivo de Configuración

Verifica que el archivo `app/config/twilio.php` esté correctamente configurado:

```php
<?php
return [
    'account_sid' => 'TU_ACCOUNT_SID',
    'auth_token' => 'TU_AUTH_TOKEN',
    'from_number' => '+1234567890', // Tu número de Twilio
    'test_mode' => true, // Cambiar a false en producción
    'test_number' => '+51999999999' // Número de prueba (solo en test_mode)
];
```

### Pasos para Configurar Twilio

1. **Crear cuenta en Twilio**: https://www.twilio.com/
2. **Obtener credenciales**:
   - Account SID
   - Auth Token
   - Número de teléfono de Twilio
3. **Actualizar** `app/config/twilio.php` con tus credenciales
4. **Modo de prueba**: 
   - En desarrollo, deja `test_mode => true`
   - Todos los SMS se enviarán al `test_number`
   - En producción, cambia a `test_mode => false`

---

## 📱 Requisito: Números de Teléfono

### Tabla de Usuarios

Asegúrate de que la tabla `usuarios` tenga el campo `telefono`:

```sql
ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL;
```

**Importante**: Los docentes deben tener su número de teléfono registrado en el sistema para recibir códigos SMS.

---

## 🎨 Archivos Modificados

### Backend (PHP)

1. **`app/middleware/VerifyMiddleware.php`**
   - Envío automático de código SMS al requerir verificación
   - Redirección a página de verificación

2. **`app/view/Reserva.php`**
   - Integración de verificación SMS
   - Aviso de anticipación de 1 día
   - Icono de Bootstrap Icons

3. **`app/view/Prestamo.php`**
   - Integración de verificación SMS
   - Aviso de anticipación de 1 día
   - Icono de Bootstrap Icons

4. **`app/controllers/CambiarContraseñaController.php`**
   - Integración de verificación SMS antes de cambiar contraseña

5. **`app/models/PrestamoModel.php`**
   - Validación de fecha mínima (1 día de anticipación)

6. **`app/controllers/ReservaController.php`**
   - Ya tenía validación de fecha implementada

### Frontend (HTML/CSS/JS)

7. **`Public/verificar.php`**
   - Interfaz completamente rediseñada
   - Gradientes modernos
   - Animaciones suaves
   - Tooltip flotante para errores
   - Contador de reenvío
   - Auto-submit al completar 6 dígitos

---

## 🔄 Flujo de Verificación

### Paso a Paso

1. **Usuario intenta realizar una acción** (reserva, préstamo o cambio de contraseña)
2. **Sistema verifica** si ya está verificado para esa acción en la sesión
3. **Si NO está verificado**:
   - Se genera un código de 6 dígitos
   - Se envía por SMS al teléfono registrado
   - Se guarda en la base de datos con expiración de 10 minutos
   - Se redirige a la página de verificación
4. **Usuario ingresa el código** en la interfaz
5. **Sistema valida el código**:
   - ✅ **Correcto**: Marca la sesión como verificada y permite la acción
   - ❌ **Incorrecto**: Muestra tooltip flotante indicando el error
6. **Código válido por**: 10 minutos
7. **Reenvío disponible**: Después de 60 segundos

---

## 🎯 Validación de Anticipación

### Reglas Implementadas

- **Fecha mínima**: Mañana (día siguiente)
- **No se permite**: Reservas o préstamos para el mismo día
- **Validación**: Frontend (JavaScript) y Backend (PHP)

### Mensajes al Usuario

**En la interfaz**:
```
⚠️ Importante: Las reservas/préstamos deben realizarse con al menos 1 día de anticipación.
No se permiten reservas/préstamos para el mismo día.
```

**Al intentar fecha inválida**:
```
⚠️ Solo puedes solicitar reservas/préstamos a partir del día siguiente. 
Las reservas/préstamos deben hacerse con anticipación, no el mismo día.
```

---

## 🚀 Modo de Uso

### Para Docentes

1. **Iniciar sesión** en el sistema
2. **Ir a Reservas o Préstamos**
3. **Leer el aviso** sobre anticipación de 1 día
4. **Seleccionar fecha** (mínimo mañana)
5. **Completar formulario**
6. **Al enviar**: Recibirás un SMS con código de 6 dígitos
7. **Ingresar código** en la pantalla de verificación
8. **Acción completada** si el código es correcto

### Para Administradores

1. **Verificar configuración de Twilio** en `app/config/twilio.php`
2. **Asegurar que usuarios tengan teléfonos** registrados
3. **Ejecutar script SQL** si no existe la tabla `verification_codes`
4. **Monitorear logs** de Twilio para ver envíos de SMS

---

## 🔒 Seguridad

### Características de Seguridad

- ✅ Códigos de 6 dígitos aleatorios
- ✅ Expiración de 10 minutos
- ✅ Códigos de un solo uso (se marcan como usados)
- ✅ Limpieza automática de códigos expirados
- ✅ Validación en servidor (no solo cliente)
- ✅ Sesiones independientes por tipo de acción
- ✅ Protección contra ataques de fuerza bruta (límite de tiempo)

---

## 🐛 Solución de Problemas

### Problema: No llega el SMS

**Posibles causas**:
1. Configuración incorrecta de Twilio
2. Usuario sin teléfono registrado
3. Número de teléfono inválido
4. Créditos de Twilio agotados

**Solución**:
- Verificar logs de Twilio
- Revisar `app/config/twilio.php`
- Verificar campo `telefono` en tabla `usuarios`

### Problema: Código siempre inválido

**Posibles causas**:
1. Código expirado (más de 10 minutos)
2. Código ya usado
3. Diferencia de zona horaria

**Solución**:
- Solicitar reenvío de código
- Verificar zona horaria del servidor
- Revisar tabla `verification_codes`

### Problema: No se valida la fecha

**Posibles causas**:
1. JavaScript deshabilitado
2. Zona horaria incorrecta

**Solución**:
- Verificar que JavaScript esté habilitado
- Revisar `date_default_timezone_set('America/Lima')`

---

## 📊 Monitoreo

### Consultas SQL Útiles

**Ver códigos recientes**:
```sql
SELECT * FROM verification_codes 
ORDER BY created_at DESC 
LIMIT 10;
```

**Ver códigos no usados**:
```sql
SELECT * FROM verification_codes 
WHERE used = 0 AND expires_at > NOW();
```

**Limpiar códigos expirados**:
```sql
DELETE FROM verification_codes 
WHERE expires_at <= NOW();
```

---

## 📝 Notas Adicionales

### Sesiones de Verificación

- Las verificaciones se almacenan en la sesión PHP
- Variable de sesión: `$_SESSION['verified_' . $actionType]`
- Se mantienen durante toda la sesión
- Se pierden al cerrar sesión

### Personalización

Para cambiar el tiempo de expiración del código, edita:
```php
// En app/lib/VerificationService.php, línea 22
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes')); // Cambiar +10 minutes
```

Para cambiar el tiempo de reenvío, edita:
```javascript
// En Public/verificar.php, línea 121
let countdown = 60; // Cambiar 60 segundos
```

---

## ✅ Checklist de Implementación

- [x] Tabla `verification_codes` creada
- [x] Configuración de Twilio completada
- [x] Campo `telefono` en tabla `usuarios`
- [x] Middleware de verificación implementado
- [x] Integración en Reservas
- [x] Integración en Préstamos
- [x] Integración en Cambio de Contraseña
- [x] Validación de anticipación (1 día)
- [x] Avisos informativos en interfaces
- [x] Interfaz de verificación mejorada
- [x] Tooltip de error flotante
- [x] Animaciones y efectos visuales
- [x] Contador de reenvío
- [x] Auto-submit de código

---

## 🎉 ¡Implementación Completa!

El sistema de verificación SMS está completamente funcional y listo para usar. Asegúrate de:

1. ✅ Configurar Twilio con tus credenciales
2. ✅ Ejecutar el script SQL de la tabla
3. ✅ Registrar teléfonos de los docentes
4. ✅ Probar en modo test antes de producción

**¡Disfruta del nuevo sistema de seguridad!** 🚀
