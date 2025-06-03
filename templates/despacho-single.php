<?php
/**
 * Template para mostrar un despacho individual
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php get_header(); ?>

    <div class="despacho-single">
        <div class="despacho-header">
            <h1><?php echo esc_html($despacho['nombre']); ?></h1>
            <div class="despacho-meta">
                <p><strong>Localidad:</strong> <?php echo esc_html($despacho['localidad']); ?></p>
                <p><strong>Provincia:</strong> <?php echo esc_html($despacho['provincia']); ?></p>
            </div>
        </div>

        <div class="despacho-content">
            <?php if (!empty($despacho['areas_practica'])): ?>
                <div class="areas-practica">
                    <h2>Áreas de Práctica</h2>
                    <p><?php echo esc_html($despacho['areas_practica']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($despacho['descripcion'])): ?>
                <div class="descripcion">
                    <h2>Descripción</h2>
                    <p><?php echo esc_html($despacho['descripcion']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($despacho['direccion'])): ?>
                <div class="direccion">
                    <h2>Dirección</h2>
                    <p><?php echo esc_html($despacho['direccion']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($despacho['telefono'])): ?>
                <div class="contacto">
                    <h2>Contacto</h2>
                    <p><strong>Teléfono:</strong> <?php echo esc_html($despacho['telefono']); ?></p>
                    <?php if (!empty($despacho['email'])): ?>
                        <p><strong>Email:</strong> <?php echo esc_html($despacho['email']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="despacho-contact-form">
            <h2>Contactar con el Despacho</h2>
            <form id="contact-form" method="post">
                <input type="hidden" name="despacho_id" value="<?php echo esc_attr($despacho['objectID']); ?>">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="mensaje">Mensaje:</label>
                    <textarea id="mensaje" name="mensaje" required></textarea>
                </div>
                <button type="submit">Enviar Mensaje</button>
            </form>
        </div>
    </div>

    <style>
        .despacho-single {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .despacho-header {
            margin-bottom: 30px;
        }

        .despacho-header h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 15px;
        }

        .despacho-meta {
            color: #666;
        }

        .despacho-content {
            margin-bottom: 40px;
        }

        .despacho-content h2 {
            font-size: 1.8em;
            color: #333;
            margin: 25px 0 15px;
        }

        .despacho-contact-form {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group textarea {
            height: 150px;
        }

        button[type="submit"] {
            background: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background: #005177;
        }
    </style>

    <?php get_footer(); ?>
    <?php wp_footer(); ?>
</body>
</html> 