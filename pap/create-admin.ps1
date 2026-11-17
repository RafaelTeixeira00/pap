$ErrorActionPreference = 'Stop'

$name = Read-Host 'Nome do administrador'
$email = Read-Host 'Email do administrador'
$securePassword = Read-Host 'Palavra-passe inicial (mínimo 12 caracteres)' -AsSecureString
$passwordPointer = [IntPtr]::Zero

try {
    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    $env:POWERLIFT_CLIENT_NAME = $name
    $env:POWERLIFT_CLIENT_EMAIL = $email
    $env:POWERLIFT_CLIENT_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    $env:POWERLIFT_ACCOUNT_ROLE = 'admin'

    & 'C:\xampp\php\php.exe' (Join-Path $PSScriptRoot 'create-client.php')
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}
finally {
    Remove-Item Env:POWERLIFT_CLIENT_NAME -ErrorAction SilentlyContinue
    Remove-Item Env:POWERLIFT_CLIENT_EMAIL -ErrorAction SilentlyContinue
    Remove-Item Env:POWERLIFT_CLIENT_PASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:POWERLIFT_ACCOUNT_ROLE -ErrorAction SilentlyContinue
    if ($passwordPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }
    $securePassword.Dispose()
}
