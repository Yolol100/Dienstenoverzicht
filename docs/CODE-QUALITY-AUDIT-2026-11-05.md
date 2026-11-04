# Repository 4/37 — Dienstenoverzicht codekwaliteitslog

Audit: **5 november 2026**. Bron: `Yolol100/Dienstenoverzicht`, `main` basis `30f305947868aca1c0d898d17861585575fd1e25`. Concept: [PR #5](https://github.com/Yolol100/Dienstenoverzicht/pull/5), branch `quality/shortcode-a11y-csv-20261104`. **Geen merge, sitewijziging of release.**

## Bevestigde fouten en regressiebewijs

| ID | Ernst | Diagnose en mislukte test | Minimale reparatie en hertest |
| --- | --- | --- | --- |
| DO-001 | Hoog / betrouwbaarheid | De WordPress Shortcode API kan bij `[dienstenoverzicht]` een lege *string* aan de callback doorgeven. De methode accepteerde alleen `array`. Een echte WP 6.0/PHP 7.4-runtime crashte met `TypeError`: [rode CI](https://github.com/Yolol100/Dienstenoverzicht/actions/runs/41847525837). | `render_shortcode()` accepteert de oorspronkelijke invoervorm en normaliseert naar array voor `shortcode_atts()`. [Groene CI](https://github.com/Yolol100/Dienstenoverzicht/actions/runs/41847797478). |
| DO-002 | Middel / toegankelijkheid | Twee shortcodes op dezelfde pagina kregen dezelfde `id=\"dienstenoverzicht-categorie\"` en `id=\"dienstenoverzicht-results\"`. Formulierlabels en ARIA-landmarks werden ambigu; dezelfde [rode CI](https://github.com/Yolol100/Dienstenoverzicht/actions/runs/41847525837) bevestigde dubbele ID's op WP 7.1/PHP 8.3. | Eén statische teller binnen de bestaande shortcode-eigenaar geeft labels, select en resultaten unieke ID's. De bestaande `class`-selectors blijven behouden; [groene CI](https://github.com/Yolol100/Dienstenoverzicht/actions/runs/41847797478). |
| DO-003 | Middel / CSV-risico | De export beveiligde `=1+2`, maar niet `  \t=1+2`, `\r+3` of vergelijkbare gemaskeerde spreadsheetformules. De zelfstandige export/import-ronde faalde op beide PHP-versies: [rode CI](https://github.com/Yolol100/Dienstenoverzicht/actions/runs/41848185959). | De bestaande CSV-helpers delen nu een typebewuste formule-prefixcontrole voor whitespace, control characters en full-width-operatoren. De apostrofbeveiliging blijft behouden voor de eigen CSV-herimport. [Hertest na wijziging](https://github.com/Yolol100/Dienstenoverzicht/actions/runs/41882761815) (WordPress 6.0/PHP 7.4 en WP 7.1/PHP 8.3 beide geslaagd). |

## Relevante online bronnen

- [WordPress Shortcode API](https://developer.wordpress.org/apis/shortcode/): callback-`$atts` kan een lege string zijn; dus eerst normaliseren. Exact de bestaande API behouden in plaats van de shortcode anders te implementeren.
- [WordPress shortcode_atts](https://developer.wordpress.org/reference/functions/shortcode_atts/): onbekende attributen negeren en defaults behouden; gebruikt in de bestaande renderfunctie.
- [OWASP CSV Injection](https://owasp.org/www-community/attacks/CSV_Injection): controleer ook tabs, regeleinden, quotes en full-width formuleoperators; quote/apostrofbeveiliging is niet gegarandeerd na het openen, wijzigen en opslaan in alle spreadsheets.
- [10up/wp-scaffold](https://github.com/10up/wp-scaffold): CI-test van publieke functies; hier hergebruik van het idee, niet kopiëren van architectuur.

## Scope, QA en releaseblokkades

- Nulmeting: WordPress 6.0/PHP 7.4 en WordPress 7.1/PHP 8.3 activeerden de plugin al. De oude CI controleerde vooral syntaxis en activatie, niet shortcode-inhoud, dubbel-ID's of CSV-semantiek.
- Nieuw: echte WordPress/MySQL-regressie met twee shortcodes, een gepubliceerd testdienstbericht en taxonomieterm; losstaande CSV-helpers-regressie met normale, apostrof- en formuleachtige waarden.
- Behouden: post type `diensten`, shortcode-naam, bestaande CSS-klassen, AJAX-action, filtervelden, opties, import/export-kolomvolgorde en plugin API.
- **Niet bewezen:** browserinteractie van AJAX-filters (race conditions, netwerkfout en herhaalde snelle wijzigingen), schermlezers op verschillende devices, Excel/LibreOffice/Google Sheets na opslaan en heropenen, import-/export van echte klantdata, grotere imports, performance en WP multisite.
- Bestaande CSV-import kan updates aan bestaande dienstberichten uitvoeren. Niet op productie getest of uitgevoerd.
- Geen onbewezen dode code verwijderd; gegenereerde vertalingen en CSS/JS blijven bewust staan.
- Voor een release: originele pluginversie `3.0.1` en readme bijwerken na eigenaarstoestemming, distributie-ZIP bouwen en na uitpakken vergelijken, staging visueel testen en onafhankelijk QA.
- **Rollback:** PR sluiten. `main` en bestaande websites blijven ongewijzigd.

**Laatste run moet exact aansluiten op de finale commit.** Voor de definitieve PR-beoordeling is een nieuwe head-CI-uitslag nodig nadat dit log wordt toegevoegd.
