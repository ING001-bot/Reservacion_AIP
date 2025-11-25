# 🧪 Guía de Prueba Rápida - Sistema AIP v2.0

## 🎯 Objetivo
Verificar que todas las nuevas funcionalidades están operando correctamente.

---

## ⏱ Tiempo Estimado
**15-20 minutos** para completar todas las pruebas.

---

## 📋 CHECKLIST DE PRUEBAS

### ✅ 1. ACCESO Y SEGURIDAD (2 min)

#### 1.1 Login como Administrador
- [ ] Iniciar sesión con usuario administrador
- [ ] Verificar que el dashboard carga correctamente
- [ ] Ver que aparece el menú "Configuración"

**Resultado esperado**: ✅ Acceso exitoso al panel de administrador

---

### ✅ 2. ESTADÍSTICAS DEL SISTEMA (3 min)

#### 2.1 Ver Estadísticas
1. Click en **"Configuración"** en el menú lateral
2. Scroll hasta la sección **"📊 Estadísticas del Sistema"**
3. Verificar que aparecen las tarjetas:
   - [ ] Total Usuarios
   - [ ] Administradores
   - [ ] Encargados
   - [ ] Profesores
   - [ ] Verificados (Email)
   - [ ] Teléfono Verificado
   - [ ] Tasa Verificación

#### 2.2 Validar Números
- [ ] Los números coinciden con usuarios reales
- [ ] La tasa de verificación es un porcentaje (0-100%)
- [ ] Las tarjetas tienen iconos y colores

**Resultado esperado**: ✅ Todas las tarjetas muestran datos correctos

---

### ✅ 3. SISTEMA DE BACKUP (5 min)

#### 3.1 Crear Backup
1. En Configuración, ir a **"💾 Gestión de Backups"**
2. Click en **"Crear Backup Completo"**
3. Esperar mensaje de confirmación
4. Verificar que aparece en la lista

**Resultado esperado**: ✅ Mensaje "✅ Backup creado exitosamente"

#### 3.2 Descargar Backup
1. Localizar el backup recién creado en la tabla
2. Click en botón **"⬇ Descargar"**
3. Verificar que se descarga archivo .zip

**Resultado esperado**: ✅ Archivo descargado (ej: `backup_completo_2025-11-25_14-30-00.sql.zip`)

#### 3.3 Verificar Contenido
1. Abrir el archivo .zip descargado
2. Extraer el archivo .sql
3. Abrir con editor de texto
4. Verificar que contiene:
   - [ ] Comentarios de fecha/hora
   - [ ] Comandos `CREATE TABLE`
   - [ ] Comandos `INSERT INTO`
   - [ ] Tablas: usuarios, equipos, aulas, etc.

**Resultado esperado**: ✅ Archivo SQL válido con datos completos

---

### ✅ 4. PROTECCIÓN DE ADMINISTRADOR (3 min)

#### 4.1 Intentar Eliminar Último Admin
**⚠️ SOLO SI TIENES UN SOLO ADMINISTRADOR:**

1. Ir a **"Gestión de Roles"**
2. Intentar cambiar el rol del admin a "Profesor"
3. Verificar mensaje de error

**Resultado esperado**: ⚠️ "No se puede eliminar/cambiar el último administrador"

#### 4.2 Crear Segundo Admin (Opcional)
1. Crear nuevo usuario tipo Profesor
2. En Configuración > Gestión de Roles
3. Cambiar rol a "Administrador"
4. Ahora SÍ debería permitir modificar el primer admin

**Resultado esperado**: ✅ Se puede cambiar roles cuando hay 2+ admins

---

### ✅ 5. GESTIÓN DE USUARIOS (4 min)

#### 5.1 Verificar Filtrado en Tabla
1. Ir a **"Gestionar Usuarios"** (menú lateral)
2. Verificar la tabla de usuarios:
   - [ ] NO aparecen usuarios tipo "Administrador"
   - [ ] Solo aparecen: Profesores y Encargados

**Resultado esperado**: ✅ Administradores ocultos de la tabla

#### 5.2 Formulario de Registro
1. En "Gestionar Usuarios", ver formulario de registro
2. Click en el select de "Tipo de Usuario"
3. Verificar opciones disponibles:
   - [ ] ✅ Profesor
   - [ ] ✅ Encargado
   - [ ] ❌ Administrador (NO debe estar)

**Resultado esperado**: ✅ Solo 2 opciones (Profesor/Encargado)

#### 5.3 Mensaje Informativo
- [ ] Debe aparecer texto: "Los administradores se gestionan desde **Configuración**"

**Resultado esperado**: ✅ Mensaje visible debajo del select

---

### ✅ 6. EDICIÓN DE USUARIOS (3 min)

#### 6.1 Editar Usuario Existente
1. Click en botón **"✏️ Editar"** de cualquier usuario
2. Verificar el modal que se abre
3. Observar el campo "Tipo de Usuario":
   - [ ] El select está **deshabilitado** (grisado)
   - [ ] Muestra el rol actual
   - [ ] No se puede cambiar

**Resultado esperado**: ✅ Campo deshabilitado con mensaje informativo

#### 6.2 Mensaje en Modal
- [ ] Debe aparecer: "Para cambiar el rol del usuario, use el módulo de **Configuración**"
- [ ] Con icono de información (ℹ️)

**Resultado esperado**: ✅ Mensaje claro y visible

---

### ✅ 7. CAMBIO DE ROLES (2 min)

