<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Charger un plan JSON ─────────────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_vbl_get_plan', 'vbl_ajax_get_plan' );
add_action( 'wp_ajax_vbl_get_plan',        'vbl_ajax_get_plan' );
function vbl_ajax_get_plan() {
    check_ajax_referer( 'vbl_nonce', 'nonce' );

    $plan_id = sanitize_key( $_POST['plan_id'] ?? '' );

    // Plans embarqués (psaumes30, nt90)
    $embedded = vbl_get_embedded_plans();
    if ( isset( $embedded[ $plan_id ] ) ) {
        wp_send_json_success( [ 'days' => $embedded[ $plan_id ] ] );
        return;
    }

    // Plans depuis fichiers JSON
    $allowed = [
        'mcheyne', 'esvthroughthebible', 'oneyearchronological',
        'backtothebiblechronological', 'esveverydayinword',
        'heartlightotandnt', 'esvgospelsandepistles', 'esvpsalmsandwisdomliterature'
    ];
    if ( ! in_array( $plan_id, $allowed, true ) ) {
        wp_send_json_error( 'Plan inconnu.' );
        return;
    }

    $file = VBL_PLANS_DIR . $plan_id . '.json';
    if ( ! file_exists( $file ) ) {
        wp_send_json_error( 'Fichier plan introuvable.' );
        return;
    }

    $raw  = file_get_contents( $file );
    $data = json_decode( $raw, true );
    $days = $data['data2'] ?? $data['data1'] ?? [];

    wp_send_json_success( [ 'days' => $days ] );
}

// ── Plans embarqués directement en PHP ──────────────────────────────────────
function vbl_get_embedded_plans() {
    // Psaumes en 30 jours : 5 Psaumes par jour
    $psaumes = [];
    $ps_groups = [
        [1,2,3,4,5],[6,7,8,9,10],[11,12,13,14,15],[16,17,18,19,20],
        [21,22,23,24,25],[26,27,28,29,30],[31,32,33,34,35],[36,37,38,39,40],
        [41,42,43,44,45],[46,47,48,49,50],[51,52,53,54,55],[56,57,58,59,60],
        [61,62,63,64,65],[66,67,68,69,70],[71,72,73,74,75],[76,77,78,79,80],
        [81,82,83,84,85],[86,87,88,89,90],[91,92,93,94,95],[96,97,98,99,100],
        [101,102,103,104,105],[106,107,108,109,110],[111,112,113,114,115],
        [116,117,118,119],[120,121,122,123,124,125,126,127,128,129,130],
        [131,132,133,134,135],[136,137,138,139,140],[141,142,143,144,145],
        [146,147,148,149],[150],
    ];
    foreach ( $ps_groups as $group ) {
        $psaumes[] = array_map( fn($n) => "Psaumes $n", $group );
    }

    // NT en 90 jours
    $nt90 = [
        ["Matthieu 1-3"],["Matthieu 4-6"],["Matthieu 7-9"],["Matthieu 10-12"],
        ["Matthieu 13-14"],["Matthieu 15-17"],["Matthieu 18-20"],["Matthieu 21-22"],
        ["Matthieu 23-24"],["Matthieu 25-26"],["Matthieu 27-28"],
        ["Marc 1-2"],["Marc 3-4"],["Marc 5-6"],["Marc 7-8"],["Marc 9-10"],
        ["Marc 11-12"],["Marc 13-14"],["Marc 15-16"],
        ["Luc 1-2"],["Luc 3-4"],["Luc 5-6"],["Luc 7-8"],["Luc 9-10"],
        ["Luc 11-12"],["Luc 13-14"],["Luc 15-16"],["Luc 17-18"],
        ["Luc 19-20"],["Luc 21-22"],["Luc 23-24"],
        ["Jean 1-2"],["Jean 3-4"],["Jean 5-6"],["Jean 7-8"],["Jean 9-10"],
        ["Jean 11-12"],["Jean 13-14"],["Jean 15-16"],["Jean 17-18"],
        ["Jean 19-20"],["Jean 21"],
        ["Actes 1-2"],["Actes 3-4"],["Actes 5-6"],["Actes 7-8"],["Actes 9-10"],
        ["Actes 11-12"],["Actes 13-14"],["Actes 15-16"],["Actes 17-18"],
        ["Actes 19-20"],["Actes 21-22"],["Actes 23-24"],["Actes 25-26"],["Actes 27-28"],
        ["Romains 1-2"],["Romains 3-4"],["Romains 5-6"],["Romains 7-8"],
        ["Romains 9-10"],["Romains 11-12"],["Romains 13-14"],["Romains 15-16"],
        ["1 Corinthiens 1-3"],["1 Corinthiens 4-6"],["1 Corinthiens 7-9"],
        ["1 Corinthiens 10-12"],["1 Corinthiens 13-16"],
        ["2 Corinthiens 1-4"],["2 Corinthiens 5-9"],["2 Corinthiens 10-13"],
        ["Galates 1-3"],["Galates 4-6"],
        ["Éphésiens 1-3"],["Éphésiens 4-6"],
        ["Philippiens 1-4"],["Colossiens 1-4"],
        ["1 Thessaloniciens 1-5"],["2 Thessaloniciens 1-3"],
        ["1 Timothée 1-6"],["2 Timothée 1-4"],["Tite 1-3"],["Philémon"],
        ["Hébreux 1-4"],["Hébreux 5-9"],["Hébreux 10-13"],
        ["Jacques 1-5"],["1 Pierre 1-5"],["2 Pierre 1-3"],
        ["1 Jean 1-5"],["2 Jean"],["3 Jean"],["Jude"],
        ["Apocalypse 1-3"],["Apocalypse 4-7"],["Apocalypse 8-11"],
        ["Apocalypse 12-15"],["Apocalypse 16-18"],["Apocalypse 19-22"],
    ];

    return [
        'psaumes30' => $psaumes,
        'nt90'      => $nt90,
    ];
}

