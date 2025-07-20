# 🍞 Sistema de Gestión de Panadería San José

Un sistema completo de gestión para panadería desarrollado con **HTML5**, **Bootstrap 5**, **JavaScript**, **PHP** (sin frameworks) y **MySQL**.

## 📋 Características Principales

### 🔐 Panel de Administración Completo
- Dashboard ejecutivo con KPIs en tiempo real
- Control total de ventas y transacciones
- Gestión completa de inventario con alertas
- Supervisión de empleados y rendimiento
- Análisis financiero con gráficos interactivos
- Centro de reportes y exportación de datos

### 💰 Sistema de Ventas para Empleados
- Punto de venta (POS) especializado
- Autenticación por PIN para empleados
- Catálogo de productos con imágenes
- Carrito de compras interactivo
- Procesamiento de ventas en tiempo real
- Gestión de stock automática

### 📊 Funcionalidades Avanzadas
- Base de datos MySQL con más de 30 registros por tabla
- APIs RESTful personalizadas
- Interfaz responsive con Bootstrap 5
- Gráficos dinámicos con Chart.js
- Sistema de autenticación seguro
- Alertas y notificaciones automáticas

## 🚀 Tecnologías Utilizadas

- **Frontend**: HTML5, Bootstrap 5, JavaScript (ES6+), CSS3
- **Backend**: PHP 8+ (sin frameworks)
- **Base de Datos**: MySQL
- **Librerías**: Chart.js, Font Awesome
- **Arquitectura**: MVC personalizada

## 📁 Estructura del Proyecto

```
/
├── admin_panel.html          # Panel de administración principal
├── login_ventas.html         # Sistema de ventas para empleados
├── sistema_completo.html     # Sistema integrado completo
├── index.html               # Sistema principal original
├── test.php                 # Diagnósticos del sistema
├── dashboard_simple.php     # API dashboard
├── panaderia_db.sql        # Base de datos MySQL
├── api/                    # APIs del sistema
│   ├── auth.php
│   ├── dashboard.php
│   ├── employees.php
│   ├── inventory.php
│   ├── products.php
│   ├── sales.php
│   └── config/
│       ├── config.php
│       ├── database.php
│       └── functions.php
├── css/
│   └── styles.css          # Estilos personalizados
└── js/
    └── app.js              # Aplicación JavaScript principal
```

## 🌐 URLs de Acceso

### Panel de Administración (Principal)
```
http://localhost/tarea%20web%20final/admin_panel.html
```
**Credenciales:**
- Email: `admin@panaderia.com`
- Contraseña: `admin123`

### Sistema de Ventas (Empleados)
```
http://localhost/tarea%20web%20final/login_ventas.html
```
**PIN de empleados:** `1234`

### Sistema de Diagnósticos
```
http://localhost/tarea%20web%20final/test.php
```

## 🚀 Instalación y Configuración

### Prerrequisitos
- XAMPP (Apache + MySQL + PHP)
- Navegador web moderno
- phpMyAdmin (incluido en XAMPP)

### Pasos de Instalación

1. **Configurar XAMPP**
   ```bash
   # Iniciar Apache y MySQL desde XAMPP Control Panel
   ```

2. **Copiar archivos**
   ```bash
   # Copiar la carpeta del proyecto a c:\xampp\htdocs\
   # Resultado: c:\xampp\htdocs\tarea web final\
   ```

3. **Crear base de datos**
   - Abrir phpMyAdmin: `http://localhost/phpmyadmin`
   - Crear nueva base de datos llamada: `panaderia_db`
   - Importar el archivo: `panaderia_db.sql`

4. **Verificar instalación**
   - Visitar: `http://localhost/tarea web final/test.php`
   - Verificar que todos los tests pasen

5. **Acceder al sistema**
   - Abrir: `http://localhost/tarea web final/index.html`
   - **Usuario**: `admin`
   - **Contraseña**: `admin123`

## 🔧 Características del Sistema

### Funcionalidades Principales

#### 🛡️ Sistema de Autenticación
- Login/logout seguro
- Gestión de sesiones
- Control de intentos fallidos
- Roles de usuario (admin, manager, cajero, vendedor)
- Cambio de contraseñas

#### 📊 Dashboard Interactivo
- Estadísticas de ventas en tiempo real
- Gráficos de productos más vendidos
- Alertas de inventario
- Resumen financiero
- Comparación con períodos anteriores

#### 🍞 Gestión de Productos
- CRUD completo de productos
- Categorización de productos
- Control de precios y costos
- Gestión de stock
- Búsqueda y filtrado avanzado

#### 👥 Gestión de Empleados
- CRUD de empleados
- Sistema de roles y permisos
- Estadísticas de rendimiento
- Control de acceso por roles

#### 💰 Sistema de Ventas (POS)
- Punto de venta interactivo
- Procesamiento de transacciones
- Múltiples métodos de pago
- Generación de facturas
- Historial de ventas

#### 📦 Control de Inventario
- Seguimiento de stock en tiempo real
- Movimientos de inventario
- Alertas de stock bajo
- Ajustes de inventario
- Reportes de valuación

### Características Técnicas

#### 🎨 Frontend Responsivo
- **Bootstrap 5** para diseño responsivo
- **CSS personalizado** con tema de panadería
- **JavaScript Vanilla** (sin frameworks)
- **Font Awesome** para iconografía
- **Chart.js** para gráficos estadísticos

#### ⚙️ Backend Robusto
- **Arquitectura MVC propia** sin frameworks externos
- **API RESTful** para todas las operaciones
- **Singleton Pattern** para conexión a base de datos
- **PDO** para operaciones de base de datos seguras
- **Sistema de logging** integrado

