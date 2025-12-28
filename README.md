# Sistema de Control de Cochera

## Descripción

* Sistema web para la gestión integral de estacionamientos.
* Diseñado para registrar, controlar y auditar la entrada y salida de vehículos.
* Permite administrar espacios, servicios activos, pagos y usuarios.
* Orientado a un entorno académico y práctico, con base para escalar a producción.

## Objetivo del Sistema

* Automatizar el control de espacios de estacionamiento.
* Reducir errores en el registro manual de vehículos.
* Mejorar el control de ingresos por servicios de cochera.
* Centralizar la gestión de usuarios y roles.

## Alcance

* Gestión operativa de una cochera única.
* Control de servicios por vehículo.
* Generación de comprobantes de pago.
* Consulta de registros históricos.

## Características

* Gestión de espacios (libres, ocupados, en reparación).
* Registro de entrada y salida de vehículos.
* Inicio y finalización de servicios.
* Cálculo automático de pagos.
* Generación de comprobantes en formato PDF.
* Reportes de servicios e ingresos.
* Gestión de usuarios y roles.

## Arquitectura

* Arquitectura monolítica.
* Separación básica de responsabilidades.
* Inspiración en el patrón MVC.
* Backend encargado de la lógica de negocio.
* Frontend encargado de vistas e interacción.
* Base de datos para persistencia de información.

## Flujo General del Sistema

* El usuario inicia sesión en el sistema.
* Accede al dashboard principal.
* Interactúa con formularios y botones.
* El frontend envía solicitudes AJAX.
* El backend procesa la lógica.
* La base de datos se actualiza.
* La interfaz se refresca dinámicamente.

## Tecnologías

* **Backend**:

  * PHP 8.x.
  * PDO para acceso a base de datos.

* **Frontend**:

  * HTML.
  * CSS.
  * JavaScript.
  * Bootstrap 5.
  * FontAwesome.
  * TailwindCSS (uso parcial).
  * jsPDF para comprobantes PDF.

* **Base de Datos**:

  * MySQL 8.x.

* **Herramientas**:

  * Visual Studio Code.
  * Apache.
  * MySQL.

## Estructura del Proyecto

* `/api`:

  * Endpoints para solicitudes AJAX.
  * Inicio y finalización de servicios.
  * Consulta de espacios y vehículos.

* `/includes`:

  * Configuración de base de datos.
  * Autenticación y sesiones.
  * Funciones utilitarias.

* `/services`:

  * Lógica de negocio del sistema.

* `/public`:

  * Vistas principales.
  * Estilos y scripts.

* `sistema_cochera.sql`:

  * Script de creación y carga de la base de datos.

## Base de Datos

* Tabla `usuarios`:

  * Almacena credenciales y roles.

* Tabla `espacios`:

  * Registra los espacios de la cochera.

* Tabla `alquiler`:

  * Registra los servicios activos y finalizados.

* Tabla `pagos`:

  * Registra los pagos asociados a cada servicio.

## Roles del Sistema

* **Administrador**:

  * Gestión de usuarios.
  * Gestión de espacios.
  * Acceso a reportes.

* **Usuario**:

  * Registro y gestión de servicios.

## Seguridad

* Autenticación mediante sesiones PHP.
* Control de acceso basado en roles.
* Uso de consultas SQL parametrizadas.
* Validaciones básicas de datos de entrada.
* Implementación de tokens CSRF.

## Requisitos del Sistema

* PHP 8.x o superior.
* MySQL 8.x.
* Servidor Apache.
* Navegador web moderno.

## Instalación

* Clonar el repositorio.
* Configurar Apache con PHP.
* Crear una base de datos MySQL.
* Importar `sistema_cochera.sql`.
* Configurar credenciales en `includes/db.php`.
* Acceder al sistema desde el navegador.

## Uso

* Iniciar sesión como administrador o usuario.
* Acceder al dashboard.
* Gestionar espacios disponibles.
* Registrar entrada y salida de vehículos.
* Generar comprobantes.
* Consultar reportes.

## Limitaciones

* Credenciales sensibles dentro del código.
* No uso de variables de entorno.
* No soporte para múltiples idiomas.
* Sin sistema de notificaciones.

## Mejoras Futuras

* Uso de variables de entorno (.env).
* Refactorización a un MVC completo.
* Implementación de HTTPS.
* Encriptación de contraseñas.
* Optimización de consultas SQL.
* Implementación de caché.
* Notificaciones en tiempo real.
* Mejora del diseño responsivo.

## Estado del Proyecto

* Proyecto funcional.
* Enfoque académico y práctico.
* Base sólida para refactorización y escalabilidad.
