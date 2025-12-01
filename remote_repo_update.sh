#!/bin/bash

# =======================================================
# SINTAXA: ./remote_repo_update.sh "Please provide commit message"
# =======================================================

# --- CONFIGURARE ---
# Aceste variabile pot fi ajustate înainte de rulare.
# ----------------------------------------------------

# Numele fișierului de log
LOG_FILE="remote_repo_update.log"

# Numele ramurii principale pe care se face push-ul
# (De obicei 'main' sau 'master')
GIT_BRANCH="dev" 

# ----------------------------------------------------
# NU MODIFICAȚI CODUL SUB ACEASTĂ SECȚIUNE
# ----------------------------------------------------

# Redirecționează ieșirea standard (stdout) și erorile (stderr) către fișierul log.
# Folosim 'tee -a' pentru a afișa și în consolă, și a adăuga (append) la log.
exec > >(tee -a "$LOG_FILE") 2>&1

# Funcție pentru a adăuga data și ora la fiecare mesaj
log_step() {
    #echo ""
    #echo "======================================================================"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1"
    #echo "======================================================================"
}

log_step "Script execution is starting..."

# 1. Verifică argumentul (Mesajul de Commit)
if [ -z "$1" ]; then
    log_step "ERROR: Please provide the commit message! USAGE: $0 \"Your message\""
    exit 1
fi

COMMIT_MESSAGE="$1"
log_step "Commit mesage taken: \"$COMMIT_MESSAGE\" | Target Branch: $GIT_BRANCH"

# --- 2. Inițializare (git init) ---
if [ ! -d .git ]; then
    log_step "Repository inititalization (git init)..."
    git init -b $GIT_BRANCH
    if [ $? -ne 0 ]; then
        log_step "ERROR: git init failed..."
        exit 1
    fi
fi

# --- 3. Verifică Remote (git remote) ---
log_step "Checking remote connection 'origin'..."
if ! git remote get-url origin > /dev/null 2>&1; then
    log_step "ERROR: Local repository in NOT connected to an online repository (remote)..."
    log_step ""
    log_step "Please run manually: git remote add origin <repo URL>"
    exit 1
fi
log_step "Remote 'origin' is configured..."


# --- 4. Verifică dacă există modificări de comitat (NEW CHECK) ---
log_step "Checking for modifications before commit..."

# Verifică dacă există fișiere unstaged/staged. Dacă este gol, nu sunt modificări.
if [ -z "$(git status --porcelain)" ]; then
    log_step "WARNING: No modifications found or new files for update. Skiping Commit and Push..."
    log_step "FINAL: No modificatoins. Script terminated..."
    exit 0
fi
log_step "Updates detected. Continue with Commit..."


# --- 5. Adaugă (git add .) ---
log_step "Add files at staging (git add .)..."
git add .

# --- 6. Comite (git commit) ---
log_step "Execute commit (git commit -m \"$COMMIT_MESSAGE\")..."
git commit -m "$COMMIT_MESSAGE"

if [ $? -ne 0 ]; then
    log_step "FATAL ERROR: 'git commit' command has failed with unexpected reason (error code non-zero)..."
    exit 1
fi

# --- 7. Trimite (git push) ---
log_step "Send updates (git push)"
git push

if [ $? -ne 0 ]; then
    log_step "ERROR: 'git push' command has failed. Check setting or branch permissions..."
    exit 1
else
    log_step "FINAL: Updates sucessfully saved on remote repository."
    log_step ""
fi
