# 🚀 Guía de Instalación Rápida

## ⚡ Instalación en 5 minutos

### 1. Prerrequisitos
- XAMPP instalado y funcionando
- Git instalado (opcional)

### 2. Descargar el proyecto
```bash
git clone https://github.com/DaniloRMC/proyectoFinal.git
cd proyectoFinal
```

O descargar ZIP desde GitHub y extraer en `c:\xampp\htdocs\`

### 3. Configurar base de datos
1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Crear nueva base de datos: `panaderia_db`
3. Importar archivo: `panaderia_db.sql`

### 4. Acceder al sistema

#### 🔐 Panel de Administración
```
http://localhost/proyectoFinal/admin_panel.html
```
- **Email**: admin@panaderia.com
- **Contraseña**: admin123

#### 💰 Sistema de Ventas
```
http://localhost/proyectoFinal/login_ventas.html
```
- **PIN**: 1234

### 5. Verificar funcionamiento
```
http://localhost/proyectoFinal/test.php
```

## ✅ ¡Listo!

El sistema está completamente funcional con:
- Base de datos poblada (30+ registros por tabla)
- Panel de administración completo
- Sistema de ventas para empleados
- APIs funcionando
- Interfaz responsive

## 🆘 Problemas comunes

- **Error de conexión DB**: Verificar que MySQL esté iniciado en XAMPP
- **Error 404**: Comprobar que la carpeta está en `htdocs`
- **Permisos**: En Linux/Mac ejecutar `chmod -R 755`

---
**¿Necesitas ayuda?** Revisa `test.php` para diagnósticos completos.
