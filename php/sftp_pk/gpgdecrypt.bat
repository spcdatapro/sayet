@echo off
setlocal

if "%~4"=="" (
    echo Usage: %0 fingerprint passphrase filename_with_fullpath file_to_decrypt
    exit /b 1
)

set "fingerprint=%~1"
set "passphrase=%~2"
set "filename_with_fullpath=%~3"
set "file_to_decrypt=%~4"

gpg --default-key "%fingerprint%" --pinentry-mode loopback --passphrase %passphrase% -o "%filename_with_fullpath%.txt" --decrypt "%file_to_decrypt%"

endlocal
