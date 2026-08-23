-- Jueves de Satoshi — Esquema de base de datos v2
--
-- Este archivo describe SIEMPRE la última versión del esquema y lo usa el
-- instalador (install.php). Una instalación nueva lo importa y marca todas las
-- migraciones como aplicadas. Para actualizar una instalación que ya existe NO
-- se usa este archivo: se usa upgrade.php, que aplica los pasos de migrations/.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Sin usuario precargado: lo crea el instalador con la contraseña del dueño.

CREATE TABLE IF NOT EXISTS years (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  year SMALLINT UNSIGNED NOT NULL UNIQUE,
  weekly_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- en la moneda del sitio
  planned_weeks TINYINT UNSIGNED NOT NULL DEFAULT 52,
  notes VARCHAR(500) DEFAULT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,              -- borrado lógico (recuperable)
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchases (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  year_id INT UNSIGNED NOT NULL,
  num SMALLINT UNSIGNED NOT NULL,                     -- número de compra dentro del año
  fecha DATE NOT NULL,
  monto_local DECIMAL(12,2) NOT NULL,                 -- monto bruto, moneda del sitio
  comision_pct DECIMAL(6,4) NOT NULL DEFAULT 0.0100,  -- 0.0200 = 2%
  precio_local_btc DECIMAL(18,2) NOT NULL,            -- precio de BTC al momento de la compra
  tipo_cambio_usd DECIMAL(12,6) NOT NULL DEFAULT 1,   -- se REGISTRA, el monto USD se CALCULA
  sats BIGINT UNSIGNED NOT NULL,                      -- sats recibidos en el wallet
  x_post_url VARCHAR(255) DEFAULT NULL,               -- link al post público de la compra
  notas VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_year_num (year_id, num),
  CONSTRAINT fk_purchase_year FOREIGN KEY (year_id) REFERENCES years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(60) NOT NULL,
  svalue TEXT NOT NULL,
  PRIMARY KEY (skey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Textos editables del sitio. is_default = 1 significa "sigue siendo la
-- plantilla": esos bloques se pintan traducidos desde lang/ y se marcan como
-- pendientes en el panel.
CREATE TABLE IF NOT EXISTS content (
  ckey VARCHAR(60) NOT NULL,
  cvalue TEXT NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (ckey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(40) NOT NULL,
  applied_at DATETIME NOT NULL,
  PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Defaults neutros. La identidad y la marca las escribe el instalador.
INSERT INTO settings (skey, svalue) VALUES
  ('installed', '0'),
  ('locale', 'es'),
  ('currency', 'MXN'),
  ('purchase_day', '4'),
  ('accent_color', '#F7931A'),
  ('skin', 'classic'),
  ('font_pair', 'system'),
  ('logo_mode', 'mono'),
  ('hero_vehicle', 'coin'),
  ('privacy_mode', 'full'),
  ('ejercicio_mode', 'link'),
  ('tool_exchange', 'none'),
  ('tool_wallet', 'none'),
  ('fallback_btc_usd', '60000'),
  ('fallback_usd_local', '17.5')
ON DUPLICATE KEY UPDATE svalue = svalue;
