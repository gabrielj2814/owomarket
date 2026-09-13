# Planes por hacer

Planes aprobados o en curso. **Al terminar uno, lo que se aprendió pasa a
[`../ESTADO_DEL_PROYECTO.md`](../ESTADO_DEL_PROYECTO.md) y el fichero se borra** — no se archiva.
La carpeta `implementados/` existió hasta el 13/09/2026 y acumuló 60 planes que nadie leía.

Antes de borrar uno, comprueba quién lo cita:

```bash
grep -rn "NOMBRE_DEL_PLAN" src/ resources/ tests/
```

---

## Lo que hay aquí

- **[PLANIFICACION_MODULOS_AVANZADOS_TENANT.md](PLANIFICACION_MODULOS_AVANZADOS_TENANT.md)** —
  seis módulos del backoffice del inquilino. Sin empezar; funcionalidad nueva, nada roto detrás.
- **[PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md](PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md)** — ya no
  es un plan sino un **runbook**: cómo reconstruir el entorno de desarrollo desde cero.

> Para saber por dónde seguir, no empieces aquí: empieza en
> [`../ESTADO_DEL_PROYECTO.md`](../ESTADO_DEL_PROYECTO.md).
