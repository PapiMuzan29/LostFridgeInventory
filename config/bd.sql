CREATE TABLE Rol(
    idRol INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreRol ENUM('Administrador', 'Vendedor', 'Operador', 'Ayudante', 'Checador') NOT NULL UNIQUE,
    estado BIT
);

CREATE TABLE Cuenta(
    idCuenta INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idRol INT,
    FOREIGN KEY (idRol) REFERENCES Rol(idRol),
    apodoUsuario VARCHAR(60),
    nombreUsuario VARCHAR(60),
    apellidoPaternoUsuario VARCHAR(60),
    apellidoMaternoUsuario VARCHAR(60),
    contrasenaUsuario VARCHAR(60),
    estado BIT
);

CREATE TABLE Categoria(
    idCategoria INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreCategoria VARCHAR(60) NOT NULL UNIQUE,
    estado BIT
);

CREATE TABLE Ubicacion(
    idUbicacion INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreUbicacion VARCHAR(60) NOT NULL UNIQUE,
    estado BIT
);

CREATE TABLE Producto(
    idProducto INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreProducto VARCHAR(60),
    idCategoria INT NOT NULL,
    FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria),
    codigoProducto VARCHAR(100) NULL UNIQUE,
    activo BIT
);

CREATE TABLE Lote(
    idLote INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idProducto INT,
    FOREIGN KEY(idProducto) REFERENCES Producto(idProducto),
    idUbicacion INT,
    FOREIGN KEY(idUbicacion) REFERENCES Ubicacion(idUbicacion),
    codigoLote VARCHAR(50),
    fechaIngreso DATE,
    fechaEgreso DATE,
    pesoActual DECIMAL(18,4),
    fechaVencimiento DATE NULL,
    estadoCalidad ENUM('optimo', 'alerta', 'expirado') DEFAULT 'optimo',
    activo BIT
);

CREATE TABLE TransaccionesInventario(
    idInventario INT AUTO_INCREMENT PRIMARY KEY,
    idLote INT NOT NULL,
    FOREIGN KEY (idLote) REFERENCES Lote(idLote),
    tipoTransaccion ENUM('entrada', 'salida', 'ajuste', 'merma','transferencia'),
    cantidad INT NOT NULL,
    fechaTransaccion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Cliente(
	idCliente INT AUTO_INCREMENT PRIMARY KEY,
    nombreCliente VARCHAR(50),
    activo BIT
);

CREATE TABLE Notas(
	idNotas INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idCliente INT,
    FOREIGN KEY (idCliente) REFERENCES Cliente(idCliente),
    idCuenta INT,
    FOREIGN KEY (idCuenta) REFERENCES Cuenta(idCuenta),
    folioTicketCaja VARCHAR(250) NULL,
    fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente','revision','aprobada','rechazada','entregada') DEFAULT 'pendiente'
);

CREATE TABLE DetalleNotas(
	idDetalle INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idNotas INT NOT NULL,
    FOREIGN KEY (idNotas) REFERENCES Notas(idNotas),
    idProducto INT NULL,
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto),
    idLote INT NULL,
    FOREIGN KEY (idLote) REFERENCES Lote(idLote),
    cantidad INT NOT NULL,
    peso DECIMAL(18,4) NOT NULL
);

CREATE TABLE Mermas(
	idMerma INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idProducto INT NULL,
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto),
    loteId INT,
    FOREIGN KEY (loteId) REFERENCES Lote(loteId),
   	pesoMerma DECIMAL(18,4) NOT NULL,
    motivoMerma ENUM('caducado','dañado','error','descomposicion'),
    fechaMerma DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Documentos (
    idDocumento INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreOriginal VARCHAR(255) NOT NULL,
    nombreServidor VARCHAR(255) NOT NULL,
    rutaArchivo VARCHAR(500) NOT NULL,   
    tipoDocumento ENUM(
        'movimiento_inventario',
        'inventario_actual',
        'vencimiento',
        'movimientos_usuario',
        'utilizacion_ubicaciones'
    ) NOT NULL,
    tamanoBytes INT NOT NULL,             
    idCuenta INT NULL,                    
    FOREIGN KEY (idCuenta) REFERENCES Cuenta(idCuenta),
    fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo BIT DEFAULT 1
);

CREATE TABLE Auditoria (
    idAuditoria INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    idCuenta INT NULL,
    FOREIGN KEY (idCuenta) REFERENCES Cuenta(idCuenta) ON DELETE SET NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT') NOT NULL,
    tablaAfectada ENUM(
    'rol',
    'cuenta',
    'categoria',
    'ubicacion',
    'producto',
    'lote',
    'transaccionesInventario',
    'cliente',
    'notas',
    'detalleNotas',
    'mermas',
    'auditoria',
    'documentos'
) NOT NULL,
    idRegistroAfectado INT NOT NULL, 
    fechaAccion DATETIME DEFAULT CURRENT_TIMESTAMP 
);

