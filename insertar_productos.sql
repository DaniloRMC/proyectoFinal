-- Script para insertar productos con la estructura correcta
USE panaderia_db;

-- Insertar los 35 productos restantes
INSERT INTO productos (nombre, descripcion, categoria_id, precio, costo, stock, stock_minimo, codigo_barras, imagen_url, estado, empleado_creador_id) VALUES
('Galletas de Avena', 'Galletas caseras con avena y pasas', 4, 25.00, 12.00, 40, 10, '7891234567893', 'galletas_avena.jpg', 'disponible', 1),
('Café Americano', 'Café recién molido y preparado', 5, 30.00, 15.00, 100, 20, '7891234567894', 'cafe_americano.jpg', 'disponible', 1),
('Empanada de Pollo', 'Empanada horneada rellena de pollo', 6, 55.00, 30.00, 15, 5, '7891234567895', 'empanada_pollo.jpg', 'disponible', 1),
('Sandwich Jamón y Queso', 'Sandwich en pan artesanal', 7, 75.00, 40.00, 12, 3, '7891234567896', 'sandwich.jpg', 'disponible', 1),
('Éclair de Vainilla', 'Éclair relleno de crema de vainilla', 8, 65.00, 35.00, 10, 2, '7891234567897', 'eclair.jpg', 'disponible', 1),
('Pan de Masa Madre', 'Pan artesanal con masa madre natural', 26, 50.00, 28.00, 18, 4, '7891234567898', 'pan_masa_madre.jpg', 'disponible', 1),
('Dona Glaseada', 'Dona esponjosa con glaseado dulce', 11, 40.00, 22.00, 30, 8, '7891234567899', 'dona_glaseada.jpg', 'disponible', 1),
('Muffin de Arándanos', 'Muffin esponjoso con arándanos frescos', 12, 42.00, 25.00, 25, 6, '7891234567900', 'muffin_arandanos.jpg', 'disponible', 1),
('Tarta de Manzana', 'Tarta casera con manzanas caramelizadas', 13, 380.00, 220.00, 6, 1, '7891234567901', 'tarta_manzana.jpg', 'disponible', 1),
('Brownie de Chocolate', 'Brownie húmedo con nueces', 14, 38.00, 20.00, 22, 5, '7891234567902', 'brownie.jpg', 'disponible', 1),
('Pan sin Gluten', 'Pan especial para celíacos', 16, 60.00, 35.00, 12, 3, '7891234567903', 'pan_sin_gluten.jpg', 'disponible', 1),
('Rosca de Reyes', 'Rosca tradicional de temporada', 17, 250.00, 150.00, 5, 1, '7891234567904', 'rosca_reyes.jpg', 'disponible', 1),
('Cupcake Infantil', 'Cupcake decorado para niños', 18, 48.00, 28.00, 20, 5, '7891234567905', 'cupcake_infantil.jpg', 'disponible', 1),
('Pan Light', 'Pan bajo en calorías y grasas', 19, 52.00, 30.00, 15, 4, '7891234567906', 'pan_light.jpg', 'disponible', 1),
('Baguette Tradicional', 'Baguette francesa artesanal', 20, 42.00, 24.00, 18, 5, '7891234567907', 'baguette.jpg', 'disponible', 1),
('Cheesecake Frutos Rojos', 'Cheesecake gourmet premium', 21, 520.00, 300.00, 4, 1, '7891234567908', 'cheesecake.jpg', 'disponible', 1),
('Pretzel Alemán', 'Pretzel tradicional alemán', 22, 38.00, 22.00, 16, 4, '7891234567909', 'pretzel.jpg', 'disponible', 1),
('Pan del Día', 'Pan fresco elaborado diariamente', 23, 28.00, 15.00, 35, 10, '7891234567910', 'pan_dia.jpg', 'disponible', 1),
('Empanada Congelada', 'Empanada lista para hornear', 24, 45.00, 25.00, 25, 8, '7891234567911', 'empanada_congelada.jpg', 'disponible', 1),
('Concha Rellena', 'Concha tradicional con relleno', 25, 48.00, 28.00, 20, 6, '7891234567912', 'concha_rellena.jpg', 'disponible', 1),
('Cappuccino', 'Cappuccino con espuma de leche', 5, 45.00, 22.00, 80, 15, '7891234567913', 'cappuccino.jpg', 'disponible', 1),
('Tarta Vegana', 'Tarta sin ingredientes de origen animal', 15, 420.00, 250.00, 3, 1, '7891234567914', 'tarta_vegana.jpg', 'disponible', 1),
('Bagel Integral', 'Bagel integral con semillas de sésamo', 9, 40.00, 24.00, 22, 6, '7891234567915', 'bagel_integral.jpg', 'disponible', 1),
('Pie de Limón', 'Pie cremoso de limón con merengue', 13, 350.00, 200.00, 5, 1, '7891234567916', 'pie_limon.jpg', 'disponible', 1),
('Hot Dog Gourmet', 'Hot dog con pan artesanal', 7, 85.00, 45.00, 10, 3, '7891234567917', 'hotdog.jpg', 'disponible', 1),
('Pan Dulce Promoción', 'Pan dulce en oferta especial', 28, 22.00, 12.00, 45, 12, '7891234567918', 'pan_dulce_promo.jpg', 'disponible', 1),
('Canapés Catering', 'Variedad de canapés para eventos', 29, 15.00, 8.00, 50, 15, '7891234567919', 'canapes.jpg', 'disponible', 1),
('Torta Especial Casa', 'Torta signature de la panadería', 30, 680.00, 400.00, 2, 1, '7891234567920', 'torta_especial.jpg', 'disponible', 1),
('Pan Francés', 'Pan francés crujiente por fuera', 2, 38.00, 22.00, 24, 6, '7891234567921', 'pan_frances.jpg', 'disponible', 1),
('Milhojas', 'Milhojas con crema pastelera', 8, 72.00, 40.00, 8, 2, '7891234567922', 'milhojas.jpg', 'disponible', 1),
('Panqué de Vainilla', 'Panqué esponjoso casero', 1, 180.00, 100.00, 6, 2, '7891234567923', 'panque_vainilla.jpg', 'disponible', 1),
('Smoothie de Frutas', 'Smoothie natural de temporada', 5, 55.00, 30.00, 20, 5, '7891234567924', 'smoothie.jpg', 'disponible', 1);

SELECT 'Productos insertados exitosamente' AS mensaje;
