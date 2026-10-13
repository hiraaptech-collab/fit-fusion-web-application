function Write-AuditFile([string]$relative, [string]$content) {
 $base='C:\Users\pro saqi\Desktop\fit-fusion\laravel'
 $target=[IO.Path]::GetFullPath((Join-Path $base $relative))
 if(!$target.StartsWith($base+'\',[StringComparison]::OrdinalIgnoreCase)){throw 'Laravel-only path required.'}
 New-Item -ItemType Directory -Path (Split-Path -Parent $target) -Force|Out-Null
 [IO.File]::WriteAllText($target,$content+"`r`n",(New-Object Text.UTF8Encoding($false)))
}