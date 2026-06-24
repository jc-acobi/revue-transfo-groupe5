<#
.SYNOPSIS
  Instancie ce gabarit pour un groupe donné (remplace les repères __GROUPE__).

.DESCRIPTION
  À lancer dans un dépôt fraîchement créé à partir du gabarit (bouton « Use this
  template » sur GitHub, puis clone en local). Le script remplace les repères,
  enregistre, recrée les trois espaces de travail (dev, binome1, binome2) à partir
  de la version finale, puis — avec -Pousser — publie le tout.

.EXAMPLE
  ./nouveau-groupe.ps1 -Numero 2
  ./nouveau-groupe.ps1 -Numero 2 -Pousser
#>
param(
  [Parameter(Mandatory = $true)][int]$Numero,
  [switch]$Pousser
)

$ErrorActionPreference = 'Stop'

$slug  = "groupe$Numero"
$label = "Groupe $Numero"

$fichiers = @(
  'CLAUDE.md',
  'manifest.json',
  'index.php',
  '.github/workflows/deploy-binome1.yml',
  '.github/workflows/deploy-binome2.yml',
  '.github/workflows/deploy-dev.yml',
  '.github/workflows/deploy-prod.yml'
)

$enc = New-Object System.Text.UTF8Encoding($false)  # UTF-8 sans BOM
foreach ($f in $fichiers) {
  $path = Join-Path (Get-Location) $f
  if (Test-Path $path) {
    $contenu = [System.IO.File]::ReadAllText($path)
    $contenu = $contenu.Replace('__GROUPE_LABEL__', $label).Replace('__GROUPE__', $slug)
    [System.IO.File]::WriteAllText($path, $contenu, $enc)
    Write-Host "  modifié : $f"
  }
}

Write-Host "Gabarit instancié pour $label ($slug)."

git add -A
git commit -m "Instanciation $slug"

# Les trois espaces de travail démarrent identiques à la version finale.
git branch -f dev main
git branch -f binome1 main
git branch -f binome2 main

# Repère de remise à neuf (utilisé par reset-groupe.ps1).
git tag -f etat-initial main

if ($Pousser) {
  git push -u origin main dev binome1 binome2
  git push -f origin etat-initial
  Write-Host "Publié. Avec l'auto-réparation, aucune manip serveur par groupe n'est nécessaire (voir README-organisateur.md)."
} else {
  Write-Host "Pour publier : git push -u origin main dev binome1 binome2 ; git push -f origin etat-initial"
}
