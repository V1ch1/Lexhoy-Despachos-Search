<?php
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

    <div class="despacho-single-container">
        <div class="despacho-header">
            <h1><?php echo esc_html($despacho['nombre']); ?></h1>
            <div class="despacho-meta">
                <p><strong>Localidad:</strong> <?php echo esc_html($despacho['localidad']); ?></p>
                <p><strong>Provincia:</strong> <?php echo esc_html($despacho['provincia']); ?></p>
            </div>
        </div>

        <div class="despacho-content">
            <div class="despacho-info">
                <h2>Información de Contacto</h2>
                <p><strong>Dirección:</strong> <?php echo esc_html($despacho['direccion']); ?></p>
                <p><strong>Código Postal:</strong> <?php echo esc_html($despacho['codigo_postal']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo esc_html($despacho['telefono']); ?></p>
                <?php if (!empty($despacho['email'])): ?>
                    <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($despacho['email']); ?>"><?php echo esc_html($despacho['email']); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($despacho['web'])): ?>
                    <p><strong>Sitio Web:</strong> <a href="<?php echo esc_url($despacho['web']); ?>" target="_blank"><?php echo esc_html($despacho['web']); ?></a></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($despacho['areas_practica'])): ?>
            <div class="despacho-areas">
                <h2>Áreas de Práctica</h2>
                <ul>
                    <?php foreach ($despacho['areas_practica'] as $area): ?>
                        <li><?php echo esc_html($area); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($despacho['especialidades'])): ?>
            <div class="despacho-especialidades">
                <h2>Especialidades</h2>
                <ul>
                    <?php foreach ($despacho['especialidades'] as $especialidad): ?>
                        <li><?php echo esc_html($especialidad); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($despacho['descripcion'])): ?>
            <div class="despacho-descripcion">
                <h2>Descripción</h2>
                <p><?php echo nl2br(esc_html($despacho['descripcion'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($despacho['horario'])): ?>
            <div class="despacho-horario">
                <h2>Horario</h2>
                <ul>
                    <?php foreach ($despacho['horario'] as $dia => $horas): ?>
                        <li><strong><?php echo esc_html($dia); ?>:</strong> <?php echo esc_html($horas); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($despacho['redes_sociales'])): ?>
            <div class="despacho-redes">
                <h2>Redes Sociales</h2>
                <ul>
                    <?php foreach ($despacho['redes_sociales'] as $red => $url): ?>
                        <li><a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_html($red); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .despacho-single-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .despacho-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .despacho-header h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 20px;
        }

        .despacho-meta {
            color: #666;
        }

        .despacho-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .despacho-info,
        .despacho-areas,
        .despacho-especialidades,
        .despacho-descripcion,
        .despacho-horario,
        .despacho-redes {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h2 {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        li {
            margin-bottom: 10px;
            padding-left: 20px;
            position: relative;
        }

        li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #007bff;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .despacho-content {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <?php get_footer(); ?>
    <?php wp_footer(); ?>
</body>
</html> 