param(
    [string]$RemoteUrl = "https://github.com/your-username/your-repo.git",
    [string]$Branch = "main"
)

Write-Host "This script will remove the local .git folder (if present) and initialize a fresh repository." -ForegroundColor Yellow
if (Test-Path .git) {
    Write-Host "Removing existing .git..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force .git
}

git init
git add .
git commit -m "Initial commit — PHP conversion"
git branch -M $Branch
if ($RemoteUrl -ne "") {
    git remote add origin $RemoteUrl
    Write-Host "Remote 'origin' set to $RemoteUrl" -ForegroundColor Green
    Write-Host "Run: git push -u origin $Branch" -ForegroundColor Cyan
}

Write-Host "Done. Edit and run the push command above when ready." -ForegroundColor Green
