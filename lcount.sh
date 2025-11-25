#!/bin/bash

# Script robust pentru numărarea liniilor non-blank cu depanare.

if [ "$#" -eq 0 ]; then
    echo "Utilizare: $0 <director1> [director2] [directorN...]"
    exit 1
fi

total_lines=0

echo "--- Pornire Analiză Robustă ---"

for dir_path in "$@"; do
    if [ -d "$dir_path" ]; then
        echo "--> Procesare Director: $dir_path"
        
        # Folosim find pentru a parcurge recursiv fișierele (inclusiv subdirectoarele)
        # folosind -type f pentru a procesa doar fișierele regulate.
        # find-ul este rulat cu -print0 pentru a gestiona nume de fișiere cu spații sau caractere speciale.
        find "$dir_path" -type f -print0 | while IFS= read -r -d $'\0' file_path; do
            
            # Verificăm dacă fișierul este citibil
            if [ -r "$file_path" ]; then
                
                # Numără liniile non-blank.
                # 2>/dev/null suprima posibilele erori binare sau de I/O ale lui grep.
                line_count=$(grep -v '^\s*$' "$file_path" 2>/dev/null | wc -l)
                
                # Afișează numărul de linii doar dacă este > 0
                if [ "$line_count" -gt 0 ]; then
                    echo "  $line_count linii: $file_path"
                    total_lines=$((total_lines + line_count))
                fi
            else
                echo "  Eroare Permisiune: Nu pot citi $file_path"
            fi
            
        done
        
    else
        echo "Avertisment: Calea '$dir_path' nu este un director valid."
    fi
done

echo "------------------------------------------------"
echo "TOTAL FINAL LINIILOR NON-BLANK NUMĂRATE: $total_lines"
echo "------------------------------------------------"
