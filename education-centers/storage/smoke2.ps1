$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8000'
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$login = Invoke-WebRequest -Uri "$base/login" -WebSession $s -UseBasicParsing
$token = ([regex]'name="_token" value="([^"]+)"').Match($login.Content).Groups[1].Value
Invoke-WebRequest -Uri "$base/login" -Method Post -WebSession $s -UseBasicParsing `
    -Body @{ _token = $token; email = 'admin@education-centers.de'; password = 'EducationAdmin2026!' } | Out-Null

function Check-Page($path, $needles) {
    $res = Invoke-WebRequest -Uri "$base$path" -WebSession $s -UseBasicParsing
    $bytes = [System.Text.Encoding]::UTF8.GetString([System.Text.Encoding]::Default.GetBytes($res.Content))
    Write-Output "GET $path -> $($res.StatusCode)"
    foreach ($n in $needles) {
        Write-Output ("   [{0}] : {1}" -f $n, ($bytes -match [regex]::Escape($n)))
    }
}

# Old standalone salary route must redirect (no auto-follow)
try {
    $r = Invoke-WebRequest -Uri "$base/super-admin/finance/professeurs" -WebSession $s -UseBasicParsing -MaximumRedirection 0
    Write-Output "professeurs status: $($r.StatusCode)"
} catch {
    $resp = $_.Exception.Response
    Write-Output "professeurs status: $([int]$resp.StatusCode) -> $($resp.Headers['Location'])"
}

Check-Page '/super-admin/finance/depenses' @('Catégorie', 'Charges engagées', 'Rapports financiers', 'Compte bancaire')
Check-Page '/super-admin/finance/depenses?lang=ar' @('rtl', 'التحكم')
