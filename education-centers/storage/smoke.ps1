$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8000'
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$login = Invoke-WebRequest -Uri "$base/login" -WebSession $s -UseBasicParsing
$token = ([regex]'name="_token" value="([^"]+)"').Match($login.Content).Groups[1].Value
Write-Output "CSRF: $($token.Substring(0,12))..."

$r = Invoke-WebRequest -Uri "$base/login" -Method Post -WebSession $s -UseBasicParsing `
    -Body @{ _token = $token; email = 'admin@education-centers.de'; password = 'EducationAdmin2026!' }
Write-Output "LOGIN status: $($r.StatusCode) | final: $($r.BaseResponse.RequestMessage.RequestUri)"

foreach ($p in @('/super-admin/finance/depenses', '/super-admin/finance/professeurs', '/super-admin/finance/configuration')) {
    try {
        $res = Invoke-WebRequest -Uri "$base$p" -WebSession $s -UseBasicParsing
        $len = $res.Content.Length
        Write-Output "GET $p -> $($res.StatusCode) ($len chars)"
        if ($p -eq '/super-admin/finance/depenses') {
            foreach ($needle in @('Nature', 'Catégorie', 'Salaires des professeurs', 'Charges engagées', 'teacher_id', 'name="paid_amount"')) {
                Write-Output ("   contains [{0}] : {1}" -f $needle, ($res.Content -match [regex]::Escape($needle)))
            }
            foreach ($gone in @('/super-admin/finance/rapports', '/super-admin/finance/compte-bancaire', '/super-admin/finance/comptabilite')) {
                Write-Output ("   sidebar link [{0}] present : {1}" -f $gone, ($res.Content -match [regex]::Escape($gone)))
            }
        }
    } catch {
        Write-Output "GET $p -> ERROR $($_.Exception.Message)"
    }
}
