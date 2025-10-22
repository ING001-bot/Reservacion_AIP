# 🚀 Instrucciones Rápidas - Sistema de Verificación SMS

## ⚡ Configuración Rápida (5 minutos)

### 1️⃣ Base de Datos
```sql
-- Ejecutar este comando en tu base de datos MySQL
-- (Si aún no existe la tabla verification_codes)

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

-- Verificar que usuarios tengan campo telefono
ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL;
```

### 2️⃣ Configurar Twilio

Editar archivo: `app/config/twilio.php`

```php
<?php
return [
    'account_sid' => 'TU_ACCOUNT_SID_AQUI',
    'auth_token' => 'TU_AUTH_TOKEN_AQUI',
    'from_number' => '+1234567890', // Tu número de Twilio
    'test_mode' => true, // true = modo prueba, false = producción
    'test_number' => '+51999999999' // Tu número para pruebas
];
```

**Obtener credenciales**: https://www.twilio.com/console

### 3️⃣ Registrar Teléfonos

Los docentes deben tener su número de teléfono registrado en el sistema:

```sql
-- Ejemplo: Actualizar teléfono de un usuario
UPDATE usuarios 
SET telefono = '+51987654321' 
WHERE correo = 'profesor@ejemplo.com';
```

**Formato**: Incluir código de país (+51 para Perú)

---

## 🎯 ¿Qué hace el sistema?

### ✅ Verificación SMS Automática

Cuando un docente intenta:
- 📅 **Hacer una reserva de aula**
- 💻 **Solicitar préstamo de equipo**
- 🔒 **Cambiar su contraseña**

**El sistema automáticamente**:
1. Genera un código de 6 dígitos
2. Lo envía por SMS al teléfono del docente
3. Muestra una pantalla para ingresar el código
4. Valida el código y permite la acción

### ⏰ Validación de Anticipación

- ❌ **NO se permite**: Reservas o préstamos para el mismo día
- ✅ **SÍ se permite**: Reservas o préstamos desde mañana en adelante
- 📢 **Aviso visible**: En las interfaces de Reserva y Préstamo

---

## 🎨 Interfaz Mejorada

### Pantalla de Verificación

- 🎨 Diseño moderno con gradientes
- ⚡ Animaciones suaves
- 🔴 Tooltip flotante cuando el código es incorrecto
- ⏱️ Contador de 60 segundos para reenviar código
- ✨ Auto-submit al completar 6 dígitos

### Avisos en Interfaces

En **Reserva de Aulas** y **Préstamo de Equipos**:

```
ℹ️ Importante: Las reservas/préstamos deben realizarse con al menos 
1 día de anticipación. No se permiten reservas/préstamos para el mismo día.
```

---

## 🧪 Modo de Prueba

### Configuración para Desarrollo

En `app/config/twilio.php`:

```php
'test_mode' => true,
'test_number' => '+51999999999' // Tu número personal
```

**Ventaja**: Todos los SMS se envían a tu número, sin importar el teléfono del docente.

### Configuración para Producción

```php
'test_mode' => false,
```

**Resultado**: Los SMS se envían al teléfono real de cada docente.

---

## 🔍 Verificar que Funciona

### Prueba Rápida

1. **Iniciar sesión** como docente
2. **Ir a Reservas** o **Préstamos**
3. **Completar formulario** con fecha de mañana
4. **Enviar**: Deberías recibir un SMS
5. **Ingresar código** en la pantalla
6. **Verificar**: La acción se completa

### Si no funciona

**Revisar**:
- ✅ Configuración de Twilio correcta
- ✅ Usuario tiene teléfono registrado
- ✅ Tabla `verification_codes` existe
- ✅ Créditos de Twilio disponibles

**Ver logs**:
```php
// En app/lib/SmsService.php
// Los errores se registran en el resultado
```

---

## 📱 Formato de Teléfonos

### Correcto ✅
```
+51987654321  (Perú)
+1234567890   (USA)
+34612345678  (España)
```

### Incorrecto ❌
```
987654321     (sin código de país)
51987654321   (sin +)
+51 987 654 321 (con espacios)
```

---

## 🔐 Seguridad

- 🔒 Códigos de 6 dígitos aleatorios
- ⏰ Expiran en 10 minutos
- 🚫 Un solo uso (se marcan como usados)
- 🧹 Limpieza automática de códigos viejos
- 🛡️ Validación en servidor

---

## 🆘 Problemas Comunes

### "No se encontró un número de teléfono válido"

**Solución**: Registrar teléfono del usuario
```sql
UPDATE usuarios SET telefono = '+51987654321' WHERE id_usuario = 1;
```

### "Error al enviar el SMS"

**Causas posibles**:
- Credenciales de Twilio incorrectas
- Sin créditos en Twilio
- Número de teléfono inválido

**Solución**: Revisar `app/config/twilio.php` y cuenta de Twilio

### "Código inválido o expirado"

**Causas posibles**:
- Código expiró (más de 10 minutos)
- Código ya fue usado
- Código incorrecto

**Solución**: Hacer clic en "Reenviar código"

### "Solo puedes reservar a partir del día siguiente"

**Causa**: Intentando reservar para hoy

**Solución**: Seleccionar fecha de mañana o posterior

---

## 📊 Consultas Útiles

### Ver códigos recientes
```sql
SELECT u.nombre, vc.code, vc.action_type, vc.created_at, vc.expires_at, vc.used
FROM verification_codes vc
JOIN usuarios u ON vc.user_id = u.id_usuario
ORDER BY vc.created_at DESC
LIMIT 10;
```

### Limpiar códigos expirados
```sql
DELETE FROM verification_codes WHERE expires_at <= NOW();
```

### Ver usuarios sin teléfono
```sql
SELECT id_usuario, nombre, correo 
FROM usuarios 
WHERE telefono IS NULL OR telefono = '';
```

---

## 🎯 Checklist Final

Antes de usar en producción:

- [ ] Tabla `verification_codes` creada
- [ ] Campo `telefono` en tabla `usuarios`
- [ ] Configuración de Twilio completada
- [ ] Teléfonos de docentes registrados
- [ ] Probado en modo test
- [ ] `test_mode` cambiado a `false`
- [ ] Créditos de Twilio suficientes

---

## 📞 Soporte

**Documentación completa**: Ver archivo `VERIFICACION_SMS_README.md`

**Twilio Console**: https://www.twilio.com/console

**Twilio Docs**: https://www.twilio.com/docs/sms

---

## ✨ ¡Listo para Usar!

El sistema está completamente implementado y funcional. Solo necesitas:

1. ✅ Configurar Twilio
2. ✅ Ejecutar SQL
3. ✅ Registrar teléfonos
4. ✅ ¡Probar!

**¡Disfruta del nuevo sistema de seguridad!** 🎉
