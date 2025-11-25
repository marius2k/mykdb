#!/usr/bin/env python3
import sys
import os

def count_non_blank_lines(file_path):
    """Numără liniile care nu sunt goale într-un fișier."""
    try:
        count = 0
        # Folosim 'r' (read) și encoding-ul implicit, gestionând erorile cu 'ignore'
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                # Verifică dacă linia conține caractere în afară de spațiu alb (inclusiv newline)
                if line.strip():
                    count += 1
        return count
    except Exception as e:
        # Afișează erorile de citire (de obicei din cauza fișierelor binare)
        # print(f"  Eroare citire: {file_path} ({e})")
        return 0

def process_directories(directories):
    """Procesează lista de directoare și returnează totalul."""
    
    if not directories:
        print("Utilizare: python3 count_lines.py <director1> [director2] [directorN...]")
        return
        
    total_lines = 0
    
    print("--- Pornire Analiză Python Robustă ---")
    
    # Iterează prin fiecare director dat ca argument
    for dir_path in directories:
        if not os.path.isdir(dir_path):
            print(f"Avertisment: Calea '{dir_path}' nu este un director valid.")
            continue
            
        print(f"--> Procesare Director: {dir_path}")
        
        # os.walk parcurge recursiv directorul și toate subdirectoarele
        for root, _, files in os.walk(dir_path):
            for file_name in files:
                file_path = os.path.join(root, file_name)
                
                # Verifică dacă este un fișier citibil
                if os.path.isfile(file_path):
                    line_count = count_non_blank_lines(file_path)
                    
                    if line_count > 0:
                        print(f"  {line_count:4} linii: {file_path}")
                        total_lines += line_count

    print("------------------------------------------------")
    print(f"TOTAL FINAL LINIILOR NON-BLANK NUMĂRATE: {total_lines}")
    print("------------------------------------------------")


if __name__ == "__main__":
    # sys.argv[1:] conține toate argumentele primite, excluzând numele scriptului
    process_directories(sys.argv[1:])
