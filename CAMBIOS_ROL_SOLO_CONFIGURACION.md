# ✅ Cambio de Rol Solo desde Configuración

## 📋 Resumen de Cambios

Se ha modificado el sistema para que el **cambio de rol de usuarios** solo se pueda realizar desde el **módulo de Configuración**, no desde la edición estándar de usuarios.

---

## 🔧 Archivos Modificados

### 1. **app/view/Registrar_Usuario.php**
- ✅ El campo "Tipo de Usuario" ahora está **deshabilitado** (`disabled`)
- ✅ Se agregó un mensaje informativo que indica:
  > *"Para cambiar el rol del usuario, use el módulo de **Configuración**"*

**Antes:**
```html
<select class="form-select" id="edit_tipo" name="tipo" required>
    <option value="Profesor">Profesor</option>
    <option value="Encargado">Encargado</option>
    <option value="Administrador">Administrador</option>
</select>
```

**Después:**
```html
<select class="form-select" id="edit_tipo" name="tipo" disabled>
    <option value="Profesor">Profesor</option>
    <option value="Encargado">Encargado</option>
    <option value="Administrador">Administrador</option>
</select>
<div class="form-text text-muted">
    <i class="bi bi-info-circle"></i> Para cambiar el rol del usuario, use el módulo de <strong>Configuración</strong>
</div>
```

---

### 2. **app/controllers/UsuarioController.php**
- ✅ El método `editarUsuario()` ahora **ignora el parámetro `$tipo_usuario`** recibido
- ✅ **Obtiene el rol actual** del usuario desde la base de datos
- ✅ **Mantiene el rol sin cambios** al actualizar nombre, correo y teléfono

**Cambio Principal:**
```php
// OBTENER EL TIPO ACTUAL DEL USUARIO (NO SE PERMITE CAMBIAR DESDE EDICIÓN)
$actual = $this->usuarioModel->obtenerPorId((int)$id_usuario);
if (!$actual) {
    return ['error' => true, 'mensaje' => '⚠️ Usuario no encontrado.'];
}
$tipo_usuario = $actual['tipo']; // Mantener el rol actual
```

**Eliminado:**
- ❌ Validación del parámetro `$tipo_usuario` en los campos requeridos
- ❌ Validación de tipos permitidos ('Profesor', 'Encargado', 'Administrador')

---

### 3. **Public/css/brand.css**
- ✅ Estilos mejorados para campos `<select>` deshabilitados
- ✅ Estilos para mensajes informativos `.form-text.text-muted`

```css
.form-select:disabled,
.form-select[disabled] {
  background-color: #f0f4f8;
  opacity: 0.8;
  cursor: not-allowed;
  color: #64748b;
}

.form-text.text-muted {
  font-size: 0.875rem;
  color: var(--muted);
}

.form-text.text-muted i {
  margin-right: 4px;
}
```

---

## 🎯 Resultado Final

### ✅ En la Vista de Edición de Usuario:
1. El select de "Tipo de Usuario" se muestra **deshabilitado** (grisado)
2. Aparece un mensaje claro: *"Para cambiar el rol del usuario, use el módulo de **Configuración**"*
3. El administrador NO puede cambiar el rol desde aquí

### ✅ En el Backend (UsuarioController):
1. Aunque se envíe un valor de `tipo` desde el formulario, **se ignora completamente**
2. El sistema **obtiene el rol actual** del usuario de la BD
3. Solo se actualizan: **nombre, correo y teléfono**
4. El rol **permanece sin cambios**

### ✅ Dónde SÍ se puede cambiar el rol:
- **Solo en**: `app/view/Configuracion_Admin.php` → Sección "Gestión de Roles"
- **Usando**: `app/controllers/ConfiguracionController.php` → método `cambiarRol()`

---

## 🔒 Seguridad

Esta separación mejora la seguridad porque:
- ✅ El cambio de roles es una acción **administrativa crítica**
- ✅ Está **centralizada** en un solo módulo especializado
- ✅ Se evitan cambios accidentales al editar usuarios
- ✅ El flujo está más claro y controlado

---

## 📝 Notas Técnicas

- El campo `disabled` en HTML **no envía su valor** en el formulario POST
- Por seguridad, el backend **sobreescribe** el valor con el rol actual de la BD
- Esto garantiza que aunque alguien modifique el HTML, **el rol no cambiará**

---

**Fecha de implementación:** 25 de noviembre de 2025  
**Desarrollado para:** Sistema de Reservación AIP
