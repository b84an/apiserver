<?php
// ---- NUOVO: Inizializzazione della Sessione Condivisa ----
// Diamo un nome fisso alla sessione per condividerla tra tutti gli utenti.
session_id("cobianchi-api-stato-temporaneo");
session_start();

// ---- Intestazioni HTTP obbligatorie ----
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key");

// ---- NUOVO: Logica di Gestione dello Stato Temporaneo ----
define('TEMPO_DI_VITA_STATO', 3600); // 3600 secondi

// Controlla se esiste uno stato salvato e se non è scaduto
if (isset($_SESSION['dati_temporanei']) && isset($_SESSION['timestamp']) && (time() - $_SESSION['timestamp'] < TEMPO_DI_VITA_STATO)) {
    // Se lo stato è valido, usiamo i dati salvati in sessione
    $aule = $_SESSION['dati_temporanei'];
} else {
    // Altrimenti (se è la prima visita o lo stato è scaduto), carichiamo i dati di default
    require_once 'data.php'; // $aule viene definito qui
    // E salviamo questo stato iniziale nella sessione, facendo partire il timer
    $_SESSION['dati_temporanei'] = $aule;
    $_SESSION['timestamp'] = time();
}

// ---- Blocco di Autenticazione con API Key ----
define('SECRET_API_KEY', 'COBIANCHI-SECRET-KEY-123');
$chiave_inviata = $_SERVER['HTTP_X_API_KEY'] ?? null;
if ($chiave_inviata !== SECRET_API_KEY) {
    http_response_code(401);
    echo json_encode(['errore' => 'Autenticazione fallita. Fornisci un header X-API-Key valido.']);
    exit;
}

// ---- Analisi della Richiesta ----
$method = $_SERVER['REQUEST_METHOD'];
$path_info = isset($_GET['path']) ? $_GET['path'] : '';
$path = explode('/', rtrim($path_info, '/'));
$risorsa = $path[0] ?? null;
$id = $path[1] ?? null;

if ($risorsa === 'errore-simulato') {
    http_response_code(500);
    echo json_encode(['errore' => 'Errore interno del server. (Simulazione didattica)']);
    exit;
}
if ($risorsa !== 'aule') {
    http_response_code(404);
    echo json_encode(['messaggio' => 'Endpoint non trovato. Prova con /api/aule']);
    exit;
}

// ---- Routing Principale (con operazioni REALI) ----
switch ($method) {
    case 'GET':
        // La logica di GET e filtraggio non cambia, ma ora opera sui dati temporanei
        if ($id) {
            if (isset($aule[$id])) {
                http_response_code(200); echo json_encode($aule[$id]);
            } else {
                http_response_code(404); echo json_encode(['messaggio' => "Aula con id $id non trovata."]);
            }
        } else {
            $aule_filtrate = $aule;
            if (isset($_GET['piano'])) $aule_filtrate = array_filter($aule_filtrate, fn($a) => $a['piano'] == $_GET['piano']);
            if (isset($_GET['disponibile'])) $aule_filtrate = array_filter($aule_filtrate, fn($a) => $a['disponibile'] === filter_var($_GET['disponibile'], FILTER_VALIDATE_BOOLEAN));
            http_response_code(200); echo json_encode(array_values($aule_filtrate));
        }
        break;

    case 'POST': // MODIFICATO: ora crea davvero l'aula
        $dati_inviati = json_decode(file_get_contents("php://input"), true);
        if (!empty($dati_inviati) && isset($dati_inviati['nome'])) {
            $nuovo_id = empty($aule) ? 1 : max(array_keys($aule)) + 1;
            $aule[$nuovo_id] = $dati_inviati;

            $_SESSION['dati_temporanei'] = $aule; // Salva lo stato aggiornato
            $_SESSION['timestamp'] = time();      // Resetta il timer

            http_response_code(201);
            echo json_encode(['messaggio' => 'SUCCESS: Aula creata.', 'aula' => $aule[$nuovo_id]]);
        } else {
            http_response_code(400); // Bad Request
            echo json_encode(['errore' => 'Dati inviati non validi o mancanti.']);
        }
        break;

    case 'PUT': // MODIFICATO: ora aggiorna davvero l'aula
        if ($id && isset($aule[$id])) {
            $dati_inviati = json_decode(file_get_contents("php://input"), true);
            $aule[$id] = array_merge($aule[$id], $dati_inviati); // Permette aggiornamenti parziali

            $_SESSION['dati_temporanei'] = $aule; // Salva lo stato aggiornato
            $_SESSION['timestamp'] = time();      // Resetta il timer

            http_response_code(200);
            echo json_encode(['messaggio' => "SUCCESS: Aula con id $id aggiornata.", 'aula' => $aule[$id]]);
        } else {
            http_response_code(404);
            echo json_encode(['messaggio' => "ERRORE: Aula con id $id non trovata."]);
        }
        break;

    case 'DELETE': // MODIFICATO: ora cancella davvero l'aula
        if ($id && isset($aule[$id])) {
            unset($aule[$id]);

            $_SESSION['dati_temporanei'] = $aule; // Salva lo stato aggiornato
            $_SESSION['timestamp'] = time();      // Resetta il timer

            http_response_code(204); // No Content - risposta standard per DELETE andato a buon fine
        } else {
            http_response_code(404);
            echo json_encode(['messaggio' => "ERRORE: Aula con id $id non trovata."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['messaggio' => 'Metodo non supportato.']);
        break;
}
?>
