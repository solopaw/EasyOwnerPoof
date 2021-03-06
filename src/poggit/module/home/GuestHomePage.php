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

namespace poggit\module\home;

use poggit\builder\ProjectBuilder;
use poggit\module\VarPage;
use poggit\Poggit;
use poggit\utils\internet\MysqlUtils;

class GuestHomePage extends VarPage {
    private $recentBuilds;

    public function __construct() {
        $latest = [];
        foreach(MysqlUtils::query("SELECT b.buildId, b.internal, b.class, UNIX_TIMESTAMP(b.created) AS created, 
            r.owner, r.name AS repoName, p.name AS projectName
            FROM builds b INNER JOIN projects p ON b.projectId = p.projectId INNER JOIN repos r ON p.repoId = r.repoId
            WHERE class = ? AND private = 0 AND r.build > 0 ORDER BY created DESC LIMIT 100", "i", ProjectBuilder::BUILD_CLASS_DEV) as $row) {
            
            if (!in_array($row["projectName"], $latest)) { 
            $row = (object) $row;
            $buildId = $row->buildId = (int) $row->buildId;
            $row->internal = (int) $row->internal;
            $row->class = (int) $row->class;
            $row->created = (int) $row->created;
            $this->recentBuilds[$buildId] = $row;  
            $latest[] = $row->projectName;
            }
        }
    }

    public function getTitle(): string {
        return "Poggit - Concentrate on your code. Leave the dirty work to the machines.";
    }

    public function bodyClasses(): array {
        return ["horiz-panes"];
    }

    public function output() {
        ?>
        <div class="guesthomepane1">
            <h1 class="motto">Concentrate on your code.<br/> Leave the dirty work to the machines.</h1>
            <h2 class="submotto">Download plugins easily. Automatic development builds. With lint tailored for
                PocketMine plugins.<br/>
                Register with GitHub in a few seconds to enable the magic.</h2>
            <p class="submotto">Why does Poggit exist? Simply to stop this situation from the web comic
                <a href="https://xkcd.com/1319"><em>xkcd</em></a> from happening.<br/>
                    <img class="resize" src="res/automationtransp.png"/></p>
            <hr/>
            <h1 class="motto">Find New Plugins</h1>
            <h2 class="submotto">Download reviewed plugins with simple URLs.</h2>
            <p>After a plugin developer submits a plugin to Poggit, it will be reviewed by Code Reviewers and Test
                Reviewers before it can be used by the public. Therefore, released plugins you download from Poggit are
                considered to be safe to use, and quality is generally promising.</p>
            <p>The plugin index is categorized, and each released plugin is versioned. You can also filter them by type
                of <span title="A spoon is a variant of PocketMine-MP. Examples include pmmp, Genisys, ClearSky, etc."
                         class="hover-title">spoon</span> that you use, number of downloads, ratings, etc.</p>
            <p><span onclick='window.location = <?= json_encode(Poggit::getRootPath() . "pi") ?>;' class="action">Look
                    for latest plugins</span></p>
            <hr/>
            <h1 class="motto">Build your Projects</h1>
            <h2 class="submotto">Create builds the moment you push to GitHub.</h2>
            <p>Poggit CI will set up webhooks in your repos to link to Poggit. When you push a commit to your repo,
                Poggit will create a development build. When you receive pull requests, Poggit also creates PR builds,
                so you can test the pull request by downloading a build from Poggit CI directly.</p>
            <p>Different plugin frameworks are supported. Currently, the normal one with a <code
                        class="code">plugin.yml</code>, and the NOWHERE framework, can be used.</p>
            <p>An online language manager can also be enabled. After you push some language files to your repo, there
                will be a webpage for online translator, and other people can help you translate your plugin to other
                languages. Then the poglang library will be compiled with your plugin, along with some language files
                contributed by the community.</p>
            <p><span onclick='login(<?= json_encode(Poggit::getSecret("meta.extPath") . "ci.cfg") ?>, true);'
                     class="action">Register with GitHub to setup projects</span></p>
            <hr/>
            <h1 class="motto">Lint for PocketMine Plugins</h1>
            <h2 class="submotto">Checks pull request before you can merge them.</h2>
            <p>After Poggit CI creates a build for your project, it will also execute lint on it. Basically, lint is
                something that checks if your code is having problems. See <a
                        href="<?= Poggit::getRootPath() ?>help.lint">Poggit Help: Lint</a> for what the lint checks.
            </p>
            <p>You can check out the lint result on the Poggit Build page. The lint result will also be uploaded to
                GitHub, in the form of status checks, which will do
                <a target="_blank" href="<?= Poggit::getRootPath() ?>ghhst">many cool things</a>.</p>
            <p class="remark">Note: Poggit cannot test the builds for you, but there is a script that you can put into
                your <a href="https://docs.travis-ci.com/user/getting-started/">Travis-CI</a> build, which will wait for
                and then download builds from Poggit for testing.</p>
        </div>
        <div class="guesthomepane2">
            <h4>Recent builds</h4>
            <?php
            if(isset($this->recentBuilds)) {
                foreach($this->recentBuilds as $build) {
                    ?>
                    <div class="brief-info">
                        <p class="recentbuildbox">
                        <a href="<?= Poggit::getRootPath() ?>ci/<?= $build->owner ?>/<?= $build->projectName ?>/<?= $build->projectName ?>/<?= (ProjectBuilder::$BUILD_CLASS_HUMAN[$build->class] . ":" ?? "") .  $build->internal ?>">
                                <?= htmlspecialchars($build->projectName) ?></a>
                            <span class="remark">(<?= $build->owner ?>/<?= $build->repoName ?>)<br/>
                                <?= ProjectBuilder::$BUILD_CLASS_HUMAN[$build->class] ?> Build #<?= $build->internal ?>
                                <br/>
                        Created <span class="time-elapse" data-timestamp="<?= $build->created ?>"></span> ago</span>
                        </p>
                    </div>
                <?php }
            } ?>
        </div>
        <?php
    }

    public function getMetaDescription(): string {
        return "Poggit is a GitHub-based plugin release platform, as well as a GitHub application for continuous integration for PocketMine-MP plugins.";
    }
}
