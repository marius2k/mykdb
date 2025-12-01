#!/bin/bash

# =======================================================
# SINTAXA: ./remote_repo_update.sh "Mesajul dumneavoastra de commit"
# =======================================================

# --- CONFIGURARE ---
# Aceste variabile pot fi ajustate înainte de rulare.
# ----------------------------------------------------

# Numele fișierului de log
LOG_FILE="remote_repo_update.log"

# Numele ramurii principale pe care se face push-ul
# (De obicei 'main' sau 'master')
GIT_BRANCH="main" 

# ----------------------------------------------------
# NU MODIFICAȚI CODUL SUB ACEASTĂ SECȚIUNE
# ----------------------------------------------------

# Redirecționează ieșirea standard (stdout) și erorile (stderr) către fișierul log.
# Folosim 'tee -a' pentru a afișa și în consolă, și a adăuga (append) la log.
exec > >(tee -a "$LOG_FILE") 2>&1

# Funcție pentru a adăuga data și ora la fiecare mesaj
log_step() {
    echo ""
    echo "======================================================================"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - PAS: $1"
    echo "======================================================================"
}

log_step "START: Începe execuția scriptului"

# 1. Verifică argumentul (Mesajul de Commit)
if [ -z "$1" ]; then
    echo "❌ EROARE: Vă rugăm să furnizați un mesaj de commit."
    echo "Utilizare: $0 \"Mesajul dumneavoastra\""
    exit 1
fi

COMMIT_MESSAGE="$1"
log_step "Mesaj Commit preluat: \"$COMMIT_MESSAGE\" | Ramura țintă: $GIT_BRANCH"

# --- 2. Inițializare (git init) ---
if [ ! -d .git ]; then
    log_step "1/4: Inițializare depozit (git init)"
    git init -b $GIT_BRANCH
    if [ $? -ne 0 ]; then
        echo "❌ EROARE: Inițializarea Git a eșuat."
        exit 1
    fi
fi

# --- 3. Verifică Remote (git remote) ---
log_step "2/4: Verificare conexiune remote 'origin'"
if ! git remote get-url origin > /dev/null 2>&1; then
    echo "❌ EROARE: Depozitul local NU este conectat la un depozit online (remote)."
    echo ""
    echo "Vă rugăm să executați manual: git remote add origin <URL-ul depozitului>"
    exit 1
fi
echo "Remote 'origin' este configurat."


# --- 4. Adaugă (git add .) ---
log_step "3/4: Adaugă fișiere la staging (git add .)"
git add .

# --- 5. Comite (git commit) ---
log_step "3/4: Execută commit (git commit -m \"$COMMIT_MESSAGE\")"
git commit -m "$COMMIT_MESSAGE"

if [ $? -ne 0 ]; then
    # Verifică dacă eșecul este din cauza lipsei de modificări
    if git status --porcelain | grep -q '^\s*$'; then
        echo "⚠️ Avertisment: Nu au existat modificări de comitat. Sărit peste 'git push'."
        exit 0
    else
        echo "❌ EROARE: Comanda 'git commit' a eșuat."
        exit 1
    fi
fi

# --- 6. Trimite (git push) ---
log_step "4/4: Trimitere modificări (git push)"
git push

if [ $? -ne 0 ]; then
    echo "❌ EROARE: Comanda 'git push' a eșuat. Verificați permisiunile sau setările ramurii."
    exit 1
else
    log_step "FINAL: Modificările au fost trimise cu succes la remote."
fi
