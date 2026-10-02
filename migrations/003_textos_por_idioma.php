<?php
/**
 * Textos editables en más de un idioma.
 *
 * `content` sigue guardando el texto en el idioma principal del sitio, sin
 * cambios. Las traducciones van en una tabla aparte: así ninguna instalación
 * existente cambia de comportamiento al actualizar, y un bloque sin traducir
 * cae a su texto original en lugar de a la plantilla genérica.
 */
mig_run('CREATE TABLE IF NOT EXISTS content_tr (
  ckey VARCHAR(60) NOT NULL,
  locale VARCHAR(5) NOT NULL,
  cvalue TEXT NOT NULL,
  PRIMARY KEY (ckey, locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
