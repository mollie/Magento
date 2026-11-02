![Mollie](https://www.mollie.nl/files/Mollie-Logo-Style-Small.png)

# Upgrading #

* Controleer vóór de upgrade of uw server PHP 7.2 of hoger gebruikt, met de PHP-extensies ’curl’, ’json’ en ’openssl’. Bij een upgrade via deze stappen controleert niets dit voor u: op een oudere PHP-versie of zonder deze extensies werken de Mollie-betaalmethoden na de upgrade niet meer.
* Download de laatste versie van de module via de [Releases-pagina](https://github.com/mollie/Magento/releases).
* Kopieer alles in de mappen ’app’ en ’lib’ naar de mappen ’app’ en ’lib’ van uw Magento installatie. U kunt de bestaande bestanden vervangen.
* De module levert geen eigen Guzzle-bibliotheek meer mee. Bestaan de mappen ’lib/Mollie/GuzzleHttp’ en ’lib/Mollie/Psr’ nog van een eerdere versie, dan kunt u deze verwijderen.
* De betaalmethoden Klarna Pay Later, Klarna Pay Now en Klarna Slice it zijn vervangen door één betaalmethode ’Klarna’. Hun instellingen worden niet overgenomen: de nieuwe Klarna staat aan, voor alle landen, zonder minimum- of maximumbedrag en zonder betaalkosten. Stel na de upgrade de titel, landen, bedragen en betaalkosten van Klarna opnieuw in.
* Ga in het menu naar ‘Systeem’ en selecteer ‘Beheer Cache’ vervolgens klikt u op het knopje ‘Verwijder Magento cache’.
* Mocht u een 404 (pagina niet gevonden) melding zien, log dan uit en weer in.
* Ga in het menu naar het Mollie instellingenscherm en verwijder de bestanden die als ’Outdated’ zijn gemarkeerd.
* Vul uw accountgegevens in en sla de gegevens vervolgens op.

Controleer de versie van de module via ‘Systeem’ en ‘Configuratie’ en klik dan op het Mollie-logo.