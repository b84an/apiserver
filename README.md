
# API Didattica per IIS Cobianchi

Questo progetto è un server API RESTful a scopo didattico, progettato per gli studenti dell'IIS Cobianchi di Verbania. Lo scopo è fornire un ambiente di test realistico ma controllato per imparare a interagire con le API web tramite strumenti come Postman o chiamate JavaScript (Fetch API).

L'API simula la gestione delle aule di un istituto scolastico e implementa un meccanismo di "stato temporaneo condiviso": le modifiche effettuate dagli utenti sono reali e visibili a tutti, ma vengono automaticamente ripristinate allo stato di default dopo un periodo di inattività, rendendo il sistema ideale per esercitazioni ripetute.

-----

## Funzionalità Principali

  * **Endpoint RESTful**: Implementazione completa dei metodi HTTP `GET`, `POST`, `PUT`, `DELETE` per la gestione delle risorse ("aule").
  * **Stato Temporaneo Condiviso**: Le modifiche ai dati sono persistenti per 120 secondi dall'ultima operazione di scrittura e condivise tra tutti gli client. Il sistema si auto-ripristina allo stato iniziale dopo il timeout.
  * **Autenticazione via API Key**: Tutte le richieste richiedono un'autenticazione tramite un HTTP Header personalizzato (`X-API-Key`).
  * **Filtro dei Dati**: L'endpoint `GET /api/aule` supporta i query parameters per filtrare i risultati (es. per piano o per disponibilità).
  * **Interfaccia Web Inclusa**: Il progetto comprende una pagina di documentazione e un simulatore web "Postman-like" per testare le funzionalità dell'API direttamente dal browser.

-----

## Struttura del Progetto

Il repository è organizzato come segue:

```
/
├── index.html              # Homepage con la documentazione completa
├── simulator.html          # Interfaccia web per testare l'API
├── assets/
│   └── style.css           # Foglio di stile
└── api/
    ├── .htaccess           # Regole di routing per Apache (mod_rewrite)
    ├── index.php           # Core dell'API (routing, logica, sessioni)
    └── data.php            # File con lo stato di default dei dati
```

-----

## Installazione e Configurazione

Per eseguire questo progetto, è necessario un server web con supporto a PHP (versione 7.x o superiore) e Apache con il modulo `mod_rewrite` abilitato.

1.  **Clonare o scaricare** il repository in una cartella del server.
2.  Assicurarsi che il server web abbia i **permessi di scrittura** per la directory delle sessioni di PHP, poiché l'API si basa su una sessione condivisa per gestire lo stato temporaneo.
3.  **Configurare il percorso**: Il file `api/.htaccess` è configurato per una sottocartella specifica (`/o/apiserver/api/`). Se si installa il progetto in un percorso diverso, aggiornare la direttiva `RewriteBase` nel file `.htaccess`.
4.  Navigare alla pagina `index.html` per visualizzare la documentazione e iniziare a usare il simulatore.

-----

## Guida all'Utilizzo dell'API

### Autenticazione

Tutte le chiamate all'API devono includere il seguente HTTP Header:

  * **Header**: `X-API-Key`
  * **Valore**: `COBIANCHI-SECRET-KEY-123`

In caso di chiave mancante o non valida, il server risponderà con uno stato `401 Unauthorized`.

### URL di Base

L'URL di base per tutti gli endpoint è: `[tuo-dominio]/[percorso-progetto]/api`

### Endpoints Disponibili

