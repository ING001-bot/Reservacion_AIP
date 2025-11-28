<?php
/**
 * Servicio de consultas inteligentes para Tommibot
 * Sistema local basado en base de datos (sin IA externa)
 */

require_once __DIR__ . '/../config/conexion.php';

class AIService {
    private $db;
    private $statsCache = null;
    private $statsCacheTime = 0;
    private $statsCacheDuration = 300; // 5 minutos
    
    // Guías paso a paso detalladas para Profesor
    private const GUIDE_RESERVA = "
📝 **GUÍA PASO A PASO: Cómo hacer una RESERVA de aula AIP**

⚠️ **RECORDATORIO IMPORTANTE SMS:**
Cuando entres al módulo 'Reservar Aula', el sistema te enviará AUTOMÁTICAMENTE un código de 6 dígitos por SMS a tu teléfono registrado. DEBES ingresar ese código en la ventana emergente para verificarte. Sin verificación, NO podrás continuar.

✅ **PASOS DETALLADOS:**

**PASO 1: Ingresar al módulo**
- Desde el dashboard de Profesor, haz clic en el botón **'📅 Reservar Aula'**
- O desde la navbar superior: **Profesor → Reservar Aula**
- Aparecerá INMEDIATAMENTE una ventana emergente con fondo oscuro bloqueando la pantalla

**PASO 2: Verificación SMS (AUTOMÁTICA - YA SE ENVIÓ)**
- El sistema YA TE ENVIÓ el SMS al cargar la página (NO necesitas solicitarlo)
- Verás el mensaje: _\"Hemos enviado un código de 6 dígitos a tu teléfono registrado\"_
- Revisa tu teléfono (+51XXXXXXXXX)
- Copia el código de 6 dígitos que recibiste (ejemplo: 123456)
- Pégalo en el campo grande de la ventana emergente
- Haz clic en **'Verificar Código'**
- ⏰ El código expira en **10 minutos**
- Si no llegó, haz clic en **'Reenviar código'** (espera 1 minuto entre envíos)

**PASO 3: Llenar el formulario de reserva**
Una vez verificado, la ventana desaparecerá y verás el formulario principal con 2 paneles:

**Panel Izquierdo (Formulario):**
- **Profesor:** Tu nombre (campo bloqueado, automático)
- **Aula AIP Disponible:** Desplegable con aulas tipo AIP activas
  - Formato: \"AIP 1 - Capacidad: 30 personas (AIP)\"
  - SOLO se muestran aulas AIP, NO aparecen aulas REGULARES
- **Fecha:** Selector de calendario (MÍNIMO mañana)
  - ❌ NO se puede reservar para HOY (validación automática)
  - ✅ Puedes reservar desde mañana en adelante
- **Hora Inicio:** Campo de hora (formato 24hrs: 06:00 - 18:00)
- **Hora Fin:** Campo de hora (debe ser mayor que inicio)
  - Rango permitido: 6:00 AM a 7:00 PM
  - Bloques de 45 minutos recomendados

**Panel Derecho (Disponibilidad Visual):**
- Muestra TODOS los bloques de 45 minutos del día seleccionado
- **Turnos:**
  - 🌅 Mañana: 6:00 AM - 12:45 PM (verde)
  - 🌙 Tarde: 1:00 PM - 7:00 PM (naranja)
- **Colores de bloques:**
  - 🟢 Verde: Disponible (puedes reservar)
  - 🔴 Rojo: Ocupado (otra reserva existe)
  - 🔵 Azul: Seleccionado (al hacer clic)
- **Interacción:**
  - Haz clic en bloque INICIO → se marca azul
  - Haz clic en bloque FIN → se marcan todos los bloques intermedios
  - Los campos de hora se llenan AUTOMÁTICAMENTE
  - Botón \"Limpiar selección\" para reiniciar

**PASO 4: Validar datos**
Antes de confirmar, verifica:
- ✅ Fecha sea mínimo MAÑANA (no hoy)
- ✅ Hora de inicio sea menor que hora de fin
- ✅ No hay conflicto con otras reservas (bloques verdes disponibles)
- ✅ Horario esté en rango permitido (6:00 - 19:00)

**PASO 5: Confirmar reserva**
- Haz clic en el botón azul **'Reservar'**
- Aparecerá un popup de confirmación de SweetAlert con:
  - Título: \"¿Confirmar reserva?\"
  - Texto: \"Se registrará la reserva con los datos seleccionados\"
  - Botón verde \"Sí, reservar\"
  - Botón gris \"Cancelar\"
- Si confirmas, el sistema enviará los datos al servidor

**PASO 6: Procesamiento del servidor**
El sistema validará:
1. ✅ Código SMS fue verificado (sesión válida)
2. ✅ Todos los campos están completos
3. ✅ Fecha es válida (mínimo mañana)
4. ✅ Horas están en rango permitido (6:00-19:00)
5. ✅ Aula existe y está activa tipo AIP
6. ✅ NO hay conflicto con otras reservas en esa fecha/hora
7. 💾 Si todo OK, se crea el registro en tabla `reservas`
8. 🔔 Se envía notificación al Encargado automáticamente

**PASO 7: Confirmación final**
- Verás un mensaje verde de éxito: ✅ \"Reserva realizada correctamente\"
- Aparecerá una notificación en tu campana 🔔 (navbar superior derecha)
- La reserva se agregará automáticamente a la tabla \"Mis Reservas Activas\" en la misma página
- Podrás ver detalles: fecha, hora inicio/fin, aula, capacidad

**PASO 8: Ver tu reserva**
Para confirmar, puedes ir a:
- **Opción 1:** Sección \"Mis Reservas Activas\" en la misma página de Reservar Aula
- **Opción 2:** Menú **\"Mi Historial\"** → Pestaña \"Historial/Reserva\" → Ver calendario semanal
- **Opción 3:** Notificaciones 🔔 → Click en la notificación de confirmación

❌ **ERRORES COMUNES Y SOLUCIONES:**

**Error: \"Debes verificar tu identidad con el código SMS\"**
- Causa: No ingresaste el código SMS o expiró (10 minutos)
- Solución: Recarga la página (F5) y se reenviará un nuevo código

**Error: \"Solo puedes reservar a partir del día siguiente\"**
- Causa: Intentas reservar para HOY
- Solución: Selecciona fecha de MAÑANA en adelante

**Error: \"La hora de inicio debe ser menor a la hora de fin\"**
- Causa: Hora fin es igual o anterior a hora inicio
- Solución: Ajusta las horas correctamente (inicio < fin)

**Error: \"Aula ocupada en el horario seleccionado\"**
- Causa: Otro profesor ya reservó esa aula en ese horario
- Solución: Elige otro horario (bloques verdes) o selecciona otra aula AIP

**Error: \"No hay aulas AIP disponibles\"**
- Causa: Administrador no ha creado aulas tipo AIP o están desactivadas
- Solución: Contacta al administrador del sistema

❌ **SI NO TE LLEGA EL SMS:**
1. Verifica que tu número esté registrado en formato **+51XXXXXXXXX** (código país + 9 dígitos)
2. Revisa que tu celular tenga señal y esté encendido
3. Espera hasta 2 minutos (algunos operadores tardan)
4. Contacta al administrador para validar tu número en la BD
5. Intenta con \"Reenviar código\" desde la ventana emergente

📌 **REGLAS Y RESTRICCIONES:**
- ✅ Anticipación MÍNIMA: 1 día (reservar desde mañana)
- ❌ NO se puede reservar para el MISMO día
- ⏰ Horario permitido: 6:00 AM - 7:00 PM (hora de Perú UTC-5)
- 🏫 Solo aulas tipo **AIP** (NO aulas REGULARES)
- 🔒 Requiere verificación SMS CADA vez que entras al módulo
- ❌ Cancelación solo el MISMO DÍA de crear la reserva
- 📅 Una reserva = 1 aula + 1 franja horaria + 1 fecha
- 🔔 Notificaciones automáticas al crear/cancelar

📌 **DIFERENCIA: AULAS AIP vs REGULARES**
- **Aulas AIP:** SOLO para RESERVAS de espacios físicos (esta guía)
- **Aulas REGULARES:** SOLO para PRÉSTAMOS de equipos (laptop, proyector, etc.)
- NO puedes hacer préstamos en aulas AIP
- NO puedes hacer reservas en aulas REGULARES

📌 **PRÓXIMOS PASOS:**
- Para CANCELAR esta reserva: Ver guía \"Cómo cancelar una reserva\"
- Para PRÉSTAMOS de equipos: Ver guía \"Cómo solicitar un préstamo\"
- Para VER tu historial: Ir a **Mi Historial** → Calendarios semanales + exportar PDF
";

