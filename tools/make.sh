#!/bin/sh
cd make-docs
php render_docs.php
cd ..
cd make-manual
php render_docs.php
cd ..
cd pear
php makepackage.php make
cd ..
