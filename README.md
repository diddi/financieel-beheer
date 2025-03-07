## Automatische processen instellen

### Terugkerende transacties verwerken

Voor het automatisch verwerken van terugkerende transacties en het versturen van herinneringen moet je een cron job instellen. Dit zorgt ervoor dat gebruikers op tijd worden herinnerd aan aankomende transacties en dat terugkerende transacties automatisch worden uitgevoerd.

#### Linux/Unix/macOS (via crontab):

```bash
# Open de crontab editor
crontab -e

# Voeg de volgende regel toe (aangepast aan jouw bestandspad):
0 0 * * * php /volledige/pad/naar/je/project/cron/process_recurring.php