| Metodo | Endpoint | Descrizione | Body (Esempio) |
| :--- | :--- | :--- | :--- |
| `GET` | `/aule` | Restituisce la lista di tutte le aule. Supporta filtri come `?piano=1` o `?disponibile=true`. | N/A |
| `GET` | `/aule/{id}` | Restituisce i dettagli di una singola aula specificandone l'ID. | N/A |
| `POST`| `/aule` | Crea una nuova aula. I dati devono essere in formato JSON. | `{"nome": "Nuova Aula", "piano": 1}` |
| `PUT` | `/aule/{id}` | Aggiorna i dati di un'aula esistente. Permette aggiornamenti parziali. | `{"capienza": 30, "disponibile": false}` |
| `DELETE`| `/aule/{id}` | Elimina un'aula esistente. Restituisce uno stato `204 No Content`. | N/A |
| `GET` | `/errore-simulato` | Endpoint speciale che restituisce sempre un errore `500 Internal Server Error` per scopi di test. | N/A |

-----

## Roadmap e Sviluppi Futuri

Questa sezione delinea possibili evoluzioni del progetto, pensate per introdurre concetti più avanzati di sviluppo API.

### 1\. Paginazione dei Risultati

  * **Obiettivo Didattico**: Introdurre il concetto di paginazione, fondamentale quando si lavora con grandi quantità di dati. Le API reali non restituiscono mai migliaia di record in una sola chiamata.
  * **Implementazione Proposta**: Modificare l'endpoint `GET /api/aule` per accettare due nuovi query parameters: `limit` (per specificare il numero di risultati per pagina) e `page` (per specificare quale pagina visualizzare). L'API dovrebbe restituire non solo i dati, ma anche informazioni sulla paginazione (pagina corrente, totale pagine, totale risultati).
  * **Esempio di Chiamata**: `GET /api/aule?page=2&limit=10`

### 2\. Filtri Avanzati e Ricerca Testuale

  * **Obiettivo Didattico**: Espandere le capacità di interrogazione dell'API, simulando funzionalità di ricerca più complesse.
  * **Implementazione Proposta**: Evolvere la logica di filtraggio per permettere ricerche testuali nel nome dell'aula (es. `?search=laboratorio`) o per filtrare in base al contenuto di un array (es. `?attrezzature=LIM`). Questo insegna a gestire logiche di business più complesse nel backend.
  * **Esempio di Chiamata**: `GET /api/aule?search=chimica`

### 3\. Autenticazione Utente e Dati Privati

  * **Obiettivo Didattico**: Introdurre un sistema di autenticazione multi-utente per superare il limite dello stato condiviso. Ogni studente potrebbe avere una propria "sandbox" di dati su cui lavorare senza interferire con gli altri.
  * **Implementazione Proposta**: Creare nuovi endpoint come `POST /api/register` e `POST /api/login`. Il login restituirebbe un token di autenticazione (es. JWT o un token semplice). Le successive richieste dovrebbero includere questo token nell'header `Authorization` (es. `Bearer [token]`). Il server assocerebbe ogni dato creato (aule, ecc.) all'utente autenticato.
  * **Esempio di Header**: `Authorization: Bearer [token-restituito-dal-login]`

### 4\. Aggiunta di Risorse Correlate (Prenotazioni)

  * **Obiettivo Didattico**: Insegnare a progettare API con risorse multiple e a gestire le relazioni tra di esse (uno-a-molti).
  * **Implementazione Proposta**: Creare un nuovo endpoint `/prenotazioni`. Sarebbe possibile creare una prenotazione per un'aula specifica (`POST /api/prenotazioni` con `aula_id` nel body) e visualizzare tutte le prenotazioni per una data aula (`GET /api/aule/{id}/prenotazioni`). Questo introduce il concetto di endpoint annidati e relazioni tra dati.
  * **Esempio di Chiamata**: `GET /api/aule/35/prenotazioni`

-----

## Tecnologie Utilizzate

  * **Backend**: PHP
  * **Frontend**: HTML5, W3.CSS, JavaScript (vanilla)
  * **Server**: Apache (con `mod_rewrite`)

-----

## Autore

Progetto creato da **Ivan Bertotto** per la didattica presso l'IIS Cobianchi di Verbania.
[https://www.ivanbertotto.it](https://www.ivanbertotto.it)
