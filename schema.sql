-- Jueves de Satoshi — Esquema de base de datos (MySQL 5.7+ / MariaDB)
-- Importar desde phpMyAdmin en cPanel.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario inicial: jorge / cambiame123  (¡cambia la contraseña desde el admin!)
INSERT INTO users (username, pass_hash) VALUES
  ('jorge', '$2y$12$3lUqANEbnYdEFeJKLEMmK.GXQXKRpEgL8oBG8UeSmGyUFNTrr1se.')
ON DUPLICATE KEY UPDATE username = username;

CREATE TABLE IF NOT EXISTS years (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  year SMALLINT UNSIGNED NOT NULL UNIQUE,
  weekly_amount_mxn DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
  planned_weeks TINYINT UNSIGNED NOT NULL DEFAULT 52,
  notes VARCHAR(500) DEFAULT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0, -- borrado lógico (recuperable)
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchases (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  year_id INT UNSIGNED NOT NULL,
  num SMALLINT UNSIGNED NOT NULL,            -- número de compra dentro del año
  fecha DATE NOT NULL,
  monto_mxn DECIMAL(10,2) NOT NULL,          -- monto bruto de la compra
  comision_pct DECIMAL(6,4) NOT NULL DEFAULT 0.0100, -- ej. 0.0200 = 2%
  precio_mxn_btc DECIMAL(14,2) NOT NULL,     -- precio BTC en MXN al momento de la compra
  tipo_cambio DECIMAL(8,4) NOT NULL,         -- USD/MXN: se REGISTRA y el USD se CALCULA
  sats BIGINT UNSIGNED NOT NULL,             -- sats recibidos en el wallet
  x_post_url VARCHAR(255) DEFAULT NULL,      -- link al post de la compra en X
  notas VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_year_num (year_id, num),
  CONSTRAINT fk_purchase_year FOREIGN KEY (year_id) REFERENCES years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(50) NOT NULL,
  svalue VARCHAR(500) NOT NULL,
  PRIMARY KEY (skey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valores manuales de respaldo del precio (se usan si la API de CoinGecko no responde)
INSERT INTO settings (skey, svalue) VALUES
  ('fallback_btc_usd', '62584.75'),
  ('fallback_usd_mxn', '17.389')
ON DUPLICATE KEY UPDATE svalue = svalue;
