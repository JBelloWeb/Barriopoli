# Barriopoli

Mapa interactivo de los barrios de CABA con la actividad cultural, los eventos y los emprendimientos de cada uno. Proyecto final de la Tecnicatura en Diseño y Programación Web.

**Stack:** Laravel 13 + Inertia.js 3 + Vue 3 (JavaScript) · MySQL 8 (consultas espaciales) · MapLibre GL + Turf.js + Three.js · Tailwind CSS 4 · Fortify (auth) · Pest 4.

Credenciales de demo y datos de entrega: [`datos.txt`](datos.txt). API: [`docs/api-v1.http`](docs/api-v1.http).

## Requisitos

- PHP 8.3 con `pdo_mysql`
- Composer 2
- Node 20.19+ (o 22+)
- **MySQL 8**. No sirve MariaDB: la detección del barrio de cada evento usa `ST_Contains` con SRID 4326 y `axis-order=long-lat`.

## Instalación

```bash
composer install
npm ci
npm run build

cp .env.example .env        # completar DB_* y MODERADOR_PASSWORD
php artisan key:generate
```

Crear la base (vacía):

```sql
CREATE DATABASE barriopoli CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Cargar los datos, con **una** de estas dos opciones:

- **A. Migraciones y seeders:** los 48 barrios, las categorías y el moderador de `.env`, más los usuarios y eventos de demo.
  ```bash
  php artisan migrate --seed
  php artisan db:seed --class=DemoSeeder
  ```
- **B. Dump autocontenido:** mismo resultado, ya armado.
  ```bash
  mysql -u root barriopoli < database/barriopoli.sql
  ```

Terminar y levantar:

```bash
php artisan storage:link
php artisan serve           # http://127.0.0.1:8000
```

En desarrollo, `composer run dev` levanta el servidor y Vite juntos.

## Tests

Los tests usan una base MySQL propia, `barriopoli_test` (ver `phpunit.xml`), que hay que crear vacía:

```sql
CREATE DATABASE barriopoli_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan test --compact
```

No uses `php artisan config:cache` en desarrollo: con la configuración cacheada, los tests (`RefreshDatabase`) toman la base real.

## Producción

- Generar un `APP_KEY` propio (`php artisan key:generate`).
- Servir **solo por HTTPS**: la API acepta HTTP Basic, que manda email y contraseña en cada request.
- `APP_ENV=production`, `APP_DEBUG=false`, y un `MAIL_MAILER` real para la verificación de email y la recuperación de contraseña.
