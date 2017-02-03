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

namespace poggit\module\build;

use poggit\builder\ProjectBuilder;
use poggit\module\Module;
use poggit\Poggit;
use poggit\utils\internet\CurlUtils;
use poggit\utils\internet\GitHubAPIException;
use poggit\utils\internet\MysqlUtils;
use poggit\utils\SessionUtils;

class GetPmmpModule extends Module {
    public function getName(): string {
        return "get.pmmp";
    }

    public function output() {
        $arg = $this->getQuery();
        if(strpos($arg, "/") !== false) {
            list($arg, $path) = explode("/", $arg, 2);
        } else $path = "PocketMine-MP.phar";

        if($arg === "html") Poggit::redirect("ci/pmmp/PocketMine-MP/PocketMine-MP");

        $paramTypes = "i";
        $params = [ProjectBuilder::BUILD_CLASS_DEV];
        if(ctype_digit($arg)) { // $arg is build number
            $condition = "internal = ?";
            $paramTypes .= "i";
            $params[] = (int) $arg;
        } elseif(isset($_REQUEST["pr"])) {
            $condition = "INSTR(cause, ?)";
            $paramTypes .= "s";
            $params[] = '"prNumber":' . ((int) $_REQUEST["pr"]) . ","; // hack
        } elseif(isset($_REQUEST["sha"])) {
            $condition = "sha = ?";
            $paramTypes .= "s";
            $params[] = $_REQUEST["sha"];
        } else {
            $condition = "branch = ?";
            $paramTypes .= "s";
            $params[] = $arg ?: "master";
        }
        
        $rows = MysqlUtils::query("SELECT resourceId FROM builds WHERE projectId = 210 AND class = ? AND ($condition)
                ORDER BY created DESC LIMIT 1", $paramTypes, ...$params);
        if(count($rows) === 0) $this->errorNotFound();
        Poggit::redirect("r/" . ((int) $rows[0]["resourceId"]) . "/" . $path);
    }
}