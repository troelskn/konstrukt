@echo off
cd make-docs
php render_docs.php
cd ..
cd make-manual
php render_docs.php
cd ..