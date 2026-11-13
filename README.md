# Plataforma web con autogestión y protección de datos — Laravel + Filament

> **Repositorio de muestra (Showcase).** El código fuente completo pertenece a un proyecto real en producción y se mantiene privado por confidencialidad del cliente. Este repositorio documenta el problema de negocio, la arquitectura y las decisiones técnicas aplicadas, junto con fragmentos de código sanitizados.

---

## 1. Descripción general

Sitio web corporativo para una organización que asesora a familias en trámites y postulaciones habitacionales. Reemplazó una landing page que no representaba al negocio por una plataforma completa con tres piezas:

| Componente | Propósito |
| --- | --- |
| **Sitio público** | Presenta los servicios, noticias, equipo, acreditaciones, contacto y redes sociales, con metadatos SEO y sitemap dinámico. |
| **Canales de atención** | Formulario de solicitud de asesoría y asistente virtual de preguntas frecuentes que guía al usuario hasta ese formulario. |
| **Panel administrativo** | Backoffice donde el equipo del cliente gestiona el contenido y las solicitudes sin intervención técnica. |

**Objetivo:** que el cliente pasara de una página estática que confundía a sus visitantes a operar su presencia digital de forma autónoma, con canales de contacto ordenados y menos consultas repetitivas.

---

## 2. Mi rol

El proyecto se desarrolló en un equipo de dos personas. Mi rol fue dirigir el proyecto, diseñar la arquitectura y la solución, y desarrollar la plataforma: el sitio público, el panel administrativo y el enfoque de protección de datos.

---

## 3. Stack tecnológico

| Capa | Tecnología |
| --- | --- |
| Backend | **PHP 8.5**, **Laravel 12** |
| Frontend público | **React 18** (JSX) + **Inertia.js v2** |
| Estilos | **Tailwind CSS 3** |
| Panel administrativo | **Filament v3** (Livewire) |
| Base de datos | **MySQL 8.4** (desarrollo y producción) |
| Contenedores | **Docker** (Laravel Sail en desarrollo) |
| Infraestructura | **Linux VPS** |
| Pruebas | **PHPUnit:** 58 casos (9 archivos Feature y 1 Unit) |

Las pruebas cubren el formulario de asesoría y la validación región/comuna, el rate limiting, la autorización del panel, el flujo ARCOP con enlaces firmados, el cifrado en reposo y la retención, la sanitización contra XSS, y el SEO y el sitemap.

---

## 4. Arquitectura y decisiones de diseño

### Visión general

```
                ┌──────────────────────────────────────────────┐
                │                  Laravel 12                  │
 Visitante ───► │  Rutas web ─► Controladores ─► Inertia       │ ───► React (SPA)
                │      │                                       │      └─ Asistente FAQ
                │  Form Requests · Throttle · URLs firmadas    │
                │      │                                       │
 Equipo del ──► │  Filament v3 (/admin) ─► Resources/Policies  │
 cliente        │      │                                       │
                │  Eloquent ORM ─────────────► MySQL           │
                └──────────────────────────────────────────────┘
```

### Decisiones principales

| Decisión | Por qué |
| --- | --- |
| **Monolito con Laravel** | Un solo despliegue, un solo modelo de datos y menor costo operativo que una arquitectura de microservicios para el tamaño del proyecto. |
| **Inertia.js en lugar de una API REST separada** | React consume datos directamente desde los controladores de Laravel. Se elimina la capa de API, la duplicación de validaciones y la gestión de tokens, sin perder la experiencia de SPA. |
| **Dos áreas con responsabilidades separadas** | El frontend público (React) y el panel administrativo (Filament) no comparten capa de presentación. Cada uno evoluciona sin afectar al otro. |
| **Asistente FAQ como árbol de decisión en el cliente** | Las preguntas frecuentes son acotadas y conocidas, así que un árbol de opciones resuelve el caso sin costos de IA, sin respuestas impredecibles y sin carga en el servidor ([ver snippet](snippets/frontend/faq-assistant/FaqAssistant.jsx)). |

### Separación de responsabilidades en el backend

- **Validación:** toda entrada pasa por Form Requests; solo se persisten los datos validados. La región y la comuna del formulario de asesoría se validan contra la base de datos, y la comuna debe pertenecer a la región seleccionada, así que una petición manipulada se rechaza aunque el formulario use un `<select>` ([ver snippet](snippets/backend/Requests/StoreAppointmentRequest.php)).
- **Autorización:** el sitio público es anónimo y se protege con Form Requests, rate limiting (`throttle` por ruta) y enlaces firmados con expiración para el flujo ARCOP ([rutas](snippets/backend/Http/routes.php), [controlador](snippets/backend/Http/PrivacyRequestController.php)). Los únicos usuarios autenticados son los del equipo administrativo: el acceso al panel Filament está restringido a una whitelist mediante `canAccessPanel()` en el modelo `User` (interfaz `FilamentUser`). Las solicitudes ARCOP tienen además su propia Policy, que impide crearlas o borrarlas desde el panel ([ver snippet](snippets/backend/Policies/PrivacyRequestPolicy.php)); el resto de los recursos del panel depende de la whitelist.
- **Modelos:** Eloquent con asignación masiva controlada y consultas parametrizadas.
- **Minimización de datos:** a las vistas React se envían solo los campos que necesitan, nunca modelos completos ([ver snippet](snippets/backend/Http/ProgramController.php)).

