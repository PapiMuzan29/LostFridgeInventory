
-- ------------------------------------------------------------
-- 1. Rol
-- ------------------------------------------------------------
CREATE TABLE Rol (
    idRol     INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreRol ENUM('Administrador','Vendedor','Operador','Ayudante','Checador') NOT NULL UNIQUE,
    estado    TINYINT(1) DEFAULT 1
);

-- ------------------------------------------------------------
-- 2. Cuenta  (depende de: Rol)
-- ------------------------------------------------------------
CREATE TABLE Cuenta (
    idCuenta               INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idRol                  INT,
    apodoUsuario           VARCHAR(60),
    nombreUsuario          VARCHAR(60),
    apellidoPaternoUsuario VARCHAR(60),
    apellidoMaternoUsuario VARCHAR(60),
    contrasenaUsuario      VARCHAR(60),
    estado                 TINYINT(1) DEFAULT 1,
    FOREIGN KEY (idRol) REFERENCES Rol(idRol)
);

-- ------------------------------------------------------------
-- 3. Proveedor  (sin dependencias)
-- ------------------------------------------------------------
CREATE TABLE Proveedor (
    idProveedor                    INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    codigoProveedor                VARCHAR(150),
    nombreProveedor                VARCHAR(60),
    rfc                            VARCHAR(20),
    direccion                      VARCHAR(250),
    colonia                        VARCHAR(20),
    codigoPostal                   VARCHAR(20),
    estadoRepublica                VARCHAR(100),
    status                         TINYINT(1) DEFAULT 1,
    codigoBarrasProductosPosicion  INT NOT NULL,
    codigoBarrasProductosLongitud  INT NOT NULL,
    codigoBarrasEnterosPosicion    INT NOT NULL,
    codigoBarrasEnterosLongitud    INT NOT NULL,
    codigoBarrasDecimalesPosicion  INT NOT NULL,
    codigoBarrasDecimalesLongitud  INT NOT NULL
);

-- ------------------------------------------------------------
-- 4. Categoria  (sin dependencias)
-- ------------------------------------------------------------
CREATE TABLE Categoria (
    idCategoria     INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreCategoria VARCHAR(60) NOT NULL UNIQUE,
    estado          TINYINT(1) DEFAULT 1
);

-- ------------------------------------------------------------
-- 5. Almacenes  (sin dependencias)
-- ------------------------------------------------------------
CREATE TABLE almacenes (
    id_almacen INT AUTO_INCREMENT PRIMARY KEY,
    clave      VARCHAR(5)   NOT NULL UNIQUE,
    nombre     VARCHAR(100) NOT NULL,
    activo     TINYINT(1)   NOT NULL DEFAULT 1
);

-- ------------------------------------------------------------
-- 6. Conceptos de entrada  (sin dependencias)
-- ------------------------------------------------------------
CREATE TABLE conceptos_entrada (
    id_concepto INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    activo      TINYINT(1)  NOT NULL DEFAULT 1
);

-- ------------------------------------------------------------
-- 7. Ubicacion  (depende de: almacenes)
--    tipoUbicacion distingue el almacen intermedio de las camaras
-- ------------------------------------------------------------
CREATE TABLE Ubicacion (
    idUbicacion     INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idAlmacen       INT NOT NULL,
    nombreUbicacion VARCHAR(60) NOT NULL UNIQUE,
    tipoUbicacion   ENUM('intermedio','camara') NOT NULL DEFAULT 'camara',
    estado          TINYINT(1) DEFAULT 1,
    FOREIGN KEY (idAlmacen) REFERENCES almacenes(id_almacen)
);

-- ------------------------------------------------------------
-- 8. Rack  (depende de: Ubicacion)
--    Se deja preparada para uso futuro; idRack en Lote es NULL mientras no haya racks
-- ------------------------------------------------------------
CREATE TABLE Rack (
    idRack        INT AUTO_INCREMENT PRIMARY KEY,
    idUbicacion   INT NOT NULL,
    nombreRack    VARCHAR(60) NOT NULL,
    activo        TINYINT(1) DEFAULT 1,
    FOREIGN KEY (idUbicacion) REFERENCES Ubicacion(idUbicacion)
);

