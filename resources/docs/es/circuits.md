# Circuitos de Tramitación, Firma Electrónica y Sede de Cotejo (CSV / QR)

**sientia.MTX** incorpora un motor completo de gobernanza colegiada y tramitación electrónica administrativa, diseñado para dotar a las propuestas, documentos y decisiones de plena validez jurídica e institucional con arreglo a la **Ley 39/2015 del Procedimiento Administrativo Común** y el **Reglamento eIDAS (UE 910/2014)**.

---

## 🏛️ 1. Concepto y Arquitectura de Circuitos

Un **Circuito de Tramitación** es un flujo secuencial o estructurado de validación, informe y resolución oficial aplicable a cualquier elemento del sistema (**Actividades**, documentos, expedientes o propuestas).

```
   [Propuesta / Documento]
              │
              ▼
    Etapa 1: Visto Bueno Inicial ───────► Conformidad CSV / Firma
              │
              ▼
    Etapa 2: Justificación Técnica ─────► Memoria y Acreditación
              │
              ▼
    Etapa 3: Aprobación Definitiva ─────► Firma AutoFirma (PAdES) / Sello
              │
              ▼
    [Diligencia Consolidada PDF] ───────► CSV + QR + Publicación en Tablón
```

### Características Principales
- **Asignación Granular por Roles**: Las etapas pueden encomendarse a usuarios concretos, a coordinadores, al responsable del elemento o a roles institucionales (ej. Asesoría Jurídica, Intervención, Dirección).
- **Control de Decisiones**: Cada participante puede **Aprobar**, **Devolver para subsanación** (indicando a qué etapa regresar y observaciones) o **Denegar / Rechazar**.
- **Inmutabilidad y Auditoría**: Registro de IP, User-Agent, sello de tiempo ISO 8601, huella SHA-256 de las evidencias y comentarios registrados.

---

## ✍️ 2. Modalidades de Firma y Acreditación

El sistema ofrece una arquitectura dual de firma adaptada a la naturaleza de cada trámite:

| Modalidad | Ámbito de Aplicación | Mecanismo Técnico | Validez |
|---|---|---|---|
| **Firma Electrónica AutoFirma (PAdES)** | Resoluciones, actos administrativos, aprobaciones formales definitivas | Certificado digital X.509 de empleado público o persona física mediante el cliente nativo AutoFirma | Firma electrónica reconocida / cualificada (Art. 10 Ley 39/2015) |
| **Conformidad Acreditada (CSV)** | Vistos buenos técnicos, revisiones internas, memorias justificativas | Sellado determinista con Código Seguro de Verificación (CSV), huella SHA-256 y trazabilidad de sesión segura | Validez interna acreditada y cotejable (Art. 27 Ley 39/2015) |
| **Firma Manual / Externa** | Contingencia si no se dispone de cliente local AutoFirma | Descarga de la diligencia previa, firma externa en visor PDF y subida del documento firmado | Preserva la firma PAdES del archivo aportado |

---

## 🔒 3. Generación y Sincronización de Diligencias Oficiales

Cada tramitación genera de forma dinámica un documento oficial consolidado en PDF con la diligencia de tramitación:

1. **Cabecera Institucional**: Con escudo, identificador del expediente, equipo/organismo emisor, fecha de inicio y **Código Seguro de Verificación (CSV)** en formato canónico (ej. `CSV-TRM-PEKW-000006-TASW`).
2. **Contenido Íntegro**: Memoria o propuesta original formateada en Markdown / HTML.
3. **Tabla de Itinerario**: Resumen tabular de todas las fases, roles intervinientes, estados (`Aprobado`, `En curso`, `Devuelto`) y estado de firma (`✓ Firmado`).
4. **Cajetines Oficiales de Firma con Código QR**:
   - Recuadros dedicados para cada etapa con código de colores verde institucional.
   - Datos del firmante, cargo, correo y sello de tiempo exacto.
   - **Huella Criptográfica SHA-256 completa** de 64 caracteres formateada en tipografía monoespaciada con protección contra desbordamiento.
   - **Código QR dinámico integrado** que enlaza directamente a la URL pública de verificación del documento.
5. **Sincronización Anticipada (`for_step`)**: Cuando un usuario accede al portal de AutoFirma, la diligencia se pre-sincroniza reflejando la etapa actual como aprobada, de manera que el PDF que viaja y sella AutoFirma coincide al 100% con el estado final del trámite.

---

## 🌐 4. Sede Electrónica de Verificación y Cotejo (Pública)

Cualquier ciudadano, organismo o auditor que reciba una diligencia impresa o electrónica de **sientia.MTX** puede verificar su autenticidad sin necesidad de disponer de cuenta en el sistema:

- **Ruta Pública**: `/verificar/{csv?}`
- **Lectura por QR**: Al escanear el código QR del cajetín desde cualquier smartphone o tableta, se abre inmediatamente el certificado de cotejo con los datos de la tramitación, los firmantes y los hashes.
- **Búsqueda Manual**: Permite introducir el código CSV para contrastar los datos.
- **Descarga de Copia Auténtica**: Acceso directo (`/verificar/{csv}/descargar`) a la copia electrónica auténtica custodiada en el repositorio oficial.

---

## 📢 5. Tablón Edictal Electrónico

Las tramitaciones concluidas pueden ser publicadas en el **Tablón Edictal Público**:
- Asignación de código edictal oficial (ej. `EDICTO-2026-0001`).
- Plazo de exposición pública con fecha de vigencia y expiración.
- Descarga pública de la diligencia oficial firmada para transparencia ciudadana.
