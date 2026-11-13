# Snippets

> **Nota:** estos son fragmentos sanitizados de un proyecto privado en producción. Se recortaron para mostrar decisiones concretas (los recortes se marcan con `// ...`), así que **no son ejecutables por sí solos**. Los nombres de marca, de modelos y los datos de prueba se reemplazaron por placeholders.

Stack: Laravel 12 · PHP 8.2 · React 18 + Inertia v2 · Filament v3 · Tailwind CSS 3 · PHPUnit.

| Archivo | Qué muestra | Decisión técnica |
|---|---|---|
| `backend/Requests/StoreAppointmentRequest.php` | Validación del formulario público de asesoría | Región y comuna se validan contra la BD y la comuna debe pertenecer a la región; el `<select>` no es una barrera de seguridad |
| `backend/Policies/PrivacyRequestPolicy.php` | Autorización de las solicitudes ARCOP en el panel | Crear y borrar se niegan explícitamente; las solicitudes se conservan como registro |
| `backend/Models/Appointment.php` | Modelo con datos personales | Cifrado en reposo con el cast `encrypted` y `$fillable` explícito |
| `backend/Console/RetentionPruning.php` | Purga por retención y su registro en el scheduler | `Prunable` por modelo + `model:prune` diario, sin comando propio |
| `backend/Http/PrivacyRequestController.php` | Verificación de identidad con enlace firmado que expira | `URL::temporarySignedRoute`, respuesta uniforme contra enumeración y estado que solo avanza |
| `backend/Http/routes.php` | Rate limiting de formularios públicos | `throttle` por ruta con límites según el riesgo de cada formulario |
| `backend/Http/ProgramController.php` | Controlador Inertia con minimización de datos | Columnas explícitas también en las relaciones con eager loading; nunca el modelo completo |
| `backend/Filament/PrivacyRequestResource.php` | Resource con estado de seguimiento | Datos del titular en solo lectura y estado bloqueado hasta verificar la identidad |
| `frontend/faq-assistant/FaqAssistant.jsx` | Asistente FAQ como árbol de decisión | Flujo modelado como datos en el cliente, sin backend; cada respuesta final lleva al agendamiento |
| `tests/AppointmentTest.php` | Pruebas de validación y rate limit | Se prueba por la ruta HTTP real con entradas manipuladas y se verifica que no se guarde nada |
| `tests/PrivacyRequestTest.php` | Pruebas del enlace firmado y de la autorización | Firma reutilizada, enlace vencido y usuario autenticado sin permiso (403) |
