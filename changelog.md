![Mollie](https://www.mollie.nl/files/Mollie-Logo-Style-Small.png)

# Changelog #

## Changes in release 4.3.0 ##
+ Added payment method renaming, sort order and limiting availability to specific countries.
+ Added functionality which restores the cart for a user who cancels a payment on the Mollie payment screen or hits back.
+ Updated [Mollie API Client](https://github.com/mollie/mollie-api-php) to 1.9.2

## Changes in release 4.2.5 ##
+ Added payment method [KBC/CBC](https://www.mollie.com/kbccbc)
+ Updated [Mollie API Client](https://github.com/mollie/mollie-api-php) to 1.7.1

## Changes in release 4.2.4 ##
+ Fixed tests
+ Updated [Mollie API Client](https://github.com/mollie/mollie-api-php) to 1.7.0
+ Fix issue #44 - "Connection timeouts cause unhandled exceptions"

## Changes in release 4.2.3 ##
+ Using store url at webhook/redirecturl to truly enable multistore functions.
+ Added bank transfer due date (This will send a email to the customer when he/she uses bank transfer)

## Changes in release 4.2.2 ##
+ Use real store identifier when using a multi-store.
+ Small fixes/refactors for the Helpers.
+ Fixed the [unittests](https://travis-ci.org/mollie/Magento/builds/130845863).
+ Updated [API library](https://github.com/mollie/mollie-api-php) to `v1.5.1`.

## Changes in release 4.2.1 ##
+ Use store locale in a payment.
+ Updated [API library](https://github.com/mollie/mollie-api-php) to `v1.4.1`.

## Changes in release 4.2.0 ##
+ Small fixes for the Helper.

## Changes in release 4.1.9 ##
+ Don't validate the redirect url locally.

## Changes in release 4.1.8 ##
+ Stop Google Analytics from listing payment provider as referrer.

## Changes in release 4.1.7 ##
+ Resolve bug with full online refunds.

## Changes in release 4.1.6 ##
+ Support partial online refunds.

## Changes in release 4.1.5 ##
+ If a customer returns to Magento and his payment is not yet finalized, the module will now send the order email.

## Changes in release 4.1.4 ##
+ Probleem opgelost in admin voor multi store configuratie

## Changes in release 4.1.3 ##
+ Probleem opgelost waarbij een betaling die was aangemaakt in een multi-store configuratie niet kon worden gerapporteerd

## Changes in release 4.1.2 ##
+ Maak het mogelijk de naam van betaalmethodes aan te passen via `/app/design/frontend/default/mollie/locale/nl_NL/translate.csv`
+ Ondersteun multi-store configuraties
+ Toon juiste melding wanneer definitieve status betaling nog niet bekend is
+ Sta gedeeltelijke refunds via offline refunds toe (via online refunds kan alleen het gehele bedrag gerefund worden)

## Changes in release 4.1.1 ##
+ Better handling of Bank transfer and Bitcoin return pages.
+ Small bug fixes.

## Wijzigingen in versie 4.1.0 ##
+ Voegt online refund mogelijkheid toe
+ Stuurt automatisch de webhook mee
+ Zoekt naar nieuwe releases op github

## Wijzigingen in versie 4.0.8 ##
+ Maakt betaalmethoden zichtbaar bij intern aangemaakte orders
+ Lost een probleem met valutaconversie en creditmemos op
+ Geeft gecorrigeerde webhook weer bij URL's met store code

## Wijzigingen in versie 4.0.7 ##
+ Voegt een optie toe om automatisch mailen na een order uit te zetten
+ Voegt een probleemoplossend upgrade script toe

## Wijzigingen in versie 4.0.6 ##
+ Lost een probleem met valutaconversie op
+ Geeft een duidelijke waarschuwing wanneer een upgrade mislukt

## Wijzigingen in versie 4.0.5 ##
+ Lost een probleem met recursieve diepte op

## Wijzigingen in versie 4.0.4 ##
+ Rectificeert de volgorde van de betaalmethoden
+ Lost een probleem met dubbele betaalmethoden op
+ Onthoudt de winkelwagen voor als de betaling geannuleerd wordt
+ Biedt de mogelijkheid om de bankenlijst in de checkout weer te geven
+ Voegt webhookverificatie toe

## Wijzigingen in versie 4.0.2 ##
+ Probleem met uitgeschakelde betaalmethoden opgelost.

## Wijzigingen in versie 4.0.1 ##
+ Probleem opgelost waarbij oude orders zochten naar een niet-meer-bestaande betaalmethode.

## Wijzigingen in versie 4.0.0 ##
+ De module gebruikt nu de nieuwe betalings-API van Mollie. Dit betekent dat de module naast [iDEAL](https://www.mollie.nl/betaaldiensten/ideal/), nu
    ook [creditcard](https://www.mollie.nl/betaaldiensten/creditcard/), [Mister Cash](https://www.mollie.nl/betaaldiensten/mistercash/) en
    [paysafecard](https://www.mollie.nl/betaaldiensten/paysafecard/) ondersteunt. Mocht een betaling om wat voor reden dan ook niet lukken, dat kan uw
    klant het gelijk nog een keer proberen. U hoeft hiervoor niets extra's te implementeren. In de toekomst zullen ook nog nieuwe betaalmethodes
    toegevoegd worden. Deze zijn dan direct beschikbaar in uw webshop.
+ Het instellingenscherm is verplaatst naar Payment Methods. Er zijn nieuwe links in het hoofdmenu om de navigatie te vereenvoudigen.
+ Er worden automatisch facturen gegenereerd bij succesvolle betalingen. Dit was een veelgevraagde feature van onze klanten.
+ Engelse en Franse vertalingen zijn toegevoegd.

## Wijzigingen in versie 3.15 ##
+ Verbeter communicatie met Mollie bij bepaalde SSL fouten.
+ Toon foutmelding tijdens afrekenen wanneer de bankenlijst niet opgehaald kan worden.

## Wijzigingen in versie 3.14 ##
+ Problemen opgelost met upgraden vanaf versie 3.12.
+ Toon juiste meldingen wanneer klant terugkomt op de website na een mislukte iDEAL betaling.

## Wijzigingen in versie 3.13 ##
+ Performance verbeterd voor webshops met grote hoeveelheden betalingen.
+ iDEAL betalingen nu ook mogelijk bij factuuradressen buiten Nederland.
+ Controleer compatibiliteit met geinstalleerde Magento versie.
+ Vanaf deze versie wordt de kwaliteit van de module bewaakt door de open source continuous integration server [Travis CI](https://travis-ci.org/mollie/magento)

## Wijzigingen in versie 3.12 ##
+ Probleem opgelost waarbij in layouts met twee kolommen op de categoriepagina de rechter kolom verdween.

## Wijzigingen in versie 3.11 ##
+ Probleem opgelost waardoor op sommige webservers de bankenlijst leeg bleef.
+ Wijzig link naar de nieuwe Mollie profielenpagina
+ Verwijder beperking dat factuuradres in Nederland moet zijn om iDEAL te kunnen gebruiken.
+ Los klein HTML probleem op

## Wijzigingen in versie 3.10 ##
+ Probleem opgelost waardoor op sommige webservers de bankenlijst leeg bleef.

## Wijzigingen in versie 3.9 ##
+ Probleem opgelost waardoor sommige klanten na een betalingsfout de order niet opnieuw konden afrekenen.

## Wijzigingen in versie 3.8 ##
+ Probleem opgelost waarbij het betaalde bedrag niet bij de order werd opgeslagen.