#### 7.1 Desde Configuración
1. Ir a Configuración
2. En la sección de acciones, click en **"Cambiar Roles"**
3. Abrir modal
4. Verificar:
   - [ ] Lista de TODOS los usuarios (incluyendo admins)
   - [ ] Select con 3 opciones: Profesor, Encargado, Administrador

**Resultado esperado**: ✅ Modal funcional con todas las opciones

#### 7.2 Ejecutar Cambio
1. Seleccionar un usuario tipo Profesor
2. Cambiar a "Encargado"
3. Guardar
4. Verificar mensaje de confirmación

**Resultado esperado**: ✅ "Rol actualizado correctamente"

---

### ✅ 8. RESPONSIVE DESIGN (2 min)

#### 8.1 Probar en Diferentes Tamaños
1. Abrir DevTools (F12)
2. Activar modo responsive
3. Probar resoluciones:
   - [ ] 375x667 (iPhone SE)
   - [ ] 768x1024 (iPad)
   - [ ] 1366x768 (Laptop)
   - [ ] 1920x1080 (Desktop)

**Resultado esperado**: ✅ Diseño adaptable sin scroll horizontal

#### 8.2 Verificar Elementos
- [ ] Tarjetas se apilan en móvil
- [ ] Tablas tienen scroll horizontal
- [ ] Botones son táctiles (> 44px)
- [ ] Textos legibles

**Resultado esperado**: ✅ Usable en todos los dispositivos

---

## 🎨 PRUEBAS VISUALES

### Colores Institucionales
- [ ] Azul principal: `#1e6bd6`
- [ ] Verde acento: `#16a34a`
- [ ] Degradados visibles
- [ ] Sombras suaves

### Iconos
- [ ] Emojis en tarjetas de estadísticas
- [ ] Bootstrap Icons en botones
- [ ] Tamaños consistentes

### Animaciones
- [ ] Hover en tarjetas (sube 2px)
- [ ] Spinner al crear backup
- [ ] Transiciones suaves

---

## 📊 TABLA DE RESULTADOS

| Prueba | Estado | Comentarios |
|--------|--------|-------------|
| 1. Acceso Admin | ⬜ | |
| 2. Estadísticas | ⬜ | |
| 3. Backup Completo | ⬜ | |
| 4. Protección Admin | ⬜ | |
| 5. Filtrado Usuarios | ⬜ | |
| 6. Edición Usuario | ⬜ | |
| 7. Cambio Roles | ⬜ | |
| 8. Responsive | ⬜ | |

**Marcar con**: ✅ (exitoso) | ⚠️ (parcial) | ❌ (fallido)

---

## 🐛 ERRORES COMUNES Y SOLUCIONES

### Error: "No se puede conectar a MySQL"
**Solución**: Verificar que XAMPP esté ejecutando MySQL

### Error: "Access denied"
**Solución**: Verificar que estás logueado como Administrador

### Error: "Cannot write to backups directory"
**Solución**: 
```bash
# Dar permisos de escritura
chmod 755 backups/database/
```

### Error: "Bootstrap Icons no cargan"
**Solución**: Verificar conexión a internet (CDN)

---

## ✅ CRITERIOS DE ACEPTACIÓN

Para considerar las pruebas **EXITOSAS**, se deben cumplir:

1. ✅ Al menos **7/8 pruebas** completadas exitosamente
2. ✅ Backup se crea y descarga correctamente
3. ✅ Estadísticas muestran datos reales
4. ✅ Protección de administrador funciona
5. ✅ Usuarios filtrados por rol
6. ✅ Responsive en móvil y desktop
7. ✅ Sin errores de JavaScript en consola (F12)
8. ✅ Sin errores PHP visibles

---

## 📸 CAPTURAS RECOMENDADAS

Tomar capturas de:
1. Dashboard de estadísticas
2. Lista de backups
3. Tabla de usuarios (sin admins)
4. Modal de edición (rol deshabilitado)
5. Vista móvil (375px)

---

## 🚀 SIGUIENTE PASO

Una vez completadas las pruebas:

### Si TODO funciona ✅
1. Marcar implementación como completa
2. Crear backup completo de producción
3. Documentar usuarios finales

### Si hay errores ❌
1. Anotar errores específicos
2. Revisar consola del navegador (F12)
3. Verificar logs de PHP
4. Consultar documentación técnica

---

## 📞 SOPORTE

### Archivos de Ayuda
- `ANALISIS_SISTEMA_MEJORAS.md` - Documentación técnica completa
- `RESUMEN_IMPLEMENTACION.md` - Resumen de cambios
- `CAMBIOS_ROL_SOLO_CONFIGURACION.md` - Sobre gestión de roles

### Logs del Sistema
```bash
# Ver errores de PHP
tail -f /xampp/apache/logs/error.log

# Ver errores de MySQL
tail -f /xampp/mysql/data/*.err
```

---

## ⏱ TIEMPO INVERTIDO

| Actividad | Tiempo |
|-----------|--------|
| Acceso y Seguridad | 2 min |
| Estadísticas | 3 min |
| Sistema Backup | 5 min |
| Protección Admin | 3 min |
| Gestión Usuarios | 4 min |
| Edición Usuarios | 3 min |
| Cambio Roles | 2 min |
| Responsive | 2 min |
| **TOTAL** | **24 min** |

---

## 🎉 FINALIZACIÓN

Una vez completadas las pruebas y verificado que todo funciona:

```
✅ Sistema probado
✅ Funcionalidades validadas
✅ Listo para producción
```

**¡Felicitaciones!** El sistema está completamente operativo. 🚀

---

**Versión**: 2.0  
**Fecha**: 25 de noviembre de 2025  
**Documento**: Guía de Prueba Rápida
