**Les informations ci-dessous sont une rapide introduction purement technique !
Consultez [www.logiciel-cabinet-medical.fr](http://www.logiciel-cabinet-medical.fr/) pour toutes les informations sur le logiciel MedShakeEHR !**

# MedShakeEHR-base
Base pour MedShakeEHR, logiciel modulaire, universel, open source pour les praticiens santé.

## Avertissements
Ce logiciel ne doit pas être utilisé en l'état pour la stockage de données patient sur un réseau ouvert.  
Son utilisation doit être exclusivement limitée à un réseau privé sans utilisateur potentiellement hostile.
Il est livré ici sans aucune garantie, conformément à la licence GPL v3.

## Utilisation
MedShakeEHR-base constitue le coeur commun du logiciel. Il peut être accompagné de l'un ou l'autre de ses modules pour fonctionner de façon optimale.

## Installation
MedShakeEHR fonctionne sur un serveur xAMP. Il a été testé sous Ubuntu 16.04 et Mint 17.3, Apache 2, PHP 7, Mysql 5.

Utiliser [Composer](https://getcomposer.org/download/) à la racine pour l'installation des packages PHP nécessaires au back-end et dans public_html pour le front-end.

Le paramétrage de base (chemins, base de données) se fait à la première utilisation, et l'ensemble des paramètres est ensuite accessible par l'administrateur sur une page dédiée.
Chaque paramètre peut être surchargé au niveau utilisateur.  

La configuration complète du logiciel est documentée sur le site [www.logiciel-cabinet-medical.fr](http://www.logiciel-cabinet-medical.fr/) à la rubrique [Documentation technique](http://www.logiciel-cabinet-medical.fr/documentation-technique/).
