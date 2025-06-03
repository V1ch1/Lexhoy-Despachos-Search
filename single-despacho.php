<?php
/**
 * Template para mostrar un despacho individual
 */

get_header();
?>

<div class="despacho-single">
    <div class="despacho-container">
        <!-- Botón Volver -->
        <div class="back-button">
            <a href="/buscador-de-despachos" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver al Buscador
            </a>
        </div>

        <!-- Cabecera del Despacho -->
        <div class="despacho-header">
            <h1><?php echo esc_html($despacho['nombre']); ?></h1>
            <?php if (!empty($despacho['estado_verificacion'])): ?>
                <span class="verification-badge">
                    <i class="fas fa-check-circle"></i> Despacho Verificado
                </span>
            <?php endif; ?>
        </div>
        
        <div class="despacho-grid">
            <!-- Columna Principal -->
            <div class="despacho-main">
                <!-- Información de Contacto -->
                <?php if (!empty($despacho['direccion']) || !empty($despacho['telefono']) || !empty($despacho['email']) || !empty($despacho['web'])): ?>
                    <div class="info-section">
                        <h2><i class="fas fa-address-card"></i> Información de Contacto</h2>
                        <div class="info-content">
                            <?php if (!empty($despacho['direccion'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div class="info-text">
                                        <strong>Dirección:</strong>
                                        <p>
                                            <?php echo esc_html($despacho['direccion']); ?>
                                            <?php if (!empty($despacho['localidad'])): ?>
                                                <br><?php echo esc_html($despacho['localidad']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($despacho['provincia'])): ?>
                                                <br><?php echo esc_html($despacho['provincia']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($despacho['codigo_postal'])): ?>
                                                <br><?php echo esc_html($despacho['codigo_postal']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($despacho['telefono'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-phone"></i>
                                    <div class="info-text">
                                        <strong>Teléfono:</strong>
                                        <p><a href="tel:<?php echo esc_attr($despacho['telefono']); ?>"><?php echo esc_html($despacho['telefono']); ?></a></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($despacho['email'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-envelope"></i>
                                    <div class="info-text">
                                        <strong>Email:</strong>
                                        <p><a href="mailto:<?php echo esc_attr($despacho['email']); ?>"><?php echo esc_html($despacho['email']); ?></a></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($despacho['web'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-globe"></i>
                                    <div class="info-text">
                                        <strong>Web:</strong>
                                        <p><a href="<?php echo esc_url($despacho['web']); ?>" target="_blank"><?php echo esc_html($despacho['web']); ?></a></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Áreas de Práctica -->
                <?php if (!empty($despacho['areas_practica'])): ?>
                    <div class="info-section">
                        <h2><i class="fas fa-briefcase"></i> Áreas de Práctica</h2>
                        <div class="areas-grid">
                            <?php foreach ($despacho['areas_practica'] as $area): ?>
                                <span class="area-tag">
                                    <?php echo esc_html($area); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Descripción -->
                <?php if (!empty($despacho['descripcion'])): ?>
                    <div class="info-section">
                        <h2><i class="fas fa-info-circle"></i> Descripción</h2>
                        <div class="info-content">
                            <?php echo wpautop(esc_html($despacho['descripcion'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Experiencia -->
                <?php if (!empty($despacho['experiencia'])): ?>
                    <div class="info-section">
                        <h2><i class="fas fa-history"></i> Experiencia</h2>
                        <div class="info-content">
                            <?php echo wpautop(esc_html($despacho['experiencia'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Especialidades -->
                <?php if (!empty($despacho['especialidades'])): ?>
                    <div class="info-section">
                        <h2><i class="fas fa-star"></i> Especialidades</h2>
                        <div class="info-content">
                            <ul class="specialties-list">
                                <?php foreach ($despacho['especialidades'] as $especialidad): ?>
                                    <li><?php echo esc_html($especialidad); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Columna Lateral -->
            <div class="despacho-sidebar">
                <!-- Información del Despacho -->
                <div class="info-section">
                    <h2><i class="fas fa-building"></i> Información del Despacho</h2>
                    <div class="info-content">
                        <?php if (!empty($despacho['tamaño_despacho'])): ?>
                            <div class="info-item">
                                <i class="fas fa-users"></i>
                                <div class="info-text">
                                    <strong>Tamaño:</strong>
                                    <p><?php echo esc_html($despacho['tamaño_despacho']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($despacho['año_fundacion'])): ?>
                            <div class="info-item">
                                <i class="fas fa-calendar-alt"></i>
                                <div class="info-text">
                                    <strong>Año de Fundación:</strong>
                                    <p><?php echo esc_html($despacho['año_fundacion']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($despacho['estado_registro'])): ?>
                            <div class="info-item">
                                <i class="fas fa-clipboard-check"></i>
                                <div class="info-text">
                                    <strong>Estado del Registro:</strong>
                                    <p><?php echo esc_html($despacho['estado_registro']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Horario -->
                <?php if (!empty($despacho['horario'])): ?>
                    <div class="info-section">
                        <h2><i class="fas fa-clock"></i> Horario</h2>
                        <div class="info-content">
                            <ul class="schedule-list">
                                <?php foreach ($despacho['horario'] as $dia => $horas): ?>
                                    <li>
                                        <strong><?php echo esc_html($dia); ?>:</strong>
                                        <span><?php echo esc_html($horas); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Redes Sociales -->
                <?php if (!empty($despacho['redes_sociales'])): ?>
                    <div class="info-section">
                        <h2><i class="fas fa-share-alt"></i> Redes Sociales</h2>
                        <div class="social-links">
                            <?php foreach ($despacho['redes_sociales'] as $red => $url): ?>
                                <a href="<?php echo esc_url($url); ?>" target="_blank" class="social-link">
                                    <i class="fab fa-<?php echo esc_attr($red); ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Contacto -->
                <div class="info-section contact-form">
                    <h2><i class="fas fa-envelope"></i> Contactar con el Despacho</h2>
                    <form id="despacho-contact-form" method="post">
                        <input type="hidden" name="despacho_id" value="<?php echo esc_attr($despacho['objectID']); ?>">
                        
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono">
                        </div>

                        <div class="form-group">
                            <label for="mensaje">Mensaje *</label>
                            <textarea id="mensaje" name="mensaje" rows="4" required></textarea>
                        </div>

                        <button type="submit" class="submit-button">
                            <i class="fas fa-paper-plane"></i> Enviar Mensaje
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos Generales */
    .despacho-single {
        padding: 40px 0;
        background: #f8f9fa;
    }

    .despacho-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Botón Volver */
    .back-button {
        margin-bottom: 30px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        background: #fff;
        color: #333;
        text-decoration: none;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background: #f0f0f0;
        transform: translateX(-5px);
    }

    .btn-back i {
        margin-right: 8px;
    }

    /* Cabecera del Despacho */
    .despacho-header {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .despacho-header h1 {
        margin: 0;
        color: #2c3e50;
        font-size: 2.5em;
    }

    .verification-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        background: #e8f5e9;
        color: #2e7d32;
        border-radius: 20px;
        font-size: 0.9em;
    }

    .verification-badge i {
        margin-right: 5px;
    }

    /* Grid Principal */
    .despacho-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    /* Secciones de Información */
    .info-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .info-section h2 {
        color: #2c3e50;
        font-size: 1.4em;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #eee;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-section h2 i {
        color: #3498db;
    }

    .info-content {
        color: #555;
    }

    .info-item {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .info-item i {
        color: #3498db;
        font-size: 1.2em;
        margin-top: 3px;
    }

    .info-text strong {
        color: #2c3e50;
        display: block;
        margin-bottom: 5px;
    }

    .info-text p {
        margin: 0;
        line-height: 1.6;
    }

    .info-text a {
        color: #3498db;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .info-text a:hover {
        color: #2980b9;
    }

    /* Áreas de Práctica */
    .areas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .area-tag {
        display: inline-block;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9em;
        color: #555;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .area-tag:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }

    /* Lista de Especialidades */
    .specialties-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .specialties-list li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .specialties-list li:last-child {
        border-bottom: none;
    }

    /* Horario */
    .schedule-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .schedule-list li {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .schedule-list li:last-child {
        border-bottom: none;
    }

    /* Redes Sociales */
    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: #f8f9fa;
        color: #555;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background: #3498db;
        color: white;
        transform: translateY(-3px);
    }

    /* Formulario de Contacto */
    .contact-form {
        background: #fff;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #2c3e50;
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1em;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #3498db;
        outline: none;
    }

    .submit-button {
        width: 100%;
        padding: 12px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 1em;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .submit-button:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 992px) {
        .despacho-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .despacho-single {
            padding: 20px 0;
        }
        
        .despacho-container {
            padding: 0 15px;
        }
        
        .despacho-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .despacho-header h1 {
            font-size: 2em;
        }

        .info-section {
            padding: 20px;
        }
    }
</style>

<?php get_footer(); ?> 