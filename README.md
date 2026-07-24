# Jueves de Satoshi — App Web

Sitio público + panel de administración para el ejercicio de acumulación semanal de Bitcoin.
PHP 8 puro + MySQL + Chart.js. Pensado para hosting con cPanel.

- **Sitio oficial:** https://satoshi.jorgeavila.com
- **Repositorio:** https://github.com/jorgeavilam/jueves-de-satoshi
- **Licencia:** [MIT](./LICENSE) — © Jorge Avila Meléndez

## Estructura

```
app/
├── index.php          ← Dashboard público (acumulado global + dashboard por año + histórico)
├── acerca.php         ← Descripción del ejercicio (basada en el post inicial de X)
├── herramientas.php   ← Aureo Bitcoin, Wallet of Satoshi
├── contacto.php       ← Formulario de contacto (email oculto, honeypot + reCAPTCHA v3 opcional)
├── admin/             ← Panel protegido con usuario/contraseña
│   ├── index.php      ← Login + listado de compras (editar / borrar)
│   ├── compra.php     ← Registrar/editar compra (captura TC, calcula USD)
│   └── anio.php       ← Gestión de años (monto semanal por año)
├── includes/          ← db.php, functions.php, layout.php
├── assets/            ← CSS, JS, logo SVG, avatar
├── config.example.php ← Plantilla de configuración
├── schema.sql         ← Esquema de la base de datos
└── seed.sql           ← Año 2026 con las 13 compras del Excel
```

## Instalación en cPanel

1. **Base de datos**: en cPanel → MySQL Databases, crea una BD y un usuario con todos los privilegios.
2. **Importar**: en phpMyAdmin, importa `schema.sql` y luego `seed.sql`.
3. **Configurar**: copia `config.example.php` como `config.php` y llena:
   - Credenciales de la BD.
   - `SITE_URL` con tu subdominio (ej. `https://satoshi.jorgeavila.com`).
   - `CONTACT_FROM`: crea esa cuenta de correo en cPanel para que `mail()` funcione bien.
   - El acceso al admin viene en la base de datos: usuario `jorge`, contraseña `cambiame123`.
     **Cámbiala al primer ingreso** en Admin → Contraseña (no se necesita terminal).
4. **Subir archivos**: sube todo el contenido de `app/` a la raíz del subdominio (ej. `public_html/satoshi/`).
5. **SSL**: activa AutoSSL en cPanel y descomenta el bloque de HTTPS en `.htaccess`.
6. Entra a `/admin/` y registra tu siguiente compra cada jueves. 🚀

## Decisiones de diseño (correcciones vs el Excel)

1. **Tipo de cambio se registra, USD se calcula** — en el formulario de compra capturas el TC del día
   y el monto en USD se deriva (`monto_mxn / tipo_cambio`). El formulario muestra el cálculo en vivo.
2. **Gráfica Inversión vs Valor corregida** — el valor acumulado de cada fecha se calcula con
   `sats_acumulados × precio_BTC_de_ESA_fecha`, no con el precio actual. Así la línea naranja
   refleja la fluctuación real (las caídas y las subidas del recorrido).

## Precio en vivo

El sitio consulta CoinGecko (gratis, sin API key) y cachea el resultado 10 minutos en la tabla
`settings`. Si la API falla, usa el último valor cacheado o los valores `fallback_*` de `settings`.

## Tracking

En `config.php` hay espacio para GA4, Google Tag Manager, Meta Pixel y reCAPTCHA v3.
Déjalos vacíos (`''`) para desactivarlos; al llenar el ID se inyectan automáticamente.

## Años futuros

En Admin → Años crea el 2027 (con su nuevo monto semanal si cambia). La portada mostrará
el acumulado total de todos los años y una tarjeta por año con su propio dashboard.