-- ------------------------------------------------------------
-- 9. Producto  (depende de: Proveedor, Categoria)
-- ------------------------------------------------------------
CREATE TABLE Producto (
    idProducto      INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    codigoProducto  VARCHAR(150),
    idProveedor     INT,
    idCategoria     INT NOT NULL,
    nombreProducto  VARCHAR(60),
    activo          TINYINT(1) DEFAULT 1,
    FOREIGN KEY (idProveedor) REFERENCES Proveedor(idProveedor),
    FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria)
);

-- ------------------------------------------------------------
-- 10. Entradas  (depende de: almacenes, conceptos_entrada, Proveedor, Cuenta)
-- ------------------------------------------------------------
CREATE TABLE entradas (
    id_entrada          INT AUTO_INCREMENT PRIMARY KEY,
    folio               INT AUTO_INCREMENT NOT NULL UNIQUE,
    fecha_captura       DATETIME       DEFAULT CURRENT_TIMESTAMP,
    id_concepto         INT            NULL,
    id_almacen          INT            NOT NULL,
    id_proveedor        INT            NULL,
    status              ENUM('A','C')  NOT NULL DEFAULT 'A',
    id_usuario          INT            NOT NULL,
    totalKgs            DECIMAL(12,3)  NULL DEFAULT 0,
    fecha_hora_registro DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_almacen)   REFERENCES almacenes(id_almacen),
    FOREIGN KEY (id_concepto)  REFERENCES conceptos_entrada(id_concepto),
    FOREIGN KEY (id_proveedor) REFERENCES Proveedor(idProveedor),
    FOREIGN KEY (id_usuario)   REFERENCES Cuenta(idCuenta)
);

-- ------------------------------------------------------------
-- 11. Entradas detalle  (depende de: entradas, Producto)
-- ------------------------------------------------------------
CREATE TABLE entradas_detalle (
    id_detalle  INT AUTO_INCREMENT PRIMARY KEY,
    id_entrada  INT            NOT NULL,
    partida     INT            NOT NULL,
    id_producto INT            NOT NULL,
    liberado    TINYINT(1)     NOT NULL DEFAULT 1,
    cantidad    DECIMAL(12,3)  NOT NULL,
    kgs         DECIMAL(12,3)  NULL DEFAULT 0,
    FOREIGN KEY (id_entrada)  REFERENCES entradas(id_entrada),
    FOREIGN KEY (id_producto) REFERENCES Producto(idProducto)
);

-- ------------------------------------------------------------
-- 12. Lote  (depende de: Producto, Ubicacion, entradas_detalle, Rack)
--    idEntradaDetalle  -> trazabilidad: qué entrada generó este lote
--    idRack  A          -> NULL por ahora, se llena cuando existan racks físicos
-- ------------------------------------------------------------
CREATE TABLE Lote (
    idLote           INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idProducto       INT,
    idUbicacion      INT,
    idEntradaDetalle INT  NULL,
    idRack           INT  NULL,
    codigoLote       VARCHAR(50),
    fechaIngreso     DATE,
    fechaEgreso      DATE,
    pesoActual       DECIMAL(18,4),
    fechaVencimiento DATE NULL,
    estadoCalidad    ENUM('optimo','alerta','expirado') DEFAULT 'optimo',
    activo           TINYINT(1) DEFAULT 1,
    FOREIGN KEY (idProducto)       REFERENCES Producto(idProducto),
    FOREIGN KEY (idUbicacion)      REFERENCES Ubicacion(idUbicacion),
    FOREIGN KEY (idEntradaDetalle) REFERENCES entradas_detalle(id_detalle),
    FOREIGN KEY (idRack)           REFERENCES Rack(idRack)
);

-- ------------------------------------------------------------
-- 13. TransaccionesInventario  (depende de: Lote)
--    Bitácora de todos los movimientos; el estado actual vive en Lote
-- ------------------------------------------------------------
CREATE TABLE TransaccionesInventario (
    idInventario     INT AUTO_INCREMENT PRIMARY KEY,
    idLote           INT           NOT NULL,
    tipoTransaccion  ENUM('entrada','salida','ajuste','merma','transferencia'),
    cantidad         DECIMAL(12,3) NOT NULL,
    fechaTransaccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idLote) REFERENCES Lote(idLote)
);

-- ------------------------------------------------------------
-- 14. Cliente  (sin dependencias)
-- ------------------------------------------------------------
CREATE TABLE Cliente (
    idCliente     INT AUTO_INCREMENT PRIMARY KEY,
    nombreCliente VARCHAR(50),
    activo        TINYINT(1) DEFAULT 1
);

