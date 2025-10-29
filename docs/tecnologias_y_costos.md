# Tecnologías y Costos del Sistema AIP

## Tecnologías utilizadas

- Lenguajes y entorno
  - PHP 8.x (app, controladores, vistas, sesiones)
  - JavaScript (ES6+) en frontend
  - HTML5/CSS3
  - Entorno de desarrollo local: XAMPP (Windows)
  - Dependencias PHP: Composer

- Base de datos
  - MySQL/MariaDB vía PDO
  - Config: `app/config/conexion.php` (UTF-8, errores por excepción, conexión persistente)
  - Scripts: `app/bd/script.sql`, `app/bd/verification_codes.sql`

- Librerías/SDKs (Composer)
  - twilio/sdk (SMS y WhatsApp)
  - phpmailer/phpmailer (correo SMTP, adjuntos)
  - dompdf/dompdf (PDF desde HTML/CSS)

- Frontend (CDN y assets)
  - Bootstrap 5.3.3 (UI)
  - SweetAlert2 (diálogos)
  - Chart.js 4.4.1 (gráficos)
  - Font Awesome 6.5 (iconos)
  - CSS propio: `Public/css/brand.css`, `admin_mobile.css`, `historial_global.css`, `historial.css`
  - JS propio: `Public/js/*.js` (Reservas, Equipos, Usuarios, Aulas, Reportes, Estadísticas, etc.)

- Servicios internos
  - OTP por SMS: `app/lib/SmsService.php`, `app/lib/VerificationService.php`, config en `app/config/twilio.php`
  - Correo: `app/lib/Mailer.php` (SMTP con fallback a `mail()`)
  - PDFs institucionales: `app/view/exportar_pdf.php`, `app/view/exportar_pdf_equipos.php`

## Costos (mensual y anual)

Notas generales:
- Los precios de Twilio pueden cambiar sin aviso y pueden aplicar tarifas adicionales de operadores (carrier fees).
- Los SMS se cobran por segmento (mensajes largos pueden dividirse en 2+ segmentos).
- WhatsApp tiene dos componentes: (1) fee de Twilio por mensaje y (2) la tarifa de Meta por conversación (passthrough), que depende de categoría y país.

Fuentes oficiales consultadas:
- Twilio SMS Pricing (Perú): https://www.twilio.com/en-us/sms/pricing/pe
- Twilio WhatsApp Pricing: https://www.twilio.com/en-us/whatsapp/pricing
- Twilio Messaging API Pricing details: https://www.twilio.com/docs/messaging/api/pricing

### 1) SMS (Perú)

- Costo por SMS saliente (outbound) con número internacional: USD $0.2476 por segmento.
- Cargo por mensajes fallidos procesados: USD $0.001 por mensaje con estado "Failed" (solo si falla).

Ejemplos de costo mensual (asumiendo 1 segmento por SMS):
- 100 SMS/mes ≈ 100 × $0.2476 = $24.76 / mes
- 500 SMS/mes ≈ 500 × $0.2476 = $123.80 / mes
- 1,000 SMS/mes ≈ 1,000 × $0.2476 = $247.60 / mes

Estimación anual (12 meses):
- 100 SMS/mes ≈ $24.76 × 12 = $297.12 / año
- 500 SMS/mes ≈ $123.80 × 12 = $1,485.60 / año
- 1,000 SMS/mes ≈ $247.60 × 12 = $2,971.20 / año

(Agregar carrier fees si aplican y multiplicar por número de segmentos cuando el texto supere el límite de un segmento.)

### 2) Número telefónico de Twilio

- Alquiler mensual (International Prefix): USD $1.15 / mes
- Estimación anual: $1.15 × 12 = $13.80 / año

### 3) WhatsApp Business a través de Twilio

- Fee de Twilio: USD $0.005 por mensaje (entrante o saliente).
- Además, se cobra la tarifa de Meta por conversación (24 h) según categoría (utility, authentication, marketing, service) y país. Twilio pasa este coste sin margen adicional.
- Para un cálculo exacto en Perú se debe consultar la rate card de Meta vigente para cada categoría.

Ejemplo orientativo (solo componente Twilio):
- 1,000 mensajes/mes → 1,000 × $0.005 = $5.00 / mes (solo Twilio)
- Anual (solo Twilio): $5.00 × 12 = $60.00 / año
- Sumar el costo por conversación de Meta según la categoría y volumen real.

### 4) Correo (PHPMailer)

- PHPMailer es gratuito. El costo depende del proveedor SMTP configurado (no se fija en el repo).
- Ejemplos típicos (referenciales, verificar proveedor elegido):
  - Amazon SES: ~USD $0.10 por 1,000 emails salientes (más cargos de datos/adjuntos) → $0.0001 por email.
  - Otros (SendGrid/Mailgun/Outlook/Gmail Workspace) dependen del plan contratado.

### 5) Generación de PDF (dompdf)

- dompdf es gratuito (open source). Sin costo mensual/anual por licencia.

### 6) Frontend (Bootstrap, SweetAlert2, Chart.js, Font Awesome)

- Uso de CDN y licencias open source (sin costo)
- Font Awesome en este proyecto usa versión gratuita por CDN (sin coste). La versión Pro es de pago (no utilizada aquí).

## Resumen por periodicidad

- Mensual
  - Twilio: SMS enviados (variable por volumen y segmentos)
  - Twilio: Número telefónico International Prefix ($1.15/mes)
  - Twilio: WhatsApp – fee $0.005 por mensaje + tarifa Meta por conversación (variable)
  - SMTP (si usas un proveedor de pago): según plan del proveedor

- Anual
  - Twilio: Número telefónico ≈ $13.80/año por cada número
  - SMS y WhatsApp: 12 × (consumo mensual real)
  - SMTP: 12 × (plan mensual del proveedor), si aplica

## Fórmulas útiles

- Costo SMS mensual ≈ (SMS_enviados_mes × $0.2476 × segmentos_promedio) + (Failed_msgs × $0.001) + (carrier_fees si aplican)
- Costo SMS anual ≈ 12 × Costo_SMS_mensual
- Costo WhatsApp (solo Twilio) mensual ≈ (mensajes_mes × $0.005)
- Costo WhatsApp total ≈ Costo Twilio + (conversaciones_mes × tarifa_Meta_categoria)
- Costo número Twilio anual ≈ $1.15 × 12 × cantidad_de_números

## Observaciones

- Los precios de Twilio pueden variar por operador y país; valida periódicamente las páginas oficiales.
- Para WhatsApp, consulta la rate card de Meta vigente para Perú y categoría de conversación.
- Si el sistema envía códigos OTP por SMS, el volumen crece con el número de acciones verificadas (reservas, préstamos, cambios de contraseña); ajusta estimaciones según tu uso.
