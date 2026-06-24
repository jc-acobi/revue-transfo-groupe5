<#
.SYNOPSIS
  Remet le dépôt du groupe à son état initial (page vierge) et redéploie.

.DESCRIPTION
  Réaligne les 4 branches (main, dev, binome1, binome2) sur le repère `etat-initial`
  posé lors de l'instanciation, puis republie. Utile entre deux sessions de test ou
  avant l'atelier.
  ATTENTION : tout le travail effectué après l'état initial est effacé.

.EXAMPLE
  ./reset-groupe.ps1            # prépare la remise à neuf en local
  ./reset-groupe.ps1 -Pousser   # remet à neuf ET republie (les pages reviennent à la page vierge)
#>
param([switch]$Pousser)

$base = git rev-parse --verify etat-initial 2>$null
if (-not $base) {
  Write-Host "Repère 'etat-initial' introuvable : ce dépôt n'a pas été instancié avec le repère de remise à neuf."
  exit 1
}

git checkout main
git reset --hard $base
foreach ($b in 'dev', 'binome1', 'binome2') { git branch -f $b $base }

if ($Pousser) {
  git push --force origin main dev binome1 binome2
  Write-Host "Groupe remis a neuf et republie. Les pages reviennent a l'etat initial."
} else {
  Write-Host "Pret en local. Pour appliquer en ligne : git push --force origin main dev binome1 binome2"
}
