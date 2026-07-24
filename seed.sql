-- Jueves de Satoshi — Datos iniciales: Año 2026 con las 13 compras del Excel
-- Importar DESPUÉS de schema.sql

SET NAMES utf8mb4;

INSERT INTO years (year, weekly_amount_mxn, planned_weeks, notes)
VALUES (2026, 1000.00, 42, 'Año 1 — inicio 19 de marzo de 2026. 42 jueves en el año.');

SET @y := (SELECT id FROM years WHERE year = 2026);

INSERT INTO purchases (year_id, num, fecha, monto_mxn, comision_pct, precio_mxn_btc, tipo_cambio, sats, x_post_url) VALUES
(@y,  1, '2026-03-19', 1000.00, 0.0100, 1252866.00, 17.6786, 79019, 'https://x.com/jorgeavilam/status/2034757270404219082'),
(@y,  2, '2026-03-26', 1000.00, 0.0132, 1252866.00, 17.3123, 80202, 'https://x.com/jorgeavilam/status/2037193374617211103'),
(@y,  3, '2026-04-02', 1000.00, 0.0132, 1188933.00, 17.3123, 82999, 'https://x.com/jorgeavilam/status/2039709470775710199'),
(@y,  4, '2026-04-09', 1000.00, 0.0132, 1246992.00, 17.3123, 79135, 'https://x.com/jorgeavilam/status/2042294462815043675'),
(@y,  5, '2026-04-16', 1000.00, 0.0132, 1291356.00, 17.3123, 76416, 'https://x.com/jorgeavilam/status/2044869402122699115'),
(@y,  6, '2026-04-23', 1000.00, 0.0196, 1354518.00, 17.2000, 72380, 'https://x.com/jorgeavilam/status/2047401006707712139'),
(@y,  7, '2026-04-30', 1000.00, 0.0196, 1336005.00, 17.2000, 73383, 'https://x.com/jorgeavilam/status/2050020857540391281'),
(@y,  8, '2026-05-07', 1000.00, 0.0196, 1385771.00, 17.2000, 70748, 'https://x.com/jorgeavilam/status/2052464708276392240'),
(@y,  9, '2026-05-14', 1000.00, 0.0200, 1393243.00, 17.1930, 70340, 'https://x.com/jorgeavilam/status/2054941476631531929'),
(@y, 10, '2026-05-21', 1000.00, 0.0200, 1340752.00, 17.1930, 73094, 'https://x.com/jorgeavilam/status/2057546367791243375'),
(@y, 11, '2026-05-28', 1000.00, 0.0200, 1263663.00, 17.5000, 77553, 'https://x.com/jorgeavilam/status/2060014417836663058'),
(@y, 12, '2026-06-04', 1000.00, 0.0200, 1112706.00, 17.1930, 88074, 'https://x.com/jorgeavilam/status/2062554758146068590'),
(@y, 13, '2026-06-11', 1000.00, 0.0200, 1096515.00, 17.3451, 89375, 'https://x.com/jorgeavilam/status/2065088048824521118');
