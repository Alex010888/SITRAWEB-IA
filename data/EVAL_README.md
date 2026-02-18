# Evaluación del Defensor Laboral IA

## Uso

```bash
php eval_defensor.php
```

O visitar: `http://localhost/sitra_web/eval_defensor.php`

## Archivos

- **eval_cases.json**: 28 preguntas con respuestas esperadas (Art./Cláusula que debe aparecer)
- **eval_defensor.php**: Script que ejecuta las consultas y mide precisión

## Métrica

**Precisión**: % de casos donde al menos un Artículo o Cláusula esperado aparece en los resultados de búsqueda.

- `expected_refs`: lista de refs (ej: ART-55, CLA-7). Si al menos una aparece → ✓
- `expect_no_relevant`: para consultas sin info (ej: teletrabajo). ✓ si no se devuelve ninguna ref

## Prioridad sugerida (según resultados)

| Prioridad | Acción | Esfuerzo | Impacto |
|-----------|--------|----------|---------|
| ~~Alta~~ | ~~Limpiar chunks ruidosos~~ | Bajo | Alto |
| ~~Alta~~ | ~~Ampliar patrones de consulta~~ | Bajo | Alto |
| **Alta** | **Mejorar bono/aguinaldo/salario** (priorizar CLA-52, 55, 56; ART-118, 196) | Medio | Alto |
| Media | Mejorar isNoiseChunk | Bajo | Medio |
| ~~Media~~ | ~~Añadir few-shot al prompt~~ | Bajo | Medio |
| Baja | Implementar embeddings (semántica) | Alto | Muy alto |

## Categorías con bajo rendimiento (baseline)

- **bono**: 0% — CLA-55, 56, 52 no aparecen en top resultados
- **aguinaldo**: 0% — ART-196, 197, CLA-55, 56
- **salario**: 0% — ART-118, 119, 120
- **horas_extras**: 0% — ART-168, 169, 170
- **sin_info**: consultas off-topic devuelven resultados (esperado: vacío)

## Cómo priorizar cambios

1. Ejecutar `eval_defensor.php` antes y después de cada cambio
2. Comparar precisión por categoría
3. Enfocarse en categorías con 0% o <50%
