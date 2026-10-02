# Jueves de Satoshi — v2

Registro público de un hábito: comprar la misma cantidad de Bitcoin el mismo día
de cada semana, y documentarlo. PHP 8 puro + MySQL + Chart.js, pensado para
hosting compartido con cPanel.

- **Sitio maestro:** https://satoshi.jorgeavila.com
- **Licencia del código:** [MIT](./LICENSE)
- **Marca y atribución:** [BRAND.md](./BRAND.md) — el código es libre, el nombre no

## Qué cambia en la v2

Esta versión existe porque la primera instalación externa salió idéntica al sitio
maestro y firmada con el nombre de su autor. No era un problema de diseño sino de
arquitectura: la identidad vivía en un archivo que el usuario copiaba, y los
textos vivían escritos a mano dentro de las plantillas.

| | v1 | v2 |
|---|---|---|
| Identidad | Constantes en `config.php` | Base de datos, editable en el panel |
| Textos | Escritos en los `.php` | Bloques editables, con plantilla marcada como pendiente |
| Marca | Una sola | Día, nombre, color, fondo, 4 estilos, 4 tipografías, logo, vehículo |
| Moneda | MXN fijo | Una moneda por instalación, 10 opciones |
| Idioma | Español fijo | Español e inglés; un sitio puede publicar los dos, con selector, `?lang=` y `hreflang` |
| Instalación | FTP + phpMyAdmin a mano | Instalador web de 7 pasos |
| Actualización | — | `upgrade.php` con migraciones anotadas |
| Privacidad | Todo público | Abierto, solo porcentajes, o bóveda |
| Montaña rusa | Un dibujo fijo | Se dibuja con tu historial real de precios |

## Requisitos

- **PHP 7.4 o superior.** Recomendado 8.1+. El código evita a propósito la
  sintaxis exclusiva de PHP 8 (`match`, `str_contains`) para que corra en
  hostings compartidos que siguen en 7.4. Si tu servidor está por debajo, el
  sitio te lo dice en lugar de darte una pantalla en blanco.
- MySQL 5.7+ o MariaDB 10.2+, con una base de datos y su usuario.
- `mod_rewrite` y `.htaccess` habilitados (estándar en cPanel).

En cPanel la versión de PHP se cambia en **MultiPHP Manager**.

## Instalación

1. **Sube los archivos** de esta carpeta a la raíz de tu dominio o subdominio.
2. **Crea la base de datos** y su usuario desde tu panel de hosting (cPanel →
   MySQL Databases). No importes nada a mano.
3. **Abre `install.php`** en tu navegador y sigue los siete pasos: base de datos,
   administrador, identidad, marca, contenido, privacidad y red.
4. **Borra `install.php`** del servidor cuando termine.
5. Activa SSL y descomenta el bloque de HTTPS en `.htaccess`.

El instalador se niega a avanzar sin tu nombre y el nombre de tu sitio. Si algún
día los borras, el sitio público deja de publicar y muestra un aviso: el default
de una instalación a medias es el silencio, no los datos de otra persona.

## Actualización desde la v1

1. **Respalda la base de datos** desde tu panel de hosting.
2. Sube los archivos nuevos encima de los viejos. Conserva tu `config.php`.
3. Abre **`upgrade.php`**, entra con tu usuario de administrador y aplica los
   cambios. Renombra columnas y agrega tablas; no toca ninguna compra.
4. Entra a **Admin → Identidad** y escribe quién eres. La migración no hereda la
   identidad del código a propósito.
5. Pasa por **Admin → Marca** y ponle tu día, tu color y tu estilo.
6. Si quieres aparecer en el directorio público, entra a **Admin → Red** y pide tu
   lugar. El instalador lo ofrece en el paso 7, pero una actualización no manda
   nada sola: esta pantalla es el camino para cualquier instalación que ya existe.

## Estructura

