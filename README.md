# 🌍 Catamap

**Plataforma de Turismo Sostenible y Colaborativo para Catamarca**

Catamap es una aplicación web diseñada para visibilizar y promover atractivos turísticos poco convencionales de la provincia de Catamarca. A través de un mapa interactivo y la participación comunitaria, buscamos descentralizar el turismo y fomentar el desarrollo local.

🔗 **Repositorio:** [https://github.com/gonza-rom/Catamap](https://github.com/gonza-rom/Catamap)

---

## 👥 Equipo de Trabajo

Proyecto desarrollado en el marco del Seminario Final de la carrera **Técnico Superior en Desarrollo de Software**.

- **Gonzalo Romero** - *Full Stack Developer*
- **Tania Quiroga** - *Full Stack Developer*

---

## 🛠️ Stack Tecnológico

La aplicación ha sido construida utilizando tecnologías web robustas y escalables:

### Backend
- **Lenguaje:** PHP 8 (Nativo, estructura MVC simplificada).
- **Base de Datos:** MySQL / MariaDB.
- **Servidor:** Apache (XAMPP).

### Frontend
- **Estructura:** HTML5 Semántico.
- **Estilos:** CSS3, Bootstrap 4.6 (Diseño Responsive).
- **Scripting:** JavaScript (ES6+), jQuery.
- **Mapas:** Google Maps JavaScript API (Integración de marcadores y geolocalización).

### Herramientas de Desarrollo
- **IDE:** Visual Studio Code.
- **Control de Versiones:** Git & GitHub.
- **Gestión DB:** phpMyAdmin.

---

## �️ Modelo de Base de Datos

El sistema gestiona la información a través de las siguientes tablas relacionales:

1.  **`usuarios`**: Gestión de cuentas, autenticación segura (hash passwords) y roles (`usuario`, `emprendedor`, `admin`, `administrador`).
2.  **`lugares_turisticos`**: Almacena los puntos de interés validados con coordenadas (`lat`, `lng`), descripciones e imágenes.
3.  **`lugares_sugeridos`**: Cola de moderación donde los usuarios proponen nuevos sitios antes de ser aprobados por un administrador.
4.  **`categorias`**: Clasificación temática (Ecoturismo, Histórico, Gastronómico, etc.).
5.  **`departamentos`**: División política de Catamarca para filtrado regional.
6.  **`comentarios`**: Sistema de feedback y reseñas de usuarios sobre los lugares.
7.  **`favoritos`**: Permite a los usuarios guardar sus lugares preferidos.

---

## 🚀 Roadmap de Desarrollo (Sprints)

El desarrollo del proyecto se organizó en 4 etapas o "Sprints" funcionales:

### 🏁 Sprint 1: Fundamentos y Autenticación
*   Configuración del entorno de desarrollo (XAMPP, Git).
*   Diseño del esquema de Base de Datos inicial.
*   Implementación del sistema de **Login y Registro** de usuarios.
*   Manejo de sesiones y seguridad (protección de rutas).

### � Sprint 2: Core - Mapas y Lugares
*   Integración de la **Google Maps API**.
*   CRUD (Crear, Leer, Actualizar, Borrar) básico de lugares turísticos.
*   Visualización dinámica de marcadores en el mapa.
*   Implementación de buscadores y filtros por Categoría y Departamento.

### 🌟 Sprint 3: Experiencia de Usuario (UX)
*   Desarrollo de perfil de usuario editable.
*   Sistema de **"Guardar en Favoritos"** (AJAX).
*   Módulo de **Comentarios y Valoraciones**: Los usuarios pueden opinar sobre los destinos.
*   Formulario de "Sugerir Lugar": Permite la colaboración ciudadana.

### 🛡️ Sprint 4: Administración y Moderación (Actual)
*   **Panel Administrativo Completo (`/admin`)**:
    *   **Dashboard:** Estadísticas en tiempo real (usuarios activos, lugares pendientes, etc.).
    *   **Moderación:** Aprobación o rechazo de lugares sugeridos por la comunidad.
    *   **Gestión:** Control total sobre usuarios, categorías y comentarios.
*   Refinamiento de seguridad (Roles y Permisos).
*   Optimización de consultas SQL y corrección de bugs.

---

## ⚙️ Instalación y Despliegue

1.  **Clonar el repositorio:**
    ```bash
    git clone https://github.com/gonza-rom/Catamap.git
    cd Catamap
    ```

2.  **Configurar Base de Datos:**
    *   Abrir phpMyAdmin.
    *   Crear una base de datos llamada `catamap`.
    *   Importar el archivo `catamap.sql` ubicado en la carpeta `/data` (o raíz).
    *   Ejecutar migraciones adicionales si es necesario (ver carpeta `/data`).

3.  **Configurar Conexión:**
    *   Editar `includes/conexion.php` y `config/database.php` con tus credenciales locales.

4.  **Ejecutar:**
    *   Mover la carpeta del proyecto a `C:\xampp\htdocs\`.
    *   Acceder desde el navegador a `http://localhost/catamap`.

---
*Desarrollado con ❤️ para impulsar el turismo en Catamarca.*