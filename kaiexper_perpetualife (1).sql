-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-02-2026 a las 17:26:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `kaiexper_perpetualife`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin_usuarios`
--

CREATE TABLE `admin_usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin') DEFAULT 'admin',
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admin_usuarios`
--

INSERT INTO `admin_usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `fecha_registro`) VALUES
(1, 'Super Admin', 'admin@perpetualife.com', '$2y$10$Hw0yEhpGybS039nks24SY.bDXamysjYD2YtzWFP3rvnJbl0d9noom', 'superadmin', '2026-02-05 00:18:31'),
(2, 'Janneth Sánchez ', 'janneth.sanchez@escala-inc.com', '$2y$10$tW2oSsxfH99h0XhHCob7muJiYyZutKMGZwJPIiCDiaJIiBH/3hPHG', 'admin', '2026-02-05 09:30:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `token_recuperacion` varchar(64) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `email`, `password`, `token_recuperacion`, `token_expiracion`, `telefono`, `direccion`, `fecha_registro`) VALUES
(1, 'Gustavo Cruz', 'paypal@kaiexperience.com', NULL, NULL, NULL, NULL, 'en algún lugar', '2026-01-20 23:26:38'),
(2, 'Nuevo usuario', 'paypal@kaiexperience.com', NULL, NULL, NULL, NULL, 'otro lado', '2026-01-20 23:30:53'),
(3, 'El otro cliente', 'paypal@kaiexperience.com', NULL, NULL, NULL, NULL, 'los mismos datos', '2026-01-20 23:35:08'),
(4, 'Gustavo Cruz', 'paypal@kaiexperience.com', NULL, NULL, NULL, NULL, 'en algún lugar', '2026-01-21 00:01:11'),
(5, 'Gustavo Cruz fdgdsfg', 'paypal@kaiexperience.com', NULL, NULL, NULL, NULL, 'ñlzkfdjtñkzjf', '2026-01-21 01:03:24'),
(6, 'Gustavo Cruz fdgdsfg', 'paypal@kaiexperience.com', NULL, NULL, NULL, NULL, 'algún lugar', '2026-01-26 16:11:58'),
(7, 'otro cliente', 'ejemplo@otros.com', NULL, NULL, NULL, '5512369874', 'en otro lado', '2026-02-03 23:59:06'),
(8, 'gusgus', 'ejemplo@unomas.com', NULL, NULL, NULL, '5512364789', 'una mas lejos', '2026-02-04 22:56:21'),
(9, 'gusgus2', 'ejemplo@mas.com', NULL, NULL, NULL, '4561237896', 'de nuevo otra', '2026-02-04 22:58:52'),
(12, 'Gustavo C', 'ejemplo@uno.com', '$2y$10$zOKaDMPDIct4MvRNeWzTQuIbKzmvHAPxLDwH5vi84NZAQSltGzykS', NULL, NULL, '5421369874', 'va de nuez', '2026-02-04 23:11:20'),
(13, 'gusgus22', 'mundo_cube@hotmail.com', '$2y$10$lWPMw4ORxIGThUijgQwjmOlgNi7LPg7Zb1NFh60n/yH1/xqqubLK2', NULL, NULL, '5512364789', 'ya se fue', '2026-02-05 05:25:24'),
(14, 'zñkdhakf', 'l-kdajfñka@ñlkrdsjñlk.com', NULL, NULL, NULL, '564754564564', 'rgsdfgfds', '2026-02-05 15:49:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'porcentaje',
  `tipo_oferta` varchar(20) NOT NULL DEFAULT 'descuento',
  `descuento` int(11) NOT NULL COMMENT 'Porcentaje de descuento (ej: 10 para 10%)',
  `limite_uso` int(11) NOT NULL DEFAULT 0,
  `usos_actuales` int(11) NOT NULL DEFAULT 0,
  `fecha_vencimiento` date NOT NULL,
  `estado` tinyint(4) DEFAULT 1,
  `estado_manual` enum('activo','pausado') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedido`
--

CREATE TABLE `detalles_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `detalles_pedido`
--

INSERT INTO `detalles_pedido` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 5, 1, 600.00),
(2, 2, 4, 1, 1150.00),
(3, 3, 5, 1, 600.00),
(4, 4, 5, 2, 600.00),
(5, 5, 5, 1, 600.00),
(6, 6, 5, 1, 600.00),
(7, 7, 5, 1, 600.00),
(8, 8, 6, 1, 12500.00),
(9, 9, 5, 1, 600.00),
(10, 10, 5, 1, 600.00),
(11, 11, 5, 1, 600.00),
(12, 12, 5, 1, 600.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagenes_productos`
--

CREATE TABLE `imagenes_productos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `url_imagen` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `imagenes_productos`
--

INSERT INTO `imagenes_productos` (`id`, `producto_id`, `url_imagen`) VALUES
(4, 4, 'imagenes/aeon_nad_complex.png'),
(5, 5, 'imagenes/magnesium_gummies.png'),
(9, 4, 'imagenes/aeon_1.png'),
(10, 4, 'imagenes/aeon_2.png'),
(11, 5, 'imagenes/aeon_3.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `paypal_order_id` varchar(100) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `moneda` varchar(10) DEFAULT 'USD',
  `estado` varchar(20) DEFAULT 'PENDIENTE',
  `metodo_pago` varchar(50) DEFAULT 'PayPal',
  `id_transaccion` varchar(100) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `paypal_order_id`, `total`, `moneda`, `estado`, `metodo_pago`, `id_transaccion`, `fecha`) VALUES
(1, 1, '47792071Y7080753T', 600.00, 'MXN', 'COMPLETADO', 'PayPal', NULL, '2026-01-20 17:26:38'),
(2, 2, '2CR55234BD154602W', 1150.00, 'MXN', 'COMPLETADO', 'PayPal', NULL, '2026-01-20 17:30:53'),
(3, 3, '2BC39351M3694050A', 600.00, 'MXN', 'COMPLETADO', 'PayPal', NULL, '2026-01-20 17:35:08'),
(4, 4, '69752135BD2225948', 1200.00, 'MXN', 'COMPLETADO', 'PayPal', NULL, '2026-01-20 18:01:11'),
(5, 5, '4U512178S87319934', 600.00, 'MXN', 'COMPLETADO', 'PayPal', NULL, '2026-01-20 19:03:24'),
(6, 6, '4VB92805UH421350D', 600.00, 'MXN', 'COMPLETADO', 'PayPal', NULL, '2026-01-26 10:11:58'),
(7, 7, '290949862K560701X', 600.00, 'MXN', 'PAGADO', 'PayPal', NULL, '2026-02-03 17:59:06'),
(8, 8, '0UG260335H421132D', 12500.00, 'MXN', 'PAGADO', 'PayPal', NULL, '2026-02-04 16:56:21'),
(9, 9, '1L161964HY020603S', 600.00, 'MXN', 'PAGADO', 'PayPal', NULL, '2026-02-04 16:58:52'),
(10, 12, NULL, 600.00, 'USD', '1', 'PayPal', '2JV35884L6621172R', '2026-02-04 17:11:20'),
(11, 13, NULL, 600.00, 'USD', '1', 'PayPal', '9M388031BX038163J', '2026-02-04 23:25:24'),
(12, 14, '40830331RK8176945', 600.00, 'MXN', 'COMPLETADO', 'PayPal', '40830331RK8176945', '2026-02-05 09:49:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'General',
  `descripcion_corta` varchar(255) DEFAULT NULL,
  `descripcion_larga` text DEFAULT NULL,
  `calificacion` decimal(2,1) DEFAULT 5.0,
  `precio` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `en_oferta` tinyint(1) DEFAULT 0,
  `es_top` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `imagen1` varchar(255) DEFAULT NULL,
  `imagen2` varchar(255) DEFAULT NULL,
  `imagen3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `categoria`, `descripcion_corta`, `descripcion_larga`, `calificacion`, `precio`, `stock`, `precio_anterior`, `en_oferta`, `es_top`, `activo`, `imagen1`, `imagen2`, `imagen3`) VALUES
(4, 'Complejo Suplementario NAD+ AEON™', 'Suplementos', 'LONGEVITY COMPLEX 2500MG - 60 Cápsulas Veganas', 'Complejo de longevidad NAD+ AEON™ de 2500mg. Diseñado para la reparación celular, longevidad y antienvejecimiento, promoviendo una piel con brillo saludable. Contiene 60 cápsulas veganas. Precio de oferta (Precio original: $1,800.00).', 4.5, 1150.00, 19, 1800.00, 0, 1, 1, 'aeon_1.png', 'aeon_2.png', 'aeon_3.png'),
(5, 'Gomitas de magnesio con L-treonato, glicinato, citrato, sulfato.', 'Equipamiento', 'PREMIUM MAGNESIUM GUMMIES 500MG - 60 Gomitas Veganas', 'Gomitas de magnesio premium de 500mg por porción. Contiene una mezcla 5X de L-treonato, sulfato, citrato, óxido y glicinato. Apoya la salud ósea y muscular. Contiene 60 gomitas veganas. Precio de oferta (Precio original: $900.00).', 5.0, 600.00, 14, 900.00, 1, 0, 1, 'magnesium_gummies.png', 'aeon_nad_complex.png', 'oferta.png'),
(6, 'BioReactor Pro X1', 'Suplementos', 'Sistema avanzado de cultivo celular', 'Biorreactor de última generación...', 3.0, 12500.00, 4, 18750.00, 1, 0, 1, NULL, NULL, NULL),
(7, 'CrioSafe 3000', 'Equipamiento', 'Tanque criogénico', 'Sistema de almacenamiento criogénico...', 5.0, 28900.00, 3, 43350.00, 0, 0, 1, NULL, NULL, NULL),
(8, 'BioReactor Pro X1', 'Suplementos', 'Sistema avanzado de cultivo celular', 'Biorreactor de última generación...', 2.0, 12500.00, 5, 18750.00, 0, 0, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_admin`
--

CREATE TABLE `usuarios_admin` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios_admin`
--

INSERT INTO `usuarios_admin` (`id`, `usuario`, `password`, `nombre`, `ultimo_login`) VALUES
(1, 'admin', '$2y$10$Hw0yEhpGybS039nks24SY.bDXamysjYD2YtzWFP3rvnJbl0d9noom', 'Gustavo Cruz', '2026-02-03 18:42:55');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin_usuarios`
--
ALTER TABLE `admin_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido_perpetua` (`pedido_id`);

--
-- Indices de la tabla `imagenes_productos`
--
ALTER TABLE `imagenes_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido_cliente` (`cliente_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios_admin`
--
ALTER TABLE `usuarios_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin_usuarios`
--
ALTER TABLE `admin_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `imagenes_productos`
--
ALTER TABLE `imagenes_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios_admin`
--
ALTER TABLE `usuarios_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `fk_pedido_perpetua` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `imagenes_productos`
--
ALTER TABLE `imagenes_productos`
  ADD CONSTRAINT `imagenes_productos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