    private const GUIDE_PRESTAMO = "
📦 **GUÍA PASO A PASO: Cómo solicitar un PRÉSTAMO de equipo**

⚠️ **RECORDATORIO IMPORTANTE SMS:**
Cuando entres al módulo 'Préstamo de Equipos', el sistema te enviará AUTOMÁTICAMENTE un código de 6 dígitos por SMS. DEBES ingresar ese código para verificarte. Sin verificación, NO podrás continuar.

✅ **PASOS DETALLADOS:**

**PASO 1: Ingresar al módulo**
- Desde el dashboard de Profesor, haz clic en **'💻 Préstamo de Equipos'**
- O desde la navbar: **Profesor → Préstamo de Equipos**
- Aparecerá INMEDIATAMENTE una ventana emergente bloqueando la pantalla

**PASO 2: Verificación SMS (AUTOMÁTICA - YA SE ENVIÓ)**
- El sistema YA TE ENVIÓ el SMS al cargar la página (NO necesitas solicitarlo)
- Verás: _\"Hemos enviado un código de 6 dígitos a tu teléfono registrado\"_
- Revisa tu teléfono (+51XXXXXXXXX)
- Ingresa el código de 6 dígitos en la ventana emergente
- Haz clic en **'Verificar Código'**
- ⏰ El código expira en **10 minutos**
- Si no llegó, haz clic en **'Reenviar código'**

**PASO 3: Llenar el formulario de préstamo**
Una vez verificado, la ventana desaparece y ves el formulario principal:

**Campos obligatorios:**
1. **Aula REGULAR Disponible:**
   - Desplegable con aulas tipo REGULAR activas
   - Formato: \"Aula 1 - Capacidad: 25 personas (REGULAR)\"
   - ⚠️ SOLO se muestran aulas REGULARES, NO aulas AIP
   - Aquí se usará el equipo (tu salón de clase)

2. **Fecha de Préstamo:**
   - Selector de calendario (MÍNIMO mañana)
   - ❌ NO se puede prestar para HOY
   - ✅ Desde mañana en adelante
   - Fecha en que USARÁS el equipo

3. **Hora de inicio:**
   - Formato 24hrs (ejemplo: 08:00)
   - Hora en que RECOGERÁS el equipo del Encargado

4. **Hora de fin:**
   - Formato 24hrs (ejemplo: 12:00)
   - Hora en que DEVOLVERÁS el equipo al Encargado
   - Debe ser mayor que hora de inicio

**PASO 4: Seleccionar equipos (IMPORTANTE)**
El formulario muestra 5 secciones de equipos:

**Equipos OBLIGATORIOS (siempre selecciona):**

📱 **Laptop:**
- Desplegable con laptops disponibles
- Formato: \"LAPTOP 001 (Stock disponible: 5)\"
- Si no hay stock, dice: \"(Sin stock disponible)\"
- Debes seleccionar UNA laptop

🖥️ **Proyector:**
- Desplegable con proyectores disponibles
- Formato: \"PROYECTOR 001 (Stock disponible: 3)\"
- Debes seleccionar UN proyector

**Equipos OPCIONALES (checkbox para activar):**

🖱️ **Mouse (Opcional):**
- Marca el checkbox ☑️ \"Incluir mouse\"
- Se activa el desplegable de mouses
- Selecciona uno si lo necesitas

🔌 **Extensión (Opcional):**
- Marca el checkbox ☑️ \"Incluir extensión\"
- Se activa el desplegable de extensiones
- Selecciona una si la necesitas

🔊 **Parlante (Opcional):**
- Marca el checkbox ☑️ \"Incluir parlante\"
- Se activa el desplegable de parlantes
- Selecciona uno si lo necesitas

**PASO 5: Validar stock en tiempo real**
- El sistema muestra stock disponible PARA LA FECHA seleccionada
- Si cambias la fecha, el stock se recalcula automáticamente
- Si un equipo dice \"Sin stock disponible\":
  - Opción 1: Cambia la fecha de préstamo
  - Opción 2: Elige otro equipo del mismo tipo
  - Opción 3: Contacta al administrador

**Ejemplo de agrupación inteligente:**
Si seleccionas:
- ✅ Laptop 001
- ✅ Proyector 001
- ✅ Mouse 001 (opcional)
- ✅ Extensión 001 (opcional)

El sistema creará **4 registros individuales en la BD** pero los agrupará como **1 pack** en notificaciones y historial para fácil seguimiento.

**PASO 6: Confirmar préstamo**
- Verifica todos los datos (fecha, hora, equipos seleccionados)
- Haz clic en el botón verde **'Solicitar Préstamo'**
- El sistema validará:
  1. ✅ Código SMS verificado
  2. ✅ Todos los campos completos
  3. ✅ Fecha válida (mínimo mañana)
  4. ✅ Horas válidas (inicio < fin)
  5. ✅ Aula existe y es tipo REGULAR
  6. ✅ Stock suficiente para CADA equipo
  7. ✅ NO hay conflicto con otros préstamos
  8. 💾 Si OK, crea registros en tabla `prestamos`
  9. 📉 Disminuye el stock automáticamente

**PASO 7: Confirmación del sistema**
- Mensaje verde de éxito: ✅ \"Préstamo solicitado correctamente\"
- Notificación 🔔 en tu campana (navbar superior)
- Los equipos quedan en estado **\"Prestado\"**
- El stock disminuye (ejemplo: Stock 5 → Stock 4)

**PASO 8: Recojo físico del equipo**
- En la fecha/hora indicada, acude al **Encargado del AIP**
- Lleva tu identificación (DNI o carnet)
- El Encargado:
  1. Verifica tu identidad
  2. Busca tu préstamo en el sistema
  3. Prepara FÍSICAMENTE los equipos solicitados
  4. Inspecciona visualmente el estado (pantalla, teclado, cables, etc.)
  5. Te entrega los equipos
  6. Firma un registro interno (opcional según colegio)
- ⚠️ Revisa TÚ TAMBIÉN el estado antes de llevártelos

**PASO 9: Uso del equipo**
- Usa los equipos en el **aula REGULAR** especificada
- Cuida el material (son recursos limitados del colegio)
- Evita comer/beber cerca de los equipos
- NO permitas que estudiantes los muevan entre salones
- Reporta cualquier daño INMEDIATAMENTE al Encargado

**PASO 10: Devolución física (CRÍTICO)**
- Al terminar (hora fin indicada o antes), devuelve al **Encargado**
- El Encargado hará una **inspección detallada** y registrará en el sistema:

**Estados posibles de devolución:**
1. ✅ **OK:** Equipo en perfecto estado, funciona correctamente
   - Stock se restaura automáticamente (Stock 4 → Stock 5)
   - No hay registro de incidencia
   
2. ⚠️ **Dañado:** Equipo con fallas (pantalla rota, teclas faltantes, no enciende, etc.)
   - El Encargado escribe comentario detallado: _\"Pantalla con fisura diagonal\"_
   - Se genera notificación al Administrador
   - Stock NO se restaura (equipo queda fuera de circulación)
   - Posible sanción según reglamento del colegio
   
3. ⚠️ **Falta accesorio:** Equipo funcional pero falta cable/mouse/adaptador
   - El Encargado anota: _\"Falta cable de poder\"_
   - Stock se restaura PARCIALMENTE
   - Debes reponer el accesorio

- El Encargado registra la devolución en: **Encargado → Devolver Equipos**
- Tú recibes notificación 🔔 confirmando la devolución

**PASO 11: Ver tu préstamo**
Para seguimiento, revisa:
- **Opción 1:** **Mi Historial** → Pestaña \"Historial/Equipos\" → Ver calendario semanal
- **Opción 2:** Notificaciones 🔔 → Ver detalles del pack prestado
- **Opción 3:** Mismo módulo \"Préstamo de Equipos\" → Tabla \"Mis Préstamos Activos\"

❌ **ERRORES COMUNES Y SOLUCIONES:**

**Error: \"Solo puedes solicitar préstamos a partir del día siguiente\"**
- Causa: Intentas prestar para HOY
- Solución: Selecciona fecha de MAÑANA en adelante (anticipación mínima)

**Error: \"Debes seleccionar al menos un equipo\"**
- Causa: No seleccionaste laptop Y proyector
- Solución: Marca AMBOS equipos obligatorios

**Error: \"El aula seleccionada no existe o está inactiva\"**
- Causa: Aula fue desactivada por administrador
- Solución: Recarga página (F5) y elige otra aula REGULAR

**Error: \"No hay stock disponible para [equipo]\"**
- Causa: Todos los equipos de ese tipo están prestados para esa fecha
- Solución: Cambia la fecha O elige otro equipo del mismo tipo

**Error: \"Debes verificar tu identidad con el código SMS\"**
- Causa: No ingresaste código SMS o expiró
- Solución: Recarga página y reingresa el código nuevo

**Error: \"No hay aulas REGULAR disponibles\"**
- Causa: Administrador no creó aulas tipo REGULAR
- Solución: Contacta al administrador del sistema

❌ **SI NO TE LLEGA EL SMS:**
1. Verifica formato **+51XXXXXXXXX** (código país + 9 dígitos)
2. Revisa señal móvil
3. Espera hasta 2 minutos
4. Haz clic en \"Reenviar código\" (espera 1 min entre envíos)
5. Contacta al administrador para validar tu número

📌 **REGLAS Y RESTRICCIONES:**
- ✅ Anticipación MÍNIMA: 1 día (prestar desde mañana)
- ❌ NO se puede prestar para el MISMO día
- 🏫 Solo aulas tipo **REGULAR** (NO aulas AIP)
- 🔒 Requiere verificación SMS CADA vez que entras
- ⚠️ Solo el **Encargado** puede registrar devoluciones (inspección física)
- 📉 Stock disminuye automáticamente al prestar
- 📈 Stock aumenta automáticamente al devolver (si estado = OK)
- 🔔 Notificaciones automáticas: confirmación, devolución, vencimiento
- ⏰ Préstamos vencidos generan alertas al Encargado

📌 **DIFERENCIA: AULAS AIP vs REGULARES**
- **Aulas REGULARES:** SOLO para PRÉSTAMOS de equipos (esta guía)
- **Aulas AIP:** SOLO para RESERVAS de espacios físicos (otra guía)
- NO puedes hacer préstamos en aulas AIP
- NO puedes hacer reservas en aulas REGULARES

📌 **AGRUPACIÓN INTELIGENTE DE EQUIPOS:**
El sistema agrupa automáticamente tus equipos como **1 pack** en:
- Notificaciones (muestra: \"Pack: LAPTOP 001, PROYECTOR 001, MOUSE 001\")
- Historial visual (1 bloque en calendario = 1 pack completo)
- Pero en BD se registran como **registros individuales** para control preciso de stock

📌 **PRÓXIMOS PASOS:**
- Para DEVOLVER: El Encargado lo hace desde su módulo (inspección obligatoria)
- Para VER historial: **Mi Historial** → Pestaña \"Historial/Equipos\"
- Para EXPORTAR PDF: **Mi Historial** → Botón \"Descargar PDF\" (incluye préstamos)

❌ **SI NO TE LLEGA EL SMS:**
1. Verifica que tu número esté en formato +51XXXXXXXXX
2. Contacta al administrador
3. Revisa tu señal móvil

📌 **NOTAS IMPORTANTES:**
- Las aulas REGULARES son EXCLUSIVAS para préstamos (NO para reservas de aula)
- Solo el Encargado puede registrar devoluciones tras inspección física
- El sistema controla automáticamente el stock disponible
- Los préstamos vencidos generan alertas
";

    private const GUIDE_CAMBIAR_CLAVE = "
🔐 **GUÍA PASO A PASO: Cómo CAMBIAR TU CONTRASEÑA**

⚠️ **RECORDATORIO IMPORTANTE SMS:**
Cuando entres a 'Cambiar Contraseña', el sistema te enviará AUTOMÁTICAMENTE un código de 6 dígitos por SMS. DEBES ingresar ese código para verificarte. Sin verificación, NO podrás continuar.

✅ **PASOS DETALLADOS:**

**PASO 1: Acceder al módulo**
- En el menú superior derecho, haz clic en tu **foto de perfil**
- Selecciona **'Cambiar Contraseña'** del menú desplegable (icono 🔒)
- O desde la barra lateral, haz clic en **'Cambiar Contraseña'**

**PASO 2: Verificación SMS (AUTOMÁTICA)**
- El sistema YA TE ENVIÓ el SMS al entrar (NO necesitas solicitarlo)
- Revisa tu teléfono (+51XXXXXXXXX)
- Ingresa el código de 6 dígitos en la ventana emergente
- Haz clic en 'Verificar'
- ⏰ El código expira en 10 minutos

**PASO 3: Completar el formulario**
Una vez verificado, ingresa:
1. **Contraseña Actual:** Tu contraseña actual (la que usas para entrar)
2. **Nueva Contraseña:** Tu nueva contraseña
   - Mínimo 8 caracteres
   - Se recomienda: mayúsculas, minúsculas, números, símbolos
3. **Confirmar Nueva Contraseña:** Repite exactamente la nueva contraseña

**PASO 4: Validar**
- Verifica que las dos nuevas contraseñas sean IDÉNTICAS
- Asegúrate de recordar la contraseña actual

**PASO 5: Guardar**
- Haz clic en el botón **'Cambiar Contraseña'**
- El sistema validará la contraseña actual
- Si es correcta, guardará la nueva

**PASO 6: Confirmación**
- Verás un mensaje de éxito ✅
- Tu sesión se cerrará automáticamente
- Deberás iniciar sesión con la NUEVA contraseña

❌ **SI NO TE LLEGA EL SMS:**
1. Verifica tu número registrado (+51XXXXXXXXX)
2. Contacta al administrador
3. Revisa tu señal móvil

⚠️ **ERRORES COMUNES:**
- **'Contraseña actual incorrecta':** Verifica que estés ingresando tu contraseña actual correctamente
- **'Las contraseñas no coinciden':** Asegúrate de escribir EXACTAMENTE la misma nueva contraseña dos veces
- **'Contraseña muy corta':** Debe tener mínimo 8 caracteres

📌 **CONSEJOS DE SEGURIDAD:**
- NO compartas tu contraseña con nadie
- Usa una combinación de letras, números y símbolos
- Cambia tu contraseña periódicamente
- NO uses contraseñas obvias (nombre, fecha de nacimiento, etc.)
";

    private const GUIDE_CANCELAR_RESERVA = "
❌ **GUÍA PASO A PASO: Cómo CANCELAR una RESERVA**

⚠️ **REGLA CRÍTICA:**
Solo puedes cancelar una reserva el MISMO DÍA en que la creaste. Si pasó más de un día, ya NO podrás cancelarla desde el sistema.

✅ **PASOS DETALLADOS:**

**PASO 1: Ir a tu historial**
- Desde el dashboard de Profesor, haz clic en **'Mi Historial'** (icono 📜)
- Verás la lista de todas tus reservas y préstamos

**PASO 2: Filtrar (opcional)**
- Usa el filtro **'Tipo'** y selecciona **'Reserva'**
- Usa el filtro **'Estado'** y selecciona **'Confirmada'**
- Esto mostrará solo reservas activas

**PASO 3: Localizar la reserva**
- Busca la reserva que deseas cancelar
- Verifica la fecha y hora
- Confirma que es la CORRECTA antes de cancelar

**PASO 4: Verificar condición de cancelación**
- Verifica que la reserva se haya creado HOY
- Si fue creada AYER o antes, el botón de cancelar NO aparecerá

**PASO 5: Cancelar**
- Haz clic en el botón **'Cancelar Reserva'** (icono ❌) de la fila correspondiente
- Aparecerá una ventana de confirmación

**PASO 6: Confirmar cancelación**
- Lee el mensaje de advertencia
- Si estás seguro, haz clic en **'Sí, cancelar'**
- Si te arrepientes, haz clic en **'No, mantener'**

**PASO 7: Verificación final**
- El sistema moverá la reserva a la tabla **'reservas_canceladas'**
- Verás un mensaje de éxito ✅
- La reserva desaparecerá de tu historial activo
- El aula quedará disponible nuevamente para otros profesores

📌 **NOTAS IMPORTANTES:**
- Una vez cancelada, NO puedes revertir la acción
- Si necesitas el aula nuevamente, deberás crear una NUEVA reserva
- Recuerda la verificación SMS al volver a reservar
- Las cancelaciones se registran en el historial del sistema

❌ **SI NO PUEDES CANCELAR:**
- **'Botón no visible':** La reserva fue creada hace más de 1 día (ya no se puede cancelar)
- **'Error al cancelar':** Contacta al administrador
- **Solución alternativa:** Contacta directamente al administrador para cancelaciones tardías
";

    private const GUIDE_SMS_TROUBLESHOOTING = "
📱 **GUÍA: Solución de problemas con SMS**

❓ **¿POR QUÉ NO ME LLEGA EL CÓDIGO SMS?**

🔍 **DIAGNÓSTICO RÁPIDO:**

**PROBLEMA 1: Número mal registrado**
✅ Solución:
1. Verifica que tu número esté en formato internacional: **+51XXXXXXXXX**
2. Contacta al administrador para verificar/corregir tu número
3. NO debe tener espacios, guiones ni paréntesis
4. Debe iniciar con +51 (código de Perú)

**PROBLEMA 2: Sin señal móvil**
✅ Solución:
1. Verifica que tu celular tenga señal
2. Revisa que no esté en modo avión
3. Intenta salir y volver al módulo para reenviar el SMS

**PROBLEMA 3: Operadora bloqueada**
✅ Solución:
1. Algunos operadores bloquean SMS automáticos
2. Agrega el número del sistema a tus contactos
3. Verifica la configuración de spam en tu celular

**PROBLEMA 4: Código expirado**
✅ Solución:
1. El código expira en 10 minutos
2. Si pasó el tiempo, sal del módulo y vuelve a entrar
3. Se enviará un NUEVO código automáticamente

**PROBLEMA 5: Buzón lleno**
✅ Solución:
1. Elimina mensajes antiguos de tu celular
2. Libera espacio en tu bandeja SMS
3. Intenta nuevamente

📞 **CONTACTO DE EMERGENCIA:**
Si ninguna solución funciona:
1. Contacta al **Administrador del Sistema**
2. Proporciona tu nombre completo y RUT
3. El administrador puede:
   - Verificar tu número registrado
   - Enviarte el código manualmente
   - Realizar la acción por ti temporalmente

⚠️ **VERIFICACIÓN TEMPORAL BLOQUEADA:**
- NO puedes omitir la verificación SMS (es una medida de seguridad)
- Admin y Encargado NO requieren SMS
- Solo Profesores requieren SMS para: Reservas, Préstamos, Cambiar Contraseña

🔐 **¿POR QUÉ EXISTE ESTA SEGURIDAD?**
- Evita suplantación de identidad
- Confirma que REALMENTE eres tú quien hace la acción
- Protege tus reservas y préstamos
";

    private const GUIDE_DIFERENCIA_AULAS = "
🏫 **GUÍA: Diferencia entre AULAS AIP y AULAS REGULARES**

📋 **CONCEPTO FUNDAMENTAL DEL SISTEMA:**
El sistema separa las aulas en DOS categorías EXCLUSIVAS y NO intercambiables:

---

🖥️ **AULAS AIP (Aula de Innovación Pedagógica)**

**¿Qué son?**
- Aulas especializadas con equipamiento tecnológico fijo
- Ejemplos: AIP 1, AIP 2, Sala de Computación, Laboratorio de Informática

**¿Para qué se usan?**
- EXCLUSIVAMENTE para RESERVAS de aula
- El profesor reserva el ESPACIO completo para dar su clase allí
- Uso típico: Clases con computadores, proyector integrado, pizarra digital

**¿Cómo se reservan?**
1. Módulo: **'Reservar Aula'** (📅)
2. En el formulario, el desplegable 'Aula AIP' SOLO muestra aulas AIP
3. NO aparecen aulas regulares en este módulo

**Ejemplo de uso:**
Profesor de Matemáticas reserva **AIP 1** para el martes 10:00-12:00 para dar una clase usando software educativo.

---

📚 **AULAS REGULARES (Aulas comunes)**

**¿Qué son?**
- Aulas tradicionales del colegio
- Ejemplos: Aula 1, Aula 2, Sala 3A, Sala de Música

**¿Para qué se usan?**
- EXCLUSIVAMENTE para PRÉSTAMOS de equipos
- El profesor solicita equipos portátiles (laptop, proyector, extensión) para usar en ESTA aula
- El equipo se lleva al aula regular donde el profesor dará su clase

**¿Cómo se usan?**
1. Módulo: **'Préstamo de Equipos'** (💻)
2. En el formulario, el desplegable 'Aula' SOLO muestra aulas REGULARES
3. NO aparecen aulas AIP en este módulo

**Ejemplo de uso:**
Profesor de Historia solicita un proyector y laptop para usar en **Aula 2** el miércoles 08:00-10:00 para una presentación de imágenes históricas.

---

🔀 **TABLA COMPARATIVA:**

| Característica | AULAS AIP | AULAS REGULARES |
|---|---|---|
| **Tipo** | Aula especializada | Aula tradicional |
| **Módulo** | Reservar Aula | Préstamo de Equipos |
| **Acción** | Reservar ESPACIO | Solicitar EQUIPOS |
| **Equipamiento** | Fijo (ya tiene PCs) | Portátil (se lleva) |
| **Ejemplos** | AIP 1, AIP 2 | Aula 1, Aula 2 |
| **SMS** | Sí (Profesor) | Sí (Profesor) |

---

❌ **ERRORES COMUNES:**

**ERROR 1:** \"Quiero reservar Aula 1 para dar clase\"
❌ Incorrecto: Aula 1 es REGULAR, NO se puede reservar como espacio
✅ Correcto: Si quieres usar Aula 1, solicita un PRÉSTAMO de equipos para usarlos allí

**ERROR 2:** \"Quiero pedir prestado un proyector para usar en AIP 1\"
❌ Incorrecto: AIP 1 es un aula AIP, ya tiene equipamiento fijo
✅ Correcto: Si quieres usar AIP 1, haz una RESERVA del aula completa

**ERROR 3:** \"No veo AIP 1 en el módulo de Préstamos\"
❌ Esto es NORMAL: Las aulas AIP NO aparecen en Préstamos
✅ Correcto: Ve al módulo 'Reservar Aula' para reservar AIP 1

---

💡 **REGLA DE ORO:**
- ¿Quieres usar un aula con computadores? → **RESERVA** una aula AIP
- ¿Quieres llevar equipos a tu aula normal? → **PRÉSTAMO** de equipos para aula REGULAR
";

    // ========================================
    // NUEVAS GUÍAS EXCLUSIVAS PARA PROFESOR
    // ========================================

    private const GUIDE_VER_HISTORIAL_PROFESOR = "
📜 **GUÍA PASO A PASO: Cómo VER tu HISTORIAL de reservas y préstamos**

El módulo 'Mi Historial' te permite ver todas tus reservas y préstamos en formato de calendario semanal, con navegación entre semanas y filtros por turno (Mañana/Tarde).

✅ **PASOS DETALLADOS:**

**PASO 1: Acceder al módulo**
- Desde el dashboard de Profesor, haz clic en **'📜 Mi Historial'**
- O desde la navbar: **Profesor → Mi Historial**
- Carga instantánea (NO requiere verificación SMS)

**PASO 2: Entender la interfaz principal**
Verás 2 pestañas en la parte superior:

**📅 Pestaña 'Historial/Reserva':**
- Muestra calendarios semanales de aulas AIP
- Vista de lunes a sábado (semana laboral)
- Calendarios separados: AIP 1 y AIP 2

**💻 Pestaña 'Historial/Equipos':**
- Muestra calendarios semanales de préstamos
- Calendarios por tipo: LAPTOP, PROYECTOR
- Tabla resumen de préstamos de la semana

**PASO 3: Navegar en la pestaña RESERVAS (predeterminada)**

**Controles superiores:**
1. **Botones de turno:**
   - 🌅 **Mañana:** Muestra bloques de 6:00 AM - 12:45 PM (fondo verde)
   - 🌙 **Tarde:** Muestra bloques de 1:00 PM - 7:00 PM (fondo naranja)
   - Haz clic para cambiar entre turnos

2. **Navegación de semanas:**
   - **⬅️ Semana anterior:** Retrocede 7 días
   - **Rango de fecha:** Muestra \"Lun 14 Ene - Sáb 19 Ene 2025\" (ejemplo)
   - **Semana siguiente ➡️:** Avanza 7 días
   - Siempre muestra de lunes a sábado (no domingo)

3. **Botón Descargar PDF:**
   - 🔴 Botón verde **'Descargar PDF'**
   - Genera PDF con AMBOS turnos (Mañana Y Tarde)
   - Incluye: calendarios de AIP 1, AIP 2, préstamos, cancelaciones
   - Se abre en nueva pestaña (target=\"_blank\")

**PASO 4: Interpretar los calendarios de RESERVAS**

Cada calendario (AIP 1, AIP 2) muestra una grilla:
- **Filas:** Bloques de tiempo de 45 minutos
- **Columnas:** Días de la semana (Lun, Mar, Mié, Jue, Vie, Sáb)

**Celdas de la grilla:**
- ✅ **Celda verde con tu nombre:** Tu reserva activa
  - Formato: \"JUAN PÉREZ\\n10:00 - 11:30\"
  - Tooltip al pasar mouse: detalles completos
- ⬜ **Celda vacía:** Horario disponible (nadie reservó)
- 🔴 **Celda roja con otro nombre:** Reserva de otro profesor (solo visible para Admin/Encargado)

**PASO 5: Ver PRÉSTAMOS (pestaña Historial/Equipos)**

Haz clic en la pestaña **'Historial/Equipos'**:

**Controles similares:**
- Botones de turno (Mañana/Tarde)
- Navegación de semanas
- Sin botón PDF (usa el de Reservas que incluye TODO)

**Calendarios por tipo de equipo:**
- **Calendario LAPTOP:** Muestra préstamos de laptops
- **Calendario PROYECTOR:** Muestra préstamos de proyectores
- Formato de celdas: \"LAPTOP 001\\nAula 2\\n08:00-12:00\"

**Agrupación inteligente:**
Si prestaste múltiples equipos (laptop + proyector + mouse) el MISMO día/hora, aparecen como:
- 1 bloque en calendario LAPTOP
- 1 bloque en calendario PROYECTOR
- (Mouse y extensión no tienen calendario propio, solo aparecen en tabla)

**Tabla resumen semanal:**
Debajo de los calendarios, tabla con columnas:
- **Equipo:** Nombre del equipo (LAPTOP 001, PROYECTOR 002, etc.)
- **Aula:** Aula regular donde se usó
- **Fecha Préstamo:** Fecha de uso
- **Hora Inicio:** Hora de recojo
- **Hora Fin:** Hora de devolución
- **Estado:** Prestado (amarillo) / Devuelto (verde)
- **Estado Devolución:** OK / Dañado / Falta accesorio
- **Comentario:** Detalles de inspección del Encargado

**PASO 6: Filtrar y buscar**

**Por turno:**
- Cambia entre Mañana/Tarde con los botones
- Los calendarios se actualizan AUTOMÁTICAMENTE
- Sincronización instantánea

**Por semana:**
- Navega hacia atrás/adelante para ver historial antiguo o futuro
- Semanas pasadas: ver reservas/préstamos completados
- Semanas futuras: ver reservas/préstamos pendientes

**Por pestaña:**
- Reservas: solo aulas AIP
- Equipos: solo préstamos de equipos

**PASO 7: Entender estados de préstamos**

En la tabla de préstamos verás:

**Estados principales:**
- 🟡 **Prestado:** Aún no devuelto, equipo en tu poder
- 🟢 **Devuelto:** Ya devuelto al Encargado

**Estados de devolución (solo si devuelto):**
- ✅ **OK:** Equipo en perfecto estado
- ⚠️ **Dañado:** Equipo con fallas reportadas (comentario explica)
- ⚠️ **Falta accesorio:** Equipo OK pero falta cable/mouse/adaptador

**Comentarios del Encargado:**
- Si estado = Dañado: \"Pantalla con fisura diagonal\"
- Si estado = Falta accesorio: \"Falta cable de poder\"
- Si estado = OK: generalmente vacío o \"Perfecto estado\"

**PASO 8: Identificar préstamos vencidos**

Si un préstamo tiene:
- Estado: **Prestado** (amarillo)
- Fecha de préstamo: hace más de 1 día

⚠️ **Préstamo VENCIDO:** Debes devolver URGENTE al Encargado
- El Encargado recibe alertas automáticas
- Genera notificaciones al Administrador
- Posible sanción según reglamento

**PASO 9: Exportar PDF de tu historial**

**Desde pestaña Reservas:**
1. Navega a la semana deseada (botones ⬅️ ➡️)
2. Haz clic en **'🟢 Descargar PDF'**
3. Se abre nueva pestaña con PDF generado
4. Contenido del PDF:
   - Logo del colegio
   - Título: \"Historial Semanal - [Tu Nombre]\"
   - Rango de fecha: Lun 14 Ene - Sáb 19 Ene 2025
   - **Sección 1:** Calendario AIP 1 (AMBOS turnos)
   - **Sección 2:** Calendario AIP 2 (AMBOS turnos)
   - **Sección 3:** Tabla completa de préstamos de la semana
   - **Sección 4:** Reservas canceladas (si las hay, con motivos)
   - Fecha de generación al pie

5. Usa las opciones del navegador:
   - **Ctrl+P:** Imprimir en papel
   - **Guardar como PDF:** Descargar a tu PC
   - **Compartir:** Enviar por email/WhatsApp

**PASO 10: Casos de uso prácticos**

**Caso 1: Verificar si tengo reservas esta semana**
→ Ir a Historial → Pestaña Reservas → Semana actual → Buscar tu nombre en las celdas

**Caso 2: Ver qué equipos aún no he devuelto**
→ Ir a Historial → Pestaña Equipos → Tabla resumen → Filtrar por estado \"Prestado\"

**Caso 3: Generar PDF para reportar al director**
→ Ir a Historial → Navegar a la semana requerida → Descargar PDF → Imprimir

**Caso 4: Ver si un equipo fue devuelto con daños**
→ Ir a Historial → Pestaña Equipos → Tabla → Ver columna \"Estado Devolución\"
→ Si dice \"Dañado\", leer el comentario del Encargado

**Caso 5: Revisar historial del mes pasado**
→ Ir a Historial → Usar \"⬅️ Semana anterior\" varias veces hasta el mes deseado

❌ **PREGUNTAS FRECUENTES:**

**P: ¿Por qué no veo las reservas de otros profesores?**
R: Como Profesor, SOLO ves TUS propias reservas y préstamos. El historial global es exclusivo de Admin/Encargado.

**P: ¿Puedo exportar PDF de varias semanas juntas?**
R: No, el PDF se genera por semana. Debes generar múltiples PDFs si necesitas varias semanas.

**P: ¿El PDF incluye préstamos Y reservas?**
R: Sí, incluye AMBOS: calendarios de reservas (AIP 1, AIP 2) + tabla completa de préstamos.

**P: ¿Por qué un préstamo aparece en LAPTOP pero no en PROYECTOR?**
R: Porque solo prestaste laptop. Si hubieras prestado ambos, aparecerían en AMBOS calendarios.

**P: ¿Qué significa 'Pack' en notificaciones?**
R: Cuando prestas múltiples equipos juntos (laptop + proyector + mouse), se agrupan como 1 pack para facilitar seguimiento.

**P: ¿Puedo cancelar una reserva desde el historial?**
R: No directamente. Debes ir al módulo 'Reservar Aula' → Tabla 'Mis Reservas Activas' → Botón 'Cancelar'.

📌 **TIPS ÚTILES:**
- 📅 Revisa tu historial ANTES de hacer nuevas reservas para evitar conflictos personales
- 🔔 Activa notificaciones para recibir alertas de confirmación y vencimientos
- 📄 Descarga PDFs mensualmente como respaldo personal
- ⏰ Marca en tu calendario personal las fechas de préstamos para no olvidar devolver
";

    private const GUIDE_DESCARGAR_PDF_PROFESOR = "
📥 **GUÍA PASO A PASO: Cómo DESCARGAR PDF de tu historial**

El sistema permite exportar tu historial semanal (reservas + préstamos) en formato PDF profesional con el logo del colegio.

✅ **PASOS DETALLADOS:**

**PASO 1: Ir al módulo Mi Historial**
- Dashboard de Profesor → Haz clic en **'📜 Mi Historial'**
- O navbar: **Profesor → Mi Historial**
- Asegúrate de estar en la pestaña **'Historial/Reserva'** (primera pestaña)

**PASO 2: Seleccionar la semana deseada**

El sistema muestra la semana ACTUAL por defecto. Para cambiar:

**Navegar a semana específica:**
- **⬅️ Semana anterior:** Retrocede 7 días (hacia el pasado)
- **Semana siguiente ➡️:** Avanza 7 días (hacia el futuro)
- **Indicador central:** Muestra el rango \"Lun 14 Ene - Sáb 19 Ene 2025\"
- Haz clic varias veces hasta llegar a la semana que necesitas

**Ejemplos:**
- Para PDF del mes pasado: Click en \"⬅️\" unas 4-5 veces
- Para PDF de próxima semana: Click en \"➡️\" 1 vez

**PASO 3: Generar el PDF**

Una vez en la semana correcta:
1. Localiza el botón verde **'🟢 Descargar PDF'** (esquina superior izquierda)
2. Haz clic en el botón
3. El sistema procesará la solicitud (tarda 2-5 segundos)
4. Se abrirá una NUEVA PESTAÑA del navegador con el PDF generado

**PASO 4: Entender el contenido del PDF**

El PDF generado incluye TODO tu historial de ESA semana:

**Encabezado:**
- Logo del Colegio Monseñor Juan Tomis Stack
- Título: \"Historial Semanal AIP\"
- Subtítulo: \"Profesor: [Tu Nombre Completo]\"
- Rango de fechas: \"Semana del Lun 14 Enero al Sáb 19 Enero 2025\"

**Sección 1: Calendario AIP 1 (Mañana + Tarde)**
- Grilla con días de la semana (columnas) y horas (filas)
- **Turno Mañana:** Fondo verde claro (6:00 AM - 12:45 PM)
- **Turno Tarde:** Fondo naranja claro (1:00 PM - 7:00 PM)
- **Tus reservas:** Celdas marcadas con tu nombre + rango horario
- **Celdas vacías:** Horarios sin reservas

**Sección 2: Calendario AIP 2 (Mañana + Tarde)**
- Mismo formato que AIP 1
- Muestra reservas en la segunda aula AIP

**Sección 3: Tabla de Préstamos de la Semana**
Tabla completa con columnas:
- **Equipo:** LAPTOP 001, PROYECTOR 002, etc.
- **Aula Regular:** Aula donde usaste el equipo
- **Fecha Préstamo:** Día de uso
- **Hora Inicio:** Hora de recojo
- **Hora Fin:** Hora de devolución
- **Estado:** Prestado / Devuelto
- **Estado Devolución:** OK / Dañado / Falta accesorio
- **Comentario:** Observaciones del Encargado

**Agrupación visual:**
Si prestaste múltiples equipos (pack), se listan todos con la misma fecha/hora:
```
LAPTOP 001    | Aula 2 | 14/01/2025 | 08:00 | 12:00 | Devuelto | OK | Perfecto estado
PROYECTOR 001 | Aula 2 | 14/01/2025 | 08:00 | 12:00 | Devuelto | OK | Perfecto estado
MOUSE 001     | Aula 2 | 14/01/2025 | 08:00 | 12:00 | Devuelto | OK | Perfecto estado
```

**Sección 4: Reservas Canceladas (si hay)**
Si cancelaste alguna reserva ESA semana:
- Tabla con fecha original, hora, aula, fecha de cancelación
- **Motivo de cancelación:** Texto completo del motivo que ingresaste

**Pie de página:**
- Fecha y hora de generación del PDF: \"Generado el: 2025-01-20 14:35:27\"

**PASO 5: Opciones con el PDF generado**

Una vez abierto en nueva pestaña, puedes:

**Opción 1: Imprimir en papel**
- Presiona **Ctrl + P** (Windows) o **Cmd + P** (Mac)
- Selecciona tu impresora
- Ajusta configuración: orientación, color, páginas
- Haz clic en **'Imprimir'**
- Usa el PDF impreso como reporte físico

**Opción 2: Guardar en tu PC**
- Click derecho en la página del PDF → **'Guardar como...'**
- O usa el icono de descarga del navegador (esquina superior derecha)
- Elige carpeta de destino (ejemplo: Documentos/Historial_AIP)
- Nombre sugerido: \"Historial_Semana_14-19_Enero_2025.pdf\"
- Click en **'Guardar'**

**Opción 3: Compartir por email**
- Guarda el PDF primero (Opción 2)
- Abre tu cliente de email (Gmail, Outlook, etc.)
- Nuevo mensaje → Adjunta el archivo PDF guardado
- Envía al destinatario (director, coordinador, etc.)

**Opción 4: Compartir por WhatsApp/Drive**
- Guarda el PDF en tu PC
- Sube a Google Drive / OneDrive
- Copia enlace para compartir
- O envía archivo directamente por WhatsApp Web

**PASO 6: Casos de uso prácticos**

**Caso 1: Reporte mensual al director**
1. Genera PDF de cada semana del mes (4-5 PDFs)
2. Guárdalos con nombres: \"Semana1_Enero.pdf\", \"Semana2_Enero.pdf\", etc.
3. Adjúntalos todos en un solo email
4. Envía al director con asunto: \"Reporte mensual AIP - Enero 2025\"

**Caso 2: Evidencia de uso de recursos**
1. Descarga PDF de la semana donde usaste equipos
2. Imprime en papel
3. Archiva en tu carpeta personal como respaldo
4. Útil para evaluaciones de desempeño

**Caso 3: Validar devoluciones de equipos**
1. Genera PDF después de devolver equipos
2. Verifica en la tabla que estado sea \"Devuelto - OK\"
3. Guarda como comprobante de que devolviste en buen estado

**Caso 4: Reportar daño de equipo**
1. Si el Encargado marcó \"Dañado\" pero tú no dañaste nada:
2. Descarga PDF inmediatamente
3. Captura de pantalla de la sección relevante
4. Envía al administrador como prueba para aclaración

**PASO 7: Troubleshooting**

**Problema: El PDF no se abre**
- Verifica que tu navegador permita popups (ventanas emergentes)
- Revisa la barra superior del navegador si bloqueó la nueva pestaña
- Haz clic en \"Permitir ventanas emergentes\" si aparece el mensaje
- Intenta nuevamente

**Problema: El PDF está vacío o sin datos**
- Causa: Esa semana NO tuviste reservas ni préstamos
- Solución: Navega a otra semana con actividad
- O verifica que estés viendo TU historial (no el de otro usuario)

**Problema: Faltan datos en el PDF**
- Refresca la página del historial (F5)
- Vuelve a generar el PDF
- Si persiste, contacta al administrador (posible error de BD)

**Problema: El PDF tarda mucho en generarse**
- Causa: Semana con MUCHOS registros (puede tardar hasta 10 segundos)
- Solución: Espera pacientemente, NO cierres la pestaña
- Si supera 30 segundos, recarga la página e intenta de nuevo

❌ **LIMITACIONES DEL PDF:**

- ⚠️ **Solo 1 semana a la vez:** No puedes exportar múltiples semanas en 1 PDF
- ⚠️ **Solo TUS registros:** No incluye reservas/préstamos de otros profesores
- ⚠️ **No editable:** El PDF es de solo lectura, no puedes modificar contenido
- ⚠️ **Requiere navegador moderno:** Chrome, Firefox, Edge actualizados
- ⚠️ **Dependiente de datos:** Si no hay registros, el PDF estará vacío

📌 **TIPS PROFESIONALES:**
- 📂 Crea una carpeta en tu PC: \"Historial_AIP\" para organizar PDFs por mes
- 📅 Descarga PDFs mensualmente como respaldo automático
- 🔖 Usa nombres descriptivos: \"2025-01_Semana1.pdf\", \"2025-01_Semana2.pdf\"
- 📧 Configura recordatorio mensual para enviar reporte al coordinador
- 💾 Sube copias a Google Drive como backup en la nube

📌 **FORMATO PROFESIONAL DEL PDF:**
- Diseño limpio con colores institucionales
- Logo del colegio en encabezado
- Fuente legible (Arial, sans-serif)
- Bordes en tablas para claridad
- Pie de página con timestamp
- Apto para impresión en A4
";

    private const GUIDE_MANEJO_SISTEMA_PROFESOR = "
🎓 **GUÍA COMPLETA: Cómo MANEJAR el SISTEMA como Profesor**

Esta guía te enseña TODO lo que puedes hacer en el sistema, desde el login hasta las funciones avanzadas.

## 📋 **1. ACCESO AL SISTEMA**

**Login estándar:**
1. Abre tu navegador (Chrome, Firefox, Edge)
2. Ve a la URL: `http://[servidor]/Reservacion_AIP/Public/index.php`
3. Ingresa tu **correo electrónico** registrado
4. Ingresa tu **contraseña**
5. Haz clic en **'Iniciar Sesión'**
6. Si las credenciales son correctas, entras al dashboard

**Magic Login (login sin contraseña):**
1. En la página de login, haz clic en **'Magic Login'**
2. Ingresa tu correo electrónico
3. Haz clic en **'Enviar enlace mágico'**
4. Revisa tu bandeja de entrada
5. Abre el email \"Acceso rápido al Sistema AIP\"
6. Haz clic en el enlace azul (válido 10 minutos)
7. Automáticamente entras al dashboard (sin contraseña)

**Recuperar contraseña olvidada:**
1. En login, haz clic en **'¿Olvidaste tu contraseña?'**
2. Ingresa tu correo
3. Haz clic en **'Enviar enlace de recuperación'**
4. Revisa tu email
5. Haz clic en el enlace (válido 1 hora)
6. Ingresa tu NUEVA contraseña (2 veces)
7. Haz clic en **'Restablecer contraseña'**
8. Inicia sesión con la nueva contraseña

**Verificación de correo (primera vez):**
- Al crear tu cuenta, el administrador te envía un email de verificación
- Abre el email \"Verifica tu cuenta\"
- Haz clic en **'Verificar mi cuenta'**
- Tu cuenta queda verificada ✅
- Si no verificas, algunas funciones pueden estar limitadas

## 📋 **2. DASHBOARD PRINCIPAL**

Al entrar, ves el **Dashboard de Profesor** con 6 cards (tarjetas):

**Card 1: 👤 Mi Perfil**
- Click para editar tu información personal
- Cambiar foto de perfil
- Actualizar biografía

**Card 2: 📅 Reservar Aula**
- Click para ir al módulo de reservas
- Reserva aulas AIP para dar clases

**Card 3: 💻 Préstamo de Equipos**
- Click para solicitar préstamos
- Pide laptops, proyectores, etc. para usar en aulas regulares

**Card 4: 📜 Mi Historial**
- Click para ver tu historial completo
- Calendarios semanales + exportar PDF

**Card 5: 🤖 TommiBot**
- Click para abrir el chatbot de IA
- Haz preguntas sobre el sistema
- Recibe guías paso a paso

**Card 6: ... (más opciones)**
- Notificaciones
- Cambiar contraseña
- Cerrar sesión

## 📋 **3. NAVBAR SUPERIOR**

**Elementos de la navbar:**

**Izquierda:**
- **Logo del colegio:** Click para volver al dashboard
- **Título:** \"Sistema AIP - Profesor\"

**Derecha:**
- **🔔 Campana de notificaciones:**
  - Contador rojo: número de notificaciones no leídas
  - Click para ver lista desplegable
  - Notificaciones: reservas confirmadas, préstamos confirmados, devoluciones, vencimientos
  - Click en una notificación para ir al módulo relacionado

- **👤 Tu nombre + foto:**
  - Click para abrir menú desplegable
  - Opciones:
    - Mi Perfil
    - Cambiar Contraseña
    - Cerrar Sesión

- **🌙 Modo oscuro:**
  - Toggle para cambiar tema claro/oscuro
  - Preferencia se guarda automáticamente

- **🤖 Chatbot flotante:**
  - Click en el icono del robot
  - Se abre panel lateral con chat
  - Botones de consultas rápidas
  - Entrada de texto para preguntas

## 📋 **4. MÓDULO: MI PERFIL**

**Cómo acceder:**
- Dashboard → Card \"Mi Perfil\"
- O navbar → Tu nombre → \"Mi Perfil\"

**Funciones disponibles:**

**Subir/cambiar foto de perfil:**
1. Haz clic en la imagen de perfil circular
2. Selecciona una imagen de tu PC (JPG, PNG)
3. Tamaño máximo: 2MB
4. La imagen se recorta automáticamente a cuadrado
5. Click en **'Guardar cambios'**

**Editar biografía:**
1. Campo de texto libre (máximo 500 caracteres)
2. Escribe sobre ti: asignaturas, intereses, experiencia
3. Ejemplo: \"Profesor de Matemáticas con 10 años de experiencia. Me apasiona la tecnología educativa.\"
4. Click en **'Guardar cambios'**

**Datos no editables (solo lectura):**
- Nombre completo (solo admin puede cambiar)
- Correo electrónico (solo admin puede cambiar)
- Teléfono (solo admin puede cambiar)
- Rol: Profesor

## 📋 **5. MÓDULO: RESERVAR AULA**

**Ver guía completa:** Pregunta al chatbot \"¿Cómo hacer una reserva?\"

**Resumen rápido:**
1. Click en \"Reservar Aula\"
2. Verifica SMS automático (código 6 dígitos)
3. Selecciona aula AIP
4. Elige fecha (mínimo mañana)
5. Selecciona hora inicio/fin usando calendario visual
6. Click en \"Reservar\"
7. Confirmación con SweetAlert
8. Listo ✅

**Ver tus reservas activas:**
- En la misma página, debajo del formulario
- Tabla \"Mis Reservas Activas\"
- Columnas: Aula, Capacidad, Fecha, Hora Inicio, Hora Fin
- Botón \"Cancelar\" (solo si se creó HOY)

## 📋 **6. MÓDULO: PRÉSTAMO DE EQUIPOS**

**Ver guía completa:** Pregunta \"¿Cómo solicitar un préstamo?\"

**Resumen rápido:**
1. Click en \"Préstamo de Equipos\"
2. Verifica SMS automático
3. Selecciona aula REGULAR
4. Elige fecha (mínimo mañana)
5. Ingresa hora inicio/fin
6. Selecciona laptop (obligatorio)
7. Selecciona proyector (obligatorio)
8. Opcionales: mouse, extensión, parlante (checkbox)
9. Valida stock disponible
10. Click en \"Solicitar Préstamo\"
11. Listo ✅

**Ver tus préstamos activos:**
- En la misma página, debajo del formulario
- Tabla \"Mis Préstamos Activos\"
- Estados: Prestado (amarillo) / Devuelto (verde)

**Devolver equipos:**
- ⚠️ Solo el **Encargado** puede registrar devoluciones
- Acude físicamente al Encargado con los equipos
- Él los inspecciona y registra en el sistema
- Recibes notificación de confirmación

## 📋 **7. MÓDULO: MI HISTORIAL**

**Ver guía completa:** Pregunta \"¿Cómo veo mi historial?\"

**Resumen rápido:**
- **Pestaña Reservas:** Calendarios AIP 1, AIP 2 (mañana/tarde)
- **Pestaña Equipos:** Calendarios LAPTOP, PROYECTOR + tabla resumen
- Navegación entre semanas (⬅️ ➡️)
- Botón \"Descargar PDF\" genera reporte completo
- Puedes ver historial pasado y futuro

## 📋 **8. MÓDULO: NOTIFICACIONES**

**Tipos de notificaciones que recibes:**

**1. Reserva confirmada:**
- Mensaje: \"Tu reserva de [Aula] para [Fecha] [Hora] ha sido confirmada\"
- Acción: Click para ir a Mi Historial

**2. Préstamo confirmado:**
- Mensaje: \"Préstamo confirmado: Pack [equipos] para [Fecha] en [Aula]\"
- Acción: Click para ver detalles

**3. Devolución registrada:**
- Mensaje: \"Devolución confirmada: [Equipo] devuelto [Estado]\"
- Estado puede ser: OK / Dañado / Falta accesorio
- Si dañado, incluye comentario del Encargado

**4. Préstamo vencido:**
- Mensaje: \"⚠️ URGENTE: Préstamo de [Equipo] vencido. Devuelve inmediatamente\"
- Se envía automáticamente si no devuelves a tiempo

**Gestión de notificaciones:**
- Click en campana 🔔 para ver lista
- Click en una notificación → te lleva al módulo relacionado
- Automáticamente se marca como \"leída\"
- Notificaciones antiguas (>3 meses) se eliminan en mantenimiento mensual

## 📋 **9. MÓDULO: CAMBIAR CONTRASEÑA**

**Ver guía completa:** Pregunta \"¿Cómo cambiar mi contraseña?\"

**Pasos rápidos:**
1. Navbar → Tu nombre → \"Cambiar Contraseña\"
2. Verifica SMS automático
3. Ingresa contraseña ACTUAL
4. Ingresa NUEVA contraseña (2 veces)
5. Click en \"Cambiar Contraseña\"
6. Sesión se cierra automáticamente
7. Inicia sesión con la NUEVA contraseña

## 📋 **10. CHATBOT TOMMIBOT**

**Cómo usarlo:**
1. Click en icono 🤖 en navbar
2. Se abre panel lateral con chat
3. Usa botones de consultas rápidas:
   - Cómo hacer una reserva
   - Cómo solicitar préstamo
   - Cómo cambiar contraseña
   - Cómo veo mi historial
   - etc.
4. O escribe tu pregunta en lenguaje natural
5. TommiBot responde INSTANTÁNEAMENTE (respuestas locales, sin API)

**Preguntas que puedes hacer:**
- \"¿Cómo hago una reserva?\"
- \"Necesito un proyector, cómo lo pido?\"
- \"No me llega el SMS, ayuda\"
- \"Diferencia entre aula AIP y regular\"
- \"¿Cuántas reservas tengo?\"
- \"¿Cómo descargo PDF?\"
- \"Enséñame a usar el sistema\"

**Navegación inteligente:**
TommiBot puede LLEVARTE directamente a módulos:
- \"Ir a reservas\" → Redirección automática
- \"Llévame a préstamos\" → Te envía al módulo
- \"Ver mi historial\" → Navegación directa

## 📋 **11. ATAJOS DE TECLADO**

**Navegación rápida:**
- `Ctrl + H` → Ir a Historial (si está configurado)
- `Esc` → Cerrar modales/popups
- `Enter` → Confirmar en formularios

**En calendarios:**
- `⬅️ ➡️` → Navegar entre semanas
- Click en celda → Ver detalles

## 📋 **12. MEJORES PRÁCTICAS**

**Reservas:**
- ✅ Reserva con al menos 1 día de anticipación
- ✅ Verifica disponibilidad en calendario visual
- ✅ Cancela INMEDIATAMENTE si ya no la necesitas (mismo día)
- ❌ No reserves \"por si acaso\" y luego no uses el aula

**Préstamos:**
- ✅ Solicita solo equipos que REALMENTE usarás
- ✅ Devuelve PUNTUALMENTE (hora fin indicada)
- ✅ Inspecciona equipos al recibirlos (reporta daños previos)
- ✅ Cuida los equipos como si fueran tuyos
- ❌ No prestes equipos a estudiantes para llevar a casa

**Seguridad:**
- ✅ Cierra sesión al terminar (especialmente en PC compartida)
- ✅ Cambia tu contraseña periódicamente
- ✅ No compartas tu contraseña con nadie
- ✅ Verifica que tu número de teléfono esté actualizado

**Historial:**
- ✅ Descarga PDFs mensualmente como respaldo
- ✅ Revisa notificaciones regularmente
- ✅ Confirma estado de devoluciones en historial

## 📋 **13. SOLUCIÓN DE PROBLEMAS**

**No puedo iniciar sesión:**
- Verifica que tu correo esté correcto
- Usa \"Olvidé mi contraseña\" para resetear
- Contacta al administrador si tu cuenta está desactivada

**No me llega el SMS:**
- Verifica que tu número esté en formato +51XXXXXXXXX
- Revisa señal móvil
- Espera hasta 2 minutos
- Contacta al admin para validar tu número

**No veo aulas disponibles:**
- Verifica que estés en el módulo correcto:
  - Reservas → Solo aulas AIP
  - Préstamos → Solo aulas REGULARES
- Si aún no hay, contacta al administrador

**No puedo cancelar una reserva:**
- Solo puedes cancelar el MISMO DÍA que creaste la reserva
- Si pasó más de 1 día, contacta al admin

**El PDF no se descarga:**
- Permite ventanas emergentes (popups) en tu navegador
- Actualiza tu navegador a la última versión
- Prueba con otro navegador

## 📋 **14. CONTACTO Y SOPORTE**

**Para problemas técnicos:**
- Contacta al Administrador del sistema
- Envía email con captura de pantalla del error
- Describe paso a paso lo que hiciste

**Para consultas pedagógicas:**
- Contacta al Coordinador AIP del colegio
- Programa capacitaciones si es necesario

**Para reportar equipos dañados:**
- Notifica INMEDIATAMENTE al Encargado
- Describe el daño detalladamente
- No intentes reparar tú mismo

📌 **PRÓXIMOS PASOS:**
- Explora cada módulo con esta guía a la mano
- Practica haciendo una reserva de prueba
- Descarga tu primer PDF de historial
- Conversa con TommiBot para familiarizarte
";

    private const GUIDE_PERMISOS_PROFESOR = "
🔐 **GUÍA: Información del SISTEMA - Permisos del ROL PROFESOR**

Como **Profesor**, tienes permisos ESPECÍFICOS para gestionar tus propias reservas y préstamos. Aquí está TODO lo que puedes y NO puedes hacer.

## ✅ **PERMISOS QUE TIENES (Lo que SÍ puedes hacer)**

### 📅 **1. RESERVAS DE AULAS AIP**

**Qué puedes hacer:**
- ✅ Reservar aulas tipo **AIP** (Aula de Innovación Pedagógica)
- ✅ Ver disponibilidad en tiempo real (calendario visual)
- ✅ Seleccionar fecha (mínimo 1 día anticipación)
- ✅ Seleccionar hora inicio/fin (6:00 AM - 7:00 PM)
- ✅ Cancelar reservas (SOLO el mismo día que las creaste)
- ✅ Ver TUS reservas activas en tabla
- ✅ Ver TUS reservas en historial personal
- ✅ Recibir notificaciones de confirmación

**Requisitos obligatorios:**
- 🔒 **Verificación SMS:** Código de 6 dígitos por SMS (10 min validez)
- 📧 **Cuenta verificada:** Debes haber verificado tu email
- 📱 **Teléfono registrado:** Formato +51XXXXXXXXX

**Restricciones:**
- ⚠️ Anticipación mínima: **1 día** (NO puedes reservar para HOY)
- ⚠️ Solo puedes cancelar el **MISMO DÍA** de crear la reserva
- ⚠️ Solo ves TUS propias reservas (no las de otros profesores)
- ⚠️ Solo aulas **AIP** (NO puedes reservar aulas REGULARES)

### 💻 **2. PRÉSTAMOS DE EQUIPOS**

**Qué puedes hacer:**
- ✅ Solicitar préstamos de equipos (laptop, proyector, mouse, extensión, parlante)
- ✅ Seleccionar aula **REGULAR** donde usarás los equipos
- ✅ Ver stock disponible en tiempo real
- ✅ Seleccionar múltiples equipos como \"pack\"
- ✅ Ver TUS préstamos activos en tabla
- ✅ Ver estado de préstamos: Prestado / Devuelto
- ✅ Ver estado de devolución: OK / Dañado / Falta accesorio
- ✅ Recibir notificaciones de confirmación y devolución

**Requisitos obligatorios:**
- 🔒 **Verificación SMS:** Código de 6 dígitos (igual que reservas)
- 📧 **Cuenta verificada**
- 📱 **Teléfono registrado**

**Restricciones:**
- ⚠️ Anticipación mínima: **1 día** (NO puedes prestar para HOY)
- ⚠️ Solo aulas **REGULARES** (NO puedes prestar para aulas AIP)
- ⚠️ Equipos obligatorios: **Laptop Y Proyector** (mínimo)
- ⚠️ Opcionales: Mouse, Extensión, Parlante (checkbox)
- ⚠️ **NO puedes registrar devoluciones tú mismo** (solo el Encargado)
- ⚠️ Solo ves TUS propios préstamos

### 📜 **3. HISTORIAL PERSONAL**

**Qué puedes hacer:**
- ✅ Ver TUS reservas en calendarios semanales (AIP 1, AIP 2)
- ✅ Ver TUS préstamos en calendarios por tipo (LAPTOP, PROYECTOR)
- ✅ Navegar entre semanas (pasado y futuro)
- ✅ Filtrar por turno: Mañana / Tarde
- ✅ Exportar PDF semanal con TODOS tus registros
- ✅ Ver tabla resumen de préstamos con estados
- ✅ Ver reservas canceladas (tuyas) con motivos

**Restricciones:**
- ⚠️ Solo ves TUS propios registros (no los de otros profesores)
- ⚠️ PDF solo de 1 semana a la vez (no múltiples semanas)
- ⚠️ No puedes editar registros del historial
- ⚠️ No puedes eliminar registros del historial

### 🔔 **4. NOTIFICACIONES**

**Qué recibes:**
- ✅ Reserva confirmada (cuando creas una reserva)
- ✅ Préstamo confirmado (cuando solicitas equipos)
- ✅ Devolución registrada (cuando Encargado devuelve tus equipos)
- ✅ Préstamo vencido (si no devuelves a tiempo)

**Restricciones:**
- ⚠️ Solo recibes notificaciones de TUS acciones
- ⚠️ No puedes enviar notificaciones a otros usuarios
- ⚠️ Notificaciones antiguas (>3 meses) se eliminan automáticamente

### 👤 **5. PERFIL Y CONFIGURACIÓN**

**Qué puedes hacer:**
- ✅ Cambiar tu **foto de perfil** (JPG, PNG, máx 2MB)
- ✅ Editar tu **biografía** (máx 500 caracteres)
- ✅ Cambiar tu **contraseña** (requiere SMS)
- ✅ Ver tus datos: nombre, correo, teléfono (solo lectura)
- ✅ Activar/desactivar modo oscuro

**Restricciones:**
- ⚠️ NO puedes cambiar tu nombre (solo Admin)
- ⚠️ NO puedes cambiar tu correo (solo Admin)
- ⚠️ NO puedes cambiar tu teléfono (solo Admin)
- ⚠️ NO puedes cambiar tu rol (siempre serás Profesor)

### 🤖 **6. CHATBOT TOMMIBOT**

**Qué puedes hacer:**
- ✅ Hacer preguntas sobre el sistema
- ✅ Pedir guías paso a paso
- ✅ Consultar TUS estadísticas (reservas, préstamos)
- ✅ Usar navegación inteligente (\"Ir a reservas\", etc.)
- ✅ Ver botones de consultas rápidas personalizadas

**Restricciones:**
- ⚠️ Solo ves TUS propias estadísticas (no las del sistema completo)
- ⚠️ No puedes consultar datos de otros profesores
- ⚠️ No puedes ejecutar acciones de administrador desde el chat

## ❌ **PERMISOS QUE NO TIENES (Lo que NO puedes hacer)**

### 👥 **1. GESTIÓN DE USUARIOS**

**NO puedes:**
- ❌ Crear nuevos usuarios (profesores, encargados, admins)
- ❌ Editar datos de otros usuarios
- ❌ Eliminar usuarios
- ❌ Cambiar roles de usuarios
- ❌ Activar/desactivar cuentas de otros
- ❌ Ver lista completa de usuarios del sistema
- ❌ Cambiar contraseñas de otros usuarios

**Solo el ADMINISTRADOR puede hacer esto.**

### 🏫 **2. GESTIÓN DE AULAS**

**NO puedes:**
- ❌ Crear nuevas aulas (AIP o REGULARES)
- ❌ Editar nombre/capacidad de aulas existentes
- ❌ Cambiar tipo de aula (AIP ↔ REGULAR)
- ❌ Activar/desactivar aulas
- ❌ Eliminar aulas del sistema

**Solo el ADMINISTRADOR puede hacer esto.**

### 💾 **3. GESTIÓN DE EQUIPOS**

**NO puedes:**
- ❌ Crear nuevos equipos (laptops, proyectores, etc.)
- ❌ Editar stock actual o stock máximo
- ❌ Cambiar nombre de equipos (LAPTOP 001 → LAPTOP 002)
- ❌ Crear nuevos tipos de equipo
- ❌ Activar/desactivar equipos
- ❌ Eliminar equipos del sistema

**Solo el ADMINISTRADOR puede hacer esto.**

### 📦 **4. DEVOLUCIÓN DE EQUIPOS**

**NO puedes:**
- ❌ Registrar tus propias devoluciones en el sistema
- ❌ Marcar equipos como \"Devuelto\"
- ❌ Cambiar estado de devolución (OK / Dañado / Falta accesorio)
- ❌ Escribir comentarios de inspección
- ❌ Restaurar stock automáticamente

**Solo el ENCARGADO puede hacer esto** (requiere inspección física).

**Flujo correcto:**
1. TÚ llevas los equipos al Encargado
2. El ENCARGADO inspecciona físicamente
3. El ENCARGADO registra la devolución en el sistema
4. TÚ recibes notificación de confirmación

### 📊 **5. HISTORIAL GLOBAL**

**NO puedes:**
- ❌ Ver reservas de otros profesores
- ❌ Ver préstamos de otros profesores
- ❌ Generar reportes filtrados del sistema completo
- ❌ Exportar PDF de todos los usuarios

**Solo ADMINISTRADOR y ENCARGADO pueden ver historial global.**

### 📈 **6. ESTADÍSTICAS DEL SISTEMA**

**NO puedes:**
- ❌ Ver gráficos de uso de aulas (últimos 30 días)
- ❌ Ver gráficos de préstamos por equipo
- ❌ Ver estadísticas globales:
  - Total de usuarios
  - Total de equipos
  - Total de aulas
  - Préstamos vencidos globales

**Solo el ADMINISTRADOR puede ver estadísticas completas.**

**TÚ solo ves:**
- ✅ TUS reservas activas
- ✅ TUS préstamos pendientes
- ✅ TUS reservas completadas
- ✅ TUS préstamos completados

### ⚙️ **7. CONFIGURACIÓN DEL SISTEMA**

**NO puedes:**
- ❌ Ejecutar mantenimiento mensual
- ❌ Hacer backups de la base de datos
- ❌ Optimizar tablas de BD
- ❌ Limpiar notificaciones antiguas
- ❌ Limpiar sesiones expiradas
- ❌ Cambiar configuración global del sistema

**Solo el ADMINISTRADOR puede hacer esto.**

### 🔐 **8. VERIFICACIÓN DE OTROS USUARIOS**

**NO puedes:**
- ❌ Enviar códigos SMS a otros usuarios
- ❌ Verificar códigos de otros usuarios
- ❌ Cambiar números de teléfono de otros
- ❌ Forzar verificación de correo de otros

**Solo el ADMINISTRADOR puede gestionar verificaciones.**

## 📊 **COMPARATIVA DE ROLES**

| Función | Profesor (TÚ) | Encargado | Administrador |
|---|:---:|:---:|:---:|
| Reservar aulas AIP | ✅ | ✅ | ✅ |
| Solicitar préstamos | ✅ | ✅ | ✅ |
| Ver historial personal | ✅ | ✅ | ✅ |
| Ver historial global | ❌ | ✅ | ✅ |
| Registrar devoluciones | ❌ | ✅ | ✅ |
| Crear usuarios | ❌ | ❌ | ✅ |
| Gestionar equipos | ❌ | ❌ | ✅ |
| Gestionar aulas | ❌ | ❌ | ✅ |
| Ver estadísticas globales | ❌ | ❌ | ✅ |
| Mantenimiento sistema | ❌ | ❌ | ✅ |
| Verificación SMS | ✅ | ❌ | ❌ |
| Exportar PDF personal | ✅ | ✅ | ✅ |
| Reportes filtrados | ❌ | ❌ | ✅ |

## 🔒 **SEGURIDAD Y VERIFICACIÓN**

**Módulos que REQUIEREN verificación SMS (solo para Profesor):**
1. ✅ Reservar Aula → SMS obligatorio cada vez
2. ✅ Préstamo de Equipos → SMS obligatorio cada vez
3. ✅ Cambiar Contraseña → SMS obligatorio cada vez

**Módulos que NO requieren SMS:**
- ❌ Mi Perfil
- ❌ Mi Historial
- ❌ Notificaciones
- ❌ Chatbot TommiBot

**¿Por qué SMS solo para Profesores?**
- Seguridad adicional para evitar suplantación de identidad
- Validar que realmente ERES tú quien solicita recursos
- Administradores y Encargados tienen acceso directo (confianza institucional)

## 📌 **FLUJOS DE TRABAJO PERMITIDOS**

**Flujo 1: Reservar aula para clase**
1. ✅ Entras a \"Reservar Aula\"
2. ✅ Verificas SMS
3. ✅ Seleccionas aula AIP, fecha, hora
4. ✅ Confirmas reserva
5. ✅ Usas el aula en la fecha/hora
6. ✅ (Opcional) Cancelas si cambias de planes (mismo día)

**Flujo 2: Solicitar equipos**
1. ✅ Entras a \"Préstamo de Equipos\"
2. ✅ Verificas SMS
3. ✅ Seleccionas aula REGULAR, fecha, hora, equipos
4. ✅ Confirmas préstamo
5. ✅ Recoges equipos del Encargado
6. ✅ Usas equipos en tu aula
7. ❌ NO puedes devolver en el sistema
8. ✅ Llevas equipos al Encargado físicamente
9. ✅ Encargado inspecciona y registra devolución
10. ✅ Recibes notificación de confirmación

**Flujo 3: Ver tu actividad**
1. ✅ Entras a \"Mi Historial\"
2. ✅ Ves calendarios semanales
3. ✅ Navegas entre semanas
4. ✅ Exportas PDF
5. ✅ Guardas/imprimes PDF como respaldo

## ❓ **PREGUNTAS FRECUENTES**

**P: ¿Por qué no puedo ver las reservas de otros profesores?**
R: Por privacidad. Solo ves TUS propios registros. El historial global es exclusivo de Admin/Encargado.

**P: ¿Por qué no puedo devolver equipos yo mismo?**
R: El Encargado debe INSPECCIONAR físicamente el estado (OK/Dañado) antes de registrar la devolución y restaurar stock.

**P: ¿Por qué no puedo crear usuarios?**
R: Solo Administradores pueden crear cuentas para evitar registros no autorizados.

**P: ¿Por qué necesito SMS cada vez que entro a Reservas/Préstamos?**
R: Seguridad adicional. El SMS valida tu identidad en acciones críticas que afectan recursos del colegio.

**P: ¿Puedo cambiar mi propio teléfono?**
R: No. Contacta al Administrador para cambiar tu número (evita suplantaciones).

**P: ¿Puedo eliminar mis reservas antiguas del historial?**
R: No. El historial es permanente para auditoría institucional.

📌 **RESUMEN FINAL:**

✅ **TÚ PUEDES:**
- Reservar aulas AIP (con SMS)
- Solicitar préstamos (con SMS)
- Ver TU historial personal
- Exportar PDF de TUS registros
- Cambiar TU contraseña (con SMS)
- Editar TU perfil (foto, bio)

❌ **TÚ NO PUEDES:**
- Gestionar usuarios/equipos/aulas
- Ver historial de otros
- Registrar devoluciones
- Ver estadísticas globales
- Ejecutar mantenimiento

🔑 **PARA MÁS AYUDA:**
- Usa el chatbot TommiBot (🤖) para preguntas rápidas
- Contacta al Administrador para cambios de cuenta
- Contacta al Encargado para devoluciones físicas
";

    // ========================================
    // GUÍAS PARA ADMINISTRADOR
    // ========================================

    private const GUIDE_GESTIONAR_USUARIOS = "
👥 **GUÍA COMPLETA: Cómo GESTIONAR USUARIOS**

El sistema permite crear, editar, activar/desactivar usuarios de tres tipos: Administrador, Profesor y Encargado.

## 📋 **CÓMO ACCEDER AL MÓDULO**

1. Desde el Dashboard de Administrador
2. Haz clic en **'Gestión de Usuarios'** (icono 👥) en el menú lateral
3. Verás una tabla con todos los usuarios registrados

---

## ➕ **CREAR UN NUEVO USUARIO**

**PASO 1:** Haz clic en el botón **'+ Nuevo Usuario'** (esquina superior derecha)

**PASO 2:** Completa el formulario:
- **Nombre completo:** Nombre y apellido del usuario
- **Correo electrónico:** Debe ser único (el sistema valida duplicados)
- **Teléfono:** Formato +51XXXXXXXXX (para SMS de verificación)
- **Tipo de usuario:** Selecciona el rol:
  - **Administrador:** Acceso total al sistema
  - **Profesor:** Puede reservar aulas y solicitar préstamos
  - **Encargado:** Gestiona devoluciones de equipos
- **Contraseña:** Mínimo 8 caracteres

**PASO 3:** Haz clic en **'Crear Usuario'**

**PASO 4:** El sistema:
- ✅ Crea la cuenta
- 📧 Envía un correo de verificación automáticamente
- 🔑 El usuario debe verificar su correo antes de usar el sistema

---

## ✏️ **EDITAR UN USUARIO EXISTENTE**

**PASO 1:** En la tabla de usuarios, localiza el usuario

**PASO 2:** Haz clic en el botón **'Editar'** (icono ✏️) en la fila del usuario

**PASO 3:** Modifica los campos que necesites:
- Nombre
- Correo (validará que no esté en uso)
- Teléfono
- Tipo de usuario (cambiar rol)

**PASO 4:** Haz clic en **'Guardar Cambios'**

⚠️ **IMPORTANTE:** Si cambias el correo, el usuario deberá verificar el nuevo correo.

---

## 🔄 **ACTIVAR/DESACTIVAR UN USUARIO**

En lugar de ELIMINAR usuarios (lo cual borraría todo su historial), el sistema permite DESACTIVARLOS.

**Para desactivar:**
1. Haz clic en el botón **'Desactivar'** (icono 🚫)
2. Confirma la acción
3. El usuario NO podrá iniciar sesión
4. Su historial se CONSERVA

**Para reactivar:**
1. Filtra por usuarios inactivos
2. Haz clic en **'Activar'** (icono ✅)
3. El usuario podrá volver a iniciar sesión

---

## 🔍 **BUSCAR Y FILTRAR USUARIOS**

**Buscador:**
- Escribe en la barra de búsqueda (busca por nombre o correo)

**Filtros:**
- **Por rol:** Administrador, Profesor, Encargado, Todos
- **Por estado:** Activos, Inactivos, Todos
- **Por verificación:** Verificados, No verificados, Todos

---

## 📊 **INFORMACIÓN IMPORTANTE**

**Estados de verificación:**
- ✅ **Verificado:** El usuario confirmó su correo electrónico
- ⏳ **Pendiente:** El usuario NO ha verificado su correo
  - Los Profesores NO podrán usar el sistema sin verificar
  - Los Admin y Encargado SÍ pueden usarlo sin verificar

**Cambio de contraseña:**
- Solo el PROPIO usuario puede cambiar su contraseña
- El administrador NO puede ver contraseñas (están encriptadas)
- Si un usuario olvidó su contraseña: usa 'Olvidé mi contraseña' en el login

---

## ⚠️ **BUENAS PRÁCTICAS**

✅ **SÍ:**
- Verifica que el correo esté escrito correctamente
- Usa el formato +51XXXXXXXXX para teléfonos chilenos
- Desactiva usuarios en lugar de eliminarlos
- Revisa periódicamente usuarios no verificados

❌ **NO:**
- No crees usuarios duplicados
- No elimines usuarios con historial activo
- No cambies roles sin consultar (puede afectar permisos)
";

    private const GUIDE_GESTIONAR_EQUIPOS = "
💻 **GUÍA COMPLETA: Cómo GESTIONAR EQUIPOS**

El sistema gestiona el inventario de equipos prestables (Laptops, Proyectores, Extensiones, etc.).

## 📋 **CÓMO ACCEDER AL MÓDULO**

1. Desde el Dashboard de Administrador
2. Haz clic en **'Gestión de Equipos'** (icono 💻) en el menú lateral
3. Verás una tabla con todos los equipos registrados

---

## ➕ **AGREGAR UN NUEVO EQUIPO**

**PASO 1:** Haz clic en el botón **'+ Nuevo Equipo'**

**PASO 2:** Completa el formulario:
- **Nombre del equipo:** Descriptivo (ej: 'Laptop Dell Inspiron')
- **Tipo de equipo:** Selecciona la categoría (Laptop, Proyector, Extensión, etc.)
  - Si no existe el tipo, créalo primero en 'Tipos de Equipo'
- **Stock inicial:** Cantidad de unidades disponibles
- **Stock máximo:** Capacidad total del equipo

**PASO 3:** Haz clic en **'Agregar Equipo'**

**PASO 4:** El equipo queda disponible para préstamos

---

## ✏️ **EDITAR UN EQUIPO EXISTENTE**

**PASO 1:** En la tabla de equipos, localiza el equipo

**PASO 2:** Haz clic en **'Editar'** (icono ✏️)

**PASO 3:** Puedes modificar:
- Nombre del equipo
- Tipo de equipo
- Stock actual (si recibiste nuevas unidades)
- Stock máximo

**PASO 4:** Haz clic en **'Guardar Cambios'**

⚠️ **IMPORTANTE:** 
- El sistema NO te permite establecer stock MAYOR al máximo
- Si hay préstamos activos, el stock disponible será menor

---

## 📦 **GESTIONAR TIPOS DE EQUIPO**

Los tipos de equipo son las categorías (Laptop, Proyector, etc.).

**Para agregar un nuevo tipo:**
1. Ve a **'Tipos de Equipo'** en el menú
2. Haz clic en **'+ Nuevo Tipo'**
3. Escribe el nombre (ej: 'Tablet', 'Cámara Web')
4. Guarda

**Para editar un tipo:**
1. Localiza el tipo en la tabla
2. Haz clic en **'Editar'**
3. Cambia el nombre
4. Guarda

⚠️ **NO elimines tipos de equipo que estén en uso**

---

## 🔄 **ACTIVAR/DESACTIVAR EQUIPOS**

**Para desactivar:**
1. Haz clic en **'Desactivar'** en la fila del equipo
2. El equipo NO aparecerá en el módulo de préstamos
3. Los préstamos activos NO se afectan
4. El stock se CONSERVA

**Para reactivar:**
1. Filtra por equipos inactivos
2. Haz clic en **'Activar'**
3. El equipo vuelve a estar disponible

---

## 📊 **CONTROL DE STOCK**

**El sistema actualiza automáticamente:**
- **Stock disponible** = Stock máximo - Equipos prestados
- Cuando un profesor solicita un préstamo → Stock BAJA
- Cuando el encargado registra una devolución → Stock SUBE

**Alertas:**
- 🔴 Stock = 0: No se pueden hacer préstamos
- 🟡 Stock bajo: Menos de 2 unidades disponibles

---

## 🔍 **BUSCAR Y FILTRAR EQUIPOS**

**Buscador:**
- Escribe el nombre del equipo

**Filtros:**
- Por tipo de equipo
- Por disponibilidad (disponibles, agotados)
- Por estado (activos, inactivos)

---

## ⚠️ **BUENAS PRÁCTICAS**

✅ **SÍ:**
- Mantén actualizado el stock máximo
- Revisa periódicamente equipos con stock 0
- Crea tipos de equipo descriptivos
- Desactiva equipos dañados en lugar de eliminarlos

❌ **NO:**
- No elimines equipos con préstamos activos
- No modifiques el stock manualmente si hay préstamos pendientes
- No uses nombres genéricos ('Equipo 1', 'Cosa 2')
";

    private const GUIDE_GESTIONAR_AULAS = "
🏫 **GUÍA COMPLETA: Cómo GESTIONAR AULAS**

El sistema gestiona dos tipos de aulas: **AIP** (para reservas) y **REGULAR** (para préstamos).

## 📋 **CÓMO ACCEDER AL MÓDULO**

1. Desde el Dashboard de Administrador
2. Haz clic en **'Gestión de Aulas'** (icono 🏫) en el menú lateral
3. Verás una tabla con todas las aulas registradas

---

## ➕ **CREAR UNA NUEVA AULA**

**PASO 1:** Haz clic en el botón **'+ Nueva Aula'**

**PASO 2:** Completa el formulario:
- **Nombre del aula:** Identificador único (ej: 'AIP 1', 'Aula 3B')
- **Tipo de aula:** Selecciona:
  - **AIP:** Para aulas de innovación pedagógica (con computadores)
  - **REGULAR:** Para aulas tradicionales (sin equipamiento fijo)
- **Capacidad:** Número de estudiantes (opcional)

**PASO 3:** Haz clic en **'Crear Aula'**

**PASO 4:** El aula queda disponible:
- Si es AIP → Aparece en 'Reservar Aula'
- Si es REGULAR → Aparece en 'Préstamo de Equipos'

---

## ✏️ **EDITAR UN AULA EXISTENTE**

**PASO 1:** Localiza el aula en la tabla

**PASO 2:** Haz clic en **'Editar'** (icono ✏️)

**PASO 3:** Puedes modificar:
- Nombre del aula
- Capacidad
- ⚠️ **NO se puede cambiar el tipo** (AIP ↔ REGULAR)

**PASO 4:** Haz clic en **'Guardar Cambios'**

---

## 🔄 **ACTIVAR/DESACTIVAR AULAS**

**Para desactivar:**
1. Haz clic en **'Desactivar'**
2. El aula NO aparecerá en los módulos de reservas/préstamos
3. Las reservas activas NO se cancelan
4. El historial se CONSERVA

**Para reactivar:**
1. Filtra por aulas inactivas
2. Haz clic en **'Activar'**
3. El aula vuelve a estar disponible

---

## 📊 **DIFERENCIA CRÍTICA: AIP vs REGULAR**

| Característica | AULA AIP | AULA REGULAR |
|---|---|---|
| **Uso** | Reserva de espacio completo | Base para préstamo de equipos |
| **Equipamiento** | Fijo (computadores, proyector) | Sin equipamiento fijo |
| **Módulo** | Reservar Aula | Préstamo de Equipos |
| **Ejemplo** | AIP 1, AIP 2, Lab. Informática | Aula 1, Aula 2, Sala 3B |

---

## 🔍 **BUSCAR Y FILTRAR AULAS**

**Buscador:**
- Escribe el nombre del aula

**Filtros:**
- Por tipo (AIP, REGULAR, Todas)
- Por estado (Activas, Inactivas, Todas)

---

## ⚠️ **BUENAS PRÁCTICAS**

✅ **SÍ:**
- Usa nombres claros y únicos ('AIP 1', no 'Aula')
- Crea aulas AIP solo si tienen equipamiento fijo
- Mantén actualizada la capacidad
- Desactiva aulas en mantenimiento

❌ **NO:**
- No crees aulas duplicadas
- No cambies el tipo de un aula con historial
- No elimines aulas con reservas activas
";

    private const GUIDE_VER_HISTORIAL_GLOBAL = "
📜 **GUÍA COMPLETA: Cómo VER EL HISTORIAL GLOBAL**

El Historial Global muestra TODAS las reservas y préstamos del sistema (de todos los usuarios).

## 📋 **CÓMO ACCEDER**

1. Desde el Dashboard de Administrador
2. Haz clic en **'Historial Global'** (icono 📜) en el menú lateral
3. Verás una tabla con TODOS los registros

---

## 🔍 **FILTROS DISPONIBLES**

**Por tipo:**
- **Reservas:** Solo reservas de aulas AIP
- **Préstamos:** Solo préstamos de equipos
- **Todos:** Ambos tipos

**Por estado:**
- **Activas/Pendientes:** Reservas futuras o préstamos sin devolver
- **Completadas/Devueltas:** Reservas pasadas o préstamos devueltos
- **Canceladas:** Solo reservas canceladas
- **Todas:** Todos los estados

**Por usuario:**
- Escribe el nombre del profesor en el buscador
- Filtra por tipo de usuario (Profesor, Encargado)

**Por fecha:**
- Rango de fechas (desde - hasta)
- Hoy, Esta semana, Este mes, Personalizado

---

## 📊 **INFORMACIÓN MOSTRADA**

**Para Reservas:**
- Usuario que reservó
- Aula AIP reservada
- Fecha y horario (inicio - fin)
- Motivo de la reserva
- Estado (Confirmada, Completada, Cancelada)

**Para Préstamos:**
- Usuario que solicitó
- Equipo prestado y cantidad
- Aula donde se usará
- Fecha y horario
- Estado (Prestado, Devuelto)
- Comentarios de devolución (si aplica)

---

## 📥 **EXPORTAR REPORTES**

**PDF:**
1. Aplica los filtros que necesites
2. Haz clic en **'Exportar a PDF'**
3. Se genera un reporte descargable

**Excel:**
1. Aplica filtros
2. Haz clic en **'Exportar a Excel'**
3. Se descarga una hoja de cálculo

---

## 🔎 **BÚSQUEDA AVANZADA**

Puedes combinar filtros:
- Usuario: 'Juan Pérez' + Tipo: 'Préstamos' + Estado: 'Devuelto'
- Fecha: 'Últimos 7 días' + Tipo: 'Reservas' + Estado: 'Confirmada'

---

## 💡 **CASOS DE USO**

**Auditoría:**
- Ver quién reservó AIP 1 la semana pasada
- Verificar cuántos equipos prestó un profesor

**Estadísticas:**
- Aula más reservada del mes
- Equipo más prestado

**Seguimiento:**
- Préstamos que aún no se han devuelto
- Reservas canceladas (detectar patrones)

---

## ⚠️ **NOTAS IMPORTANTES**

- ⏱️ El historial se actualiza en TIEMPO REAL
- 🔒 Solo Administradores tienen acceso al historial global
- 📊 Los profesores solo ven SU propio historial
- 💾 Los registros NUNCA se eliminan (se conservan para auditoría)
";

    // ========================================
    // GUÍAS PARA ENCARGADO
    // ========================================

    private const GUIDE_DEVOLVER_EQUIPOS_ENCARGADO = "
🔄 **GUÍA PASO A PASO: Cómo REGISTRAR DEVOLUCIONES (Encargado)**

Como Encargado, tu función principal es recibir los equipos prestados e inspeccionarlos físicamente antes de registrar la devolución en el sistema.

## 📋 **CÓMO ACCEDER AL MÓDULO**

1. Desde el Dashboard de Encargado
2. Haz clic en **'🔄 Devoluciones'** en el panel principal
3. O desde la navbar superior: **Encargado → Devoluciones**

---

## ✅ **PROCESO COMPLETO DE DEVOLUCIÓN**

### **PASO 1: Recibir al Profesor**
- El profesor acude contigo con el(los) equipo(s) prestado(s)
- Identifica el préstamo verificando:
  - Nombre del profesor
  - Fecha del préstamo
  - Equipos que debe devolver

### **PASO 2: Inspección Física (MUY IMPORTANTE)**
Debes revisar CADA equipo antes de aceptarlo:

✅ **Checklist de inspección:**
- ¿El equipo enciende correctamente?
- ¿La pantalla funciona sin problemas?
- ¿El teclado y touchpad funcionan?
- ¿Tiene todos los accesorios? (cargador, mouse, cables, etc.)
- ¿Hay daños físicos visibles? (rayones, golpes, pantalla rota)
- ¿Está limpio y en buen estado general?

**Estados posibles:**
- ✅ **OK:** Equipo en perfecto estado
- ⚠️ **Dañado:** Equipo con fallas o roturas
- 🔴 **Falta accesorio:** Falta cargador, cable, etc.

### **PASO 3: Registrar en el Sistema**

**Ubicar el préstamo:**
- En la tabla de 'Préstamos Activos', busca el nombre del profesor
- Verás todos sus préstamos pendientes de devolución
- Puede aparecer como:
  - **Préstamo individual:** 1 solo equipo
  - **Pack agrupado:** Varios equipos prestados juntos

**Registrar devolución individual:**
1. Haz clic en el botón **'Devolver'** (botón azul) del préstamo
2. Se abre un modal con el detalle:
   - Nombre del equipo
   - Profesor que lo prestó
   - Fecha y hora del préstamo
   - Aula donde se prestó
3. Selecciona el **Estado del equipo:**
   - OK (por defecto)
   - Dañado
   - Falta accesorio
4. **Comentario (opcional):** Describe cualquier problema:
   - \"Pantalla con rayón en esquina superior\"
   - \"Falta cable de carga\"
   - \"Teclado con tecla floja\"
5. Haz clic en **'Confirmar Devolución'**

**Registrar devolución de pack:**
1. Si el profesor prestó varios equipos juntos (pack), verás el botón **'Devolver Pack'**
2. Al hacer clic, se abre un modal mostrando TODOS los equipos del pack
3. Puedes marcar el estado de CADA equipo individualmente:
   - Laptop: OK
   - Proyector: Dañado (escribir comentario)
   - Mouse: Falta accesorio (especificar cuál)
4. Agrega un **comentario general** si es necesario
5. Haz clic en **'Devolver todos'**

### **PASO 4: Confirmación del Sistema**
- El sistema muestra: **\"✅ Devolución registrada correctamente\"**
- El stock del equipo se actualiza AUTOMÁTICAMENTE (+1 disponible)
- Se envía notificación automática a:
  - El profesor (confirmando devolución)
  - Los administradores (para conocimiento)
  
### **PASO 5: Notificar al Profesor**
- Informa verbalmente al profesor que la devolución fue registrada
- Si hay daños/faltantes, explica que se notificará al administrador
- El profesor puede irse

---

## 🔍 **FILTROS Y BÚSQUEDA**

**Filtros disponibles:**
- **Por profesor:** Escribe el nombre en el buscador
- **Por equipo:** Filtra por tipo (Laptop, Proyector, etc.)
- **Por fecha:** Rango de fechas del préstamo
- **Por estado:** Solo préstamos vencidos o próximos a vencer

**Vista de calendario:**
- Haz clic en el ícono del calendario
- Verás los préstamos organizados por fecha
- Útil para identificar préstamos vencidos

---

## ⚠️ **PRÉSTAMOS VENCIDOS**

**¿Qué es un préstamo vencido?**
- Un préstamo cuya hora de FIN ya pasó y AÚN NO se devolvió
- Ejemplo: Préstamo termina a las 14:00, pero son las 15:00 y no se devolvió

**Alertas automáticas:**
- Al iniciar sesión, si hay préstamos vencidos, verás un modal rojo de alerta
- El modal muestra:
  - Cantidad de préstamos vencidos
  - Nombre del profesor
  - Equipo(s) sin devolver
  - Cuánto tiempo lleva vencido

**Acciones recomendadas:**
1. Contacta al profesor inmediatamente (por teléfono/correo)
2. Recuérdele que debe devolver el equipo
3. Si el profesor no responde, notifica al administrador

---

## 📊 **INFORMACIÓN IMPORTANTE**

**Responsabilidades del Encargado:**
- ✅ Inspeccionar FÍSICAMENTE cada equipo antes de registrar
- ✅ Ser OBJETIVO al calificar el estado (OK, Dañado, Falta accesorio)
- ✅ Documentar CLARAMENTE cualquier problema en el comentario
- ✅ Registrar devoluciones INMEDIATAMENTE (no esperar)
- ✅ Mantener el orden del inventario físico

**Consecuencias de no inspeccionar:**
- Si aceptas un equipo dañado como \"OK\", será difícil atribuir la responsabilidad
- El inventario quedará desactualizado
- Futuros profesores podrían recibir equipos en mal estado

**Datos que se registran automáticamente:**
- Fecha y hora EXACTA de la devolución
- Tu nombre (Encargado que registró)
- Estado del equipo
- Comentarios adicionales

---

## 🔍 **VER HISTORIAL DE DEVOLUCIONES**

Para consultar devoluciones pasadas:
1. Ve a **'Historial'** desde el menú
2. Filtra por **'Préstamos devueltos'**
3. Verás:
   - Quién devolvió
   - Cuándo se devolvió
   - Estado en que se devolvió
   - Comentarios del Encargado

---

## ⚙️ **CASOS ESPECIALES**

**Caso 1: El profesor perdió el equipo completo**
1. NO registres la devolución
2. Deja el préstamo como \"vencido\"
3. Notifica URGENTEMENTE al administrador
4. El administrador tomará las medidas correspondientes

**Caso 2: El equipo llegó muy dañado (inutilizable)**
1. Regístralo como \"Dañado\"
2. En comentarios: describe el daño DETALLADAMENTE
3. Notifica al administrador inmediatamente
4. El administrador puede desactivar el equipo del inventario

**Caso 3: Falta solo un accesorio menor**
1. Regístralo como \"Falta accesorio\"
2. Especifica exactamente QUÉ falta (ej: \"Falta cable HDMI\")
3. El administrador decidirá si cobra/reemplaza

**Caso 4: El profesor devuelve ANTES de la hora de fin**
1. ✅ Perfectamente válido
2. Regístralo normalmente
3. El stock se liberará inmediatamente

---

## 💡 **BUENAS PRÁCTICAS**

✅ **SÍ:**
- Inspecciona SIEMPRE antes de aceptar
- Sé riguroso pero justo en la evaluación
- Documenta TODO en comentarios
- Registra inmediatamente (no acumules)
- Mantén comunicación con profesores

❌ **NO:**
- No aceptes equipos sin revisar
- No omitas daños para evitar conflictos
- No dejes préstamos sin registrar
- No alteres fechas/horas de devolución

---

## 📧 **NOTIFICACIONES AUTOMÁTICAS**

Cuando registras una devolución, el sistema envía notificaciones a:

**Al Profesor:**
- \"✅ Tu préstamo ha sido devuelto correctamente\"
- Incluye: equipo(s), estado, hora de devolución, comentarios

**Al Administrador:**
- \"📦 Devolución registrada por [tu nombre]\"
- Incluye: profesor, equipo(s), estado, comentarios
- Si hay daños, el admin puede tomar acciones

---

## 🎯 **RESUMEN RÁPIDO**

1. **Recibir** al profesor con los equipos
2. **Inspeccionar** físicamente cada equipo
3. **Registrar** en el módulo Devoluciones
4. **Seleccionar estado:** OK, Dañado, Falta accesorio
5. **Agregar comentario** si hay problemas
6. **Confirmar** devolución
7. **Notificar** verbalmente al profesor
8. El sistema actualiza stock automáticamente
";

    private const GUIDE_VER_HISTORIAL_ENCARGADO = "
📜 **GUÍA: Ver HISTORIAL (Encargado)**

Como Encargado, puedes consultar el historial global de reservas y préstamos del sistema para monitorear la actividad y verificar devoluciones pasadas.

## 📋 **CÓMO ACCEDER**

1. Desde el Dashboard de Encargado
2. Haz clic en **'📄 Historial'** en el panel principal
3. Verás dos pestañas principales:
   - **Historial de Reservas:** Todas las reservas de aulas AIP
   - **Historial de Equipos:** Todos los préstamos de equipos

---

## 🔍 **FILTROS DISPONIBLES**

**Por tipo:**
- Reservas (aulas AIP)
- Préstamos (equipos)
- Ambos

**Por estado:**
- Activos (préstamos sin devolver, reservas futuras)
- Completados/Devueltos
- Vencidos (préstamos que pasaron su hora y no se devolvieron)
- Todos

**Por usuario:**
- Busca por nombre del profesor

**Por fecha:**
- Selecciona rango de fechas
- Útil para auditorías

---

## 📊 **INFORMACIÓN QUE PUEDES VER**

**Para Préstamos:**
- Profesor que solicitó
- Equipo(s) prestado(s)
- Aula regular asignada
- Fecha y hora (inicio - fin)
- Estado actual:
  - ⏳ **Pendiente:** Aún no se ha devuelto
  - ✅ **Devuelto:** Ya fue devuelto
  - 🔴 **Vencido:** Pasó la hora de fin sin devolverse
- Estado del equipo al devolverse (OK, Dañado, Falta accesorio)
- Encargado que registró la devolución
- Comentarios adicionales

**Para Reservas:**
- Profesor que reservó
- Aula AIP reservada
- Fecha y horario
- Motivo de la reserva
- Estado (Confirmada, Completada, Cancelada)

---

## 🎯 **CASOS DE USO COMUNES**

**Verificar si un profesor devolvió:**
1. Filtra por nombre del profesor
2. Filtra por \"Préstamos devueltos\"
3. Verás todas sus devoluciones con fechas y estados

**Identificar préstamos vencidos:**
1. Filtra por \"Estado: Vencidos\"
2. Verás los préstamos que NO se devolvieron a tiempo
3. Contacta a los profesores para solicitar devolución

**Consultar historial de un equipo:**
1. Busca por nombre del equipo (ej: \"Laptop 001\")
2. Verás TODAS las veces que se prestó
3. Útil para identificar equipos problemáticos

**Revisar tus propias devoluciones:**
1. Filtra por \"Encargado: [tu nombre]\"
2. Verás todas las devoluciones que TÚ registraste
3. Útil para reportes personales

---

## 📥 **EXPORTAR HISTORIAL (PDF)**

1. Haz clic en **'Descargar PDF'**
2. El sistema genera un reporte con:
   - Todas las reservas y préstamos filtrados
   - Gráficos estadísticos
   - Información completa de cada registro
3. Útil para reportes mensuales o auditorías

---

## 💡 **CONSEJOS**

✅ **Revisa regularmente los préstamos vencidos** para contactar a profesores
✅ **Usa filtros de fecha** para generar reportes semanales/mensuales
✅ **Verifica el estado de equipos devueltos** para detectar patrones de daños
✅ **Exporta PDF al final del mes** como respaldo
";

    private const GUIDE_PERFIL_ENCARGADO = "
👤 **GUÍA: Configurar PERFIL (Encargado)**

Puedes personalizar tu perfil y gestionar tu información personal.

## 📋 **CÓMO ACCEDER**

1. Desde el Dashboard de Encargado
2. Haz clic en **'👤 Mi Perfil'**
3. O desde la navbar: clic en tu nombre → **'Configuración'**

---

## ✏️ **QUÉ PUEDES EDITAR**

**Información Personal:**
- Nombre completo (se mostrará en notificaciones)
- Correo electrónico (se usa para recuperar contraseña)
- Teléfono (formato +51XXXXXXXXX)
- Biografía/Descripción (opcional)

**Foto de Perfil:**
- Sube una imagen desde tu computadora
- Formatos: JPG, PNG, GIF
- Tamaño máximo: 2MB
- Se mostrará en tu panel y notificaciones

**Cambiar Contraseña:**
- Ingresa tu contraseña actual
- Escribe nueva contraseña (mínimo 8 caracteres)
- Confirma la nueva contraseña
- Guarda cambios

---

## 🔒 **SEGURIDAD**

- Tu contraseña está encriptada (nadie puede verla)
- Si olvidas tu contraseña: usa \"Olvidé mi contraseña\" en el login
- Cambia tu contraseña periódicamente
- NO compartas tus credenciales con nadie

---

## 💡 **CONSEJOS**

✅ **Mantén actualizado tu correo** para recibir notificaciones
✅ **Usa una foto profesional** para identificación
✅ **Cambia tu contraseña cada 3 meses** por seguridad
";

    private const GUIDE_NOTIFICACIONES_ENCARGADO = "
🔔 **GUÍA: Consultar NOTIFICACIONES (Encargado)**

El sistema te envía notificaciones sobre préstamos, devoluciones y alertas importantes.

## 📋 **CÓMO ACCEDER**

1. Desde el Dashboard de Encargado
2. Haz clic en el ícono de campana 🔔 (esquina superior derecha)
3. O ve a **'Notificaciones'** desde el panel

---

## 📧 **TIPOS DE NOTIFICACIONES**

**Préstamos registrados:**
- Cuando un profesor solicita un préstamo
- Contiene: equipo, fecha, hora, profesor

**Préstamos próximos a vencer:**
- 1 hora antes de que termine un préstamo
- Te recuerda prepararte para recibir la devolución

**Préstamos vencidos:**
- Cuando un préstamo NO se devolvió a tiempo
- Acción: contactar al profesor

**Devoluciones confirmadas:**
- Cuando TÚ registras una devolución
- Confirmación del sistema

**Equipos dañados:**
- Cuando un administrador marca un equipo como dañado
- Para que estés al tanto del inventario

---

## 🎯 **ACCIONES CON NOTIFICACIONES**

- **Marcar como leída:** Haz clic en la notificación
- **Ver detalle:** Haz clic en \"Ver más\"
- **Ir al módulo:** Algunas notificaciones tienen botón \"Ir a Devoluciones\"
- **Eliminar:** Desliza o haz clic en el ícono de basura

---

## 💡 **CONSEJOS**

✅ **Revisa tus notificaciones AL INICIO de tu turno** para ver préstamos pendientes
✅ **Atiende primero las notificaciones de préstamos vencidos** (urgentes)
✅ **Marca como leídas** para mantener tu bandeja organizada
";

    private const GUIDE_PERMISOS_ENCARGADO = "
🔐 **GUÍA: Permisos y Funciones del ENCARGADO**

Como Encargado del sistema AIP, tu rol es FUNDAMENTAL para el control físico del inventario y las devoluciones.

## ✅ **LO QUE SÍ PUEDES HACER**

### 🔄 **1. Registrar Devoluciones (FUNCIÓN PRINCIPAL)**
- Recibir equipos prestados
- Inspeccionar físicamente cada equipo
- Registrar el estado: OK, Dañado, Falta accesorio
- Agregar comentarios sobre problemas detectados
- El sistema actualiza automáticamente el stock

### 📜 **2. Ver Historial Global**
- Consultar TODAS las reservas de aulas AIP
- Consultar TODOS los préstamos de equipos
- Filtrar por profesor, fecha, estado
- Identificar préstamos vencidos
- Exportar reportes en PDF

### 🔔 **3. Recibir Notificaciones**
- Préstamos registrados por profesores
- Préstamos próximos a vencer
- Préstamos vencidos (sin devolver)
- Equipos dañados o sin stock

### 👤 **4. Gestionar tu Perfil**
- Actualizar tu nombre, correo, teléfono
- Subir foto de perfil
- Cambiar tu contraseña
- Configurar preferencias

### 🏠 **5. Ver Dashboard**
- Resumen de préstamos activos
- Alertas de préstamos vencidos
- Estadísticas rápidas

---

## ❌ **LO QUE NO PUEDES HACER**

**NO puedes:**
- ❌ Hacer reservas de aulas AIP (solo Profesor)
- ❌ Solicitar préstamos de equipos (solo Profesor)
- ❌ Crear, editar o eliminar usuarios (solo Administrador)
- ❌ Gestionar equipos del inventario (solo Administrador)
- ❌ Crear o editar aulas (solo Administrador)
- ❌ Cancelar reservas de otros (solo el Profesor que reservó)
- ❌ Ver contraseñas de otros usuarios (están encriptadas)
- ❌ Modificar historial ya registrado (auditoría)

---

## 🎯 **TU ROL EN EL SISTEMA**

**Eres el PUENTE entre el sistema digital y el inventario físico:**

1. **Control de Calidad:**
   - Inspeccionas FÍSICAMENTE cada equipo devuelto
   - Detectas daños, faltantes, problemas
   - Documentas el estado real del inventario

2. **Actualización del Sistema:**
   - Registras devoluciones inmediatamente
   - Mantienes el stock actualizado
   - Generas trazabilidad de cada equipo

3. **Alertas y Seguimiento:**
   - Identificas préstamos vencidos
   - Contactas a profesores para solicitar devoluciones
   - Notificas al administrador sobre equipos dañados

4. **Reportes y Auditoría:**
   - Consultas historial para verificaciones
   - Exportas reportes mensuales
   - Provees información para toma de decisiones

---

## 🔐 **DIFERENCIAS CON OTROS ROLES**

| Función | Encargado | Profesor | Administrador |
|---|---|---|---|
| **Registrar devoluciones** | ✅ SÍ | ❌ NO | ❌ NO |
| **Hacer reservas** | ❌ NO | ✅ SÍ | ❌ NO |
| **Solicitar préstamos** | ❌ NO | ✅ SÍ | ❌ NO |
| **Ver historial global** | ✅ SÍ | ❌ NO (solo propio) | ✅ SÍ |
| **Gestionar usuarios** | ❌ NO | ❌ NO | ✅ SÍ |
| **Gestionar equipos** | ❌ NO | ❌ NO | ✅ SÍ |
| **Verificación SMS** | ❌ NO | ✅ SÍ | ❌ NO |

---

## 💡 **RESPONSABILIDADES**

**Eres responsable de:**
- ✅ Inspeccionar OBJETIVAMENTE cada equipo
- ✅ Documentar CLARAMENTE cualquier problema
- ✅ Registrar devoluciones INMEDIATAMENTE
- ✅ Mantener la INTEGRIDAD del inventario físico
- ✅ Comunicar problemas al administrador

**NO eres responsable de:**
- ❌ Aprobar o rechazar préstamos (el sistema lo hace automáticamente)
- ❌ Sancionar a profesores (eso lo decide el administrador)
- ❌ Reparar equipos dañados (eso lo gestiona mantenimiento)

---

## 🔒 **ACCESO AL SISTEMA**

**Requisitos:**
- ✅ Correo verificado (link enviado por email al registrarte)
- ❌ NO requieres verificación SMS (acceso directo)

**Inicio de sesión:**
1. Ingresa tu correo y contraseña
2. Acceso inmediato (sin código SMS)
3. Verás el Dashboard de Encargado

---

## 🎯 **FLUJO DE TRABAJO TÍPICO**

**Inicio del turno:**
1. Inicia sesión en el sistema
2. Revisa notificaciones (especialmente préstamos vencidos)
3. Consulta la lista de préstamos activos del día
4. Prepárate para recibir devoluciones

**Durante el turno:**
1. Profesor llega con equipos a devolver
2. Inspecciona físicamente cada equipo
3. Registra devolución en el sistema
4. Notifica al profesor verbalmente
5. Si hay daños, documenta y notifica al admin

**Fin del turno:**
1. Verifica que NO haya devoluciones pendientes de registrar
2. Revisa préstamos vencidos y contacta profesores si es necesario
3. Exporta reporte del día (opcional)
4. Cierra sesión

---

## 💬 **PREGUNTAS FRECUENTES**

**P: ¿Puedo rechazar una devolución si el equipo está dañado?**
R: NO. Debes ACEPTAR la devolución y registrarla como \"Dañado\" con comentarios detallados. El administrador decidirá las acciones a tomar.

**P: ¿Qué hago si un profesor no devuelve a tiempo?**
R: El sistema te alertará automáticamente. Contacta al profesor por teléfono/correo. Si no responde, notifica al administrador.

**P: ¿Puedo editar una devolución ya registrada?**
R: NO. Las devoluciones son permanentes (auditoría). Si cometiste un error, contacta al administrador.

**P: ¿Puedo prestar equipos directamente?**
R: NO. Los profesores deben solicitarlo desde su panel. Tu función es RECIBIR devoluciones, no entregar préstamos.

**P: ¿Qué hago si encuentro un equipo sin su código/etiqueta?**
R: Notifica al administrador inmediatamente. NO lo registres como devuelto hasta confirmar su identidad.

---

## 📞 **SOPORTE**

Si tienes dudas técnicas o necesitas ayuda:
- Contacta al **Administrador del sistema**
- O escribe a la dirección académica del colegio
- También puedes consultar estas guías en cualquier momento
";

    private const GUIDE_COMO_FUNCIONA_SISTEMA = "
⚙️ **GUÍA COMPLETA: Cómo FUNCIONA EL SISTEMA**

## 🎯 **PROPÓSITO DEL SISTEMA**

El Sistema de Reservas AIP gestiona:
1. **Reservas de Aulas AIP** (espacios con equipamiento fijo)
2. **Préstamos de Equipos** (dispositivos portátiles)
3. **Control de Inventario** (stock de equipos)
4. **Usuarios y Permisos** (profesores, encargados, admins)

---

## 👥 **ROLES DEL SISTEMA**

### 🔑 **ADMINISTRADOR**
**Permisos:**
- ✅ Gestionar usuarios (crear, editar, activar/desactivar)
- ✅ Gestionar equipos (agregar, editar stock)
- ✅ Gestionar aulas (crear, editar, activar/desactivar)
- ✅ Ver historial global (de TODOS los usuarios)
- ✅ Exportar reportes (PDF, Excel)
- ✅ Configurar sistema
- ❌ NO puede hacer reservas ni préstamos (es solo gestor)

**Acceso:**
- ⚠️ REQUIERE verificación de correo (link enviado por email)
- Sin verificación SMS requerida

---

### 👨‍🏫 **PROFESOR**
**Permisos:**
- ✅ Reservar aulas AIP
- ✅ Solicitar préstamos de equipos
- ✅ Cancelar sus propias reservas (solo el mismo día)
- ✅ Ver su propio historial
- ✅ Cambiar su contraseña
- ❌ NO puede gestionar usuarios, equipos ni aulas
- ❌ NO puede ver el historial de otros profesores

**Acceso:**
- ⚠️ REQUIERE verificación de correo (link enviado por email)
- ⚠️ REQUIERE verificación SMS (código de 6 dígitos) para:
  - Reservar aulas
  - Solicitar préstamos
  - Cambiar contraseña

---

### 📦 **ENCARGADO**
**Permisos:**
- ✅ Registrar devoluciones de equipos
- ✅ Inspeccionar estado de equipos (OK, Dañado, Falta accesorio)
- ✅ Ver préstamos pendientes
- ✅ Ver su propio historial
- ❌ NO puede hacer reservas ni préstamos
- ❌ NO puede gestionar usuarios ni equipos

**Acceso:**
- ⚠️ REQUIERE verificación de correo (link enviado por email)
- Sin verificación SMS requerida

---

## 🔄 **FLUJO DE TRABAJO**

### **FLUJO 1: Reserva de Aula AIP**

1. Profesor inicia sesión
2. Verifica su correo (si es primera vez)
3. Va a 'Reservar Aula'
4. El sistema envía SMS de verificación automáticamente
5. Profesor ingresa código SMS
6. Completa formulario (aula, fecha, horario, motivo)
7. Sistema valida disponibilidad
8. Reserva queda confirmada
9. Profesor recibe notificación

### **FLUJO 2: Préstamo de Equipo**

1. Profesor inicia sesión
2. Va a 'Préstamo de Equipos'
3. El sistema envía SMS de verificación automáticamente
4. Profesor ingresa código SMS
5. Completa formulario (aula, equipo, cantidad, fecha, horario)
6. Sistema valida stock disponible
7. Préstamo queda registrado (stock BAJA automáticamente)
8. Profesor recoge equipo con el Encargado
9. Al finalizar, devuelve equipo al Encargado
10. Encargado inspecciona y registra devolución
11. Sistema actualiza stock automáticamente (stock SUBE)

### **FLUJO 3: Gestión de Usuarios (Admin)**

1. Admin inicia sesión
2. Va a 'Gestión de Usuarios'
3. Puede:
   - Crear nuevos usuarios
   - Editar usuarios existentes
   - Activar/desactivar usuarios
   - Cambiar roles
   - Ver estadísticas

---

## 🔐 **SEGURIDAD DEL SISTEMA**

**Verificación de Correo:**
- Al registrarse, se envía un link de verificación por correo electrónico
- El usuario debe hacer clic en el enlace del email
- Obligatorio para TODOS los usuarios (Admin, Profesor, Encargado)
- Sin verificación NO se puede acceder al sistema

**Verificación SMS:**
- Al ingresar a módulos críticos (Reservas, Préstamos, Cambiar Contraseña)
- Se envía código de 6 dígitos automáticamente
- Expira en 10 minutos
- Solo para PROFESORES

**Contraseñas:**
- Encriptadas con bcrypt
- Mínimo 8 caracteres
- Solo el usuario puede cambiarla
- Recuperación por correo ('Olvidé mi contraseña')

---

## 📊 **NOTIFICACIONES**

El sistema envía notificaciones por:
- ✅ Reserva confirmada
- ✅ Préstamo registrado
- ⚠️ Préstamo próximo a vencer
- 🔴 Préstamo vencido
- 📧 Verificación de correo
- 🔑 Recuperación de contraseña

---

## 💡 **REGLAS DE NEGOCIO**

**Reservas:**
- NO se puede reservar para HOY (mínimo mañana)
- Solo se puede cancelar el MISMO DÍA de creación
- Un profesor NO puede tener dos reservas simultáneas
- Las aulas AIP NO se pueden prestar

**Préstamos:**
- NO se puede prestar para HOY (mínimo mañana)
- El stock se controla automáticamente
- Solo el Encargado puede registrar devoluciones
- Las aulas REGULARES NO se pueden reservar

**Usuarios:**
- Los correos deben ser únicos
- Los teléfonos deben tener formato +51XXXXXXXXX
- Los usuarios desactivados NO pueden iniciar sesión
- El historial se CONSERVA siempre
";
    
    public function __construct($conexion) {
        $this->db = $conexion;
    }
    
    /**
     * Genera una respuesta usando el motor de consultas local
     */
    public function generateResponse($userMessage, $userRole = 'Profesor', $userId = null) {
        // PRIMERO: Detectar si está pidiendo una guía paso a paso (respuesta inmediata)
        $guideResponse = $this->detectAndReturnGuide($userMessage, $userRole);
        if ($guideResponse) {
            return $guideResponse;
        }

        // SEGUNDO: Si no es una guía, usar el motor de respuestas locales basado en estadísticas
        $localResponse = $this->generateLocalResponse($userMessage, $userRole, $userId);
        if ($localResponse) {
            return $localResponse;
        }

        // TERCERO: Si el motor local no puede responder, dar un mensaje de ayuda contextual.
        return $this->getFallbackResponse($userRole);
    }

    /**
     * Motor de respuestas local inteligente - Responde TODO sobre la base de datos
     * Utiliza análisis semántico, consultas dinámicas y estadísticas del sistema.
     */
    private function generateLocalResponse($userMessage, $userRole, $userId) {
        $lower = mb_strtolower($userMessage, 'UTF-8');
        $stats = $this->getSystemStatistics($userRole, $userId);

        // NIVEL 1: Resumen general del sistema
        if (preg_match('/(resumen|informacion|información|estado|dashboard|vista general).*(sistema|todo|completo)/i', $userMessage)) {
            return $this->getSystemOverview($stats, $userRole);
        }

        // NIVEL 2: Análisis semántico de la pregunta
        $response = $this->analyzeAndRespond($lower, $stats, $userRole, $userId);
        if ($response) {
            return $response;
        }

        // NIVEL 3: Consultas avanzadas que requieren acceso directo a BD
        if ($userRole === 'Administrador') {
            $advancedResponse = $this->handleAdvancedAdminQuery($lower, $userId);
            if ($advancedResponse) {
                return $advancedResponse;
            }
        }
        
        // NIVEL 3B: Consultas comunes para Profesor y Encargado
        if ($userRole === 'Profesor' || $userRole === 'Encargado') {
            $commonResponse = $this->handleCommonQueries($lower, $userId);
            if ($commonResponse) {
                return $commonResponse;
            }
        }

        return null; // No se encontró una respuesta local.
    }

    /**
     * Proporciona una vista general completa del sistema
     */
    private function getSystemOverview($stats, $userRole) {
        $overview = "## 📊 **Resumen General del Sistema**\n\n";
        
        $overview .= "### 👥 Usuarios\n";
        $overview .= "- **Total de usuarios:** {$stats['total_usuarios']}\n";
        $overview .= "- Profesores: {$stats['profesores']}\n";
        $overview .= "- Encargados: {$stats['encargados']}\n";
        $overview .= "- Administradores: {$stats['administradores']}\n";
        $overview .= "- ✅ Verificados: {$stats['verificados']}\n";
        $overview .= "- ⏳ Pendientes: {$stats['no_verificados']}\n\n";
        
        $overview .= "### 🏫 Aulas\n";
        $overview .= "- **Total de aulas:** {$stats['total_aulas']}\n";
        $overview .= "- AIP (Reservables): {$stats['aulas_aip']}\n";
        $overview .= "- Regulares: {$stats['aulas_regulares']}\n";
        $overview .= "- 📅 Reservas activas: {$stats['reservas_activas_global']}\n\n";
        
        $overview .= "### 💻 Equipos\n";
        $overview .= "- **Total de equipos:** {$stats['total_equipos']}\n";
        $overview .= "- ✅ Disponibles: {$stats['equipos_disponibles']}\n";
        $overview .= "- 📦 Prestados: {$stats['equipos_prestados']}\n";
        $overview .= "- 📂 Tipos de equipo: {$stats['tipos_equipo']}\n\n";
        
        $overview .= "### 📋 Préstamos\n";
        $overview .= "- Pendientes: {$stats['prestamos_pendientes_global']}\n";
        if ($stats['prestamos_vencidos'] > 0) {
            $overview .= "- ⚠️ **VENCIDOS:** {$stats['prestamos_vencidos']}\n";
        }
        $overview .= "- ✅ Devoluciones hoy: {$stats['devoluciones_hoy']}\n\n";
        
        $overview .= "---\n\n";
        $overview .= "#### 🚀 **Acciones Rápidas**\n\n";
        $overview .= "```\n";
        $overview .= "• Dame un listado de usuarios\n";
        $overview .= "• Muestra los préstamos activos\n";
        $overview .= "• ¿Hay usuarios sin verificar?\n";
        $overview .= "• ¿Cómo gestiono equipos?\n";
        $overview .= "```\n\n";
        
        $overview .= "_Actualizado en tiempo real desde la base de datos._";
        
        return $overview;
    }

    /**
     * Analiza semánticamente la pregunta y responde con datos reales
     */
    private function analyzeAndRespond($lower, $stats, $userRole, $userId) {
        // Mapeo semántico mejorado con sinónimos y variaciones
        // IMPORTANTE: Orden de más específico a menos específico
        $entityMap = [
            'aulas_aip' => ['aulas aip', 'aip', 'aulas reservables', 'salones aip'],
            'aulas_regulares' => ['aulas regulares', 'regulares', 'salones regulares'],
            'equipos_disponibles' => ['equipos disponibles', 'disponibles', 'en stock', 'libres', 'para prestar'],
            'equipos_prestados' => ['equipos prestados', 'prestados', 'fuera', 'en uso'],
            'tipos_equipo' => ['tipos de equipo', 'categorias de equipo', 'clases de equipo'],
            'no_verificados' => ['no verificados', 'sin verificar', 'pendientes de verificacion', 'sin confirmar'],
            'reservas_activas' => ['reservas activas', 'reservas', 'reservaciones'],
            'prestamos_pendientes' => ['prestamos pendientes', 'prestamos activos'],
            'prestamos_vencidos' => ['prestamos vencidos', 'vencidos', 'atrasados', 'morosos'],
            'devoluciones_hoy' => ['devoluciones hoy', 'devoluciones de hoy', 'devueltos hoy'],
            'profesores' => ['profesores', 'profesor', 'docentes', 'docente', 'maestros', 'maestro', 'teacher'],
            'encargados' => ['encargados', 'encargado', 'staff', 'personal'],
            'administradores' => ['administradores', 'administrador', 'admin', 'admins'],
            'verificados' => ['verificados', 'verificado', 'confirmados', 'activos con correo'],
            'aulas' => ['aulas', 'aula', 'salones', 'salon', 'salas', 'sala', 'classrooms'],
            'equipos' => ['equipos', 'equipo', 'dispositivos', 'aparatos', 'items'],
            'usuarios' => ['usuarios', 'usuario', 'user', 'users', 'personas', 'gente', 'cuentas'],
            'prestamos' => ['prestamos', 'prestamo']
        ];

        // Detectar tipo de pregunta
        $isQuantitative = preg_match('/(cuantos|cuantas|total|numero|cantidad|hay|tenemos|existe)/i', $lower);
        $isExplanatory = preg_match('/(que es|que son|explicame|dime sobre|informacion sobre)/i', $lower);
        $isList = preg_match('/(lista|listado|muestra|dame|ver|cuales son)/i', $lower);
        
        // Casos especiales para preguntas negativas
        if (preg_match('/(que|qué|cuales|cuáles).*(equipos).*(no tienen|sin).*(stock|disponibilidad)/i', $lower)) {
            if ($userRole === 'Administrador') {
                return $this->getEquiposSinStock();
            }
        }
        
        if (preg_match('/(hay|muestra|dame|lista).*(usuarios).*(sin verificar|no verificados)/i', $lower)) {
            if ($userRole === 'Administrador') {
                return $this->getUsuariosSinVerificar();
            }
        }

        // Buscar la entidad mencionada (en orden de especificidad)
        foreach ($entityMap as $entity => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (strpos($lower, $synonym) !== false) {
                    // Mapeamos la entidad a la clave de estadísticas
                    $statKey = $this->mapEntityToStatKey($entity);
                    
                    if ($statKey && isset($stats[$statKey])) {
                        if ($isQuantitative) {
                            return $this->formatQuantitativeResponse($entity, $stats[$statKey]);
                        } elseif ($isExplanatory) {
                            return $this->formatExplanatoryResponse($entity, $stats[$statKey]);
                        }
                    }
                    
                    // Si encontramos la entidad pero no coincide con el tipo de pregunta, salir
                    break 2;
                }
            }
        }

        return null;
    }

    /**
     * Mapea entidades semánticas a claves de estadísticas
     */
    private function mapEntityToStatKey($entity) {
        $mapping = [
            'usuarios' => 'total_usuarios',
            'profesores' => 'profesores',
            'encargados' => 'encargados',
            'administradores' => 'administradores',
            'verificados' => 'verificados',
            'no_verificados' => 'no_verificados',
            'aulas' => 'total_aulas',
            'aulas_aip' => 'aulas_aip',
            'aulas_regulares' => 'aulas_regulares',
            'equipos' => 'total_equipos',
            'equipos_disponibles' => 'equipos_disponibles',
            'equipos_prestados' => 'equipos_prestados',
            'tipos_equipo' => 'tipos_equipo',
            'reservas_activas' => 'reservas_activas_global',
            'prestamos_pendientes' => 'prestamos_pendientes_global',
            'prestamos' => 'prestamos_pendientes_global',
            'prestamos_vencidos' => 'prestamos_vencidos',
            'devoluciones_hoy' => 'devoluciones_hoy'
        ];

        return $mapping[$entity] ?? null;
    }

    /**
     * Formatea respuesta cuantitativa
     */
    private function formatQuantitativeResponse($entity, $value) {
        $responses = [
            'usuarios' => "Actualmente hay **{$value}** usuarios registrados en el sistema.",
            'profesores' => "Hay **{$value}** profesores activos en el sistema.",
            'encargados' => "Hay **{$value}** encargados activos.",
            'administradores' => "Hay **{$value}** administradores en el sistema.",
            'verificados' => "**{$value}** usuarios han verificado su correo electrónico.",
            'no_verificados' => "Hay **{$value}** usuarios pendientes de verificar su correo.",
            'aulas' => "El sistema gestiona **{$value}** aulas en total.",
            'aulas_aip' => "Hay **{$value}** aulas de tipo AIP (para reservas).",
            'aulas_regulares' => "Hay **{$value}** aulas de tipo Regular.",
            'equipos' => "En total hay **{$value}** equipos registrados.",
            'equipos_disponibles' => "Actualmente hay **{$value}** equipos disponibles para préstamo.",
            'equipos_prestados' => "En este momento hay **{$value}** equipos prestados.",
            'tipos_equipo' => "El sistema gestiona **{$value}** tipos diferentes de equipos.",
            'reservas_activas' => "Hay **{$value}** reservas de aulas activas.",
            'prestamos_pendientes' => "Hay **{$value}** préstamos pendientes de devolución.",
            'prestamos' => "Hay **{$value}** préstamos pendientes de devolución.",
            'prestamos_vencidos' => $value > 0 ? "⚠️ ¡Atención! Hay **{$value}** préstamos vencidos que requieren atención inmediata." : "✅ No hay préstamos vencidos en este momento.",
            'devoluciones_hoy' => "Hoy se han registrado **{$value}** devoluciones de equipos."
        ];

        return $responses[$entity] ?? "El valor es: **{$value}**";
    }

    /**
     * Formatea respuesta explicativa
     */
    private function formatExplanatoryResponse($entity, $value) {
        $explanations = [
            'usuarios' => "Los usuarios son las cuentas registradas en el sistema. Actualmente hay **{$value}** usuarios, que pueden ser profesores, encargados o administradores.",
            'aulas' => "Las aulas son espacios gestionados por el sistema. Hay **{$value}** aulas: algunas son AIP (para reservas de profesores) y otras son regulares (para préstamos de equipos).",
            'equipos' => "Los equipos son dispositivos que se pueden prestar a los profesores (laptops, proyectores, etc.). El sistema gestiona **{$value}** equipos en total.",
            'prestamos_vencidos' => $value > 0 ? "Los préstamos vencidos son aquellos que han superado su fecha de devolución. Actualmente hay **{$value}** préstamos vencidos que requieren seguimiento." : "Los préstamos vencidos son aquellos que superan su fecha de devolución. Actualmente no hay ninguno."
        ];

        return $explanations[$entity] ?? $this->formatQuantitativeResponse($entity, $value);
    }

    /**
     * Maneja consultas avanzadas del administrador que requieren queries específicas
     */
    private function handleAdvancedAdminQuery($lower, $userId) {
        // Listado de usuarios
        if (preg_match('/(lista|listado|muestra|dame).*(usuarios|profesores|encargados|administradores)/i', $lower)) {
            return $this->getUsuariosList($lower);
        }

        // Listado de aulas
        if (preg_match('/(lista|listado|muestra|dame).*(aulas|salones)/i', $lower)) {
            return $this->getAulasList($lower);
        }

        // Listado de equipos - AMPLIADO para detectar más variaciones
        if (preg_match('/(lista|listado|muestra|dame|qué|que|cuales|cuáles).*(equipos|dispositivos)/i', $lower) ||
            preg_match('/(equipos|dispositivos).*(disponibles|hay|están|estan|tenemos|puedo|solicitar)/i', $lower) ||
            preg_match('/(que equipos|qué equipos|cuales equipos|cuáles equipos).*(disponibles|hay|puedo)/i', $lower) ||
            preg_match('/(disponibles ahora|disponibles|en stock).*(equipos)/i', $lower)) {
            return $this->getEquiposList($lower);
        }

        // Estado del sistema (alertas, problemas)
        if (preg_match('/(estado|salud|problemas|alertas|issues).*(sistema)/i', $lower)) {
            return $this->getSystemStatus();
        }
        
        // Préstamos activos/pendientes
        if (preg_match('/(muestra|dame|lista).*(prestamos|préstamos).*(activos|pendientes)/i', $lower)) {
            return $this->getPrestamosActivos();
        }
        
        // Reservas activas
        if (preg_match('/(muestra|dame|lista).*(reservas).*(activas|pendientes|futuras)/i', $lower)) {
            return $this->getReservasActivas();
        }
        
        // Usuarios sin verificar
        if (preg_match('/(muestra|dame|lista).*(usuarios).*(sin verificar|no verificados|pendientes)/i', $lower)) {
            return $this->getUsuariosSinVerificar();
        }
        
        // Equipos sin stock
        if (preg_match('/(muestra|dame|lista).*(equipos).*(sin stock|agotados|sin disponibilidad)/i', $lower)) {
            return $this->getEquiposSinStock();
        }
        
        // Roles disponibles en el sistema
        if (preg_match('/(cuantos|cuales|que).*(roles|tipos de usuario)/i', $lower)) {
            return $this->getRolesInfo();
        }

        return null;
    }

    /**
     * Maneja consultas comunes para Profesor y Encargado
     */
    private function handleCommonQueries($lower, $userId) {
        // Listado de equipos disponibles - MUCHAS VARIACIONES
        if (preg_match('/(lista|listado|muestra|dame|qué|que|cuales|cuáles).*(equipos|dispositivos)/i', $lower) ||
            preg_match('/(equipos|dispositivos).*(disponibles|hay|están|estan|tenemos|puedo|solicitar)/i', $lower) ||
            preg_match('/(que equipos|qué equipos|cuales equipos|cuáles equipos).*(disponibles|hay|puedo)/i', $lower) ||
            preg_match('/(disponibles ahora|disponibles|en stock).*(equipos)/i', $lower) ||
            preg_match('/(necesito|quiero|busco).*(equipo|laptop|proyector|mouse|teclado)/i', $lower)) {
            return $this->getEquiposList($lower);
        }
        
        // Listado de aulas disponibles
        if (preg_match('/(lista|listado|muestra|dame|qué|que|cuales|cuáles).*(aulas|salones)/i', $lower) ||
            preg_match('/(aulas|salones).*(disponibles|hay|puedo|reservar)/i', $lower) ||
            preg_match('/(que aulas|qué aulas|cuales aulas).*(puedo|disponibles)/i', $lower)) {
            return $this->getAulasList($lower);
        }
        
        return null;
    }

    /**
     * Obtiene listado de usuarios desde la BD
     */
    private function getUsuariosList($query) {
        try {
            $roleFilter = '';
            if (strpos($query, 'profesor') !== false) {
                $roleFilter = "tipo_usuario = 'Profesor' AND";
            } elseif (strpos($query, 'encargado') !== false) {
                $roleFilter = "tipo_usuario = 'Encargado' AND";
            } elseif (strpos($query, 'administrador') !== false) {
                $roleFilter = "tipo_usuario = 'Administrador' AND";
            }

            $sql = "SELECT nombre, correo, tipo_usuario, verificado 
                    FROM usuarios 
                    WHERE $roleFilter activo = 1
                    ORDER BY tipo_usuario, nombre 
                    LIMIT 10";
            
            $stmt = $this->db->query($sql);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($usuarios)) {
                return "No se encontraron usuarios con esos criterios.";
            }

            $response = "### 👥 Listado de Usuarios\n\n";
            foreach ($usuarios as $u) {
                $verificado = $u['verificado'] ? '✅' : '⏳';
                $response .= "- **{$u['nombre']}** ({$u['tipo_usuario']}) $verificado\n";
                $response .= "  📧 {$u['correo']}\n";
            }

            if (count($usuarios) >= 10) {
                $response .= "\n_Mostrando los primeros 10 resultados._";
            }

            return $response;
        } catch (Exception $e) {
            error_log("Error en getUsuariosList: " . $e->getMessage());
            return "Error al obtener el listado de usuarios.";
        }
    }

    /**
     * Obtiene listado de aulas desde la BD
     */
    private function getAulasList($query) {
        try {
            $sql = "SELECT nombre_aula, tipo, capacidad FROM aulas WHERE activo = 1 ORDER BY tipo, nombre_aula";
            $stmt = $this->db->query($sql);
            $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($aulas)) {
                return "No hay aulas registradas en el sistema.";
            }

            $response = "### 🏫 Listado de Aulas\n\n";
            $currentType = '';
            foreach ($aulas as $a) {
                if ($currentType !== $a['tipo']) {
                    $currentType = $a['tipo'];
                    $icon = $a['tipo'] === 'AIP' ? '📅' : '🏛️';
                    $response .= "\n**{$icon} {$a['tipo']}:**\n";
                }
                $response .= "- {$a['nombre_aula']} (Capacidad: {$a['capacidad']})\n";
            }

            return $response;
        } catch (Exception $e) {
            error_log("Error en getAulasList: " . $e->getMessage());
            return "Error al obtener el listado de aulas.";
        }
    }

    /**
     * Obtiene listado de equipos desde la BD
     */
    private function getEquiposList($query) {
        try {
            $sql = "SELECT nombre_equipo, tipo_equipo, stock, stock_maximo
                    FROM equipos
                    WHERE activo = 1
                    ORDER BY tipo_equipo, nombre_equipo
                    LIMIT 15";
            
            $stmt = $this->db->query($sql);
            $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($equipos)) {
                return "No hay equipos registrados en el sistema.";
            }

            $response = "### 💻 Listado de Equipos\n\n";
            $currentType = '';
            foreach ($equipos as $eq) {
                if ($currentType !== $eq['tipo_equipo']) {
                    $currentType = $eq['tipo_equipo'];
                    $response .= "\n**📦 {$eq['tipo_equipo']}:**\n";
                }
                $disponible = $eq['stock'];
                $total = $eq['stock_maximo'];
                $status = $disponible > 0 ? '✅' : '❌';
                $response .= "- **{$eq['nombre_equipo']}** {$status}\n";
                $response .= "  Stock: {$disponible}/{$total} disponibles\n";
            }

            if (count($equipos) >= 15) {
                $response .= "\n_Mostrando los primeros 15 equipos._";
            }

            return $response;
        } catch (Exception $e) {
            error_log("Error en getEquiposList: " . $e->getMessage());
            return "Error al obtener el listado de equipos.";
        }
    }

    /**
     * Obtiene el estado de salud del sistema
     */
    private function getSystemStatus() {
        try {
            $alerts = [];

            // Verificar préstamos vencidos (más de 1 día prestados)
            $stmt = $this->db->query("
                SELECT COUNT(*) as total 
                FROM prestamos 
                WHERE estado = 'Prestado' 
                AND DATEDIFF(CURDATE(), fecha_prestamo) > 1
            ");
            $vencidos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            if ($vencidos > 0) {
                $alerts[] = "⚠️ **{$vencidos}** préstamos vencidos requieren atención";
            }

            // Verificar usuarios sin verificar
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE verificado = 0 AND activo = 1");
            $sinVerificar = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            if ($sinVerificar > 5) {
                $alerts[] = "⏳ **{$sinVerificar}** usuarios pendientes de verificación";
            }

            // Verificar equipos sin stock
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM equipos WHERE stock = 0 AND activo = 1");
            $sinStock = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            if ($sinStock > 0) {
                $alerts[] = "📦 **{$sinStock}** tipos de equipos sin stock disponible";
            }

            $response = "### 🏥 Estado del Sistema\n\n";
            
            if (empty($alerts)) {
                $response .= "✅ **Todo funcionando correctamente**\n\n";
                $response .= "No hay alertas ni problemas detectados en este momento.";
            } else {
                $response .= "**Alertas Activas:**\n\n";
                foreach ($alerts as $alert) {
                    $response .= "- {$alert}\n";
                }
            }

            return $response;
        } catch (Exception $e) {
            error_log("Error en getSystemStatus: " . $e->getMessage());
            return "Error al verificar el estado del sistema.";
        }
    }

    /**
     * Proporciona una respuesta de fallback contextual si no se encuentra guía ni respuesta local.
     */
    private function getFallbackResponse($userRole) {
        // Mostrar consultas rápidas según el rol
        if ($userRole === 'Administrador') {
            return $this->getConsultasRapidasAdmin();
        }
        
        if ($userRole === 'Profesor') {
            return $this->getConsultasRapidasProfesor();
        }
        
        if ($userRole === 'Encargado') {
            return $this->getConsultasRapidasEncargado();
        }

        return $this->getConsultasRapidasProfesor();
    }
    
    /**
     * Muestra consultas rápidas para el Administrador (con botones clicables)
     */
    private function getConsultasRapidasAdmin() {
        $response = "### 🎯 ¿En qué puedo ayudarte?\n\n";
        $response .= "_Haz clic en cualquier pregunta para obtener la respuesta:_\n\n";
        
        $response .= "#### 📊 **CONSULTAS DE DATOS**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cuántos usuarios hay?\")'>👥 ¿Cuántos usuarios hay?</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cuántos profesores hay?\")'>👨‍🏫 ¿Cuántos profesores hay?</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Hay préstamos vencidos?\")'>⏰ ¿Hay préstamos vencidos?</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cuántos equipos disponibles?\")'>💻 ¿Cuántos equipos disponibles?</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"Dame información del sistema\")'>📊 Información del sistema</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 📚 **GUÍAS DE GESTIÓN**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo gestiono usuarios?\")'>👥 Gestionar usuarios</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo administro equipos?\")'>💻 Administrar equipos</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo gestiono aulas?\")'>🏫 Gestionar aulas</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo funciona el sistema?\")'>⚙️ Cómo funciona el sistema</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué roles existen?\")'>🔑 Roles del sistema</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 📋 **LISTADOS**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"Dame un listado de usuarios\")'>📝 Listado de usuarios</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"Muestra los equipos\")'>💾 Listado de equipos</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"Lista las aulas\")'>🏛️ Listado de aulas</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"Préstamos activos\")'>📦 Préstamos activos</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"Reservas activas\")'>📅 Reservas activas</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### ⚠️ **ALERTAS**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"Estado del sistema\")'>🔔 Estado del sistema</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Usuarios sin verificar?\")'>⚠️ Usuarios sin verificar</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Equipos sin stock?\")'>📉 Equipos sin stock</button>\n";
        $response .= "</div>\n\n";
        
        return $response;
    }
    
    /**
     * Muestra consultas rápidas para el Profesor (con botones clicables)
     */
    private function getConsultasRapidasProfesor() {
        $response = "### 🎯 ¿En qué puedo ayudarte?\n\n";
        $response .= "_Haz clic en cualquier pregunta para obtener la respuesta INSTANTÁNEA:_\n\n";
        
        $response .= "#### 📅 **RESERVAS DE AULAS**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo hago una reserva paso a paso?\")'>📝 Cómo hacer una reserva (PASO A PASO)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo cancelo una reserva?\")'>❌ Cómo cancelar una reserva</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué aulas puedo reservar?\")'>🏛️ Qué aulas puedo reservar</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Puedo reservar para hoy?\")'>⏰ ¿Puedo reservar para hoy?</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 💻 **PRÉSTAMOS DE EQUIPOS**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo solicito un préstamo de equipos?\")'>📦 Cómo solicitar préstamo (PASO A PASO)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué equipos puedo solicitar?\")'>🖥️ Qué equipos puedo solicitar</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo devuelvo los equipos?\")'>🔄 Cómo devolver equipos</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué equipos están disponibles ahora?\")'>💾 Equipos disponibles ahora</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 📜 **HISTORIAL Y REPORTES**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo veo mi historial de reservas y préstamos?\")'>📊 Ver mi historial (PASO A PASO)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo descargo PDF de mi historial?\")'>📥 Descargar PDF (GUÍA COMPLETA)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cuántas reservas tengo activas?\")'>📈 Mis reservas activas</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cuántos préstamos tengo pendientes?\")'>📦 Mis préstamos pendientes</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 🔐 **SEGURIDAD Y VERIFICACIÓN**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo cambio mi contraseña?\")'>🔑 Cambiar contraseña (PASO A PASO)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Por qué no me llega el SMS?\")'>📱 No me llega el SMS (SOLUCIÓN)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué es la verificación SMS?\")'>🔒 ¿Qué es verificación SMS?</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 🏫 **INFORMACIÓN DEL SISTEMA**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo funciona el sistema completo?\")'>⚙️ Cómo funciona el sistema (TUTORIAL)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué permisos tengo como Profesor?\")'>🔐 Mis permisos y funciones</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Diferencia entre aulas AIP y REGULARES?\")'>🏛️ Diferencia AIP vs REGULAR</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "---\n\n";
        $response .= "💬 **O escribe tu pregunta en lenguaje natural:**\n";
        $response .= "_Ejemplos: \"necesito un proyector\", \"cómo reservo\", \"no me llega el código\", \"dame información del sistema\"_\n\n";
        $response .= "🚀 **Navegación inteligente:** También puedo llevarte directamente a módulos:\n";
        $response .= "_\"Ir a reservas\", \"Llévame a préstamos\", \"Ver mi historial\", \"Ir a notificaciones\"_\n\n";
        
        return $response;
    }
    
    /**
     * Muestra consultas rápidas para el Encargado (con botones clicables)
     */
    private function getConsultasRapidasEncargado() {
        $response = "### 🎯 ¿En qué puedo ayudarte?\n\n";
        $response .= "_Haz clic en cualquier pregunta para obtener la respuesta INSTANTÁNEA:_\n\n";
        
        $response .= "#### 🔄 **DEVOLUCIONES (TU FUNCIÓN PRINCIPAL)**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo registro una devolución paso a paso?\")'>📦 Cómo registrar devolución (PASO A PASO)</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo inspecciono los equipos?\")'>🔍 Cómo inspeccionar equipos</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué hago con equipos dañados?\")'>⚠️ Equipos dañados - qué hacer</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Hay préstamos vencidos ahora?\")'>🔴 Préstamos vencidos ahora</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 📜 **HISTORIAL Y CONSULTAS**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo veo el historial?\")'>📊 Ver historial completo</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cuántos préstamos activos hay?\")'>📦 Préstamos activos ahora</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cuántos equipos disponibles hay?\")'>💻 Equipos disponibles</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo descargo PDF del historial?\")'>📥 Descargar PDF historial</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 🔔 **NOTIFICACIONES Y ALERTAS**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo veo mis notificaciones?\")'>🔔 Ver notificaciones</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué notificaciones recibo?\")'>📧 Tipos de notificaciones</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 👤 **PERFIL Y CONFIGURACIÓN**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo edito mi perfil?\")'>✏️ Editar mi perfil</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo cambio mi contraseña?\")'>🔑 Cambiar contraseña</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "#### 🏫 **INFORMACIÓN DEL SISTEMA**\n";
        $response .= "<div class='quick-queries'>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Qué permisos tengo como Encargado?\")'>🔐 Mis permisos y funciones</button>\n";
        $response .= "<button class='query-btn' onclick='sendQuery(\"¿Cómo funciona el sistema?\")'>⚙️ Cómo funciona el sistema</button>\n";
        $response .= "</div>\n\n";
        
        $response .= "---\n\n";
        $response .= "💬 **O escribe tu pregunta en lenguaje natural:**\n";
        $response .= "_Ejemplos: \"hay préstamos vencidos\", \"cómo registro devolución\", \"equipos dañados\", \"ver historial\"_\n\n";
        $response .= "🚀 **Navegación inteligente:** También puedo llevarte directamente a módulos:\n";
        $response .= "_\"Ir a devoluciones\", \"Ver historial\", \"Ir a notificaciones\", \"Ver perfil\"_\n\n";
        
        return $response;
    }
    
    /**
     * Detecta si el usuario está pidiendo una guía paso a paso y la retorna directamente
     * Esto da respuestas más rápidas y consistentes desde la base de datos
     */
    private function detectAndReturnGuide($userMessage, $userRole) {
        $lower = mb_strtolower($userMessage, 'UTF-8');
        
        // ========================================
        // CONSULTAS RÁPIDAS PARA ADMINISTRADOR
        // ========================================
        
        if ($userRole === 'Administrador') {
            // "Ayuda" o "Qué puedo hacer"
            if (preg_match('/(ayuda|help|que puedo|qué puedo|opciones|comandos)/i', $userMessage)) {
                return $this->getConsultasRapidasAdmin();
            }
            
            // "Cómo registrar usuario" - respuesta directa
            if (preg_match('/(como|cómo).*(registrar|crear|agregar|añadir).*(usuario|usuarios)/i', $userMessage)) {
                return $this->getGuiaRapidaRegistrarUsuario();
            }
            
            // "Cómo usar el sistema" 
            if (preg_match('/(como|cómo).*(usar|utilizar|trabajar con).*(sistema)/i', $userMessage)) {
                return self::GUIDE_COMO_FUNCIONA_SISTEMA;
            }
        }
        
        // ========================================
        // CONSULTAS RÁPIDAS PARA PROFESOR
        // ========================================
        
        if ($userRole === 'Profesor') {
            // "Ayuda" o "Qué puedo hacer"
            if (preg_match('/(ayuda|help|que puedo|qué puedo|opciones|comandos)/i', $userMessage)) {
                return $this->getConsultasRapidasProfesor();
            }
        }
        
        // ========================================
        // GUÍAS PARA ADMINISTRADOR
        // ========================================
        
        if ($userRole === 'Administrador') {
            // GUÍA: Cómo gestionar usuarios
            if (preg_match('/(como|cómo|guia|guía).*(gestionar|administrar|crear|editar|agregar|eliminar|manejar).*(usuarios|usuario)/i', $userMessage) ||
                preg_match('/(gestion|gestión|manejo|administracion|administración).*(usuarios)/i', $userMessage)) {
                return self::GUIDE_GESTIONAR_USUARIOS;
            }
            
            // GUÍA: Cómo gestionar equipos
            if (preg_match('/(como|cómo|guia|guía).*(gestionar|administrar|crear|editar|agregar|manejar).*(equipos|equipo|inventario)/i', $userMessage) ||
                preg_match('/(gestion|gestión|manejo|administracion|administración).*(equipos|inventario)/i', $userMessage) ||
                preg_match('/(administro|manejo).*(equipos)/i', $userMessage)) {
                return self::GUIDE_GESTIONAR_EQUIPOS;
            }
            
            // GUÍA: Cómo gestionar aulas
            if (preg_match('/(como|cómo|guia|guía).*(gestionar|administrar|crear|editar|agregar|manejar).*(aulas|aula)/i', $userMessage) ||
                preg_match('/(gestion|gestión|manejo|administracion|administración).*(aulas)/i', $userMessage)) {
                return self::GUIDE_GESTIONAR_AULAS;
            }
            
            // GUÍA: Cómo ver historial global
            if (preg_match('/(como|cómo|guia|guía).*(ver|revisar|consultar|acceder).*(historial|historico)/i', $userMessage) ||
                preg_match('/(historial global|historial completo|todos los registros|veo el historial)/i', $userMessage)) {
                return self::GUIDE_VER_HISTORIAL_GLOBAL;
            }
            
            // GUÍA: Cómo funciona el sistema
            if (preg_match('/(como|cómo).*(funciona|trabaja|opera).*(sistema|todo)/i', $userMessage) ||
                preg_match('/(explicame|dime|cuentame).*(sistema|como funciona|funcionamiento)/i', $userMessage) ||
                preg_match('/(informacion|información).*(sistema|completo|todo)/i', $userMessage) ||
                preg_match('/(dame informacion|brindame informacion)/i', $userMessage)) {
                return self::GUIDE_COMO_FUNCIONA_SISTEMA;
            }
            
            // RESPUESTA: Roles del sistema
            if (preg_match('/(cuantos|cuales|que|qué).*(roles|tipos de usuario)/i', $userMessage) ||
                preg_match('/(roles).*(existen|hay|tiene|tiene el sistema)/i', $userMessage)) {
                return $this->getRolesInfo();
            }
        }
        
        // ========================================
        // DETECCIONES ESPECÍFICAS DE BOTONES DE CONSULTA RÁPIDA
        // (Estas deben ir ANTES de las guías generales para tener prioridad)
        // ========================================
        
        // Botón: "Cómo solicitar préstamo" (PASO A PASO)
        if (preg_match('/c(o|ó)mo solicito.*pr(e|é)stamo/i', $userMessage) ||
            preg_match('/c(o|ó)mo solicitar.*pr(e|é)stamo/i', $userMessage) ||
            preg_match('/solicitar.*pr(e|é)stamo.*paso.*paso/i', $userMessage)) {
            return self::GUIDE_PRESTAMO;
        }
        
        // Botón: "Cómo cancelar una reserva"
        if (preg_match('/c(o|ó)mo cancelo.*reserva/i', $userMessage) ||
            preg_match('/cancelar.*reserva/i', $userMessage)) {
            return self::GUIDE_CANCELAR_RESERVA;
        }
        
        // Botón: "Ver mi historial (PASO A PASO)"
        if (preg_match('/c(o|ó)mo veo.*historial/i', $userMessage) ||
            preg_match('/ver.*historial.*reservas.*pr(e|é)stamos/i', $userMessage)) {
            if ($userRole === 'Profesor') {
                return self::GUIDE_VER_HISTORIAL_PROFESOR;
            } elseif ($userRole === 'Encargado') {
                return self::GUIDE_VER_HISTORIAL_ENCARGADO;
            } else {
                return self::GUIDE_VER_HISTORIAL_GLOBAL;
            }
        }
        
        // Botón: "Descargar PDF (GUÍA COMPLETA)"
        if (preg_match('/c(o|ó)mo descargo.*pdf/i', $userMessage) ||
            preg_match('/descargar.*pdf.*historial/i', $userMessage)) {
            return self::GUIDE_DESCARGAR_PDF_PROFESOR;
        }
        
        // Botón: "No me llega el SMS (SOLUCIÓN)"
        if (preg_match('/por.*qu(e|é).*no.*llega.*sms/i', $userMessage) ||
            preg_match('/no.*llega.*sms/i', $userMessage)) {
            return self::GUIDE_SMS_TROUBLESHOOTING;
        }
        
        // Botón: "Cómo funciona el sistema (TUTORIAL)"
        if (preg_match('/c(o|ó)mo funciona.*sistema.*completo/i', $userMessage) ||
            preg_match('/funciona.*sistema.*tutorial/i', $userMessage)) {
            return self::GUIDE_COMO_FUNCIONA_SISTEMA;
        }
        
        // Botón: "Mis permisos y funciones"
        if (preg_match('/qu(e|é).*permisos.*tengo.*profesor/i', $userMessage) ||
            preg_match('/mis.*permisos.*funciones/i', $userMessage)) {
            if ($userRole === 'Profesor') {
                return self::GUIDE_PERMISOS_PROFESOR;
            } elseif ($userRole === 'Encargado') {
                return self::GUIDE_PERMISOS_ENCARGADO;
            } elseif ($userRole === 'Administrador') {
                return $this->getRolesInfo();
            }
        }
        
        // Botón: "¿Qué es verificación SMS?"
        if (preg_match('/qu(e|é) es.*verificaci(o|ó)n.*sms/i', $userMessage) ||
            preg_match('/verificaci(o|ó)n.*sms.*qu(e|é) es/i', $userMessage)) {
            return self::GUIDE_SMS_TROUBLESHOOTING;
        }
        
        // Botón: "¿Qué aulas puedo reservar?"
        if (preg_match('/que aulas.*puedo.*reservar/i', $userMessage) ||
            preg_match('/aulas.*disponibles.*reservar/i', $userMessage)) {
            return $this->getAulasList($lower);
        }
        
        // Botón: "¿Puedo reservar para hoy?"
        if (preg_match('/puedo.*reservar.*hoy/i', $userMessage) ||
            preg_match('/reservar.*para.*hoy/i', $userMessage)) {
            return "❌ **NO** puedes reservar para hoy.\n\n" .
                   "📋 **Regla del sistema:**\n" .
                   "- Todas las reservas requieren **MÍNIMO 1 DÍA de anticipación**\n" .
                   "- La fecha mínima permitida es **MAÑANA**\n\n" .
                   "💡 **Ejemplo:**\n" .
                   "- Si hoy es lunes, puedes reservar desde martes en adelante\n\n" .
                   "Esta regla garantiza una mejor organización y evita conflictos de último momento.";
        }
        
        // Botón: "¿Qué equipos puedo solicitar?"
        if (preg_match('/que equipos.*puedo.*solicitar/i', $userMessage)) {
            return $this->getEquiposList($lower);
        }
        
        // Botón: "Equipos disponibles ahora"
        if (preg_match('/equipos.*disponibles.*ahora/i', $userMessage)) {
            return $this->getEquiposList($lower);
        }
        
        // Botón: "Cómo devolver equipos"
        if (preg_match('/como.*devolver.*equipos/i', $userMessage)) {
            if ($userRole === 'Encargado') {
                return self::GUIDE_DEVOLVER_EQUIPOS_ENCARGADO;
            } else {
                return "📦 **Devolución de Equipos**\n\n" .
                       "❗ Solo el **Encargado** puede registrar devoluciones tras inspección física del equipo.\n\n" .
                       "👨‍🏫 **Si eres Profesor:**\n" .
                       "- Lleva el equipo al Encargado\n" .
                       "- El Encargado inspeccionará el estado\n" .
                       "- Validará: OK, Dañado, o Falta accesorio\n" .
                       "- Registrará la devolución en el sistema\n\n" .
                       "💡 El stock se actualiza automáticamente al devolver.";
            }
        }
        
        // Botón: "Mis reservas activas"
        if (preg_match('/mis.*reservas.*activas/i', $userMessage) ||
            preg_match('/cu(a|á)ntas.*reservas.*tengo.*activas/i', $userMessage)) {
            return "📅 Para ver tus reservas activas:\n\n" .
                   "1. Ve al módulo **Historial**\n" .
                   "2. Selecciona la pestaña **Aulas (Reservas)**\n" .
                   "3. Verás todas tus reservas de la semana actual\n\n" .
                   "💡 También puedes descargar un PDF con tu historial completo.";
        }
        
        // Botón: "Mis préstamos pendientes"
        if (preg_match('/mis.*pr(e|é)stamos.*pendientes/i', $userMessage) ||
            preg_match('/cu(a|á)ntos.*pr(e|é)stamos.*tengo.*pendientes/i', $userMessage)) {
            return "📦 Para ver tus préstamos pendientes:\n\n" .
                   "1. Ve al módulo **Historial**\n" .
                   "2. Selecciona la pestaña **Equipos (Préstamos)**\n" .
                   "3. Verás todos tus préstamos activos\n\n" .
                   "💡 Recuerda devolver los equipos al Encargado cuando termines de usarlos.";
        }
        
        // Botón: "Diferencia AIP vs REGULAR"
        if (preg_match('/diferencia.*aip.*regular/i', $userMessage)) {
            return self::GUIDE_DIFERENCIA_AULAS;
        }
        
        // ========================================
        // DETECCIONES ESPECÍFICAS DE BOTONES PARA ENCARGADO
        // (Estas deben ir ANTES de las guías generales para tener prioridad)
        // ========================================
        
        if ($userRole === 'Encargado') {
            // Botón: "Cómo registrar devolución (PASO A PASO)"
            if (preg_match('/c(o|ó)mo registro.*devoluci(o|ó)n.*paso.*paso/i', $userMessage) ||
                preg_match('/registrar.*devoluci(o|ó)n.*paso/i', $userMessage)) {
                return self::GUIDE_DEVOLVER_EQUIPOS_ENCARGADO;
            }
            
            // Botón: "Cómo inspecciono los equipos"
            if (preg_match('/c(o|ó)mo inspecciono.*equipos/i', $userMessage) ||
                preg_match('/inspeccionar.*equipos/i', $userMessage) ||
                preg_match('/inspecci(o|ó)n.*equipos/i', $userMessage)) {
                return self::GUIDE_DEVOLVER_EQUIPOS_ENCARGADO; // La guía incluye inspección
            }
            
            // Botón: "¿Qué hago con equipos dañados?"
            if (preg_match('/qu(e|é) hago.*equipos.*da(ñ|n)ados/i', $userMessage) ||
                preg_match('/equipos.*da(ñ|n)ados.*qu(e|é) hacer/i', $userMessage)) {
                return "⚠️ **Equipos Dañados - Procedimiento:**\n\n" .
                       "1. **Durante inspección:** Marca el estado como 'Dañado'\n" .
                       "2. **Comentario obligatorio:** Describe el daño específico\n" .
                       "   - Ejemplos: 'Pantalla rota', 'Teclado con teclas sueltas'\n" .
                       "3. **Registra la devolución** con ese estado\n" .
                       "4. **Notificación automática:** Se envía alerta al Administrador\n" .
                       "5. **NO vuelvas a prestar ese equipo** hasta que sea reparado\n\n" .
                       "💡 El Administrador recibirá la notificación y tomará medidas.";
            }
            
            // Botón: "¿Hay préstamos vencidos ahora?"
            if (preg_match('/hay.*pr(e|é)stamos.*vencidos/i', $userMessage) ||
                preg_match('/pr(e|é)stamos.*vencidos.*ahora/i', $userMessage)) {
                return $this->getPrestamosVencidos();
            }
            
            // Botón: "Ver historial completo"
            if (preg_match('/ver.*historial.*completo/i', $userMessage) ||
                preg_match('/c(o|ó)mo veo.*historial/i', $userMessage)) {
                return self::GUIDE_VER_HISTORIAL_ENCARGADO;
            }
            
            // Botón: "Préstamos activos ahora"
            if (preg_match('/cu(a|á)ntos.*pr(e|é)stamos.*activos/i', $userMessage) ||
                preg_match('/pr(e|é)stamos.*activos.*ahora/i', $userMessage)) {
                return $this->getPrestamosActivos();
            }
            
            // Botón: "Equipos disponibles"
            if (preg_match('/cu(a|á)ntos.*equipos.*disponibles/i', $userMessage) ||
                preg_match('/equipos.*disponibles.*hay/i', $userMessage)) {
                return $this->getEquiposList($userMessage);
            }
            
            // Botón: "Descargar PDF historial"
            if (preg_match('/c(o|ó)mo descargo.*pdf.*historial/i', $userMessage) ||
                preg_match('/descargar.*pdf.*historial/i', $userMessage)) {
                return "📥 **Descargar PDF del Historial (Encargado):**\n\n" .
                       "1. Ve al módulo **Historial**\n" .
                       "2. Selecciona el turno: **Mañana** o **Tarde**\n" .
                       "3. Navega a la semana que deseas exportar (flechas ◀ ▶)\n" .
                       "4. Haz clic en **'🟢 Descargar PDF'** (esquina superior)\n" .
                       "5. El PDF se descarga automáticamente con:\n" .
                       "   - Todas las devoluciones de esa semana\n" .
                       "   - Estados de los equipos\n" .
                       "   - Comentarios de inspección\n\n" .
                       "💡 Puedes imprimir o guardar el reporte para tus registros.";
            }
            
            // Botón: "Ver notificaciones"
            if (preg_match('/c(o|ó)mo veo.*notificaciones/i', $userMessage) ||
                preg_match('/ver.*notificaciones/i', $userMessage)) {
                return self::GUIDE_NOTIFICACIONES_ENCARGADO;
            }
            
            // Botón: "Tipos de notificaciones"
            if (preg_match('/qu(e|é).*notificaciones.*recibo/i', $userMessage) ||
                preg_match('/tipos.*notificaciones/i', $userMessage)) {
                return "🔔 **Notificaciones que recibes como Encargado:**\n\n" .
                       "1. **Nueva reserva creada** (informativo)\n" .
                       "   - Un profesor reservó un aula\n" .
                       "   - Solo para conocimiento\n\n" .
                       "2. **Nuevo préstamo solicitado** (informativo)\n" .
                       "   - Un profesor solicitó equipos\n" .
                       "   - Solo para conocimiento\n\n" .
                       "3. **Alertas del sistema** (si las configura Admin)\n" .
                       "   - Equipos sin stock\n" .
                       "   - Préstamos vencidos\n\n" .
                       "💡 Accede a tus notificaciones haciendo clic en el icono 🔔 en la navbar.";
            }
            
            // Botón: "Editar mi perfil"
            if (preg_match('/c(o|ó)mo edito.*perfil/i', $userMessage) ||
                preg_match('/editar.*perfil/i', $userMessage)) {
                return self::GUIDE_PERFIL_ENCARGADO;
            }
            
            // Botón: "Cambiar contraseña"
            if (preg_match('/c(o|ó)mo cambio.*contrase(ñ|n)a/i', $userMessage)) {
                return self::GUIDE_CAMBIAR_CLAVE;
            }
            
            // Botón: "Mis permisos y funciones"
            if (preg_match('/qu(e|é).*permisos.*tengo.*encargado/i', $userMessage) ||
                preg_match('/mis.*permisos.*funciones/i', $userMessage)) {
                return self::GUIDE_PERMISOS_ENCARGADO;
            }
            
            // Botón: "Cómo funciona el sistema"
            if (preg_match('/c(o|ó)mo funciona.*sistema/i', $userMessage)) {
                return self::GUIDE_COMO_FUNCIONA_SISTEMA;
            }
        }
        
        // ========================================
        // GUÍAS GENERALES (TODOS LOS ROLES)
        // ========================================
        
        // GUÍA: Cómo hacer una reserva (MUCHAS VARIACIONES)
        if (preg_match('/(pasos|guia|guía|tutorial|como|cómo).*(reservar|hacer una reserva|reserva de aula)/i', $userMessage) ||
            preg_match('/(quiero|necesito|puedo).*(reservar|hacer una reserva).*(aula|aip)/i', $userMessage) ||
            preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(reservar|hacer reserva)/i', $userMessage) ||
            preg_match('/(como hago|cómo hago|como se hace|cómo se hace).*(reserva|reservar)/i', $userMessage) ||
            preg_match('/(proceso|procedimiento|forma).*(reservar|reserva de aula)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(reservar|reserva)/i', $userMessage)) {
            return self::GUIDE_RESERVA;
        }
        
        // GUÍA: Cómo solicitar un préstamo (MUCHAS VARIACIONES)
        if (preg_match('/(pasos|guia|guía|tutorial|como|cómo).*(préstamo|prestamo|pedir|solicitar).*(equipo|equipos|laptop|proyector)/i', $userMessage) ||
            preg_match('/(quiero|necesito|puedo).*(pedir|solicitar|prestamo|préstamo).*(laptop|proyector|equipo|equipos)/i', $userMessage) ||
            preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(prestamo|préstamo|solicitar equipo)/i', $userMessage) ||
            preg_match('/(como hago|cómo hago|como se hace|cómo se hace).*(prestamo|préstamo|pido equipo)/i', $userMessage) ||
            preg_match('/(proceso|procedimiento|forma).*(prestamo|préstamo|solicitar equipo)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(prestamo|préstamo|equipos)/i', $userMessage) ||
            preg_match('/(como pido|cómo pido|como solicito|cómo solicito).*(laptop|proyector|equipos)/i', $userMessage)) {
            return self::GUIDE_PRESTAMO;
        }
        
        // GUÍA: Cómo cambiar contraseña (MUCHAS VARIACIONES)
        if (preg_match('/(pasos|guia|guía|tutorial|como|cómo).*(cambiar|modificar|actualizar).*(contraseña|password|clave|pass)/i', $userMessage) ||
            preg_match('/(quiero|necesito|puedo).*(cambiar|modificar).*(contraseña|password|clave)/i', $userMessage) ||
            preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(cambiar).*(contraseña|password)/i', $userMessage) ||
            preg_match('/(como cambio|cómo cambio|como modifico).*(contraseña|password|clave)/i', $userMessage) ||
            preg_match('/(resetear|reiniciar|restablecer).*(contraseña|password)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(contraseña|password)/i', $userMessage)) {
            return self::GUIDE_CAMBIAR_CLAVE;
        }
        
        // GUÍA: Cómo cancelar una reserva (MUCHAS VARIACIONES)
        if (preg_match('/(pasos|guia|guía|tutorial|como|cómo).*(cancelar|eliminar|borrar|anular).*(reserva)/i', $userMessage) ||
            preg_match('/(quiero|necesito|puedo).*(cancelar|eliminar|borrar).*(reserva)/i', $userMessage) ||
            preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(cancelar).*(reserva)/i', $userMessage) ||
            preg_match('/(como cancelo|cómo cancelo|como elimino).*(reserva)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(cancelar).*(reserva)/i', $userMessage)) {
            return self::GUIDE_CANCELAR_RESERVA;
        }
        
        // GUÍA: Problemas con SMS (MUCHAS VARIACIONES)
        if (preg_match('/(no|por que|porque|porqué|por qué).*(llega|recib|viene|envia|envía).*(sms|codigo|código|mensaje|verificacion|verificación)/i', $userMessage) ||
            preg_match('/(problema|error|ayuda|fallo|no funciona).*(sms|codigo|código|verificacion|verificación)/i', $userMessage) ||
            preg_match('/(no me llega|no recibo|no llego|no llegó).*(sms|codigo|código|mensaje)/i', $userMessage) ||
            preg_match('/(sms|codigo|código).*(no llega|no llego|no llegó|no funciona)/i', $userMessage) ||
            preg_match('/(ayuda|help|auxilio).*(verificacion|verificación|sms)/i', $userMessage)) {
            return self::GUIDE_SMS_TROUBLESHOOTING;
        }
        
        // GUÍA: Diferencia entre aulas AIP y REGULARES (MUCHAS VARIACIONES)
        if (preg_match('/(diferencia|que es|qué es|cual es|cuál es).*(aula|aulas).*(aip|regular|regulares)/i', $userMessage) ||
            preg_match('/(explica|explicame|explicámelo|dime|cuentame|cuéntame).*(aulas|aip|regulares)/i', $userMessage) ||
            preg_match('/(que significa|qué significa|que son|qué son).*(aip|aulas aip|aulas regulares)/i', $userMessage) ||
            preg_match('/(diferencia|comparacion|comparación).*(aip|regular)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(aulas|aip|regular)/i', $userMessage)) {
            return self::GUIDE_DIFERENCIA_AULAS;
        }
        
        // ========================================
        // NUEVAS GUÍAS EXCLUSIVAS PARA PROFESOR
        // ========================================
        
        // GUÍA: Cómo ver historial (MUCHAS VARIACIONES)
        if (preg_match('/(como|cómo).*(veo|ver|consulto|consultar|reviso|revisar|accedo|acceder).*(historial|mis reservas|mis prestamos|mis préstamos|mi actividad)/i', $userMessage) ||
            preg_match('/(quiero|necesito|puedo).*(ver|consultar|revisar).*(historial|mis reservas|mis prestamos)/i', $userMessage) ||
            preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(historial|mis reservas|ver reservas)/i', $userMessage) ||
            preg_match('/(donde|dónde).*(veo|ver|está|esta).*(historial|mis reservas|mis prestamos)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(historial|ver reservas|mis prestamos)/i', $userMessage) ||
            preg_match('/(como accedo|cómo accedo|como entro).*(historial)/i', $userMessage) ||
            preg_match('/(ver|consultar|revisar).*(mi|mis).*(reservas|prestamos|préstamos)/i', $userMessage)) {
            return self::GUIDE_VER_HISTORIAL_PROFESOR;
        }
        
        // GUÍA: Cómo descargar PDF (MUCHAS VARIACIONES)
        if (preg_match('/(como|cómo).*(descargo|descargar|exporto|exportar|genero|generar|imprimo|imprimir).*(pdf|reporte|informe|documento)/i', $userMessage) ||
            preg_match('/(quiero|necesito|puedo).*(descargar|exportar|generar).*(pdf|reporte|historial)/i', $userMessage) ||
            preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(descargar|exportar).*(pdf|reporte)/i', $userMessage) ||
            preg_match('/(donde|dónde).*(descargo|descargar|genero).*(pdf|reporte)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(pdf|descargar|exportar|reporte)/i', $userMessage) ||
            preg_match('/(exportar|generar).*(historial|reporte|informe)/i', $userMessage) ||
            preg_match('/(como saco|cómo saco|como obtengo).*(pdf|reporte)/i', $userMessage)) {
            return self::GUIDE_DESCARGAR_PDF_PROFESOR;
        }
        
        // GUÍA: Cómo manejar el sistema (MUCHAS VARIACIONES)
        if (preg_match('/(como|cómo).*(manejo|manejar|uso|usar|utilizo|utilizar|trabajo|trabajar|funciona).*(sistema|plataforma|aplicacion|aplicación)/i', $userMessage) ||
            preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(usar|manejar|trabajar).*(sistema)/i', $userMessage) ||
            preg_match('/(tutorial|guia|guía).*(sistema|usar sistema|manejar sistema)/i', $userMessage) ||
            preg_match('/(como se usa|cómo se usa|como funciona|cómo funciona).*(sistema|plataforma)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(usar|manejar).*(sistema)/i', $userMessage) ||
            preg_match('/(como empiezo|cómo empiezo|por donde empiezo|por dónde empiezo)/i', $userMessage) ||
            preg_match('/(explicame|explicámelo|dime).*(sistema|como funciona|funcionamiento)/i', $userMessage)) {
            return self::GUIDE_MANEJO_SISTEMA_PROFESOR;
        }
        
        // GUÍA: Permisos de Profesor (MUCHAS VARIACIONES)
        if (preg_match('/(que|qué).*(puedo|puede).*(hacer|realizar|usar|funciones|permisos)/i', $userMessage) ||
            preg_match('/(cuales|cuáles).*(son|tengo).*(mis permisos|mis funciones|capacidades)/i', $userMessage) ||
            preg_match('/(informacion|información).*(profesor|mi rol|mis permisos)/i', $userMessage) ||
            preg_match('/(dame informacion|brindame información|dime).*(sistema|profesor|mi rol)/i', $userMessage) ||
            preg_match('/(que funciones|qué funciones|que opciones).*(tengo|puedo|dispongo)/i', $userMessage) ||
            preg_match('/(ayuda|help).*(permisos|funciones|rol profesor)/i', $userMessage) ||
            preg_match('/(soy profesor|mi rol|rol profesor).*(que puedo|qué puedo|funciones|permisos)/i', $userMessage) ||
            preg_match('/(limitaciones|restricciones).*(profesor|mi rol)/i', $userMessage)) {
            return self::GUIDE_PERMISOS_PROFESOR;
        }
        
        // ========================================
        // GUÍAS EXCLUSIVAS PARA ENCARGADO
        // ========================================
        
        if ($userRole === 'Encargado') {
            // "Ayuda" o "Qué puedo hacer"
            if (preg_match('/(ayuda|help|que puedo|qué puedo|opciones|comandos)/i', $userMessage)) {
                return $this->getConsultasRapidasEncargado();
            }
            
            // GUÍA: Cómo registrar devoluciones (FUNCIÓN PRINCIPAL)
            if (preg_match('/(como|cómo).*(registro|registrar|hago|hacer|proceso|procesar).*(devolucion|devolución|devoluci|entrega)/i', $userMessage) ||
                preg_match('/(pasos|guia|guía|tutorial).*(devolucion|devolución|registrar devolucion)/i', $userMessage) ||
                preg_match('/(quiero|necesito|puedo).*(registrar|hacer).*(devolucion|devolución)/i', $userMessage) ||
                preg_match('/(enseñame|enséñame|muéstrame|muestrame).*(devolucion|devolución|registrar)/i', $userMessage) ||
                preg_match('/(como devuelvo|cómo devuelvo|como recibo|cómo recibo).*(equipos|equipo)/i', $userMessage) ||
                preg_match('/(ayuda|help).*(devolucion|devolución|devolver)/i', $userMessage) ||
                preg_match('/(inspeccionar|inspeccion|inspección|revisar).*(equipos|devolucion|devolución)/i', $userMessage) ||
                preg_match('/(profesor|usuario).*(devuelve|devolver|entregar|entrega).*(equipo)/i', $userMessage)) {
                return self::GUIDE_DEVOLVER_EQUIPOS_ENCARGADO;
            }
            
            // GUÍA: Cómo ver historial
            if (preg_match('/(como|cómo).*(veo|ver|consulto|consultar|reviso|revisar).*(historial)/i', $userMessage) ||
                preg_match('/(donde|dónde).*(esta|está|veo).*(historial)/i', $userMessage) ||
                preg_match('/(quiero|necesito).*(ver|consultar).*(historial)/i', $userMessage) ||
                preg_match('/(ayuda|help).*(historial)/i', $userMessage)) {
                return self::GUIDE_VER_HISTORIAL_ENCARGADO;
            }
            
            // GUÍA: Configurar perfil
            if (preg_match('/(como|cómo).*(cambio|cambiar|edito|editar|actualizo|actualizar).*(perfil|foto|datos|información)/i', $userMessage) ||
                preg_match('/(mi perfil|mis datos|mi información)/i', $userMessage) ||
                preg_match('/(configurar|configuración).*(perfil|cuenta)/i', $userMessage)) {
                return self::GUIDE_PERFIL_ENCARGADO;
            }
            
            // GUÍA: Notificaciones
            if (preg_match('/(como|cómo).*(veo|ver|consulto).*(notificaciones|alertas|avisos)/i', $userMessage) ||
                preg_match('/(notificaciones|alertas|avisos).*(sistema)/i', $userMessage) ||
                preg_match('/(donde|dónde).*(notificaciones)/i', $userMessage)) {
                return self::GUIDE_NOTIFICACIONES_ENCARGADO;
            }
            
            // GUÍA: Permisos de Encargado
            if (preg_match('/(que|qué).*(puedo|puede).*(hacer|realizar|funciones|permisos)/i', $userMessage) ||
                preg_match('/(cuales|cuáles).*(mis permisos|mis funciones)/i', $userMessage) ||
                preg_match('/(informacion|información).*(encargado|mi rol)/i', $userMessage) ||
                preg_match('/(mi rol|rol encargado|soy encargado)/i', $userMessage) ||
                preg_match('/(limitaciones|restricciones).*(encargado)/i', $userMessage)) {
                return self::GUIDE_PERMISOS_ENCARGADO;
            }
        }
        
        // No se detectó ninguna guía, continuar con IA
        return null;
    }
    
    /**
     * Obtiene estadísticas reales del sistema desde la base de datos
     * Optimizado con caché en memoria para evitar consultas repetidas
     */
    private function getSystemStatistics($userRole, $userId = null) {
        // Usar caché si existe y no ha expirado (5 minutos)
        $now = time();
        if ($this->statsCache !== null && ($now - $this->statsCacheTime) < $this->statsCacheDuration) {
            return $this->statsCache;
        }
        
        try {
            $stats = [];
            
            // Estadísticas GLOBALES (para todos los roles) - OPTIMIZADO con una sola consulta compuesta
            
            // USUARIOS - consulta única optimizada
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN tipo_usuario = 'Profesor' THEN 1 ELSE 0 END) as profesores,
                    SUM(CASE WHEN tipo_usuario = 'Encargado' THEN 1 ELSE 0 END) as encargados,
                    SUM(CASE WHEN tipo_usuario = 'Administrador' THEN 1 ELSE 0 END) as administradores,
                    SUM(CASE WHEN verificado = 1 THEN 1 ELSE 0 END) as verificados,
                    SUM(CASE WHEN verificado = 0 THEN 1 ELSE 0 END) as no_verificados
                FROM usuarios WHERE activo = 1
            ");
            $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats = array_merge($stats, $userStats);
            
            // AULAS - consulta única optimizada
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_aulas,
                    SUM(CASE WHEN tipo = 'AIP' THEN 1 ELSE 0 END) as aulas_aip,
                    SUM(CASE WHEN tipo = 'REGULAR' THEN 1 ELSE 0 END) as aulas_regulares
                FROM aulas WHERE activo = 1
            ");
            $aulaStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats = array_merge($stats, $aulaStats);
            
            // EQUIPOS - consulta única optimizada
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_equipos,
                    COALESCE(SUM(stock), 0) as equipos_disponibles,
                    COALESCE(SUM(stock_maximo - stock), 0) as equipos_prestados
                FROM equipos WHERE activo = 1
            ");
            $equipoStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats = array_merge($stats, $equipoStats);
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM tipos_equipo");
            $stats['tipos_equipo'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // RESERVAS Y PRÉSTAMOS GLOBALES
            // Nota: Las reservas no tienen estado, todas las reservas en la tabla están activas
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM reservas WHERE fecha >= CURDATE()");
            $stats['reservas_activas_global'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'Prestado'");
            $stats['prestamos_pendientes_global'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM reservas WHERE fecha < CURDATE()");
            $stats['reservas_completadas_global'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'Devuelto'");
            $stats['prestamos_completados_global'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM reservas_canceladas");
            $stats['reservas_canceladas_global'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // PRÉSTAMOS VENCIDOS (fecha_prestamo + si no tiene hora_fin, considerar vencido si ya pasaron 2 días)
            $stmt = $this->db->query("
                SELECT COUNT(*) as total 
                FROM prestamos 
                WHERE estado = 'Prestado' 
                AND DATEDIFF(CURDATE(), fecha_prestamo) > 1
            ");
            $stats['prestamos_vencidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // DEVOLUCIONES HOY (para Encargado)
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'Devuelto' AND DATE(fecha_devolucion) = CURDATE()");
            $stats['devoluciones_hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Estadísticas PERSONALES (solo para Profesor)
            if ($userRole === 'Profesor' && $userId) {
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE id_usuario = ? AND fecha >= CURDATE()");
                $stmt->execute([$userId]);
                $stats['reservas_activas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM prestamos WHERE id_usuario = ? AND estado = 'Prestado'");
                $stmt->execute([$userId]);
                $stats['prestamos_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE id_usuario = ? AND fecha < CURDATE()");
                $stmt->execute([$userId]);
                $stats['reservas_completadas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM prestamos WHERE id_usuario = ? AND estado = 'Devuelto'");
                $stmt->execute([$userId]);
                $stats['prestamos_completados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } else {
                // Valores por defecto para Admin/Encargado
                $stats['reservas_activas'] = 0;
                $stats['prestamos_pendientes'] = 0;
                $stats['reservas_completadas'] = 0;
                $stats['prestamos_completados'] = 0;
            }
            
            // Guardar en caché
            $this->statsCache = $stats;
            $this->statsCacheTime = time();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            // Retornar valores por defecto en caso de error
            return [
                'total_usuarios' => 0,
                'profesores' => 0,
                'encargados' => 0,
                'administradores' => 0,
                'verificados' => 0,
                'no_verificados' => 0,
                'total_aulas' => 0,
                'aulas_aip' => 0,
                'aulas_regulares' => 0,
                'total_equipos' => 0,
                'equipos_disponibles' => 0,
                'equipos_prestados' => 0,
                'tipos_equipo' => 0,
                'reservas_activas_global' => 0,
                'prestamos_pendientes_global' => 0,
                'reservas_completadas_global' => 0,
                'prestamos_completados_global' => 0,
                'reservas_canceladas_global' => 0,
                'prestamos_vencidos' => 0,
                'devoluciones_hoy' => 0,
                'reservas_activas' => 0,
                'prestamos_pendientes' => 0,
                'reservas_completadas' => 0,
                'prestamos_completados' => 0
            ];
        }
    }
    
    /**
     * Obtiene contexto específico según el rol del usuario
     */
    private function getRoleSpecificContext($userRole, $userId = null) {
        // Obtener estadísticas reales de la BD
        $stats = $this->getSystemStatistics($userRole, $userId);
        
        $contexts = [
            'Profesor' => "\n\n👤 ROL ACTUAL: PROFESOR" .
                "\n📊 TU ESTADÍSTICA PERSONAL:" .
                "\n  - Reservas activas: {$stats['reservas_activas']}" .
                "\n  - Préstamos pendientes: {$stats['prestamos_pendientes']}" .
                "\n  - Reservas completadas: {$stats['reservas_completadas']}" .
                "\n  - Préstamos completados: {$stats['prestamos_completados']}" .
                
                "\n\n⚠️ RECORDATORIO SMS AUTOMÁTICO:" .
                "\nCuando entres a 'Reservar Aula', 'Préstamo de Equipos' o 'Cambiar Contraseña', el sistema te enviará AUTOMÁTICAMENTE un código de 6 dígitos por SMS. Debes ingresar ese código para verificarte. Sin verificación, NO podrás continuar." .
                
                "\n\n📚 GUÍAS PASO A PASO DISPONIBLES:" .
                "\nPuedes pedirme:" .
                "\n  • 'Cómo hacer una reserva' → Te daré los pasos EXACTOS" .
                "\n  • 'Cómo solicitar un préstamo' → Guía completa con SMS" .
                "\n  • 'Cómo cambiar mi contraseña' → Proceso paso a paso" .
                "\n  • 'Cómo cancelar una reserva' → Instrucciones detalladas" .
                "\n  • 'Por qué no me llega el SMS' → Solución de problemas" .
                "\n  • 'Diferencia entre aulas AIP y regulares' → Explicación completa" .
                
                "\n\n🚀 NAVEGACIÓN INTELIGENTE:" .
                "\nPuedo llevarte directamente a cualquier sección. Solo dime:" .
                "\n  • 'Ir a reservas' / 'Llévame a reservas' → Te redirijo automáticamente" .
                "\n  • 'Ir a préstamos' / 'Mostrar préstamos' → Navegación directa" .
                "\n  • 'Ver mi historial' / 'Ir a historial' → Acceso instantáneo" .
                "\n  • 'Cambiar contraseña' / 'Ir a seguridad' → Navegación rápida" .
                "\n  • 'Ir a notificaciones' / 'Ver avisos' → Te llevo allí" .
                
                "\n\n💬 Pregúntame lo que necesites sobre el sistema. ¡Estoy aquí para guiarte paso a paso!",
                
            'Administrador' => "\n\n👑 ROL ACTUAL: ADMINISTRADOR (Acceso Total)" .
                "\n📊 ESTADÍSTICAS GENERALES DEL SISTEMA:" .
                "\n  👥 Usuarios:" .
                "\n    - Total registrados: {$stats['total_usuarios']}" .
                "\n    - Profesores: {$stats['profesores']}" .
                "\n    - Encargados: {$stats['encargados']}" .
                "\n    - Administradores: {$stats['administradores']}" .
                "\n    - Verificados: {$stats['verificados']}" .
                "\n    - Pendientes de verificar: {$stats['no_verificados']}" .
                
                "\n  🏫 Aulas:" .
                "\n    - Total: {$stats['total_aulas']}" .
                "\n    - Aulas AIP (para reservas): {$stats['aulas_aip']}" .
                "\n    - Aulas REGULARES (para préstamos): {$stats['aulas_regulares']}" .
                
                "\n  💻 Equipos:" .
                "\n    - Total registrados: {$stats['total_equipos']}" .
                "\n    - Disponibles: {$stats['equipos_disponibles']}" .
                "\n    - Prestados actualmente: {$stats['equipos_prestados']}" .
                "\n    - Tipos de equipo: {$stats['tipos_equipo']}" .
                
                "\n  📋 Reservas y Préstamos:" .
                "\n    - Reservas activas: {$stats['reservas_activas_global']}" .
                "\n    - Préstamos pendientes: {$stats['prestamos_pendientes_global']}" .
                "\n    - Reservas completadas (total histórico): {$stats['reservas_completadas_global']}" .
                "\n    - Préstamos completados (total histórico): {$stats['prestamos_completados_global']}" .
                "\n    - Reservas canceladas (total histórico): {$stats['reservas_canceladas_global']}" .
                
                "\n\n📚 CONOCIMIENTO COMPLETO DEL SISTEMA:" .
                "\n\n🎯 PROPÓSITO DEL SISTEMA:" .
                "\nSistema web profesional para gestionar las Aulas de Innovación Pedagógica (AIP) del Colegio Monseñor Juan Tomis Stack en Iquique, Chile. Permite reservar aulas, prestar equipos tecnológicos, controlar inventario y generar reportes administrativos." .
                
                "\n\n🏗️ ARQUITECTURA TÉCNICA:" .
                "\n  • Patrón MVC (Model-View-Controller)" .
                "\n  • Backend: PHP 7.4+ con PDO" .
                "\n  • Base de datos: MySQL con 12 tablas optimizadas" .
                "\n  • Frontend: Bootstrap 5.3.3 + JavaScript ES6" .
                "\n  • Librerías: PHPMailer, Twilio SDK, DomPDF, Chart.js" .
                "\n  • Chatbot: Tommibot con consultas inteligentes" .
                "\n  • Estructura: app/ (MVC), Public/ (assets), backups/, vendor/" .
                
                "\n\n🗄️ BASE DE DATOS (12 TABLAS):" .
                "\n  1. usuarios - Profesores, Encargados, Admins (con teléfono para SMS)" .
                "\n  2. aulas - AIP y REGULARES (capacidad, tipo, estado)" .
                "\n  3. tipos_equipo - Categorías (Laptop, Proyector, etc.)" .
                "\n  4. equipos - Inventario (stock actual/máximo)" .
                "\n  5. reservas - Reservas de aulas AIP" .
                "\n  6. prestamos - Préstamos de equipos (con comentario_devolucion)" .
                "\n  7. reservas_canceladas - Historial de cancelaciones" .
                "\n  8. notificaciones - Sistema in-app (metadata JSON)" .
                "\n  9. verification_codes - Códigos SMS (6 dígitos, 10 min validez)" .
                "\n  10. configuracion_usuario - Perfiles (foto, bio)" .
                "\n  11. mantenimiento_sistema - Log de mantenimientos mensuales" .
                "\n  12. app_config - Configuración general" .
                
                "\n\n🔐 SISTEMA DE SEGURIDAD:" .
                "\n  • Contraseñas hasheadas (bcrypt)" .
                "\n  • Verificación SMS para Profesores (Twilio, código 6 dígitos, 10 min)" .
                "\n  • Tokens únicos: verificación email, reset password, magic login" .
                "\n  • Prevención de caché en páginas autenticadas" .
                "\n  • Validación de sesiones y redirecciones automáticas" .
                
                "\n\n📋 MÓDULOS DEL SISTEMA:" .
                "\n\n1️⃣ MÓDULO AUTENTICACIÓN:" .
                "\n  • Login estándar (email + password)" .
                "\n  • Magic Login (link temporal 10 min por email)" .
                "\n  • Recuperación contraseña (token 1 hora)" .
                "\n  • Verificación email (token único)" .
                "\n  • Registro solo por administradores" .
                
                "\n2️⃣ MÓDULO RESERVAS (Solo Aulas AIP):" .
                "\n  • Anticipación mínima: 1 día (NO mismo día)" .
                "\n  • Verificación SMS automática para profesores" .
                "\n  • Calendario visual por horas (6:00-18:00)" .
                "\n  • Turnos: Mañana (6:00-12:45), Tarde (13:00-18:00)" .
                "\n  • Cancelación: solo el mismo día de crear la reserva" .
                "\n  • Registro en reservas_canceladas con motivo" .
                
                "\n3️⃣ MÓDULO PRÉSTAMOS (Solo Aulas REGULARES):" .
                "\n  • Anticipación mínima: 1 día" .
                "\n  • Verificación SMS automática para profesores" .
                "\n  • Control de stock automático (disminuye al prestar)" .
                "\n  • Agrupación inteligente (varios equipos = 1 pack)" .
                "\n  • Devolución por Encargado con inspección física" .
                "\n  • Estados: OK, Dañado, Falta accesorio" .
                "\n  • Stock aumenta automáticamente al devolver" .
                
                "\n4️⃣ MÓDULO HISTORIAL:" .
                "\n  • Personal (Profesor): solo sus reservas/préstamos" .
                "\n  • Global (Admin/Encargado): todos los usuarios" .
                "\n  • Vista semanal con navegación" .
                "\n  • Calendarios AIP 1, AIP 2 (mañana/tarde)" .
                "\n  • Exportar PDF semanal o personalizado" .
                
                "\n5️⃣ MÓDULO GESTIÓN (Solo Admins):" .
                "\n  • Usuarios: crear, editar, cambiar rol, activar/desactivar" .
                "\n  • Aulas: crear AIP/REGULAR, editar capacidad, activar/desactivar" .
                "\n  • Equipos: crear, editar stock/stock_maximo, activar/desactivar" .
                "\n  • Tipos de equipo: crear nuevas categorías" .
                "\n  • Reportes filtrados: fecha, profesor, tipo, estado" .
                
                "\n6️⃣ MÓDULO ESTADÍSTICAS (Solo Admins):" .
                "\n  • Gráficos de barras: uso de aulas (últimos 30 días)" .
                "\n  • Gráficos de barras: préstamos por equipo" .
                "\n  • Datos en tiempo real desde BD" .
                "\n  • Chart.js para visualización" .
                
                "\n7️⃣ MÓDULO NOTIFICACIONES (Todos):" .
                "\n  • In-app (campana en navbar con contador)" .
                "\n  • Tipos: Reserva confirmada, Préstamo confirmado, Devolución registrada, Préstamo vencido" .
                "\n  • Metadata JSON con detalles completos" .
                "\n  • Limpieza automática: >3 meses en mantenimiento" .
                "\n  • Agrupación inteligente de packs" .
                
                "\n8️⃣ MÓDULO CONFIGURACIÓN:" .
                "\n  • Personal: foto perfil, bio, cambiar contraseña" .
                "\n  • Sistema (Admin): mantenimiento mensual, backups" .
                "\n  • Mantenimiento ejecuta: OPTIMIZE TABLE, limpieza notificaciones, backup auto, limpieza sesiones" .
                "\n  • Limitación: solo cada 30 días" .
                
                "\n9️⃣ MÓDULO TOMMIBOT (IA):" .
                "\n  • Asistente inteligente integrado" .
                "\n  • Contexto por rol (conoce permisos)" .
                "\n  • Guías paso a paso para profesores" .
                "\n  • Consultas a BD en tiempo real para admins" .
                "\n  • Navegación inteligente (verbos: ir, llevar, mostrar)" .
                
                "\n\n🎭 ROLES Y PERMISOS DETALLADOS:" .
                "\n\n👨‍🏫 PROFESOR:" .
                "\n  ✅ Reservar aulas AIP (mínimo 1 día anticipación, requiere SMS)" .
                "\n  ✅ Solicitar préstamos equipos en aulas REGULARES (requiere SMS)" .
                "\n  ✅ Ver historial personal (solo sus registros)" .
                "\n  ✅ Cancelar reservas (solo mismo día)" .
                "\n  ✅ Cambiar contraseña (requiere SMS)" .
                "\n  ✅ Configurar perfil (foto, bio)" .
                "\n  ✅ Consultar Tommibot" .
                "\n  ❌ NO puede ver otros usuarios" .
                "\n  ❌ NO puede gestionar recursos" .
                "\n  ❌ NO puede registrar devoluciones" .
                
                "\n🔧 ENCARGADO:" .
                "\n  ✅ Ver historial global (todos los usuarios)" .
                "\n  ✅ Registrar devoluciones (inspección física obligatoria)" .
                "\n  ✅ Validar estados: OK, Dañado, Falta accesorio" .
                "\n  ✅ Buscar préstamos por profesor/equipo/aula" .
                "\n  ✅ Cambiar contraseña (SIN SMS)" .
                "\n  ✅ Configurar perfil" .
                "\n  ❌ NO puede crear usuarios" .
                "\n  ❌ NO puede gestionar equipos/aulas" .
                "\n  ❌ NO puede generar reportes filtrados" .
                
                "\n👑 ADMINISTRADOR (TU ROL):" .
                "\n  ✅ TODOS los permisos de Profesor y Encargado" .
                "\n  ✅ Gestionar usuarios: crear, editar, eliminar, cambiar roles" .
                "\n  ✅ Gestionar aulas: crear AIP/REGULAR, editar, activar/desactivar" .
                "\n  ✅ Gestionar equipos: stock, stock máximo, tipos" .
                "\n  ✅ Ver historial global completo" .
                "\n  ✅ Reportes filtrados personalizados (PDF)" .
                "\n  ✅ Estadísticas con gráficos (últimos 30 días)" .
                "\n  ✅ Mantenimiento mensual automatizado" .
                "\n  ✅ Backups manuales y automáticos" .
                "\n  ✅ Configuración del sistema" .
                "\n  ✅ Sin restricciones de SMS (acceso directo)" .
                
                "\n\n🔄 FLUJOS DE TRABAJO PRINCIPALES:" .
                "\n\n📝 FLUJO: Profesor Reserva Aula AIP" .
                "\n  1. Login → Dashboard Profesor" .
                "\n  2. Click 'Reservar Aula'" .
                "\n  3. Sistema envía SMS automático (6 dígitos, 10 min)" .
                "\n  4. Ingresa código verificación" .
                "\n  5. Selecciona fecha (mínimo mañana)" .
                "\n  6. Elige aula AIP disponible" .
                "\n  7. Selecciona horas (6:00-18:00)" .
                "\n  8. Confirma → Notificación enviada" .
                "\n  9. Puede cancelar solo hoy" .
                
                "\n💻 FLUJO: Profesor Solicita Préstamo" .
                "\n  1. Click 'Préstamo Equipos'" .
                "\n  2. SMS automático → Verifica código" .
                "\n  3. Selecciona fecha (mínimo mañana)" .
                "\n  4. Elige aula REGULAR" .
                "\n  5. Selecciona equipos (valida stock)" .
                "\n  6. Define horas uso" .
                "\n  7. Confirma → Stock disminuye automáticamente" .
                "\n  8. Notificación enviada (individual o pack)" .
                
                "\n📦 FLUJO: Encargado Registra Devolución" .
                "\n  1. Login → 'Registrar Devolución'" .
                "\n  2. Busca préstamo (profesor/equipo/aula)" .
                "\n  3. Filtra por estado 'Prestado'" .
                "\n  4. Inspecciona físicamente equipo(s)" .
                "\n  5. Click 'Confirmar devolución'" .
                "\n  6. Selecciona estado (OK/Dañado/Falta accesorio)" .
                "\n  7. Si NO es OK: agrega comentario obligatorio" .
                "\n  8. Confirma → Stock aumenta automáticamente" .
                "\n  9. Notificación a profesor y admins" .
                
                "\n🔧 FLUJO: Admin Ejecuta Mantenimiento" .
                "\n  1. Dashboard → 'Configuración'" .
                "\n  2. Sección 'Mantenimiento Sistema'" .
                "\n  3. Verifica que pasaron 30+ días" .
                "\n  4. Click 'Ejecutar Mantenimiento'" .
                "\n  5. Confirma en SweetAlert" .
                "\n  6. Sistema ejecuta:" .
                "\n     - OPTIMIZE TABLE (12 tablas)" .
                "\n     - DELETE notificaciones >3 meses" .
                "\n     - Backup automático .sql" .
                "\n     - Limpieza sesiones /tmp/" .
                "\n     - Clear cache estadísticas" .
                "\n  7. Registro en mantenimiento_sistema" .
                "\n  8. Mensaje éxito con resumen" .
                
                "\n\n🎨 CARACTERÍSTICAS DE INTERFAZ:" .
                "\n  • Diseño responsivo (móvil y desktop)" .
                "\n  • Navbar unificada con botón 'Atrás' inteligente" .
                "\n  • Offcanvas móvil con animaciones" .
                "\n  • SweetAlert2 para confirmaciones" .
                "\n  • Font Awesome 6.5.0 para iconos" .
                "\n  • Bootstrap 5.3.3 con tema personalizado" .
                "\n  • Chart.js para gráficos estadísticos" .
                "\n  • DomPDF para exportar reportes" .
                "\n  • Buscador avanzado con filtros combinables" .
                "\n  • Badges de estado (Activo, Cancelado, Devuelto)" .
                "\n  • Notificaciones in-app con contador" .
                
                "\n\n💡 REGLAS DE NEGOCIO CRÍTICAS:" .
                "\n  1. Separación estricta: AIP=Reservas, REGULAR=Préstamos" .
                "\n  2. Anticipación obligatoria: mínimo 1 día (NO mismo día)" .
                "\n  3. SMS automático solo para Profesores en: reservas, préstamos, cambio clave" .
                "\n  4. Cancelación de reservas: solo mismo día de creación" .
                "\n  5. Devoluciones: solo Encargado con inspección física" .
                "\n  6. Stock automático: disminuye al prestar, aumenta al devolver" .
                "\n  7. Mantenimiento: máximo 1 vez cada 30 días" .
                "\n  8. Notificaciones agrupadas: varios equipos = 1 pack" .
                "\n  9. Verificación email: token único al registrarse" .
                "\n  10. Backups: manuales + automáticos en mantenimientos" .
                
                "\n\n📊 REPORTES Y ESTADÍSTICAS:" .
                "\n  • Historial PDF semanal (todos los roles)" .
                "\n  • Reportes filtrados PDF (solo Admin): fecha, profesor, tipo, estado" .
                "\n  • Gráficos uso aulas: barras por aula (30 días)" .
                "\n  • Gráficos préstamos: barras por equipo (30 días)" .
                "\n  • Exportación: botón único con formato profesional" .
                "\n  • Metadata: incluye filtros aplicados en PDF" .
                
                "\n\n💡 CONSULTAS DISPONIBLES:" .
                "\nPuedes preguntarme:" .
                "\n  • 'Cuántos usuarios hay de cada tipo' → Desglose detallado" .
                "\n  • 'Cuántos equipos están disponibles' → Stock actual vs máximo" .
                "\n  • 'Cómo funciona el sistema de reservas' → Explicación completa" .
                "\n  • 'Explica la diferencia entre AIP y REGULAR' → Separación de aulas" .
                "\n  • 'Cómo crear un usuario' → Guía paso a paso" .
                "\n  • 'Cómo funciona el SMS automático' → Proceso técnico" .
                "\n  • 'Qué hace el mantenimiento mensual' → Tareas detalladas" .
                "\n  • 'Cómo gestionar equipos' → CRUD completo" .
                "\n  • 'Dame información completa del sistema' → Overview total" .
                "\n  • 'Explica los roles y permisos' → Matriz de permisos" .
                "\n  • 'Cómo funcionan las notificaciones' → Sistema in-app" .
                "\n  • 'Qué tablas hay en la BD' → Esquema completo" .
                "\n  • 'Cómo se registran las devoluciones' → Flujo completo" .
                "\n  • 'Cuáles son las reglas de negocio' → 10 reglas críticas" .
                
                "\n\n🚀 NAVEGACIÓN INTELIGENTE:" .
                "\nPuedo llevarte directamente a cualquier sección administrativa:" .
                "\n  • 'Ir a usuarios' / 'Gestionar usuarios' → Panel de usuarios" .
                "\n  • 'Ir a equipos' / 'Ver inventario' → Gestión de equipos" .
                "\n  • 'Ir a aulas' / 'Gestionar aulas' → Administrar aulas" .
                "\n  • 'Ir a reportes' / 'Ver estadísticas' → Reportes y filtros" .
                "\n  • 'Ir a historial' / 'Ver todo' → Historial global" .
                "\n  • 'Ir a configuración' / 'Ver perfil' → Configuración" .
                "\n  • 'Ir a mantenimiento' → Mantenimiento del sistema" .
                
                "\n🔓 Tienes acceso completo sin restricciones SMS. Soy tu asistente experto del sistema. ¡Pregúntame lo que necesites!",
                
            'Encargado' => "\n\n🔧 ROL ACTUAL: ENCARGADO DE EQUIPOS" .
                "\n📊 ESTADÍSTICAS DE EQUIPOS:" .
                "\n  💻 Inventario:" .
                "\n    - Total equipos: {$stats['total_equipos']}" .
                "\n    - Disponibles: {$stats['equipos_disponibles']}" .
                "\n    - Prestados actualmente: {$stats['equipos_prestados']}" .
                
                "\n  📦 Préstamos:" .
                "\n    - Pendientes de devolución: {$stats['prestamos_pendientes_global']}" .
                "\n    - Devueltos hoy: {$stats['devoluciones_hoy']}" .
                "\n    - Completados (histórico): {$stats['prestamos_completados_global']}" .
                
                "\n  ⚠️ Alertas:" .
                "\n    - Préstamos vencidos: {$stats['prestamos_vencidos']}" .
                
                "\n\n🔍 TU RESPONSABILIDAD PRINCIPAL:" .
                "\nRegistrar devoluciones tras INSPECCIÓN FÍSICA del equipo:" .
                "\n  1. Verificar el estado del equipo (OK, Dañado, Falta accesorio)" .
                "\n  2. Registrar observaciones si hay problemas" .
                "\n  3. El sistema actualiza automáticamente el stock al confirmar" .
                
                "\n\n💬 CONSULTAS DISPONIBLES:" .
                "\nPuedes preguntarme:" .
                "\n  • 'Cómo registrar una devolución' → Guía paso a paso" .
                "\n  • 'Qué hacer si un equipo está dañado' → Procedimiento" .
                "\n  • 'Cómo buscar un préstamo específico' → Uso de filtros" .
                "\n  • 'Cuántos préstamos hay pendientes' → Listado actual" .
                
                "\n\n🚀 NAVEGACIÓN INTELIGENTE:" .
                "\nPuedo llevarte directamente a:" .
                "\n  • 'Ir a devoluciones' / 'Registrar devolución' → Panel de devoluciones" .
                "\n  • 'Ir a historial' / 'Ver préstamos' → Historial global" .
                "\n  • 'Ir a configuración' / 'Ver perfil' → Configuración" .
                "\n  • 'Ir a notificaciones' / 'Ver alertas' → Notificaciones" .
                
                "\n🔓 Acceso directo sin verificación SMS. ¡Tu rol es clave para el control de inventario!"
        ];
        
        return $contexts[$userRole] ?? $contexts['Profesor'];
    }
    
    // ========================================
    // NUEVAS CONSULTAS AVANZADAS PARA ADMIN
    // ========================================
    
    /**
     * Obtiene préstamos vencidos (pasaron su hora de devolución)
     */
    private function getPrestamosVencidos() {
        try {
            $now = date('Y-m-d H:i:s');
            $today = date('Y-m-d');
            
            $sql = "SELECT p.id_prestamo, u.nombre as usuario, e.nombre_equipo, a.nombre_aula, 
                           p.fecha_prestamo, p.hora_inicio, p.hora_fin,
                           CONCAT(p.fecha_prestamo, ' ', p.hora_fin) as fecha_limite
                    FROM prestamos p
                    INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    LEFT JOIN aulas a ON p.id_aula = a.id_aula
                    WHERE p.estado = 'Prestado'
                    AND CONCAT(p.fecha_prestamo, ' ', p.hora_fin) < ?
                    ORDER BY p.fecha_prestamo ASC, p.hora_fin ASC
                    LIMIT 20";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$now]);
            $vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($vencidos)) {
                return "✅ **No hay préstamos vencidos en este momento.**\n\n" .
                       "Todos los equipos prestados están dentro de su horario o ya fueron devueltos.\n\n" .
                       "💡 Recuerda revisar periódicamente para contactar a profesores con retrasos.";
            }

            $response = "### 🔴 Préstamos Vencidos (" . count($vencidos) . ")\n\n";
            $response .= "_Estos equipos debieron ser devueltos ya:_\n\n";
            
            foreach ($vencidos as $p) {
                $fecha_limite = new DateTime($p['fecha_limite']);
                $ahora = new DateTime($now);
                $diff = $ahora->diff($fecha_limite);
                
                $retraso = "";
                if ($diff->days > 0) {
                    $retraso = $diff->days . " día(s)";
                } elseif ($diff->h > 0) {
                    $retraso = $diff->h . " hora(s)";
                } else {
                    $retraso = $diff->i . " minuto(s)";
                }
                
                $response .= "**⚠️ Préstamo #{$p['id_prestamo']}** - Retraso: {$retraso}\n";
                $response .= "- Usuario: {$p['usuario']}\n";
                $response .= "- Equipo: {$p['nombre_equipo']}\n";
                $response .= "- Aula: {$p['nombre_aula']}\n";
                $response .= "- Debió devolverse: {$p['fecha_prestamo']} a las {$p['hora_fin']}\n\n";
            }

            $response .= "---\n\n";
            $response .= "💡 **Acciones recomendadas:**\n";
            $response .= "- Contacta a los profesores para que devuelvan los equipos\n";
            $response .= "- Verifica si ya los devolvieron físicamente y falta registro\n";
            $response .= "- Notifica al Administrador si hay casos persistentes\n";

            return $response;
        } catch (Exception $e) {
            error_log("Error en getPrestamosVencidos: " . $e->getMessage());
            return "❌ Error al obtener los préstamos vencidos. Por favor, intenta nuevamente.";
        }
    }
    
    /**
     * Obtiene préstamos activos/pendientes
     */
    private function getPrestamosActivos() {
        try {
            $sql = "SELECT p.id_prestamo, u.nombre as usuario, e.nombre_equipo, a.nombre_aula, 
                           p.fecha_prestamo, p.hora_inicio, p.hora_fin
                    FROM prestamos p
                    INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    LEFT JOIN aulas a ON p.id_aula = a.id_aula
                    WHERE p.estado = 'Prestado'
                    ORDER BY p.fecha_prestamo DESC
                    LIMIT 10";
            
            $stmt = $this->db->query($sql);
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($prestamos)) {
                return "✅ No hay préstamos activos en este momento. Todos los equipos han sido devueltos.";
            }

            $response = "### 📦 Préstamos Activos\n\n";
            foreach ($prestamos as $p) {
                $response .= "**Préstamo #{$p['id_prestamo']}**\n";
                $response .= "- Usuario: {$p['usuario']}\n";
                $response .= "- Equipo: {$p['nombre_equipo']}\n";
                $response .= "- Aula: {$p['nombre_aula']}\n";
                $response .= "- Fecha: {$p['fecha_prestamo']} ({$p['hora_inicio']} - {$p['hora_fin']})\n\n";
            }

            if (count($prestamos) >= 10) {
                $response .= "_Mostrando los primeros 10 préstamos._";
            }

            return $response;
        } catch (Exception $e) {
            error_log("Error en getPrestamosActivos: " . $e->getMessage());
            return "Error al obtener los préstamos activos.";
        }
    }
    
    /**
     * Obtiene reservas activas
     */
    private function getReservasActivas() {
        try {
            $sql = "SELECT r.id_reserva, u.nombre as usuario, a.nombre_aula, 
                           r.fecha, r.hora_inicio, r.hora_fin
                    FROM reservas r
                    INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                    LEFT JOIN aulas a ON r.id_aula = a.id_aula
                    WHERE r.fecha >= CURDATE()
                    ORDER BY r.fecha ASC, r.hora_inicio ASC
                    LIMIT 10";
            
            $stmt = $this->db->query($sql);
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($reservas)) {
                return "📅 No hay reservas activas o futuras. Las aulas AIP están disponibles.";
            }

            $response = "### 📅 Reservas Activas\n\n";
            foreach ($reservas as $r) {
                $response .= "**Reserva #{$r['id_reserva']}**\n";
                $response .= "- Usuario: {$r['usuario']}\n";
                $response .= "- Aula: {$r['nombre_aula']}\n";
                $response .= "- Fecha: {$r['fecha']} ({$r['hora_inicio']} - {$r['hora_fin']})\n\n";
            }

            if (count($reservas) >= 10) {
                $response .= "_Mostrando las próximas 10 reservas._";
            }

            return $response;
        } catch (Exception $e) {
            error_log("Error en getReservasActivas: " . $e->getMessage());
            return "Error al obtener las reservas activas.";
        }
    }
    
    /**
     * Obtiene usuarios sin verificar
     */
    private function getUsuariosSinVerificar() {
        try {
            $sql = "SELECT nombre, correo, tipo_usuario, 
                           DATEDIFF(CURDATE(), DATE(token_expira)) as dias_sin_verificar
                    FROM usuarios 
                    WHERE verificado = 0 AND activo = 1
                    ORDER BY token_expira DESC
                    LIMIT 10";
            
            $stmt = $this->db->query($sql);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($usuarios)) {
                return "✅ ¡Excelente! Todos los usuarios activos han verificado su correo electrónico.";
            }

            $response = "### ⏳ Usuarios Sin Verificar\n\n";
            $response .= "Los siguientes usuarios NO han verificado su correo:\n\n";
            
            foreach ($usuarios as $u) {
                $response .= "- **{$u['nombre']}** ({$u['tipo_usuario']})\n";
                $response .= "  📧 {$u['correo']}\n";
                if ($u['tipo_usuario'] === 'Profesor') {
                    $response .= "  ⚠️ NO puede usar el sistema hasta verificar\n";
                }
                $response .= "\n";
            }

            $response .= "\n💡 **Recomendación:** Contacta a estos usuarios para que revisen su correo y verifiquen su cuenta.";

            return $response;
        } catch (Exception $e) {
            error_log("Error en getUsuariosSinVerificar: " . $e->getMessage());
            return "Error al obtener usuarios sin verificar.";
        }
    }
    
    /**
     * Obtiene equipos sin stock
     */
    private function getEquiposSinStock() {
        try {
            $sql = "SELECT nombre_equipo, tipo_equipo, stock_maximo
                    FROM equipos 
                    WHERE stock = 0 AND activo = 1
                    ORDER BY tipo_equipo, nombre_equipo";
            
            $stmt = $this->db->query($sql);
            $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($equipos)) {
                return "✅ ¡Perfecto! Todos los equipos activos tienen stock disponible.";
            }

            $response = "### 📦 Equipos Sin Stock\n\n";
            $response .= "Los siguientes equipos están AGOTADOS (todos prestados):\n\n";
            
            $currentType = '';
            foreach ($equipos as $eq) {
                if ($currentType !== $eq['tipo_equipo']) {
                    $currentType = $eq['tipo_equipo'];
                    $response .= "\n**{$eq['tipo_equipo']}:**\n";
                }
                $response .= "- {$eq['nombre_equipo']} ❌\n";
                $response .= "  Capacidad total: {$eq['stock_maximo']} unidades\n";
            }

            $response .= "\n💡 **Recomendación:** Espera a que se devuelvan los equipos prestados o considera adquirir más unidades.";

            return $response;
        } catch (Exception $e) {
            error_log("Error en getEquiposSinStock: " . $e->getMessage());
            return "Error al obtener equipos sin stock.";
        }
    }
    
    /**
     * Obtiene información sobre los roles del sistema
     */
    private function getRolesInfo() {
        $response = "### 👥 Roles del Sistema\n\n";
        $response .= "El sistema gestiona **3 tipos de usuarios** (roles):\n\n";
        
        $response .= "**1️⃣ ADMINISTRADOR** 🔑\n";
        $response .= "- Gestiona usuarios, equipos y aulas\n";
        $response .= "- Ve el historial global de todos\n";
        $response .= "- Exporta reportes\n";
        $response .= "- ⚠️ REQUIERE verificación de correo (link por email)\n";
        $response .= "- NO requiere verificación SMS\n";
        $response .= "- NO puede hacer reservas ni préstamos\n\n";
        
        $response .= "**2️⃣ PROFESOR** 👨‍🏫\n";
        $response .= "- Reserva aulas AIP\n";
        $response .= "- Solicita préstamos de equipos\n";
        $response .= "- Ve su propio historial\n";
        $response .= "- ⚠️ REQUIERE verificación de correo (link por email)\n";
        $response .= "- ⚠️ REQUIERE verificación SMS para acciones críticas\n\n";
        
        $response .= "**3️⃣ ENCARGADO** 📦\n";
        $response .= "- Registra devoluciones de equipos\n";
        $response .= "- Inspecciona estado de equipos\n";
        $response .= "- Ve préstamos pendientes\n";
        $response .= "- ⚠️ REQUIERE verificación de correo (link por email)\n";
        $response .= "- NO requiere verificación SMS\n";
        $response .= "- NO puede hacer reservas ni préstamos\n\n";
        
        // Obtener estadísticas reales
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'Administrador' AND activo = 1");
            $admins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'Profesor' AND activo = 1");
            $profesores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'Encargado' AND activo = 1");
            $encargados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $response .= "📊 **Distribución actual:**\n";
            $response .= "- Administradores: **{$admins}**\n";
            $response .= "- Profesores: **{$profesores}**\n";
            $response .= "- Encargados: **{$encargados}**\n";
        } catch (Exception $e) {
            // Silenciar error
        }
        
        return $response;
    }
    
    /**
     * Guía rápida: Cómo registrar un nuevo usuario
     */
    private function getGuiaRapidaRegistrarUsuario() {
        $response = "### ➕ **Cómo Registrar un Nuevo Usuario**\n\n";
        
        $response .= "**Pasos rápidos:**\n\n";
        $response .= "1️⃣ Ve a **Gestión de Usuarios** desde el menú lateral\n\n";
        $response .= "2️⃣ Haz clic en el botón **+ Nuevo Usuario**\n\n";
        $response .= "3️⃣ Completa el formulario:\n";
        $response .= "```\n";
        $response .= "• Nombre completo\n";
        $response .= "• Correo (único en el sistema)\n";
        $response .= "• Teléfono (+51XXXXXXXXX)\n";
        $response .= "• Tipo de usuario:\n";
        $response .= "  - Administrador (acceso total)\n";
        $response .= "  - Profesor (reservas y préstamos)\n";
        $response .= "  - Encargado (devoluciones)\n";
        $response .= "• Contraseña (mínimo 8 caracteres)\n";
        $response .= "```\n\n";
        $response .= "4️⃣ Haz clic en **Crear Usuario**\n\n";
        $response .= "5️⃣ El sistema enviará automáticamente un correo de verificación\n\n";
        
        $response .= "---\n\n";
        $response .= "**📌 Importante:**\n";
        $response .= "- El correo debe ser único (no puede estar registrado)\n";
        $response .= "- Los Profesores DEBEN verificar su correo para usar el sistema\n";
        $response .= "- Los Admin y Encargado pueden usar el sistema sin verificar\n\n";
        
        $response .= "**💡 ¿Necesitas más detalles?**\n";
        $response .= "Escribe: _\"¿Cómo gestiono usuarios?\"_ para la guía completa.";
        
        return $response;
    }
}
