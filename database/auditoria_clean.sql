-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 08-10-2018 a las 17:21:35
-- Versión del servidor: 5.6.39-cll-lve
-- Versión de PHP: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `auditoria_gruma`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuestionarios`
--

CREATE TABLE `cuestionarios` (
  `id_cuestionario` int(20) NOT NULL,
  `id_empresa` int(20) NOT NULL,
  `id_usuario` int(20) NOT NULL,
  `codigo` varchar(60) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `status` int(1) NOT NULL,
  `fecha_alta` datetime NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuestionarios_respondidos`
--

CREATE TABLE `cuestionarios_respondidos` (
  `id_cuestionario_respondido` int(20) NOT NULL,
  `id_cuestionario` int(20) NOT NULL,
  `id_cliente` int(20) NOT NULL,
  `id_auditor` int(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `fecha_de_inicio` date NOT NULL,
  `coordenadas_inicio` varchar(60) DEFAULT NULL,
  `hora_finalizacion` time DEFAULT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `coordenadas_finalizacion` varchar(60) DEFAULT NULL,
  `hora_limite` time DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `estado` int(1) NOT NULL,
  `liberado` int(1) DEFAULT '0',
  `cerrar_replicas` int(1) NOT NULL DEFAULT '0',
  `fecha_auditoria` date DEFAULT NULL,
  `fecha_alta` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_replicas`
--

CREATE TABLE `detalle_replicas` (
  `id_detalle_replica` int(20) NOT NULL,
  `id_replica` int(20) NOT NULL,
  `id_usuario` int(20) NOT NULL,
  `comentario` varchar(500) NOT NULL DEFAULT '',
  `evidencia` varchar(200) NOT NULL,
  `fecha_alta` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id_empresa` int(11) NOT NULL,
  `nombre_comercial` varchar(45) DEFAULT NULL,
  `razon_social` varchar(75) DEFAULT NULL,
  `telefono` varchar(10) DEFAULT NULL,
  `calle_numero` varchar(60) DEFAULT NULL,
  `colonia` varchar(45) DEFAULT NULL,
  `delegacion` varchar(45) DEFAULT NULL,
  `estado` varchar(45) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `id_tipo_empresa` int(2) DEFAULT NULL,
  `logotipo` varchar(180) NOT NULL,
  `fecha_alta` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas`
--

CREATE TABLE `preguntas` (
  `id_pregunta` int(30) NOT NULL,
  `id_seccion` int(20) NOT NULL,
  `num_pregunta` int(3) DEFAULT NULL,
  `pregunta` varchar(500) NOT NULL,
  `texto_ayuda` text,
  `ponderacion` int(2) NOT NULL,
  `critica` int(1) NOT NULL,
  `foto` int(1) DEFAULT NULL,
  `max_caracteres` int(2) DEFAULT NULL,
  `tipo` int(1) NOT NULL,
  `fecha_alta` datetime NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `privilegios_usuario`
--

CREATE TABLE `privilegios_usuario` (
  `id_privilegio` int(20) NOT NULL,
  `privilegio` varchar(45) NOT NULL,
  `descripcion` tinytext,
  `fecha_alta` datetime NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `privilegios_usuario`
--

INSERT INTO `privilegios_usuario` (`id_privilegio`, `privilegio`, `descripcion`, `fecha_alta`, `fecha_modificacion`) VALUES
(1, 'Administrador', 'Administrador total del sistema', '2017-03-21 12:44:00', NULL),
(2, 'Gerente', 'Gerente del cliente o interno', '2017-03-21 12:44:00', NULL),
(3, 'Auditor', 'Auditor del cliente o interno', '2017-03-21 12:44:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `replicas`
--

CREATE TABLE `replicas` (
  `id_replica` int(20) NOT NULL,
  `id_cuestionario_respondido` int(20) NOT NULL,
  `id_pregunta` int(20) NOT NULL,
  `id_usuario` int(20) NOT NULL,
  `causa_raiz` text,
  `accion_correctiva` text NOT NULL,
  `responsables` text NOT NULL,
  `fecha_compromiso` date DEFAULT NULL,
  `archivo_evidencia` varchar(200) DEFAULT NULL,
  `validacion` int(1) DEFAULT NULL,
  `satisfactorio` int(1) DEFAULT NULL,
  `comentario_cierre` text,
  `fecha_alta` datetime NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `replicas_abiertas`
--

CREATE TABLE `replicas_abiertas` (
  `id_replica_abierta` int(11) UNSIGNED NOT NULL,
  `id_usuario` int(20) NOT NULL,
  `id_replica` int(11) NOT NULL,
  `id_reporte` int(11) NOT NULL,
  `estado` int(1) NOT NULL,
  `fecha_apertura` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_abiertos`
--

CREATE TABLE `reportes_abiertos` (
  `id_reporte_abierto` int(20) UNSIGNED NOT NULL,
  `id_reporte` int(20) NOT NULL,
  `id_usuario` int(20) NOT NULL,
  `estado` int(1) NOT NULL,
  `fecha_apertura` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas_cuestionarios`
--

CREATE TABLE `respuestas_cuestionarios` (
  `id_respuesta` int(20) NOT NULL,
  `id_cuestionario_respondido` int(20) NOT NULL,
  `id_cuestionario` int(20) DEFAULT NULL,
  `id_seccion` int(20) DEFAULT NULL,
  `id_pregunta` int(20) NOT NULL,
  `id_opcion` int(20) NOT NULL,
  `puntaje` float DEFAULT NULL,
  `valor` float DEFAULT NULL,
  `fecha_respuesta` date NOT NULL,
  `hora_respuesta` time NOT NULL,
  `observaciones_auditor` text,
  `no_conformidad` text,
  `no_aplica` int(1) DEFAULT NULL,
  `fecha_alta` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `r_auditores_empresas`
--

CREATE TABLE `r_auditores_empresas` (
  `id_relacion` int(20) NOT NULL,
  `id_auditor` int(20) NOT NULL,
  `id_empresa` int(20) NOT NULL,
  `fecha_alta` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `r_clientes_cuestionarios`
--

CREATE TABLE `r_clientes_cuestionarios` (
  `id_relacion` int(11) NOT NULL,
  `id_cliente` int(20) NOT NULL,
  `id_cuestionario` int(20) NOT NULL,
  `fecha_alta` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `r_clientes_plantas_proveedores`
--

CREATE TABLE `r_clientes_plantas_proveedores` (
  `id_relacion` int(20) NOT NULL,
  `id_empresa` int(20) NOT NULL,
  `id_planta_proveedor` int(20) NOT NULL,
  `fecha_alta` datetime DEFAULT NULL COMMENT 'Esta tabla guarda la relación entre una empresa cliente y sus plantas o proveedores, los datos son obtenidos de la tabla empresas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `r_gerentes_auditores`
--

CREATE TABLE `r_gerentes_auditores` (
  `id_relacion` int(25) NOT NULL,
  `id_gerente` int(20) NOT NULL,
  `id_auditor` int(20) NOT NULL,
  `fecha_alta` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `r_gerentes_empresas`
--

CREATE TABLE `r_gerentes_empresas` (
  `id_relacion` int(20) NOT NULL,
  `id_gerente` int(20) NOT NULL,
  `id_empresa` int(20) NOT NULL,
  `fecha_alta` datetime NOT NULL COMMENT 'Esta tabla guarda la relación de gerentes con las empresas, un gerente puede tener mas de un cliente, planta o proveedor asignado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `r_opciones_preguntas`
--

CREATE TABLE `r_opciones_preguntas` (
  `id_opcion` int(11) NOT NULL,
  `id_pregunta` int(20) NOT NULL,
  `opcion` varchar(80) NOT NULL,
  `valor` float NOT NULL,
  `fecha_alta` datetime NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones_cuestionario`
--

CREATE TABLE `secciones_cuestionario` (
  `id_seccion` int(20) NOT NULL,
  `id_cuestionario` int(20) NOT NULL,
  `num_seccion` float DEFAULT NULL,
  `nombre_seccion` varchar(60) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `valor` int(11) NOT NULL,
  `fecha_alta` datetime NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_empresa`
--

CREATE TABLE `tipos_empresa` (
  `id_tipo_empresa` int(11) NOT NULL,
  `tipo` varchar(45) DEFAULT NULL,
  `descripcion` varchar(60) DEFAULT NULL,
  `fecha_alta` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `tipos_empresa`
--

INSERT INTO `tipos_empresa` (`id_tipo_empresa`, `tipo`, `descripcion`, `fecha_alta`, `fecha_modificacion`) VALUES
(1, 'Cliente', 'Empresa tipo cliente, es el organismo principal que contrata', '2017-03-13 12:00:00', NULL),
(2, 'Planta', 'Tipo de empresa que pertenece a un organismo tipo Cliente, n', '2017-03-13 12:01:00', NULL),
(3, 'Proveedor', 'Tipo de empresa que también pertenece a un organismo tipo Cl', '2017-03-13 12:05:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_usuario`
--

CREATE TABLE `tipos_usuario` (
  `id_tipo_usuario` int(11) NOT NULL,
  `tipo` varchar(45) DEFAULT NULL,
  `descripcion` varchar(60) DEFAULT NULL,
  `fecha_alta` datetime DEFAULT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `tipos_usuario`
--

INSERT INTO `tipos_usuario` (`id_tipo_usuario`, `tipo`, `descripcion`, `fecha_alta`, `fecha_modificacion`) VALUES
(1, 'Interno', 'Usuario que pertenece la entidad administradora del sistema', '2014-06-27 01:31:05', NULL),
(2, 'Externo', 'Usuario que pertenece a un cliente', '2017-03-21 12:35:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_empresa` int(20) NOT NULL,
  `id_tipo_usuario` int(2) DEFAULT NULL,
  `id_privilegios` int(20) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `apellido_paterno` varchar(45) DEFAULT NULL,
  `apellido_materno` varchar(45) DEFAULT NULL,
  `usuario` varchar(45) DEFAULT NULL,
  `correo_electronico` varchar(60) NOT NULL,
  `contrasenia` text,
  `estado` int(1) DEFAULT '1',
  `es_revisor` int(1) DEFAULT '0',
  `es_regional` int(1) NOT NULL,
  `fecha_alta` datetime DEFAULT NULL,
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_empresa`, `id_tipo_usuario`, `id_privilegios`, `nombre`, `apellido_paterno`, `apellido_materno`, `usuario`, `correo_electronico`, `contrasenia`, `estado`, `es_revisor`, `es_regional`, `fecha_alta`, `fecha_modificacion`) VALUES
(1, 0, 1, 1, 'Erick', 'Lopez', 'Torres', 'ericktorres87@gmail.com', 'ericktorres87@gmail.com', '475096ed5fbfa7dc0be1b1112c1ceb101c79b5b2', 1, 0, 0, '2018-10-08 18:20:00', NULL),
(2, 0, 1, 1, 'José Raúl', 'Romo', 'Saucedo', 'raul.romo@bh-cg.com.mx', 'raul.romo@bh-cg.com.mx', 'ea77310e8e50fb3ae101dfcd35dc78565161ba32', 1, 0, 0, '2018-10-08 18:21:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_empresa`
--

CREATE TABLE `usuarios_empresa` (
  `id_usuario_empresa` int(11) NOT NULL,
  `id_usuario` int(10) DEFAULT NULL,
  `id_empresa` int(10) DEFAULT NULL,
  `fecha_alta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cuestionarios`
--
ALTER TABLE `cuestionarios`
  ADD PRIMARY KEY (`id_cuestionario`);

--
-- Indices de la tabla `cuestionarios_respondidos`
--
ALTER TABLE `cuestionarios_respondidos`
  ADD PRIMARY KEY (`id_cuestionario_respondido`);

--
-- Indices de la tabla `detalle_replicas`
--
ALTER TABLE `detalle_replicas`
  ADD PRIMARY KEY (`id_detalle_replica`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id_empresa`);

--
-- Indices de la tabla `preguntas`
--
ALTER TABLE `preguntas`
  ADD PRIMARY KEY (`id_pregunta`);

--
-- Indices de la tabla `privilegios_usuario`
--
ALTER TABLE `privilegios_usuario`
  ADD PRIMARY KEY (`id_privilegio`);

--
-- Indices de la tabla `replicas`
--
ALTER TABLE `replicas`
  ADD PRIMARY KEY (`id_replica`);

--
-- Indices de la tabla `replicas_abiertas`
--
ALTER TABLE `replicas_abiertas`
  ADD PRIMARY KEY (`id_replica_abierta`);

--
-- Indices de la tabla `reportes_abiertos`
--
ALTER TABLE `reportes_abiertos`
  ADD PRIMARY KEY (`id_reporte_abierto`);

--
-- Indices de la tabla `respuestas_cuestionarios`
--
ALTER TABLE `respuestas_cuestionarios`
  ADD PRIMARY KEY (`id_respuesta`),
  ADD KEY `id_cuestionario_respondido` (`id_cuestionario_respondido`),
  ADD KEY `id_seccion` (`id_seccion`);

--
-- Indices de la tabla `r_auditores_empresas`
--
ALTER TABLE `r_auditores_empresas`
  ADD PRIMARY KEY (`id_relacion`);

--
-- Indices de la tabla `r_clientes_cuestionarios`
--
ALTER TABLE `r_clientes_cuestionarios`
  ADD PRIMARY KEY (`id_relacion`);

--
-- Indices de la tabla `r_clientes_plantas_proveedores`
--
ALTER TABLE `r_clientes_plantas_proveedores`
  ADD PRIMARY KEY (`id_relacion`);

--
-- Indices de la tabla `r_gerentes_auditores`
--
ALTER TABLE `r_gerentes_auditores`
  ADD PRIMARY KEY (`id_relacion`);

--
-- Indices de la tabla `r_gerentes_empresas`
--
ALTER TABLE `r_gerentes_empresas`
  ADD PRIMARY KEY (`id_relacion`);

--
-- Indices de la tabla `r_opciones_preguntas`
--
ALTER TABLE `r_opciones_preguntas`
  ADD PRIMARY KEY (`id_opcion`);

--
-- Indices de la tabla `secciones_cuestionario`
--
ALTER TABLE `secciones_cuestionario`
  ADD PRIMARY KEY (`id_seccion`);

--
-- Indices de la tabla `tipos_empresa`
--
ALTER TABLE `tipos_empresa`
  ADD PRIMARY KEY (`id_tipo_empresa`);

--
-- Indices de la tabla `tipos_usuario`
--
ALTER TABLE `tipos_usuario`
  ADD PRIMARY KEY (`id_tipo_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `usuarios_empresa`
--
ALTER TABLE `usuarios_empresa`
  ADD PRIMARY KEY (`id_usuario_empresa`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cuestionarios`
--
ALTER TABLE `cuestionarios`
  MODIFY `id_cuestionario` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cuestionarios_respondidos`
--
ALTER TABLE `cuestionarios_respondidos`
  MODIFY `id_cuestionario_respondido` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_replicas`
--
ALTER TABLE `detalle_replicas`
  MODIFY `id_detalle_replica` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id_empresa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `preguntas`
--
ALTER TABLE `preguntas`
  MODIFY `id_pregunta` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `privilegios_usuario`
--
ALTER TABLE `privilegios_usuario`
  MODIFY `id_privilegio` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `replicas`
--
ALTER TABLE `replicas`
  MODIFY `id_replica` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `replicas_abiertas`
--
ALTER TABLE `replicas_abiertas`
  MODIFY `id_replica_abierta` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes_abiertos`
--
ALTER TABLE `reportes_abiertos`
  MODIFY `id_reporte_abierto` int(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `respuestas_cuestionarios`
--
ALTER TABLE `respuestas_cuestionarios`
  MODIFY `id_respuesta` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `r_auditores_empresas`
--
ALTER TABLE `r_auditores_empresas`
  MODIFY `id_relacion` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `r_clientes_cuestionarios`
--
ALTER TABLE `r_clientes_cuestionarios`
  MODIFY `id_relacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `r_clientes_plantas_proveedores`
--
ALTER TABLE `r_clientes_plantas_proveedores`
  MODIFY `id_relacion` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `r_gerentes_auditores`
--
ALTER TABLE `r_gerentes_auditores`
  MODIFY `id_relacion` int(25) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `r_gerentes_empresas`
--
ALTER TABLE `r_gerentes_empresas`
  MODIFY `id_relacion` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `r_opciones_preguntas`
--
ALTER TABLE `r_opciones_preguntas`
  MODIFY `id_opcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `secciones_cuestionario`
--
ALTER TABLE `secciones_cuestionario`
  MODIFY `id_seccion` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_empresa`
--
ALTER TABLE `tipos_empresa`
  MODIFY `id_tipo_empresa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_usuario`
--
ALTER TABLE `tipos_usuario`
  MODIFY `id_tipo_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios_empresa`
--
ALTER TABLE `usuarios_empresa`
  MODIFY `id_usuario_empresa` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