// ── Recherche d'articles via.bible pour un passage ──────────────────────────
add_action( 'wp_ajax_nopriv_vbl_get_articles', 'vbl_ajax_get_articles' );
add_action( 'wp_ajax_vbl_get_articles',        'vbl_ajax_get_articles' );
function vbl_ajax_get_articles() {
    check_ajax_referer( 'vbl_nonce', 'nonce' );

    $passages = array_map( 'sanitize_text_field', (array)( $_POST['passages'] ?? [] ) );
    if ( empty( $passages ) ) {
        wp_send_json_success( [] );
        return;
    }

    $results = [];

    foreach ( $passages as $passage ) {
        // Parser "Genesis 1" → book=genesis, chapter=1
        $parsed = vbl_parse_passage( $passage );
        if ( ! $parsed ) continue;

        [ $book_slug, $chapters ] = $parsed;

        foreach ( $chapters as $chapter ) {
            $tax_query = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'book',
                    'field'    => 'slug',
                    'terms'    => $book_slug,
                ],
            ];

            if ( $chapter ) {
                $tax_query[] = [
                    'taxonomy' => 'chapter',
                    'field'    => 'slug',
                    'terms'    => $book_slug . '-' . $chapter,
                ];
            }

            $query = new WP_Query([
                'post_type'      => 'post',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'tax_query'      => $tax_query,
                'fields'         => 'ids',
            ]);

            foreach ( $query->posts as $post_id ) {
                if ( isset( $results[ $post_id ] ) ) continue;

                // Déterminer le type d'article (Comprendre / Méditer / autre)
                $article_type = '';
                $type_terms   = get_the_terms( $post_id, 'article_type' );
                if ( $type_terms && ! is_wp_error( $type_terms ) ) {
                    $article_type = $type_terms[0]->name;
                }

                $results[ $post_id ] = [
                    'id'           => $post_id,
                    'title'        => get_the_title( $post_id ),
                    'url'          => get_permalink( $post_id ),
                    'article_type' => $article_type,
                    'passage'      => $passage,
                ];
            }
        }
    }

    wp_send_json_success( array_values( $results ) );
}

