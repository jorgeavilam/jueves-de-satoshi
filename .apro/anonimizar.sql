-- Jueves de Satoshi — sitio personal del responsable: los datos se conservan tal cual.
-- acceso: /admin con el usuario de la tabla users (SELECT username FROM users)
-- Solo se reemplaza la contraseña de producción por la contraseña de prueba (@hash_prueba).
UPDATE users SET pass_hash = @hash_prueba;
