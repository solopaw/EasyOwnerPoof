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

namespace poggit\module\error;

use poggit\module\Module;
use const poggit\RES_DIR;

class BadRequestPage extends Module {
    public function getName(): string {
        return "err";
    }

    public function output() {
        http_response_code(500);
        ?>
        <html>
        <head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# object: http://ogp.me/ns/object# article: http://ogp.me/ns/article# profile: http://ogp.me/ns/profile#">
            <style type="text/css">
                <?php readfile(RES_DIR . "style.css") ?>
            </style>
            <title>400 Bad Request</title>
        </head>
        <body>
        <div id="body">
            <h1>400 Bad Request</h1>
            <p>You entered an invalid link that points to an invalid resource.</p>
            <p><?= $this->getQuery() ?></p>
        </div>
        </body>
        </html>
        <?php
    }
}
