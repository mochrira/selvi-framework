<?php

// Selvi\Database\Manager::add('main', json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIG'),true));
Selvi\Database\Manager::add('mysql', json_decode(file_get_contents(BASEPATH. '/private/.DBCONFIGMYSQL'),true));