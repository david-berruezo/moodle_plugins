# Desarrollo de plugins Moodle – Plataforma de formación interna Nacex

## Descripción del proyecto

Desarrollo y personalización de la plataforma de formación interna de Nacex basada en **Moodle**, orientada a la capacitación de empleados de la red logística. El proyecto consistió en el desarrollo de un ecosistema de plugins que cubren todo el ciclo de formación: desde la incorporación automática de empleados al sistema, la asignación y seguimiento de cursos obligatorios por departamento, hasta la generación de informes para el equipo de RRHH.

La plataforma da servicio a los distintos departamentos de la empresa (Almacén, Reparto, Atención al cliente, Administración, IT), permitiendo a los responsables de formación asignar cursos obligatorios, hacer seguimiento del progreso de cada empleado, enviar recordatorios automáticos y generar informes exportables con métricas de cumplimiento.

---

## Plugins desarrollados

### 1. `local_nacex_tracking` - Seguimiento de cursos obligatorios
**Tipo:** local | **Ubicación:** `local/nacex_tracking/`

Permite a los responsables de RRHH asignar cursos obligatorios por departamento y hacer seguimiento del progreso de cada empleado.

**Funcionalidades:**
- Asignación de cursos obligatorios por departamento (Almacén, Reparto, Atención al cliente, etc.)
- Panel de gestión con resumen de estado por departamento
- Vista de progreso personal para cada empleado con barra de progreso
- Sincronización automática con el sistema de completion de Moodle (tarea cron cada 2h)
- Notificaciones por email de recordatorio configurables (L-V a las 8:00)
- Detección automática de cursos vencidos según fecha límite
- Sistema de estados: pendiente → en progreso → completado / vencido

**Ficheros principales:**
- `manage.php` - Panel de gestión para managers/RRHH
- `my_progress.php` - Vista de progreso para empleados
- `classes/manager.php` - Lógica de negocio principal
- `classes/task/sync_completion.php` - Tarea programada de sincronización
- `classes/task/send_reminders.php` - Tarea programada de envío de recordatorios
- `db/install.xml` - Definición de tablas (local_nacex_mandatory, local_nacex_tracking)
- `db/access.php` - Definición de capabilities
- `db/tasks.php` - Definición de tareas programadas
- `db/messages.php` - Provider de mensajes para notificaciones

---

### 2. `local_nacex_hrsync` - Sincronización con RRHH
**Tipo:** local | **Ubicación:** `local/nacex_hrsync/`

Sincroniza automáticamente los usuarios de Moodle con la base de datos de empleados de Nacex a través de un fichero CSV exportado por el departamento de RRHH.

**Funcionalidades:**
- Importación automática de empleados desde fichero CSV (delimitador `;`)
- Creación de nuevos usuarios con asignación de rol, departamento y puesto
- Actualización de datos existentes (nombre, estado, departamento, puesto)
- Suspensión automática de empleados dados de baja (no presentes en CSV)
- Log detallado de todas las operaciones (creación, actualización, suspensión, errores)
- Ejecución manual desde la interfaz web con botón de sincronización
- Generación automática de usernames únicos (primera letra nombre + apellido)
- Tarea cron programada de L-V a las 6:00

**CSV esperado (delimitador `;`):**
```
employee_id;first_name;last_name;email;department;position;active
NAC001;Carlos;García López;cgarcia@nacex.es;Almacén;Responsable de almacén;1
```

**Ficheros principales:**
- `sync_log.php` - Visor de logs y botón de sync manual
- `classes/sync_manager.php` - Lógica de sincronización completa
- `classes/task/sync_users.php` - Tarea programada
- `db/install.xml` - Tabla de log (local_nacex_hrsync_log)

---

### 3. `block_nacex_dashboard` - Panel del empleado
**Tipo:** block | **Ubicación:** `blocks/nacex_dashboard/`

Bloque para el Dashboard de Moodle que muestra información personalizada del empleado con la imagen corporativa de Nacex.

