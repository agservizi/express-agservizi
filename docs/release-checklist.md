# Checklist rilascio (vendita)

Data: 2026-02-01

## Stato generale
- Alcune attività richiedono ambiente di staging/produzione e accessi infrastrutturali.
- Test automatici disponibili: PHPUnit installato e test unitari OK.

## Verifiche completate in workspace
- Isolamento tenant a livello applicativo (tenant_id nei servizi principali): completato.
- Personalizzazione scontrino per tenant (config per-tenant): completato.
- Privacy/GDPR: policy aggiornata e assegnata al tenant: completato.

## Attività da completare (richiedono ambiente/azioni esterne)
1) Verifica isolamento tenant end‑to‑end (login tenant, CRUD principali, report).
   - Azione: test manuali con account tenant.

2) Test funzionali principali (vendite, clienti, prodotti, supporto, energia).
   - Azione: test manuali su staging.

3) Migrazioni e seed dati su ambiente clean.
   - Azione: provisioning database pulito + esecuzione migrations.

4) Backup/restore DB testati.
   - Azione: definire piano backup, eseguire restore di prova.

5) Monitoraggio/logging attivo + alert.
   - Azione: configurare log shipping / alerting.

6) Sicurezza: MFA, password policy, rate‑limit login, gestione sessioni.
   - Note: RateLimiter presente ma non integrato nel login. Session cookie hardening da verificare.

7) Licenze/tenant: flussi creazione/assegnazione/renewal.
   - Azione: testare flussi in UI.

8) Scontrino: personalizzazione per tenant verificata.
   - Azione: test UI con 2 tenant diversi.

9) Performance: carichi base e paginazioni.
   - Azione: test con dataset realistico.

10) Documentazione utente e supporto.
   - Azione: aggiornare manuali/FAQ.

11) Piano rollback e versioning.
   - Azione: definire versione e rollback plan.

## Test automatici
- PHPUnit: **OK** (27 test, 35 assertion).