// ── Parser un passage texte → [book_slug, [chapters]] ───────────────────────
function vbl_parse_passage( $passage ) {
    // Table de correspondance noms anglais → slugs français via.bible
    $book_map = [
        'genesis' => 'genese', 'gen' => 'genese',
        'exodus' => 'exode', 'exod' => 'exode',
        'leviticus' => 'levitique', 'lev' => 'levitique',
        'numbers' => 'nombres', 'num' => 'nombres',
        'deuteronomy' => 'deuteronome', 'deut' => 'deuteronome',
        'joshua' => 'josue', 'josh' => 'josue',
        'judges' => 'juges', 'judg' => 'juges',
        'ruth' => 'ruth',
        '1 samuel' => '1-samuel', '1samuel' => '1-samuel',
        '2 samuel' => '2-samuel', '2samuel' => '2-samuel',
        '1 kings' => '1-rois', '1kings' => '1-rois',
        '2 kings' => '2-rois', '2kings' => '2-rois',
        '1 chronicles' => '1-chroniques', '1chronicles' => '1-chroniques',
        '2 chronicles' => '2-chroniques', '2chronicles' => '2-chroniques',
        'ezra' => 'esdras',
        'nehemiah' => 'nehemie', 'neh' => 'nehemie',
        'esther' => 'esther',
        'job' => 'job',
        'psalms' => 'psaumes', 'psalm' => 'psaumes', 'ps' => 'psaumes',
        'psaumes' => 'psaumes',
        'proverbs' => 'proverbes', 'prov' => 'proverbes',
        'ecclesiastes' => 'ecclesiaste', 'eccl' => 'ecclesiaste',
        'song of solomon' => 'cantique', 'song' => 'cantique',
        'isaiah' => 'isaie', 'isa' => 'isaie',
        'jeremiah' => 'jeremie', 'jer' => 'jeremie',
        'lamentations' => 'lamentations', 'lam' => 'lamentations',
        'ezekiel' => 'ezechiel', 'ezek' => 'ezechiel',
        'daniel' => 'daniel', 'dan' => 'daniel',
        'hosea' => 'osee', 'hos' => 'osee',
        'joel' => 'joel',
        'amos' => 'amos',
        'obadiah' => 'abdias', 'obad' => 'abdias',
        'jonah' => 'jonas', 'jon' => 'jonas',
        'micah' => 'michee', 'mic' => 'michee',
        'nahum' => 'nahum', 'nah' => 'nahum',
        'habakkuk' => 'habacuc', 'hab' => 'habacuc',
        'zephaniah' => 'sophonie', 'zeph' => 'sophonie',
        'haggai' => 'aggee', 'hag' => 'aggee',
        'zechariah' => 'zacharie', 'zech' => 'zacharie',
        'malachi' => 'malachie', 'mal' => 'malachie',
        'matthew' => 'matthieu', 'matt' => 'matthieu', 'matthieu' => 'matthieu',
        'mark' => 'marc', 'marc' => 'marc',
        'luke' => 'luc', 'luc' => 'luc',
        'john' => 'jean', 'jean' => 'jean',
        'acts' => 'actes', 'actes' => 'actes',
        'romans' => 'romains', 'rom' => 'romains', 'romains' => 'romains',
        '1 corinthians' => '1-corinthiens', '1corinthians' => '1-corinthiens', '1 corinthiens' => '1-corinthiens',
        '2 corinthians' => '2-corinthiens', '2corinthians' => '2-corinthiens', '2 corinthiens' => '2-corinthiens',
        'galatians' => 'galates', 'gal' => 'galates', 'galates' => 'galates',
        'ephesians' => 'ephesiens', 'eph' => 'ephesiens', 'éphésiens' => 'ephesiens',
        'philippians' => 'philippiens', 'phil' => 'philippiens', 'philippiens' => 'philippiens',
        'colossians' => 'colossiens', 'col' => 'colossiens', 'colossiens' => 'colossiens',
        '1 thessalonians' => '1-thessaloniciens', '1 thessaloniciens' => '1-thessaloniciens',
        '2 thessalonians' => '2-thessaloniciens', '2 thessaloniciens' => '2-thessaloniciens',
        '1 timothy' => '1-timothee', '1 timothée' => '1-timothee',
        '2 timothy' => '2-timothee', '2 timothée' => '2-timothee',
        'titus' => 'tite', 'tite' => 'tite',
        'philemon' => 'philemon', 'philémon' => 'philemon',
        'hebrews' => 'hebreux', 'heb' => 'hebreux', 'hébreux' => 'hebreux',
        'james' => 'jacques', 'jas' => 'jacques', 'jacques' => 'jacques',
        '1 peter' => '1-pierre', '1 pierre' => '1-pierre',
        '2 peter' => '2-pierre', '2 pierre' => '2-pierre',
        '1 john' => '1-jean', '1 jean' => '1-jean',
        '2 john' => '2-jean', '2 jean' => '2-jean',
        '3 john' => '3-jean', '3 jean' => '3-jean',
        'jude' => 'jude',
        'revelation' => 'apocalypse', 'rev' => 'apocalypse', 'apocalypse' => 'apocalypse',
    ];

    $passage = trim( $passage );

    // Retirer les références de versets (Genèse 1:1-2:3 → Genèse 1-2)
    $passage = preg_replace( '/:\d+(-\d+)?/', '', $passage );

    // Extraire le nom du livre et les chapitres
    // Formes : "Genesis 1", "Genesis 1-3", "1 Samuel 5", "Psaumes 119"
    if ( ! preg_match( '/^(\d\s+)?([a-zA-ZÀ-ÿ\s]+?)\s+(\d+)(?:-(\d+))?$/u', $passage, $m ) ) {
        return null;
    }

    $book_raw = strtolower( trim( ( $m[1] ?? '' ) . $m[2] ) );
    $ch_start = (int) $m[3];
    $ch_end   = isset( $m[4] ) ? (int) $m[4] : $ch_start;

    $book_slug = $book_map[ $book_raw ] ?? $book_raw;

    $chapters = [];
    for ( $c = $ch_start; $c <= min( $ch_end, $ch_start + 5 ); $c++ ) {
        $chapters[] = $c;
    }

    return [ $book_slug, $chapters ];
}