### Seguridad y protección de datos

Diseñado con privacidad desde el origen, alineado con la **Ley 21.719** de protección de datos personales (Chile):

- Cifrado en reposo de los campos personales sensibles ([ver snippet](snippets/backend/Models/Appointment.php)).
- Registro del consentimiento informado en cada solicitud.
- Canal de derechos ARCOP con verificación de identidad mediante enlaces firmados con expiración.
- Retención limitada con el trait `Prunable` en los modelos con datos personales: las solicitudes de asesoría se eliminan a los 12 meses y las solicitudes ARCOP nunca verificadas, a los 7 días. El scheduler ejecuta `model:prune` una vez al día ([ver snippet](snippets/backend/Console/RetentionPruning.php)).
- Rate limiting con el middleware `throttle` en cada ruta de formulario público (5 envíos por minuto en el formulario de asesoría y 3 por minuto en el de solicitudes ARCOP) y mensajes de error genéricos hacia el usuario.

---

## 5. Enfoque de negocio

### El problema

El cliente ya tenía un sitio web, pero no le servía:

| Situación anterior | Consecuencia para el negocio |
| --- | --- |
| **Mensaje confuso:** el sitio daba a entender que la organización se dedicaba a otro rubro. | Los visitantes no entendían qué servicio ofrecía, lo que dañaba la credibilidad y la captación. |
| **Landing page estática:** solo información básica y un número de teléfono. | No había espacio para mostrar servicios, equipo, noticias ni redes sociales. |
| **Un único canal de contacto:** todo llegaba por teléfono y WhatsApp. | Una sola persona respondía las mismas preguntas una y otra vez, sin orden ni registro de las solicitudes. |
| **Dependencia del desarrollador:** cualquier cambio requería intervención técnica. | Costos recurrentes y demoras para algo tan simple como publicar una noticia. |

### La solución

La plataforma ataca cada uno de esos puntos:

| Necesidad | Cómo se resolvió |
| --- | --- |
| **Identidad clara** | Sitio público reestructurado alrededor de lo que el cliente realmente hace: catálogo de servicios, equipo, acreditaciones y noticias, con SEO para que lo encuentren las personas correctas. |
| **Darse a conocer** | Secciones de contenido actualizables, carrusel de portada y enlaces a redes sociales integrados en todo el sitio. |
| **Canal de contacto ordenado** | Formulario de solicitud de asesoría con selección de región y comuna, consentimiento registrado y límite de envíos contra abuso. Cada solicitud queda registrada y con estado de seguimiento en el panel. |
| **Menos consultas repetitivas** | Asistente virtual con estructura de árbol que responde las preguntas frecuentes (requisitos, alcance de los servicios, casos especiales) y, al final de cada rama, invita al usuario a agendar a través del formulario. |
| **Autonomía del cliente** | Panel administrativo con Filament para gestionar la mayor parte del contenido del sitio sin tocar código. |

### Autogestión con Filament: la pieza central

El panel administrativo es lo que convierte el sitio en una herramienta del cliente y no en una dependencia del desarrollador:

| Módulo del panel | Qué gestiona el cliente |
| --- | --- |
| Contenido institucional | Catálogo de servicios, noticias y carrusel de portada. |
| Equipo | Integrantes, cargos, descripciones y fotografías. |
| Solicitudes de asesoría | Seguimiento del estado de contacto de cada solicitud recibida. |
| Solicitudes de privacidad (ARCOP) | Gestión de las solicitudes verificadas de los titulares de datos ([ver snippet](snippets/backend/Filament/PrivacyRequestResource.php)). |

Principios aplicados:

- **Cero código para operar:** formularios guiados y validaciones pensadas para usuarios no técnicos.
- **Acceso controlado:** solo los usuarios del equipo administrativo incluidos en la whitelist de `canAccessPanel()` ingresan al panel; los visitantes del sitio público no tienen cuenta. Las solicitudes ARCOP tienen además una Policy propia que impide crearlas o borrarlas desde el panel.
- **Consistencia visual garantizada:** el cliente edita contenido, no diseño; el sitio mantiene su identidad sin importar quién publique.

### Impacto esperado

