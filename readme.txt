=== Dienstenoverzicht Plugin ===
Contributors: webactueel
Tags: diensten, shortcode, custom post type, csv, import, export
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Toont een veilig, beheerbaar dienstenoverzicht via het custom post type Diensten en de shortcode [dienstenoverzicht].

== Description ==

Dienstenoverzicht Plugin registreert een custom post type voor diensten, een categorie-taxonomie, metaboxen voor prijsinformatie en samenvatting, een shortcode met categorie- en zoekfiltering en CSV import/export voor beheerders.

De plugin gebruikt WordPress-native APIs, nonce/capability checks, gescopen assets en veilige escaping/sanitization voor admin- en frontend-output.

== Installation ==

1. Upload de pluginmap naar /wp-content/plugins/.
2. Activeer de plugin via Plugins.
3. Voeg diensten toe via Diensten.
4. Plaats [dienstenoverzicht] op een pagina.

== Frequently Asked Questions ==

= Welke shortcode kan ik gebruiken? =

Gebruik [dienstenoverzicht]. Optioneel kun je een startcategorie meegeven met [dienstenoverzicht category="123"].

= Verwijdert de plugin diensten bij uninstall? =

Nee. De uninstall-handler verwijdert alleen pluginopties. Diensten blijven bewust bewaard om onbedoeld dataverlies te voorkomen.

== Changelog ==

= 3.0.1 =
* Compatibiliteit gecontroleerd in een schone runtime op WordPress 6.0/PHP 7.4 en WordPress 7.1/PHP 8.3.
* GitHub Actions-compatibiliteitsgate toegevoegd voor toekomstige WordPress-upgrades.

= 3.0.0 =
* Release-hardening voor security, WPCS-readiness, accessibility en packaging.
* AJAX-respons omgezet naar wp_send_json_success().
* Admin CSS volledig gescoped.
* Settings sanitization aangescherpt voor CSS-length waardes.
* CSV import/export verder gehard met publish-only export, upload error checks en veilige formula-prefix roundtrip.
* Plugin headers en readme bijgewerkt naar WordPress 6.9 readiness.

= 2.5.0 =
* Bootstrap opgeschoond.
* Dubbele hook-registraties verwijderd.
* CSV import/export veiliger en schaalbaarder gemaakt.
* Dode upload- en voorbeeldcode verwijderd.
* Frontend assets en AJAX-filtering opgeschoond.