#### 🔒 Seguridad Implementada
- **Hash de contraseñas** con PHP password_hash()
- **Validación de entrada** en frontend y backend
- **Sanitización de datos** para prevenir XSS
- **Prepared Statements** para prevenir SQL Injection
- **Control de sesiones** con tokens seguros

#### 🗄️ Base de Datos Completa
- **7 tablas principales** con relaciones
- **Triggers automáticos** para inventario
- **Stored procedures** para reportes
- **Datos de ejemplo** incluidos
- **Índices optimizados** para rendimiento

## 📖 Uso del Sistema

### Acceso al Sistema
1. Navegue a `http://localhost/tarea web final/index.html`
2. Ingrese credenciales:
   - **Usuario**: `admin`
   - **Contraseña**: `admin123`

### Navegación
- **Dashboard**: Vista general del negocio
- **Productos**: Gestionar inventario de productos
- **Ventas**: Punto de venta y historial
- **Inventario**: Control de stock y movimientos
- **Empleados**: Gestión de personal
- **Reportes**: Estadísticas y análisis

### Flujo de Trabajo Típico
1. **Configurar productos** en el módulo de productos
2. **Ajustar inventario** inicial
3. **Procesar ventas** en el punto de venta
4. **Monitorear dashboard** para métricas
5. **Generar reportes** según necesidad

## 🔧 Configuración Técnica

### Configuración de Base de Datos
```php
// Archivo: api/config/config.php
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'panaderia_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Configuración de Autenticación
```php
// Tiempo de sesión: 2 horas
define('SESSION_LIFETIME', 7200);

// Intentos máximos de login: 5
define('MAX_LOGIN_ATTEMPTS', 5);

// Tiempo de bloqueo: 15 minutos
define('LOCKOUT_TIME', 15);
```

## 📋 API Endpoints Disponibles

### Autenticación (`api/auth.php`)
- `POST /auth/login` - Iniciar sesión
- `POST /auth/logout` - Cerrar sesión
- `GET /auth/status` - Estado de autenticación
- `POST /auth/change-password` - Cambiar contraseña

### Productos (`api/products.php`)
- `GET /products` - Listar productos
- `POST /products` - Crear producto
- `PUT /products/{id}` - Actualizar producto
- `DELETE /products/{id}` - Eliminar producto

### Ventas (`api/sales.php`)
- `GET /sales` - Listar ventas
- `POST /sales` - Procesar venta
- `GET /sales/receipt/{id}` - Obtener recibo
- `GET /sales/summary` - Resumen de ventas

### Inventario (`api/inventory.php`)
- `GET /inventory` - Estado de inventario
- `POST /inventory/movement` - Registrar movimiento
- `GET /inventory/alerts` - Alertas de stock
- `POST /inventory/adjust` - Ajustar stock

### Dashboard (`api/dashboard.php`)
- `GET /dashboard` - Resumen general
- `GET /dashboard/sales-chart` - Gráfico de ventas
- `GET /dashboard/top-products` - Productos top
- `GET /dashboard/financial-summary` - Resumen financiero

## 🎯 Objetivos Cumplidos

✅ **HTML5**: Estructura semántica moderna  
✅ **Bootstrap 5**: Framework CSS para responsividad  
✅ **CSS personalizado**: Tema de panadería con variables CSS  
✅ **JavaScript Vanilla**: Sin frameworks externos  
✅ **phpMyAdmin**: Gestión de base de datos MySQL  
✅ **PHP sin frameworks**: Arquitectura MVC propia  
✅ **Comentarios extensivos**: Documentación en cada archivo  
✅ **Base de datos completa**: Lista para producción  
✅ **Sistema funcional**: Todas las características implementadas  

## 🏆 Características Avanzadas Implementadas

### Frontend Avanzado
- **Tema personalizado** con colores de panadería
- **Animaciones CSS** para mejor UX
- **Componentes interactivos** sin jQuery
- **Gráficos dinámicos** con Chart.js
- **Notificaciones toast** personalizadas

### Backend Profesional
- **Patrón Singleton** para base de datos
- **Clases utilitarias** (Response, Validator, Logger, Security)
- **Manejo de errores** centralizado
- **Logging de sistema** con niveles
- **Validación robusta** en múltiples capas

### Base de Datos Optimizada
- **Triggers automáticos** para inventario
- **Stored procedures** para reportes complejos
- **Índices optimizados** para consultas rápidas
- **Constraints de integridad** referencial
- **Datos de ejemplo** realistas

## 📞 Soporte y Mantenimiento

### Logs del Sistema
Los logs se guardan en: `api/logs/app.log`

### Backup de Base de Datos
```sql
mysqldump -u root -p panaderia_db > backup_panaderia.sql
```

### Monitoreo de Errores
- Revise logs regularmente
- Monitoree espacio en disco
- Verifique conexiones de base de datos

## 📝 Notas Importantes

1. **Seguridad**: Cambiar contraseñas por defecto en producción
2. **Performance**: El sistema está optimizado para uso normal
3. **Escalabilidad**: Arquitectura preparada para crecimiento
4. **Mantenimiento**: Código bien documentado para facilitar cambios
5. **Compatibilidad**: Funciona en todos los navegadores modernos

---

**Desarrollado cumpliendo exactamente los requisitos del tema 33: Sistema de gestión de una panadería**

*Este sistema implementa todas las mejores prácticas de desarrollo web con PHP, JavaScript y MySQL, sin usar frameworks externos en PHP tal como se solicitó.*
