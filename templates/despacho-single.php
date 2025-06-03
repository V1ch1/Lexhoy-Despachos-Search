<?php
/**
 * Template para mostrar un despacho individual
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener los datos del despacho
$despacho = $GLOBALS['despacho'];
?>

<div class="despacho-single">
    <!-- Cabecera del despacho -->
    <header class="despacho-header">
        <div class="despacho-header-content">
            <h1 class="despacho-title"><?php echo esc_html($despacho['nombre']); ?></h1>
            <?php if (!empty($despacho['estado_verificacion']) && $despacho['estado_verificacion'] === 'verificado'): ?>
                <span class="despacho-verified">
                    <i class="fas fa-check-circle"></i> Despacho Verificado
                </span>
            <?php endif; ?>
        </div>
    </header>

    <!-- Contenido principal -->
    <div class="despacho-content">
        <!-- Columna izquierda -->
        <div class="despacho-main">
            <!-- Información básica -->
            <section class="despacho-section">
                <h2><i class="fas fa-info-circle"></i> Información Básica</h2>
                <div class="despacho-info-grid">
                    <?php if (!empty($despacho['direccion'])): ?>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-content">
                                <strong>Dirección:</strong>
                                <p><?php echo esc_html($despacho['direccion']); ?></p>
                                <p><?php echo esc_html($despacho['localidad'] . ', ' . $despacho['provincia']); ?></p>
                                <p><?php echo esc_html($despacho['codigo_postal']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($despacho['telefono'])): ?>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div class="info-content">
                                <strong>Teléfono:</strong>
                                <p><a href="tel:<?php echo esc_attr($despacho['telefono']); ?>"><?php echo esc_html($despacho['telefono']); ?></a></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($despacho['email'])): ?>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="info-content">
                                <strong>Email:</strong>
                                <p><a href="mailto:<?php echo esc_attr($despacho['email']); ?>"><?php echo esc_html($despacho['email']); ?></a></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($despacho['web'])): ?>
                        <div class="info-item">
                            <i class="fas fa-globe"></i>
                            <div class="info-content">
                                <strong>Sitio Web:</strong>
                                <p><a href="<?php echo esc_url($despacho['web']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($despacho['web']); ?></a></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Descripción -->
            <?php if (!empty($despacho['descripcion'])): ?>
                <section class="despacho-section">
                    <h2><i class="fas fa-align-left"></i> Descripción</h2>
                    <div class="despacho-description">
                        <?php echo wpautop(esc_html($despacho['descripcion'])); ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Áreas de Práctica -->
            <?php if (!empty($despacho['areas_practica'])): ?>
                <section class="despacho-section">
                    <h2><i class="fas fa-gavel"></i> Áreas de Práctica</h2>
                    <div class="despacho-areas">
                        <?php foreach ($despacho['areas_practica'] as $area): ?>
                            <span class="area-tag"><?php echo esc_html($area); ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Especialidades -->
            <?php if (!empty($despacho['especialidades'])): ?>
                <section class="despacho-section">
                    <h2><i class="fas fa-star"></i> Especialidades</h2>
                    <ul class="despacho-specialties">
                        <?php foreach ($despacho['especialidades'] as $especialidad): ?>
                            <li><?php echo esc_html($especialidad); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <!-- Experiencia -->
            <?php if (!empty($despacho['experiencia'])): ?>
                <section class="despacho-section">
                    <h2><i class="fas fa-briefcase"></i> Experiencia</h2>
                    <div class="despacho-experience">
                        <?php echo wpautop(esc_html($despacho['experiencia'])); ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <!-- Columna derecha -->
        <div class="despacho-sidebar">
            <!-- Formulario de contacto -->
            <section class="despacho-section contact-form">
                <h2><i class="fas fa-envelope"></i> Contactar</h2>
                <form id="despacho-contact-form" class="contact-form">
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
                        <textarea id="mensaje" name="mensaje" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="submit-button">
                        <i class="fas fa-paper-plane"></i> Enviar Mensaje
                    </button>
                </form>
            </section>

            <!-- Información adicional -->
            <?php if (!empty($despacho['horario'])): ?>
                <section class="despacho-section">
                    <h2><i class="fas fa-clock"></i> Horario</h2>
                    <div class="despacho-schedule">
                        <?php foreach ($despacho['horario'] as $dia => $horas): ?>
                            <div class="schedule-item">
                                <strong><?php echo esc_html($dia); ?>:</strong>
                                <span><?php echo esc_html($horas); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Redes sociales -->
            <?php if (!empty($despacho['redes_sociales'])): ?>
                <section class="despacho-section">
                    <h2><i class="fas fa-share-alt"></i> Redes Sociales</h2>
                    <div class="despacho-social">
                        <?php foreach ($despacho['redes_sociales'] as $red => $url): ?>
                            <?php if (!empty($url)): ?>
                                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" class="social-link">
                                    <i class="fab fa-<?php echo esc_attr($red); ?>"></i>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Estilos generales */
.despacho-single {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Cabecera */
.despacho-header {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.despacho-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.despacho-title {
    margin: 0;
    color: #333;
    font-size: 2em;
}

.despacho-verified {
    background: #2ecc71;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Layout principal */
.despacho-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* Secciones */
.despacho-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.despacho-section h2 {
    color: #333;
    font-size: 1.4em;
    margin-top: 0;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Grid de información */
.despacho-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.info-item i {
    color: #2271b1;
    font-size: 1.2em;
    margin-top: 3px;
}

.info-content strong {
    display: block;
    color: #555;
    margin-bottom: 5px;
}

.info-content p {
    margin: 0;
    color: #666;
}

/* Áreas de práctica */
.despacho-areas {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.area-tag {
    background: #e9ecef;
    color: #495057;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9em;
}

/* Especialidades */
.despacho-specialties {
    list-style: none;
    padding: 0;
    margin: 0;
}

.despacho-specialties li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.despacho-specialties li:last-child {
    border-bottom: none;
}

/* Formulario de contacto */
.contact-form {
    background: #f8f9fa;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #555;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
}

.submit-button {
    background: #2271b1;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.3s;
}

.submit-button:hover {
    background: #135e96;
}

/* Horario */
.schedule-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.schedule-item:last-child {
    border-bottom: none;
}

/* Redes sociales */
.despacho-social {
    display: flex;
    gap: 15px;
}

.social-link {
    color: #666;
    font-size: 1.5em;
    transition: color 0.3s;
}

.social-link:hover {
    color: #2271b1;
}

/* Responsive */
@media (max-width: 768px) {
    .despacho-content {
        grid-template-columns: 1fr;
    }

    .despacho-header-content {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }

    .despacho-info-grid {
        grid-template-columns: 1fr;
    }
}
</style> 