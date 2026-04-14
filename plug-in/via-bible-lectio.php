<?php
/**
 * Plugin Name: Via Bible – Lectio Continua
 * Plugin URI:  https://via.bible
 * Description: Plans de lecture de la Bible avec progression stockée dans le navigateur. Aucune connexion requise.
 * Version:     1.0.0
 * Author:      via.bible
 * License:     GPL-2.0+
 * Text Domain: via-bible-lectio
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'VBL_VERSION',  '1.0.0' );
define( 'VBL_DIR',      plugin_dir_path( __FILE__ ) );
define( 'VBL_URL',      plugin_dir_url( __FILE__ ) );
define( 'VBL_PLANS_DIR', VBL_DIR . 'plans/' );

// ── Chargement ─────────────────────────────────────────────────────────────
require_once VBL_DIR . 'includes/ajax-handler.php';

// ── Enqueue ─────────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'vbl_enqueue' );
function vbl_enqueue() {
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'lectio_continua' ) ) return;

    wp_enqueue_style( 'vbl-style', VBL_URL . 'assets/lectio.css', [], VBL_VERSION );
    wp_enqueue_script( 'vbl-script', VBL_URL . 'assets/lectio.js', [], VBL_VERSION, true );

    wp_localize_script( 'vbl-script', 'VBL', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'vbl_nonce' ),
        'plans'   => vbl_get_plans_meta(),
        'i18n'    => [
            'selectPlan'   => __( 'Choisir un plan', 'via-bible-lectio' ),
            'dayOf'        => __( 'Jour %d sur %d', 'via-bible-lectio' ),
            'markRead'     => __( 'Marquer comme lu', 'via-bible-lectio' ),
            'markedRead'   => __( '✓ Lu aujourd\'hui', 'via-bible-lectio' ),
            'articles'     => __( 'Articles via.bible sur ce passage', 'via-bible-lectio' ),
            'noArticles'   => __( 'Aucun article disponible pour ce passage.', 'via-bible-lectio' ),
            'streak'       => __( 'Série en cours', 'via-bible-lectio' ),
            'days'         => __( 'jours', 'via-bible-lectio' ),
            'exportBtn'    => __( '⬇ Exporter ma progression', 'via-bible-lectio' ),
            'importBtn'    => __( '⬆ Importer une progression', 'via-bible-lectio' ),
            'importOk'     => __( 'Progression importée avec succès !', 'via-bible-lectio' ),
            'importFail'   => __( 'Fichier invalide.', 'via-bible-lectio' ),
            'resetConfirm' => __( 'Réinitialiser toute la progression ? Cette action est irréversible.', 'via-bible-lectio' ),
            'comprendre'   => __( 'Comprendre', 'via-bible-lectio' ),
            'mediter'      => __( 'Méditer', 'via-bible-lectio' ),
            'loading'      => __( 'Chargement…', 'via-bible-lectio' ),
        ],
    ]);
}

// ── Plans meta (noms, descriptions, durée) ──────────────────────────────────
function vbl_get_plans_meta() {
    return [
        'mcheyne' => [
            'id'          => 'mcheyne',
            'name'        => 'M\'Cheyne (classique)',
            'description' => '4 passages par jour – AT, NT, Psaumes, Épîtres. Le plan historique de Robert Murray M\'Cheyne (1842).',
            'days'        => 365,
            'readings_per_day' => 4,
            'testament'   => 'complet',
        ],
        'esvthroughthebible' => [
            'id'          => 'esvthroughthebible',
            'name'        => 'ESV – Bible complète en 1 an',
            'description' => '2 passages par jour, AT + NT en parallèle. Équilibré et accessible.',
            'days'        => 365,
            'readings_per_day' => 2,
            'testament'   => 'complet',
        ],
        'oneyearchronological' => [
            'id'          => 'oneyearchronological',
            'name'        => 'Bible chronologique en 1 an',
            'description' => 'Les textes dans l\'ordre chronologique des événements bibliques. Idéal pour comprendre la grande histoire.',
            'days'        => 365,
            'readings_per_day' => 1,
            'testament'   => 'complet',
        ],
        'backtothebiblechronological' => [
            'id'          => 'backtothebiblechronological',
            'name'        => 'Back to the Bible – Chronologique',
            'description' => 'Plan chronologique alternatif. 1 passage par jour sur 365 jours.',
            'days'        => 365,
            'readings_per_day' => 1,
            'testament'   => 'complet',
        ],
        'esveverydayinword' => [
            'id'          => 'esveverydayinword',
            'name'        => 'ESV – Chaque jour dans la Parole',
            'description' => '4 passages/jour : Genèse→Job, Matthieu→Actes, Psaumes, Proverbes. Très complet.',
            'days'        => 365,
            'readings_per_day' => 4,
            'testament'   => 'complet',
        ],
        'heartlightotandnt' => [
            'id'          => 'heartlightotandnt',
            'name'        => 'Heartlight – AT & NT en parallèle',
            'description' => '2 passages/jour, Ancien et Nouveau Testament simultanément. Plan Heartlight.',
            'days'        => 365,
            'readings_per_day' => 2,
            'testament'   => 'complet',
        ],
        'esvgospelsandepistles' => [
            'id'          => 'esvgospelsandepistles',
            'name'        => 'ESV – Évangiles & Épîtres',
            'description' => 'Uniquement le Nouveau Testament. Idéal pour les débutants ou un parcours de carême étendu.',
            'days'        => 365,
            'readings_per_day' => 1,
            'testament'   => 'nt',
        ],
        'esvpsalmsandwisdomliterature' => [
            'id'          => 'esvpsalmsandwisdomliterature',
            'name'        => 'ESV – Psaumes & Littérature de Sagesse',
            'description' => 'Psaumes, Proverbes, Ecclésiaste, Job, Cantique. Un plan de prière et de méditation.',
            'days'        => 365,
            'readings_per_day' => 1,
            'testament'   => 'at',
        ],
        'psaumes30' => [
            'id'          => 'psaumes30',
            'name'        => 'Psaumes en 30 jours',
            'description' => 'Les 150 Psaumes répartis sur 30 jours. 5 Psaumes par jour.',
            'days'        => 30,
            'readings_per_day' => 5,
            'testament'   => 'at',
        ],
        'nt90' => [
            'id'          => 'nt90',
            'name'        => 'Nouveau Testament en 90 jours',
            'description' => 'Les 27 livres du NT en 90 jours. Rythme soutenu, vision d\'ensemble.',
            'days'        => 90,
            'readings_per_day' => 2,
            'testament'   => 'nt',
        ],
    ];
}

// ── Shortcode ────────────────────────────────────────────────────────────────
add_shortcode( 'lectio_continua', 'vbl_shortcode' );
function vbl_shortcode( $atts ) {
    $atts = shortcode_atts( [ 'plan' => '' ], $atts );
    ob_start();
    ?>
    <div id="vbl-app" class="vbl-app" data-default-plan="<?php echo esc_attr( $atts['plan'] ); ?>">
        <div id="vbl-plan-selector" class="vbl-section">
            <h2 class="vbl-title">📖 Plans de lecture</h2>
            <p class="vbl-subtitle">Votre progression est sauvegardée dans votre navigateur — aucune inscription requise.</p>
            <div id="vbl-plans-grid" class="vbl-plans-grid"></div>
        </div>
        <div id="vbl-reader" class="vbl-section" style="display:none">
            <div class="vbl-reader-header">
                <button id="vbl-back-btn" class="vbl-btn vbl-btn-ghost">← Changer de plan</button>
                <div id="vbl-plan-title" class="vbl-plan-title"></div>
                <div id="vbl-streak" class="vbl-streak"></div>
            </div>
            <div class="vbl-progress-bar-wrap">
                <div id="vbl-progress-bar" class="vbl-progress-bar"></div>
                <span id="vbl-progress-label" class="vbl-progress-label"></span>
            </div>
            <div id="vbl-day-nav" class="vbl-day-nav">
                <button id="vbl-prev-day" class="vbl-btn vbl-btn-ghost">‹ Jour précédent</button>
                <span id="vbl-day-label" class="vbl-day-label"></span>
                <button id="vbl-next-day" class="vbl-btn vbl-btn-ghost">Jour suivant ›</button>
            </div>
            <div id="vbl-passages" class="vbl-passages"></div>
            <div id="vbl-articles" class="vbl-articles"></div>
            <div class="vbl-day-actions">
                <button id="vbl-mark-read" class="vbl-btn vbl-btn-primary">Marquer comme lu</button>
            </div>
            <div class="vbl-tools">
                <button id="vbl-export" class="vbl-btn vbl-btn-ghost">⬇ Exporter ma progression</button>
                <label class="vbl-btn vbl-btn-ghost" for="vbl-import-file">⬆ Importer une progression</label>
                <input type="file" id="vbl-import-file" accept=".json" style="display:none">
                <button id="vbl-reset" class="vbl-btn vbl-btn-danger">↺ Réinitialiser</button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
