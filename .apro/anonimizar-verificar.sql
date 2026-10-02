-- Debe dar 0: ninguna contraseña de producción en local.
SELECT COUNT(*) AS pendientes FROM users WHERE pass_hash <> @hash_prueba;