- **Imagen coherente:** que el sitio comunique con claridad a qué se dedica la organización.
- **Menor carga de atención:** que las preguntas frecuentes se resuelvan en el sitio y que las solicitudes lleguen ordenadas, en lugar de saturar WhatsApp.
- **Reducción de costos operativos:** que las tareas de contenido dejen de requerir horas de desarrollo.
- **Autonomía:** que el cliente sea dueño de su operación digital y que el soporte técnico se enfoque en mejoras de valor.

---

## 6. Código de muestra

Fragmentos sanitizados del proyecto. No son ejecutables por sí solos (ver [snippets/README.md](snippets/README.md)).

| Archivo | Qué decisión demuestra |
| --- | --- |
| [`StoreAppointmentRequest.php`](snippets/backend/Requests/StoreAppointmentRequest.php) | Validación de región y comuna contra la base de datos; el `<select>` no es una barrera de seguridad. |
| [`PrivacyRequestPolicy.php`](snippets/backend/Policies/PrivacyRequestPolicy.php) | Autorización en el panel: las solicitudes ARCOP no se crean ni se borran desde el backoffice. |
| [`Appointment.php`](snippets/backend/Models/Appointment.php) | Cifrado en reposo con el cast `encrypted` y asignación masiva controlada con `$fillable`. |
| [`RetentionPruning.php`](snippets/backend/Console/RetentionPruning.php) | Retención con `Prunable` y `model:prune` diario en el scheduler. |
| [`PrivacyRequestController.php`](snippets/backend/Http/PrivacyRequestController.php) | Verificación ARCOP con enlace firmado que expira y respuesta uniforme contra la enumeración de correos. |
| [`routes.php`](snippets/backend/Http/routes.php) | Rate limiting con `throttle` por ruta en los formularios públicos. |
| [`ProgramController.php`](snippets/backend/Http/ProgramController.php) | Minimización de datos: solo los campos necesarios llegan a la vista de Inertia. |
| [`PrivacyRequestResource.php`](snippets/backend/Filament/PrivacyRequestResource.php) | Seguimiento de estado en Filament, bloqueado hasta que el titular verifica su identidad. |
| [`FaqAssistant.jsx`](snippets/frontend/faq-assistant/FaqAssistant.jsx) | Asistente FAQ como árbol de decisión en el cliente, sin IA ni backend. |
| [`AppointmentTest.php`](snippets/tests/AppointmentTest.php) | Pruebas Feature de la validación región/comuna y del rate limit. |
| [`PrivacyRequestTest.php`](snippets/tests/PrivacyRequestTest.php) | Pruebas Feature de enlaces firmados manipulados o vencidos y de la autorización del panel. |

---

## 7. Despliegue y DevOps

### Paridad local / producción con Docker

- El entorno de desarrollo corre completamente en **contenedores Docker** (Laravel Sail): PHP, Composer, Node y servicios auxiliares viven dentro del contenedor, junto con **MySQL 8.4** y **Mailpit** para los correos.
- Desarrollo y producción usan las mismas versiones de runtime: **PHP 8.5** y **MySQL 8.4**. Así se evita el clásico "en mi máquina funciona".
- Flujo de trabajo en **WSL 2**, con alineación de usuario y permisos entre host y contenedor para mantener un repositorio limpio.

### Control de versiones con Git (GitHub Flow)

- Repositorio en **GitHub** con **GitHub Flow**: la rama `main` siempre contiene código estable y desplegable.
- Cada funcionalidad o corrección se desarrolla en una **rama propia** creada desde `main` y se integra mediante **Pull Request** una vez probada.
- Commits pequeños y descriptivos siguiendo **Conventional Commits** (`feat:`, `fix:`, `docs:`), lo que deja un historial legible y fácil de auditar.
- El historial permite rastrear cada cambio y revertirlo con seguridad si algo falla en producción.

### Administración de servidores VPS

- Configuración y mantenimiento de un servidor **Linux VPS** para alojar la plataforma.
- El scheduler de Laravel se ejecuta cada minuto mediante cron en el VPS y dispara tareas como `model:prune` según la política de retención: los datos vencidos se eliminan una vez al día (trait `Prunable`).
- Compilación de assets de producción con Vite y configuración segura del entorno (modo debug desactivado, secretos fuera del código).
- Base de datos **MySQL 8.4** en producción, con consultas a través de Eloquent y el query builder, sin SQL específico de un motor.

---

## 8. Qué haría distinto

- Enviar ids en vez de nombres en región y comuna, y guardarlos como claves foráneas.
- Roles y Policies por cada Resource de Filament, para que el panel pueda crecer a perfiles con permisos distintos (por ejemplo, alguien que solo publique noticias).
- Widgets con métricas operativas reales (solicitudes por estado y por región).
- Mover la whitelist de administradores a configuración por entorno en vez de tenerla en el código.

---

<p align="center"><sub>Proyecto desarrollado y mantenido de forma privada. Disponible para conversar sobre la arquitectura y las decisiones técnicas en una entrevista.</sub></p>
