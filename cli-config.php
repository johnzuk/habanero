<?php
require 'index.php';
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($a->getEntityManager());