```
├── index.php            Portada: acumulado, tarjetas por año, montaña rusa generada
├── year.php             Dashboard de un año: KPIs, gráficas, histórico
├── acerca.php           Tu versión del ejercicio (solo en modo "propio")
├── herramientas.php     Se arma con el exchange y el wallet que elegiste
├── contacto.php         Formulario con honeypot + reCAPTCHA v3 opcional
├── api.php              Agregado público opt-in, para el directorio de la red
├── logo.php             Monograma SVG generado con tus iniciales y tu color
├── robots.php           robots.txt con tu dirección
├── sitemap.php          Sitemap de las páginas que realmente publicas
├── red.php              Directorio de la red (solo donde está el módulo hub)
├── registro.php         Punto de registro de nodos (idem)
├── install.php          Instalador web — bórralo al terminar
├── upgrade.php          Actualizador de base de datos
├── admin/               Panel: compras, años, identidad, marca, contenido,
│                        herramientas, privacidad, red, contraseña
├── includes/            functions, layout, i18n, currencies, brandkit,
│                        tools, coaster, migrate, demo, setup, db
├── lang/                es.php, en.php
├── migrations/          Pasos de base de datos, anotados en schema_migrations
├── modules/             Punto de extensión (el módulo hub vive aparte)
├── assets/              css, js, img
├── tests/               smoke.php — pruebas de idioma, plantillas y geometría
├── config.example.php   Plantilla: solo secretos e infraestructura
└── schema.sql           Esquema de la última versión (lo usa el instalador)
```

## Reglas de negocio (no romper)

1. **Se registra el tipo de cambio; el monto en USD se calcula**
   (`monto_local / tipo_cambio_usd`). Es al revés del Excel original.
2. **Gráfica Inversión vs Valor:** el valor de cada fecha usa el precio de BTC de
   *esa* fecha, no el precio actual. Así la línea refleja la fluctuación real.
3. **Compras planeadas por año:** se calculan desde la fecha de la primera compra
   hasta el 31 de diciembre, contando el día de compra configurado. El campo
   manual solo aplica mientras el año está vacío.
4. **Precio en vivo:** CoinGecko sin API key, cacheado 10 minutos. Para las
   monedas que CoinGecko no cotiza, el precio local sale del USD por el tipo de
   cambio de respaldo del panel.
5. **Multi-año:** cada año tiene su propio monto semanal. La portada suma todo.
6. **Borrado de años = lógico** (`deleted`), recuperable. Las compras no se tocan.
7. **Cambiar de moneda no convierte** las compras ya registradas. Es a propósito:
   convertir historia con el tipo de cambio de hoy sería inventar datos.

## La montaña rusa

El riel no es un `path` escrito a mano: se genera con la serie de precios de BTC
de tus propias compras (`includes/coaster.php`). Precio alto, riel alto. Con más
de 16 compras se suaviza con una media móvil para que siga siendo montable, y
antes de la cuarta compra se muestra una estación con el carrito esperando.

Dos instalaciones no pueden tener el mismo riel salvo que hayan comprado los
mismos días al mismo precio. Y cambia cada semana.

## Módulos

`modules/` es un punto de extensión, documentado en `modules/README.md`. Un módulo es una carpeta con un `module.php`
que puede declarar entradas en el menú del panel y textos propios de idioma. No
hay bandera de configuración que lo active: un módulo existe o no existe en el
servidor.

El módulo `hub` —el directorio público de la red, el punto de registro y la cola
de aprobación— vive en un repositorio privado y por eso no viene aquí. Es lo que
distingue al sitio maestro de un nodo, y la distinción es de datos: aunque
alguien reescribiera el módulo, nacería con un directorio vacío.

## Seguridad

- SQL con prepared statements (PDO, sin emulación). Salida escapada con `e()`.
- CSRF en todos los formularios del panel. Contraseñas con `password_hash`.
- Cookie de sesión HttpOnly + SameSite=Lax + Secure bajo HTTPS.
- Pausa anti-fuerza-bruta en el login.
- Subida de imágenes validada por contenido, no por extensión. SVG con `<script>`
  o atributos `on*` se rechaza.
- HTML del contenido editable filtrado con lista blanca de etiquetas.
- `.htaccess` protege `config.php`, los `.sql`, `includes/`, `lang/`,
  `migrations/` y el módulo hub.

## Pruebas

```bash
php tests/smoke.php
```

Revisa que los archivos de idioma estén alineados, que cada plantilla de
contenido renderice en los dos idiomas y que la montaña rusa quepa en su lienzo
con cualquier serie de precios. Córrelo antes de tocar `lang/` o el riel.

## Entorno local de pruebas

```bash
mysql -u TU_USUARIO -e "CREATE DATABASE jds_test CHARACTER SET utf8mb4"
php -S localhost:8123 -t .
# abre http://localhost:8123/install.php
```
