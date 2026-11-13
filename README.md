# Plataforma Web Corporativa con Autogestión de Contenidos

> **Repositorio de muestra (Showcase).** El código fuente completo pertenece a un proyecto real en producción y se mantiene privado por confidencialidad del cliente. Este repositorio documenta el problema de negocio, la arquitectura, las decisiones técnicas y el enfoque de gobernabilidad aplicados.

---

## 1. Descripción general

Sitio web corporativo para una organización que asesora a familias en trámites y postulaciones habitacionales. Reemplazó una landing page que no representaba al negocio por una plataforma completa con tres piezas:

| Componente | Propósito |
| --- | --- |
| **Sitio público** | Presenta los servicios, noticias, equipo, acreditaciones, contacto y redes sociales, con metadatos SEO y sitemap dinámico. |
| **Canales de atención** | Formulario de solicitud de asesoría y asistente virtual de preguntas frecuentes que guía al usuario hasta ese formulario. |
| **Panel administrativo** | Backoffice donde el equipo del cliente gestiona el contenido y las solicitudes sin intervención técnica. |

**Resultado clave:** el cliente pasó de tener una página estática que confundía a sus visitantes a operar su presencia digital de forma autónoma, con canales de contacto ordenados y menos consultas repetitivas.

---

## 2. Stack tecnológico

| Capa | Tecnología |
| --- | --- |
| Backend | **PHP 8.2+**, **Laravel 12** |
| Frontend público | **React 18** (JSX) + **Inertia.js v2** |
| Estilos | **Tailwind CSS 3** |
| Panel administrativo | **Filament v3** (Livewire) |
| Base de datos | **MySQL** (producción) |
| Contenedores | **Docker** (Laravel Sail en desarrollo) |
| Infraestructura | **Linux VPS** |
| Pruebas | PHPUnit (pruebas Feature) |

---

## 3. Arquitectura y decisiones de diseño

### Visión general

```
                ┌──────────────────────────────────────────────┐
                │                  Laravel 12                  │
 Visitante ───► │  Rutas web ─► Controladores ─► Inertia       │ ───► React (SPA)
                │      │                                       │      └─ Asistente FAQ
                │  Form Requests · Policies · Rate limiting    │
                │      │                                       │
 Equipo del ──► │  Filament v3 (/admin) ─► Resources/Policies  │
 cliente        │      │                                       │
                │  Eloquent ORM ─────────────► MySQL           │
                └──────────────────────────────────────────────┘
```

### Decisiones principales

| Decisión | Por qué |
| --- | --- |
| **Monolito modular con Laravel** | Un solo despliegue, un solo modelo de datos y menor costo operativo que una arquitectura de microservicios para el tamaño del proyecto. |
| **Inertia.js en lugar de una API REST separada** | React consume datos directamente desde los controladores de Laravel. Se elimina la capa de API, la duplicación de validaciones y la gestión de tokens, sin perder la experiencia de SPA. |
| **Dos áreas con responsabilidades separadas** | El frontend público (React) y el panel administrativo (Filament) no comparten capa de presentación. Cada uno evoluciona sin afectar al otro. |
| **Asistente FAQ como árbol de decisión en el cliente** | Las preguntas frecuentes son acotadas y conocidas, así que un árbol de opciones resuelve el caso sin costos de IA, sin respuestas impredecibles y sin carga en el servidor. |

### Separación de responsabilidades en el backend

- **Validación:** toda entrada pasa por Form Requests; solo se persisten los datos validados.
- **Autorización:** Policies por recurso, tanto en el sitio como en el panel. Estar autenticado no basta para acceder a un recurso.
- **Modelos:** Eloquent con asignación masiva controlada y consultas parametrizadas.
- **Minimización de datos:** a las vistas React se envían solo los campos que necesitan, nunca modelos completos.

### Seguridad y protección de datos

Diseñado con privacidad desde el origen, alineado con la **Ley 21.719** de protección de datos personales (Chile):

