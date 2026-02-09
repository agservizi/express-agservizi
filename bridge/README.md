# Bridge CUSTOM RT (XON/XOFF)

Bridge locale HTTP per registratori di cassa CUSTOM BIG PLUS con protocollo XON/XOFF.
Espone un endpoint HTTP utilizzabile dal browser e inoltra i comandi in TCP alla stampante.

## Requisiti
- Node.js 18+ (consigliato LTS)

## Setup rapido
1) Copia il file di configurazione:

```
cp config.example.json config.json
```

2) Modifica config.json con IP/porta della stampante e una chiave API.

3) Avvia il bridge:

```
npm start
```

Il bridge ascolta su http://127.0.0.1:4789

## UI locale
Apri nel browser:

```
http://127.0.0.1:4789/ui
```

Da qui puoi salvare API key, device ID, IP/porta stampante e altri parametri. La UI aggiorna `config.json`.

### Ricerca stampante
Nella UI c'e' il pulsante "Cerca stampante" che scansiona la rete locale /24 sulla porta TCP indicata (default 9100).

## Autostart
Gli script di installazione sono in `bridge/install`.

### macOS (LaunchAgent)
1) Copia e configura `config.json`.
2) Rendi eseguibile lo script (solo la prima volta):

```
chmod +x bridge/install/macos/install.sh bridge/install/macos/uninstall.sh
```

3) Esegui lo script:

```
./bridge/install/macos/install.sh
```

Per rimuovere:

```
./bridge/install/macos/uninstall.sh
```

### Windows (Task Scheduler)
1) Copia e configura `config.json`.
2) Esegui da PowerShell:

```
powershell -ExecutionPolicy Bypass -File bridge\install\windows\install.ps1
```

Per rimuovere:

```
powershell -ExecutionPolicy Bypass -File bridge\install\windows\uninstall.ps1
```

## Configurazione
Esempio config.json:

```
{
  "host": "127.0.0.1",
  "port": 4789,
  "api_key": "change-me",
  "command_delay_ms": 120,
  "terminator": "",
  "devices": {
    "cassa_1": {
      "host": "192.168.1.50",
      "port": 9100,
      "terminator": ""
    }
  }
}
```

- `terminator`: terminatore da aggiungere a ogni comando (vuoto di default).
- `command_delay_ms`: delay tra comandi per evitare overflow.

## API
Tutte le richieste devono includere header `X-Bridge-Key` con la chiave API.

### GET /health
Ritorna stato del bridge.

### GET /devices
Lista dei device configurati.

### POST /send
Invia comandi grezzi.

Body:
```
{
  "device_id": "cassa_1",
  "commands": ["100H1R", "=", "1T"]
}
```

### POST /receipt
Crea uno scontrino fiscale da righe articolo.

Body:
```
{
  "device_id": "cassa_1",
  "operator": 1,
  "customer_tax_code": "RSSMRA80A01H501U",
  "items": [
    {"desc": "PROVA", "price_cents": 123, "dept": 1},
    {"qty": 2, "desc": "ACCESSORIO", "price_cents": 500, "dept": 2}
  ],
  "payment": {"type": "cash", "amount_cents": 1000}
}
```

Note:
- `dept` usa il comando `R` (reparto).
- `plu` usa il comando `P` (PLU).
- `price_cents` e `amount_cents` sono in centesimi.

### POST /void
Annulla scontrino aperto (`25F`).

Body:
```
{ "device_id": "cassa_1" }
```

### POST /z-close
Chiusura fiscale Z (`z1Fc`).

### POST /x-report
Lettura giornaliera X (`x1Fc`).

### POST /status
Stato registratore (`89F`).

### POST /drawer
Apertura cassetto (`a`).

## Note protocollo
- Se la stampante risponde con XOFF, il bridge restituisce errore.
- Se il modello richiede un terminatore diverso, impostarlo in config.json.