**Funcionalidades:**
- Mensaje de bienvenida personalizado con nombre y departamento
- Barra de progreso de formación con código de colores
- Contadores de cursos completados, pendientes y vencidos
- Lista de cursos urgentes (vencidos o con fecha límite en los próximos 7 días)
- Accesos rápidos a: Mis cursos, Mi progreso, Mi perfil, Calendario
- Estilo corporativo Nacex (colores rojo corporativo #e40613)

**Ficheros principales:**
- `block_nacex_dashboard.php` - Clase principal del bloque
- `styles.css` - Estilos corporativos personalizados
- `db/access.php` - Capabilities (addinstance, myaddinstance)

---

### 4. `report_nacex_training` - Informes de formación
**Tipo:** report | **Ubicación:** `report/nacex_training/`

Generador de informes con métricas de formación por departamento, curso y empleado, con exportación a Excel y PDF.

**Funcionalidades:**
- Filtrado por departamento, curso, estado y rango de fechas
- Tarjetas resumen con KPIs: total empleados, asignaciones, completados, tasa de finalización, vencidos
- Tabla detallada con todos los registros de formación
- Exportación a Excel con dos hojas (resumen ejecutivo + detalle por empleado)
- Exportación a PDF en formato tabla profesional (A4 horizontal)
- Cálculo automático de horas de formación por empleado

**Ficheros principales:**
- `index.php` - Página principal del informe con filtros y tabla
- `classes/report_generator.php` - Lógica de generación de informes y exportación
- `db/access.php` - Capability report/nacex_training:view

---

## APIs de Moodle utilizadas

### Access API (access)
La Access API proporciona funciones para determinar qué puede hacer el usuario actual en el sistema. Permite a los plugins definir sus propias **capabilities** (permisos) y asignarlas a diferentes roles.

**Uso en el proyecto:**
- Definición de capabilities en `db/access.php` de cada plugin (ej: `local/nacex_tracking:manage`, `local/nacex_tracking:viewreports`, `local/nacex_tracking:viewown`)
- Comprobación de permisos con `require_capability()` y `has_capability()` en cada página
- Asignación de capabilities a arquetipos de rol (manager, editingteacher, student)
- Control de acceso granular: managers gestionan asignaciones, empleados solo ven su progreso

### Data Manipulation API (dml)
La Data Manipulation API permite leer y escribir en la base de datos de forma consistente y segura, abstrayendo las diferencias entre motores (MySQL, PostgreSQL, MariaDB, etc.).

**Uso en el proyecto:**
- Operaciones CRUD con `$DB->get_record()`, `$DB->get_records_sql()`, `$DB->insert_record()`, `$DB->update_record()`, `$DB->delete_records()`
- Consultas SQL personalizadas con JOINs para obtener datos cruzados entre tablas (tracking + users + courses + mandatory)
- Uso de parámetros con nombre (`:param`) para prevenir inyección SQL
- `$DB->get_in_or_equal()` para consultas con cláusula IN dinámica
- `$DB->record_exists()` para comprobación de duplicados antes de insertar
- `$DB->set_field()` para actualizaciones de campos individuales
- Definición de tablas en `db/install.xml` con formato XMLDB

### File API (files)
La File API controla el almacenamiento de ficheros en conexión con los distintos plugins del sistema.

**Uso en el proyecto:**
- Lectura del fichero CSV de empleados en el plugin de sincronización RRHH con funciones de lectura de ficheros
- Gestión de la ruta de fichero configurable desde los ajustes del plugin
- Generación de ficheros de exportación (Excel y PDF) para descarga directa

### Form API (form)
La Form API define y gestiona la entrada de datos del usuario mediante formularios web con validación y protección contra ataques CSRF.

**Uso en el proyecto:**
- Formularios HTML con validación para asignación de cursos obligatorios
- Filtros de búsqueda en el informe de formación (departamento, curso, estado, fechas)
- Uso de `sesskey()` y `confirm_sesskey()` para protección CSRF en todas las acciones POST
- `optional_param()` y `required_param()` para recogida segura de parámetros GET/POST con tipos definidos (PARAM_INT, PARAM_TEXT, PARAM_ALPHA)

### Logging API (log)
La Logging API permite registrar eventos y acciones relevantes en el sistema, facilitando la auditoría y el seguimiento de operaciones.

**Uso en el proyecto:**
- Tabla de log personalizada `local_nacex_hrsync_log` para registrar todas las operaciones de sincronización
- Registro de acciones: creación de usuarios, actualización de datos, suspensión, errores
- Uso de `mtrace()` en las tareas programadas para registro en el log del cron de Moodle
- Visor de logs en la interfaz web (`sync_log.php`) con tabla paginada

### Navigation API (navigation)
La Navigation API permite manipular el árbol de navegación de Moodle para añadir y quitar elementos de menú según el rol del usuario.

**Uso en el proyecto:**
- Implementación de `local_nacex_tracking_extend_navigation()` para añadir enlace al panel de gestión en el menú lateral
- Implementación de `local_nacex_hrsync_extend_navigation()` para añadir enlace al log de sincronización
- Implementación de `report_nacex_training_extend_navigation_course()` para añadir el informe al menú de informes
- Uso de `navigation_node::TYPE_CUSTOM` y `pix_icon` para los iconos de menú
- Visibilidad condicional según capabilities del usuario

### Page API (page)
La Page API se utiliza para configurar la página actual, definir su layout, añadir JavaScript y configurar cómo se mostrará el contenido al usuario.

**Uso en el proyecto:**
- Configuración de cada página con `$PAGE->set_url()`, `$PAGE->set_context()`, `$PAGE->set_title()`, `$PAGE->set_heading()`
- Selección de layout apropiado: `admin` para páginas de gestión, `standard` para páginas de usuario, `report` para informes
- Uso del contexto `context_system::instance()` para páginas a nivel de sitio

### Output API (output)
La Output API genera la salida HTML siguiendo las convenciones del tema activo de Moodle, asegurando consistencia visual en toda la plataforma.

**Uso en el proyecto:**
- Uso de `$OUTPUT->header()` y `$OUTPUT->footer()` para renderizar la estructura de página completa
- `$OUTPUT->heading()` para encabezados de sección con el nivel correcto
- `$OUTPUT->notification()` para mensajes de estado (éxito, error, información)
- `$OUTPUT->pix_icon()` para iconos del sistema de Moodle
- `redirect()` con mensajes de notificación después de acciones POST (patrón Post/Redirect/Get)
- Uso de clases CSS de Bootstrap 4 integradas en el tema Boost de Moodle

### Otras APIs y funcionalidades de Moodle utilizadas

- **Messaging API**: Definición de message providers en `db/messages.php` y envío de notificaciones con `message_send()` y la clase `core\message\message`
- **Scheduled Tasks API**: Definición de tareas programadas en `db/tasks.php` con clases que extienden `core\task\scheduled_task`
- **User API**: Creación de usuarios con `user_create_user()`, gestión de contraseñas con `hash_internal_user_password()`
- **Custom Profile Fields**: Lectura/escritura de campos de perfil personalizados via tablas `user_info_field` y `user_info_data`
- **Course Completion API**: Consulta de finalización de cursos via tabla `course_completions`
- **Settings API**: Configuración de ajustes con `admin_settingpage` y tipos `admin_setting_configtext`, `admin_setting_configcheckbox`, `admin_setting_configselect`
- **String API (i18n)**: Internacionalización con ficheros en `lang/es/` y `lang/en/`, uso de `get_string()` con placeholders `{$a}`
- **Excel/PDF Export**: Uso de `MoodleExcelWorkbook` (PHPSpreadsheet) y `pdf` (TCPDF)

---

## Instalación

### Requisitos
- Moodle 4.0 o superior
- PHP 7.4 o superior
- MySQL 5.7+ / MariaDB 10.4+ / PostgreSQL 13+

### Campos de perfil personalizados (prerequisito)
Antes de instalar los plugins, crear los siguientes campos de perfil en:
**Administración del sitio > Usuarios > Cuentas > Campos de perfil de usuario**

| Nombre corto  | Nombre         | Tipo             |
|---------------|----------------|------------------|
| `department`  | Departamento   | Campo de texto   |
| `position`    | Puesto         | Campo de texto   |
| `employeeid`  | ID Empleado    | Campo de texto   |

### Orden de instalación

1. Copiar `local_nacex_tracking` a `{moodle}/local/nacex_tracking/`
2. Copiar `local_nacex_hrsync` a `{moodle}/local/nacex_hrsync/`
3. Copiar `block_nacex_dashboard` a `{moodle}/blocks/nacex_dashboard/`
4. Copiar `report_nacex_training` a `{moodle}/report/nacex_training/`
5. Acceder a Moodle como administrador → se ejecutará la instalación automática de las tablas
6. Configurar los ajustes de cada plugin en **Administración del sitio > Plugins > Plugins locales**

### Configuración post-instalación

**Sincronización RRHH:**
1. Ir a **Administración > Plugins > Plugins locales > Nacex - Sincronización RRHH**
2. Indicar la ruta al fichero CSV en el servidor (ej: `/var/data/nacex/employees.csv`)
3. Configurar el delimitador (por defecto `;`)
4. Establecer la contraseña por defecto para nuevos usuarios
5. La sincronización automática se ejecuta de lunes a viernes a las 6:00

**Seguimiento de formación:**
1. Ir a **Administración > Plugins > Plugins locales > Nacex - Seguimiento de formación**
2. Configurar los departamentos disponibles
3. Activar/desactivar notificaciones por email
4. Configurar los días de antelación para recordatorios

**Bloque Dashboard:**
1. Ir al Dashboard (Área personal)
2. Activar edición
3. Añadir el bloque "Panel Nacex Formación"

---

## Estructura de ficheros

```
local/nacex_tracking/
├── version.php              # Metadatos del plugin
├── lib.php                  # Hooks de navegación
├── settings.php             # Página de ajustes admin
├── manage.php               # Panel de gestión (managers)
├── my_progress.php          # Vista de progreso (empleados)
├── classes/
│   ├── manager.php          # Clase principal de lógica de negocio
│   └── task/
│       ├── sync_completion.php  # Tarea cron: sincronizar completion
│       └── send_reminders.php   # Tarea cron: enviar recordatorios
├── db/
│   ├── install.xml          # Definición de tablas XMLDB
│   ├── access.php           # Definición de capabilities
│   ├── tasks.php            # Definición de tareas programadas
│   └── messages.php         # Provider de mensajes
└── lang/
    ├── en/local_nacex_tracking.php
    └── es/local_nacex_tracking.php

local/nacex_hrsync/
├── version.php
├── lib.php
├── settings.php
├── sync_log.php             # Visor de logs + sync manual
├── classes/
│   ├── sync_manager.php     # Lógica de sincronización CSV → Moodle
│   └── task/
│       └── sync_users.php   # Tarea cron: sync diario
├── db/
│   ├── install.xml          # Tabla de log
│   ├── access.php
│   └── tasks.php
└── lang/
    ├── en/local_nacex_hrsync.php
    └── es/local_nacex_hrsync.php

blocks/nacex_dashboard/
├── version.php
├── block_nacex_dashboard.php  # Clase principal del bloque
├── styles.css                 # Estilos corporativos Nacex
├── db/
│   └── access.php
└── lang/
    ├── en/block_nacex_dashboard.php
    └── es/block_nacex_dashboard.php

report/nacex_training/
├── version.php
├── lib.php
├── index.php                # Página principal del informe
├── classes/
│   └── report_generator.php # Generación de informes + export
├── db/
│   └── access.php
└── lang/
    ├── en/report_nacex_training.php
    └── es/report_nacex_training.php
```

---

## Tecnologías utilizadas

- **PHP 7.4+** - Lenguaje de desarrollo principal
- **Moodle 4.x API** - Access, DML, File, Form, Logging, Navigation, Page, Output
- **MySQL / MariaDB** - Base de datos (acceso vía Moodle DML)
- **XMLDB** - Definición de esquema de base de datos portable
- **HTML5 / CSS3 / Bootstrap 4** - Interfaz de usuario (tema Boost de Moodle)
- **JavaScript** - Validaciones y confirmaciones en cliente
- **Moodle Scheduled Tasks** - Tareas programadas vía cron de Moodle
- **MoodleExcelWorkbook (PHPSpreadsheet)** - Exportación de informes a Excel
- **TCPDF** - Generación de informes en PDF
- **Moodle Messaging API** - Sistema de notificaciones por email y popup
