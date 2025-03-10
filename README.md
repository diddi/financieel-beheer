# Financieel Beheer

Een webapplicatie voor het beheren van persoonlijke financiën, bijhouden van transacties, budgetteren en financiële rapportages.

## Functionaliteiten

- Transacties bijhouden en categoriseren
- Meerdere rekeningen beheren
- Budgetten instellen en monitoren
- Financiële rapportages bekijken
- Spaardoelen instellen en volgen
- Terugkerende transacties beheren
- Mobiel-vriendelijke interface

## Technische Details

- Ontwikkeld met PHP (zonder framework)
- MySQL database
- Tailwind CSS voor styling
- Responsive design voor optimale weergave op alle apparaten
- Progressive Web App (PWA) functionaliteit voor mobiel gebruik

## Installatie

1. Clone deze repository
2. Configureer een virtual host naar de `/public` directory
3. Importeer de SQL bestanden uit de `database` map
4. Kopieer `config/database.example.php` naar `config/database.php` en vul de juiste gegevens in
5. De applicatie zou nu bereikbaar moeten zijn via de geconfigureerde URL

## Development

### Vereisten

- PHP 7.4 of hoger
- MySQL 5.7 of hoger
- Composer (optioneel, voor eventuele dependencies)

### Setup

```bash
# Clone het project
git clone https://github.com/jouw-username/financieel-beheer.git
cd financieel-beheer

# Installeer dependencies (indien van toepassing)
# composer install

# Configuratie
cp config/database.example.php config/database.php
# Bewerk database.php met jouw credentials
```

## Deployment

De applicatie kan worden gedeployed op elke hosting die PHP en MySQL ondersteunt. Zorg ervoor dat de webserver is geconfigureerd om de `/public` directory te gebruiken als webroot.

## Licentie

Dit project is beschikbaar onder de MIT licentie.

### Terugkerende transacties verwerken

Voor het automatisch verwerken van terugkerende transacties en het versturen van herinneringen moet je een cron job instellen. Dit zorgt ervoor dat gebruikers op tijd worden herinnerd aan aankomende transacties en dat terugkerende transacties automatisch worden uitgevoerd.

#### Linux/Unix/macOS (via crontab):

```bash
# Open de crontab editor
crontab -e

# Voeg de volgende regel toe (aangepast aan jouw bestandspad):
0 0 * * * php /volledige/pad/naar/je/project/cron/process_recurring.php