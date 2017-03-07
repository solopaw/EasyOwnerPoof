<?php

/*
 * Poggit
 *
 * Copyright (C) 2016-2017 Poggit
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace poggit\virion;

const VIRION_BUILDER_VERSION = "1.0";

echo "Using virion builder: version " . VIRION_BUILDER_VERSION, PHP_EOL;

function virion_infect(\Phar $virus, \Phar $host, bool $doubleInfection) {
    if(!isset($virus["virus.json"])) {
        throw new \RuntimeException("virus.json not found, could not activate virion", 2);
    }
    $data = json_decode(file_get_contents($virus["virus.json"]));
    if(!is_object($data)) {
        throw new \RuntimeException("Corrupted virus.json, could not activate virion", 2);
    }

    $infectionLog = isset($host["virus-infections.json"]) ? json_decode(file_get_contents($host["virus-infections.json"]), true) : [];

    $genus = $data->name;
    $antigen = $data->antigen;

    foreach($infectionLog as $old) {
        if($old["antigen"] === $antigen) {
            echo "[!] Target already infected by this virion, aborting\n";
            exit(3);
        }
    }

    do {
        $antibody = str_replace(["+", "/"], "_", trim(base64_encode(random_bytes(10)), "="));
        if(ctype_digit($antibody{0})) $antibody = "_" . $antibody;
    } while(isset($infectionLog[$antibody]));

    $infectionLog[$antibody] = $data;

    echo "Using antibody $antibody for virion $genus ({$antigen})\n";

    foreach(new \RecursiveIteratorIterator($host) as $name => $chromosome) {
        if($chromosome->isDir()) continue;
        if($chromosome->getExtension() !== "php") continue;

        $rel = cut_prefix($name, "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $host->getPath()));
        $data = change_dna(file_get_contents($name), $antigen, $antibody, $doubleInfection);
        if($data !== "") $host[$rel] = $data;
    }

    $restriction = "src/" . str_replace("\\", "/", $antigen) . "/"; // restriction enzyme ^_^
    $ligase = "src/" . str_replace("\\", "/", $antibody) . "/";

    foreach(new \RecursiveIteratorIterator($virus) as $name => $genome) {
        if($genome->isDir()) continue;

        $rel = cut_prefix($name, "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $virus->getPath()) . "/");

        if(substr($rel, 0, strlen("resources/")) === "resources/") {
            $host[$rel] = file_get_contents($name);
        } elseif(substr($rel, 0, 4) === "src/") {
            if(substr($rel, 0, strlen($restriction)) != $restriction) {
                echo "Warning: file $rel in virion is not under the antigen $antigen ($restriction)\n";
                $newRel = $rel;
            }else{
                $newRel = $ligase . cut_prefix($rel, $restriction);
            }
            $data = change_dna(file_get_contents($name), $antigen, $antibody, $doubleInfection); // it's actually RNA
            $host[$newRel] = $data;
        }
    }

    $host["virus-infections.json"] = json_encode($infectionLog);
}

function cut_prefix(string $string, string $prefix): string {
    if(substr($string, 0, strlen($prefix)) !== $prefix) throw new \AssertionError("\$string does not start with \$prefix:\n$string\n$prefix");
    return substr($string, strlen($prefix));
}

function change_dna(string $chromosome, string $antigen, string $antibody, bool $doubleInfection): string {
    $ret = str_replace($antigen, $antibody, $chromosome);
    if($doubleInfection) {
        $ret = str_replace(
            str_replace("\\", "\\\\", $antigen),
            str_replace("\\", "\\\\", $antibody),
            $ret);
    }

    return $ret;
}