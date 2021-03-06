<?php
/*
 * This file is part of MedShakeEHR.
 *
 * Copyright (c) 2017
 * fr33z00 <https://github.com/fr33z00>
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
 * enregistrement des paramètres d'agenda utilisateur
 *
 * @author fr33z00 <https://github.com/fr33z00>
 */

$preparams=array();

foreach ($_POST as $k=>$v) {
    preg_match('/(desc|back|border|duree|key)_(.*)/', $k, $matches);
    if ($matches[2]) {
        $preparams[$matches[2]][$matches[1]]=$v;
    }
}
foreach ($preparams as $k=>$v) {
    if ($v['key']) {
        $params["'[".$v['key']."]'"]=array('descriptif'=>$v['desc'], 'backgroundColor'=>$v['back'], 'borderColor'=>$v['border'], 'duree'=>$v['duree']);
    }
}

file_put_contents($p['config']['homeDirectory'].'config/configTypesRdv'.$p['user']['id'].'.yml', Spyc::YAMLDump($params, false, 0, true));

msTools::redirRoute('userParameters');
