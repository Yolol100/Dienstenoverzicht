<?php
/**
 * CSV formula-prefix regression for the existing export/import contract.
 * No site, database, spreadsheet application or network is required.
 */

define( 'ABSPATH', __DIR__ . '/' );
require_once dirname( __DIR__ ) . '/includes/class-dienstenoverzicht-import-export.php';

$escape = new ReflectionMethod( 'Dienstenoverzicht_ImportExport', 'escape_csv_formula' );
$escape->setAccessible( true );
$unescape = new ReflectionMethod( 'Dienstenoverzicht_ImportExport', 'remove_csv_formula_prefix' );
$unescape->setAccessible( true );

$cases = [
    [ '=1+2', true ],
    [ '+3', true ],
    [ '-4', true ],
    [ '@SUM(1)', true ],
    [ "  \t=1+2", true ],
    [ "\t=1+2", true ],
    [ "\r+3", true ],
    [ "\n@SUM(1)", true ],
    [ "'=1+2", true ],
    [ "''=1+2", true ],
    [ "Normal description", false ],
    [ " Text only", false ],
    [ "'Normal title", false ],
];

foreach ( $cases as $case ) {
    list( $original, $must_escape ) = $case;
    $encoded = $escape->invoke( null, $original );
    $expected_prefix = $must_escape ? "'" . $original : $original;
    if ( $encoded !== $expected_prefix ) {
        fwrite( STDERR, 'CSV formula prefix mismatch for ' . json_encode( $original ) . PHP_EOL );
        exit( 1 );
    }
    $decoded = $unescape->invoke( null, $encoded );
    if ( $decoded !== $original ) {
        fwrite( STDERR, 'CSV formula roundtrip changed ' . json_encode( $original ) . PHP_EOL );
        exit( 1 );
    }
}

echo "Dienstenoverzicht CSV formula prefix and roundtrip: OK\n";