- Cifrado en reposo de los campos personales sensibles.
- Registro del consentimiento informado en cada solicitud.
- Canal de derechos ARCOP con verificación de identidad mediante enlaces firmados con expiración.
- Retención limitada con purga automática programada.
- Rate limiting en formularios públicos y mensajes de error genéricos hacia el usuario.

---

## 4. Enfoque de gobernabilidad y negocio

### El problema

El cliente ya tenía un sitio web, pero no le servía:

| Situación anterior | Consecuencia para el negocio |
| --- | --- |
| **Mensaje confuso:** el sitio daba a entender que la organización se dedicaba a otro rubro. | Los visitantes no entendían qué servicio ofrecía, lo que dañaba la credibilidad y la captación. |
| **Landing page estática:** solo información básica y un número de teléfono. | No había espacio para mostrar servicios, equipo, noticias ni redes sociales. |
| **Un único canal de contacto:** todo llegaba por teléfono y WhatsApp. | Una sola persona respondía las mismas preguntas una y otra vez, sin orden ni registro de las solicitudes. |
| **Dependencia del desarrollador:** cualquier cambio requería intervención técnica. | Costos recurrentes y demoras para algo tan simple como publicar una noticia. |

### La solución

Diseñé y desarrollé una plataforma que ataca cada uno de esos puntos:

| Necesidad | Cómo la resolví |
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
| Solicitudes de privacidad (ARCOP) | Gestión de las solicitudes verificadas de los titulares de datos. |
| Indicadores | Widgets con métricas operativas en el panel de inicio. |

Principios aplicados:

- **Cero código para operar:** formularios guiados y validaciones pensadas para usuarios no técnicos.
- **Acceso controlado:** solo usuarios autorizados ingresan al panel, y cada recurso tiene su propia Policy.
- **Consistencia visual garantizada:** el cliente edita contenido, no diseño; el sitio mantiene su identidad sin importar quién publique.

### Impacto en el negocio

- **Imagen coherente:** el sitio comunica con claridad a qué se dedica la organización.
- **Menor carga de atención:** las preguntas frecuentes se resuelven en el sitio y las solicitudes llegan ordenadas, en lugar de saturar WhatsApp.
- **Reducción de costos operativos:** las tareas de contenido dejan de requerir horas de desarrollo.
- **Autonomía:** el cliente es dueño de su operación digital y el soporte técnico se enfoca en mejoras de valor.

---

## 5. Despliegue y DevOps

### Paridad local / producción con Docker

- El entorno de desarrollo corre completamente en **contenedores Docker** (Laravel Sail): PHP, Composer, Node y servicios auxiliares viven dentro del contenedor.
- Se evita el clásico "en mi máquina funciona": las mismas versiones de runtime se usan en desarrollo y en producción.
- Flujo de trabajo en **WSL 2**, con alineación de usuario y permisos entre host y contenedor para mantener un repositorio limpio.

### Control de versiones con Git (GitHub Flow)

- Repositorio en **GitHub** con **GitHub Flow**: la rama `main` siempre contiene código estable y desplegable.
- Cada funcionalidad o corrección se desarrolla en una **rama propia** creada desde `main` y se integra mediante **Pull Request** una vez probada.
- Commits pequeños y descriptivos siguiendo **Conventional Commits** (`feat:`, `fix:`, `docs:`), lo que deja un historial legible y fácil de auditar.
- El historial permite rastrear cada cambio y revertirlo con seguridad si algo falla en producción.

### Administración de servidores VPS

- Configuración y mantenimiento de un servidor **Linux VPS** para alojar la plataforma.
- Programador de tareas (cron) para procesos automáticos, como la purga de datos según la política de retención.
- Compilación de assets de producción con Vite y configuración segura del entorno (modo debug desactivado, secretos fuera del código).
- Base de datos **MySQL** en producción con consultas independientes del motor, lo que permite usar SQLite en desarrollo.

---

<p align="center"><sub>Proyecto desarrollado y mantenido de forma privada. Disponible para conversar sobre la arquitectura y las decisiones técnicas en una entrevista.</sub></p>
