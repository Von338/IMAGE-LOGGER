# Game Market MVP

Un **Minimum Viable Product** di un negozio digitale di videogiochi, ispirato ai principi descritti nel briefing. Questo è un MVP semplice ma funzionale che implementa le **3 funzionalità core** di un game market.

## 🎯 Le 3 Funzionalità Core (MVP)

### 1. 📋 Catalogo e Pagamento (La Vetrina)
- **Catalogo giochi** con immagini, prezzi e descrizioni
- **Ricerca** per titolo e descrizione
- **Pagina di dettaglio** per ogni gioco
- **Carrello della spesa** funzionale
- **Sistema di pagamento** integrato con Stripe
- **Gestione licenze digitali** dopo l'acquisto

### 2. 📚 Libreria e Download (La Tua Collezione)
- **Libreria personale** con tutti i giochi acquistati
- **Pulsante "Scarica"** per ogni gioco posseduto
- **Cronologia acquisti** con date
- **Accesso permanente** ai giochi acquistati

### 3. 👤 Profili Utente (La Tua Identità)
- **Registrazione** con email e password
- **Sistema di login/logout**
- **Profilo utente** collegato agli acquisti
- **Gestione sessioni** sicura

## 🚀 Caratteristiche Tecniche

- **Backend**: Flask (Python)
- **Database**: SQLite (per semplicità MVP)
- **Frontend**: Bootstrap 5 + HTML/CSS responsivo
- **Pagamenti**: Integrazione Stripe
- **Sicurezza**: Hash delle password, sessioni sicure

## 📦 Installazione e Avvio

1. **Installa le dipendenze**:
   ```bash
   pip install -r requirements.txt
   ```

2. **Configura Stripe** (opzionale per il demo):
   - Registrati su [Stripe](https://stripe.com)
   - Sostituisci le chiavi di test in `app.py`:
     ```python
     stripe.api_key = 'sk_test_your_secret_key'
     STRIPE_PUBLISHABLE_KEY = 'pk_test_your_publishable_key'
     ```

3. **Avvia l'applicazione**:
   ```bash
   python app.py
   ```

4. **Apri il browser** su: `http://localhost:5000`

## 🎮 Come Funziona

### Per i Giocatori:
1. **Registrati** con email e password
2. **Esplora il catalogo** di giochi disponibili
3. **Aggiungi giochi al carrello** 
4. **Procedi al pagamento** con carta di credito
5. **Scarica i tuoi giochi** dalla libreria personale

### Per gli Sviluppatori:
- I giochi sono gestiti tramite database
- Facile aggiungere nuovi titoli
- Sistema di tracking vendite integrato

## 🛡️ Cosa NON Include (Fuori dall'MVP)

Come descritto nel briefing, questo MVP **non include**:
- ❌ Community social (recensioni, forum)
- ❌ Achievement e classifiche
- ❌ Marketplace tra utenti
- ❌ Streaming in cloud
- ❌ Client desktop complesso

## 🗂️ Struttura del Progetto

```
game-market-mvp/
├── app.py                 # Backend Flask principale
├── requirements.txt       # Dipendenze Python
├── gamemarket.db         # Database SQLite (auto-generato)
├── templates/            # Template HTML
│   ├── base.html         # Layout base
│   ├── index.html        # Homepage/catalogo
│   ├── login.html        # Pagina login
│   ├── register.html     # Pagina registrazione
│   ├── game_detail.html  # Dettaglio gioco
│   ├── cart.html         # Carrello
│   ├── checkout.html     # Pagina pagamento
│   ├── library.html      # Libreria utente
│   └── search.html       # Risultati ricerca
└── static/
    └── css/
        └── style.css     # Stili personalizzati
```

## 🎨 Design e UX

- **Design moderno** con Bootstrap 5
- **Interfaccia intuitiva** e user-friendly
- **Responsive** per mobile e desktop
- **Colori gaming** (viola/blu) per l'atmosfera
- **Icone intuitive** per ogni azione

## 📊 Database Schema

### Tabelle principali:
- **users**: utenti registrati
- **games**: catalogo giochi
- **purchases**: cronologia acquisti
- **cart**: carrello della spesa

## 💳 Integrazione Pagamenti

- **Stripe Elements** per form sicuri
- **Gestione errori** di pagamento
- **Conferme email** (placeholder)
- **Tracking transazioni**

## 🔧 Configurazione Ambiente

### Variabili da configurare:
- `app.secret_key`: Chiave segreta Flask
- `stripe.api_key`: Chiave segreta Stripe
- `STRIPE_PUBLISHABLE_KEY`: Chiave pubblica Stripe

## 📱 Responsive Design

Il design è completamente responsive e funziona su:
- 📱 **Mobile** (smartphone)
- 💻 **Tablet** (iPad, Android tablet)
- 🖥️ **Desktop** (PC, Mac)

## 🎯 Filosofia MVP

Questo progetto segue la filosofia MVP descritta nel briefing:

> "Un Game Market non è la piattaforma mostruosa e piena di funzioni che vediamo oggi. È semplicemente il modo più diretto per mettere in contatto uno sviluppatore che vuole vendere un gioco con un giocatore che vuole comprarlo e giocarci."

## 🚀 Prossimi Passi (Post-MVP)

Una volta validato l'MVP, si potrebbero aggiungere:
- Sistema di recensioni
- Wishlist
- Sconti e promozioni
- API per sviluppatori
- Client desktop
- Sistema di achievement

## 📄 Licenza

Progetto educativo/dimostrativo per comprendere i principi MVP di un game market.

---

**Creato seguendo i principi del Minimum Viable Product per Game Market** 🎮