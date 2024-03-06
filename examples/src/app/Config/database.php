<?php

// var_dump(json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIG'),true));
Selvi\Database\Manager::add('main', json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIG'),true));