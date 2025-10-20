# Arquitectura del Sistema de Reservación AIP

A continuación se presenta un diagrama Mermaid que resume la estructura del sistema.

```mermaid
graph TB
  subgraph Roles[ ]
    A[Administrador]
    E[Encargado]
    P[Profesor]
  end

  A --> SW
  E --> SW
  P --> SW

  subgraph SW[Sistema Web de Reservación AIP]
    D[Dashboard]
    CAL[Calendarios\n(Aulas AIP)]
    CEQ[Calendario de Préstamos\n(Equipos)]
    RF[Reportes y Filtros\n(PDF/CSV)]
    GE[Gestión de Entidades\n(Aulas, Equipos, Usuarios)]
    INC[Incidentes / Cancelaciones]
    CFG[Configuración]
  end

  SW -->|Generación PDF| PDF[DomPDF]
  SW -->|Notificaciones| MAIL[Mailer/Email]

  SW --> DB[(Base de Datos MySQL)]

  subgraph DB[(Base de Datos MySQL)]
    U[(usuarios)]
    AU[(aulas)]
    R[(reservas)]
    RC[(reservas_canceladas)]
    EQ[(equipos)]
    PR[(prestamos)]
    PK[(prestamos_pack)]
    PKI[(prestamos_pack_items)]
    OBS[(observaciones)]
    LOG[(logs/auditoría)]
  end

  classDef role fill:#d9f7be,stroke:#8c8c8c,color:#333;
  class A,E,P role
  classDef api fill:#fff2cc,stroke:#8c8c8c,color:#333;
  class PDF,MAIL api
  classDef web fill:#cfe2ff,stroke:#8c8c8c,color:#0b3d91;
  class SW,D,CAL,CEQ,RF,GE,INC,CFG web
  classDef db fill:#e8e8e8,stroke:#8c8c8c,color:#333;
  class DB,U,AU,R,RC,EQ,PR,PK,PKI,OBS,LOG db
```

## Notas rápidas
- Exporta a PNG/SVG copiando el bloque en https://mermaid.live
- Puedes eliminar este archivo cuando termines de revisarlo.
