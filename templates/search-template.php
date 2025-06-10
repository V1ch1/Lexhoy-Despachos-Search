<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lexhoy-despachos-search">
    <div class="search-header">
        <div class="search-title">
            | Busca alfabéticamente o por nombre en nuestra lista de abogados: |
        </div>

        <div class="alphabet-container">
            <?php
            $letters = range('A', 'Z');
            foreach ($letters as $letter) {
                echo '<div class="alphabet-letter" data-letter="' . $letter . '">' . $letter . '</div>';
            }
            ?>
        </div>

        <div id="searchbox"></div>
    </div>

    <div class="search-content">
        <div class="filters-sidebar">
            <div class="filters-tabs">
                <div class="filters-tab-header">
                    <button class="filter-tab-btn active" data-tab="province">Provincias</button>
                    <button class="filter-tab-btn" data-tab="location">Localidades</button>
                    <button class="filter-tab-btn" data-tab="practice">Áreas</button>
                </div>
                <div class="filters-tab-content">
                    <div id="province-list" class="filter-tab-pane active"></div>
                    <div id="location-list" class="filter-tab-pane"></div>
                    <div id="practice-list" class="filter-tab-pane"></div>
                </div>
            </div>
            <div id="current-refinements"></div>
        </div>
        <div class="results-sidebar">
            <div id="hits" class="results-container"></div>
            <div id="pagination"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Búsqueda por letra
    const alphabetLetters = document.querySelectorAll('.alphabet-letter');
    alphabetLetters.forEach(letter => {
        letter.addEventListener('click', function() {
            const searchInput = document.getElementById('searchbox');
            searchInput.value = this.dataset.letter;
            searchInput.dispatchEvent(new Event('input'));
        });
    });
});
</script> 