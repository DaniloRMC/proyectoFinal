/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - VERSIÓN SIMPLIFICADA FUNCIONAL
 * Sistema completo para gestión de panadería con conexión directa a datos reales
 */

class PanaderiaApp {
    constructor() {
        this.currentSection = 'login';
        this.isLoggedIn = false;
        this.dashboardData = null;
        this.init();
    }

    /**
     * Inicializar la aplicación
     */
    init() {
        console.log('🍞 Iniciando Sistema de Panadería...');
        this.showLogin();
        this.bindEvents();
    }

    /**
     * Vincular eventos de la aplicación
     */
    bindEvents() {
        // Eventos de login
        document.addEventListener('click', (e) => {
            if (e.target.id === 'loginBtn') {
                e.preventDefault();
                this.handleLogin();
            }
            
            if (e.target.id === 'logoutBtn') {
                e.preventDefault();
                this.handleLogout();
            }
        });

        // Eventos de navegación
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('nav-btn')) {
                e.preventDefault();
                const section = e.target.dataset.section;
                this.navigateToSection(section);
            }
        });
    }

    /**
     * Mostrar pantalla de login
     */
    showLogin() {
        const app = document.getElementById('app');
        app.innerHTML = `
            <div class="min-vh-100 d-flex align-items-center justify-content-center bg-gradient">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow-lg border-0 rounded-3">
                                <div class="card-body p-5">
                                    <div class="text-center mb-4">
                                        <i class="fas fa-bread-slice fa-3x text-warning mb-3"></i>
                                        <h3 class="fw-bold text-primary">🍞 Panadería San José</h3>
                                        <p class="text-muted">Sistema de Gestión</p>
                                    </div>
                                    
                                    <form id="loginForm">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope me-2"></i>Correo Electrónico
                                            </label>
                                            <input type="email" class="form-control form-control-lg" 
                                                   id="email" value="admin@panaderia.com" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="password" class="form-label">
                                                <i class="fas fa-lock me-2"></i>Contraseña
                                            </label>
                                            <input type="password" class="form-control form-control-lg" 
                                                   id="password" value="password" required>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" id="loginBtn" class="btn btn-primary btn-lg">
                                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <div class="text-center mt-4">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Usa: admin@panaderia.com / password
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Manejar inicio de sesión (simplificado)
     */
    async handleLogin() {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        // Validación simple
        if (email === 'admin@panaderia.com' && password === 'password') {
            this.isLoggedIn = true;
            this.showSuccessMessage('¡Bienvenido al sistema!');
            await this.loadDashboard();
        } else {
            this.showErrorMessage('Credenciales incorrectas');
        }
    }

    /**
     * Manejar cierre de sesión
     */
    handleLogout() {
        this.isLoggedIn = false;
        this.currentSection = 'login';
        this.dashboardData = null;
        this.showLogin();
        this.showSuccessMessage('Sesión cerrada correctamente');
    }

    /**
     * Cargar dashboard con datos reales
     */
    async loadDashboard() {
        try {
            this.showLoadingMessage('Cargando datos del dashboard...');
            
            const response = await fetch('dashboard_simple.php');
            const result = await response.json();
            
            if (result.success) {
                this.dashboardData = result.data;
                this.showDashboard();
            } else {
                this.showErrorMessage('Error al cargar datos: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showErrorMessage('Error de conexión con el servidor');
        }
    }

    /**
     * Mostrar dashboard principal
     */
    showDashboard() {
        const data = this.dashboardData;
        const app = document.getElementById('app');
        
        app.innerHTML = `
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
                <div class="container-fluid">
                    <a class="navbar-brand fw-bold">
                        <i class="fas fa-bread-slice me-2"></i>Panadería San José
                    </a>
                    
                    <div class="navbar-nav ms-auto">
                        <button class="btn btn-outline-light" id="logoutBtn">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <div class="container-fluid py-4">
                <!-- Estadísticas Principales -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-gradient-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Ventas Hoy</h6>
                                        <h3 class="mb-0">$${this.formatNumber(data.ventasHoy)}</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-cash-register fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-gradient-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Productos Activos</h6>
                                        <h3 class="mb-0">${data.productosActivos}</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-box fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-gradient-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Empleados</h6>
                                        <h3 class="mb-0">${data.empleadosActivos}</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-users fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-gradient-danger text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Ventas del Mes</h6>
                                        <h3 class="mb-0">$${this.formatNumber(data.ventasMes)}</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico y Listas -->
                <div class="row">
                    <!-- Gráfico de Ventas -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                                    Ventas de la Semana
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="ventasChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Productos Populares -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-star me-2 text-warning"></i>
                                    Productos Populares
                                </h5>
                            </div>
                            <div class="card-body">
                                ${this.renderProductosPopulares(data.productosPopulares)}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas de Stock -->
                ${data.alertasStock.length > 0 ? `
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Alertas de Stock Bajo
                                </h5>
                            </div>
                            <div class="card-body">
                                ${this.renderAlertasStock(data.alertasStock)}
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
        `;

        // Inicializar gráfico después de renderizar
        setTimeout(() => {
            this.initChart(data.ventasChart);
        }, 100);
    }

    /**
     * Renderizar productos populares
     */
    renderProductosPopulares(productos) {
        if (!productos || productos.length === 0) {
            return '<p class="text-muted">No hay datos disponibles</p>';
        }

        return productos.map((producto, index) => `
            <div class="d-flex justify-content-between align-items-center py-2 ${index < productos.length - 1 ? 'border-bottom' : ''}">
                <div>
                    <h6 class="mb-0">${producto.nombre}</h6>
                </div>
                <span class="badge bg-primary rounded-pill">${producto.ventas} ventas</span>
            </div>
        `).join('');
    }

    /**
     * Renderizar alertas de stock
     */
    renderAlertasStock(alertas) {
        if (!alertas || alertas.length === 0) {
            return '<p class="text-muted">No hay alertas de stock</p>';
        }

        return `
            <div class="row">
                ${alertas.map(alerta => `
                    <div class="col-md-4 mb-2">
                        <div class="alert alert-warning mb-0 p-2">
                            <strong>${alerta.producto}</strong><br>
                            <small>Stock: ${alerta.stock} (Mín: ${alerta.minimo})</small>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Inicializar gráfico de ventas
     */
    initChart(chartData) {
        const ctx = document.getElementById('ventasChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Ventas ($)',
                    data: chartData.data,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    /**
     * Formatear números para mostrar
     */
    formatNumber(num) {
        return parseFloat(num).toLocaleString('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /**
     * Mostrar mensaje de éxito
     */
    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }

    /**
     * Mostrar mensaje de error
     */
    showErrorMessage(message) {
        this.showToast(message, 'error');
    }

    /**
     * Mostrar mensaje de carga
     */
    showLoadingMessage(message) {
        this.showToast(message, 'info');
    }

    /**
     * Mostrar toast notification
     */
    showToast(message, type = 'info') {
        // Crear container si no existe
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1050';
            document.body.appendChild(toastContainer);
        }

        const toastId = 'toast' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        
        const toastHtml = `
            <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle me-2"></i>
                    ${message}
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();

        // Limpiar después de mostrar
        setTimeout(() => {
            toastElement.remove();
        }, 4000);
    }
}

// Inicializar aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.panaderiaApp = new PanaderiaApp();
});

console.log('🍞 Sistema de Panadería cargado correctamente');
