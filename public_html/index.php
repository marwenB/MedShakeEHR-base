<?php
/*
 * This file is part of MedShakeEHR.
 *
 * Copyright (c) 2017
 * Bertrand Boutillier <b.boutillier@gmail.com>
 * http://www.medshake.net
 *
 * MedShakeEHR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * MedShakeEHR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MedShakeEHR.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Pivot central des pages loguées
 *
 * @author Bertrand Boutillier <b.boutillier@gmail.com>
 * @contrib fr33z00 <https://www.github.com/fr33z00>
 */

ini_set('display_errors', 1);
setlocale(LC_ALL, "fr_FR.UTF-8");

/////////// Petites vérifications de l'installation
if (!is_dir("../vendor")) {
    die("L'installation de MedShakeEHR ne semble pas complète, veuillez installer COMPOSER (<a href='https://getcomposer.org'>https://getcomposer.org</a>)<br>Tapez ensuite <code>composer update</code> en ligne de commande dans le répertoire d'installation de MedShakeEHR.");
}
if (!is_dir("bower_components")) {
    die("L'installation de MedShakeEHR ne semble pas complète, veuillez installer BOWER (<a href='https://bower.io'>https://bower.io</a>)<br>Tapez ensuite <code>bower update --save</code> en ligne de commande dans le répertoire /public_html de MedShakeEHR.");
}
if (!is_file('../config/config.yml')) {
    die("L'installation de MedShakeEHR ne semble pas complète, veuillez créer le fichier config/config.yml");
}

session_start();

/////////// Composer class auto-upload
require '../vendor/autoload.php';

/////////// Class medshakeEHR auto-upload
spl_autoload_register(function ($class) {
    if (is_file('../class/' . $class . '.php')) {
        include '../class/' . $class . '.php';
    }
});


/////////// Config loader
$p['config']=Spyc::YAMLLoad('../config/config.yml');
$p['config']['relativePathForInbox']=str_replace($p['config']['webDirectory'], '', $p['config']['apicryptCheminInbox']);

/////////// correction pour host non présent (IP qui change)
if ($p['config']['host']=='') {
    $p['config']['host']=$_SERVER['SERVER_ADDR'];
    $p['config']['cookieDomain']=$_SERVER['SERVER_ADDR'];
}

/////////// SQL connexion
$mysqli=msSQL::sqlConnect();

/////////// Validators loader
require '../fonctions/validators.php';

/////////// Router
$router = new AltoRouter();
$routes=Spyc::YAMLLoad('../config/routes.yml');
$router->addRoutes($routes);
$router->setBasePath($p['config']['urlHostSuffixe']);
$match = $router->match();


///////// user
if (isset($_COOKIE['userName'])) {
    $p['user']=msUser::userIdentification();
    if (isset($p['user']['id'])) {
        msUser::applySpecificConfig($p['config'], $p['user']['id']);
    }
} else {
    $p['user']=null;
    $p['user']['id']=null;
    $p['user']['module']='base';
    if (msSQL::sqlUniqueChamp("SELECT COUNT(*) FROM people WHERE type='pro'") == "0") {
        if ($match['target']!='login/logInFirst' and $match['target']!='login/logInFirstDo') {
            msTools::redirRoute('userLogInFirst');
        }
    }
    elseif ($match['target']!='login/logIn' and $match['target']!='login/logInDo') {
        msTools::redirRoute('userLogIn');
    }
}

///////// Controler
if ($match and is_file('../controlers/'.$match['target'].'.php')) {
    include '../controlers/'.$match['target'].'.php';

    // complément lié au module installé
    if (is_file('../controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php')) {
        include '../controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php';
    }
} elseif ($match and is_file('../controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php')) {
    include '../controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php';
}


//////// View if defined
if (isset($template)) {
    if (isset($_SESSION)) {
        $p['session']=$_SESSION;
    }

    if (isset($p['user']['id'])) {
        //inbox number of messages
      $p['page']['inbox']['numberOfMsg']=msSQL::sqlUniqueChamp("select count(txtFileName) from inbox where archived='n' and mailForUserID = '".$p['config']['apicryptInboxMailForUserID']."' ");

      // patients of the day
      if (isset($p['config']['agendaNumberForPatientsOfTheDay'])) {
          if ($p['config']['agendaNumberForPatientsOfTheDay'] > 0) {
              $events = new msAgenda();
              $events->set_userID($p['config']['agendaNumberForPatientsOfTheDay']);
              $p['page']['patientsOfTheDay']=$events->getPatientsOfTheDay();
          }
      }
        if (!isset($p['page']['patientsOfTheDay']) and isset($p['config']['agendaLocalPatientsOfTheDay'])) {
            $p['page']['patientsOfTheDay']=msExternalData::jsonFileToPhpArray($p['config']['workingDirectory'].$p['config']['agendaLocalPatientsOfTheDay']);
        }
    }

    // crédits SMS
    if (is_file($p['config']['workingDirectory'].$p['config']['smsCreditsFile'])) {
        $p['page']['creditsSMS']=file_get_contents($p['config']['workingDirectory'].$p['config']['smsCreditsFile']);
    }

    //utilisateurs pouvant avoir un agenda
    $agendaUsers= new msPeople();
    $p['page']['agendaUsers']=$agendaUsers->getUsersListForService('administratifPeutAvoirAgenda');

    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");


    //générer et sortir le html
    $getHtml = new msGetHtml();
    $getHtml->set_template($template);
    if(isset($forceAllTemplates)) {
      $getHtml->set_templatesDirectories(msTools::getAllSubDirectories($p['config']['templatesFolder'],'/'));
    }
    echo $getHtml->genererHtml();
}

//////// Debug
if (!isset($debug)) {
    $debug=null;
}

//and $p['user']['id']=='1'

if ($debug=='y' and $p['user']['id']=='1') {
    echo '<pre style="margin-top : 50px;">';
    //echo '$p[\'config\'] :';
    //print_r($p['config']);
    echo '$p[\'page\'] :';
    print_r($p['page']);
    echo '$p[\'user\'] :';
    print_r($p['user']);
    echo '$MATCH :';
    print_r($match);
    echo '$_COOKIE :';
    print_r($_COOKIE);
    echo '$_SESSION :';
    print_r($_SESSION);
}