-- ------------------------------------------------------------
-- 15. Notas  (depende de: Cliente, Cuenta)
-- ------------------------------------------------------------
CREATE TABLE Notas (
    idNotas         INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idCliente       INT,
    idCuenta        INT,
    folioTicketCaja VARCHAR(250) NULL,
    fechaCreacion   DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado          ENUM('pendiente','revision','aprobada','rechazada','entregada') DEFAULT 'pendiente',
    FOREIGN KEY (idCliente) REFERENCES Cliente(idCliente),
    FOREIGN KEY (idCuenta)  REFERENCES Cuenta(idCuenta)
);

-- ------------------------------------------------------------
-- 16. DetalleNotas  (depende de: Notas, Producto, Lote)
-- ------------------------------------------------------------
CREATE TABLE DetalleNotas (
    idDetalle INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idNotas   INT            NOT NULL,
    idProducto INT           NULL,
    idLote    INT            NULL,
    cantidad  INT            NOT NULL,
    peso      DECIMAL(18,4)  NOT NULL,
    FOREIGN KEY (idNotas)    REFERENCES Notas(idNotas),
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto),
    FOREIGN KEY (idLote)     REFERENCES Lote(idLote)
);

-- ------------------------------------------------------------
-- 17. Mermas  (depende de: Producto, Lote)
-- ------------------------------------------------------------
CREATE TABLE Mermas (
    idMerma      INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idProducto   INT NULL,
    loteId       INT,
    pesoMerma    DECIMAL(18,4) NOT NULL,
    motivoMerma  ENUM('caducado','dañado','error','descomposicion'),
    fechaMerma   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto),
    FOREIGN KEY (loteId)     REFERENCES Lote(idLote)
);

-- ------------------------------------------------------------
-- 18. Documentos  (depende de: Cuenta)
-- ------------------------------------------------------------
CREATE TABLE Documentos (
    idDocumento    INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreOriginal VARCHAR(255) NOT NULL,
    nombreServidor VARCHAR(255) NOT NULL,
    rutaArchivo    VARCHAR(500) NOT NULL,
    nombreDocumento ENUM(
        'movimiento_inventario',
        'inventario_actual',
        'vencimiento',
        'movimientos_usuario',
        'utilizacion_ubicaciones'
    ) NOT NULL,
    tipoDocumento ENUM(
        'Inventario',
        'Alertas',
        'Auditoria'
    ) NOT NULL,
    tamanoBytes      INT      NOT NULL,
    idCuenta         INT      NULL,
    fechaCreacion    DATETIME DEFAULT CURRENT_TIMESTAMP,
    fechaFinalizacion DATETIME NULL,
    activo           TINYINT(1) DEFAULT 1,
    FOREIGN KEY (idCuenta) REFERENCES Cuenta(idCuenta)
);

-- ------------------------------------------------------------
-- 19. Auditoria  (depende de: Cuenta)
-- ------------------------------------------------------------
CREATE TABLE Auditoria (
    idAuditoria      INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idCuenta         INT NULL,
    accion           ENUM('INSERT','UPDATE','DELETE','LOGIN','LOGOUT') NOT NULL,
    tablaAfectada    ENUM(
        'rol','cuenta','categoria','ubicacion','producto',
        'lote','transaccionesInventario','cliente','notas',
        'detalleNotas','mermas','auditoria','documentos',
        'almacenes','entradas','entradas_detalle','rack'
    ) NOT NULL,
    idRegistroAfectado INT      NOT NULL,
    fechaAccion        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idCuenta) REFERENCES Cuenta(idCuenta) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- 20. Vista de inventario actual  (reemplaza la tabla Inventario)
--    Calcula stock en tiempo real desde Lote; nunca se desincroniza
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vista_inventario AS
SELECT
    p.idProducto,
    p.nombreProducto,
    a.id_almacen                  AS idAlmacen,
    a.nombre                      AS nombreAlmacen,
    u.idUbicacion,
    u.nombreUbicacion,
    u.tipoUbicacion,
    COUNT(l.idLote)               AS totalLotes,
    SUM(l.pesoActual)             AS pesoDisponible
FROM Lote          l
JOIN Producto      p ON l.idProducto  = p.idProducto
JOIN Ubicacion     u ON l.idUbicacion = u.idUbicacion
JOIN almacenes     a ON u.idAlmacen   = a.id_almacen
WHERE l.activo = 1
GROUP BY p.idProducto, u.idUbicacion;

SET FOREIGN_KEY_CHECKS = 1;
