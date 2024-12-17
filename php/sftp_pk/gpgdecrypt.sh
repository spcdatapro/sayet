#!/bin/bash

# Verificar si se pasaron los argumentos necesarios
if [ "$#" -ne 4 ]; then
    echo "Usage: $0 fingerprint passphrase filename_with_fullpath file_to_decrypt"
    exit 1
fi

# Asignar argumentos a variables
fingerprint="$1"
passphrase="$2"
filename_with_fullpath="$3"
file_to_decrypt="$4"

# Ejecutar el comando gpg para desencriptar
gpg --batch --yes --default-key "$fingerprint" --passphrase "$passphrase" -o "${filename_with_fullpath}.txt" --decrypt "$file_to_decrypt"

# Verificar si el comando gpg tuvo éxito
if [ $? -eq 0 ]; then
    echo "Decryption successful"
else
    echo "Decryption failed"
    exit 1
fi