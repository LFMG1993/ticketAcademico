# TicketFlow Académico 🚀

**TicketFlow Académico** es una plataforma integral de gestión de tiquetes de soporte diseñada para entornos educativos. El proyecto demuestra la implementación de arquitecturas modernas utilizando **PHP nativo**, **SQLite** para persistencia de datos y **Redis** como motor de alto rendimiento para caché, logs en tiempo real y gestión de sesiones.

## 🛠️ Stack Tecnológico

- **Backend:** PHP 8.2 (Programación procedimental y funcional).
- **Base de Datos:** SQLite 3 (Ligera y portable).
- **Caché y Mensajería:** Redis (Estructuras de datos String y List).
- **Frontend:** HTML5, CSS3 (Custom Properties y Grid Layout) y Vanilla JavaScript (ES6).
- **Comunicación:** AJAX mediante Fetch API para una experiencia Single Page Application (SPA).

## ✨ Características Principales

### 👨‍🎓 Interfaz del Estudiante (Pública)
- **Creación de Solicitudes:** Formulario optimizado con validación de categorías (Plataforma, Matrícula, Pagos, etc.).
- **Seguimiento en Tiempo Real:** Listado dinámico de tickets con estados visuales (Pendiente/Resuelto).
- **Optimización de Lectura:** Implementación de caché de nivel 1 en Redis para reducir la carga en la base de datos SQLite.

### 🔐 Panel Administrativo (Privado)
- **Sistema de Autenticación:** Login seguro con `password_hash` y `password_verify`.
- **Gestión de Sesiones en Redis:** Las sesiones activas se registran en Redis con un TTL (Time To Live) de 1 hora.
- **Dashboard Dinámico:** Gestión de respuestas mediante AJAX, permitiendo responder tiquetes sin recargar la página.
- **Preservación de Estado de UI:** Algoritmo en JavaScript que detecta el foco en `textarea` para evitar la pérdida de texto durante las actualizaciones automáticas.

## 🏗️ Arquitectura Técnica y Redis

El corazón del proyecto es la integración con **Redis**, utilizado de tres formas críticas:

1.  **Caché de Datos (`tickets:recientes`):**
    - Almacena el JSON de los últimos tiquetes con un TTL de 30 segundos.
    - Implementa la estrategia *Cache-Aside*: el sistema busca en Redis; si no existe, consulta SQLite y repuebla la caché.

2.  **Contador Atómico (`tickets:contador`):**
    - Utiliza Redis para llevar un conteo global de tiquetes, demostrando el manejo de consistencia en memoria.

3.  **Logs de Actividad (`tickets:log`):**
    - Implementa una estructura de tipo **LIST** (`LPUSH` y `LTRIM`) para mantener un historial circular de las últimas 20 acciones realizadas en la plataforma (inicios de sesión, respuestas, etc.).

4.  **Monitoreo de Sesiones (`admin:sesion:*`):**
    - Rastreo de administradores activos directamente desde la memoria de Redis.

## 📂 Estructura del Proyecto

```text
ticketAcademico/
├── app/
│   ├── Controllers/
│   │   ├── Api/            # Endpoints JSON para el frontend
│   │   │   ├── tickets.php # API de listado de tiquetes
│   │   │   └── redis.php   # API de estadísticas de Redis
│   │   └── create.php      # Lógica de creación de tickets
│   ├── Core/
│   │   ├── Database.php    # Singleton/Conexión PDO SQLite y Migraciones
│   │   └── Redis.php       # Adaptador de conexión para PhpRedis
│   └── views/              # Capa de presentación
│       ├── dashboard.php   # Panel de administración (SPA)
│       ├── index.php       # Vista pública del estudiante
│       └── login.php       # Control de acceso
├── assets/
│   ├── css/                # Estilos modulares (admin.css, style.css)
│   └── js/                 # Lógica de cliente (opcionalmente separado)
└── database.sqlite         # Base de datos persistente
