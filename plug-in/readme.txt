=== Via Bible – Lectio Continua ===
Contributors: via.bible
Tags: bible, lecture, plan de lecture, lectio, progression
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
License: GPLv2 or later

== Description ==

Plugin WordPress pour via.bible : plans de lecture de la Bible avec suivi de progression
stocké dans le navigateur. Aucune inscription requise, aucune charge serveur excessive.

**Plans disponibles (10 plans) :**
- M'Cheyne – 4 passages/jour (classique, 1842)
- ESV Bible complète en 1 an
- Bible chronologique (ESV One Year Chronological)
- Back to the Bible Chronologique
- ESV Chaque jour dans la Parole (4 passages/jour)
- Heartlight AT & NT en parallèle
- ESV Évangiles & Épîtres (NT uniquement)
- ESV Psaumes & Littérature de Sagesse
- Psaumes en 30 jours (5 Psaumes/jour)
- Nouveau Testament en 90 jours

**Fonctionnalités :**
- Progression dans localStorage (pas de login requis)
- Affichage automatique des articles via.bible liés aux passages
- Suivi du streak (jours consécutifs)
- Barre de progression par plan
- Export/Import JSON de la progression (multi-device)
- Navigation jour par jour
- Responsive mobile

== Installation ==

1. Uploader le dossier `via-bible-lectio` dans `/wp-content/plugins/`
2. Activer le plugin dans l'admin WordPress
3. Placer le shortcode `[lectio_continua]` dans une page

**Shortcode optionnel avec plan par défaut :**
`[lectio_continua plan="mcheyne"]`

**IDs de plans disponibles :**
mcheyne, esvthroughthebible, oneyearchronological, backtothebiblechronological,
esveverydayinword, heartlightotandnt, esvgospelsandepistles,
esvpsalmsandwisdomliterature, psaumes30, nt90

== Prérequis via.bible ==

Le plugin interroge les taxonomies WordPress `book`, `chapter` et `article_type`
(déjà présentes sur via.bible via le plugin Constellations).
Si les taxonomies ont des slugs différents, adapter `vbl_parse_passage()` dans
`includes/ajax-handler.php`.

== Changelog ==

= 1.0.0 =
* Version initiale

